<?php

namespace Modules\Designer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Designer\Domain\Models\DesignTemplate;

class DesignerTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DesignTemplate::query()->where('status', 'published');
        if ($pid = $request->query('product_id')) {
            $query->where('product_id', $pid);
        }
        return response()->json(['data' => $query->with('thumbnail')->get()]);
    }

    public function show(DesignTemplate $template): JsonResponse
    {
        return response()->json($template->load('thumbnail', 'product'));
    }

    public function store(Request $request): JsonResponse
    {
        $template = DesignTemplate::query()->create($this->validated($request));
        return response()->json($template, 201);
    }

    public function update(Request $request, DesignTemplate $template): JsonResponse
    {
        $template->update($this->validated($request));
        return response()->json($template);
    }

    public function destroy(DesignTemplate $template): JsonResponse
    {
        $template->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:draft,published,archived',
            'width_mm' => 'required|integer|min:1|max:5000',
            'height_mm' => 'required|integer|min:1|max:5000',
            'bleed_mm' => 'nullable|integer|min:0|max:50',
            'safe_margin_mm' => 'nullable|integer|min:0|max:50',
            'page_count' => 'nullable|integer|min:1|max:1000',
            'thumbnail_file_id' => 'nullable|uuid|exists:files,id',
            'template_json' => 'nullable|array',
        ]);
    }
}
