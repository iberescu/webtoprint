<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PIM\Domain\Models\Product;

/**
 * Top-of-dashboard summary tiles. We probe tables defensively because not
 * every module is shipped on every install (Designer/Distribution may be
 * present without seeded data; ecommerce is optional).
 */
class OverviewStats extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $stats = [
            Stat::make('Products', Product::query()->count())
                ->description('Published + drafts')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),
        ];

        if (Schema::hasTable('designs')) {
            $count = DB::table('designs')->count();
            $approved = DB::table('designs')->where('status', 'approved')->count();
            $stats[] = Stat::make('Designs', $count)
                ->description("$approved approved")
                ->descriptionIcon('heroicon-m-paint-brush')
                ->color('success');
        }

        if (Schema::hasTable('production_jobs')) {
            $count = DB::table('production_jobs')->count();
            $pending = DB::table('production_jobs')->where('status', 'pending')->count();
            $stats[] = Stat::make('Production jobs', $count)
                ->description("$pending pending")
                ->descriptionIcon('heroicon-m-printer')
                ->color($pending > 0 ? 'warning' : 'gray');
        }

        if (Schema::hasTable('files')) {
            // Column name varies by install — defensively pick whichever
            // exists so missing migrations don't crash the dashboard.
            $col = Schema::hasColumn('files', 'size') ? 'size'
                : (Schema::hasColumn('files', 'size_bytes') ? 'size_bytes' : null);
            if ($col) {
                $bytes = (int) DB::table('files')->sum($col);
                $stats[] = Stat::make('Storage used', $this->formatBytes($bytes))
                    ->description(DB::table('files')->count().' files')
                    ->descriptionIcon('heroicon-m-cloud')
                    ->color('gray');
            }
        }

        return $stats;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $i ? 1 : 0).' '.$units[$i];
    }
}
