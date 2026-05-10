import { Canvas } from 'fabric';
import { DesignerClient } from './api';
import { drawBleedAndSafe } from './overlays';
import { renderPanel, renderProperties, type PanelKind } from './panels';

const params = new URLSearchParams(window.location.search);
const designId = params.get('design');
const productId = params.get('product') ?? '';
const apiBase = (import.meta as any).env?.VITE_API_URL ?? 'http://localhost:8000/api/v1';
const client = new DesignerClient(apiBase);

const statusEl = document.getElementById('status') as HTMLElement;
function flash(msg: string) {
  statusEl.textContent = msg;
  statusEl.classList.add('show');
  setTimeout(() => statusEl.classList.remove('show'), 2200);
}

// Canvas setup — use a business-card aspect ratio by default. Real production
// dimensions get applied when a product context is loaded.
const stageEl = document.getElementById('stage') as HTMLElement;
const canvasEl = document.getElementById('canvas') as HTMLCanvasElement;
const CFG = { trim_w: 600, trim_h: 400, bleed: 18, safe: 30 };
canvasEl.width = CFG.trim_w;
canvasEl.height = CFG.trim_h;

const canvas = new Canvas(canvasEl, { backgroundColor: '#ffffff', preserveObjectStacking: true });
drawBleedAndSafe(canvas, CFG);

// --- History stack (undo/redo) ---------------------------------------------
const history: string[] = [];
let historyIdx = -1;
let isRestoring = false;

function snapshot() {
  if (isRestoring) return;
  const json = JSON.stringify(canvas.toJSON());
  // truncate forward history when a new edit happens after an undo
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
canvas.on('object:added', snapshot);
canvas.on('object:modified', snapshot);
canvas.on('object:removed', snapshot);
snapshot(); // initial empty state

document.getElementById('undo')!.addEventListener('click', () => {
  if (historyIdx <= 0) return;
  historyIdx--;
  restore(history[historyIdx]);
});
document.getElementById('redo')!.addEventListener('click', () => {
  if (historyIdx >= history.length - 1) return;
  historyIdx++;
  restore(history[historyIdx]);
});

// keyboard shortcuts
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
        canvas.remove(obj);
        canvas.discardActiveObject();
        canvas.renderAll();
      }
    }
  }
});

// --- Left sidebar tabs ------------------------------------------------------
const panelEl = document.getElementById('panel') as HTMLElement;
const sidebarBtns = document.querySelectorAll<HTMLButtonElement>('.sidebar button');

function setPanel(kind: PanelKind) {
  sidebarBtns.forEach((b) => b.classList.toggle('active', b.dataset.panel === kind));
  renderPanel(panelEl, kind, canvas);
}
sidebarBtns.forEach((b) => b.addEventListener('click', () => setPanel(b.dataset.panel as PanelKind)));
setPanel('templates');

// --- Right-side selected-layer properties -----------------------------------
const rightEl = document.getElementById('right') as HTMLElement;
const refreshProps = () => renderProperties(rightEl, canvas);
canvas.on('selection:created', refreshProps);
canvas.on('selection:updated', refreshProps);
canvas.on('selection:cleared', refreshProps);
canvas.on('object:modified', refreshProps);

// --- Top-bar actions --------------------------------------------------------
let currentDesignId: string | null = designId;

document.getElementById('save')!.addEventListener('click', async () => {
  const json = canvas.toJSON();
  try {
    if (!currentDesignId) {
      const created = await client.createDesign(productId, json);
      currentDesignId = created.id;
      history.replaceState(null, '', `?design=${created.id}&product=${productId}`);
      flash(`Saved · design ${created.id.slice(0, 8)}`);
    } else {
      await client.updateDesign(currentDesignId, json);
      flash('Saved.');
    }
  } catch (e) { flash(`Save failed: ${e}`); }
});

document.getElementById('preview')!.addEventListener('click', async () => {
  if (!currentDesignId) return flash('Save first.');
  await client.preview(currentDesignId);
  flash('Preview generation queued.');
});

document.getElementById('approve')!.addEventListener('click', async () => {
  if (!currentDesignId) return flash('Save first.');
  await client.approve(currentDesignId);
  flash('Approved! Returning to product…');
  setTimeout(() => {
    if (window.opener) window.opener.postMessage({ type: 'design-approved', design_id: currentDesignId }, '*');
    window.history.length > 1 ? window.history.back() : (window.location.href = '/');
  }, 1200);
});

// --- Auto-load existing design ----------------------------------------------
(async () => {
  if (!currentDesignId) return;
  try {
    const design = await client.getDesign(currentDesignId);
    if (design.design_json) {
      await canvas.loadFromJSON(design.design_json);
      drawBleedAndSafe(canvas, CFG);
      canvas.renderAll();
      flash(`Loaded design ${currentDesignId.slice(0, 8)}`);
    }
  } catch (e) {
    flash(`Could not load design: ${e}`);
  }
})();
