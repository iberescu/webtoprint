<?php

namespace Modules\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Templates\Domain\ContentTemplate;

class ContentTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ContentTemplate::query();
        if ($kind = $request->query('kind')) {
            $query->where('kind', $kind);
        }
        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $template = ContentTemplate::query()->create($this->validated($request));
        $template->snapshot(optional($request->user())->id);
        return response()->json($template, 201);
    }

    public function show(ContentTemplate $template): JsonResponse
    {
        return response()->json($template->load('versions'));
    }

    public function update(Request $request, ContentTemplate $template): JsonResponse
    {
        $template->update($this->validated($request));
        $template->snapshot(optional($request->user())->id);
        return response()->json($template);
    }

    public function destroy(ContentTemplate $template): JsonResponse
    {
        $template->delete();
        return response()->json(null, 204);
    }

    public function versions(ContentTemplate $template): JsonResponse
    {
        return response()->json(['data' => $template->versions]);
    }

    public function restore(Request $request, ContentTemplate $template, int $version): JsonResponse
    {
        $vRow = $template->versions()->where('version', $version)->firstOrFail();
        $template->update(['body' => $vRow->body]);
        $template->snapshot(optional($request->user())->id);
        return response()->json($template);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'key' => 'required|string|max:128',
            'kind' => 'required|in:jobsheet_pdf,jdf_xml,mxml_xml,folder_name,file_name',
            'body' => 'required|string',
            'engine' => 'nullable|in:blade,twig,raw',
            'metadata_json' => 'nullable|array',
        ]);
    }
}
