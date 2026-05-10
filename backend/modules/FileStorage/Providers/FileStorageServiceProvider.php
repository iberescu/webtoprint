<?php

namespace Modules\FileStorage\Providers;

use App\Modules\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Modules\FileStorage\Application\Actions\CleanAbandonedFiles;

class FileStorageServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'FileStorage';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }

    public function boot(): void
    {
        parent::boot();

        $this->commands([CleanAbandonedFiles::class]);
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('files:clean-abandoned')->hourly();
        });
    }
}
