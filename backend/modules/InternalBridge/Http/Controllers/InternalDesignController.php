<?php

namespace Modules\InternalBridge\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Designer\Domain\Models\Design;

class InternalDesignController extends Controller
{
    /**
     * The shop calls this:
     *   - on cart-add (sanity-check the design exists)
     *   - on checkout (refuse to place orders with failed preflight)
     */
    public function preflight(string $id): JsonResponse
    {
        $design = Design::query()->findOrFail($id);

        return response()->json([
            'id' => $design->id,
            'preflight_status' => $design->preflight_status,
            'status' => $design->status,
            'print_pdf_file_id' => $design->print_pdf_file_id,
        ]);
    }
}
