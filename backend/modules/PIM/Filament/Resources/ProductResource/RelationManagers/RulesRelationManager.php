<?php

namespace Modules\PIM\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Configuration whitelist/blacklist rules. The RuleEvaluator runs these on
 * every POST /products/{slug}/validate and uses them to grey out impossible
 * option combinations in the storefront configurator.
 *
 * `rule_json` example:
 *   { "when": { "format": "a3" }, "then_disable": { "paper": ["80g"] } }
 */
class RulesRelationManager extends RelationManager
{
    protected static string $relationship = 'rules';
    protected static ?string $title = 'Rules (whitelist / blacklist)';
    protected static ?string $icon = 'heroicon-o-shield-check';
    protected static ?string $modelLabel = 'rule';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('kind')
                ->options([
                    'whitelist' => 'Whitelist — only the listed combinations are allowed',
                    'blacklist' => 'Blacklist — forbid the listed combination',
                ])
                ->required()
                ->default('blacklist'),

            Forms\Components\TextInput::make('reason')
                ->columnSpanFull()
                ->helperText('Shown to the customer if validation fails, e.g. "Lamination unavailable for A3 posters".'),

            Forms\Components\TextInput::make('priority')
                ->numeric()
                ->default(0)
                ->helperText('Higher priority rules win when conflicts arise. Most rules can stay at 0.'),

            Forms\Components\Textarea::make('rule_json')
                ->required()
                ->columnSpanFull()
                ->rows(10)
                ->extraInputAttributes([
                    'style' => 'font-family: ui-monospace, monospace; font-size: 12px; tab-size: 2;',
                    'spellcheck' => 'false',
                ])
                ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $state)
                ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                ->rules(['json'])
                ->helperText(<<<'TEXT'
JSON describing the rule. Common shapes:

• Blacklist a combo:
  {"when": {"format": "a3", "refinement": "lamination"}}

• Disable specific values when another is chosen:
  {"when": {"format": "a3"}, "then_disable": {"refinement": ["lamination", "uv_coating"]}}

• Whitelist only valid combos:
  {"allowed": [{"format": "a4", "paper": "80g"}, {"format": "a4", "paper": "120g"}]}
TEXT),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority', 'desc')
            ->columns([
                Tables\Columns\BadgeColumn::make('kind')->colors([
                    'success' => 'whitelist',
                    'danger'  => 'blacklist',
                ]),
                Tables\Columns\TextColumn::make('reason')->wrap(),
                Tables\Columns\TextColumn::make('rule_json')
                    ->label('Rule')
                    ->fontFamily('mono')
                    ->limit(80)
                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_UNESCAPED_SLASHES)),
                Tables\Columns\TextColumn::make('priority')->alignEnd(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
