import { Canvas, FabricImage, IText, Rect, Circle, Triangle } from 'fabric';

/**
 * Renders left-tool panels (templates / text / uploads / shapes / colors / QR)
 * and the always-visible "selected layer" properties block. Vistaprint-style
 * "click to add" interactions, Canva-style template thumbs.
 *
 * Templates are pulled from `GET /api/v1/designer/templates?product_id=...`
 * (filtered to status=published) and applied via `canvas.loadFromJSON` so
 * every template seeded by `DesignTemplatesSeeder` works out of the box.
 * If the API can't be reached (designer opened without a backend), the panel
 * falls back to the hand-rolled `LEGACY_TEMPLATES` below so the editor stays
 * usable.
 */
export type PanelKind = 'templates' | 'text' | 'uploads' | 'shapes' | 'colors' | 'qr';

/**
 * Set once the bootstrapping product is resolved (see main.ts).
 * Anyone waiting via `whenProductIdReady()` (the templates panel) is woken up.
 */
let CURRENT_PRODUCT_ID: string | null = null;
let pendingResolvers: Array<(id: string) => void> = [];
export function setProductIdForTemplates(id: string) {
  CURRENT_PRODUCT_ID = id;
  const drain = pendingResolvers;
  pendingResolvers = [];
  drain.forEach((r) => r(id));
}
function whenProductIdReady(): Promise<string> {
  if (CURRENT_PRODUCT_ID) return Promise.resolve(CURRENT_PRODUCT_ID);
  return new Promise<string>((resolve) => pendingResolvers.push(resolve));
}

type ApiTemplate = {
  id: string;
  name: string;
  width_mm: number;
  height_mm: number;
  template_json: any;
};

/**
 * Same resolver as main.ts: prefer same-origin when behind the aggregator
 * (path `/designer/…`) so the templates fetch never hits a foreign host
 * and never triggers a CORS preflight. See main.ts for the rationale.
 */
function resolveApiBase(): string {
  if (typeof window !== 'undefined' && window.location.pathname.startsWith('/designer/')) {
    return `${window.location.origin}/api/v1`;
  }
  return (import.meta as any).env?.VITE_API_URL ?? 'http://localhost:8000/api/v1';
}
const apiBase = resolveApiBase();

const SWATCHES = [
  '#0f172a', '#1e293b', '#475569', '#94a3b8', '#cbd5e1', '#e2e8f0', '#f1f5f9', '#ffffff',
  '#dc2626', '#ea580c', '#f59e0b', '#facc15', '#84cc16', '#22c55e', '#06b6d4', '#0ea5e9',
  '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#000000',
];

const LEGACY_TEMPLATES = [
  { name: 'Modern Indigo',  bg: '#4f46e5', accent: '#fbbf24', text: 'Acme Studio',  text2: 'Design + Print' },
  { name: 'Bold Amber',     bg: '#f59e0b', accent: '#1e293b', text: 'Sunrise Café', text2: 'Coffee · Pastries · Books' },
  { name: 'Minimal Slate',  bg: '#0f172a', accent: '#94a3b8', text: 'Helvetica Co.', text2: 'Brand Strategy' },
  { name: 'Pastel Pink',    bg: '#fbcfe8', accent: '#9d174d', text: 'Bloom Florals', text2: 'Wedding · Events' },
  { name: 'Forest',         bg: '#166534', accent: '#86efac', text: 'Greenleaf',     text2: 'Sustainable design' },
  { name: 'Premium Black',  bg: '#18181b', accent: '#fbbf24', text: 'Onyx Studio',   text2: 'Luxury & lifestyle' },
];

async function fetchTemplates(productId: string | null): Promise<ApiTemplate[]> {
  if (!productId) return [];
  try {
    const url = `${apiBase}/designer/templates?product_id=${encodeURIComponent(productId)}`;
    const res = await fetch(url);
    if (!res.ok) return [];
    const body = await res.json();
    return Array.isArray(body?.data) ? (body.data as ApiTemplate[]) : [];
  } catch {
    return [];
  }
}

/**
 * Inline preview of a template. If the scene contains photo objects (any
 * Fabric `image` type referencing a /images/templates/… URL), we drop one
 * in as the card's background — so image-hero and photo-grid cards look
 * like real previews instead of solid colour blocks. Pure-typography
 * layouts still show the bg colour + brand name overlay.
 */
function templateCardHtml(tpl: ApiTemplate): string {
  const bg = tpl.template_json?.background ?? '#0f1a30';
  let accent = '#D4AF37', heading = '#FFFFFF', name = 'YOUR COMPANY';
  let photoSrc: string | null = null;

  for (const o of tpl.template_json?.objects ?? []) {
    if (o?.type === 'image' && typeof o?.src === 'string' && photoSrc === null) {
      photoSrc = o.src;
    }
    if (o?.name === 'accent-divider' || o?.name === 'accent-strip' || o?.name === 'accent-band' || o?.name === 'accent-corner') {
      accent = o.fill ?? accent;
    }
    if (o?.name === 'placeholder-company') {
      heading = o.fill ?? heading;
      name = (o.text ?? '').slice(0, 22);
    }
  }

  const bgLayer = photoSrc
    ? `<div style="position:absolute; inset:0; background:url('${photoSrc}') center/cover no-repeat;"></div>
       <div style="position:absolute; inset:0; background:linear-gradient(to top, ${bg}ee 0%, ${bg}66 55%, transparent 100%);"></div>`
    : `<div style="position:absolute; inset:0; background:${bg};"></div>`;

  return `
    ${bgLayer}
    <div style="position:absolute; inset:0; padding:8px; display:flex; flex-direction:column; justify-content:flex-end; color:${heading}; pointer-events:none;">
      <div style="height:3px; width:24px; background:${accent}; margin-bottom:5px; border-radius:2px;"></div>
      <div style="font-weight:800; font-size:11px; line-height:1.1; opacity:0.95; text-shadow:0 1px 2px rgba(0,0,0,0.4);">${name}</div>
      <div style="font-size:9px; opacity:0.7; margin-top:1px; text-shadow:0 1px 1px rgba(0,0,0,0.4);">${tpl.width_mm}×${tpl.height_mm} mm</div>
    </div>
  `;
}

const SHAPES = [
  { kind: 'rect',     label: 'Rectangle' },
  { kind: 'circle',   label: 'Circle' },
  { kind: 'triangle', label: 'Triangle' },
  { kind: 'line',     label: 'Line' },
];

export function renderPanel(panelEl: HTMLElement, kind: PanelKind, canvas: Canvas) {
  switch (kind) {
    case 'templates': return renderTemplates(panelEl, canvas);
    case 'text':      return renderText(panelEl, canvas);
    case 'uploads':   return renderUploads(panelEl, canvas);
    case 'shapes':    return renderShapes(panelEl, canvas);
    case 'colors':    return renderColors(panelEl, canvas);
    case 'qr':        return renderQR(panelEl, canvas);
  }
}

async function renderTemplates(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2 class="section-title">Templates</h2>
    <p style="margin: 0 0 12px; font-size: 12px; color: var(--muted)">Click a template to start. You can edit every element afterwards.</p>
    <div class="templates" id="templates-grid">
      <div class="empty-state" style="grid-column: 1 / -1;">Loading templates…</div>
    </div>
  `;
  const grid = panel.querySelector<HTMLElement>('#templates-grid')!;

  // Block on the product ID. If the user clicked Templates before
  // main.ts finished its `ensureProductId()` pre-warm, this resolves
  // the moment that pre-warm completes — the panel stays on "Loading…"
  // until then instead of dropping back to the 6 legacy templates.
  const productId = await whenProductIdReady();
  const apiTemplates = await fetchTemplates(productId);

  grid.innerHTML = '';
  if (apiTemplates.length > 0) {
    apiTemplates.forEach((tpl) => {
      const card = document.createElement('div');
      card.className = 'template-card';
      card.title = tpl.name;
      card.innerHTML = templateCardHtml(tpl);
      card.onclick = () => applySeededTemplate(canvas, tpl);
      grid.appendChild(card);
    });
    return;
  }

  // The backend has no templates for this product (or the request failed).
  // Show the 6 legacy templates so the editor is still usable.
  grid.insertAdjacentHTML('beforebegin',
    `<div class="empty-state" style="margin: 0 0 12px; padding: 8px 12px; background: #fff7ed; color: #9a3412; border-radius: 8px; font-size: 12px;">
       No seeded templates for this product yet — showing built-in fallbacks.
     </div>`);
  LEGACY_TEMPLATES.forEach((tpl) => {
    const card = document.createElement('div');
    card.className = 'template-card';
    card.style.background = tpl.bg;
    card.innerHTML = `
      <div style="position:absolute; inset:0; padding:10px; display:flex; flex-direction:column; justify-content:flex-end; color:white; font-family: ui-sans-serif, system-ui, sans-serif;">
        <div style="height:3px; width:24px; background:${tpl.accent}; margin-bottom:6px; border-radius:2px;"></div>
        <div style="font-weight:800; font-size:12px;">${tpl.text}</div>
        <div style="font-size:9px; opacity:0.85;">${tpl.text2}</div>
      </div>
    `;
    card.title = tpl.name;
    card.onclick = () => applyLegacyTemplate(canvas, tpl);
    grid.appendChild(card);
  });
}

/**
 * Replace canvas content with a backend-seeded template (Fabric.js scene).
 * Keeps the bleed/safe overlays in place.
 */
async function applySeededTemplate(canvas: Canvas, tpl: ApiTemplate) {
  const keepers: any[] = (canvas.getObjects() as any[]).filter((o) => !o.selectable);
  canvas.clear();

  try {
    await canvas.loadFromJSON(tpl.template_json);
  } catch (e) {
    console.error('Failed to load template JSON:', e);
    // Restore overlays at least.
    keepers.forEach((o) => canvas.add(o));
    return;
  }

  // Put bleed/safe overlays back on top.
  keepers.forEach((o) => canvas.add(o));
  canvas.renderAll();
}

function applyLegacyTemplate(canvas: Canvas, tpl: typeof LEGACY_TEMPLATES[number]) {
  // remove user objects but keep overlays
  const keepers: any[] = (canvas.getObjects() as any[]).filter((o) => !o.selectable);
  canvas.clear();
  keepers.forEach((o) => canvas.add(o));

  const bg = new Rect({
    left: 0, top: 0, width: canvas.getWidth(), height: canvas.getHeight(),
    fill: tpl.bg, selectable: false, evented: false,
  });
  canvas.add(bg);
  canvas.sendObjectToBack(bg);

  // Accent bar
  const bar = new Rect({
    left: 40, top: 60, width: 60, height: 4, fill: tpl.accent,
  });
  const heading = new IText(tpl.text, {
    left: 40, top: 80, fontFamily: 'Helvetica', fontSize: 30,
    fontWeight: 'bold', fill: '#ffffff',
  });
  const sub = new IText(tpl.text2, {
    left: 40, top: 120, fontFamily: 'Helvetica', fontSize: 14,
    fill: 'rgba(255,255,255,0.85)',
  });
  canvas.add(bar, heading, sub);
  canvas.setActiveObject(heading);
  canvas.renderAll();
}

function renderText(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2 class="section-title">Text</h2>
    <button class="btn btn-secondary" id="add-heading" style="width:100%; margin-bottom:8px;">＋ Heading</button>
    <button class="btn btn-secondary" id="add-subheading" style="width:100%; margin-bottom:8px;">＋ Subheading</button>
    <button class="btn btn-secondary" id="add-body" style="width:100%; margin-bottom:8px;">＋ Body text</button>
    <h3 class="subsection-title" style="margin-top: 18px;">Quick text</h3>
    <button class="btn btn-secondary" data-text="Your Name" style="width:100%; margin-bottom:6px;">Your Name</button>
    <button class="btn btn-secondary" data-text="Job Title" style="width:100%; margin-bottom:6px;">Job Title</button>
    <button class="btn btn-secondary" data-text="hello@example.com" style="width:100%; margin-bottom:6px;">Email</button>
    <button class="btn btn-secondary" data-text="+49 30 123 4567" style="width:100%; margin-bottom:6px;">Phone</button>
    <button class="btn btn-secondary" data-text="www.example.com" style="width:100%;">Website</button>
  `;
  const add = (text: string, fontSize: number, fontWeight = 'normal') => {
    const t = new IText(text, { left: 80, top: 80, fontFamily: 'Helvetica', fontSize, fontWeight, fill: '#0f172a' });
    canvas.add(t); canvas.setActiveObject(t); canvas.renderAll();
  };
  panel.querySelector('#add-heading')!.addEventListener('click', () => add('Heading', 32, 'bold'));
  panel.querySelector('#add-subheading')!.addEventListener('click', () => add('Subheading', 20, '600'));
  panel.querySelector('#add-body')!.addEventListener('click', () => add('Body text', 14));
  panel.querySelectorAll('[data-text]').forEach((b) => {
    b.addEventListener('click', () => add((b as HTMLElement).dataset.text!, 16));
  });
}

function renderUploads(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2 class="section-title">Uploads</h2>
    <input type="file" id="image-input" accept="image/*" hidden multiple />
    <button class="btn btn-primary" id="upload-trigger" style="width:100%; justify-content: center;">⤴ Upload images</button>
    <p style="margin:8px 0 16px; font-size:11px; color: var(--muted)">JPG, PNG, SVG · up to 20 MB each</p>
    <h3 class="subsection-title">Recent uploads</h3>
    <div class="uploads" id="upload-grid">
      <div class="upload-tile">No uploads yet</div>
    </div>
  `;
  const input = panel.querySelector<HTMLInputElement>('#image-input')!;
  const grid = panel.querySelector<HTMLElement>('#upload-grid')!;
  panel.querySelector('#upload-trigger')!.addEventListener('click', () => input.click());

  input.addEventListener('change', async () => {
    if (!input.files) return;
    grid.innerHTML = '';
    for (const file of Array.from(input.files)) {
      const dataUrl = await new Promise<string>((r) => {
        const fr = new FileReader(); fr.onload = () => r(fr.result as string); fr.readAsDataURL(file);
      });
      const tile = document.createElement('div');
      tile.className = 'upload-tile';
      tile.style.padding = '0';
      tile.innerHTML = `<img src="${dataUrl}" alt="" />`;
      tile.onclick = async () => {
        const img = await FabricImage.fromURL(dataUrl);
        img.set({ left: 60, top: 60, scaleX: 0.4, scaleY: 0.4 });
        canvas.add(img); canvas.setActiveObject(img); canvas.renderAll();
      };
      grid.appendChild(tile);
    }
  });
}

function renderShapes(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2 class="section-title">Shapes</h2>
    <div class="templates" id="shapes-grid"></div>
  `;
  const grid = panel.querySelector<HTMLElement>('#shapes-grid')!;
  SHAPES.forEach((s) => {
    const tile = document.createElement('div');
    tile.className = 'template-card';
    tile.style.background = '#f1f5f9';
    tile.style.display = 'grid';
    tile.style.placeItems = 'center';
    tile.title = s.label;
    tile.innerHTML = svgIconForShape(s.kind);
    tile.onclick = () => addShape(canvas, s.kind);
    grid.appendChild(tile);
  });
}

function svgIconForShape(kind: string) {
  switch (kind) {
    case 'rect':     return '<svg width="40" height="30" viewBox="0 0 40 30"><rect x="3" y="3" width="34" height="24" fill="#4f46e5"/></svg>';
    case 'circle':   return '<svg width="36" height="36" viewBox="0 0 36 36"><circle cx="18" cy="18" r="14" fill="#f59e0b"/></svg>';
    case 'triangle': return '<svg width="36" height="36" viewBox="0 0 36 36"><polygon points="18,4 32,30 4,30" fill="#10b981"/></svg>';
    case 'line':     return '<svg width="50" height="6" viewBox="0 0 50 6"><line x1="0" y1="3" x2="50" y2="3" stroke="#0f172a" stroke-width="3"/></svg>';
    default:         return '';
  }
}

function addShape(canvas: Canvas, kind: string) {
  let obj: any;
  switch (kind) {
    case 'rect':     obj = new Rect({ left: 100, top: 100, width: 120, height: 80, fill: '#4f46e5' }); break;
    case 'circle':   obj = new Circle({ left: 120, top: 120, radius: 50, fill: '#f59e0b' }); break;
    case 'triangle': obj = new Triangle({ left: 120, top: 120, width: 100, height: 100, fill: '#10b981' }); break;
    case 'line':     obj = new Rect({ left: 80, top: 200, width: 200, height: 4, fill: '#0f172a' }); break;
  }
  canvas.add(obj); canvas.setActiveObject(obj); canvas.renderAll();
}

function renderColors(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2 class="section-title">Colors</h2>
    <h3 class="subsection-title">Document background</h3>
    <div class="colors" id="bg-colors"></div>
    <h3 class="subsection-title" style="margin-top: 16px;">Selected element fill</h3>
    <p style="margin:0 0 8px; font-size:11px; color: var(--muted)">Pick an element first.</p>
    <div class="colors" id="fill-colors"></div>
  `;
  const renderSwatches = (el: Element, onPick: (c: string) => void) => {
    SWATCHES.forEach((c) => {
      const b = document.createElement('button');
      b.style.background = c;
      b.title = c;
      b.onclick = () => onPick(c);
      el.appendChild(b);
    });
  };
  renderSwatches(panel.querySelector('#bg-colors')!, (c) => {
    canvas.backgroundColor = c;
    canvas.renderAll();
  });
  renderSwatches(panel.querySelector('#fill-colors')!, (c) => {
    const obj = canvas.getActiveObject();
    if (!obj) return;
    (obj as any).set('fill', c);
    canvas.renderAll();
  });
}

function renderQR(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2 class="section-title">QR code</h2>
    <p style="margin:0 0 12px; font-size:12px; color: var(--muted)">Generate a QR code from any URL or text — perfect for business cards and flyers.</p>
    <div class="field"><label>Content</label><input type="text" id="qr-text" placeholder="https://your-website.com" /></div>
    <button class="btn btn-primary" id="qr-add" style="width:100%; justify-content: center;">＋ Add QR code</button>
  `;
  panel.querySelector('#qr-add')!.addEventListener('click', async () => {
    const text = panel.querySelector<HTMLInputElement>('#qr-text')!.value || 'https://printhub.example';
    const url = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(text)}`;
    try {
      const img = await FabricImage.fromURL(url, { crossOrigin: 'anonymous' });
      img.set({ left: 60, top: 60, scaleX: 0.6, scaleY: 0.6 });
      canvas.add(img); canvas.setActiveObject(img); canvas.renderAll();
    } catch (e) {
      alert(`QR generation failed: ${e}`);
    }
  });
}

// ----- Selected-layer properties (rendered in the LEFT bottom block) --------

export function renderProperties(propsEl: HTMLElement, canvas: Canvas) {
  const obj: any = canvas.getActiveObject();
  if (!obj) {
    propsEl.innerHTML = '<div class="empty-state">Click any element on the canvas to edit it.</div>';
    return;
  }

  const isText = obj.type === 'i-text' || obj.type === 'text';
  propsEl.innerHTML = `
    ${isText ? `
      <div class="field"><label>Text</label><input type="text" id="p-text" value="${escapeHtml(obj.text ?? '')}" /></div>
      <div class="row">
        <div class="field"><label>Size</label><input type="number" id="p-size" value="${obj.fontSize ?? 16}" min="6" max="200"/></div>
        <div class="field"><label>Weight</label>
          <select id="p-weight">
            <option value="normal" ${obj.fontWeight === 'normal' ? 'selected' : ''}>Regular</option>
            <option value="600" ${obj.fontWeight === '600' ? 'selected' : ''}>Semibold</option>
            <option value="bold" ${obj.fontWeight === 'bold' ? 'selected' : ''}>Bold</option>
          </select>
        </div>
      </div>
      <div class="field"><label>Font</label>
        <select id="p-font">
          <option value="Helvetica" ${obj.fontFamily === 'Helvetica' ? 'selected' : ''}>Helvetica</option>
          <option value="Georgia" ${obj.fontFamily === 'Georgia' ? 'selected' : ''}>Georgia</option>
          <option value="Times New Roman" ${obj.fontFamily === 'Times New Roman' ? 'selected' : ''}>Times</option>
          <option value="Courier New" ${obj.fontFamily === 'Courier New' ? 'selected' : ''}>Courier</option>
        </select>
      </div>
    ` : ''}
    <div class="field"><label>Fill colour</label><input type="color" id="p-fill" value="${asHex(obj.fill)}" style="height: 36px; padding: 2px;"/></div>
    <div class="row">
      <div class="field"><label>X</label><input type="number" id="p-x" value="${Math.round(obj.left ?? 0)}"/></div>
      <div class="field"><label>Y</label><input type="number" id="p-y" value="${Math.round(obj.top ?? 0)}"/></div>
    </div>
    <div class="row">
      <div class="field"><label>Rotation</label><input type="number" id="p-angle" value="${Math.round(obj.angle ?? 0)}" /></div>
      <div class="field"><label>Opacity</label><input type="number" id="p-op" value="${Math.round((obj.opacity ?? 1) * 100)}" min="0" max="100"/></div>
    </div>
    <div class="row" style="margin-top: 4px;">
      <button class="btn btn-secondary" id="p-front" style="justify-content: center;">↑ Forward</button>
      <button class="btn btn-secondary" id="p-back" style="justify-content: center;">↓ Back</button>
    </div>
    <div class="row" style="margin-top: 6px;">
      <button class="btn btn-secondary" id="p-duplicate" style="justify-content: center;">⧉ Duplicate</button>
      <button class="btn btn-secondary" id="p-delete" style="justify-content: center; color: var(--danger); border-color: #fecaca;">🗑 Delete</button>
    </div>
  `;

  if (isText) {
    propsEl.querySelector<HTMLInputElement>('#p-text')!.addEventListener('input', (e) => { obj.set('text', (e.target as HTMLInputElement).value); canvas.renderAll(); });
    propsEl.querySelector<HTMLInputElement>('#p-size')!.addEventListener('input', (e) => { obj.set('fontSize', +(e.target as HTMLInputElement).value); canvas.renderAll(); });
    propsEl.querySelector<HTMLSelectElement>('#p-weight')!.addEventListener('change', (e) => { obj.set('fontWeight', (e.target as HTMLSelectElement).value); canvas.renderAll(); });
    propsEl.querySelector<HTMLSelectElement>('#p-font')!.addEventListener('change', (e) => { obj.set('fontFamily', (e.target as HTMLSelectElement).value); canvas.renderAll(); });
  }
  propsEl.querySelector<HTMLInputElement>('#p-fill')!.addEventListener('input', (e) => { obj.set('fill', (e.target as HTMLInputElement).value); canvas.renderAll(); });
  propsEl.querySelector<HTMLInputElement>('#p-x')!.addEventListener('input', (e) => { obj.set('left', +(e.target as HTMLInputElement).value); canvas.renderAll(); });
  propsEl.querySelector<HTMLInputElement>('#p-y')!.addEventListener('input', (e) => { obj.set('top', +(e.target as HTMLInputElement).value); canvas.renderAll(); });
  propsEl.querySelector<HTMLInputElement>('#p-angle')!.addEventListener('input', (e) => { obj.set('angle', +(e.target as HTMLInputElement).value); canvas.renderAll(); });
  propsEl.querySelector<HTMLInputElement>('#p-op')!.addEventListener('input', (e) => { obj.set('opacity', +(e.target as HTMLInputElement).value / 100); canvas.renderAll(); });
  propsEl.querySelector<HTMLButtonElement>('#p-front')!.addEventListener('click', () => { canvas.bringObjectForward(obj); canvas.renderAll(); });
  propsEl.querySelector<HTMLButtonElement>('#p-back')!.addEventListener('click', () => { canvas.sendObjectBackwards(obj); canvas.renderAll(); });
  propsEl.querySelector<HTMLButtonElement>('#p-duplicate')!.addEventListener('click', async () => {
    const cloned: any = await obj.clone();
    cloned.set({ left: (obj.left ?? 0) + 16, top: (obj.top ?? 0) + 16 });
    canvas.add(cloned); canvas.setActiveObject(cloned); canvas.renderAll();
  });
  propsEl.querySelector<HTMLButtonElement>('#p-delete')!.addEventListener('click', () => {
    canvas.remove(obj); canvas.discardActiveObject(); canvas.renderAll();
  });
}

function escapeHtml(s: string) {
  return s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' } as any)[c]);
}

function asHex(value: any): string {
  if (typeof value !== 'string') return '#0f172a';
  if (/^#[0-9a-f]{6}$/i.test(value)) return value;
  if (/^#[0-9a-f]{3}$/i.test(value)) return '#' + value.slice(1).split('').map((c) => c + c).join('');
  return '#0f172a';
}
