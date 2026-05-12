import { Canvas } from 'fabric';
import { PDFDocument } from 'pdf-lib';
import { DesignerClient } from './api';
import { drawBleedAndSafe } from './overlays';
import { renderPanel, renderProperties, setProductIdForTemplates, type PanelKind } from './panels';

// ---------------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------------

const params = new URLSearchParams(window.location.search);
const designIdFromUrl = params.get('design');
// `?product=` accepts either a UUID (legacy) or a slug like "flyer". We
// resolve to both forms so save can use the UUID and the redirect can use
// the slug.
const productFromUrl = params.get('product') ?? '';

/**
 * Resolve where the designer should talk to the backend.
 *
 * When this page is served behind the aggregator nginx (path begins with
 * `/designer/`) the same host already proxies `/api/v1/*` to the print
 * backend — using same-origin URLs avoids CORS entirely and makes the
 * designer work identically on localhost, the ngrok preview, or any
 * other deploy.
 *
 * Only when the designer is loaded standalone (e.g. opened directly on
 * the Vite dev server at :5173 with no aggregator in front) do we fall
 * back to VITE_API_URL or the hardcoded localhost backend.
 */
function resolveApiBase(): string {
  if (typeof window !== 'undefined' && window.location.pathname.startsWith('/designer/')) {
    return `${window.location.origin}/api/v1`;
  }
  return (import.meta as any).env?.VITE_API_URL ?? 'http://localhost:8000/api/v1';
}
function resolveStorefrontUrl(): string {
  if (typeof window !== 'undefined' && window.location.pathname.startsWith('/designer/')) {
    return window.location.origin;
  }
  return (import.meta as any).env?.VITE_STOREFRONT_URL ?? 'http://localhost:4321';
}

const apiBase = resolveApiBase();
const storefrontUrl = resolveStorefrontUrl();
const client = new DesignerClient(apiBase);

const UUID_RE = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
let productId = UUID_RE.test(productFromUrl) ? productFromUrl : '';
let productSlug = UUID_RE.test(productFromUrl) ? '' : productFromUrl;

const toastEl = document.getElementById('toast') as HTMLElement;
function toast(msg: string, kind: 'info' | 'success' | 'error' = 'info') {
  toastEl.textContent = msg;
  toastEl.className = `show ${kind}`;
  clearTimeout((toast as any)._t);
  (toast as any)._t = setTimeout(() => toastEl.classList.remove('show'), 2400);
}

// Page geometry — defaults to BC; in production this would come from product config.
const CFG = { trim_w: 600, trim_h: 400, bleed: 18, safe: 30 };
const canvasEl = document.getElementById('canvas') as HTMLCanvasElement;
canvasEl.width = CFG.trim_w;
canvasEl.height = CFG.trim_h;

const canvas = new Canvas(canvasEl, { backgroundColor: '#ffffff', preserveObjectStacking: true });
drawBleedAndSafe(canvas, CFG);

// ---------------------------------------------------------------------------
// History (undo/redo) — wraps Fabric's lifecycle events
// ---------------------------------------------------------------------------

const history: string[] = [];
let historyIdx = -1;
let isRestoring = false;

function snapshot() {
  if (isRestoring) return;
  const json = JSON.stringify(canvas.toJSON());
  if (historyIdx < history.length - 1) history.length = historyIdx + 1;
  history.push(json);
  historyIdx = history.length - 1;
  if (history.length > 60) history.shift();
}
async function restore(json: string) {
  isRestoring = true;
  await canvas.loadFromJSON(json);
  drawBleedAndSafe(canvas, CFG);
  canvas.renderAll();
  isRestoring = false;
}
canvas.on('object:added', () => { snapshot(); scheduleAutosave(); });
canvas.on('object:modified', () => { snapshot(); scheduleAutosave(); });
canvas.on('object:removed', () => { snapshot(); scheduleAutosave(); });
snapshot();

document.getElementById('undo')!.addEventListener('click', () => {
  if (historyIdx <= 0) return;
  historyIdx--; restore(history[historyIdx]); scheduleAutosave();
});
document.getElementById('redo')!.addEventListener('click', () => {
  if (historyIdx >= history.length - 1) return;
  historyIdx++; restore(history[historyIdx]); scheduleAutosave();
});

window.addEventListener('keydown', (e) => {
  const ctrl = e.ctrlKey || e.metaKey;
  if (ctrl && e.key === 'z') { e.preventDefault(); document.getElementById('undo')!.click(); }
  if (ctrl && (e.key === 'y' || (e.shiftKey && e.key === 'Z'))) { e.preventDefault(); document.getElementById('redo')!.click(); }
  if (e.key === 'Delete' || e.key === 'Backspace') {
    const obj = canvas.getActiveObject();
    if (obj && !(obj as any).isEditing) {
      const target = e.target as HTMLElement;
      if (target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        canvas.remove(obj); canvas.discardActiveObject(); canvas.renderAll();
      }
    }
  }
});

// ---------------------------------------------------------------------------
// Tool sheets (slide up from the bottom action bar)
// ---------------------------------------------------------------------------

const toolPanelEl   = document.getElementById('tool-panel') as HTMLElement;
const toolButtons   = document.querySelectorAll<HTMLButtonElement>('.tool-btn[data-panel]');
const sheetEl       = document.getElementById('tool-sheet') as HTMLElement;
const sheetBackdrop = document.getElementById('sheet-backdrop') as HTMLElement;
const sheetCloseBtn = document.getElementById('sheet-close')!;

let activePanel: PanelKind | null = null;
function openSheet(kind: PanelKind) {
  toolButtons.forEach((b) => b.classList.toggle('active', b.dataset.panel === kind));
  renderPanel(toolPanelEl, kind, canvas);
  sheetEl.classList.add('show');
  sheetBackdrop.classList.add('show');
  activePanel = kind;
}
function closeSheet() {
  sheetEl.classList.remove('show');
  sheetBackdrop.classList.remove('show');
  toolButtons.forEach((b) => b.classList.remove('active'));
  activePanel = null;
}
toolButtons.forEach((b) => b.addEventListener('click', () => {
  const kind = b.dataset.panel as PanelKind;
  if (activePanel === kind) closeSheet();
  else openSheet(kind);
}));
sheetCloseBtn.addEventListener('click', closeSheet);
sheetBackdrop.addEventListener('click', closeSheet);
window.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && activePanel) { closeSheet(); }
});

// ---------------------------------------------------------------------------
// Contextual format toolbar (visible only when text is selected)
// ---------------------------------------------------------------------------

const ALLOWED_FONTS = [
  'Inter', 'Playfair Display', 'Roboto Slab', 'Bebas Neue', 'DM Serif Display',
  'Cormorant Garamond', 'Manrope', 'Source Sans 3', 'Lato', 'Karla',
  'Poppins', 'Raleway', 'Montserrat',
];
const formatBar  = document.getElementById('format-toolbar') as HTMLElement;
const fmtFont    = document.getElementById('fmt-font')    as HTMLSelectElement;
const fmtSize    = document.getElementById('fmt-size')    as HTMLInputElement;
const fmtColor   = document.getElementById('fmt-color')   as HTMLInputElement;
const fmtBold    = document.getElementById('fmt-bold')    as HTMLButtonElement;
const fmtItalic  = document.getElementById('fmt-italic')  as HTMLButtonElement;
const fmtAlignL  = document.getElementById('fmt-align-left')   as HTMLButtonElement;
const fmtAlignC  = document.getElementById('fmt-align-center') as HTMLButtonElement;
const fmtAlignR  = document.getElementById('fmt-align-right')  as HTMLButtonElement;
const fmtDelete  = document.getElementById('fmt-delete')  as HTMLButtonElement;
const fmtMeta    = document.getElementById('fmt-meta')    as HTMLSpanElement;

// Populate font dropdown once.
fmtFont.innerHTML = ALLOWED_FONTS
  .map((f) => `<option value="${f}" style="font-family: '${f}', sans-serif">${f}</option>`)
  .join('');

function isTextObject(o: any): boolean {
  return o && (o.type === 'i-text' || o.type === 'textbox' || o.type === 'text');
}

function syncFormatToolbar() {
  const active: any = canvas.getActiveObject();
  if (!active || !isTextObject(active)) {
    formatBar.classList.remove('show');
    return;
  }
  formatBar.classList.add('show');
  fmtFont.value   = active.fontFamily ?? 'Inter';
  fmtSize.value   = String(active.fontSize ?? 16);
  fmtColor.value  = colorToHex(active.fill);
  fmtBold.classList.toggle('active',   String(active.fontWeight ?? '').toLowerCase() === 'bold' || Number(active.fontWeight) >= 600);
  fmtItalic.classList.toggle('active', (active.fontStyle ?? 'normal') === 'italic');
  fmtAlignL.classList.toggle('active', (active.textAlign ?? 'left') === 'left');
  fmtAlignC.classList.toggle('active', active.textAlign === 'center');
  fmtAlignR.classList.toggle('active', active.textAlign === 'right');
  fmtMeta.textContent = `${active.type === 'textbox' ? 'textbox' : 'text'} · ${(active.text ?? '').length} chars`;
}

function colorToHex(c: any): string {
  if (typeof c !== 'string') return '#0f1a30';
  if (c.startsWith('#')) return c.length === 4
    ? '#' + c.slice(1).split('').map((x) => x + x).join('')
    : c.slice(0, 7);
  // basic rgb(...) parsing — Fabric usually stores hex but legacy templates use rgb
  const m = c.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
  if (m) {
    const h = (n: number) => n.toString(16).padStart(2, '0');
    return '#' + h(+m[1]) + h(+m[2]) + h(+m[3]);
  }
  return '#0f1a30';
}

function withActiveText(fn: (t: any) => void) {
  const t: any = canvas.getActiveObject();
  if (!isTextObject(t)) return;
  fn(t);
  t.set('dirty', true);
  canvas.requestRenderAll();
  syncFormatToolbar();
  scheduleAutosave();
}

fmtFont  .addEventListener('change', () => withActiveText((t) => t.set('fontFamily', fmtFont.value)));
fmtSize  .addEventListener('input',  () => withActiveText((t) => t.set('fontSize', Math.max(6, Math.min(240, +fmtSize.value || 16)))));
fmtColor .addEventListener('input',  () => withActiveText((t) => t.set('fill', fmtColor.value)));
fmtBold  .addEventListener('click',  () => withActiveText((t) => t.set('fontWeight', fmtBold.classList.contains('active') ? 'normal' : 'bold')));
fmtItalic.addEventListener('click',  () => withActiveText((t) => t.set('fontStyle',  fmtItalic.classList.contains('active') ? 'normal' : 'italic')));
fmtAlignL.addEventListener('click',  () => withActiveText((t) => t.set('textAlign', 'left')));
fmtAlignC.addEventListener('click',  () => withActiveText((t) => t.set('textAlign', 'center')));
fmtAlignR.addEventListener('click',  () => withActiveText((t) => t.set('textAlign', 'right')));
fmtDelete.addEventListener('click',  () => {
  const t = canvas.getActiveObject();
  if (!t) return;
  canvas.remove(t);
  canvas.discardActiveObject();
  canvas.requestRenderAll();
  scheduleAutosave();
});

// ---------------------------------------------------------------------------
// Floating layer-props panel — shown only for non-text selections
// ---------------------------------------------------------------------------

const layerPropsEl = document.getElementById('layer-props') as HTMLElement;
const layerBodyEl  = document.getElementById('layer-props-body') as HTMLElement;
const refreshProps = () => {
  const active: any = canvas.getActiveObject();
  if (!active) {
    layerPropsEl.classList.remove('show');
    syncFormatToolbar();
    return;
  }
  if (isTextObject(active)) {
    // Text → format toolbar handles it, hide the floating panel.
    layerPropsEl.classList.remove('show');
  } else {
    layerPropsEl.classList.add('show');
    renderProperties(layerBodyEl, canvas);
  }
  syncFormatToolbar();
};
canvas.on('selection:created', refreshProps);
canvas.on('selection:updated', refreshProps);
canvas.on('selection:cleared', refreshProps);
canvas.on('object:modified',   refreshProps);
canvas.on('text:changed',      syncFormatToolbar);

// ---------------------------------------------------------------------------
// Save / autosave
//
// We *always* operate on a server-side design id. If the URL didn't carry one,
// the first edit creates a draft transparently. Saving is debounced; the UI
// shows "Saving…" / "All changes saved" / "Unsaved changes" via the indicator.
// ---------------------------------------------------------------------------

let currentDesignId: string | null = designIdFromUrl;
const saveIndicator = document.getElementById('autosave-indicator')!;
let autosaveTimer: number | null = null;

async function persistOnce(): Promise<string | null> {
  saveIndicator.textContent = 'Saving…';
  const json = canvas.toJSON();
  try {
    if (!currentDesignId) {
      // ensureProductId is async (it may have to fetch the catalogue if the
      // page was opened without ?product=…); awaiting here means save never
      // races the boot fetch and "could not save" can't fire spuriously.
      const pid = await ensureProductId();
      const created = await client.createDesign(pid, json);
      currentDesignId = created.id;
      saveIndicator.textContent = `All changes saved · #${currentDesignId.slice(0, 8)}`;
    } else {
      await client.updateDesign(currentDesignId, json);
      saveIndicator.textContent = 'All changes saved';
    }
    return currentDesignId;
  } catch (e: any) {
    console.error(e);
    saveIndicator.textContent = 'Save failed';
    toast(`Save failed: ${e?.message ?? e}`, 'error');
    return null;
  }
}

function scheduleAutosave() {
  saveIndicator.textContent = 'Unsaved changes…';
  if (autosaveTimer) window.clearTimeout(autosaveTimer);
  autosaveTimer = window.setTimeout(persistOnce, 1200);
}

// Product resolution. Always async — saves wait for it.
// Sources, in priority order:
//   1. ?product=<uuid>  → already have id, fetch slug for the redirect
//   2. ?product=<slug>  → fetch the product, derive id
//   3. (nothing)        → take the first published product
let resolvingProduct: Promise<string> | null = null;

async function ensureProductId(): Promise<string> {
  if (productId && productSlug) return productId;
  if (resolvingProduct) return resolvingProduct;

  resolvingProduct = (async () => {
    let p = null;
    if (productSlug) {
      const res = await fetch(`${apiBase}/products/${encodeURIComponent(productSlug)}`);
      if (res.ok) p = await res.json();
    } else if (productId) {
      // Have UUID, find slug from the catalogue.
      const res = await fetch(`${apiBase}/products?per_page=200`);
      const list = (await res.json())?.data?.data ?? [];
      p = list.find((x) => x.id === productId) ?? null;
    } else {
      const res = await fetch(`${apiBase}/products?per_page=1`);
      const list = (await res.json())?.data?.data ?? [];
      p = list[0] ?? null;
    }
    if (!p?.id) throw new Error('No published product available.');

    productId = p.id;
    productSlug = p.slug;

    // Let the templates panel scope its API call to this product.
    setProductIdForTemplates(productId);

    // Update whatever product-display elements exist. The MOO-style top bar
    // only has #product-name-top — the right-side order card is gone — but
    // null-safe sets keep this working if someone reintroduces them later.
    const setText = (id: string, text: string) => {
      const el = document.getElementById(id);
      if (el) el.textContent = text;
    };
    setText('product-name', p.name);
    setText('product-name-top', p.name);
    setText('product-meta', p.description ? truncate(p.description, 70) : '85 × 55 mm · 350 gsm');
    setText('page-size-label',
      `${p.metadata_json?.format_label ?? '85 × 55 mm'}`);

    return productId;
  })();

  return resolvingProduct;
}

// Pre-warm so the right-side product card populates without waiting for the first save.
ensureProductId().catch((e) => console.warn('Pre-warm product id failed:', e));
function truncate(s: string, n: number) { return s.length > n ? s.slice(0, n - 1) + '…' : s; }

document.getElementById('save-draft')!.addEventListener('click', async () => {
  const id = await persistOnce();
  if (id) toast('Draft saved.', 'success');
});

// ---------------------------------------------------------------------------
// Approve flow:
//   1. Persist current state (force a save, even if autosave is debounced)
//   2. Render canvas → high-DPI PNG → PDF-LIB document at trim+bleed size
//   3. Show modal with editor PNG vs production PDF side-by-side
//   4. On confirm: tell backend to approve the design
// ---------------------------------------------------------------------------

const modal = document.getElementById('proof-modal')!;
const editorPreview = document.getElementById('editor-preview') as HTMLImageElement;
const pdfPreview = document.getElementById('pdf-preview') as HTMLIFrameElement;
const pdfDownload = document.getElementById('pdf-download') as HTMLAnchorElement;

document.getElementById('proof-close')!.addEventListener('click', () => modal.classList.remove('show'));
document.getElementById('proof-back')!.addEventListener('click', () => modal.classList.remove('show'));

// Hold the most-recently-rendered proof PDF bytes so the modal's "Confirm"
// button can upload them without re-rendering.
let lastProofBytes: Uint8Array | null = null;

document.getElementById('approve')!.addEventListener('click', async () => {
  const approveBtn = document.getElementById('approve') as HTMLButtonElement;
  approveBtn.disabled = true;
  approveBtn.textContent = '⏳ Generating proof…';

  try {
    // 1. Make sure we have a saved design.
    if (autosaveTimer) { window.clearTimeout(autosaveTimer); autosaveTimer = null; }
    const id = await persistOnce();
    if (!id) {
      toast('Could not save before approving.', 'error');
      return;
    }

    // 2. Render the canvas at print resolution, then build a PDF.
    const editorPng = canvas.toDataURL({ format: 'png', multiplier: 2 });
    lastProofBytes = await renderProofPdf(editorPng);

    // 3. Show the proof modal.
    editorPreview.src = editorPng;
    const blob = new Blob([lastProofBytes], { type: 'application/pdf' });
    const blobUrl = URL.createObjectURL(blob);
    pdfPreview.src = blobUrl;
    pdfDownload.href = blobUrl;
    modal.classList.add('show');

    toast('Proof PDF generated. Review it side-by-side with your editor preview.', 'success');
  } catch (e: any) {
    console.error(e);
    toast(`Approve failed: ${e?.message ?? e}`, 'error');
  } finally {
    approveBtn.disabled = false;
    approveBtn.textContent = '✓ Approve & continue';
  }
});

document.getElementById('proof-confirm')!.addEventListener('click', async () => {
  if (!currentDesignId) return;
  const btn = document.getElementById('proof-confirm') as HTMLButtonElement;
  btn.disabled = true;
  btn.textContent = '⏳ Uploading proof…';
  try {
    // Upload the client-rendered PDF so the server has the canonical print
    // artefact (not the placeholder the queued worker would otherwise create).
    if (lastProofBytes) {
      const meta = await client.uploadPrintPdf(currentDesignId, lastProofBytes);
      console.log('Proof uploaded:', meta);
    }
    await client.approve(currentDesignId);
    toast('Approved! Taking you to the product page to finalise…', 'success');

    // Make sure we have a slug for the redirect (resolve if user opened the
    // designer with no ?product= or with a UUID).
    await ensureProductId();
    const slug = productSlug || 'business-card';
    const target = `${storefrontUrl}/products/${encodeURIComponent(slug)}/?design=${encodeURIComponent(currentDesignId)}`;

    setTimeout(() => {
      if (window.opener) {
        window.opener.postMessage({ type: 'design-approved', design_id: currentDesignId, redirect: target }, '*');
        try { window.opener.location.href = target; } catch { /* cross-origin */ }
        window.close();
      } else {
        window.location.href = target;
      }
    }, 900);
  } catch (e: any) {
    toast(`Approve failed: ${e?.message ?? e}`, 'error');
    btn.disabled = false;
    btn.textContent = '✓ Confirm & add to cart';
  }
});

// ---------------------------------------------------------------------------
// PDF-LIB rendering
//
// The canvas is rendered to a data URL at 2× multiplier (≈300 DPI for a
// 85 × 55 mm trim). We embed that PNG into a PDF-LIB document whose page
// size is trim + 2× bleed in points (1 mm = 2.83465 pt) so the production
// PDF matches the spec §8 brief: "set page size including bleed".
// ---------------------------------------------------------------------------

async function renderProofPdf(pngDataUrl: string): Promise<Uint8Array> {
  const pdf = await PDFDocument.create();

  // Convert mm geometry to PDF points.
  const MM = 2.83465;
  const trimWmm = 85, trimHmm = 55, bleedMm = 3;
  const pageW = (trimWmm + bleedMm * 2) * MM;
  const pageH = (trimHmm + bleedMm * 2) * MM;

  const page = pdf.addPage([pageW, pageH]);

  // Strip the data URL prefix and embed.
  const base64 = pngDataUrl.replace(/^data:image\/png;base64,/, '');
  const binary = Uint8Array.from(atob(base64), (c) => c.charCodeAt(0));
  const png = await pdf.embedPng(binary);

  page.drawImage(png, { x: 0, y: 0, width: pageW, height: pageH });

  // Add a tiny imprint with the design id + timestamp in the bleed area.
  const meta = `Design ${currentDesignId?.slice(0, 8) ?? 'draft'} · ${new Date().toISOString()}`;
  page.setFontSize(4);
  page.drawText(meta, { x: 2, y: 2 });

  pdf.setTitle('PrintHub proof');
  pdf.setAuthor('PrintHub Designer');
  pdf.setSubject('Production-ready proof PDF');
  pdf.setProducer('PDF-LIB');

  return await pdf.save();
}

// ---------------------------------------------------------------------------
// Auto-load existing design when ?design=… is in the URL
// ---------------------------------------------------------------------------

(async () => {
  if (!currentDesignId) return;
  try {
    const design = await client.getDesign(currentDesignId);
    if (design.design_json) {
      await canvas.loadFromJSON(design.design_json);
      drawBleedAndSafe(canvas, CFG);
      canvas.renderAll();
      saveIndicator.textContent = `Loaded · #${currentDesignId.slice(0, 8)}`;
    }
  } catch (e) {
    toast(`Could not load design: ${e}`, 'error');
  }
})();
