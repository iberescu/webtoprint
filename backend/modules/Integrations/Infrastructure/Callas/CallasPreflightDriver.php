<?php

namespace Modules\Integrations\Infrastructure\Callas;

use Illuminate\Support\Facades\Storage;
use Modules\FileStorage\Domain\Models\File;
use Modules\FileStorage\Domain\Services\FileStorageService;
use Modules\Integrations\Domain\Contracts\PreflightDriver;
use Symfony\Component\Process\Process;

/**
 * Wraps callas pdfToolbox CLI: runs the configured preflight profile against
 * the artwork PDF, parses the report, and returns the normalised summary.
 *
 * `callas` is a paid product; this driver assumes the binary path is in
 * services.callas.bin. If the binary is missing the driver returns 'error'
 * so the system can fall back gracefully to PDF-LIB-based checks.
 */
class CallasPreflightDriver implements PreflightDriver
{
    public function __construct(private readonly FileStorageService $files)
    {
    }

    public function key(): string
    {
        return 'callas';
    }

    public function check(File $artwork, string $profile): array
    {
        $bin = config('services.callas.bin');
        if (! $bin || ! is_executable($bin)) {
            return ['status' => 'error', 'summary' => ['reason' => 'callas binary not configured']];
        }

        $tmpIn = tempnam(sys_get_temp_dir(), 'pdf_');
        $tmpReport = tempnam(sys_get_temp_dir(), 'rpt_') . '.xml';

        try {
            $contents = Storage::disk($artwork->disk)->get($artwork->path);
            file_put_contents($tmpIn, $contents);

            $process = new Process([
                $bin,
                '--report=XML,Path=' . $tmpReport,
                '--profile=' . $profile,
                $tmpIn,
            ]);
            $process->setTimeout(120);
            $process->run();

            $report = file_exists($tmpReport) ? file_get_contents($tmpReport) : null;
            if (! $report) {
                return ['status' => 'error', 'summary' => ['reason' => 'no report produced']];
            }

            // Persist the report so the admin/UI can show it later.
            $reportPath = $this->files->newPath('preflight-reports', basename($tmpReport));
            Storage::disk(config('filesystems.default'))->put($reportPath, $report);
            $reportFile = $this->files->record([
                'disk' => config('filesystems.default'),
                'path' => $reportPath,
                'original_name' => "preflight-{$artwork->id}.xml",
                'mime_type' => 'application/xml',
                'size' => strlen($report),
                'attached' => true,
            ]);

            $status = $this->extractStatus($report);

            return [
                'status' => $status,
                'summary' => ['profile' => $profile],
                'report_file_id' => $reportFile->id,
            ];
        } finally {
            @unlink($tmpIn);
            @unlink($tmpReport);
        }
    }

    private function extractStatus(string $report): string
    {
        // Real callas XML reports have <Result Status="Error|Warning|Pass"/> nodes.
        if (str_contains($report, 'Status="Error"')) {
            return 'failed';
        }
        if (str_contains($report, 'Status="Warning"')) {
            return 'passed_with_warnings';
        }
        return 'passed';
    }
}
