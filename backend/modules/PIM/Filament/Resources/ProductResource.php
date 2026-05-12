<?php

namespace Modules\PIM\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\PIM\Domain\Models\Product;
use Modules\PIM\Filament\Resources\ProductResource\Pages;
use Modules\PIM\Filament\Resources\ProductResource\RelationManagers;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'PIM';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
            Forms\Components\Select::make('category_id')
                ->relationship('category', 'name')
                ->searchable(),
            Forms\Components\Select::make('status')
                ->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])
                ->default('draft')
                ->required(),
            Forms\Components\Toggle::make('requires_design'),
            Forms\Components\Toggle::make('allows_pdf_upload')->default(true),
            Forms\Components\TextInput::make('default_bleed_mm')->numeric()->default(3),
            Forms\Components\TextInput::make('default_safe_margin_mm')->numeric()->default(5),
            Forms\Components\Textarea::make('description')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('slug')->copyable(),
            Tables\Columns\TextColumn::make('category.name')->label('Category'),
            Tables\Columns\BadgeColumn::make('status')->colors([
                'gray' => 'draft', 'success' => 'published', 'danger' => 'archived',
            ]),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OptionsRelationManager::class,
            RelationManagers\PriceTablesRelationManager::class,
            RelationManagers\PriceModifiersRelationManager::class,
            RelationManagers\RulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
