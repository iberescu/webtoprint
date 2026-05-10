<?php

use Modules\PIM\Domain\Models\Product;
use Modules\PIM\Domain\Models\ProductOption;
use Modules\PIM\Domain\Rules\RuleEvaluator;

/**
 * Direct unit tests for the rule evaluator. We construct the smallest possible
 * product graph (one option, two values) and exercise each branch.
 */
function flyerProduct(): Product
{
    $product = Product::query()->create([
        'name' => 'Flyer', 'slug' => 'flyer-test-' . uniqid(), 'status' => 'published',
    ]);

    $format = $product->options()->create([
        'code' => 'format', 'label' => 'Format', 'type' => 'select', 'required' => true,
    ]);
    $format->values()->createMany([
        ['code' => 'a4', 'label' => 'A4'],
        ['code' => 'a5', 'label' => 'A5'],
    ]);

    $colors = $product->options()->create([
        'code' => 'colors', 'label' => 'Colors', 'type' => 'select', 'required' => true,
    ]);
    $colors->values()->createMany([
        ['code' => '4-0', 'label' => '4/0'],
        ['code' => '4-4', 'label' => '4/4'],
    ]);

    return $product;
}

it('returns valid when there are no rules', function () {
    $product = flyerProduct();
    $result = (new RuleEvaluator())->evaluate($product, ['format' => 'a4', 'colors' => '4-0']);
    expect($result['valid'])->toBeTrue();
    expect($result['errors'])->toBe([]);
});

it('rejects a configuration that fully matches a blacklist block', function () {
    $product = flyerProduct();
    $product->rules()->create([
        'kind' => 'blacklist',
        'rule_json' => ['block' => ['format' => 'a4', 'colors' => '4-0']],
        'reason' => 'A4 + 4/0 not available',
    ]);

    $result = (new RuleEvaluator())->evaluate($product, ['format' => 'a4', 'colors' => '4-0']);
    expect($result['valid'])->toBeFalse();
    expect($result['errors'][0]['code'])->toBe('combination_not_allowed');
    expect($result['errors'][0]['message'])->toBe('A4 + 4/0 not available');
});

it('does not fire a blacklist when the configuration only partially matches', function () {
    $product = flyerProduct();
    $product->rules()->create([
        'kind' => 'blacklist',
        'rule_json' => ['block' => ['format' => 'a4', 'colors' => '4-0']],
    ]);

    $result = (new RuleEvaluator())->evaluate($product, ['format' => 'a4', 'colors' => '4-4']);
    expect($result['valid'])->toBeTrue();
});

it('supports list values in blacklist block', function () {
    $product = flyerProduct();
    $product->rules()->create([
        'kind' => 'blacklist',
        'rule_json' => ['block' => ['colors' => ['4-0', '4-1']]],
    ]);

    $result = (new RuleEvaluator())->evaluate($product, ['format' => 'a4', 'colors' => '4-0']);
    expect($result['valid'])->toBeFalse();
});

it('rejects values not in a whitelist when one is set for that option', function () {
    $product = flyerProduct();
    $product->rules()->create([
        'kind' => 'whitelist',
        'rule_json' => ['allow' => ['colors' => ['4-4']]],
    ]);

    $resultBad = (new RuleEvaluator())->evaluate($product, ['format' => 'a4', 'colors' => '4-0']);
    expect($resultBad['valid'])->toBeFalse();

    $resultGood = (new RuleEvaluator())->evaluate($product, ['format' => 'a4', 'colors' => '4-4']);
    expect($resultGood['valid'])->toBeTrue();
});

it('reports per-option disabled values for the UI', function () {
    $product = flyerProduct();
    $product->rules()->create([
        'kind' => 'blacklist',
        'rule_json' => ['block' => ['format' => 'a4', 'colors' => '4-0']],
    ]);

    // Given the user has format=a4 already, picking colors=4-0 would violate.
    $result = (new RuleEvaluator())->evaluate($product, ['format' => 'a4', 'colors' => '4-4']);
    expect($result['disabled_options'])->toBe(['colors' => ['4-0']]);
});

it('combines whitelist union across multiple rules', function () {
    $product = flyerProduct();
    $product->rules()->createMany([
        ['kind' => 'whitelist', 'rule_json' => ['allow' => ['format' => ['a4']]]],
        ['kind' => 'whitelist', 'rule_json' => ['allow' => ['format' => ['a5']]]],
    ]);

    expect((new RuleEvaluator())->evaluate($product, ['format' => 'a4', 'colors' => '4-4'])['valid'])->toBeTrue();
    expect((new RuleEvaluator())->evaluate($product, ['format' => 'a5', 'colors' => '4-4'])['valid'])->toBeTrue();
});
