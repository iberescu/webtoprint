<?php

namespace Modules\PIM\Domain\Rules;

use Modules\PIM\Domain\Models\Product;

/**
 * Evaluates whitelist + blacklist rules from spec §7 against a configuration.
 *
 * Whitelist rule shape (rule_json):
 *   { "allow": { "format": ["a4", "a5"], "colors": ["4-4"] } }
 * The configuration must be a subset of the union of all whitelist rules per
 * option that has at least one whitelist mention.
 *
 * Blacklist rule shape (rule_json):
 *   { "block": { "format": "a4", "colors": "4-0" } }
 *   { "block": { "format": ["a4"], "colors": ["4-0", "4-1"] } }
 * If the configuration matches every key/value in `block`, the rule fires
 * and the combination is rejected.
 *
 * The evaluator returns:
 *   - errors[]            — per spec §7 ({code, message})
 *   - disabled_options{}  — option_code => list of value codes that would
 *                           cause violation given the rest of the config.
 */
class RuleEvaluator
{
    public function evaluate(Product $product, array $configuration): array
    {
        $product->loadMissing(['rules', 'options.values']);

        $whitelist = $product->rules->where('kind', 'whitelist')->values();
        $blacklist = $product->rules->where('kind', 'blacklist')->values();

        $errors = [];

        // --- whitelist evaluation: configuration option must be in allowed set, when constrained
        $allowedByOption = $this->collectWhitelistAllowed($whitelist);
        foreach ($allowedByOption as $opt => $allowed) {
            if (! array_key_exists($opt, $configuration)) {
                continue;
            }
            if (! in_array((string) $configuration[$opt], $allowed, true)) {
                $errors[] = [
                    'code' => 'combination_not_allowed',
                    'message' => "{$product->name} {$configuration[$opt]} for option `{$opt}` is not in the allowed list.",
                ];
            }
        }

        // --- blacklist evaluation: any block rule that fully matches configuration is rejected
        foreach ($blacklist as $rule) {
            if ($this->blockMatches($rule->rule_json['block'] ?? [], $configuration)) {
                $errors[] = [
                    'code' => 'combination_not_allowed',
                    'message' => $rule->reason ?: "This combination is not available.",
                ];
            }
        }

        // --- disabled options: per option, scan its possible values and ask "would picking
        // this value cause a violation given the rest of the configuration?"
        $disabled = [];
        foreach ($product->options as $option) {
            $candidates = [];
            foreach ($option->values as $value) {
                $hypothetical = array_merge($configuration, [$option->code => $value->code]);

                if (isset($allowedByOption[$option->code])
                    && ! in_array($value->code, $allowedByOption[$option->code], true)) {
                    $candidates[] = $value->code;
                    continue;
                }

                foreach ($blacklist as $rule) {
                    if ($this->blockMatches($rule->rule_json['block'] ?? [], $hypothetical)) {
                        $candidates[] = $value->code;
                        break;
                    }
                }
            }
            if ($candidates) {
                $disabled[$option->code] = array_values(array_unique($candidates));
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'disabled_options' => $disabled,
        ];
    }

    private function collectWhitelistAllowed($rules): array
    {
        $allowed = [];
        foreach ($rules as $rule) {
            $allow = $rule->rule_json['allow'] ?? [];
            foreach ($allow as $option => $values) {
                $values = array_map('strval', (array) $values);
                $allowed[$option] = array_values(array_unique(array_merge($allowed[$option] ?? [], $values)));
            }
        }
        return $allowed;
    }

    private function blockMatches(array $block, array $configuration): bool
    {
        if (empty($block)) {
            return false;
        }
        foreach ($block as $option => $values) {
            if (! array_key_exists($option, $configuration)) {
                return false;
            }
            $values = array_map('strval', (array) $values);
            if (! in_array((string) $configuration[$option], $values, true)) {
                return false;
            }
        }
        return true;
    }
}
