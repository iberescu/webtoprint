<?php

namespace Modules\Templates\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Templates\Domain\ContentTemplate;

class ContentTemplateResource extends Resource
{
    protected static ?string $model = ContentTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Templates';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')->required(),
            Forms\Components\Select::make('kind')->options([
                'jobsheet_pdf' => 'Jobsheet PDF',
                'jdf_xml' => 'JDF XML',
                'mxml_xml' => 'MXML',
                'folder_name' => 'Folder name',
                'file_name' => 'File name',
            ])->required(),
            Forms\Components\Select::make('engine')->options([
                'blade' => 'Blade', 'twig' => 'Twig', 'raw' => 'Raw',
            ])->default('blade')->required(),
            Forms\Components\Textarea::make('body')->required()->columnSpanFull()->rows(20),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('key')->searchable(),
            Tables\Columns\BadgeColumn::make('kind'),
            Tables\Columns\TextColumn::make('engine'),
            Tables\Columns\TextColumn::make('updated_at')->dateTime(),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }
}
