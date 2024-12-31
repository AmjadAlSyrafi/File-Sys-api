<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use App\Models\Folder;
use App\Models\File;
use App\Models\User;

class Func
{
    /**
     * Check if the user can read the resource (Folder or File).
     */
    public function canRead(User $user, $resource): bool
    {
        if ($resource instanceof Folder) {
            return $this->canReadFolder($user, $resource);
        } elseif ($resource instanceof File) {
            return $this->canReadFile($user, $resource);
        }
        return false;
    }

    /**
     * Check if the user can write to the resource (Folder or File).
     */
    public function canWrite(User $user, $resource): bool
    {
        if ($resource instanceof Folder) {
            return $this->canWriteFolder($user, $resource);
        } elseif ($resource instanceof File) {
            return $this->canWriteFile($user, $resource);
        }
        return false;
    }

    /**
     * Check if the user can read the folder.
     */
    protected function canReadFolder(User $user, Folder $folder): bool
    {
        return $this->isOwner($user, $folder) || $this->hasFolderPermission($user, $folder, 'read');
    }

    /**
     * Check if the user can read the file.
     */
    protected function canReadFile(User $user, File $file): bool
    {
        if ($this->isOwner($user, $file)) {
            return true;
        }

        $explicitPermission = $this->getFilePermission($user, $file);
        if ($explicitPermission) {
            return in_array($explicitPermission, ['read', 'write', 'full_access']);
        }

        return $this->canReadFolder($user, $file->folder);
    }

    /**
     * Check if the user can write to the folder.
     */
    protected function canWriteFolder(User $user, Folder $folder): bool
    {
        return $this->isOwner($user, $folder) || $this->hasFolderPermission($user, $folder, 'write');
    }

    /**
     * Check if the user can write to the file.
     */
    protected function canWriteFile(User $user, File $file): bool
    {
        if ($this->isOwner($user, $file)) {
            return true;
        }

        $explicitPermission = $this->getFilePermission($user, $file);
        if ($explicitPermission) {
            return in_array($explicitPermission, ['write', 'full_access']);
        }

        return $this->canWriteFolder($user, $file->folder);
    }

    /**
     * Check if the user has full access to the folder.
     */
    public function hasFullAccess(User $user, Folder $folder): bool
    {
        return $this->isOwner($user, $folder);
    }

    /**
     * Determine if the user is the owner of the resource.
     */
    private function isOwner(User $user, $resource): bool
    {
        return $resource->user_id === $user->id;
    }

    /**
     * Get the user's specific permission for the folder.
     */
    private function getFolderPermission(User $user, Folder $folder): ?string
    {
        return $folder->userPermissions()
            ->where('user_id', $user->id)
            ->value('permissions');
    }

    /**
     * Get the user's specific permission for the file.
     */
    private function getFilePermission(User $user, File $file): ?string
    {
        return $file->userPermissions()
            ->where('user_id', $user->id)
            ->value('permissions');
    }

    /**
     * Check if the user has a specific permission for the folder.
     */
    private function hasFolderPermission(User $user, Folder $folder, string $permission): bool
    {
        $userPermission = $this->getFolderPermission($user, $folder);
        return in_array($userPermission, $this->getPermissionsForAction($permission));
    }

    /**
     * Get valid permissions for the given action.
     */
    private function getPermissionsForAction(string $action): array
    {
        switch ($action) {
            case 'read':
                return ['read','full_access'];
            case 'write':
                return ['read','write', 'full_access'];
            case 'full_access':
                return ['full_access'];
            default:
                return [];
        }
    }

    /**
     * Get the root folder and its descendants using nested set.
     */
    public function getRootAndDescendants(Folder $rootFolder): Collection
    {
        return Folder::where('_lft', '>=', $rootFolder->_lft)
            ->where('_rgt', '<=', $rootFolder->_rgt)
            ->orderBy('_lft')
            ->get();
    }

    /**
     * Get accessible child folders for the user.
     */
    public function getAccessibleChildren(Folder $folder, User $user): Collection
    {
        return Folder::where('parent_id', $folder->id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('userPermissions', function ($subQuery) use ($user) {
                        $subQuery->where('user_id', $user->id)
                            ->whereIn('permissions', ['read', 'write', 'full_access']);
                    });
            })
            ->get();
    }
    
    /**
     * Get accessible files in the folder for the user.
     */
    public function getAccessibleFiles(Folder $folder, User $user): Collection
    {
        return $folder->files()
            ->where(function ($query) use ($user, $folder) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('userPermissions', function ($subQuery) use ($user) {
                        $subQuery->where('user_id', $user->id)
                            ->whereIn('permissions', ['read', 'write', 'full_access']);
                    });
            })
            ->get();
    }

    /**
     * Apply child folder permissions for the query.
     */
    public function applyChildFolderPermissions($query, User $user)
    {
        $query->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('userPermissions', function ($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id)
                        ->whereIn('permissions', ['read', 'write', 'full_access']);
                });
        });
    }

    /**
     * Apply file permissions for the query.
     */
    public function applyFilePermissions($query, User $user)
    {
        $query->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('folder.userPermissions', function ($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id)
                        ->whereIn('permissions', ['read', 'write', 'full_access']);
                });
        });
    }

/**
 * Get accessible folders for a user, either as the owner or through explicit permissions.
 */
public function getAccessibleFolders($parentId, User $user)
{
    // Fetch folders accessible to the user
    return Folder::where('parent_id', $parentId)
        ->where(function ($query) use ($user) {
            $query->where('user_id', $user->id) // Owner
                ->orWhereHas('userPermissions', function ($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id)
                        ->whereIn('permissions', ['read', 'write', 'full_access']);
                });
        })
        ->get();
}

}
