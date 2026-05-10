<?php

namespace Modules\FileStorage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\FileStorage\Domain\Models\File;
use Modules\FileStorage\Domain\Services\FileStorageService;

class FilesController extends Controller
{
    public function __construct(private readonly FileStorageService $files)
    {
    }

    public function presign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'folder' => 'required|string|max:128',
            'original_name' => 'required|string|max:255',
            'mime_type' => 'nullable|string|max:128',
            'size' => 'nullable|integer|min:1',
        ]);

        $key = $this->files->newPath($data['folder'], $data['original_name']);
        $signed = $this->files->presignUpload($key);

        return response()->json($signed);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'disk' => 'required|string',
            'path' => 'required|string',
            'original_name' => 'required|string',
            'mime_type' => 'nullable|string',
            'size' => 'nullable|integer|min:0',
            'checksum' => 'nullable|string',
            'metadata_json' => 'nullable|array',
        ]);

        $file = $this->files->record($data);
        return response()->json($file, 201);
    }

    public function show(File $file): JsonResponse
    {
        return response()->json($file);
    }

    public function download(File $file): JsonResponse
    {
        return response()->json([
            'url' => $this->files->signedReadUrl($file),
        ]);
    }

    public function destroy(File $file): JsonResponse
    {
        $this->files->delete($file);
        return response()->json(null, 204);
    }
}
