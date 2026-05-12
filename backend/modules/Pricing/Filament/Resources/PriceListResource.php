<?php

namespace Modules\Pricing\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Pricing\Domain\Models\PriceList;
use Modules\Pricing\Filament\Resources\PriceListResource\Pages;

class PriceListResource extends Resource
{
    protected static ?string $model = PriceList::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Pricing';
    protected static ?int $navigationSort = 10;
    protected static ?string $modelLabel = 'price list';
    protected static ?string $pluralModelLabel = 'price lists';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->helperText('E.g. "Retail EUR", "Trade USD", "Customer X 2026".'),
            Forms\Components\Select::make('currency')
                ->options([
                    'EUR' => 'EUR — Euro',
                    'USD' => 'USD — US dollar',
                    'GBP' => 'GBP — British pound',
                    'CHF' => 'CHF — Swiss franc',
                    'PLN' => 'PLN — Polish zloty',
                ])
                ->default('EUR')
                ->required(),
            Forms\Components\Toggle::make('is_default')
                ->helperText('Used when a product has price tables but no explicit list assignment.'),
            Forms\Components\KeyValue::make('metadata_json')
                ->label('Metadata')
                ->columnSpanFull()
                ->addable()
                ->reorderable()
                ->helperText('Free-form notes (e.g. "valid_until=2026-12-31", "channel=web").'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold'),
                Tables\Columns\BadgeColumn::make('currency'),
                Tables\Columns\IconColumn::make('is_default')->boolean()->label('Default'),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPriceLists::route('/'),
            'create' => Pages\CreatePriceList::route('/create'),
            'edit'   => Pages\EditPriceList::route('/{record}/edit'),
        ];
    }
}
