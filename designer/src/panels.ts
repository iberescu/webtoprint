import { Canvas, FabricImage, IText, Rect, Circle, Triangle } from 'fabric';

/**
 * Renders the active left-panel content (templates / text / uploads / shapes
 * / colors / QR) and wires up button-level interactions on the canvas.
 *
 * Vistaprint-style "click to add", and Canva-style template thumbs.
 */
export type PanelKind = 'templates' | 'text' | 'uploads' | 'shapes' | 'colors' | 'qr';

const SWATCHES = [
  '#0f172a', '#1e293b', '#475569', '#94a3b8', '#cbd5e1', '#e2e8f0', '#f1f5f9', '#ffffff',
  '#dc2626', '#ea580c', '#f59e0b', '#facc15', '#84cc16', '#22c55e', '#06b6d4', '#0ea5e9',
  '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#000000',
];

const TEMPLATES = [
  { name: 'Modern Indigo',  bg: 'linear-gradient(135deg, #4f46e5, #6366f1)', text: 'Acme Studio',  text2: 'Design + Print' },
  { name: 'Bold Amber',     bg: 'linear-gradient(135deg, #f59e0b, #ea580c)', text: 'Sunrise Café', text2: 'Coffee · Pastries · Books' },
  { name: 'Minimal Slate',  bg: '#0f172a',                                   text: 'Helvetica Co.', text2: 'Brand Strategy' },
  { name: 'Pastel Pink',    bg: 'linear-gradient(135deg, #fbcfe8, #fda4af)', text: 'Bloom Florals', text2: 'Wedding · Events' },
  { name: 'Forest',         bg: 'linear-gradient(135deg, #166534, #22c55e)', text: 'Greenleaf',     text2: 'Sustainable design' },
  { name: 'Premium Black',  bg: 'linear-gradient(135deg, #18181b, #3f3f46)', text: 'Onyx Studio',   text2: 'Luxury & lifestyle' },
];

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

// ----- Templates ------------------------------------------------------------

function renderTemplates(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2>Templates</h2>
    <div class="panel-section">
      <h3>Quick start</h3>
      <p style="margin: 0 0 10px; font-size: 12px; color: var(--muted)">Click a template to apply it to your design.</p>
      <div class="templates"></div>
    </div>
  `;
  const grid = panel.querySelector('.templates')!;
  TEMPLATES.forEach((tpl) => {
    const card = document.createElement('div');
    card.className = 'template-card';
    card.style.background = tpl.bg;
    card.innerHTML = `
      <div style="position:absolute; inset:0; padding:12px; display:flex; flex-direction:column; justify-content:flex-end; color:white; font-family: ui-sans-serif, system-ui, sans-serif;">
        <div style="font-weight:800; font-size:13px;">${tpl.text}</div>
        <div style="font-size:10px; opacity:0.85;">${tpl.text2}</div>
      </div>
    `;
    card.title = tpl.name;
    card.onclick = () => applyTemplate(canvas, tpl);
    grid.appendChild(card);
  });
}

function applyTemplate(canvas: Canvas, tpl: typeof TEMPLATES[number]) {
  // remove user objects but keep overlays
  const keepers: any[] = (canvas.getObjects() as any[]).filter((o) => !o.selectable);
  canvas.clear();
  keepers.forEach((o) => canvas.add(o));

  // Background — use a flat colour (Fabric doesn't render CSS gradients on background)
  const flat = tpl.bg.startsWith('linear-gradient') ? tpl.bg.match(/#[0-9a-f]{6}/i)?.[0] ?? '#1e1b4b' : tpl.bg;
  const bg = new Rect({
    left: 0, top: 0, width: canvas.getWidth(), height: canvas.getHeight(),
    fill: flat, selectable: false, evented: false,
  });
  canvas.add(bg);
  canvas.sendObjectToBack(bg);

  const heading = new IText(tpl.text, {
    left: 40, top: canvas.getHeight() - 90, fontFamily: 'Helvetica', fontSize: 30,
    fontWeight: 'bold', fill: '#ffffff',
  });
  const sub = new IText(tpl.text2, {
    left: 40, top: canvas.getHeight() - 50, fontFamily: 'Helvetica', fontSize: 14,
    fill: 'rgba(255,255,255,0.85)',
  });
  canvas.add(heading, sub);
  canvas.setActiveObject(heading);
  canvas.renderAll();
}

// ----- Text -----------------------------------------------------------------

function renderText(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2>Text</h2>
    <div class="panel-section">
      <button class="btn btn-secondary" id="add-heading" style="width:100%; margin-bottom:8px;">＋ Heading</button>
      <button class="btn btn-secondary" id="add-subheading" style="width:100%; margin-bottom:8px;">＋ Subheading</button>
      <button class="btn btn-secondary" id="add-body" style="width:100%; margin-bottom:8px;">＋ Body text</button>
    </div>
    <div class="panel-section">
      <h3>Quick text</h3>
      <button class="btn btn-secondary" data-text="Your Name" style="width:100%; margin-bottom:6px;">Your Name</button>
      <button class="btn btn-secondary" data-text="Job Title" style="width:100%; margin-bottom:6px;">Job Title</button>
      <button class="btn btn-secondary" data-text="hello@example.com" style="width:100%; margin-bottom:6px;">Email</button>
      <button class="btn btn-secondary" data-text="+49 30 123 4567" style="width:100%; margin-bottom:6px;">Phone</button>
      <button class="btn btn-secondary" data-text="www.example.com" style="width:100%;">Website</button>
    </div>
  `;
  const add = (text: string, fontSize: number, fontWeight: string = 'normal') => {
    const t = new IText(text, {
      left: 80, top: 80, fontFamily: 'Helvetica', fontSize, fontWeight, fill: '#0f172a',
    });
    canvas.add(t); canvas.setActiveObject(t); canvas.renderAll();
  };
  panel.querySelector('#add-heading')!.addEventListener('click', () => add('Heading', 32, 'bold'));
  panel.querySelector('#add-subheading')!.addEventListener('click', () => add('Subheading', 20, '600'));
  panel.querySelector('#add-body')!.addEventListener('click', () => add('Body text', 14));
  panel.querySelectorAll('[data-text]').forEach((b) => {
    b.addEventListener('click', () => add((b as HTMLElement).dataset.text!, 16));
  });
}

// ----- Uploads --------------------------------------------------------------

function renderUploads(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2>Uploads</h2>
    <div class="panel-section">
      <input type="file" id="image-input" accept="image/*" hidden multiple />
      <button class="btn btn-primary" id="upload-trigger" style="width:100%;">⤴ Upload images</button>
      <p style="margin:8px 0 0; font-size:11px; color: var(--muted)">JPG, PNG, SVG · up to 20 MB each</p>
    </div>
    <div class="panel-section">
      <h3>Recent uploads</h3>
      <div class="uploads" id="upload-grid">
        <div class="upload-tile">No uploads yet</div>
      </div>
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

// ----- Shapes ---------------------------------------------------------------

function renderShapes(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2>Shapes</h2>
    <div class="panel-section">
      <div class="templates" id="shapes-grid"></div>
    </div>
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

// ----- Colors ---------------------------------------------------------------

function renderColors(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2>Colors</h2>
    <div class="panel-section">
      <h3>Document background</h3>
      <div class="colors" id="bg-colors"></div>
    </div>
    <div class="panel-section">
      <h3>Selected element fill</h3>
      <p style="margin:0 0 8px; font-size:12px; color: var(--muted)">Pick something on the canvas first.</p>
      <div class="colors" id="fill-colors"></div>
    </div>
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

// ----- QR code --------------------------------------------------------------

function renderQR(panel: HTMLElement, canvas: Canvas) {
  panel.innerHTML = `
    <h2>QR code</h2>
    <div class="panel-section">
      <p style="margin:0 0 8px; font-size:12px; color: var(--muted)">Generate a QR code from any URL or text — perfect for business cards and flyers.</p>
      <div class="field">
        <label>Content</label>
        <input type="text" id="qr-text" placeholder="https://your-website.com" />
      </div>
      <button class="btn btn-primary" id="qr-add" style="width:100%;">＋ Add QR code to design</button>
    </div>
  `;
  panel.querySelector('#qr-add')!.addEventListener('click', async () => {
    const text = panel.querySelector<HTMLInputElement>('#qr-text')!.value || 'https://printhub.example';
    // External QR service. In production swap for an offline lib (qrcode-svg).
    const url = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(text)}`;
    const img = await FabricImage.fromURL(url, { crossOrigin: 'anonymous' });
    img.set({ left: 60, top: 60, scaleX: 0.6, scaleY: 0.6 });
    canvas.add(img); canvas.setActiveObject(img); canvas.renderAll();
  });
}

// ----- Right-side: properties for the selected layer ------------------------

export function renderProperties(rightEl: HTMLElement, canvas: Canvas) {
  const obj: any = canvas.getActiveObject();
  const propsEl = rightEl.querySelector<HTMLElement>('#props')!;
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
          <option value="Helvetica">Helvetica</option>
          <option value="Georgia">Georgia</option>
          <option value="Times New Roman">Times</option>
          <option value="Courier New">Courier</option>
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
    <div class="row" style="margin-top: 8px;">
      <button class="btn btn-secondary" id="p-front">Bring forward</button>
      <button class="btn btn-secondary" id="p-back">Send back</button>
    </div>
    <button class="btn btn-secondary" id="p-duplicate" style="width:100%; margin-top: 8px;">⧉ Duplicate</button>
    <button class="btn btn-secondary" id="p-delete" style="width:100%; margin-top: 6px; color: var(--danger); border-color: #fecaca;">🗑 Delete</button>
  `;

  // bind
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
