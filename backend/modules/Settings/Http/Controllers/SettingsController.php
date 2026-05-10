<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Settings\Domain\Services\SettingsRepository;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsRepository $settings)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->all($request->query('group')),
        ]);
    }

    public function show(string $key): JsonResponse
    {
        return response()->json(['key' => $key, 'value' => $this->settings->get($key)]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $data = $request->validate([
            'value' => 'present',
            'group' => 'nullable|string',
        ]);

        $this->settings->set($key, $data['value'], $data['group'] ?? 'general');
        return response()->json(['key' => $key, 'value' => $this->settings->get($key)]);
    }

    public function destroy(string $key): JsonResponse
    {
        $this->settings->forget($key);
        return response()->json(null, 204);
    }
}
