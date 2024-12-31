<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Http\Requests\StoreFileRequest;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateFileRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\FileResource;


class FileController extends Controller
{

    protected $permissionService;

    public function __construct(Func $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFileRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();
        $path = $request->file('file')->store('files/' . auth()->id());

        $file = File::create([
            'name' => $request->file('file')->getClientOriginalName(),
            'path' => $path,
            'size' => $request->file('file')->getSize(),
            'type' => $request->file('file')->getMimeType(),
            'user_id' => auth()->id(),
            'folder_id' => $validated['folder_id'],
        ]);

        // Check if the user has write permissions
        if (!$this->permissionService->canWrite($user ,$file)) {
            return response()->json(['message' => 'Forbidden: No write permission'], 403);
        }

        return response()->json($file, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $file = File::findOrFail($id);

        if (!$file->canRead()) {
            return response()->json(['message' => 'Forbidden: No read permission'], 403);
        }

        return response()->download(Storage::path($file->path));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(File $file)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFileRequest $request, File $file)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $file = File::findOrFail($id);

        Storage::delete($file->path); // Delete the physical file
        $file->delete(); // Delete the database record

        return response()->json(['message' => 'File deleted successfully']);
    }

    public function updatePermissions(Request $request, $id)
    {
        $user = $request->user();

        // Find the file
        $file = File::findOrFail($id);

        // Check if the user is the owner of the file
        if (!$file->isOwner($user)) {
            return response()->json(['message' => 'Unauthorized: Only the owner can change permissions'], 403);
        }

        // Validate the request
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'required|string|in:read,write,full_access,private',
        ]);

        // Update or create the file-specific user permission
        $file->userPermissions()->updateOrCreate(
            ['user_id' => $validated['user_id']],
            ['permissions' => $validated['permissions']]
        );

        return response()->json([
            'message' => 'File permissions updated successfully',
            'file' => new FileResource($file),
        ], 200);
    }



}
