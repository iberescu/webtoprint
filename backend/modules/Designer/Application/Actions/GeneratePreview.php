<?php

namespace Modules\Designer\Application\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Designer\Domain\Models\Design;
use Modules\FileStorage\Domain\Services\FileStorageService;

/**
 * Renders a preview image for a design using ImageMagick / poppler.
 * The MVP path:
 *   1. Read design_json fabric pages.
 *   2. Render an HTML/SVG snapshot per page (deferred to designer service).
 *   3. Place each page as PNG via Imagick.
 *
 * This implementation is the queue/orchestration shell — the actual rendering
 * happens in the dedicated designer service (separate Node process) that
 * receives the design_json over an internal API. This action stores the
 * resulting PNG and updates the design.
 */
class GeneratePreview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $designId)
    {
    }

    public function handle(FileStorageService $files): void
    {
        $design = Design::query()->findOrFail($this->designId);

        // Placeholder: in production we POST design_json to the designer service
        // and receive back a PNG stream. Here we record a placeholder file.
        $path = $files->newPath('previews', $design->id . '.png');
        // Real implementation would $files->disk()->put($path, $bytes).

        $file = $files->record([
            'disk' => config('filesystems.default'),
            'path' => $path,
            'original_name' => $design->id . '.png',
            'mime_type' => 'image/png',
            'size' => 0,
            'attached' => true,
            'metadata_json' => ['kind' => 'design_preview'],
        ]);

        $design->forceFill([
            'preview_file_id' => $file->id,
            'status' => 'preview_generated',
        ])->save();
    }
}
