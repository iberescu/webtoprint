<?php

namespace Modules\PIM\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Core\Domain\Concerns\HasUuid;
use Vanilo\Contracts\Buyable;

/**
 * Implements Vanilo's Buyable contract so configured products can be added
 * to Vanilo carts. The unit price isn't stored on the product — it's computed
 * by the Pricing module per configuration. Set ::priceOverride() before
 * adding to a cart so Vanilo records the correct line price.
 */
class Product extends Model implements Buyable
{
    use HasUuid;

    /** Transient unit price for the current cart-add. */
    protected ?float $priceOverride = null;

    protected $table = 'products';

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'description', 'status',
        'requires_design', 'allows_pdf_upload', 'default_bleed_mm',
        'default_safe_margin_mm', 'sort_order', 'metadata_json',
    ];

    protected $casts = [
        'requires_design' => 'bool',
        'allows_pdf_upload' => 'bool',
        'default_bleed_mm' => 'int',
        'default_safe_margin_mm' => 'int',
        'sort_order' => 'int',
        'metadata_json' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(ProductRule::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ProductAsset::class)->orderBy('sort_order');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProductVersion::class)->orderByDesc('version');
    }

    // --- Vanilo Buyable contract ---------------------------------------------

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
        // We don't track stock or sales velocity at the product level.
    }

    public function removeSale(int|float $units = 1): void
    {
        // We don't track stock or sales velocity at the product level.
    }

    public function morphTypeName(): string
    {
        // Persist the full class name so the cart's morph relation resolves
        // without Relation::morphMap setup. The Buyable contract supports
        // this — see vendor/vanilo/framework/src/Contracts/Buyable.php docblock.
        return self::class;
    }

    public function priceOverride(float $unitPrice): static
    {
        $this->priceOverride = $unitPrice;
        return $this;
    }

    // --- Vanilo HasImages contract -------------------------------------------
    // Images flow through our FileStorage / ProductAsset relation, not Vanilo's
    // media library. Stub these to keep the interface satisfied.

    public function hasImage(): bool { return $this->assets()->exists(); }
    public function imageCount(): int { return $this->assets()->count(); }
    public function getThumbnailUrl(): ?string { return null; }
    public function getThumbnailUrls(): Collection { return new Collection(); }
    public function getImageUrl(string $variant = ''): ?string { return null; }
    public function getImageUrls(string $variant = ''): Collection { return new Collection(); }
}
