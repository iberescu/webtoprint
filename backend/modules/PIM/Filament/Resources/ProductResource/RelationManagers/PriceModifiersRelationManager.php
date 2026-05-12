<?php

namespace Modules\PIM\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Surcharges, setup fees, percent uplifts applied after the base price table.
 * Each modifier has a `match_json` (which configuration it fires on) and a
 * strategy that tells the calculator how to apply `amount`.
 */
class PriceModifiersRelationManager extends RelationManager
{
    protected static string $relationship = 'priceModifiers';
    protected static ?string $title = 'Price modifiers';
    protected static ?string $icon = 'heroicon-o-plus-circle';
    protected static ?string $modelLabel = 'modifier';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('label')
                ->required()
                ->helperText('Shown in the price breakdown on the storefront, e.g. "Setup fee", "Lamination surcharge".'),

            Forms\Components\Select::make('strategy')
                ->options([
                    'flat_add'        => 'Flat add — add `amount` to the total',
                    'per_unit'        => 'Per unit — add `amount × quantity`',
                    'percent_of_base' => 'Percent of base — multiply base by (1 + amount/100)',
                    'flat_min'        => 'Minimum total — ensure total ≥ `amount`',
                ])
                ->required()
                ->default('flat_add'),

            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->step('0.0001')
                ->required()
                ->helperText('Currency amount for flat_add / per_unit / flat_min. Percentage points for percent_of_base.'),

            Forms\Components\KeyValue::make('match_json')
                ->label('Match (option_code → value_code)')
                ->columnSpanFull()
                ->addable()
                ->reorderable()
                ->helperText('Empty = applies to every configuration. Otherwise all key/value pairs must match.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('label')->weight('semibold'),
                Tables\Columns\BadgeColumn::make('strategy'),
                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(fn ($state, $record) => $record->strategy === 'percent_of_base'
                        ? rtrim(rtrim(number_format($state, 4), '0'), '.').' %'
                        : '€' . number_format($state, 2))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('match_json')
                    ->label('Match')
                    ->formatStateUsing(fn ($state) => empty($state)
                        ? 'any'
                        : collect($state)->map(fn ($v, $k) => "$k=$v")->implode(' · ')),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
