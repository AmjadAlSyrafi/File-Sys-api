<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'path', 'size', 'type', 'user_id', 'folder_id'];

    /**
     * Define relationship with FileUserPermission.
     */
    public function userPermissions()
    {
        return $this->hasMany(FileUserPermission::class);
    }

    /**
     * Define relationship with Folder.
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Check if the user has specific permission for the file.
     */
    public function hasSpecificPermission($user, $action)
    {
        // Owner has full access
        if ($this->isOwner($user)) {
            return true;
        }

        // Check explicit file permissions
        $explicitPermission = $this->userPermissions()
            ->where('user_id', $user->id)
            ->value('permissions');

        if ($explicitPermission === 'private') {
            return false; // Private files are inaccessible except by the owner
        }

        if ($explicitPermission) {
            return in_array($explicitPermission, $this->getPermissionsForAction($action));
        }

        // Inherit permissions from parent folder if no explicit file permission
        if ($this->folder) {
            return $this->folder->hasSpecificPermission($user, $action);
        }

        return false;
    }

    /**
     * Map action to allowed permissions.
     */
    protected function getPermissionsForAction($action)
    {
        switch ($action) {
            case 'read':
                return ['read', 'write', 'full_access'];
            case 'write':
                return ['write', 'full_access'];
            case 'full_access':
                return ['full_access'];
            default:
                return [];
        }
    }

    /**
     * Check if the user is the owner of the file.
     */
    public function isOwner($user)
    {
        return $this->user_id === $user->id;
    }
}
