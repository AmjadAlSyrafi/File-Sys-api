<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{


    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $request->user();


    // Exclude the file if the user does not have read access
    if (!$this->hasSpecificPermission($user, 'read')) {
        return null; // Skip this file
    }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'size' => $this->size,
            'type' => $this->type,
            'folder_id' => $this->folder_id,
            'user_id' => $this->user_id,
            'permissions' => $this->resolvePermissions($user),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    /**
     * Resolve the effective permissions for the file.
     *
     * @param \App\Models\User $user
     * @return string|null
     */
    protected function resolvePermissions($user)
    {
        // Check explicit file permissions
        $explicitPermission = $this->userPermissions()
            ->where('user_id', $user->id)
            ->value('permissions');

        if ($explicitPermission) {
            return $explicitPermission;
        }

        // Fallback to parent folder permissions
        if ($this->folder) {
            return $this->folder->resolvePermissions($user);
        }

        return null; // No permissions found
    }
}
