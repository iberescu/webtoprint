<?php

namespace Modules\Templates\Filament\Resources\ContentTemplateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Templates\Filament\Resources\ContentTemplateResource;

class ListContentTemplates extends ListRecords
{
    protected static string $resource = ContentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
