<?php

namespace Modules\Templates\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Templates\Domain\ContentTemplate;
use Modules\Templates\Filament\Resources\ContentTemplateResource\Pages;

class ContentTemplateResource extends Resource
{
    protected static ?string $model = ContentTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Templates';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')
                ->required()
                ->helperText('Stable lookup key, e.g. "default.jobsheet" or "customer-x.jdf".'),
            Forms\Components\Select::make('kind')->options([
                'jobsheet_pdf' => 'Jobsheet PDF',
                'jdf_xml' => 'JDF XML',
                'mxml_xml' => 'MXML',
                'folder_name' => 'Folder name',
                'file_name' => 'File name',
            ])->required()->live(),
            Forms\Components\Select::make('engine')->options([
                'blade' => 'Blade (Laravel)',
                'twig'  => 'Twig',
                'raw'   => 'Raw ({var.path} substitution only)',
            ])->default('blade')->required(),
            Forms\Components\Textarea::make('body')
                ->required()
                ->columnSpanFull()
                ->rows(24)
                ->extraInputAttributes([
                    'style' => 'font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px; line-height: 1.45; tab-size: 2;',
                    'spellcheck' => 'false',
                    'wrap' => 'off',
                ])
                ->helperText('Variables available: $job, $config, $company, $artwork, $now. See the seeded defaults for the full surface.'),
            Forms\Components\KeyValue::make('metadata_json')
                ->label('Metadata')
                ->columnSpanFull()
                ->addable()
                ->reorderable()
                ->helperText('Free-form notes, version tags, MIS-specific knobs.'),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContentTemplates::route('/'),
            'create' => Pages\CreateContentTemplate::route('/create'),
            'edit'   => Pages\EditContentTemplate::route('/{record}/edit'),
        ];
    }
}
