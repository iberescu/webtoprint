<?php

namespace Modules\PIM\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Edit price tables (and their rows) inline on the Product edit page.
 *
 * Structure:
 *   PriceTable (axes_json — which option codes the table is indexed by)
 *    └── PriceTableRow (match_json — concrete option-value combo)
 *          └── quantity_breaks_json — [{ qty: 100, unit: 0.18 }, …]
 */
class PriceTablesRelationManager extends RelationManager
{
    protected static string $relationship = 'priceTables';
    protected static ?string $title = 'Price tables';
    protected static ?string $icon = 'heroicon-o-table-cells';
    protected static ?string $modelLabel = 'price table';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->helperText('Human-readable name shown in admin only, e.g. "Default — 4-0 print".'),

            Forms\Components\Select::make('price_list_id')
                ->label('Price list')
                ->relationship('priceList', 'name')
                ->searchable()
                ->placeholder('— default —')
                ->helperText('Leave blank to use the global default list.'),

            Forms\Components\TagsInput::make('axes_json')
                ->label('Axes (option codes)')
                ->columnSpanFull()
                ->helperText('Option codes this table is indexed by, e.g. "format", "paper", "colors". The pricing engine matches a configuration against rows below.'),

            Forms\Components\Repeater::make('rows')
                ->relationship('rows')
                ->columnSpanFull()
                ->defaultItems(0)
                ->collapsed()
                ->itemLabel(fn (array $state) => collect($state['match_json'] ?? [])
                    ->map(fn ($v, $k) => "$k=$v")->implode(' · ') ?: 'New row')
                ->schema([
                    Forms\Components\KeyValue::make('match_json')
                        ->label('Match (option_code → value_code)')
                        ->keyLabel('Option')
                        ->valueLabel('Value')
                        ->columnSpanFull()
                        ->helperText('E.g. format → a4, paper → 300g. All axes must match for this row to fire.')
                        ->required()
                        ->addable()
                        ->reorderable(),

                    Forms\Components\Repeater::make('quantity_breaks_json')
                        ->label('Quantity breaks')
                        ->columnSpanFull()
                        ->defaultItems(1)
                        ->minItems(1)
                        ->itemLabel(fn (array $state) => isset($state['qty'])
                            ? "{$state['qty']} × €" . number_format((float) ($state['unit'] ?? 0), 4)
                            : 'New break')
                        ->schema([
                            Forms\Components\TextInput::make('qty')
                                ->label('From quantity')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('unit')
                                ->label('Unit price')
                                ->numeric()
                                ->step('0.0001')
                                ->required()
                                ->minValue(0)
                                ->columnSpan(1),
                        ])
                        ->columns(2)
                        ->helperText('Sorted ascending by qty. The row whose qty is the highest ≤ requested quantity wins.'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->weight('semibold'),
                Tables\Columns\TextColumn::make('priceList.name')->label('Price list')->placeholder('— default —'),
                Tables\Columns\TextColumn::make('axes_json')
                    ->label('Axes')
                    ->formatStateUsing(fn ($state) => implode(' · ', $state ?? [])),
                Tables\Columns\TextColumn::make('rows_count')
                    ->counts('rows')->label('Rows')->badge(),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
