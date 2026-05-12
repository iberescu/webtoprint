<?php

namespace Modules\PIM\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Inline editor for a product's options ("Format", "Paper", "Colors" …).
 * Each option's individual values (A4, A6, 300g, …) are managed via the
 * nested ValuesRelationManager — open an option row to edit them.
 */
class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';
    protected static ?string $title = 'Options';
    protected static ?string $modelLabel = 'option';
    protected static ?string $pluralModelLabel = 'options';
    protected static ?string $icon = 'heroicon-o-adjustments-horizontal';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->required()
                ->maxLength(255)
                ->helperText('Machine name (e.g. "format", "paper"). Lowercase, no spaces.')
                ->rules(['regex:/^[a-z][a-z0-9_-]*$/']),
            Forms\Components\TextInput::make('label')
                ->required()
                ->helperText('User-facing label shown in the storefront configurator.'),
            Forms\Components\Select::make('type')
                ->options([
                    'select'  => 'Select (radio buttons)',
                    'numeric' => 'Numeric (quantity-like)',
                    'text'    => 'Free text',
                    'boolean' => 'Toggle',
                ])
                ->default('select')
                ->required(),
            Forms\Components\Toggle::make('required')->inline(false),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\TextInput::make('help_text')
                ->columnSpanFull()
                ->helperText('Optional one-line hint under the option in the storefront.'),

            Forms\Components\Repeater::make('values')
                ->relationship('values')
                ->columnSpanFull()
                ->orderColumn('sort_order')
                ->defaultItems(0)
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['label'] ?? $state['code'] ?? 'New value')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->helperText('Stable machine code (e.g. "a4", "a6", "85x55"). Used by the API.')
                        ->rules(['regex:/^[a-zA-Z0-9_.-]+$/']),
                    Forms\Components\TextInput::make('label')
                        ->required()
                        ->helperText('User-facing label, e.g. "A4 (210 × 297 mm)".'),
                    Forms\Components\TextInput::make('value')
                        ->helperText('Optional raw value passed to the pricing engine (often left blank).'),
                    Forms\Components\KeyValue::make('metadata_json')
                        ->columnSpanFull()
                        ->helperText('Free-form metadata for pricing/distribution (e.g. width_mm=210, height_mm=297).')
                        ->addable()
                        ->reorderable(),
                ])
                ->columns(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('label')->weight('semibold'),
                Tables\Columns\TextColumn::make('code')->fontFamily('mono')->color('gray'),
                Tables\Columns\BadgeColumn::make('type'),
                Tables\Columns\IconColumn::make('required')->boolean(),
                Tables\Columns\TextColumn::make('values_count')
                    ->counts('values')->label('Values')->badge(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
