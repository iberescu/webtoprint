#!/usr/bin/env node
/**
 * Generates the storefront's logo, hero banner, and per-product photography
 * via the Gemini image API (Nano Banana 2 — gemini-3-pro-image-preview).
 *
 * Usage:
 *   GEMINI_API_KEY=... node scripts/generate-images.mjs
 *
 * Output goes to storefront/public/images/.
 */
import { writeFile, mkdir, access } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';

const API_KEY = process.env.GEMINI_API_KEY;
if (!API_KEY) {
  console.error('Set GEMINI_API_KEY env var.');
  process.exit(1);
}

const MODEL = process.env.GEMINI_MODEL || 'gemini-2.5-flash-image-preview';
const OUT_DIR = resolve(process.cwd(), 'storefront/public/images');

const SYSTEM_STYLE = 'Vistaprint-style professional product photography, ultra clean, soft shadows, neutral white-grey background, subtle gradient, studio lighting, square 1:1 aspect ratio, high resolution.';

const JOBS = [
  { file: 'logo.png', prompt: `Minimalist flat logo for an online print shop called "PrintHub". Stylized indigo-purple letter "P" inside a rounded square tile, set against pure white background. Crisp vector look, no text. Square.` },
  { file: 'banners/hero.png', prompt: `Wide promotional banner image: top-down hero photograph of premium printed materials laid out artistically — business cards, folded brochures, a poster, postcards and greeting cards in coordinated indigo and amber palette. Clean white surface, natural soft window light, slight 3D depth. No text overlays. Cinematic, magazine-quality.` },
  { file: 'banners/category-cards.png', prompt: `${SYSTEM_STYLE} A neat fan of premium business cards and folded greeting cards on a soft cream surface. No text on the cards. Slate-blue colour palette.` },
  { file: 'banners/category-marketing.png', prompt: `${SYSTEM_STYLE} A stack of glossy promotional flyers and a tri-fold brochure on a soft warm-white surface. Amber-orange palette. No text on the materials.` },
  { file: 'banners/category-stationery.png', prompt: `${SYSTEM_STYLE} A composition of branded envelopes, a notepad and a sheet of letterhead on a marble desk. Neutral grey palette. No text.` },
  { file: 'banners/category-signage.png', prompt: `${SYSTEM_STYLE} A free-standing roll-up banner and a rolled large-format poster against a soft cyan gradient background. No text.` },

  // Products
  { file: 'products/flyer.png', prompt: `${SYSTEM_STYLE} A neat fan-stack of A4 promotional flyers in vibrant amber and orange tones. Glossy print finish, premium silk paper. No text on the flyers.` },
  { file: 'products/business-card.png', prompt: `${SYSTEM_STYLE} A clean stack of premium white business cards (85x55mm) with a single subtle embossed silver mark, top-down hero shot. No legible text.` },
  { file: 'products/premium-business-card.png', prompt: `${SYSTEM_STYLE} A luxury business card with gold foil stamping and spot UV on heavy 450gsm cotton paper, dramatic side lighting, dark velvet surface. No text.` },
  { file: 'products/postcard.png', prompt: `${SYSTEM_STYLE} A short stack of vibrant photo postcards in sky-blue tones, slightly fanned, with small stamp and address area visible. No legible text.` },
  { file: 'products/greeting-card.png', prompt: `${SYSTEM_STYLE} An elegant folded greeting card on a kraft envelope, pink pastel palette, with a small dried flower next to it. No text on the card.` },
  { file: 'products/brochure.png', prompt: `${SYSTEM_STYLE} A tri-fold A4 brochure standing semi-open, rich rose colour scheme, glossy paper, three panels visible. No legible text.` },
  { file: 'products/booklet.png', prompt: `${SYSTEM_STYLE} A saddle-stitched A5 booklet with a rich purple cover, lying open showing internal pages with a colour spread. No legible text.` },
  { file: 'products/poster.png', prompt: `${SYSTEM_STYLE} A large-format A2 poster rolled at one end, leaning gently against a soft cyan wall. No legible text on the poster.` },
  { file: 'products/rollup-banner.png', prompt: `${SYSTEM_STYLE} A free-standing roll-up banner with indigo and white graphics, fully extended, on a clean studio backdrop. No legible text.` },
  { file: 'products/vinyl-sticker.png', prompt: `${SYSTEM_STYLE} A scatter of colourful die-cut vinyl stickers in various shapes (square, circle, custom shape) on a soft emerald-green surface. No legible text on the stickers.` },
  { file: 'products/letterhead.png', prompt: `${SYSTEM_STYLE} A neat stack of A4 letterheads with a subtle zinc-grey corporate header design, top-down hero shot. No legible text body.` },
  { file: 'products/envelope.png', prompt: `${SYSTEM_STYLE} A neat stack of branded DL envelopes in warm stone-beige with a kraft paper accent, top-down view. No legible text.` },
  { file: 'products/notepad.png', prompt: `${SYSTEM_STYLE} An A5 glued-top notepad with a yellow branded cover next to a black fountain pen on a wooden desk surface. No legible text.` },
  { file: 'products/presentation-folder.png', prompt: `${SYSTEM_STYLE} An A4 teal presentation folder slightly open, showing a printed brochure inside. Top-down hero shot. No legible text.` },
];

async function exists(path) {
  try { await access(path); return true; } catch { return false; }
}

async function generate({ file, prompt }) {
  const out = resolve(OUT_DIR, file);
  if (await exists(out)) {
    console.log(`✓ ${file} (cached)`);
    return;
  }
  await mkdir(dirname(out), { recursive: true });

  const url = `https://generativelanguage.googleapis.com/v1beta/models/${MODEL}:generateContent?key=${API_KEY}`;
  const body = {
    contents: [{ parts: [{ text: prompt }] }],
    generationConfig: { responseModalities: ['IMAGE', 'TEXT'] },
  };

  const t0 = Date.now();
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const dur = ((Date.now() - t0) / 1000).toFixed(1);

  if (!res.ok) {
    const err = await res.text();
    console.error(`✗ ${file}: HTTP ${res.status} after ${dur}s`);
    console.error(`  ${err.slice(0, 400)}`);
    return;
  }

  const json = await res.json();
  const parts = json?.candidates?.[0]?.content?.parts ?? [];
  const inline = parts.find((p) => p.inlineData)?.inlineData;
  if (!inline?.data) {
    console.error(`✗ ${file}: no image data after ${dur}s`);
    console.error(`  ${JSON.stringify(json).slice(0, 400)}`);
    return;
  }

  await writeFile(out, Buffer.from(inline.data, 'base64'));
  console.log(`✓ ${file} (${dur}s, ${(Buffer.byteLength(inline.data, 'base64') / 1024).toFixed(0)} KiB)`);
}

console.log(`Generating ${JOBS.length} images via ${MODEL}...`);
for (const job of JOBS) {
  try { await generate(job); }
  catch (e) { console.error(`✗ ${job.file}: ${e.message}`); }
}
console.log('Done.');
