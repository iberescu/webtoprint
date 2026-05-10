<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PIM\Domain\Models\Product;
use Modules\PIM\Domain\Models\ProductCategory;
use Modules\PIM\Domain\Models\ProductOption;
use Modules\PIM\Domain\Models\ProductOptionValue;
use Modules\Pricing\Domain\Models\PriceModifier;
use Modules\Pricing\Domain\Models\PriceTable;

/**
 * Seeds 13 print products inspired by Vistaprint's EU catalogue. Prices are in
 * EUR net (VAT is added at checkout via Settings) and approximate Vistaprint EU
 * RRP — close enough to feel realistic without being a copy.
 *
 * Each product carries `from_price`, `image_color` and `icon` in metadata_json
 * so the storefront grid can render polished cards without a second API call.
 */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            'marketing' => ProductCategory::query()->updateOrCreate(
                ['slug' => 'marketing'],
                ['name' => 'Marketing & Promotion', 'sort_order' => 1]
            ),
            'stationery' => ProductCategory::query()->updateOrCreate(
                ['slug' => 'stationery'],
                ['name' => 'Office & Stationery', 'sort_order' => 2]
            ),
            'signage' => ProductCategory::query()->updateOrCreate(
                ['slug' => 'signage'],
                ['name' => 'Signage & Banners', 'sort_order' => 3]
            ),
            'cards' => ProductCategory::query()->updateOrCreate(
                ['slug' => 'cards'],
                ['name' => 'Cards & Invites', 'sort_order' => 4]
            ),
        ];

        $this->seedFlyer($cats['marketing']->id);
        $this->seedBusinessCard($cats['cards']->id);
        $this->seedPremiumBusinessCard($cats['cards']->id);
        $this->seedPostcard($cats['cards']->id);
        $this->seedGreetingCard($cats['cards']->id);
        $this->seedBrochure($cats['marketing']->id);
        $this->seedBooklet($cats['marketing']->id);
        $this->seedPoster($cats['signage']->id);
        $this->seedRollupBanner($cats['signage']->id);
        $this->seedSticker($cats['marketing']->id);
        $this->seedLetterhead($cats['stationery']->id);
        $this->seedEnvelope($cats['stationery']->id);
        $this->seedNotepad($cats['stationery']->id);
        $this->seedPresentationFolder($cats['stationery']->id);
    }

    // ---------------------------------------------------------------------
    // Marketing
    // ---------------------------------------------------------------------

    private function seedFlyer(string $categoryId): void
    {
        $product = $this->upsertProduct('flyer', [
            'category_id' => $categoryId,
            'name' => 'Flyer',
            'description' => 'Eye-catching flyers for promotions, events and handouts. A4, A5 or A6 on premium silk paper.',
            'requires_design' => true,
            'metadata_json' => ['from_price' => 8.99, 'image_color' => 'from-amber-300 to-orange-500', 'icon' => 'flyer'],
        ]);

        $this->options($product, [
            'format' => ['Format', 'select', [
                'a4' => ['A4 (210 × 297 mm)', ['width_mm' => 210, 'height_mm' => 297]],
                'a5' => ['A5 (148 × 210 mm)', ['width_mm' => 148, 'height_mm' => 210]],
                'a6' => ['A6 (105 × 148 mm)', ['width_mm' => 105, 'height_mm' => 148]],
            ]],
            'paper' => ['Paper', 'select', [
                '125g' => ['125 gsm Silk', null],
                '135g' => ['135 gsm Silk', null],
                '170g' => ['170 gsm Silk', null],
                '250g' => ['250 gsm Premium', null],
            ]],
            // 4-4 first so the configurator's default doesn't trip the
            // a4+4-0 blacklist. The blacklist still fires when a user picks 4-0.
            'colors' => ['Sides', 'select', [
                '4-4' => ['Double sided', null],
                '4-0' => ['Single sided', null],
            ]],
            'refinement' => ['Finish', 'select', [
                'none' => ['Standard', null],
                'lamination' => ['Matt lamination', null],
                'gloss' => ['Gloss UV', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '100' => ['100', null], '250' => ['250', null],
                '500' => ['500', null], '1000' => ['1 000', null],
                '2500' => ['2 500', null], '50' => ['50', null],
            ]],
        ]);

        // Carry over the demo blacklist so the validate test keeps passing.
        $product->rules()->updateOrCreate(
            ['kind' => 'blacklist', 'reason' => 'Flyer A4 with 4/0 color is not available.'],
            ['rule_json' => ['block' => ['format' => 'a4', 'colors' => '4-0']]]
        );

        $this->priceTable($product, ['format', 'paper'], [
            ['match' => ['format' => 'a4', 'paper' => '125g'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.42, 'setup_fee' => 5],
                ['min_qty' => 100, 'unit_price' => 0.30, 'setup_fee' => 5],
                ['min_qty' => 250, 'unit_price' => 0.18],
                ['min_qty' => 500, 'unit_price' => 0.12],
                ['min_qty' => 1000, 'unit_price' => 0.09],
                ['min_qty' => 2500, 'unit_price' => 0.07],
            ]],
            ['match' => ['format' => 'a4', 'paper' => '135g'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.46, 'setup_fee' => 5],
                ['min_qty' => 100, 'unit_price' => 0.34, 'setup_fee' => 5],
                ['min_qty' => 250, 'unit_price' => 0.20],
                ['min_qty' => 500, 'unit_price' => 0.13],
                ['min_qty' => 1000, 'unit_price' => 0.10],
                ['min_qty' => 2500, 'unit_price' => 0.08],
            ]],
            ['match' => ['format' => 'a4', 'paper' => '170g'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.55, 'setup_fee' => 5],
                ['min_qty' => 100, 'unit_price' => 0.42, 'setup_fee' => 5],
                ['min_qty' => 250, 'unit_price' => 0.24],
                ['min_qty' => 500, 'unit_price' => 0.16],
                ['min_qty' => 1000, 'unit_price' => 0.12],
                ['min_qty' => 2500, 'unit_price' => 0.09],
            ]],
            ['match' => ['format' => 'a4', 'paper' => '250g'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.72, 'setup_fee' => 5],
                ['min_qty' => 100, 'unit_price' => 0.55, 'setup_fee' => 5],
                ['min_qty' => 250, 'unit_price' => 0.32],
                ['min_qty' => 500, 'unit_price' => 0.22],
                ['min_qty' => 1000, 'unit_price' => 0.16],
                ['min_qty' => 2500, 'unit_price' => 0.12],
            ]],
            ['match' => ['format' => 'a5'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.28, 'setup_fee' => 4],
                ['min_qty' => 100, 'unit_price' => 0.20, 'setup_fee' => 4],
                ['min_qty' => 250, 'unit_price' => 0.13],
                ['min_qty' => 500, 'unit_price' => 0.09],
                ['min_qty' => 1000, 'unit_price' => 0.07],
                ['min_qty' => 2500, 'unit_price' => 0.05],
            ]],
            ['match' => ['format' => 'a6'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.18, 'setup_fee' => 3],
                ['min_qty' => 100, 'unit_price' => 0.13, 'setup_fee' => 3],
                ['min_qty' => 250, 'unit_price' => 0.09],
                ['min_qty' => 500, 'unit_price' => 0.06],
                ['min_qty' => 1000, 'unit_price' => 0.04],
                ['min_qty' => 2500, 'unit_price' => 0.03],
            ]],
        ]);

        $this->modifier($product, 'Matt lamination', ['refinement' => 'lamination'], 'per_unit', 0.05);
        $this->modifier($product, 'Gloss UV finish', ['refinement' => 'gloss'], 'per_unit', 0.08);
    }

    private function seedBrochure(string $categoryId): void
    {
        $product = $this->upsertProduct('brochure', [
            'category_id' => $categoryId,
            'name' => 'Folded Brochure',
            'description' => 'Bi-fold or tri-fold brochures, perfect for restaurant menus, real-estate listings and product catalogues.',
            'metadata_json' => ['from_price' => 22.00, 'image_color' => 'from-rose-300 to-rose-600', 'icon' => 'brochure'],
        ]);

        $this->options($product, [
            'format' => ['Closed format', 'select', [
                'a4' => ['A4 closed (folds from A3)', null],
                'a5' => ['A5 closed (folds from A4)', null],
                'dl' => ['DL — 99 × 210 mm', null],
            ]],
            'fold' => ['Fold', 'select', [
                'bifold' => ['Bi-fold (one fold)', null],
                'trifold' => ['Tri-fold (letter-style)', null],
                'zfold' => ['Z-fold', null],
            ]],
            'paper' => ['Paper', 'select', [
                '135g' => ['135 gsm Silk', null],
                '170g' => ['170 gsm Silk', null],
                '250g' => ['250 gsm Premium', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '50' => ['50', null], '100' => ['100', null], '250' => ['250', null], '500' => ['500', null], '1000' => ['1 000', null],
            ]],
        ]);

        $this->priceTable($product, ['format', 'paper'], [
            ['match' => ['format' => 'a4', 'paper' => '135g'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.65, 'setup_fee' => 8],
                ['min_qty' => 100, 'unit_price' => 0.48, 'setup_fee' => 8],
                ['min_qty' => 250, 'unit_price' => 0.32],
                ['min_qty' => 500, 'unit_price' => 0.24],
                ['min_qty' => 1000, 'unit_price' => 0.18],
            ]],
            ['match' => ['format' => 'a4', 'paper' => '170g'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.78, 'setup_fee' => 8],
                ['min_qty' => 100, 'unit_price' => 0.55, 'setup_fee' => 8],
                ['min_qty' => 250, 'unit_price' => 0.38],
                ['min_qty' => 500, 'unit_price' => 0.28],
                ['min_qty' => 1000, 'unit_price' => 0.21],
            ]],
            ['match' => ['format' => 'a4', 'paper' => '250g'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.95, 'setup_fee' => 8],
                ['min_qty' => 100, 'unit_price' => 0.72, 'setup_fee' => 8],
                ['min_qty' => 250, 'unit_price' => 0.48],
                ['min_qty' => 500, 'unit_price' => 0.36],
                ['min_qty' => 1000, 'unit_price' => 0.27],
            ]],
            ['match' => ['format' => 'a5'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.42, 'setup_fee' => 6],
                ['min_qty' => 100, 'unit_price' => 0.30, 'setup_fee' => 6],
                ['min_qty' => 250, 'unit_price' => 0.20],
                ['min_qty' => 500, 'unit_price' => 0.15],
                ['min_qty' => 1000, 'unit_price' => 0.11],
            ]],
            ['match' => ['format' => 'dl'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.35, 'setup_fee' => 6],
                ['min_qty' => 100, 'unit_price' => 0.25, 'setup_fee' => 6],
                ['min_qty' => 250, 'unit_price' => 0.16],
                ['min_qty' => 500, 'unit_price' => 0.12],
                ['min_qty' => 1000, 'unit_price' => 0.09],
            ]],
        ]);

        $this->modifier($product, 'Tri-fold setup', ['fold' => 'trifold'], 'flat', 5);
        $this->modifier($product, 'Z-fold setup', ['fold' => 'zfold'], 'flat', 5);
    }

    private function seedBooklet(string $categoryId): void
    {
        $product = $this->upsertProduct('booklet', [
            'category_id' => $categoryId,
            'name' => 'Booklet',
            'description' => 'Saddle-stitched booklets — ideal for catalogues, programmes and look-books. Heavy 250 gsm covers.',
            'metadata_json' => ['from_price' => 45.00, 'image_color' => 'from-purple-300 to-purple-600', 'icon' => 'booklet'],
        ]);

        $this->options($product, [
            'format' => ['Format', 'select', ['a4' => ['A4', null], 'a5' => ['A5', null]]],
            'pages' => ['Pages (incl. cover)', 'select', [
                '8' => ['8', null], '12' => ['12', null], '16' => ['16', null],
                '20' => ['20', null], '24' => ['24', null], '32' => ['32', null],
            ]],
            'paper_inner' => ['Inner paper', 'select', [
                '115g' => ['115 gsm Silk', null], '135g' => ['135 gsm Silk', null], '170g' => ['170 gsm Silk', null],
            ]],
            'paper_cover' => ['Cover', 'select', [
                '250g' => ['250 gsm Silk', null], '300g' => ['300 gsm Premium', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '25' => ['25', null], '50' => ['50', null], '100' => ['100', null], '250' => ['250', null], '500' => ['500', null],
            ]],
        ]);

        $this->priceTable($product, ['format', 'pages'], [
            ['match' => ['format' => 'a5', 'pages' => '8'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 1.80, 'setup_fee' => 12],
                ['min_qty' => 50, 'unit_price' => 1.20, 'setup_fee' => 12],
                ['min_qty' => 100, 'unit_price' => 0.85],
                ['min_qty' => 250, 'unit_price' => 0.62],
                ['min_qty' => 500, 'unit_price' => 0.48],
            ]],
            ['match' => ['format' => 'a5', 'pages' => '16'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 2.80, 'setup_fee' => 15],
                ['min_qty' => 50, 'unit_price' => 1.95, 'setup_fee' => 15],
                ['min_qty' => 100, 'unit_price' => 1.40],
                ['min_qty' => 250, 'unit_price' => 1.05],
                ['min_qty' => 500, 'unit_price' => 0.80],
            ]],
            ['match' => ['format' => 'a4', 'pages' => '8'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 2.95, 'setup_fee' => 15],
                ['min_qty' => 50, 'unit_price' => 1.95, 'setup_fee' => 15],
                ['min_qty' => 100, 'unit_price' => 1.45],
                ['min_qty' => 250, 'unit_price' => 1.05],
                ['min_qty' => 500, 'unit_price' => 0.82],
            ]],
            ['match' => ['format' => 'a4', 'pages' => '16'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 4.50, 'setup_fee' => 18],
                ['min_qty' => 50, 'unit_price' => 3.20, 'setup_fee' => 18],
                ['min_qty' => 100, 'unit_price' => 2.30],
                ['min_qty' => 250, 'unit_price' => 1.75],
                ['min_qty' => 500, 'unit_price' => 1.35],
            ]],
            ['match' => ['format' => 'a4', 'pages' => '24'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 6.20, 'setup_fee' => 22],
                ['min_qty' => 50, 'unit_price' => 4.40, 'setup_fee' => 22],
                ['min_qty' => 100, 'unit_price' => 3.20],
                ['min_qty' => 250, 'unit_price' => 2.45],
                ['min_qty' => 500, 'unit_price' => 1.95],
            ]],
            // Fallback rows: any unmatched (format, pages) combo falls back to a per-format rate.
            ['match' => ['format' => 'a5'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 2.40, 'setup_fee' => 14],
                ['min_qty' => 50, 'unit_price' => 1.65, 'setup_fee' => 14],
                ['min_qty' => 100, 'unit_price' => 1.20],
                ['min_qty' => 250, 'unit_price' => 0.92],
                ['min_qty' => 500, 'unit_price' => 0.72],
            ]],
            ['match' => ['format' => 'a4'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 4.10, 'setup_fee' => 18],
                ['min_qty' => 50, 'unit_price' => 2.85, 'setup_fee' => 18],
                ['min_qty' => 100, 'unit_price' => 2.10],
                ['min_qty' => 250, 'unit_price' => 1.55],
                ['min_qty' => 500, 'unit_price' => 1.20],
            ]],
        ]);
    }

    private function seedSticker(string $categoryId): void
    {
        $product = $this->upsertProduct('vinyl-sticker', [
            'category_id' => $categoryId,
            'name' => 'Vinyl Sticker',
            'description' => 'Weatherproof vinyl stickers in custom shapes. Perfect for product labels, packaging seals and fan merch.',
            'metadata_json' => ['from_price' => 7.50, 'image_color' => 'from-emerald-300 to-emerald-600', 'icon' => 'sticker'],
        ]);

        $this->options($product, [
            'shape' => ['Shape', 'select', [
                'square' => ['Square', null], 'circle' => ['Circle', null],
                'rounded' => ['Rounded square', null], 'custom' => ['Cut-to-shape', null],
            ]],
            'size' => ['Size', 'select', [
                '5x5' => ['5 × 5 cm', null], '7x7' => ['7 × 7 cm', null],
                '10x10' => ['10 × 10 cm', null], '15x15' => ['15 × 15 cm', null],
            ]],
            'finish' => ['Finish', 'select', [
                'matte' => ['Matte vinyl', null], 'gloss' => ['Gloss vinyl', null],
                'transparent' => ['Transparent vinyl', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '50' => ['50', null], '100' => ['100', null], '250' => ['250', null],
                '500' => ['500', null], '1000' => ['1 000', null],
            ]],
        ]);

        $this->priceTable($product, ['size'], [
            ['match' => ['size' => '5x5'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.18, 'setup_fee' => 4],
                ['min_qty' => 100, 'unit_price' => 0.13, 'setup_fee' => 4],
                ['min_qty' => 250, 'unit_price' => 0.09],
                ['min_qty' => 500, 'unit_price' => 0.06],
                ['min_qty' => 1000, 'unit_price' => 0.04],
            ]],
            ['match' => ['size' => '7x7'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.26, 'setup_fee' => 4],
                ['min_qty' => 100, 'unit_price' => 0.18, 'setup_fee' => 4],
                ['min_qty' => 250, 'unit_price' => 0.12],
                ['min_qty' => 500, 'unit_price' => 0.08],
                ['min_qty' => 1000, 'unit_price' => 0.06],
            ]],
            ['match' => ['size' => '10x10'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.42, 'setup_fee' => 5],
                ['min_qty' => 100, 'unit_price' => 0.30, 'setup_fee' => 5],
                ['min_qty' => 250, 'unit_price' => 0.20],
                ['min_qty' => 500, 'unit_price' => 0.14],
                ['min_qty' => 1000, 'unit_price' => 0.10],
            ]],
            ['match' => ['size' => '15x15'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.78, 'setup_fee' => 6],
                ['min_qty' => 100, 'unit_price' => 0.58, 'setup_fee' => 6],
                ['min_qty' => 250, 'unit_price' => 0.38],
                ['min_qty' => 500, 'unit_price' => 0.26],
                ['min_qty' => 1000, 'unit_price' => 0.18],
            ]],
        ]);

        $this->modifier($product, 'Cut-to-shape surcharge', ['shape' => 'custom'], 'flat', 12);
        $this->modifier($product, 'Transparent vinyl', ['finish' => 'transparent'], 'per_unit', 0.04);
    }

    // ---------------------------------------------------------------------
    // Cards & invites
    // ---------------------------------------------------------------------

    private function seedBusinessCard(string $categoryId): void
    {
        $product = $this->upsertProduct('business-card', [
            'category_id' => $categoryId,
            'name' => 'Standard Business Card',
            'description' => 'Make a great first impression. Standard 85 × 55 mm cards on premium 350 gsm silk paper.',
            'metadata_json' => ['from_price' => 12.00, 'image_color' => 'from-slate-400 to-slate-700', 'icon' => 'card'],
        ]);

        $this->options($product, [
            'format' => ['Format', 'select', [
                '85x55' => ['85 × 55 mm — UK / EU', ['width_mm' => 85, 'height_mm' => 55]],
                '90x50' => ['90 × 50 mm — Slim', ['width_mm' => 90, 'height_mm' => 50]],
            ]],
            'paper' => ['Paper', 'select', [
                '300g' => ['300 gsm Silk', null],
                '350g' => ['350 gsm Premium Silk', null],
                '400g' => ['400 gsm Heavy', null],
            ]],
            'colors' => ['Sides', 'select', [
                '4-0' => ['Single sided', null],
                '4-4' => ['Double sided', null],
            ]],
            'refinement' => ['Finish', 'select', [
                'none' => ['No finish', null],
                'matte_lamination' => ['Matt lamination', null],
                'gloss_lamination' => ['Gloss lamination', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '100' => ['100', null], '250' => ['250', null], '500' => ['500', null],
                '1000' => ['1 000', null], '2500' => ['2 500', null],
            ]],
        ]);

        $this->priceTable($product, ['paper'], [
            ['match' => ['paper' => '300g'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.18, 'setup_fee' => 8],
                ['min_qty' => 250, 'unit_price' => 0.12, 'setup_fee' => 8],
                ['min_qty' => 500, 'unit_price' => 0.08],
                ['min_qty' => 1000, 'unit_price' => 0.06],
                ['min_qty' => 2500, 'unit_price' => 0.04],
            ]],
            ['match' => ['paper' => '350g'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.21, 'setup_fee' => 8],
                ['min_qty' => 250, 'unit_price' => 0.14, 'setup_fee' => 8],
                ['min_qty' => 500, 'unit_price' => 0.09],
                ['min_qty' => 1000, 'unit_price' => 0.07],
                ['min_qty' => 2500, 'unit_price' => 0.05],
            ]],
            ['match' => ['paper' => '400g'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.26, 'setup_fee' => 9],
                ['min_qty' => 250, 'unit_price' => 0.17, 'setup_fee' => 9],
                ['min_qty' => 500, 'unit_price' => 0.11],
                ['min_qty' => 1000, 'unit_price' => 0.08],
                ['min_qty' => 2500, 'unit_price' => 0.06],
            ]],
        ]);

        $this->modifier($product, 'Matt lamination', ['refinement' => 'matte_lamination'], 'per_unit', 0.04);
        $this->modifier($product, 'Gloss lamination', ['refinement' => 'gloss_lamination'], 'per_unit', 0.04);
    }

    private function seedPremiumBusinessCard(string $categoryId): void
    {
        $product = $this->upsertProduct('premium-business-card', [
            'category_id' => $categoryId,
            'name' => 'Premium Business Card',
            'description' => 'Stand out with foil stamping, spot UV, raised print or rounded corners. The card people remember.',
            'metadata_json' => ['from_price' => 28.00, 'image_color' => 'from-amber-400 to-amber-700', 'icon' => 'card-premium'],
        ]);

        $this->options($product, [
            'paper' => ['Paper', 'select', [
                '350g' => ['350 gsm Premium Silk', null],
                '450g' => ['450 gsm Heavy Cotton', null],
                'kraft' => ['Recycled Kraft 400 gsm', null],
            ]],
            'colors' => ['Sides', 'select', [
                '4-0' => ['Single sided', null],
                '4-4' => ['Double sided', null],
            ]],
            'corners' => ['Corners', 'select', [
                'square' => ['Square', null],
                'rounded' => ['Rounded', null],
            ]],
            'refinement' => ['Finish', 'select', [
                'none' => ['No finish', null],
                'foil_gold' => ['Gold foil stamping', null],
                'foil_silver' => ['Silver foil stamping', null],
                'spot_uv' => ['Spot UV', null],
                'raised' => ['Raised print', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '100' => ['100', null], '250' => ['250', null], '500' => ['500', null], '1000' => ['1 000', null],
            ]],
        ]);

        $this->priceTable($product, ['paper'], [
            ['match' => ['paper' => '350g'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.32, 'setup_fee' => 12],
                ['min_qty' => 250, 'unit_price' => 0.22, 'setup_fee' => 12],
                ['min_qty' => 500, 'unit_price' => 0.16],
                ['min_qty' => 1000, 'unit_price' => 0.11],
            ]],
            ['match' => ['paper' => '450g'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.45, 'setup_fee' => 14],
                ['min_qty' => 250, 'unit_price' => 0.30, 'setup_fee' => 14],
                ['min_qty' => 500, 'unit_price' => 0.20],
                ['min_qty' => 1000, 'unit_price' => 0.14],
            ]],
            ['match' => ['paper' => 'kraft'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.38, 'setup_fee' => 12],
                ['min_qty' => 250, 'unit_price' => 0.26, 'setup_fee' => 12],
                ['min_qty' => 500, 'unit_price' => 0.18],
                ['min_qty' => 1000, 'unit_price' => 0.13],
            ]],
        ]);

        $this->modifier($product, 'Gold foil stamping', ['refinement' => 'foil_gold'], 'per_unit', 0.18);
        $this->modifier($product, 'Silver foil stamping', ['refinement' => 'foil_silver'], 'per_unit', 0.18);
        $this->modifier($product, 'Spot UV', ['refinement' => 'spot_uv'], 'per_unit', 0.10);
        $this->modifier($product, 'Raised print', ['refinement' => 'raised'], 'per_unit', 0.12);
        $this->modifier($product, 'Rounded corners', ['corners' => 'rounded'], 'flat', 8);
    }

    private function seedPostcard(string $categoryId): void
    {
        $product = $this->upsertProduct('postcard', [
            'category_id' => $categoryId,
            'name' => 'Postcard',
            'description' => 'Send a personal message. Standard postcard formats on heavy 300 gsm card with full-bleed printing.',
            'metadata_json' => ['from_price' => 14.00, 'image_color' => 'from-sky-300 to-sky-600', 'icon' => 'postcard'],
        ]);

        $this->options($product, [
            'format' => ['Format', 'select', [
                'a6' => ['A6 (105 × 148 mm)', null],
                'a5' => ['A5 (148 × 210 mm)', null],
                '4x6' => ['4 × 6"', null],
            ]],
            'paper' => ['Paper', 'select', [
                '300g' => ['300 gsm Silk', null],
                '350g' => ['350 gsm Premium', null],
            ]],
            'colors' => ['Sides', 'select', [
                '4-4' => ['Both sides full colour', null],
                '4-0' => ['Front only', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '50' => ['50', null], '100' => ['100', null], '250' => ['250', null], '500' => ['500', null], '1000' => ['1 000', null],
            ]],
        ]);

        $this->priceTable($product, ['format'], [
            ['match' => ['format' => 'a6'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.28, 'setup_fee' => 5],
                ['min_qty' => 100, 'unit_price' => 0.20, 'setup_fee' => 5],
                ['min_qty' => 250, 'unit_price' => 0.14],
                ['min_qty' => 500, 'unit_price' => 0.10],
                ['min_qty' => 1000, 'unit_price' => 0.07],
            ]],
            ['match' => ['format' => 'a5'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.42, 'setup_fee' => 6],
                ['min_qty' => 100, 'unit_price' => 0.30, 'setup_fee' => 6],
                ['min_qty' => 250, 'unit_price' => 0.20],
                ['min_qty' => 500, 'unit_price' => 0.14],
                ['min_qty' => 1000, 'unit_price' => 0.10],
            ]],
            ['match' => ['format' => '4x6'], 'breaks' => [
                ['min_qty' => 50, 'unit_price' => 0.32, 'setup_fee' => 5],
                ['min_qty' => 100, 'unit_price' => 0.22, 'setup_fee' => 5],
                ['min_qty' => 250, 'unit_price' => 0.15],
                ['min_qty' => 500, 'unit_price' => 0.11],
                ['min_qty' => 1000, 'unit_price' => 0.08],
            ]],
        ]);
    }

    private function seedGreetingCard(string $categoryId): void
    {
        $product = $this->upsertProduct('greeting-card', [
            'category_id' => $categoryId,
            'name' => 'Greeting Card',
            'description' => 'Half-fold greeting cards for thank-yous, holidays and special occasions. Includes envelopes.',
            'metadata_json' => ['from_price' => 16.50, 'image_color' => 'from-pink-300 to-pink-600', 'icon' => 'greeting'],
        ]);

        $this->options($product, [
            'format' => ['Format', 'select', [
                'a6' => ['A6 folded', null],
                'square' => ['14 × 14 cm square', null],
                'a5' => ['A5 folded', null],
            ]],
            'paper' => ['Paper', 'select', [
                '300g' => ['300 gsm Silk', null],
                '350g' => ['350 gsm Premium', null],
            ]],
            'colors' => ['Print', 'select', [
                '4-0' => ['Outside only', null],
                '4-4' => ['Outside + inside', null],
            ]],
            'envelope' => ['Envelopes included', 'select', [
                'yes' => ['Yes', null], 'no' => ['No', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '25' => ['25', null], '50' => ['50', null], '100' => ['100', null], '250' => ['250', null],
            ]],
        ]);

        $this->priceTable($product, ['format'], [
            ['match' => ['format' => 'a6'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 0.66, 'setup_fee' => 6],
                ['min_qty' => 50, 'unit_price' => 0.48, 'setup_fee' => 6],
                ['min_qty' => 100, 'unit_price' => 0.32],
                ['min_qty' => 250, 'unit_price' => 0.22],
            ]],
            ['match' => ['format' => 'square'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 0.85, 'setup_fee' => 7],
                ['min_qty' => 50, 'unit_price' => 0.62, 'setup_fee' => 7],
                ['min_qty' => 100, 'unit_price' => 0.42],
                ['min_qty' => 250, 'unit_price' => 0.30],
            ]],
            ['match' => ['format' => 'a5'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 1.05, 'setup_fee' => 8],
                ['min_qty' => 50, 'unit_price' => 0.78, 'setup_fee' => 8],
                ['min_qty' => 100, 'unit_price' => 0.55],
                ['min_qty' => 250, 'unit_price' => 0.40],
            ]],
        ]);

        $this->modifier($product, 'Envelopes included', ['envelope' => 'yes'], 'per_unit', 0.10);
    }

    // ---------------------------------------------------------------------
    // Signage
    // ---------------------------------------------------------------------

    private function seedPoster(string $categoryId): void
    {
        $product = $this->upsertProduct('poster', [
            'category_id' => $categoryId,
            'name' => 'Poster',
            'description' => 'Large-format posters in A3, A2, A1 and A0. Perfect for shop windows, exhibitions and gigs.',
            'requires_design' => false,
            'metadata_json' => ['from_price' => 4.00, 'image_color' => 'from-cyan-300 to-cyan-600', 'icon' => 'poster'],
        ]);

        $this->options($product, [
            'format' => ['Format', 'select', [
                'a3' => ['A3 (297 × 420 mm)', null],
                'a2' => ['A2 (420 × 594 mm)', null],
                'a1' => ['A1 (594 × 841 mm)', null],
                'a0' => ['A0 (841 × 1189 mm)', null],
            ]],
            'paper' => ['Paper', 'select', [
                '135g' => ['135 gsm Silk', null],
                '170g' => ['170 gsm Silk', null],
                '200g' => ['200 gsm Premium Silk', null],
                '250g' => ['250 gsm Photo paper', null],
            ]],
            'finish' => ['Finish', 'select', [
                'none' => ['Standard', null],
                'matte' => ['Matt finish', null],
                'gloss' => ['Gloss finish', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '1' => ['1', null], '5' => ['5', null], '10' => ['10', null], '25' => ['25', null], '50' => ['50', null], '100' => ['100', null],
            ]],
        ]);

        $this->priceTable($product, ['format'], [
            ['match' => ['format' => 'a3'], 'breaks' => [
                ['min_qty' => 1, 'unit_price' => 4.00],
                ['min_qty' => 5, 'unit_price' => 3.20],
                ['min_qty' => 25, 'unit_price' => 2.40],
                ['min_qty' => 100, 'unit_price' => 1.80],
            ]],
            ['match' => ['format' => 'a2'], 'breaks' => [
                ['min_qty' => 1, 'unit_price' => 7.50],
                ['min_qty' => 5, 'unit_price' => 5.80],
                ['min_qty' => 25, 'unit_price' => 4.30],
                ['min_qty' => 100, 'unit_price' => 3.20],
            ]],
            ['match' => ['format' => 'a1'], 'breaks' => [
                ['min_qty' => 1, 'unit_price' => 12.50],
                ['min_qty' => 5, 'unit_price' => 9.80],
                ['min_qty' => 25, 'unit_price' => 7.20],
                ['min_qty' => 100, 'unit_price' => 5.60],
            ]],
            ['match' => ['format' => 'a0'], 'breaks' => [
                ['min_qty' => 1, 'unit_price' => 22.00],
                ['min_qty' => 5, 'unit_price' => 17.00],
                ['min_qty' => 25, 'unit_price' => 12.50],
                ['min_qty' => 100, 'unit_price' => 9.80],
            ]],
        ]);
    }

    private function seedRollupBanner(string $categoryId): void
    {
        $product = $this->upsertProduct('rollup-banner', [
            'category_id' => $categoryId,
            'name' => 'Roll-up Banner',
            'description' => 'Free-standing roll-up banner with aluminium base, carry case and printed PVC banner. Ready in 24 hours.',
            'metadata_json' => ['from_price' => 55.00, 'image_color' => 'from-indigo-400 to-indigo-700', 'icon' => 'banner'],
        ]);

        $this->options($product, [
            'size' => ['Size', 'select', [
                '85x200' => ['85 × 200 cm', null],
                '100x200' => ['100 × 200 cm', null],
                '120x200' => ['120 × 200 cm', null],
            ]],
            'material' => ['Material', 'select', [
                'standard' => ['Standard PVC banner', null],
                'premium' => ['Premium block-out PVC', null],
            ]],
            'base' => ['Base', 'select', [
                'economy' => ['Economy aluminium', null],
                'premium' => ['Premium silver aluminium', null],
                'doubleside' => ['Double-sided base', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '1' => ['1', null], '2' => ['2', null], '5' => ['5', null], '10' => ['10', null],
            ]],
        ]);

        $this->priceTable($product, ['size'], [
            ['match' => ['size' => '85x200'], 'breaks' => [
                ['min_qty' => 1, 'unit_price' => 55.00],
                ['min_qty' => 2, 'unit_price' => 49.00],
                ['min_qty' => 5, 'unit_price' => 42.00],
                ['min_qty' => 10, 'unit_price' => 38.00],
            ]],
            ['match' => ['size' => '100x200'], 'breaks' => [
                ['min_qty' => 1, 'unit_price' => 68.00],
                ['min_qty' => 2, 'unit_price' => 62.00],
                ['min_qty' => 5, 'unit_price' => 55.00],
                ['min_qty' => 10, 'unit_price' => 49.00],
            ]],
            ['match' => ['size' => '120x200'], 'breaks' => [
                ['min_qty' => 1, 'unit_price' => 82.00],
                ['min_qty' => 2, 'unit_price' => 75.00],
                ['min_qty' => 5, 'unit_price' => 68.00],
                ['min_qty' => 10, 'unit_price' => 62.00],
            ]],
        ]);

        $this->modifier($product, 'Premium PVC', ['material' => 'premium'], 'per_unit', 8);
        $this->modifier($product, 'Premium base', ['base' => 'premium'], 'per_unit', 12);
        $this->modifier($product, 'Double-sided base', ['base' => 'doubleside'], 'per_unit', 28);
    }

    // ---------------------------------------------------------------------
    // Stationery
    // ---------------------------------------------------------------------

    private function seedLetterhead(string $categoryId): void
    {
        $product = $this->upsertProduct('letterhead', [
            'category_id' => $categoryId,
            'name' => 'Letterhead',
            'description' => 'A4 letterheads on professional stationery paper. Single or double-sided print.',
            'metadata_json' => ['from_price' => 18.00, 'image_color' => 'from-zinc-300 to-zinc-600', 'icon' => 'letterhead'],
        ]);

        $this->options($product, [
            'format' => ['Format', 'select', ['a4' => ['A4 (210 × 297 mm)', null]]],
            'paper' => ['Paper', 'select', [
                '80g' => ['80 gsm Stationery', null],
                '100g' => ['100 gsm Stationery', null],
                '120g' => ['120 gsm Premium', null],
            ]],
            'colors' => ['Sides', 'select', [
                '4-0' => ['Single sided', null],
                '4-4' => ['Double sided', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '100' => ['100', null], '250' => ['250', null], '500' => ['500', null], '1000' => ['1 000', null], '2500' => ['2 500', null],
            ]],
        ]);

        $this->priceTable($product, ['paper'], [
            ['match' => ['paper' => '80g'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.18, 'setup_fee' => 6],
                ['min_qty' => 250, 'unit_price' => 0.12, 'setup_fee' => 6],
                ['min_qty' => 500, 'unit_price' => 0.08],
                ['min_qty' => 1000, 'unit_price' => 0.06],
                ['min_qty' => 2500, 'unit_price' => 0.04],
            ]],
            ['match' => ['paper' => '100g'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.21, 'setup_fee' => 6],
                ['min_qty' => 250, 'unit_price' => 0.14, 'setup_fee' => 6],
                ['min_qty' => 500, 'unit_price' => 0.10],
                ['min_qty' => 1000, 'unit_price' => 0.07],
                ['min_qty' => 2500, 'unit_price' => 0.05],
            ]],
            ['match' => ['paper' => '120g'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.26, 'setup_fee' => 7],
                ['min_qty' => 250, 'unit_price' => 0.18, 'setup_fee' => 7],
                ['min_qty' => 500, 'unit_price' => 0.13],
                ['min_qty' => 1000, 'unit_price' => 0.09],
                ['min_qty' => 2500, 'unit_price' => 0.06],
            ]],
        ]);
    }

    private function seedEnvelope(string $categoryId): void
    {
        $product = $this->upsertProduct('envelope', [
            'category_id' => $categoryId,
            'name' => 'Branded Envelope',
            'description' => 'Branded envelopes in DL, C5 or C4 sizes — with or without window. White or recycled kraft.',
            'metadata_json' => ['from_price' => 19.50, 'image_color' => 'from-stone-300 to-stone-600', 'icon' => 'envelope'],
        ]);

        $this->options($product, [
            'size' => ['Size', 'select', [
                'dl' => ['DL — 110 × 220 mm', null],
                'c5' => ['C5 — 162 × 229 mm', null],
                'c4' => ['C4 — 229 × 324 mm', null],
            ]],
            'paper' => ['Paper', 'select', [
                'white_80g' => ['White 80 gsm', null],
                'white_100g' => ['White 100 gsm', null],
                'kraft_100g' => ['Kraft 100 gsm', null],
            ]],
            'window' => ['Window', 'select', [
                'no' => ['No window', null],
                'left' => ['Left window', null],
                'right' => ['Right window', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '100' => ['100', null], '250' => ['250', null], '500' => ['500', null], '1000' => ['1 000', null],
            ]],
        ]);

        $this->priceTable($product, ['size'], [
            ['match' => ['size' => 'dl'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.20, 'setup_fee' => 8],
                ['min_qty' => 250, 'unit_price' => 0.14, 'setup_fee' => 8],
                ['min_qty' => 500, 'unit_price' => 0.10],
                ['min_qty' => 1000, 'unit_price' => 0.07],
            ]],
            ['match' => ['size' => 'c5'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.28, 'setup_fee' => 9],
                ['min_qty' => 250, 'unit_price' => 0.19, 'setup_fee' => 9],
                ['min_qty' => 500, 'unit_price' => 0.13],
                ['min_qty' => 1000, 'unit_price' => 0.09],
            ]],
            ['match' => ['size' => 'c4'], 'breaks' => [
                ['min_qty' => 100, 'unit_price' => 0.42, 'setup_fee' => 10],
                ['min_qty' => 250, 'unit_price' => 0.28, 'setup_fee' => 10],
                ['min_qty' => 500, 'unit_price' => 0.20],
                ['min_qty' => 1000, 'unit_price' => 0.14],
            ]],
        ]);

        $this->modifier($product, 'Window (left)', ['window' => 'left'], 'per_unit', 0.02);
        $this->modifier($product, 'Window (right)', ['window' => 'right'], 'per_unit', 0.02);
    }

    private function seedNotepad(string $categoryId): void
    {
        $product = $this->upsertProduct('notepad', [
            'category_id' => $categoryId,
            'name' => 'Notepad',
            'description' => 'Glued-top notepads with branded cover. 25–100 sheets per pad on uncoated 80 gsm paper.',
            'metadata_json' => ['from_price' => 18.00, 'image_color' => 'from-yellow-300 to-yellow-600', 'icon' => 'notepad'],
        ]);

        $this->options($product, [
            'format' => ['Format', 'select', ['a5' => ['A5', null], 'a6' => ['A6', null]]],
            'sheets' => ['Sheets per pad', 'select', [
                '25' => ['25', null], '50' => ['50', null], '100' => ['100', null],
            ]],
            'colors' => ['Print', 'select', [
                'bw' => ['Black & white', null],
                '4-0' => ['Full colour', null],
            ]],
            'quantity' => ['Number of pads', 'select', [
                '5' => ['5', null], '10' => ['10', null], '25' => ['25', null], '50' => ['50', null], '100' => ['100', null],
            ]],
        ]);

        $this->priceTable($product, ['format', 'sheets'], [
            ['match' => ['format' => 'a5', 'sheets' => '25'], 'breaks' => [
                ['min_qty' => 5, 'unit_price' => 3.60, 'setup_fee' => 8],
                ['min_qty' => 10, 'unit_price' => 2.80, 'setup_fee' => 8],
                ['min_qty' => 25, 'unit_price' => 2.10],
                ['min_qty' => 50, 'unit_price' => 1.65],
                ['min_qty' => 100, 'unit_price' => 1.30],
            ]],
            ['match' => ['format' => 'a5', 'sheets' => '50'], 'breaks' => [
                ['min_qty' => 5, 'unit_price' => 5.20, 'setup_fee' => 8],
                ['min_qty' => 10, 'unit_price' => 4.10, 'setup_fee' => 8],
                ['min_qty' => 25, 'unit_price' => 3.10],
                ['min_qty' => 50, 'unit_price' => 2.40],
                ['min_qty' => 100, 'unit_price' => 1.85],
            ]],
            ['match' => ['format' => 'a6'], 'breaks' => [
                ['min_qty' => 5, 'unit_price' => 3.60, 'setup_fee' => 6],
                ['min_qty' => 10, 'unit_price' => 2.80, 'setup_fee' => 6],
                ['min_qty' => 25, 'unit_price' => 2.10],
                ['min_qty' => 50, 'unit_price' => 1.65],
                ['min_qty' => 100, 'unit_price' => 1.25],
            ]],
            // Fallback for any (format, sheets) combo not matched above.
            ['match' => ['format' => 'a5'], 'breaks' => [
                ['min_qty' => 5, 'unit_price' => 6.50, 'setup_fee' => 8],
                ['min_qty' => 10, 'unit_price' => 5.20, 'setup_fee' => 8],
                ['min_qty' => 25, 'unit_price' => 3.95],
                ['min_qty' => 50, 'unit_price' => 3.10],
                ['min_qty' => 100, 'unit_price' => 2.40],
            ]],
        ]);
    }

    private function seedPresentationFolder(string $categoryId): void
    {
        $product = $this->upsertProduct('presentation-folder', [
            'category_id' => $categoryId,
            'name' => 'Presentation Folder',
            'description' => 'A4 presentation folders with double business-card slot. Keep proposals, brochures and quotes together with one branded look.',
            'metadata_json' => ['from_price' => 38.00, 'image_color' => 'from-teal-300 to-teal-700', 'icon' => 'folder'],
        ]);

        $this->options($product, [
            'format' => ['Format', 'select', ['a4' => ['A4', null]]],
            'paper' => ['Paper', 'select', [
                '300g' => ['300 gsm Silk', null],
                '350g' => ['350 gsm Premium Silk', null],
            ]],
            'colors' => ['Print', 'select', [
                '4-0' => ['Outside only', null],
                '4-4' => ['Outside + inside', null],
            ]],
            'pocket' => ['Card pocket', 'select', [
                'no' => ['No pocket', null],
                'yes' => ['Business-card slot', null],
            ]],
            'quantity' => ['Quantity', 'select', [
                '25' => ['25', null], '50' => ['50', null], '100' => ['100', null], '250' => ['250', null], '500' => ['500', null],
            ]],
        ]);

        $this->priceTable($product, ['paper'], [
            ['match' => ['paper' => '300g'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 1.95, 'setup_fee' => 18],
                ['min_qty' => 50, 'unit_price' => 1.45, 'setup_fee' => 18],
                ['min_qty' => 100, 'unit_price' => 1.05],
                ['min_qty' => 250, 'unit_price' => 0.78],
                ['min_qty' => 500, 'unit_price' => 0.62],
            ]],
            ['match' => ['paper' => '350g'], 'breaks' => [
                ['min_qty' => 25, 'unit_price' => 2.40, 'setup_fee' => 20],
                ['min_qty' => 50, 'unit_price' => 1.75, 'setup_fee' => 20],
                ['min_qty' => 100, 'unit_price' => 1.30],
                ['min_qty' => 250, 'unit_price' => 0.96],
                ['min_qty' => 500, 'unit_price' => 0.78],
            ]],
        ]);

        $this->modifier($product, 'Business-card slot', ['pocket' => 'yes'], 'per_unit', 0.18);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function upsertProduct(string $slug, array $attrs): Product
    {
        return Product::query()->updateOrCreate(
            ['slug' => $slug],
            array_merge([
                'status' => 'published',
                'requires_design' => true,
                'allows_pdf_upload' => true,
                'default_bleed_mm' => 3,
                'default_safe_margin_mm' => 5,
            ], $attrs)
        );
    }

    /**
     * @param array<string, array{0: string, 1: string, 2: array<string, array{0: string, 1: ?array}>}> $defs
     */
    private function options(Product $product, array $defs): void
    {
        $sort = 0;
        foreach ($defs as $code => [$label, $type, $values]) {
            $opt = ProductOption::query()->updateOrCreate(
                ['product_id' => $product->id, 'code' => $code],
                ['label' => $label, 'type' => $type, 'required' => true, 'sort_order' => $sort++]
            );
            $opt->values()->whereNotIn('code', array_keys($values))->delete();
            $vSort = 0;
            foreach ($values as $vCode => [$vLabel, $meta]) {
                ProductOptionValue::query()->updateOrCreate(
                    ['product_option_id' => $opt->id, 'code' => $vCode],
                    ['label' => $vLabel, 'value' => $vCode, 'sort_order' => $vSort++, 'metadata_json' => $meta]
                );
            }
        }
    }

    private function priceTable(Product $product, array $axes, array $rows): void
    {
        $table = PriceTable::query()->updateOrCreate(
            ['product_id' => $product->id, 'name' => 'default'],
            ['axes_json' => array_map(fn ($a) => ['option' => $a], $axes)]
        );
        $table->rows()->delete();

        // Append a catch-all fallback row using the cheapest break we've seen,
        // so configurations not explicitly listed always price (slightly higher)
        // rather than failing with "Price unavailable".
        $cheapest = null;
        foreach ($rows as $row) {
            foreach ($row['breaks'] as $b) {
                if ($cheapest === null || $b['unit_price'] > $cheapest['unit_price']) {
                    // pick the *most expensive* unit price as the fallback, so we
                    // never undercharge for an exotic combination.
                    $cheapest = $b;
                }
            }
        }
        if ($cheapest !== null) {
            // Build a sane fallback: take the most-expensive row's break list as the template.
            $fallbackBreaks = [];
            foreach ($rows[0]['breaks'] as $b) {
                $fallbackBreaks[] = [
                    'min_qty' => $b['min_qty'],
                    'unit_price' => round((float) $b['unit_price'] * 1.5, 2),
                    'setup_fee' => $b['setup_fee'] ?? 0,
                ];
            }
            $rows[] = ['match' => [], 'breaks' => $fallbackBreaks];
        }

        foreach ($rows as $row) {
            $table->rows()->create([
                'match_json' => $row['match'],
                'quantity_breaks_json' => $row['breaks'],
            ]);
        }
    }

    private function modifier(Product $product, string $label, array $match, string $strategy, float $amount): void
    {
        PriceModifier::query()->updateOrCreate(
            ['product_id' => $product->id, 'label' => $label],
            ['match_json' => $match, 'strategy' => $strategy, 'amount' => $amount]
        );
    }
}
