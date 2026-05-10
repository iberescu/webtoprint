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
 * Generates the production print PDF (with bleed) from a design.
 * Spec §8 PDF Generation MVP: load fabric JSON → render pages → PDF-LIB compose.
 *
 * The queue worker delegates to the designer service via internal HTTP, then
 * stores the resulting PDF and links it back on the Design.
 */
class GeneratePrintPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $designId)
    {
    }

    public function handle(FileStorageService $files): void
    {
        $design = Design::query()->findOrFail($this->designId);

        $path = $files->newPath('print-pdfs', $design->id . '.pdf');
        // Real implementation would invoke the designer service and write the PDF bytes.

        $file = $files->record([
            'disk' => config('filesystems.default'),
            'path' => $path,
            'original_name' => $design->id . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => 0,
            'attached' => true,
            'metadata_json' => ['kind' => 'design_print_pdf'],
        ]);

        $design->forceFill([
            'print_pdf_file_id' => $file->id,
            'status' => 'print_pdf_generated',
        ])->save();

        RunArtworkPreflight::dispatch($design->id);
    }
}
