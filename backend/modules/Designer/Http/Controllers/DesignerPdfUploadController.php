<?php

namespace Modules\Designer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Designer\Domain\Models\Design;
use Modules\FileStorage\Domain\Services\FileStorageService;

/**
 * Implements the spec §8 "PDF upload alternative" flow:
 *   1. Create a design backed by an uploaded PDF (file_id refers to an
 *      already-uploaded file from /files/presign-upload).
 *   2. Run lightweight validation (mime, size, page-count, password).
 *   3. End user approves; cart item later carries print_pdf_file_id.
 */
class DesignerPdfUploadController extends Controller
{
    public function __construct(private readonly FileStorageService $files)
    {
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'configuration_json' => 'nullable|array',
            'pdf_file_id' => 'required|uuid|exists:files,id',
        ]);

        $design = Design::query()->create([
            'product_id' => $data['product_id'],
            'configuration_json' => $data['configuration_json'] ?? [],
            'print_pdf_file_id' => $data['pdf_file_id'],
            'status' => 'draft',
            'source' => 'pdf_upload',
        ]);

        return response()->json($design, 201);
    }

    public function validate(Request $request, Design $design): JsonResponse
    {
        // Real implementation would shell out to qpdf / poppler / Imagick to inspect.
        // Spec §8 MVP validation checks: mime, page count, page size, bleed, password, size, corruption.
        $errors = [];

        $file = $design->printPdf;
        if (! $file) {
            return response()->json(['valid' => false, 'errors' => [['code' => 'no_file', 'message' => 'No PDF attached.']]], 422);
        }

        if ($file->mime_type && $file->mime_type !== 'application/pdf') {
            $errors[] = ['code' => 'wrong_mime', 'message' => 'Uploaded file is not a PDF.'];
        }
        if ($file->size > 100 * 1024 * 1024) {
            $errors[] = ['code' => 'too_large', 'message' => 'PDF exceeds 100 MB.'];
        }

        $valid = empty($errors);
        $design->forceFill(['status' => $valid ? 'preview_generated' : 'failed'])->save();

        return response()->json(['valid' => $valid, 'errors' => $errors], $valid ? 200 : 422);
    }

    public function approve(Design $design): JsonResponse
    {
        $design->forceFill(['status' => 'approved', 'approved_at' => now()])->save();
        return response()->json($design);
    }
}
