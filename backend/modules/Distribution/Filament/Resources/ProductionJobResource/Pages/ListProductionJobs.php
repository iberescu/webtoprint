<?php

namespace Modules\Distribution\Filament\Resources\ProductionJobResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Distribution\Filament\Resources\ProductionJobResource;

class ListProductionJobs extends ListRecords
{
    protected static string $resource = ProductionJobResource::class;
}
