<?php

use Custom\customer\Integrations\Shopify\ShopifyOrderWebhookController;
use Modules\Distribution\Application\Actions\CreateProductionJob;
use Modules\Distribution\Application\Contracts\ProductionJobInput;
use Modules\Distribution\Domain\Models\ProductionJob;

/**
 * Verifies the Shopify webhook controller maps the wire payload into a
 * correct ProductionJobInput, without going through Vanilo. The dependency
 * on CreateProductionJob is replaced with a capturing fake so we can assert
 * on the exact DTO shape — which is the contract any external ecommerce
 * adapter must produce.
 */

class CapturingCreateProductionJob extends CreateProductionJob
{
    /** @var ProductionJobInput[] */
    public array $captured = [];

    public function __construct() { /* no parent boot */ }

    public function execute(ProductionJobInput $input): ProductionJob
    {
        $this->captured[] = $input;
        return new ProductionJob([
            'job_number' => "{$input->source}-{$input->externalOrderRef}-{$input->externalOrderItemRef}",
        ]);
    }
}

it('maps a Shopify orders/paid webhook into one ProductionJobInput per line', function () {
    $fake = new CapturingCreateProductionJob();
    $this->app->instance(CreateProductionJob::class, $fake);

    $payload = [
        'name' => '#1042',
        'id' => 1234567890,
        'customer' => ['first_name' => 'Anita', 'last_name' => 'Print', 'email' => 'a@example.com', 'phone' => '+44'],
        'shipping_address' => [
            'first_name' => 'Anita', 'last_name' => 'Print',
            'company' => 'Acme', 'address1' => '1 High St', 'address2' => 'Suite 2',
            'city' => 'Leeds', 'province' => 'West Yorkshire',
            'zip' => 'LS1 1AA', 'country_code' => 'GB', 'phone' => '+44',
        ],
        'line_items' => [
            [
                'id' => 14087351, 'variant_id' => 99, 'sku' => 'FLYER-A4',
                'title' => 'Flyer', 'quantity' => 250, 'price' => '0.30',
                'properties' => [
                    ['name' => 'format', 'value' => 'a4'],
                    ['name' => 'paper', 'value' => '125g'],
                    ['name' => 'colors', 'value' => '4-4'],
                    ['name' => 'quantity', 'value' => '250'],
                    ['name' => '_artwork_file_id', 'value' => '01HX0000000000000000000000'],
                ],
            ],
            [
                'id' => 14087352, 'variant_id' => 100, 'sku' => 'BC-85',
                'title' => 'Business Card', 'quantity' => 500, 'price' => '0.18',
                'properties' => [],
            ],
        ],
    ];

    // Register the webhook route on the fly and drive the controller through
    // Laravel's actual request pipeline.
    \Illuminate\Support\Facades\Route::post(
        '/test/webhooks/shopify/orders',
        [ShopifyOrderWebhookController::class, 'handle'],
    );
    $this->postJson('/test/webhooks/shopify/orders', $payload)->assertOk();

    expect($fake->captured)->toHaveCount(2);

    [$flyer, $card] = $fake->captured;

    expect($flyer)
        ->source->toBe('shopify')
        ->externalOrderRef->toBe('#1042')
        ->externalOrderItemRef->toBe('14087351')
        ->productName->toBe('Flyer')
        ->quantity->toBe(250)
        ->artworkFileId->toBe('01HX0000000000000000000000');

    expect($flyer->configuration)->toBe([
        'format' => 'a4', 'paper' => '125g', 'colors' => '4-4', 'quantity' => '250',
        '_artwork_file_id' => '01HX0000000000000000000000',
    ]);

    expect($flyer->customerSnapshot)->toMatchArray([
        'name' => 'Anita Print',
        'email' => 'a@example.com',
    ]);

    expect($flyer->shippingAddress)->toMatchArray([
        'name' => 'Anita Print',
        'street1' => '1 High St',
        'city' => 'Leeds',
        'postcode' => 'LS1 1AA',
        'country' => 'GB',
    ]);

    expect($card)
        ->source->toBe('shopify')
        ->externalOrderItemRef->toBe('14087352')
        ->productName->toBe('Business Card')
        ->quantity->toBe(500)
        ->artworkFileId->toBeNull();
});
