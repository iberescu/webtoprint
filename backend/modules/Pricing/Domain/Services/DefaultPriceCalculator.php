<?php

namespace Modules\Pricing\Domain\Services;

use Modules\Pricing\Domain\Contracts\PriceCalculatorInterface;
use Modules\Pricing\Domain\Models\PriceModifier;
use Modules\Pricing\Domain\Models\PriceRule;
use Modules\Pricing\Domain\Models\PriceTable;
use Modules\Pricing\Domain\ValueObjects\PriceResult;
use Modules\Pricing\Domain\ValueObjects\ProductConfiguration;
use Modules\Settings\Domain\Services\SettingsRepository;

/**
 * Default calculator implementing spec §7:
 *   1. Find the price_table whose row's match_json is a subset of configuration.
 *   2. Pick the largest quantity break <= requested quantity.
 *   3. base = unit_price * quantity, plus row-level setup_fee.
 *   4. Apply any matching price_modifiers (flat | per_unit | percent).
 *   5. Apply min_price rule, then add tax.
 *
 * The breakdown returned matches the example response in spec §7.
 */
class DefaultPriceCalculator implements PriceCalculatorInterface
{
    public function __construct(private readonly ?SettingsRepository $settings = null)
    {
    }

    public function calculate(ProductConfiguration $config): PriceResult
    {
        $product = $config->product;
        $cfg = $config->configuration;
        $qty = max(1, $config->quantity);

        $table = PriceTable::query()
            ->where('product_id', $product->id)
            ->with('rows')
            ->first();

        if (! $table) {
            return PriceResult::invalid('No price table configured for this product.', $config->currency);
        }

        $row = $this->matchRow($table, $cfg);
        if (! $row) {
            return PriceResult::invalid('No price defined for the selected configuration.', $config->currency);
        }

        $break = $this->pickBreak($row->quantity_breaks_json ?? [], $qty);
        if (! $break) {
            return PriceResult::invalid('No quantity break available for the requested quantity.', $config->currency);
        }

        $unit = (float) ($break['unit_price'] ?? 0);
        $setup = (float) ($break['setup_fee'] ?? 0);

        $breakdown = [];
        $breakdown[] = ['label' => "Base price ({$qty} × {$unit})", 'amount' => $unit * $qty];

        if ($setup > 0) {
            $breakdown[] = ['label' => 'Setup fee', 'amount' => $setup];
        }

        $modifiers = PriceModifier::query()->where('product_id', $product->id)->get();
        foreach ($modifiers as $mod) {
            if (! $this->matches($mod->match_json ?? [], $cfg)) {
                continue;
            }
            $amount = match ($mod->strategy) {
                'flat' => (float) $mod->amount,
                'per_unit' => (float) $mod->amount * $qty,
                'percent' => array_sum(array_column($breakdown, 'amount')) * ((float) $mod->amount / 100),
                default => 0.0,
            };
            if ($amount != 0.0) {
                $breakdown[] = ['label' => $mod->label, 'amount' => $amount];
            }
        }

        $net = array_sum(array_column($breakdown, 'amount'));

        // min_price rule
        foreach (PriceRule::query()->where('product_id', $product->id)->where('kind', 'min_price')->get() as $rule) {
            $min = (float) ($rule->rule_json['amount'] ?? 0);
            if ($min > 0 && $net < $min) {
                $breakdown[] = ['label' => 'Minimum price adjustment', 'amount' => $min - $net];
                $net = $min;
            }
        }

        $rate = $this->resolveVatRate($config);
        $tax = $net * $rate;
        $gross = $net + $tax;

        return new PriceResult(
            valid: true,
            currency: $config->currency,
            netPrice: $net,
            tax: $tax,
            grossPrice: $gross,
            breakdown: $breakdown,
            note: sprintf('Includes %.1f%% VAT', $rate * 100),
        );
    }

    /**
     * VAT rate resolution:
     *   1. configuration.shipping_country (per-line override) → tax.country_rates[CC]
     *   2. tax.shop_country → tax.country_rates[shop_country]
     *   3. tax.default_rate
     *   4. fallback 0.19
     */
    private function resolveVatRate(ProductConfiguration $config): float
    {
        if (! $this->settings) {
            return 0.19;
        }

        $rates = $this->settings->get('tax.country_rates', []);
        $cc = strtoupper((string) ($config->configuration['shipping_country']
            ?? $this->settings->get('tax.shop_country', '')));

        if ($cc && isset($rates[$cc])) {
            return (float) $rates[$cc];
        }

        return (float) $this->settings->get('tax.default_rate', 0.19);
    }

    private function matchRow(PriceTable $table, array $configuration): ?\Modules\Pricing\Domain\Models\PriceTableRow
    {
        $best = null;
        $bestSize = -1;
        foreach ($table->rows as $row) {
            $match = $row->match_json ?? [];
            if (! $this->matches($match, $configuration)) {
                continue;
            }
            // Prefer the row with the most specific match.
            if (count($match) > $bestSize) {
                $best = $row;
                $bestSize = count($match);
            }
        }
        return $best;
    }

    private function matches(array $match, array $configuration): bool
    {
        foreach ($match as $key => $expected) {
            if (! array_key_exists($key, $configuration)) {
                return false;
            }
            $actual = (string) $configuration[$key];
            $expected = is_array($expected) ? array_map('strval', $expected) : (string) $expected;
            if (is_array($expected)) {
                if (! in_array($actual, $expected, true)) {
                    return false;
                }
            } elseif ($actual !== $expected) {
                return false;
            }
        }
        return true;
    }

    private function pickBreak(array $breaks, int $qty): ?array
    {
        $applicable = array_filter($breaks, fn ($b) => (int) ($b['min_qty'] ?? 0) <= $qty);
        if (! $applicable) {
            return null;
        }
        usort($applicable, fn ($a, $b) => ((int) $b['min_qty']) <=> ((int) $a['min_qty']));
        return $applicable[0];
    }
}
