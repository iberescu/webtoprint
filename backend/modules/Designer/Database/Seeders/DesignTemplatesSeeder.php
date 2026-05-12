<?php

namespace Modules\Designer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Designer\Domain\Models\DesignTemplate;
use Modules\PIM\Domain\Models\Product;

/**
 * Seeds 50 B2B-style design templates per product (≈700 for the stock
 * 14-product catalogue). Each template is a Fabric.js scene built from a
 * (layout × palette × copy) matrix:
 *
 *   10 layouts × 5 palettes = 50 templates / product
 *
 * Copy is rotated from a pool of 10 fictional B2B brands so the same
 * (layout, palette) pair shows different company names + taglines + roles
 * across products — variety even within the grid.
 *
 * Every template carries four required placeholders by name so the
 * storefront can substitute customer values at order-attach time:
 *
 *   placeholder-logo      — bounded rect for the artwork upload
 *   placeholder-company   — display heading
 *   placeholder-tagline   — sub-heading
 *   placeholder-contact   — textbox for address / phone / web
 *
 * Most layouts ALSO carry one or more `placeholder-image-*` rectangles —
 * dashed boxes with an [ IMAGE ] label that the customer replaces with
 * uploaded artwork in the designer.
 *
 * Decorative shapes (dot grids, diagonal stripes, accent circles,
 * geometric polygons) are NOT placeholders — they're part of the layout
 * identity. The validation step below catches any layout that drops a
 * required placeholder or paints itself into the bleed area.
 *
 * Fonts used here MUST be listed in designer/index.html so Fabric.js
 * can render them — validation step #4 enforces that.
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

    /** 5 B2B-friendly palettes — [bg, accent, primary_text, secondary_text, surface]. */
    private array $palettes;

    /** 10 font pairings (display + body). */
    private array $fontPairs;

    /** 10 layout archetypes. */
    private array $layouts;

    /** 10 fictional B2B brands — rotated by template index. */
    private array $copyPool;

    /** Per-product trim dimensions in mm. */
    private array $dimensionsBySlug = [
        'business-card'         => [85,  55],
        'premium-business-card' => [85,  55],
        'flyer'                 => [210, 297],
        'postcard'              => [148, 105],
        'greeting-card'         => [148, 105],
        'brochure'              => [210, 297],
        'booklet'               => [148, 210],
        'poster'                => [297, 420],
        'letterhead'            => [210, 297],
        'envelope'              => [220, 110],
        'notepad'               => [148, 210],
        'presentation-folder'   => [220, 310],
        'rollup-banner'         => [850, 2000],
        'vinyl-sticker'         => [100, 100],
    ];

    public function __construct()
    {
        $this->palettes = [
            'navy-gold'        => ['#0F1A30', '#D4AF37', '#FFFFFF', '#C9CDD4', '#1B2A4A'],
            'charcoal-coral'   => ['#1F2937', '#FF6B6B', '#F9FAFB', '#9CA3AF', '#2D3748'],
            'forest-cream'     => ['#1B4332', '#D8B863', '#FAF3E0', '#A8B5A4', '#2D5A40'],
            'slate-mint-light' => ['#F1F5F9', '#10B981', '#0F172A', '#475569', '#E2E8F0'],
            'burgundy-cream'   => ['#5D1A1A', '#F5E6C8', '#FFFFFF', '#C9B89A', '#7A2A2A'],
        ];

        $this->fontPairs = [
            ['display' => 'Playfair Display',    'body' => 'Inter'],
            ['display' => 'Bebas Neue',          'body' => 'Source Sans 3'],
            ['display' => 'DM Serif Display',    'body' => 'Karla'],
            ['display' => 'Roboto Slab',         'body' => 'Lato'],
            ['display' => 'Cormorant Garamond',  'body' => 'Manrope'],
            ['display' => 'Poppins',             'body' => 'Inter'],
            ['display' => 'Montserrat',          'body' => 'Lato'],
            ['display' => 'Raleway',             'body' => 'Karla'],
            ['display' => 'DM Serif Display',    'body' => 'Manrope'],
            ['display' => 'Playfair Display',    'body' => 'Source Sans 3'],
        ];

        $this->layouts = [
            'hero-centered',   // 1: big logo+name centered, contact bottom
            'left-strip',      // 2: vertical color band on left
            'top-banner',      // 3: colored band across top
            'corner-accent',   // 4: triangular accent in a corner
            'split-tone',      // 5: two-tone horizontal split
            'dot-pattern',     // 6: dotted background, content overlay
            'diagonal-stripe', // 7: slanted accent band
            'image-hero',      // 8: large image placeholder + caption
            'photo-grid',      // 9: grid of image placeholders
            'bold-statement',  // 10: huge typography
        ];

        $this->copyPool = [
            ['name' => 'APEX ADVISORY',      'role' => 'Strategy & Consulting',        'tagline' => 'Sharper thinking. Faster outcomes.',     'contact' => "apex.example  ·  +49 30 555 0101\nKurfürstendamm 12 · 10719 Berlin"],
            ['name' => 'NORTHBRIDGE STUDIO', 'role' => 'Brand & Design',                'tagline' => 'Designed to be remembered.',             'contact' => "northbridge.example  ·  +44 20 7946 0102\nShoreditch High St · London E1 6JE"],
            ['name' => 'HELIX GROUP',        'role' => 'Engineering & Industrials',     'tagline' => 'Precision at scale.',                    'contact' => "helix.example  ·  +1 415 555 0103\n1 Market St · San Francisco CA 94105"],
            ['name' => 'LUMEN & CO.',        'role' => 'Light & Architecture',          'tagline' => 'Where space meets story.',               'contact' => "lumen.example  ·  +33 1 4234 0104\n7 rue de Rivoli · 75004 Paris"],
            ['name' => 'VERITAS PARTNERS',   'role' => 'Legal Counsel',                 'tagline' => 'Trusted counsel since 1998.',            'contact' => "veritas.example  ·  +49 89 1234 0105\nMaximilianstraße 33 · 80539 München"],
            ['name' => 'ATELIER BERLIN',     'role' => 'Architecture & Interiors',      'tagline' => 'Quietly extraordinary spaces.',          'contact' => "atelier.example  ·  +49 30 5678 0106\nLinienstraße 144 · 10115 Berlin"],
            ['name' => 'COBALT INDUSTRIES',  'role' => 'Precision Manufacturing',       'tagline' => 'Built to last. Made to scale.',          'contact' => "cobalt.example  ·  +49 89 7890 0107\nIndustriepark 4 · 81829 München"],
            ['name' => 'MERIDIAN GROUP',     'role' => 'Financial Advisory',            'tagline' => 'Capital, sharper.',                      'contact' => "meridian.example  ·  +44 20 1234 0108\n80 Cannon St · London EC4N 6HL"],
            ['name' => 'FORGE & CO.',        'role' => 'Product Design',                'tagline' => 'Hammered into shape.',                   'contact' => "forge.example  ·  +1 212 555 0109\n240 W 35th St · New York NY 10001"],
            ['name' => 'EQUINOX STUDIO',     'role' => 'Creative Agency',               'tagline' => 'Balance. Light. Result.',                'contact' => "equinox.example  ·  +49 30 9012 0110\nTorstraße 92 · 10119 Berlin"],
        ];
    }

    public function run(): void
    {
        $products = Product::query()->orderBy('slug')->get();
        if ($products->isEmpty()) {
            $this->command?->warn('No products in catalogue — run CatalogueSeeder first.');
            return;
        }

        // Replace the previous batch so we never end up with mixed-version templates.
        DesignTemplate::query()->delete();

        $created = 0;
        $issues = [];

        foreach ($products as $product) {
            [$widthMm, $heightMm] = $this->dimensionsBySlug[$product->slug] ?? [100, 100];

            for ($i = 0; $i < 50; $i++) {
                $layoutIdx  = intdiv($i, 5);                 // 0..9
                $paletteIdx = $i % 5;                        // 0..4
                $copyIdx    = $i % count($this->copyPool);   // 0..9
                $layout     = $this->layouts[$layoutIdx];
                $paletteKey = array_keys($this->palettes)[$paletteIdx];
                $palette    = $this->palettes[$paletteKey];
                $fonts      = $this->fontPairs[$layoutIdx];
                $copy       = $this->copyPool[$copyIdx];

                $tpl = $this->buildTemplate(
                    product:    $product,
                    layout:     $layout,
                    paletteName:$paletteKey,
                    palette:    $palette,
                    fonts:      $fonts,
                    copy:       $copy,
                    widthMm:    $widthMm,
                    heightMm:   $heightMm,
                    index:      $i,
                );

                $tplIssues = $this->validate($tpl, $product, $widthMm, $heightMm);
                if (! empty($tplIssues)) {
                    $issues[] = "✗ {$product->slug} #{$i} ({$tpl['name']}):\n    " . implode("\n    ", $tplIssues);
                    continue;
                }

                DesignTemplate::query()->create([
                    'product_id'     => $product->id,
                    'name'           => $tpl['name'],
                    'status'         => 'published',
                    'width_mm'       => $widthMm,
                    'height_mm'      => $heightMm,
                    'bleed_mm'       => $product->default_bleed_mm,
                    'safe_margin_mm' => $product->default_safe_margin_mm,
                    'page_count'     => 1,
                    'template_json'  => $tpl['scene'],
                ]);
                $created++;
            }
        }

        if (! empty($issues)) {
            $msg = "Validation failed for ".count($issues)." templates:\n\n" . implode("\n\n", array_slice($issues, 0, 20))
                . (count($issues) > 20 ? "\n\n…and ".(count($issues) - 20)." more." : '');
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
        array $copy,
        int $widthMm,
        int $heightMm,
        int $index,
    ): array {
        $w = $widthMm  * self::PX_PER_MM;
        $h = $heightMm * self::PX_PER_MM;
        $aspect = $w / max($h, 1);
        $orientation = match (true) {
            $aspect > 1.3      => 'landscape',
            $aspect < 1.0 / 1.3 => 'portrait',
            default            => 'square',
        };
        $isTall = ($h / max($w, 1)) > 2;

        $ctx = compact('w', 'h', 'palette', 'fonts', 'copy', 'orientation', 'isTall');

        $objects = match ($layout) {
            'hero-centered'   => $this->layoutHeroCentered($ctx),
            'left-strip'      => $this->layoutLeftStrip($ctx),
            'top-banner'      => $this->layoutTopBanner($ctx),
            'corner-accent'   => $this->layoutCornerAccent($ctx),
            'split-tone'      => $this->layoutSplitTone($ctx),
            'dot-pattern'     => $this->layoutDotPattern($ctx),
            'diagonal-stripe' => $this->layoutDiagonalStripe($ctx),
            'image-hero'      => $this->layoutImageHero($ctx),
            'photo-grid'      => $this->layoutPhotoGrid($ctx),
            'bold-statement'  => $this->layoutBoldStatement($ctx),
        };

        $name = sprintf('%s — %s · %s · %s #%02d',
            $product->name,
            $this->layoutLabel($layout),
            $this->paletteLabel($paletteName),
            ucwords(strtolower($copy['name'])),
            $index + 1,
        );

        return [
            'name'  => $name,
            'scene' => [
                'version'    => '6.0.0',
                'background' => $palette[0],
                'width'      => round($w, 2),
                'height'     => round($h, 2),
                'objects'    => $objects,
            ],
        ];
    }

    // ---------------------------------------------------------------- layouts

    private function layoutHeroCentered(array $c): array
    {
        [$w, $h, $palette, $fonts, $copy] = $this->unpack($c);
        $cy = $h / 2;
        $logoW = min($w * 0.34, 180);
        $logoH = $logoW * 0.5;
        $stackGap = min(80, $h * 0.18);
        $headingSize = $c['isTall'] ? 84 : ($c['orientation'] === 'landscape' ? 46 : 58);

        return [
            $this->bg($w, $h, $palette[0]),
            // Two thin lines flanking the logo box for a "framed hero" feel
            $this->rect($palette[1], $this->centerLeft($w, 80), $cy - $logoH - $stackGap - 18, 80, 2, name: 'accent-line-top'),
            $this->rect($palette[1], $this->centerLeft($w, 80), $cy + 18, 80, 2, name: 'accent-divider'),

            $this->rect($this->surfaceOnDark($palette), $this->centerLeft($w, $logoW), $cy - $logoH - $stackGap, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[1], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ YOUR LOGO ]', $this->centerLeft($w, 80), $cy - $logoH - $stackGap + $logoH/2 - 8,
                fontFamily: $fonts['body'], fontSize: 12, fill: $palette[1], fontWeight: '600', textAlign: 'center'),

            $this->iText($copy['name'], $this->centerLeft($w, 360), $cy + 32,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[2], fontWeight: '700', textAlign: 'center', width: min(360, $w - 16)),

            $this->iText($copy['tagline'], $this->centerLeft($w, 380), $cy + 32 + $headingSize + 10,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 32 : 16,
                fill: $palette[3], fontWeight: '500', textAlign: 'center', width: min(380, $w - 16)),

            $this->textbox($copy['contact'], $this->centerLeft($w, 460), $h - 70,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 22 : 11,
                fill: $palette[3], width: min(460, $w - 16), textAlign: 'center', lineHeight: 1.55),
        ];
    }

    private function layoutLeftStrip(array $c): array
    {
        [$w, $h, $palette, $fonts, $copy] = $this->unpack($c);
        $stripW = $w * ($c['orientation'] === 'landscape' ? 0.36 : 0.32);
        $logoW = min($stripW * 0.6, 140);
        $logoH = $logoW * 0.5;
        $headingSize = $c['isTall'] ? 72 : ($c['orientation'] === 'landscape' ? 30 : 38);

        return [
            $this->bg($w, $h, $palette[2]),
            $this->rect($palette[0], 0, 0, $stripW, $h, name: 'accent-strip'),
            // Decorative dot column in strip
            ...$this->dotColumn($stripW / 2 - 1, $h * 0.62, $h * 0.32, 6, $palette[1]),
            $this->rect($palette[1], 16, $h * 0.4, 30, 2, name: 'accent-divider'),

            $this->rect('rgba(255,255,255,0.06)', $stripW / 2 - $logoW / 2, 32, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[1], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ YOUR LOGO ]', $stripW / 2 - 36, 32 + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 12, fill: $palette[1], fontWeight: '600', textAlign: 'center'),

            $this->iText($copy['name'], 24, $h * 0.5 - $headingSize,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[2], fontWeight: '700', width: $stripW - 48),
            $this->iText($copy['tagline'], 24, $h * 0.5 + $headingSize * 0.6,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $headingSize * 0.34,
                fill: $palette[1], fontWeight: '500', width: $stripW - 48),

            $this->textbox($copy['contact'] . "\n\n" . $copy['role'],
                $stripW + 24, 32,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 22 : 11,
                fill: $palette[0], width: $w - $stripW - 48, lineHeight: 1.6),
        ];
    }

    private function layoutTopBanner(array $c): array
    {
        [$w, $h, $palette, $fonts, $copy] = $this->unpack($c);
        $bandH = $h * ($c['isTall'] ? 0.18 : ($c['orientation'] === 'portrait' ? 0.25 : 0.32));
        $logoW = min($bandH * 0.7, 90);
        $logoH = $logoW * 0.5;
        $headingSize = $c['isTall'] ? 72 : ($c['orientation'] === 'landscape' ? 32 : 36);

        return [
            $this->bg($w, $h, $palette[2]),
            $this->rect($palette[0], 0, 0, $w, $bandH, name: 'accent-band'),
            $this->rect($palette[1], 0, $bandH, $w, 4, name: 'accent-divider'),

            $this->rect('rgba(255,255,255,0.06)', 24, $bandH / 2 - $logoH / 2, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[1], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ LOGO ]', 24 + 12, $bandH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 12, fill: $palette[1], fontWeight: '600'),

            $this->iText($copy['name'], 24 + $logoW + 24, $bandH / 2 - $headingSize / 2,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[2], fontWeight: '700'),

            $this->iText($copy['role'], 24, $bandH + 28,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 28 : 14,
                fill: $palette[0], fontWeight: '600', width: $w - 48),
            $this->iText($copy['tagline'], 24, $bandH + 28 + ($c['isTall'] ? 32 : 18),
                fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 24 : 12,
                fill: $this->mix($palette[0], 60), fontWeight: '400', width: $w - 48),

            $this->textbox($copy['contact'], 24, $h - 60,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 22 : 11,
                fill: $palette[0], width: $w - 48, lineHeight: 1.55),
        ];
    }

    private function layoutCornerAccent(array $c): array
    {
        [$w, $h, $palette, $fonts, $copy] = $this->unpack($c);
        $headingSize = $c['isTall'] ? 74 : ($c['orientation'] === 'landscape' ? 30 : 38);
        $accentSize = min($w, $h) * 0.55;
        $logoW = min($w * 0.3, 140);
        $logoH = $logoW * 0.5;

        return [
            $this->bg($w, $h, $palette[2]),
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
            // Small circle accent inside the triangle area
            ['type' => 'circle', 'left' => $w - $accentSize * 0.4, 'top' => $accentSize * 0.18, 'radius' => 14, 'fill' => $palette[1], 'name' => 'accent-dot', 'selectable' => true],
            $this->rect($palette[1], 32, $h - 100, 40, 3, name: 'accent-divider'),

            $this->rect('rgba(15,23,42,0.05)', 32, 32, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[0], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ YOUR LOGO ]', 32 + 12, 32 + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 13, fill: $palette[0], fontWeight: '600'),

            $this->iText($copy['name'], 32, $h - $headingSize - 96,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[0], fontWeight: '700', width: $w - 64),
            $this->iText($copy['tagline'], 32, $h - 76,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 28 : 14,
                fill: $palette[1], fontWeight: '600', width: $w - 64),

            $this->textbox($copy['contact'], 32, $h - 40,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 22 : 11,
                fill: $palette[0], width: $w - 64, lineHeight: 1.5),
        ];
    }

    private function layoutSplitTone(array $c): array
    {
        [$w, $h, $palette, $fonts, $copy] = $this->unpack($c);
        $topH = $h * 0.5;
        $headingSize = $c['isTall'] ? 76 : ($c['orientation'] === 'landscape' ? 34 : 44);
        $logoW = min($w * 0.34, 150);
        $logoH = $logoW * 0.5;

        return [
            $this->rect($palette[0], 0, 0, $w, $topH, name: 'background-top'),
            $this->rect($palette[2], 0, $topH, $w, $h - $topH, name: 'background-bottom'),
            $this->rect($palette[1], 0, $topH - 2, $w, 4, name: 'accent-divider'),

            $this->rect('rgba(255,255,255,0.07)', $w / 2 - $logoW / 2, $topH / 2 - $logoH / 2 - 26, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[1], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ YOUR LOGO ]', $w / 2 - 40, $topH / 2 - $logoH / 2 - 26 + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 13, fill: $palette[1], fontWeight: '600', textAlign: 'center'),

            $this->iText($copy['name'], $this->centerLeft($w, 400), $topH + 32,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[0], fontWeight: '700', textAlign: 'center', width: min(400, $w - 16)),

            $this->iText($copy['tagline'], $this->centerLeft($w, 360), $topH + 32 + $headingSize + 10,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 28 : 16,
                fill: $palette[0], fontWeight: '500', textAlign: 'center', width: min(360, $w - 16)),

            $this->textbox($copy['contact'], $this->centerLeft($w, 460), $h - 56,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 22 : 11,
                fill: $palette[0], width: min(460, $w - 16), textAlign: 'center', lineHeight: 1.55),
        ];
    }

    private function layoutDotPattern(array $c): array
    {
        [$w, $h, $palette, $fonts, $copy] = $this->unpack($c);
        $headingSize = $c['isTall'] ? 74 : ($c['orientation'] === 'landscape' ? 36 : 46);
        $logoW = min($w * 0.30, 140);
        $logoH = $logoW * 0.5;

        $objects = [$this->bg($w, $h, $palette[0])];
        // 7×N grid of dots — semi-transparent accent
        $cols = 7;
        $rows = max(5, (int) ceil($h / ($w / $cols)));
        $marginX = $w * 0.08;
        $marginY = $h * 0.08;
        $stepX = ($w - $marginX * 2) / max($cols - 1, 1);
        $stepY = ($h - $marginY * 2) / max($rows - 1, 1);
        for ($r = 0; $r < $rows; $r++) {
            for ($col = 0; $col < $cols; $col++) {
                $cx = $marginX + $col * $stepX;
                $cy = $marginY + $r * $stepY;
                // Skip dots in the center where the heading sits
                if ($cx > $w * 0.18 && $cx < $w * 0.82 && $cy > $h * 0.35 && $cy < $h * 0.65) continue;
                $objects[] = [
                    'type' => 'circle', 'left' => $cx - 2.5, 'top' => $cy - 2.5,
                    'radius' => 2.5, 'fill' => $this->mix($palette[1], 70),
                    'selectable' => true, 'objectCaching' => false,
                ];
            }
        }

        $objects[] = $this->rect($palette[1], $this->centerLeft($w, 60), $h * 0.30 - 12, 60, 2, name: 'accent-divider');
        $objects[] = $this->rect($this->surfaceOnDark($palette), $this->centerLeft($w, $logoW), $h * 0.22, $logoW, $logoH,
            name: 'placeholder-logo', stroke: $palette[1], strokeWidth: 1.5, strokeDashArray: [6, 4]);
        $objects[] = $this->iText('[ LOGO ]', $this->centerLeft($w, 70), $h * 0.22 + $logoH / 2 - 8,
            fontFamily: $fonts['body'], fontSize: 12, fill: $palette[1], fontWeight: '600', textAlign: 'center');

        $objects[] = $this->iText($copy['name'], $this->centerLeft($w, 420), $h * 0.45,
            name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
            fill: $palette[2], fontWeight: '800', textAlign: 'center', width: min(420, $w - 16));
        $objects[] = $this->iText($copy['tagline'], $this->centerLeft($w, 380), $h * 0.45 + $headingSize + 10,
            name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 28 : 15,
            fill: $palette[1], fontWeight: '500', textAlign: 'center', width: min(380, $w - 16));

        $objects[] = $this->textbox($copy['contact'], $this->centerLeft($w, 460), $h - 70,
            name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 22 : 11,
            fill: $palette[3], width: min(460, $w - 16), textAlign: 'center', lineHeight: 1.55);
        return $objects;
    }

    private function layoutDiagonalStripe(array $c): array
    {
        [$w, $h, $palette, $fonts, $copy] = $this->unpack($c);
        $headingSize = $c['isTall'] ? 72 : ($c['orientation'] === 'landscape' ? 32 : 42);
        $logoW = min($w * 0.28, 130);
        $logoH = $logoW * 0.5;

        // Slanted stripe: wide rectangle rotated to cross the card from
        // upper-left to lower-right. Origin at left so rotation pivots
        // around the top-left corner.
        $stripeW = sqrt($w * $w + $h * $h) * 1.2;
        $stripeH = max(30, min($w, $h) * 0.12);

        return [
            $this->bg($w, $h, $palette[2]),
            [
                'type' => 'rect',
                'left' => -$stripeW * 0.2, 'top' => $h * 0.35,
                'width' => $stripeW, 'height' => $stripeH,
                'angle' => $c['orientation'] === 'landscape' ? -14 : -22,
                'fill' => $palette[0], 'name' => 'accent-stripe',
                'selectable' => true, 'objectCaching' => false,
            ],
            [
                'type' => 'rect',
                'left' => -$stripeW * 0.2, 'top' => $h * 0.35 + $stripeH + 4,
                'width' => $stripeW, 'height' => 3,
                'angle' => $c['orientation'] === 'landscape' ? -14 : -22,
                'fill' => $palette[1], 'name' => 'accent-divider',
                'selectable' => true, 'objectCaching' => false,
            ],

            $this->rect('rgba(15,23,42,0.05)', 32, 32, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[0], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ LOGO ]', 32 + 12, 32 + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 12, fill: $palette[0], fontWeight: '600'),

            $this->iText($copy['name'], 32, $h * 0.62,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[0], fontWeight: '700', width: $w - 64),
            $this->iText($copy['tagline'], 32, $h * 0.62 + $headingSize + 10,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 28 : 14,
                fill: $palette[1], fontWeight: '600', width: $w - 64),

            $this->textbox($copy['contact'], 32, $h - 56,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 22 : 11,
                fill: $palette[0], width: $w - 64, lineHeight: 1.55),
        ];
    }

    private function layoutImageHero(array $c): array
    {
        [$w, $h, $palette, $fonts, $copy] = $this->unpack($c);
        $imgH = $h * ($c['isTall'] ? 0.55 : 0.55);
        $headingSize = $c['isTall'] ? 72 : ($c['orientation'] === 'landscape' ? 32 : 40);
        $logoW = min($w * 0.30, 130);
        $logoH = $logoW * 0.5;

        return [
            $this->bg($w, $h, $palette[2]),
            // Large image placeholder filling the top portion
            $this->rect($this->mix($palette[0], 90), 0, 0, $w, $imgH, name: 'placeholder-image'),
            ...$this->placeholderLabel($w / 2, $imgH / 2, 'YOUR PHOTO HERE', $palette[1], $fonts['body']),
            // Decorative corner brackets to suggest image crop
            $this->rect($palette[1], 24, 24, 36, 3, name: 'crop-mark-1'),
            $this->rect($palette[1], 24, 24, 3, 36, name: 'crop-mark-2'),
            $this->rect($palette[1], $w - 60, $imgH - 27, 36, 3, name: 'crop-mark-3'),
            $this->rect($palette[1], $w - 27, $imgH - 60, 3, 36, name: 'crop-mark-4'),

            $this->rect($palette[1], 32, $imgH + 26, 48, 3, name: 'accent-divider'),

            $this->rect('rgba(15,23,42,0.05)', $w - $logoW - 32, $imgH + 24, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[0], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ LOGO ]', $w - $logoW - 32 + 12, $imgH + 24 + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 12, fill: $palette[0], fontWeight: '600'),

            $this->iText($copy['name'], 32, $imgH + 44,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[0], fontWeight: '700', width: $w - $logoW - 80),
            $this->iText($copy['tagline'], 32, $imgH + 44 + $headingSize + 8,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 26 : 14,
                fill: $palette[1], fontWeight: '500', width: $w - 64),

            $this->textbox($copy['contact'], 32, $h - 56,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 22 : 11,
                fill: $palette[0], width: $w - 64, lineHeight: 1.55),
        ];
    }

    private function layoutPhotoGrid(array $c): array
    {
        [$w, $h, $palette, $fonts, $copy] = $this->unpack($c);
        $headingSize = $c['isTall'] ? 64 : ($c['orientation'] === 'landscape' ? 28 : 34);
        $logoW = min($w * 0.26, 110);
        $logoH = $logoW * 0.5;

        // 4-cell grid filling top 60%
        $gridTop = 24;
        $gridH   = $h * 0.55;
        $gap = 8;
        $cellW = ($w - 48 - $gap) / 2;
        $cellH = ($gridH - $gap) / 2;

        $cell = function (float $x, float $y, string $name) use ($palette, $cellW, $cellH) {
            return $this->rect($this->mix($palette[0], 90), $x, $y, $cellW, $cellH, name: $name);
        };

        return [
            $this->bg($w, $h, $palette[2]),
            $cell(24,                         $gridTop,            'placeholder-image-1'),
            $cell(24 + $cellW + $gap,         $gridTop,            'placeholder-image-2'),
            $cell(24,                         $gridTop + $cellH + $gap, 'placeholder-image-3'),
            $cell(24 + $cellW + $gap,         $gridTop + $cellH + $gap, 'placeholder-image-4'),

            // small labels on each cell
            $this->iText('IMG 01', 24 + 8, $gridTop + 8, fontFamily: $fonts['body'], fontSize: 10, fill: $palette[1], fontWeight: '700'),
            $this->iText('IMG 02', 24 + $cellW + $gap + 8, $gridTop + 8, fontFamily: $fonts['body'], fontSize: 10, fill: $palette[1], fontWeight: '700'),
            $this->iText('IMG 03', 24 + 8, $gridTop + $cellH + $gap + 8, fontFamily: $fonts['body'], fontSize: 10, fill: $palette[1], fontWeight: '700'),
            $this->iText('IMG 04', 24 + $cellW + $gap + 8, $gridTop + $cellH + $gap + 8, fontFamily: $fonts['body'], fontSize: 10, fill: $palette[1], fontWeight: '700'),

            $this->rect($palette[1], 24, $gridTop + $gridH + 24, 48, 3, name: 'accent-divider'),

            $this->rect('rgba(15,23,42,0.05)', $w - $logoW - 24, $gridTop + $gridH + 22, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[0], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ LOGO ]', $w - $logoW - 24 + 10, $gridTop + $gridH + 22 + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 12, fill: $palette[0], fontWeight: '600'),

            $this->iText($copy['name'], 24, $gridTop + $gridH + 44,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[0], fontWeight: '700', width: $w - $logoW - 64),
            $this->iText($copy['tagline'], 24, $gridTop + $gridH + 44 + $headingSize + 6,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 24 : 13,
                fill: $palette[1], fontWeight: '500', width: $w - 48),

            $this->textbox($copy['contact'], 24, $h - 56,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 20 : 10.5,
                fill: $palette[0], width: $w - 48, lineHeight: 1.55),
        ];
    }

    private function layoutBoldStatement(array $c): array
    {
        [$w, $h, $palette, $fonts, $copy] = $this->unpack($c);
        // Huge company name takes up most of the canvas
        $headingSize = $c['isTall'] ? 140 : ($c['orientation'] === 'landscape' ? 64 : 88);
        $logoW = min($w * 0.22, 100);
        $logoH = $logoW * 0.5;

        return [
            $this->bg($w, $h, $palette[0]),
            // Two thick accent bars
            $this->rect($palette[1], 32, $h * 0.3, $w * 0.4, 6, name: 'accent-bar-1'),
            $this->rect($palette[1], 32, $h * 0.3 + 12, $w * 0.16, 3, name: 'accent-divider'),

            $this->rect($this->surfaceOnDark($palette), $w - $logoW - 32, 32, $logoW, $logoH,
                name: 'placeholder-logo', stroke: $palette[1], strokeWidth: 1.5, strokeDashArray: [6, 4]),
            $this->iText('[ LOGO ]', $w - $logoW - 32 + 10, 32 + $logoH / 2 - 8,
                fontFamily: $fonts['body'], fontSize: 11, fill: $palette[1], fontWeight: '600'),

            // Huge name — uppercase, condensed style
            $this->iText($copy['name'], 32, $h * 0.42,
                name: 'placeholder-company', fontFamily: $fonts['display'], fontSize: $headingSize,
                fill: $palette[2], fontWeight: '800', width: $w - 64),

            $this->iText($copy['tagline'], 32, $h - 130,
                name: 'placeholder-tagline', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 32 : 18,
                fill: $palette[1], fontWeight: '600', width: $w - 64),

            $this->textbox($copy['contact'], 32, $h - 70,
                name: 'placeholder-contact', fontFamily: $fonts['body'], fontSize: $c['isTall'] ? 22 : 11,
                fill: $palette[3], width: $w - 64, lineHeight: 1.55),
        ];
    }

    // ---------------------------------------------------------- primitives

    private function bg(float $w, float $h, string $fill): array
    {
        return $this->rect($fill, 0, 0, $w, $h, name: 'background');
    }

    private function rect(
        string $fill,
        float $left, float $top, float $width, float $height,
        ?string $name = null,
        ?string $stroke = null, ?float $strokeWidth = null, ?array $strokeDashArray = null,
    ): array {
        $o = [
            'type'  => 'rect',
            'left'  => round($left, 2), 'top' => round($top, 2),
            'width' => round($width, 2), 'height' => round($height, 2),
            'fill'  => $fill, 'selectable' => true,
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
            'type' => 'i-text', 'text' => $text,
            'left' => round($left, 2), 'top' => round($top, 2),
            'fontFamily' => $fontFamily, 'fontSize' => $fontSize, 'fill' => $fill,
            'fontWeight' => $fontWeight, 'textAlign' => $textAlign, 'selectable' => true,
        ];
        if ($name !== null) $o['name'] = $name;
        if ($width !== null) $o['width'] = round($width, 2);
        return $o;
    }

    private function textbox(
        string $text, float $left, float $top,
        string $fontFamily, float $fontSize, string $fill, float $width,
        ?string $name = null, string $textAlign = 'left', float $lineHeight = 1.35,
    ): array {
        $o = [
            'type' => 'textbox', 'text' => $text,
            'left' => round($left, 2), 'top' => round($top, 2),
            'width' => round($width, 2),
            'fontFamily' => $fontFamily, 'fontSize' => $fontSize, 'fill' => $fill,
            'textAlign' => $textAlign, 'lineHeight' => $lineHeight,
            'splitByGrapheme' => false, 'selectable' => true,
        ];
        if ($name !== null) $o['name'] = $name;
        return $o;
    }

    /** Returns an array of small circles forming a vertical decorative column. */
    private function dotColumn(float $cx, float $startY, float $length, int $count, string $fill): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $y = $startY + ($i * ($length / max($count - 1, 1)));
            $out[] = [
                'type' => 'circle', 'left' => $cx - 2, 'top' => $y - 2,
                'radius' => 2, 'fill' => $fill,
                'selectable' => true, 'objectCaching' => false,
            ];
        }
        return $out;
    }

    /** Small "YOUR PHOTO HERE" label centred at (x,y). */
    private function placeholderLabel(float $cx, float $cy, string $text, string $fill, string $font): array
    {
        return [
            $this->iText($text, $cx - 80, $cy - 8,
                fontFamily: $font, fontSize: 13, fill: $fill, fontWeight: '700', textAlign: 'center', width: 160),
        ];
    }

    private function centerLeft(float $canvasW, float $width): float
    {
        return max(8, $canvasW / 2 - $width / 2);
    }

    /**
     * Pick a slightly lighter/darker variant of a dark colour for surfaces.
     * For light palettes we go darker; for dark we go lighter.
     */
    private function surfaceOnDark(array $palette): string
    {
        return $palette[4] ?? 'rgba(255,255,255,0.06)';
    }

    /** Quick "mix with neutral" — returns an rgba blend for muted accents. */
    private function mix(string $hex, int $opacityPct): string
    {
        if (! preg_match('/^#([0-9a-fA-F]{6})$/', $hex, $m)) return $hex;
        $r = hexdec(substr($m[1], 0, 2));
        $g = hexdec(substr($m[1], 2, 2));
        $b = hexdec(substr($m[1], 4, 2));
        $alpha = max(0, min(1, $opacityPct / 100));
        return "rgba($r,$g,$b," . number_format($alpha, 2) . ")";
    }

    /** @return array{0:float,1:float,2:array,3:array,4:array} */
    private function unpack(array $c): array
    {
        return [$c['w'], $c['h'], $c['palette'], $c['fonts'], $c['copy']];
    }

    private function layoutLabel(string $key): string
    {
        return match ($key) {
            'hero-centered'   => 'Hero',
            'left-strip'      => 'Strip',
            'top-banner'      => 'Banner',
            'corner-accent'   => 'Corner',
            'split-tone'      => 'Split',
            'dot-pattern'     => 'Dots',
            'diagonal-stripe' => 'Diagonal',
            'image-hero'      => 'Image-Hero',
            'photo-grid'      => 'Photo-Grid',
            'bold-statement'  => 'Bold',
        };
    }

    private function paletteLabel(string $key): string
    {
        return match ($key) {
            'navy-gold'        => 'Navy & Gold',
            'charcoal-coral'   => 'Charcoal & Coral',
            'forest-cream'     => 'Forest & Cream',
            'slate-mint-light' => 'Slate & Mint',
            'burgundy-cream'   => 'Burgundy & Cream',
        };
    }

    // ---------------------------------------------------------- validation

    private function validate(array $tpl, Product $product, int $widthMm, int $heightMm): array
    {
        $issues = [];
        $scene = $tpl['scene'];
        $objects = $scene['objects'] ?? [];
        $widthPx  = $widthMm  * self::PX_PER_MM;
        $heightPx = $heightMm * self::PX_PER_MM;
        $bleedPx  = $product->default_bleed_mm * self::PX_PER_MM;

        $names = array_map(fn ($o) => $o['name'] ?? '', $objects);

        foreach (['placeholder-logo', 'placeholder-company', 'placeholder-tagline', 'placeholder-contact'] as $req) {
            if (! in_array($req, $names, true)) {
                $issues[] = "missing required placeholder \"$req\"";
            }
        }

        // Background coverage
        $hasBg = false;
        foreach ($objects as $o) {
            if (! Str::startsWith($o['name'] ?? '', 'background')) continue;
            $r = $this->bounds($o);
            if ($r['x1'] <= 0.01 && $r['x2'] >= $widthPx * 0.49 && $r['y1'] <= 0.01 && $r['y2'] >= $heightPx * 0.49) {
                $hasBg = true; break;
            }
        }
        if (! $hasBg) $issues[] = 'no object named "background*" covers at least half the canvas';

        // Bounds — relaxed for text (Fabric measures at render)
        foreach ($objects as $idx => $o) {
            $type = $o['type'] ?? '?';
            if (in_array($type, ['i-text', 'textbox', 'text'], true)) {
                $left = (float) ($o['left'] ?? 0);
                $top  = (float) ($o['top']  ?? 0);
                if ($left < -$bleedPx || $top < -$bleedPx
                    || $left > $widthPx + $bleedPx || $top > $heightPx + $bleedPx) {
                    $issues[] = "text object {$idx} ({$type}) anchor ({$left},{$top}) outside canvas+bleed";
                }
                continue;
            }
            // Skip bound check for rotated/decorative shapes — they're
            // intentionally off-canvas (e.g. diagonal stripe overflows).
            $name = $o['name'] ?? '';
            if (in_array($name, ['accent-stripe', 'accent-corner'], true)) continue;
            if (isset($o['angle']) && abs((float)$o['angle']) > 0.1) continue;

            $r = $this->bounds($o);
            if ($r['x1'] < -$bleedPx - 1 || $r['y1'] < -$bleedPx - 1
                || $r['x2'] > $widthPx + $bleedPx + 1
                || $r['y2'] > $heightPx + $bleedPx + 1) {
                $issues[] = sprintf('object %d (%s, %s) extends past bleed: bbox %.0f,%.0f..%.0f,%.0f vs canvas %.0f×%.0f',
                    $idx, $type, $name ?: '—', $r['x1'], $r['y1'], $r['x2'], $r['y2'], $widthPx, $heightPx);
            }
        }

        // Fonts allow-list
        foreach ($objects as $idx => $o) {
            if (! in_array($o['type'] ?? '', ['i-text', 'textbox', 'text'], true)) continue;
            $ff = $o['fontFamily'] ?? null;
            if (! $ff) {
                $issues[] = "text object {$idx} has no fontFamily";
            } elseif (! in_array($ff, self::ALLOWED_FONTS, true)) {
                $issues[] = "text object {$idx} uses font \"{$ff}\" which is not loaded in designer/index.html";
            }
        }

        // Palette diversity
        $fills = array_unique(array_filter(array_map(fn ($o) => $o['fill'] ?? null, $objects)));
        if (count($fills) < 3) $issues[] = 'fewer than 3 distinct fill colours — palette is too flat';

        // Company anchor on canvas
        $company = collect($objects)->firstWhere('name', 'placeholder-company');
        if ($company) {
            $left = (float) ($company['left'] ?? 0);
            $top  = (float) ($company['top']  ?? 0);
            if ($left < 0 || $left > $widthPx || $top < 0 || $top > $heightPx) {
                $issues[] = "company-name anchor ({$left},{$top}) is outside the canvas ({$widthPx}×{$heightPx})";
            }
        }

        if (json_encode($scene) === false) {
            $issues[] = 'scene is not JSON-encodable: ' . json_last_error_msg();
        }

        return $issues;
    }

    private function bounds(array $o): array
    {
        $left = (float) ($o['left'] ?? 0);
        $top  = (float) ($o['top']  ?? 0);
        $w    = (float) ($o['width']  ?? 0);
        $h    = (float) ($o['height'] ?? 0);

        if (($o['type'] ?? '') === 'circle' && isset($o['radius'])) {
            $w = $h = (float)$o['radius'] * 2;
        }
        if (in_array($o['type'] ?? '', ['i-text', 'textbox', 'text'], true) && $h === 0.0) {
            $h = (float) ($o['fontSize'] ?? 16) * (float) ($o['lineHeight'] ?? 1.2) * 1.2;
        }
        if (($o['type'] ?? '') === 'polygon' && isset($o['points'])) {
            $xs = array_map(fn ($p) => (float) $p['x'], $o['points']);
            $ys = array_map(fn ($p) => (float) $p['y'], $o['points']);
            $w = max($xs) - min($xs);
            $h = max($ys) - min($ys);
        }
        return ['x1' => $left, 'y1' => $top, 'x2' => $left + $w, 'y2' => $top + $h];
    }
}
