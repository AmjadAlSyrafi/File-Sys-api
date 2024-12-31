<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kalnoy\Nestedset\NodeTrait;

class Folder extends Model
{
    use HasFactory, NodeTrait;

    protected $fillable = ['name', 'parent_id', 'user_id', 'permissions'];
    // Define relationships
    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
{
    parent::boot();

    static::deleting(function ($folder) {
        // Delete all descendant folders
        $folder->descendants()->delete();

        // Delete all files in this folder and its descendants
        File::whereIn('folder_id', $folder->descendants()->pluck('id')->push($folder->id))
            ->delete();
    });
}


    // Update permissions and cascade to descendants
    public function updatePermissions($newPermissions)
    {
        $this->update(['permissions' => $newPermissions]);

        // Update permissions for descendants
        $this->descendants()->update(['permissions' => $newPermissions]);

        // Update permissions for files in this folder and descendants
        File::whereIn('folder_id', $this->descendants()->pluck('id')->push($this->id))
            ->update(['permissions' => $newPermissions]);
    }

    public function getEffectivePermissionsAttribute()
    {
        if (auth()->check() && $this->user_id === auth()->id()) {
            return 'full_access';
        }

        return $this->attributes['permissions'];
    }

    public function isOwner($user)
    {
        return $this->user_id === $user->id;
    }

    public function userPermissions()
    {
        return $this->hasMany(FolderUserPermission::class);
    }

    public function hasSpecificPermission($user, $requiredPermission)
    {
        // Owner has full access
        if ($this->user_id === $user->id) {
            return true;
        }

        // Private folder (null permissions) is inaccessible to everyone but the owner
        if ($this->permissions === null) {
            return false;
        }

        // Private folder check (only owner can access)
        if ($this->permissions === 'private') {
            return false;
        }

        // Check explicit permissions for the user
        $permission = $this->userPermissions()
            ->where('user_id', $user->id)
            ->value('permissions');

        if ($requiredPermission === 'read') {
            return in_array($permission, ['read', 'write', 'full_access']);
        }

        if ($requiredPermission === 'full_access') {
            return $permission === 'full_access';
        }

        return false; // Default deny
    }

    public function resolvePermissions($user)
    {
        // Check explicit folder permissions
        $explicitPermission = $this->userPermissions()
            ->where('user_id', $user->id)
            ->value('permissions');

        if ($explicitPermission) {
            return $explicitPermission;
        }

        // Fallback to parent folder permissions
        if ($this->parent) {
            return $this->parent->resolvePermissions($user);
        }

        return null; // No permissions found
    }


}
