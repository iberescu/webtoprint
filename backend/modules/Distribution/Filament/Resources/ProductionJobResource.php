<?php

namespace Modules\Distribution\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Distribution\Domain\Models\ProductionJob;

class ProductionJobResource extends Resource
{
    protected static ?string $model = ProductionJob::class;
    protected static ?string $navigationIcon = 'heroicon-o-printer';
    protected static ?string $navigationGroup = 'Production';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('job_number')->searchable(),
            Tables\Columns\TextColumn::make('product_name'),
            Tables\Columns\BadgeColumn::make('status')->colors([
                'gray' => 'pending', 'warning' => 'generating',
                'success' => ['ready', 'exported'],
                'danger' => ['failed', 'cancelled'],
            ]),
            Tables\Columns\TextColumn::make('source')->badge(),
            Tables\Columns\TextColumn::make('external_order_ref')->label('Ext. order'),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])->defaultSort('created_at', 'desc');
    }
}
