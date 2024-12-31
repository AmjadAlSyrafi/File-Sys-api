<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\File;
use App\Models\FolderUserPermission;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Resources\FolderResource;
use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class FolderController extends Controller
{
    protected $permissionService;

    public function __construct(Func $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Fetch root folders accessible to the user
        $rootFolders = $this->permissionService->getAccessibleFolders(null, $user);

        // Ensure children and files are loaded with proper permissions
        $rootFolders->load([
            'children' => function ($query) use ($user) {
                $this->permissionService->applyChildFolderPermissions($query, $user);
            },
            'files' => function ($query) use ($user) {
                $this->permissionService->applyFilePermissions($query, $user);
            },
        ]);

        return response()->json([
            'message' => 'Folders retrieved successfully',
            'folders' => FolderResource::collection($rootFolders), // FolderResource now filters files via FileResource
        ]);
    }


    /**
     * Store a newly created folder.
     */
    public function store(StoreFolderRequest $request)
    {
        $validated = $request->validated();
        $parentFolder = Folder::find($validated['parent_id']);

        // Check write permissions for parent folder
        if ($parentFolder && !$this->permissionService->canWrite($request->user(), $parentFolder)) {
            return response()->json(['message' => 'Forbidden: No write permission'], 403);
        }

        $folder = new Folder([
            'name' => $validated['name'],
            'user_id' => $request->user()->id,
        ]);

        $parentFolder ? $parentFolder->appendNode($folder) : $folder->saveAsRoot();

        return response()->json([
            'message' => 'Folder created successfully',
            'folder' => new FolderResource($folder),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $folder = Folder::findOrFail($id);

        if (!$this->permissionService->canRead($user, $folder)) {
            return response()->json(['message' => 'Forbidden: No read permission'], 403);
        }

        $accessibleChildren = $this->permissionService->getAccessibleChildren($folder, $user);
        $accessibleFiles = $this->permissionService->getAccessibleFiles($folder, $user);

        return response()->json([
            'message' => 'Folder retrieved successfully',
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
                'children' => FolderResource::collection($accessibleChildren),
                'files' => FileResource::collection($accessibleFiles),
            ],
        ]);
    }

    /**
     * Update the specified folder.
     */
    public function update(Request $request, $id)
    {
        $folder = Folder::findOrFail($id);
        $user = $request->user();

        if (!$this->permissionService->canWrite($user, $folder)) {
            return response()->json(['message' => 'Forbidden: No write permission'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:folders,id',
                'different:id',
                function ($attribute, $value, $fail) use ($folder) {
                    if ($value && $folder->descendants()->pluck('id')->contains($value)) {
                        $fail('You cannot move a folder into one of its descendants.');
                    }
                },
            ],
        ]);

        if (isset($validated['name'])) {
            $folder->update(['name' => $validated['name']]);
        }

        if (isset($validated['parent_id'])) {
            $newParent = Folder::find($validated['parent_id']);
            $folder->appendToNode($newParent)->save();
        }

        return response()->json([
            'message' => 'Folder updated successfully',
            'folder' => new FolderResource($folder),
        ]);
    }

    /**
     * Remove the specified folder.
     */
    public function destroy($id)
    {
        $folder = Folder::findOrFail($id);
        $user = request()->user();

        if (!$this->permissionService->hasFullAccess($user, $folder)) {
            return response()->json(['message' => 'Unauthorized: Only the owner can delete this folder'], 403);
        }

        $folder->delete();

        return response()->json([
            'message' => 'Folder deleted successfully',
        ]);
    }

    /**
     * Update user-specific permissions.
     */
    public function updateUserPermissions(Request $request, $id)
    {
        $folder = Folder::findOrFail($id);
        $user = $request->user();

        if (!$this->permissionService->hasFullAccess($user, $folder)) {
            return response()->json(['message' => 'Unauthorized: Only the owner can update permissions'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'required|string|in:read,write,full_access,private',
        ]);

        $folder->userPermissions()->updateOrCreate(
            ['user_id' => $validated['user_id']],
            ['permissions' => $validated['permissions']]
        );

        $this->cascadePermissions($folder, $validated['user_id'], $validated['permissions']);

        return response()->json(['message' => 'User permissions updated successfully']);
    }

    /**
     * Set folder permissions to private.
     */
    public function setPrivate(Request $request, $id)
    {
        $folder = Folder::findOrFail($id);
        $user = $request->user();

        if (!$this->permissionService->hasFullAccess($user, $folder)) {
            return response()->json(['message' => 'Unauthorized: Only the owner can set permissions'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        FolderUserPermission::updateOrCreate(
            ['folder_id' => $folder->id, 'user_id' => $validated['user_id']],
            ['permissions' => 'private']
        );

        return response()->json([
            'message' => 'User permissions set to private successfully',
            'folder' => new FolderResource($folder),
        ]);
    }

    /**
     * Search within the folder and its descendants.
     */
    public function search(Request $request, $id)
    {
        $user = $request->user();
        $searchTerm = $request->input('query');

        $request->validate([
            'query' => 'required|string|max:255',
        ]);

        $folder = Folder::findOrFail($id);

        if (!$this->permissionService->canRead($user, $folder)) {
            return response()->json(['message' => 'Forbidden: No read permission'], 403);
        }

        $accessibleFolders = $this->permissionService->getRootAndDescendants($folder)->filter(function ($descendant) use ($user) {
            return $this->permissionService->canRead($user, $descendant);
        });

        $folderIds = $accessibleFolders->pluck('id');

        $files = File::whereIn('folder_id', $folderIds)
            ->where('name', 'LIKE', "%{$searchTerm}%")
            ->get();

        $folders = Folder::whereIn('id', $folderIds)
            ->where('name', 'LIKE', "%{$searchTerm}%")
            ->get();

        return response()->json([
            'message' => 'Search results retrieved successfully',
            'folders' => FolderResource::collection($folders),
            'files' => FileResource::collection($files),
        ]);
    }

     // Cascade permissions to descendants.

    protected function cascadePermissions(Folder $folder, $userId, $permissions)
    {
        $descendantIds = $folder->descendants()->pluck('id');

        if ($descendantIds->isEmpty()) {
            return;
        }

        $bulkData = $descendantIds->map(fn($descendantId) => [
            'folder_id' => $descendantId,
            'user_id' => $userId,
            'permissions' => $permissions,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        FolderUserPermission::upsert($bulkData, ['folder_id', 'user_id'], ['permissions', 'updated_at']);
    }

    private function addFolderToZip(Folder $folder, ZipArchive $zip, string $parentPath = '', $user = null)
    {
        // Build the folder path in the ZIP
        $currentPath = $parentPath . $folder->name . '/';

        //even if it's empty
        $zip->addEmptyDir($currentPath);

        //Add files in this folder
        foreach ($folder->files as $file) {
            if ($this->permissionService->canRead($user, $file)) {
                $filePath = Storage::path($file->path);
                if (file_exists($filePath)) {
                    // In the ZIP, it will appear under "My Folder/fileName"
                    $zip->addFile($filePath, $currentPath . $file->name);
                } else {
                    Log::warning("File not found: {$filePath}");
                }
            }
        }

        // Recurse into subfolders
        foreach ($folder->children as $childFolder) {
            if ($this->permissionService->canRead($user, $childFolder)) {
                $this->addFolderToZip($childFolder, $zip, $currentPath, $user);
            }
        }
    }

    // Download and Create the Zip File

    public function download(Request $request, $folderId)
    {
        // 1) Auth checks
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $folder = Folder::findOrFail($folderId);

        if (!$this->permissionService->canRead($user, $folder)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $zipFileName = 'folder_' . $folderId . '_' . uniqid() . '.zip';
        $zipRelativePath = 'tmp/' . $zipFileName; // relative to the private disk

        if (!Storage::disk('private')->exists('tmp')) {
            Storage::disk('private')->makeDirectory('tmp');
        }

        // 3) Create the ZIP file
        $zip = new ZipArchive();
        $zipFullPath = Storage::disk('private')->path($zipRelativePath);

        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // Add folder & subfolders
            $this->addFolderToZip($folder, $zip, '', $user);
            $zip->close();
        } else {
            return response()->json(['message' => 'Failed to create ZIP file'], 500);
        }

        // 4) Ensure the ZIP actually exists
        if (!Storage::disk('private')->exists($zipRelativePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // 5) Generate a temporary signed download link

        $downloadLink = URL::temporarySignedRoute(
            'serveDownload',
            now()->addMinutes(15),
            [
                'folderId' => $folderId,
                'filePath' => "tmp/$zipFileName",
            ]
        );


        // 6) Return a clean JSON response
        return response()->json([
            'download_link' => $downloadLink,
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    // Direct link for Download
    public function serveDownload(Request $request, $folderId, $filePath)
    {
        // 1) Verify the request is still signed (not tampered/expired)
        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired link'], 403);
        }

        // 2) Auth checks
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $folder = Folder::findOrFail($folderId);
        if (!$this->permissionService->canRead($user, $folder)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 3) Decode file path
        $filePath = urldecode($filePath);

        // 4) Ensure the file exists on the private disk
        if (!Storage::disk('private')->exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // 5) Serve the file for download from the private disk
        $absolutePath = Storage::disk('private')->path($filePath);
        return response()->download($absolutePath)->deleteFileAfterSend(true);
    }

}
