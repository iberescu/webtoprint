<?php

namespace Shop\Services;

use Shop\Models\Product;
use Illuminate\Support\Facades\Http;

/**
 * Typed client over the print backend's /api/v1/internal/* endpoints.
 * The shop has zero knowledge of PIM / Designer / Distribution
 * internals — only this client does.
 */
class PrintApi
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            baseUrl: rtrim((string) config('services.print.base_url'), '/'),
            token: (string) config('services.print.token'),
        );
    }

    /**
     * Fetch a product from print, upsert it into the local cache, and
     * return the local row (an Eloquent Product, ready to be passed to
     * Vanilo's `Cart::addItem`).
     *
     * @throws \DomainException if the product doesn't exist on print.
     */
    public function resolveProduct(string $id, bool $withSnapshot = false): Product
    {
        $url = "{$this->baseUrl}/internal/products/{$id}" . ($withSnapshot ? '?snapshot=1' : '');
        $resp = \Illuminate\Support\Facades\Http::withToken($this->token)->acceptJson()->timeout(10)->get($url);

        if ($resp->status() === 404) {
            throw new \DomainException("Product {$id} not found on print backend.");
        }

        $resp->throw();
        $body = $resp->json();

        $product = Product::query()->updateOrCreate(
            ['id' => $body['id']],
            [
                'slug' => $body['slug'] ?? null,
                'name' => $body['name'] ?? '(unnamed)',
                'sku' => $body['sku'] ?? null,
                'metadata_json' => array_filter([
                    'status' => $body['status'] ?? null,
                    'requires_design' => $body['requires_design'] ?? null,
                    'meta' => $body['metadata_json'] ?? null,
                    'snapshot' => $body['snapshot'] ?? null,
                ], fn ($v) => $v !== null),
                'last_synced_at' => now(),
            ],
        );

        return $product->fresh();
    }

    /**
     * Lightweight preflight lookup. Returns null if the design doesn't
     * exist (we silently accept the order without an artwork).
     *
     * @return array{id:string,preflight_status:?string,status:?string,print_pdf_file_id:?string}|null
     */
    public function designPreflight(string $designId): ?array
    {
        $resp = \Illuminate\Support\Facades\Http::withToken($this->token)
            ->acceptJson()
            ->timeout(10)
            ->get("{$this->baseUrl}/internal/designs/{$designId}/preflight");

        if ($resp->status() === 404) {
            return null;
        }

        $resp->throw();
        return $resp->json();
    }

    /**
     * Post a batch of ProductionJobInput-shaped jobs to print.
     *
     * @param array<int,array<string,mixed>> $jobs
     * @return array<int,array{id:string,job_number:string}>
     */
    public function createProductionJobs(array $jobs): array
    {
        $resp = \Illuminate\Support\Facades\Http::withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post("{$this->baseUrl}/internal/production-jobs/batch", ['jobs' => $jobs]);

        $resp->throw();
        return $resp->json('jobs') ?? [];
    }
}
