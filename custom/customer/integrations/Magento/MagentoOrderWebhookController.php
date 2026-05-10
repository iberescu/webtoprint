<?php

namespace Custom\customer\Integrations\Magento;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Distribution\Application\Actions\CreateProductionJob;
use Modules\Distribution\Application\Contracts\ProductionJobInput;

/**
 * Magento order_save webhook adapter. Same pattern as the Shopify adapter:
 * parse Magento's order JSON, build a ProductionJobInput per item, hand to
 * Distribution. Distribution code path is identical regardless of source.
 */
class MagentoOrderWebhookController extends Controller
{
    public function __construct(private readonly CreateProductionJob $create)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderRef = (string) ($payload['increment_id'] ?? $payload['entity_id'] ?? '');

        $customer = [
            'name' => trim(($payload['customer_firstname'] ?? '') . ' ' . ($payload['customer_lastname'] ?? '')),
            'email' => $payload['customer_email'] ?? null,
        ];

        foreach ($payload['items'] ?? [] as $item) {
            $opts = $item['product_options']['info_buyRequest']['super_attribute']
                 ?? $item['product_options']['options']
                 ?? [];

            $this->create->execute(new ProductionJobInput(
                externalOrderRef: $orderRef,
                externalOrderItemRef: (string) ($item['item_id'] ?? $item['id'] ?? ''),
                source: 'magento',
                productName: (string) ($item['name'] ?? 'Item'),
                productSnapshot: [
                    'sku' => $item['sku'] ?? null,
                    'product_type' => $item['product_type'] ?? null,
                    'options' => $item['product_options'] ?? null,
                ],
                configuration: $this->normaliseOptions($opts),
                quantity: (int) ($item['qty_ordered'] ?? 1),
                artworkFileId: $item['artwork_file_id'] ?? null,
                customerSnapshot: array_filter($customer),
                shippingAddress: (array) ($payload['extension_attributes']['shipping_assignments'][0]['shipping']['address'] ?? []),
                billingAddress: (array) ($payload['billing_address'] ?? []),
                metadata: ['magento_item_id' => $item['item_id'] ?? null],
            ));
        }

        return response()->json(['accepted' => true]);
    }

    private function normaliseOptions(array $opts): array
    {
        $out = [];
        foreach ($opts as $key => $value) {
            $out[is_string($key) ? $key : "option_{$key}"] = is_scalar($value) ? (string) $value : $value;
        }
        return $out;
    }
}
