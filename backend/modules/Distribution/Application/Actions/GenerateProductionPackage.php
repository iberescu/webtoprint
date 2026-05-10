<?php

namespace Modules\Distribution\Application\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\Distribution\Domain\Models\ProductionJob;
use Modules\Distribution\Domain\Services\TemplateRenderer;
use Modules\Distribution\Events\ProductionPackageGenerated;
use Modules\FileStorage\Domain\Services\FileStorageService;
use ZipArchive;

/**
 * Spec §9: orchestrates jobsheet (HTML/PDF) + JDF + MXML + metadata + ZIP.
 *
 * Reads only `configuration_snapshot_json` and the artwork file. No
 * dependency on any ecommerce model — replacing Vanilo with Shopify changes
 * nothing here.
 */
class GenerateProductionPackage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $productionJobId)
    {
    }

    public function handle(TemplateRenderer $renderer, FileStorageService $files): void
    {
        $job = ProductionJob::query()->with('artwork')->findOrFail($this->productionJobId);
        $job->forceFill(['status' => 'generating'])->save();

        try {
            $vars = [
                'job' => $job,
                'snapshot' => $job->configuration_snapshot_json ?? [],
                'files' => [
                    'artwork' => $job->artwork ? $files->signedReadUrl($job->artwork, 60 * 24 * 7) : null,
                ],
            ];

            $jobsheetHtml = $renderer->render('jobsheet.blade.html', $vars);
            $jdfXml = $renderer->render('jdf.blade.xml', $vars);
            $mxmlXml = $renderer->render('mxml.blade.xml', $vars);

            $jobsheetFile = $this->store($files, "jobsheets/{$job->id}.html", $jobsheetHtml, 'text/html');
            $jdfFile = $this->store($files, "jdf/{$job->id}.jdf", $jdfXml, 'application/vnd.cip4-jdf+xml');
            $mxmlFile = $this->store($files, "mxml/{$job->id}.mxml", $mxmlXml, 'application/xml');

            $packageFile = $this->store(
                $files, "packages/{$job->id}.zip",
                $this->buildZip($job, $jobsheetHtml, $jdfXml, $mxmlXml, $files),
                'application/zip',
                $this->packageFilename($job),
            );

            $job->forceFill([
                'jobsheet_file_id' => $jobsheetFile->id,
                'jdf_file_id' => $jdfFile->id,
                'mxml_file_id' => $mxmlFile->id,
                'package_file_id' => $packageFile->id,
                'status' => 'ready',
            ])->save();

            ProductionPackageGenerated::dispatch($job);
        } catch (\Throwable $e) {
            $job->forceFill([
                'status' => 'failed',
                'metadata_json' => array_merge($job->metadata_json ?? [], [
                    'last_error' => $e->getMessage(),
                ]),
            ])->save();
            throw $e;
        }
    }

    private function packageFilename(ProductionJob $job): string
    {
        $slug = preg_replace('/\\s+/', '-', strtoupper($job->product_name));
        return "{$job->source}-{$job->external_order_ref}-{$job->external_order_item_ref}-{$slug}.zip";
    }

    private function store(FileStorageService $files, string $folder, string $contents, string $mime, ?string $originalName = null): \Modules\FileStorage\Domain\Models\File
    {
        $name = $originalName ?? basename($folder);
        $path = $files->newPath(dirname($folder), $name);
        Storage::disk(config('filesystems.default'))->put($path, $contents);

        return $files->record([
            'disk' => config('filesystems.default'),
            'path' => $path,
            'original_name' => $name,
            'mime_type' => $mime,
            'size' => strlen($contents),
            'attached' => true,
        ]);
    }

    private function buildZip(ProductionJob $job, string $jobsheet, string $jdf, string $mxml, FileStorageService $files): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pkg_');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Could not open temporary zip.');
        }

        $base = "{$job->source}/order-{$job->external_order_ref}/item-{$job->external_order_item_ref}";
        $zip->addFromString("$base/jobsheet.html", $jobsheet);
        $zip->addFromString("$base/job.jdf", $jdf);
        $zip->addFromString("$base/job.mxml", $mxml);
        $zip->addFromString("$base/metadata.json", json_encode([
            'job_number' => $job->job_number,
            'source' => $job->source,
            'external_order_ref' => $job->external_order_ref,
            'external_order_item_ref' => $job->external_order_item_ref,
            'product' => $job->product_name,
            'configuration' => $job->configuration_snapshot_json['configuration'] ?? [],
            'quantity' => $job->configuration_snapshot_json['quantity'] ?? null,
            'customer' => $job->configuration_snapshot_json['customer'] ?? null,
            'shipping_address' => $job->configuration_snapshot_json['shipping_address'] ?? null,
            'generated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        if ($job->artwork) {
            $contents = $files->disk($job->artwork->disk)->get($job->artwork->path);
            if ($contents !== null) {
                $zip->addFromString("$base/artwork.pdf", $contents);
            }
        }

        $zip->close();
        $bytes = file_get_contents($tmp);
        unlink($tmp);
        return $bytes;
    }
}
