<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Modules\Settings\Domain\Services\SettingsRepository;

/**
 * Company-wide configuration — flat KV stored in the `settings` table,
 * grouped here by section for readability. Each form field maps 1:1 to a
 * key that the rest of the application reads via SettingsRepository::get().
 *
 * Add a new setting in three steps:
 *   1. Add it to the seeder (so a fresh install has a sensible default).
 *   2. Add a form field below under the right Section.
 *   3. Read it via SettingsRepository wherever you need it.
 */
class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Company settings';
    protected static ?string $title = 'Company settings';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.manage-settings';
    protected static ?string $slug = 'settings';

    public ?array $data = [];

    public function mount(): void
    {
        $repo = app(SettingsRepository::class);

        $this->form->fill([
            // company.*
            'company_name'              => $repo->get('company.name'),
            'company_legal_name'        => $repo->get('company.legal_name'),
            'company_email'             => $repo->get('company.email'),
            'company_phone'             => $repo->get('company.phone'),
            'company_vat_id'            => $repo->get('company.vat_id'),
            'company_registration'      => $repo->get('company.registration'),
            'company_managing_director' => $repo->get('company.managing_director'),
            'company_address'           => $repo->get('company.address'),

            // storefront.*
            'storefront_currency'      => $repo->get('storefront.currency'),
            'storefront_locale'        => $repo->get('storefront.locale'),
            'storefront_support_email' => $repo->get('storefront.support_email'),

            // tax.*
            'tax_shop_country'        => $repo->get('tax.shop_country'),
            'tax_default_rate'        => $repo->get('tax.default_rate'),
            'tax_prices_include_vat'  => (bool) $repo->get('tax.prices_include_vat', false),
            'tax_country_rates'       => $repo->get('tax.country_rates'),

            // gdpr.*
            'gdpr_dpo_email'             => $repo->get('gdpr.dpo_email'),
            'gdpr_privacy_url'           => $repo->get('gdpr.privacy_url'),
            'gdpr_cookie_policy_url'     => $repo->get('gdpr.cookie_policy_url'),
            'gdpr_data_retention_months' => $repo->get('gdpr.data_retention_months'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Company')
                    ->description('Legal entity details — shown on invoices, jobsheets, and the storefront imprint page.')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('company_name')->label('Display name')->required(),
                        Forms\Components\TextInput::make('company_legal_name')->label('Legal name'),
                        Forms\Components\TextInput::make('company_email')->label('Contact email')->email()->required(),
                        Forms\Components\TextInput::make('company_phone')->label('Phone')->tel(),
                        Forms\Components\TextInput::make('company_vat_id')->label('VAT ID'),
                        Forms\Components\TextInput::make('company_registration')->label('Registration'),
                        Forms\Components\TextInput::make('company_managing_director')->label('Managing director'),
                        Forms\Components\KeyValue::make('company_address')
                            ->label('Address')->columnSpanFull()->addable()->reorderable(),
                    ]),

                Section::make('Storefront')
                    ->description('Customer-facing defaults.')
                    ->icon('heroicon-o-globe-europe-africa')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('storefront_currency')
                            ->options(['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP', 'CHF' => 'CHF', 'PLN' => 'PLN'])
                            ->required(),
                        Forms\Components\TextInput::make('storefront_locale')->placeholder('en-EU')->required(),
                        Forms\Components\TextInput::make('storefront_support_email')->email(),
                    ]),

                Section::make('Tax')
                    ->description('VAT defaults. The configurator pricing pipeline reads these on every price request.')
                    ->icon('heroicon-o-receipt-percent')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('tax_shop_country')->maxLength(2)->placeholder('DE'),
                        Forms\Components\TextInput::make('tax_default_rate')
                            ->numeric()->step('0.001')->minValue(0)->maxValue(1)
                            ->helperText('Decimal — 0.19 = 19 %.'),
                        Forms\Components\Toggle::make('tax_prices_include_vat')->inline(false),
                        Forms\Components\KeyValue::make('tax_country_rates')
                            ->label('Per-country VAT rates')
                            ->columnSpanFull()
                            ->keyLabel('Country (ISO-2)')
                            ->valueLabel('Rate (decimal)')
                            ->addable()->reorderable()
                            ->helperText('Overrides default_rate when the customer is in one of these countries.'),
                    ]),

                Section::make('GDPR & compliance')
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('gdpr_dpo_email')->email()->label('DPO email'),
                        Forms\Components\TextInput::make('gdpr_data_retention_months')->numeric()->minValue(1),
                        Forms\Components\TextInput::make('gdpr_privacy_url')->label('Privacy URL'),
                        Forms\Components\TextInput::make('gdpr_cookie_policy_url')->label('Cookie-policy URL'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $d = $this->form->getState();
        $repo = app(SettingsRepository::class);

        $map = [
            'company.name'               => 'company_name',
            'company.legal_name'         => 'company_legal_name',
            'company.email'              => 'company_email',
            'company.phone'              => 'company_phone',
            'company.vat_id'             => 'company_vat_id',
            'company.registration'       => 'company_registration',
            'company.managing_director'  => 'company_managing_director',
            'company.address'            => 'company_address',
            'storefront.currency'        => 'storefront_currency',
            'storefront.locale'          => 'storefront_locale',
            'storefront.support_email'   => 'storefront_support_email',
            'tax.shop_country'           => 'tax_shop_country',
            'tax.default_rate'           => 'tax_default_rate',
            'tax.prices_include_vat'     => 'tax_prices_include_vat',
            'tax.country_rates'          => 'tax_country_rates',
            'gdpr.dpo_email'             => 'gdpr_dpo_email',
            'gdpr.privacy_url'           => 'gdpr_privacy_url',
            'gdpr.cookie_policy_url'     => 'gdpr_cookie_policy_url',
            'gdpr.data_retention_months' => 'gdpr_data_retention_months',
        ];

        foreach ($map as $settingKey => $formKey) {
            $value = $d[$formKey] ?? null;
            [$group] = explode('.', $settingKey, 2);
            // Coerce types that Filament returns as strings.
            if ($settingKey === 'tax.default_rate' && $value !== null) {
                $value = (float) $value;
            }
            if ($settingKey === 'gdpr.data_retention_months' && $value !== null) {
                $value = (int) $value;
            }
            $repo->set($settingKey, $value, $group);
        }

        Notification::make()
            ->title('Settings saved')
            ->body('Company, storefront, tax and GDPR settings have been updated.')
            ->success()
            ->send();
    }
}
