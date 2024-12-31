<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // Retrieve user-specific permission for the authenticated user
        $userPermission = $this->userPermissions()
        ->where('user_id', auth()->id())
        ->value('permissions');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'children' => FolderResource::collection($this->whenLoaded('children')),
            'files' => FileResource::collection($this->whenLoaded('files')),
            'permission' => $userPermission //?? $this->permissions,
        ];
    }
}
