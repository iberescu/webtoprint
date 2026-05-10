<?php

namespace Modules\Designer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Designer\Application\Actions\GeneratePreview;
use Modules\Designer\Application\Actions\GeneratePrintPdf;
use Modules\Designer\Domain\Models\Design;

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
}
