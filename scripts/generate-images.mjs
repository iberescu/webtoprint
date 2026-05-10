#!/usr/bin/env node
/**
 * Generates the storefront's logo, hero banner, and per-product photography
 * via the Gemini image API (Nano Banana 2 — gemini-3-pro-image-preview).
 *
 * Per-product photos can include a CloudLab brand reference image; Gemini
 * uses it to render the logo correctly on the printed material.
 *
 * Usage:
 *   GEMINI_API_KEY=… node scripts/generate-images.mjs
 *   FORCE=1 GEMINI_API_KEY=… node scripts/generate-images.mjs   # overwrite cached
 *   ONLY=products/flyer.png GEMINI_API_KEY=… node scripts/generate-images.mjs
 */
import { writeFile, readFile, mkdir, access } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import sharp from 'sharp';

const API_KEY = process.env.GEMINI_API_KEY;
if (!API_KEY) { console.error('Set GEMINI_API_KEY env var.'); process.exit(1); }

const MODEL = process.env.GEMINI_MODEL || 'gemini-2.5-flash-image-preview';
const OUT_DIR = resolve(process.cwd(), 'storefront/public/images');
const FORCE = !!process.env.FORCE;
const ONLY = process.env.ONLY?.split(',') ?? null;

// ---- CloudLab brand context -----------------------------------------------

const BRAND = {
  name:    'CloudLab',
  navy:    '#2D5096',
  website: 'www.cloudlab-solutions.com',
  email:   'info@cloudlab-solutions.com',
  address: 'Germany, Dortmund',
};

// Style baseline applied on top of every prompt.
const STYLE = `Vistaprint-style professional product photography, ultra clean, soft shadows, neutral white-grey background, subtle gradient, studio lighting, square 1:1 aspect ratio, hyper-realistic, magazine-quality.`;

// Branding instruction — appended to product prompts so Gemini renders the
// CloudLab wordmark + selected contact details ON the printed item itself.
function brandingForProduct(opts) {
  const { lines = [BRAND.website], logoStyle = 'small CloudLab logo' } = opts;
  return `
CRITICAL — LOGO RULE:
USE THE EXACT LOGO from the supplied reference image. MAKE NO CHANGES TO IT.
No cloud icons, no symbols, no extra glyphs, no different fonts, no different
weights, no different colour, no recoloring, no kerning adjustments, no
recreating from imagination. Pixel-paste it like a sticker. Only scale it and
position it. If you cannot render the logo cleanly at the chosen scale, leave
a small empty navy rectangle as a placeholder rather than inventing one.

Logo placement / scale on the product: ${logoStyle}.

In addition to the logo, render these short text lines clearly and legibly
in a clean Inter-style sans-serif, in navy (${BRAND.navy}) or neutral grey:
${lines.map((l) => `  - "${l}"`).join('\n')}
The text MUST be spelled exactly as written above (case-sensitive, dots, dashes).
Do NOT add taglines, slogans or extra words. If unsure of spelling, omit the
text rather than approximate it.`;
}

// ---- Job definitions ------------------------------------------------------

const JOBS = [
  // Logo + chrome (no logo reference yet — these define it)
  { file: 'logo.png', prompt: `Minimalist flat logo for an online print shop called "PrintHub". Stylized indigo-purple letter "P" inside a rounded square tile, set against pure white background. Crisp vector look, no text. Square.` },

  // Banners — atmospheric. Hero now carries the CloudLab brand subtly.
  {
    file: 'banners/hero.png',
    useLogo: true,
    prompt: `Editorial lifestyle photograph: a graphic designer's hands (only hands visible, no face) at a clean white wooden desk arranging crisp branded business cards and a folded brochure, with a rolled poster nearby. A small green plant in the corner. Soft warm window light from the left, shallow depth of field. Subtle indigo accents.\n\nThe printed items (business cards on top of the stack, the brochure cover) MUST carry the supplied CloudLab logo — USE THE EXACT LOGO from the reference image, MAKE NO CHANGES, no cloud icons, no extra glyphs, just the wordmark, pixel-pasted at a small scale. Do NOT render any other text or contact details on the materials. Square 1:1 aspect ratio. Hyper-realistic, magazine-quality.`,
  },
  { file: 'banners/category-cards.png',      prompt: `${STYLE} A neat fan of premium business cards and folded greeting cards on a soft cream surface. No text on the cards. Slate-blue colour palette.` },
  { file: 'banners/category-marketing.png',  prompt: `${STYLE} A stack of glossy promotional flyers and a tri-fold brochure on a soft warm-white surface. Amber-orange palette. No text on the materials.` },
  { file: 'banners/category-stationery.png', prompt: `${STYLE} A composition of branded envelopes, a notepad and a sheet of letterhead on a marble desk. Neutral grey palette. No text.` },
  { file: 'banners/category-signage.png',    prompt: `${STYLE} A free-standing roll-up banner and a rolled large-format poster against a soft cyan gradient background. No text.` },

  // Products — every one carries the CloudLab brand
  {
    file: 'products/flyer.png',
    useLogo: true,
    prompt: `${STYLE} A neat fan-stack of A4 promotional flyers on a soft white desk. Clean modern flyer design with a navy header band, a single bold headline area, and white space below. Glossy print finish, premium silk paper.\n${brandingForProduct({ lines: [BRAND.website, BRAND.address], logoStyle: 'large navy wordmark on the flyer header band' })}`,
  },
  {
    file: 'products/business-card.png',
    useLogo: true,
    prompt: `${STYLE} A clean stack of premium 85x55mm business cards (white face up) with one card lifted slightly. Top-down hero shot. Minimal, professional layout: logo top-left, contact details bottom-right.\n${brandingForProduct({ lines: ['Maria Schmidt — Account Manager', BRAND.email, BRAND.website], logoStyle: 'navy wordmark in the top-left of the card' })}`,
  },
  {
    file: 'products/premium-business-card.png',
    useLogo: true,
    prompt: `${STYLE} A luxury business card with gold-foil-stamped logo on heavy 450gsm cotton paper, dramatic side lighting, dark velvet surface. Elegant minimal layout — large foil logo centred on front, small contact info below in subtle cream foil.\n${brandingForProduct({ lines: [BRAND.website], logoStyle: 'metallic gold foil-stamped CLOUDLAB wordmark' })}`,
  },
  {
    file: 'products/postcard.png',
    useLogo: true,
    prompt: `${STYLE} A short stack of vibrant photo postcards in sky-blue tones, slightly fanned. Front-facing card has a clean photo area on the left half and brand details on the right half.\n${brandingForProduct({ lines: [BRAND.website, BRAND.email], logoStyle: 'navy CLOUDLAB wordmark on the right half of the postcard' })}`,
  },
  {
    file: 'products/greeting-card.png',
    useLogo: true,
    prompt: `${STYLE} An elegant folded greeting card on a kraft envelope, pink-pastel palette, with a small dried flower next to it. The card is slightly open showing inside content. Subtle navy logo embossed on the back-cover bottom-right.\n${brandingForProduct({ lines: [BRAND.website], logoStyle: 'small subtle navy CLOUDLAB wordmark embossed on the back of the card' })}`,
  },
  {
    file: 'products/brochure.png',
    useLogo: true,
    prompt: `${STYLE} A tri-fold A4 brochure standing semi-open in rich rose colour scheme, glossy paper, three panels visible. Front panel has a clean header with logo, sub-header with website, and a small block of contact info.\n${brandingForProduct({ lines: [BRAND.website, BRAND.email], logoStyle: 'large navy CLOUDLAB wordmark across the brochure cover' })}`,
  },
  {
    file: 'products/booklet.png',
    useLogo: true,
    prompt: `${STYLE} A saddle-stitched A5 booklet with a rich purple cover, lying open showing internal pages with a colour spread. Front cover prominently displays the logo.\n${brandingForProduct({ lines: [BRAND.website], logoStyle: 'large white CLOUDLAB wordmark on the purple cover' })}`,
  },
  {
    file: 'products/poster.png',
    useLogo: true,
    prompt: `${STYLE} A large-format A2 poster rolled at one end, leaning gently against a soft cyan wall. The poster's top half has an abstract design; bottom half is clean white with the CloudLab brand block.\n${brandingForProduct({ lines: [BRAND.website, BRAND.address], logoStyle: 'large bold navy CLOUDLAB wordmark on the lower portion of the poster' })}`,
  },
  {
    file: 'products/rollup-banner.png',
    useLogo: true,
    prompt: `${STYLE} A free-standing roll-up banner with indigo and white graphics, fully extended on a clean studio backdrop. The banner has a clear hierarchy: large logo at the top, large headline area in the middle, contact info at the bottom.\n${brandingForProduct({ lines: [BRAND.website, BRAND.email, BRAND.address], logoStyle: 'huge navy CLOUDLAB wordmark at the top of the banner' })}`,
  },
  {
    file: 'products/vinyl-sticker.png',
    useLogo: true,
    prompt: `${STYLE} A scatter of die-cut vinyl stickers in various shapes (square, circle, custom shape) on a soft emerald-green surface. Each sticker shows the CloudLab logo with a clean modern design — different colour-ways (white-on-navy, navy-on-white, gold-on-black).\n${brandingForProduct({ lines: [], logoStyle: 'CLOUDLAB wordmark as the main sticker design, no other text' })}`,
  },
  {
    file: 'products/letterhead.png',
    useLogo: true,
    prompt: `${STYLE} A neat stack of A4 letterheads, top-down hero shot. Each sheet has a clean header with the logo and a footer with the website, email and address. Generous white space in between.\n${brandingForProduct({ lines: [BRAND.website, BRAND.email, BRAND.address], logoStyle: 'navy CLOUDLAB wordmark in the header (top-left)' })}`,
  },
  {
    file: 'products/envelope.png',
    useLogo: true,
    prompt: `${STYLE} A neat stack of branded white DL envelopes with a kraft accent, top-down view. Each envelope shows the logo and return-address block in the upper-left corner.\n${brandingForProduct({ lines: [BRAND.address, BRAND.website], logoStyle: 'small navy CLOUDLAB wordmark in the upper-left return-address block' })}`,
  },
  {
    file: 'products/notepad.png',
    useLogo: true,
    prompt: `${STYLE} An A5 glued-top notepad with a yellow branded cover next to a black fountain pen on a wooden desk surface. Cover has a clean modern design: logo prominently at the top, small contact line at the very bottom.\n${brandingForProduct({ lines: [BRAND.website], logoStyle: 'large navy CLOUDLAB wordmark across the top of the notepad cover' })}`,
  },
  {
    file: 'products/presentation-folder.png',
    useLogo: true,
    prompt: `${STYLE} An A4 teal presentation folder slightly open, showing a printed brochure inside. Top-down hero shot. The folder cover has a clean modern design with logo top-centre and contact block bottom-centre.\n${brandingForProduct({ lines: [BRAND.website, BRAND.address], logoStyle: 'large white CLOUDLAB wordmark centred at the top of the folder cover' })}`,
  },
];

// ---- Engine ---------------------------------------------------------------

async function fileExists(p) {
  try { await access(p); return true; } catch { return false; }
}

async function rasteriseLogo() {
  const svgPath = resolve(process.cwd(), 'storefront/public/images/logo.svg');
  const pngPath = resolve(process.cwd(), 'storefront/public/images/logo.png');
  if (!FORCE && await fileExists(pngPath)) return pngPath;
  console.log('Rasterising logo SVG → PNG …');
  await sharp(svgPath, { density: 300 }).resize(640).png().toFile(pngPath);
  return pngPath;
}

async function generate(job, logoPng) {
  const out = resolve(OUT_DIR, job.file);
  if (ONLY && !ONLY.some((p) => job.file.endsWith(p))) return;
  if (!FORCE && await fileExists(out)) {
    console.log(`✓ ${job.file} (cached)`);
    return;
  }
  await mkdir(dirname(out), { recursive: true });

  const parts = [{ text: job.prompt }];
  if (job.useLogo && logoPng) {
    const logoBytes = await readFile(logoPng);
    parts.push({
      inlineData: { mimeType: 'image/png', data: logoBytes.toString('base64') },
    });
  }

  const url = `https://generativelanguage.googleapis.com/v1beta/models/${MODEL}:generateContent?key=${API_KEY}`;
  const body = {
    contents: [{ parts }],
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
    console.error(`✗ ${job.file}: HTTP ${res.status} after ${dur}s`);
    console.error(`  ${err.slice(0, 500)}`);
    return;
  }

  const json = await res.json();
  const outParts = json?.candidates?.[0]?.content?.parts ?? [];
  const inline = outParts.find((p) => p.inlineData)?.inlineData;
  if (!inline?.data) {
    console.error(`✗ ${job.file}: no image data after ${dur}s`);
    console.error(`  ${JSON.stringify(json).slice(0, 500)}`);
    return;
  }

  await writeFile(out, Buffer.from(inline.data, 'base64'));
  console.log(`✓ ${job.file} (${dur}s, ${(Buffer.byteLength(inline.data, 'base64') / 1024).toFixed(0)} KiB)`);
}

const logoPng = await rasteriseLogo();
console.log(`Generating ${JOBS.length} images via ${MODEL} (${FORCE ? 'force' : 'cached'} mode)`);
for (const job of JOBS) {
  try { await generate(job, logoPng); }
  catch (e) { console.error(`✗ ${job.file}: ${e.message}`); }
}
console.log('Done.');
