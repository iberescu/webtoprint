<?php

namespace Modules\FileStorage\Application\Actions;

use Illuminate\Console\Command;
use Modules\FileStorage\Domain\Models\File;
use Modules\FileStorage\Domain\Services\FileStorageService;

class CleanAbandonedFiles extends Command
{
    protected $signature = 'files:clean-abandoned {--hours=24}';
    protected $description = 'Delete unattached files older than --hours.';

    public function handle(FileStorageService $files): int
    {
        $cutoff = now()->subHours((int) $this->option('hours'));

        $count = 0;
        File::query()
            ->where('attached', false)
            ->where('created_at', '<', $cutoff)
            ->chunkById(200, function ($batch) use ($files, &$count) {
                foreach ($batch as $file) {
                    $files->delete($file);
                    $count++;
                }
            });

        $this->info("Deleted {$count} abandoned file(s).");
        return self::SUCCESS;
    }
}
