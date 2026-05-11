<?php

namespace Shop\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Vanilo\Contracts\Buyable;

/**
 * Local snapshot of a print-backend product. Vanilo's cart_items use a
 * morph relation, which means the buyable must be an Eloquent model with
 * a stable id/type combo. The shop doesn't own product data — print does —
 * but it needs an Eloquent row so Vanilo's morph works.
 *
 * Populated lazily by PrintApi::resolveProduct() on cart-add: HTTP GET
 * the print backend, upsert into `products_cache`, return the row.
 */
class Product extends Model implements Buyable
{
    protected $table = 'products_cache';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'slug', 'name', 'sku', 'metadata_json', 'last_synced_at'];

    protected $casts = [
        'metadata_json' => 'array',
        'last_synced_at' => 'datetime',
    ];

    /** Transient unit price set right before cart-add. */
    protected ?float $priceOverride = null;

    public function getId(): string|int
    {
        return (string) $this->getKey();
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function getPrice(): float
    {
        return $this->priceOverride ?? 0.0;
    }

    public function addSale(Carbon $date, int|float $units = 1): void
    {
    }

    public function removeSale(int|float $units = 1): void
    {
    }

    public function morphTypeName(): string
    {
        // FQCN so morph resolves without an explicit morphMap. This is
        // Shop\Models\Product (the shop's local cache), NOT print's
        // Modules\PIM\Domain\Models\Product — by design.
        return self::class;
    }

    public function priceOverride(float $unitPrice): static
    {
        $this->priceOverride = $unitPrice;
        return $this;
    }

    // --- Vanilo HasImages stubs ---------------------------------------------

    public function hasImage(): bool { return false; }
    public function imageCount(): int { return 0; }
    public function getThumbnailUrl(): ?string { return null; }
    public function getThumbnailUrls(): Collection { return new Collection(); }
    public function getImageUrl(string $variant = ''): ?string { return null; }
    public function getImageUrls(string $variant = ''): Collection { return new Collection(); }
}
