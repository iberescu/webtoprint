<?php

namespace Custom\customer\Integrations\Shopify;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Distribution\Application\Actions\CreateProductionJob;
use Modules\Distribution\Application\Contracts\ProductionJobInput;

/**
 * Example: a Shopify "orders/paid" webhook lands here. The adapter parses
 * the Shopify payload, maps each line item to a ProductionJobInput, and
 * hands it to Distribution. No Vanilo, no Ecommerce module involved —
 * proof that Distribution is genuinely ecommerce-agnostic.
 *
 * Wire-up (in CustomerServiceProvider::boot()):
 *
 *     Route::post('/webhooks/shopify/orders', [ShopifyOrderWebhookController::class, 'handle']);
 *
 * Verify the HMAC header in production; omitted here for clarity.
 */
class ShopifyOrderWebhookController extends Controller
{
    public function __construct(private readonly CreateProductionJob $create)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderRef = (string) ($payload['name'] ?? $payload['id'] ?? '');

        $customer = [
            'name' => trim(($payload['customer']['first_name'] ?? '') . ' ' . ($payload['customer']['last_name'] ?? '')),
            'email' => $payload['customer']['email'] ?? null,
            'phone' => $payload['customer']['phone'] ?? null,
        ];

        $shipping = $this->mapAddress($payload['shipping_address'] ?? []);
        $billing = $this->mapAddress($payload['billing_address'] ?? []);

        foreach ($payload['line_items'] ?? [] as $line) {
            $properties = collect($line['properties'] ?? [])
                ->mapWithKeys(fn ($p) => [$p['name'] => $p['value']])
                ->all();

            $this->create->execute(new ProductionJobInput(
                externalOrderRef: $orderRef,
                externalOrderItemRef: (string) $line['id'],
                source: 'shopify',
                productName: (string) ($line['title'] ?? 'Item'),
                productSnapshot: [
                    'variant_id' => $line['variant_id'] ?? null,
                    'sku' => $line['sku'] ?? null,
                    'title' => $line['title'] ?? null,
                    'properties' => $properties,
                ],
                // Shopify line item properties carry the configurator selections
                // when the storefront uses our PIM configurator and posts them
                // as line item properties.
                configuration: $properties,
                quantity: (int) ($line['quantity'] ?? 1),
                artworkFileId: $properties['_artwork_file_id'] ?? null,
                customerSnapshot: array_filter($customer),
                shippingAddress: $shipping,
                billingAddress: $billing,
                metadata: [
                    'shopify_line_id' => $line['id'] ?? null,
                    'price' => $line['price'] ?? null,
                ],
            ));
        }

        return response()->json(['accepted' => true]);
    }

    private function mapAddress(array $a): array
    {
        if (empty($a)) return [];
        return [
            'name' => trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')),
            'company' => $a['company'] ?? null,
            'street1' => $a['address1'] ?? null,
            'street2' => $a['address2'] ?? null,
            'city' => $a['city'] ?? null,
            'region' => $a['province'] ?? null,
            'postcode' => $a['zip'] ?? null,
            'country' => $a['country_code'] ?? null,
            'phone' => $a['phone'] ?? null,
        ];
    }
}
