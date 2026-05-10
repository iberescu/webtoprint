<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Domain\Services\SettingsRepository;

/**
 * Seeds platform-wide settings: company identity, EU VAT, GDPR contact.
 *
 * VAT rates are the standard rate per EU country as of 2025-12. The shop
 * configures its own home country in `tax.shop_country` — that's the rate
 * applied to invoices for B2C buyers in the same country. Cross-border B2B
 * with a valid VAT ID is reverse-charge (0%); we don't validate VAT IDs in
 * the MVP but the slot is reserved.
 */
class SettingsSeeder extends Seeder
{
    public function run(SettingsRepository $settings): void
    {
        // ---------------- Company / imprint -----------------------------
        $settings->set('company.name', 'Demo Print GmbH', 'company');
        $settings->set('company.legal_name', 'Demo Print GmbH', 'company');
        $settings->set('company.address', [
            'street1' => 'Druckereistraße 12',
            'postcode' => '10115',
            'city' => 'Berlin',
            'country' => 'DE',
        ], 'company');
        $settings->set('company.email', 'hello@demoprint.example', 'company');
        $settings->set('company.phone', '+49 30 1234 5678', 'company');
        $settings->set('company.vat_id', 'DE123456789', 'company');
        $settings->set('company.registration', 'HRB 123456 — Amtsgericht Berlin', 'company');
        $settings->set('company.managing_director', 'Erika Musterfrau', 'company');

        // ---------------- Tax / VAT -------------------------------------
        $settings->set('tax.shop_country', 'DE', 'tax');
        $settings->set('tax.default_rate', 0.19, 'tax');
        $settings->set('tax.prices_include_vat', false, 'tax');
        $settings->set('tax.country_rates', $this->euVatRates(), 'tax');

        // ---------------- GDPR / privacy --------------------------------
        $settings->set('gdpr.dpo_email', 'dpo@demoprint.example', 'gdpr');
        $settings->set('gdpr.privacy_url', '/legal/privacy', 'gdpr');
        $settings->set('gdpr.cookie_policy_url', '/legal/cookies', 'gdpr');
        $settings->set('gdpr.data_retention_months', 36, 'gdpr');

        // ---------------- Storefront ------------------------------------
        $settings->set('storefront.currency', 'EUR', 'storefront');
        $settings->set('storefront.locale', 'en-EU', 'storefront');
        $settings->set('storefront.support_email', 'support@demoprint.example', 'storefront');
    }

    /**
     * EU standard VAT rates as of 2025. Decimals so we can multiply directly.
     */
    private function euVatRates(): array
    {
        return [
            'AT' => 0.20, // Austria
            'BE' => 0.21, // Belgium
            'BG' => 0.20, // Bulgaria
            'HR' => 0.25, // Croatia
            'CY' => 0.19, // Cyprus
            'CZ' => 0.21, // Czech Republic
            'DK' => 0.25, // Denmark
            'EE' => 0.22, // Estonia
            'FI' => 0.255, // Finland
            'FR' => 0.20, // France
            'DE' => 0.19, // Germany
            'GR' => 0.24, // Greece
            'HU' => 0.27, // Hungary
            'IE' => 0.23, // Ireland
            'IT' => 0.22, // Italy
            'LV' => 0.21, // Latvia
            'LT' => 0.21, // Lithuania
            'LU' => 0.17, // Luxembourg
            'MT' => 0.18, // Malta
            'NL' => 0.21, // Netherlands
            'PL' => 0.23, // Poland
            'PT' => 0.23, // Portugal
            'RO' => 0.19, // Romania
            'SK' => 0.23, // Slovakia
            'SI' => 0.22, // Slovenia
            'ES' => 0.21, // Spain
            'SE' => 0.25, // Sweden
            // Common non-EU but EEA / nearby
            'GB' => 0.20, // UK (not EU)
            'CH' => 0.081, // Switzerland
            'NO' => 0.25, // Norway
        ];
    }
}
