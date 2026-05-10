<?php

namespace Modules\Designer\Application\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Designer\Domain\Models\Design;
use Modules\Integrations\Domain\Services\PreflightManager;

/**
 * Spec §17 prepress hardening: dispatched after a print PDF is generated
 * or a PDF is uploaded. Stores the normalised report on the design and
 * sets preflight_status; downstream the OrderPlaced flow refuses to
 * dispatch a ProductionJob if any item has preflight_status=failed.
 */
class RunArtworkPreflight implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $designId,
        public readonly string $profile = 'PDFX-1a:2003',
        public readonly ?string $driver = null,
    ) {
    }

    public function handle(PreflightManager $preflight): void
    {
        $design = Design::query()->with('printPdf')->findOrFail($this->designId);
        if (! $design->printPdf) {
            $design->forceFill([
                'preflight_status' => 'error',
                'preflight_summary_json' => ['reason' => 'no print pdf'],
                'preflight_checked_at' => now(),
                'preflight_profile' => $this->profile,
            ])->save();
            return;
        }

        $design->forceFill(['preflight_status' => 'checking'])->save();

        $result = $preflight->driver($this->driver)->check($design->printPdf, $this->profile);

        $design->forceFill([
            'preflight_status' => $result['status'],
            'preflight_summary_json' => $result['summary'] ?? [],
            'preflight_report_file_id' => $result['report_file_id'] ?? null,
            'preflight_checked_at' => now(),
            'preflight_profile' => $this->profile,
        ])->save();
    }
}
