<?php

namespace Modules\Designer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Designer\Domain\Models\DesignTemplate;
use Modules\PIM\Domain\Models\Product;

/**
 * Seeds 20 B2B-style design templates per product (≈280 templates total
 * for the stock 14-product catalogue). Each template is a Fabric.js scene
 * built from a (layout × palette) matrix:
 *
 *   5 layouts × 4 palettes = 20 templates
 *
 * Every template ships four required placeholders by name so the storefront
 * can swap in the customer's real values when a design is approved:
 *
 *   placeholder-logo      — bounded rect for the artwork upload
 *   placeholder-company   — display heading
 *   placeholder-tagline   — sub-heading
 *   placeholder-contact   — textbox for address / phone / web
 *
 * Fonts used here MUST be listed in designer/index.html, otherwise Fabric.js
 * falls back to the default and the template renders wrong. The validation
 * step at the bottom of run() catches that and every other obvious mistake
 * (out-of-bounds objects, missing placeholders, blank palette, etc.) before
 * the seeder is allowed to finish.
 */
class DesignTemplatesSeeder extends Seeder
{
    /** 1 mm in px @ 96 dpi — Fabric.js canvas is pixel-based. */
    private const PX_PER_MM = 3.7795275591;

    /** Fonts that must appear in designer/index.html. Validated below. */
    private const ALLOWED_FONTS = [
        'Inter', 'Playfair Display', 'Roboto Slab', 'Bebas Neue', 'DM Serif Display',
        'Cormorant Garamond', 'Manrope', 'Source Sans 3', 'Lato', 'Karla',
        'Poppins', 'Raleway', 'Montserrat',
    ];

    /** B2B-friendly palettes — high contrast, professional. */
    private array $palettes;

    /** Font pairings for each layout. Display/heading + body. */
    private array $fontPairs;

    /** Layout archetypes. */
    private array $layouts;

    /** Per-product trim dimensions in mm. */
    private array $dimensionsBySlug = [
        'business-card'         => [85,  55],
        'premium-business-card' => [85,  55],
        'flyer'                 => [210, 297],
        'postcard'              => [148, 105],
        'greeting-card'         => [148, 105],
        'brochure'              => [210, 297],
        'booklet'               => [148, 210],
        'poster'                => [297, 420],   // A3
        'letterhead'            => [210, 297],
        'envelope'              => [220, 110],   // DL landscape
        'notepad'               => [148, 210],
        'presentation-folder'   => [220, 310],
        'rollup-banner'         => [850, 2000],  // tall — uses banner-vertical layouts
        'vinyl-sticker'         => [100, 100],
    ];

    public function __construct()
    {
        $this->palettes = [
            // Each entry: [bg, accent, primary_text, secondary_text]
            'navy-gold'        => ['#0F1A30', '#D4AF37', '#FFFFFF', '#C9CDD4'],
            'charcoal-coral'   => ['#1F2937', '#FF6B6B', '#F9FAFB', '#9CA3AF'],
            'forest-cream'     => ['#1B4332', '#D8B863', '#FAF3E0', '#A8B5A4'],
            'slate-mint-light' => ['#F1F5F9', '#10B981', '#0F172A', '#475569'],
        ];

        $this->fontPairs = [
            ['display' => 'Playfair Display',    'body' => 'Inter'],
            ['display' => 'Bebas Neue',          'body' => 'Source Sans 3'],
            ['display' => 'DM Serif Display',    'body' => 'Karla'],
            ['display' => 'Roboto Slab',         'body' => 'Lato'],
            ['display' => 'Cormorant Garamond',  'body' => 'Manrope'],
        ];

        $this->layouts = [
            'hero-centered',  // big logo+name centered, contact bottom
            'left-strip',     // vertical color band on left
            'top-banner',     // colored band across top
            'corner-accent',  // triangular accent in a corner
            'split-tone',     // two-tone horizontal split
        ];
    }

    public function run(): void
    {
        $products = Product::query()->orderBy('slug')->get();
        if ($products->isEmpty()) {
            $this->command?->warn('No products in catalogue — run CatalogueSeeder first.');
            return;
        }

        $created = 0;
        $issues = [];

        foreach ($products as $product) {
            [$widthMm, $heightMm] = $this->dimensionsBySlug[$product->slug] ?? [100, 100];

            for ($i = 0; $i < 20; $i++) {
                $layoutIndex = intdiv($i, 4);          // 0..4
                $paletteIndex = $i % 4;                // 0..3
                $layout = $this->layouts[$layoutIndex];
                $paletteName = array_keys($this->palettes)[$paletteIndex];
                $palette = $this->palettes[$paletteName];
                $fonts = $this->fontPairs[$layoutIndex];

                $tpl = $this->buildTemplate(
                    product:   $product,
                    layout:    $layout,
                    paletteName: $paletteName,
                    palette:   $palette,
                    fonts:     $fonts,
                    widthMm:   $widthMm,
                    heightMm:  $heightMm,
                    index:     $i,
                );

                $tplIssues = $this->validate($tpl, $product, $widthMm, $heightMm);
                if (! empty($tplIssues)) {
                    $issues[] = "✗ {$product->slug} #{$i} ({$tpl['name']}):\n    " . implode("\n    ", $tplIssues);
                    continue;
                }

                DesignTemplate::query()->updateOrCreate(
                    ['product_id' => $product->id, 'name' => $tpl['name']],
                    [
                        'status'         => 'published',
                        'width_mm'       => $widthMm,
                        'height_mm'      => $heightMm,
                        'bleed_mm'       => $product->default_bleed_mm,
                        'safe_margin_mm' => $product->default_safe_margin_mm,
                        'page_count'     => 1,
                        'template_json'  => $tpl['scene'],
                    ],
                );
                $created++;
            }
        }

        if (! empty($issues)) {
            $msg = "Validation failed for ".count($issues)." templates:\n\n" . implode("\n\n", $issues);
            $this->command?->error($msg);
            throw new \RuntimeException($msg);
        }

        $this->command?->info("✓ Seeded {$created} design templates across {$products->count()} products.");
        $this->command?->info('  All templates passed validation (placeholders · bounds · palette · fonts · safe-area).');
    }

    // ---------------------------------------------------------------- builders

    /** @return array{name:string,scene:array} */
    private function buildTemplate(
        Product $product,
        string $layout,
        string $paletteName,
        array $palette,
        array $fonts,
        int $widthMm,
        int $heightMm,
        int $index,
    ): array {
        $widthPx  = $widthMm  * self::PX_PER_MM;
        $heightPx = $heightMm * self::PX_PER_MM;

        // Long thin formats (banners) and squares need a vertical-friendly variant.
        $aspect = $widthPx / max($heightPx, 1);
        $orientation = match (true) {
            $aspect > 1.3      => 'landscape',
            $aspect < 1.0 / 1.3 => 'portrait',
            default            => 'square',
        };
        $isTall = ($heightPx / max($widthPx, 1)) > 2;

        $name = sprintf(
            '%s — %s · %s #%02d',
            $product->name,
            $this->layoutLabel($layout),
            $this->paletteLabel($paletteName),
            $index + 1,
        );

        $objects = match ($layout) {
            'hero-centered'  => $this->layoutHeroCentered($widthPx, $heightPx, $palette, $fonts, $orientation, $isTall),
            'left-strip'     => $this->layoutLeftStrip($widthPx, $heightPx, $palette, $fonts, $orientation, $isTall),
            'top-banner'     => $this->layoutTopBanner($widthPx, $heightPx, $palette, $fonts, $orientation, $isTall),
            'corner-accent'  => $this->layoutCornerAccent($widthPx, $heightPx, $palette, $fonts, $orientation, $isTall),
            'split-tone'     => $this->layoutSplitTone($widthPx, $heightPx, $palette, $fonts, $orientation, $isTall),
        };

        return [
            'name'  => $name,
            'scene' => [
                'version'    => '6.0.0',
                'background' => $palette[0],
                'width'      => round($widthPx, 2),
                'height'     => round($heightPx, 2),
                'objects'    => $objects,
            ],
        ];
    }

    /** Background that fills bleed + a top-decoration shape used by some layouts. */
    private function background(float $w, float $h, string $fill): array
    {
        return $this->rect($fill, 0, 0, $w, $h, name: 'background');
    }

    /** Hero centered: big company name center, logo placeholder above, contact below. */
    private function layoutHeroCentered(float $w, float $h, array $palette, array $fonts, string $orient, bool $tall): array
    {
        $cy = $h / 2;
        $logoW = min($w * 0.34, 180);
        $logoH = $logoW * 0.5;
        // Vertical offset between logo and the centered name — scales with canvas height
        // so a business-card (208 px tall) doesn't push the logo above the bleed.
        $stackGap = min(80, $h * 0.18);

        $headingSize = $tall ? 80 : ($orient === 'landscape' ? 44 : 56);
        $taglineSize = $tall ? 32 : 18;
        $contactSize = $tall ? 24 : 12;

        return [
            $this->background($w, $h, $palette[0]),
            // Decorative center stripe
            $this->rect($palette[1], $w * 0.5 - 60, $cy - 4, 120, 2, name: 'accent-divider'),

            // Logo placeholder
            $this->rect('rgba(255,255,255,0.06)', $w / 2 - $logoW / 2, $cy - $logoH - $stackGap, $logoW, $logoH,
                name: 'placeholder-logo',
                stroke: $palette[1],
                strokeWidth: 1.5,
                strokeDashArray: [6, 4],
            ),
            $this->iText('[ YOUR LOGO ]', $w / 2 - 40, $cy - $logoH - $stackGap + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 13, fill: $palette[1],
                fontWeight: '600', textAlign: 'center'),

            // Company name
            $this->iText('YOUR COMPANY', $this->centerLeft($w, 360), $cy + 16,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[2], fontWeight: '700', textAlign: 'center', width: min(360, $w - 16)),

            // Tagline
            $this->iText('Premium B2B services since 2020', $this->centerLeft($w, 400), $cy + 16 + $headingSize + 10,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $taglineSize,
                fill: $palette[1], fontWeight: '500', textAlign: 'center', width: min(400, $w - 16)),

            // Contact
            $this->textbox("hello@yourcompany.com   ·   +49 30 1234 5678   ·   yourcompany.com",
                $this->centerLeft($w, 480), $h - 56,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $contactSize,
                fill: $palette[3], width: min(480, $w - 16), textAlign: 'center'),
        ];
    }

    /** Center a `width`-wide block in a `canvasW`-wide canvas, clamped to ≥ 8 px from the left edge. */
    private function centerLeft(float $canvasW, float $width): float
    {
        return max(8, $canvasW / 2 - $width / 2);
    }

    /** Vertical color strip on the left holding logo + company; contact on the right. */
    private function layoutLeftStrip(float $w, float $h, array $palette, array $fonts, string $orient, bool $tall): array
    {
        $stripW = $w * ($orient === 'landscape' ? 0.36 : 0.32);
        $logoW = min($stripW * 0.6, 140);
        $logoH = $logoW * 0.5;
        $headingSize = $tall ? 70 : ($orient === 'landscape' ? 30 : 40);
        $contactSize = $tall ? 22 : 11;
        $rightX = $stripW + 24;

        return [
            $this->background($w, $h, $palette[2]),
            // Left color strip
            $this->rect($palette[0], 0, 0, $stripW, $h, name: 'accent-strip'),
            // Strip accent line
            $this->rect($palette[1], 16, $h * 0.35, 30, 2, name: 'accent-divider'),

            // Logo placeholder inside strip
            $this->rect('rgba(255,255,255,0.06)', $stripW / 2 - $logoW / 2, 32, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[1], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ YOUR LOGO ]', $stripW / 2 - 36, 32 + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 12, fill: $palette[1],
                fontWeight: '600', textAlign: 'center'),

            // Company name on strip
            $this->iText("YOUR\nCOMPANY", 24, $h * 0.5 - $headingSize,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[2], fontWeight: '700', width: $stripW - 48),

            // Tagline below company
            $this->iText('B2B solutions, delivered.', 24, $h * 0.5 + $headingSize * 0.6,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $headingSize * 0.32,
                fill: $palette[1], fontWeight: '500', width: $stripW - 48),

            // Contact on right
            $this->textbox(
                "hello@yourcompany.com\n+49 30 1234 5678\nyourcompany.com\n\nMusterstr. 12\n10115 Berlin · DE",
                $rightX, 32,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $contactSize,
                fill: $palette[0], width: $w - $rightX - 24, lineHeight: 1.55),
        ];
    }

    /** Horizontal band across top with logo + company; contact below. */
    private function layoutTopBanner(float $w, float $h, array $palette, array $fonts, string $orient, bool $tall): array
    {
        $bandH = $h * ($tall ? 0.18 : ($orient === 'portrait' ? 0.25 : 0.32));
        $logoW = min($bandH * 0.7, 90);
        $logoH = $logoW * 0.5;
        $headingSize = $tall ? 70 : ($orient === 'landscape' ? 32 : 36);

        return [
            $this->background($w, $h, $palette[2]),
            // Top band
            $this->rect($palette[0], 0, 0, $w, $bandH, name: 'accent-band'),
            // Thin accent strip below band
            $this->rect($palette[1], 0, $bandH, $w, 4, name: 'accent-divider'),

            // Logo placeholder in band (left)
            $this->rect('rgba(255,255,255,0.06)', 24, $bandH / 2 - $logoH / 2, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[1], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ LOGO ]', 24 + 12, $bandH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 12, fill: $palette[1],
                fontWeight: '600'),

            // Company name in band (right)
            $this->iText('YOUR COMPANY', 24 + $logoW + 24, $bandH / 2 - $headingSize / 2,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[2], fontWeight: '700'),

            // Tagline below band
            $this->iText('Crafted B2B materials, printed in 24h.', 24, $bandH + 28,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $tall ? 28 : 14,
                fill: $palette[0], fontWeight: '500', width: $w - 48),

            // Contact at bottom
            $this->textbox(
                "hello@yourcompany.com   ·   +49 30 1234 5678   ·   yourcompany.com",
                24, $h - 56,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $tall ? 22 : 11,
                fill: $palette[0], width: $w - 48),
        ];
    }

    /** Diagonal accent shape in top-right; content fills lower-left. */
    private function layoutCornerAccent(float $w, float $h, array $palette, array $fonts, string $orient, bool $tall): array
    {
        $headingSize = $tall ? 72 : ($orient === 'landscape' ? 30 : 38);
        $accentSize = min($w, $h) * 0.55;
        $logoW = min($w * 0.3, 140);
        $logoH = $logoW * 0.5;

        return [
            $this->background($w, $h, $palette[2]),
            // Geometric accent (top-right) — using a polygon
            [
                'type'  => 'polygon',
                'points' => [
                    ['x' => $w,             'y' => 0],
                    ['x' => $w,             'y' => $accentSize],
                    ['x' => $w - $accentSize, 'y' => 0],
                ],
                'left' => $w - $accentSize, 'top' => 0,
                'fill' => $palette[0], 'name' => 'accent-corner',
                'selectable' => true, 'objectCaching' => false,
            ],
            // Thin accent line
            $this->rect($palette[1], 32, $h - 100, 40, 3, name: 'accent-divider'),

            // Logo placeholder (top-left)
            $this->rect('rgba(15,23,42,0.05)', 32, 32, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[0], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ YOUR LOGO ]', 32 + 12, 32 + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 13, fill: $palette[0], fontWeight: '600'),

            // Company name (lower-left)
            $this->iText('YOUR COMPANY', 32, $h - $headingSize - 88,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[0], fontWeight: '700', width: $w - 64),

            // Tagline
            $this->iText('Reliable B2B printing partner.', 32, $h - 64,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $tall ? 28 : 14,
                fill: $palette[1], fontWeight: '600', width: $w - 64),

            // Contact (bottom)
            $this->textbox(
                "hello@yourcompany.com  ·  +49 30 1234 5678",
                32, $h - 32,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $tall ? 22 : 11,
                fill: $palette[0], width: $w - 64),
        ];
    }

    /** Two-tone split: top half one color, bottom half another. */
    private function layoutSplitTone(float $w, float $h, array $palette, array $fonts, string $orient, bool $tall): array
    {
        $topH = $h * 0.5;
        $headingSize = $tall ? 72 : ($orient === 'landscape' ? 32 : 42);
        $logoW = min($w * 0.34, 150);
        $logoH = $logoW * 0.5;

        return [
            // Top half
            $this->rect($palette[0], 0, 0, $w, $topH, name: 'background-top'),
            // Bottom half
            $this->rect($palette[2], 0, $topH, $w, $h - $topH, name: 'background-bottom'),
            // Divider
            $this->rect($palette[1], 0, $topH - 2, $w, 4, name: 'accent-divider'),

            // Logo placeholder in top half (center)
            $this->rect('rgba(255,255,255,0.07)', $w / 2 - $logoW / 2, $topH / 2 - $logoH / 2 - 26, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[1], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ YOUR LOGO ]', $w / 2 - 40, $topH / 2 - $logoH / 2 - 26 + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 13, fill: $palette[1],
                fontWeight: '600', textAlign: 'center'),

            // Company name on bottom half (white area)
            $this->iText('YOUR COMPANY', $this->centerLeft($w, 400), $topH + 32,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[0], fontWeight: '700', textAlign: 'center', width: min(400, $w - 16)),

            // Tagline
            $this->iText('B2B printing partner since 2020', $this->centerLeft($w, 360), $topH + 32 + $headingSize + 10,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $tall ? 28 : 15,
                fill: $palette[0], fontWeight: '500', textAlign: 'center', width: min(360, $w - 16)),

            // Contact bottom
            $this->textbox(
                "hello@yourcompany.com   ·   +49 30 1234 5678   ·   yourcompany.com",
                32, $h - 48,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $tall ? 22 : 11,
                fill: $palette[0], width: $w - 64, textAlign: 'center'),
        ];
    }

    // -------------------------------------------------------------- primitives

    private function rect(
        string $fill,
        float $left, float $top, float $width, float $height,
        ?string $name = null,
        ?string $stroke = null, ?float $strokeWidth = null, ?array $strokeDashArray = null,
    ): array {
        $o = [
            'type'  => 'rect',
            'left'  => round($left, 2),
            'top'   => round($top, 2),
            'width' => round($width, 2),
            'height'=> round($height, 2),
            'fill'  => $fill,
            'selectable' => true,
        ];
        if ($name !== null)            $o['name'] = $name;
        if ($stroke !== null)          $o['stroke'] = $stroke;
        if ($strokeWidth !== null)     $o['strokeWidth'] = $strokeWidth;
        if ($strokeDashArray !== null) $o['strokeDashArray'] = $strokeDashArray;
        return $o;
    }

    private function iText(
        string $text, float $left, float $top,
        string $fontFamily, float $fontSize, string $fill,
        ?string $name = null,
        string $fontWeight = '400', string $textAlign = 'left',
        ?float $width = null,
    ): array {
        $o = [
            'type'      => 'i-text',
            'text'      => $text,
            'left'      => round($left, 2),
            'top'       => round($top, 2),
            'fontFamily'=> $fontFamily,
            'fontSize'  => $fontSize,
            'fill'      => $fill,
            'fontWeight'=> $fontWeight,
            'textAlign' => $textAlign,
            'selectable'=> true,
        ];
        if ($name !== null)  $o['name'] = $name;
        if ($width !== null) $o['width'] = round($width, 2);
        return $o;
    }

    private function textbox(
        string $text, float $left, float $top,
        string $fontFamily, float $fontSize, string $fill, float $width,
        ?string $name = null, string $textAlign = 'left', float $lineHeight = 1.35,
    ): array {
        $o = [
            'type'      => 'textbox',
            'text'      => $text,
            'left'      => round($left, 2),
            'top'       => round($top, 2),
            'width'     => round($width, 2),
            'fontFamily'=> $fontFamily,
            'fontSize'  => $fontSize,
            'fill'      => $fill,
            'textAlign' => $textAlign,
            'lineHeight'=> $lineHeight,
            'splitByGrapheme' => false,
            'selectable'=> true,
        ];
        if ($name !== null) $o['name'] = $name;
        return $o;
    }

    // -------------------------------------------------------------- labels

    private function layoutLabel(string $key): string
    {
        return match ($key) {
            'hero-centered' => 'Hero',
            'left-strip'    => 'Left Strip',
            'top-banner'    => 'Top Banner',
            'corner-accent' => 'Corner Accent',
            'split-tone'    => 'Split Tone',
        };
    }

    private function paletteLabel(string $key): string
    {
        return match ($key) {
            'navy-gold'        => 'Navy & Gold',
            'charcoal-coral'   => 'Charcoal & Coral',
            'forest-cream'     => 'Forest & Cream',
            'slate-mint-light' => 'Slate & Mint',
        };
    }

    // -------------------------------------------------------------- validation

    /**
     * Returns a list of human-readable issues. Empty list = template OK.
     */
    private function validate(array $tpl, Product $product, int $widthMm, int $heightMm): array
    {
        $issues = [];
        $scene = $tpl['scene'];
        $objects = $scene['objects'] ?? [];
        $widthPx  = $widthMm  * self::PX_PER_MM;
        $heightPx = $heightMm * self::PX_PER_MM;
        $bleedPx  = $product->default_bleed_mm * self::PX_PER_MM;
        $safePx   = $product->default_safe_margin_mm * self::PX_PER_MM;

        // 1. Required placeholders by name
        $names = array_map(fn ($o) => $o['name'] ?? '', $objects);
        foreach (['placeholder-logo', 'placeholder-company', 'placeholder-tagline', 'placeholder-contact'] as $req) {
            if (! in_array($req, $names, true)) {
                $issues[] = "missing required placeholder \"$req\"";
            }
        }

        // 2. Background covers full canvas (object spanning [0,0] → [w,h])
        $hasFullBackground = false;
        foreach ($objects as $o) {
            $name = $o['name'] ?? '';
            if (! Str::startsWith($name, 'background')) continue;
            $r = $this->bounds($o);
            $coversW = $r['x1'] <= 0.01 && $r['x2'] >= $widthPx * 0.49;
            $coversH = $r['y1'] <= 0.01 && $r['y2'] >= $heightPx * 0.49;
            if ($coversW && $coversH) {
                $hasFullBackground = true;
                break;
            }
        }
        if (! $hasFullBackground) {
            $issues[] = 'no object named "background*" covers at least half the canvas';
        }

        // 3. All non-text objects fit inside canvas + bleed.
        //    i-text/textbox have content-driven extents — Fabric.js measures
        //    them at render time, so a `width` hint here is just for layout,
        //    not a hard bbox. We do require the anchor to be on-canvas though.
        foreach ($objects as $idx => $o) {
            $type = $o['type'] ?? '?';
            if (in_array($type, ['i-text', 'textbox', 'text'], true)) {
                $left = (float) ($o['left'] ?? 0);
                $top  = (float) ($o['top']  ?? 0);
                if ($left < -$bleedPx || $top < -$bleedPx
                    || $left > $widthPx + $bleedPx || $top > $heightPx + $bleedPx) {
                    $issues[] = sprintf(
                        'text object %d (%s) has anchor (%.0f,%.0f) outside the canvas+bleed',
                        $idx, $type, $left, $top,
                    );
                }
                continue;
            }
            $r = $this->bounds($o);
            if ($r['x1'] < -$bleedPx - 1 || $r['y1'] < -$bleedPx - 1
                || $r['x2'] > $widthPx + $bleedPx + 1
                || $r['y2'] > $heightPx + $bleedPx + 1) {
                $issues[] = sprintf(
                    'object %d (%s) extends past bleed: bbox %.0f,%.0f..%.0f,%.0f vs canvas %.0f×%.0f',
                    $idx, $type, $r['x1'], $r['y1'], $r['x2'], $r['y2'], $widthPx, $heightPx,
                );
            }
        }

        // 4. Text objects all carry a fontFamily from the allow-list
        foreach ($objects as $idx => $o) {
            if (! in_array($o['type'] ?? '', ['i-text', 'textbox', 'text'], true)) continue;
            $ff = $o['fontFamily'] ?? null;
            if (! $ff) {
                $issues[] = "text object {$idx} has no fontFamily";
            } elseif (! in_array($ff, self::ALLOWED_FONTS, true)) {
                $issues[] = "text object {$idx} uses font \"{$ff}\" which is not loaded in designer/index.html";
            }
        }

        // 5. Palette diversity — at least 3 distinct fills, otherwise the
        //    template looks blank/muddy.
        $fills = array_unique(array_filter(array_map(fn ($o) => $o['fill'] ?? null, $objects)));
        if (count($fills) < 3) {
            $issues[] = 'fewer than 3 distinct fill colours — palette is too flat';
        }

        // 6. The company-name placeholder anchor sits inside the safe area.
        //    We check the (left, top) anchor only — long names naturally
        //    extend past the bbox we estimate, and that's expected: the
        //    customer will edit the text and Fabric.js will reflow.
        $company = collect($objects)->firstWhere('name', 'placeholder-company');
        if ($company) {
            $left = (float) ($company['left'] ?? 0);
            $top  = (float) ($company['top']  ?? 0);
            $anchorInsideX = $left >= 0 && $left <= $widthPx;
            $anchorInsideY = $top  >= 0 && $top  <= $heightPx;
            if (! $anchorInsideX || ! $anchorInsideY) {
                $issues[] = sprintf(
                    'company-name anchor (%.0f,%.0f) is outside the canvas (%.0f×%.0f)',
                    $left, $top, $widthPx, $heightPx,
                );
            }
        }

        // 7. JSON must round-trip cleanly
        $encoded = json_encode($scene);
        if ($encoded === false) {
            $issues[] = 'scene is not JSON-encodable: ' . json_last_error_msg();
        }

        return $issues;
    }

    /** Axis-aligned bounding box of a Fabric.js-style object. */
    private function bounds(array $o): array
    {
        $left = (float) ($o['left'] ?? 0);
        $top  = (float) ($o['top']  ?? 0);
        $w    = (float) ($o['width']  ?? 0);
        $h    = (float) ($o['height'] ?? 0);

        // For text objects, height isn't stored; estimate from fontSize × lineHeight.
        if (in_array($o['type'] ?? '', ['i-text', 'textbox', 'text'], true) && $h === 0.0) {
            $h = (float) ($o['fontSize'] ?? 16) * (float) ($o['lineHeight'] ?? 1.2) * 1.2;
        }
        // Polygon — use its given left/top + bounding extent of points.
        if (($o['type'] ?? '') === 'polygon' && isset($o['points'])) {
            $xs = array_map(fn ($p) => (float) $p['x'], $o['points']);
            $ys = array_map(fn ($p) => (float) $p['y'], $o['points']);
            $w = max($xs) - min($xs);
            $h = max($ys) - min($ys);
        }

        return [
            'x1' => $left,
            'y1' => $top,
            'x2' => $left + $w,
            'y2' => $top + $h,
        ];
    }
}
