<?php

namespace Modules\Designer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Designer\Application\Actions\GeneratePreview;
use Modules\Designer\Application\Actions\GeneratePrintPdf;
use Modules\Designer\Domain\Models\Design;
use Modules\FileStorage\Domain\Services\FileStorageService;

class DesignerDesignController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'template_id' => 'nullable|uuid|exists:design_templates,id',
            'configuration_json' => 'nullable|array',
            'design_json' => 'nullable|array',
        ]);

        $design = Design::query()->create($data + ['status' => 'draft', 'source' => 'designer']);
        return response()->json($design, 201);
    }

    public function show(Design $design): JsonResponse
    {
        return response()->json($design->load('preview', 'printPdf'));
    }

    public function update(Request $request, Design $design): JsonResponse
    {
        $design->update($request->validate([
            'configuration_json' => 'nullable|array',
            'design_json' => 'nullable|array',
        ]));
        return response()->json($design);
    }

    public function preview(Design $design): JsonResponse
    {
        GeneratePreview::dispatch($design->id);
        return response()->json(['queued' => true, 'design_id' => $design->id]);
    }

    public function generatePrintPdf(Design $design): JsonResponse
    {
        GeneratePrintPdf::dispatch($design->id);
        return response()->json(['queued' => true, 'design_id' => $design->id]);
    }

    public function approve(Design $design): JsonResponse
    {
        $design->forceFill(['status' => 'approved', 'approved_at' => now()])->save();
        // Once approved, we can fire-and-forget the print pdf generation.
        if (! $design->print_pdf_file_id) {
            GeneratePrintPdf::dispatch($design->id);
        }
        return response()->json($design);
    }

    /**
     * Receives a print-ready PDF generated client-side by the designer
     * (PDF-LIB) and persists it via FileStorage. The design's
     * `print_pdf_file_id` is updated so the proof can later be retrieved by
     * any consumer (admin panel, distribution job, etc.).
     */
    public function uploadPrintPdf(Request $request, Design $design, FileStorageService $files): JsonResponse
    {
        $request->validate([
            'pdf' => 'required|file|mimetypes:application/pdf|max:25600', // 25 MB
        ]);

        $upload = $request->file('pdf');
        $contents = file_get_contents($upload->getRealPath());

        $path = $files->newPath('print-pdfs', "design-{$design->id}.pdf");
        Storage::disk(config('filesystems.default'))->put($path, $contents);

        $file = $files->record([
            'disk' => config('filesystems.default'),
            'path' => $path,
            'original_name' => "design-{$design->id}.pdf",
            'mime_type' => 'application/pdf',
            'size' => strlen($contents),
            'attached' => true,
            'metadata_json' => ['kind' => 'design_print_pdf', 'design_id' => $design->id, 'source' => 'pdf-lib-client'],
        ]);

        $design->forceFill([
            'print_pdf_file_id' => $file->id,
            'status' => 'print_pdf_generated',
        ])->save();

        return response()->json([
            'design_id' => $design->id,
            'print_pdf_file_id' => $file->id,
            'size' => strlen($contents),
            'sha256' => hash('sha256', $contents),
        ]);
    }
}
