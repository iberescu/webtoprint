<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\PIM\Domain\Models\Product;

class RecentProducts extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recently updated products')
            ->description('The eight catalog entries most recently touched.')
            ->query(Product::query()->orderByDesc('updated_at')->limit(8))
            ->columns([
                Tables\Columns\TextColumn::make('name')->weight('semibold'),
                Tables\Columns\TextColumn::make('slug')->fontFamily('mono')->color('gray'),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->placeholder('—'),
                Tables\Columns\IconColumn::make('requires_design')->boolean()->label('Needs design'),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'gray' => 'draft', 'success' => 'published', 'danger' => 'archived',
                ]),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->paginated(false);
    }
}
