import { Canvas, Rect } from 'fabric';

/**
 * Bleed (red dashed) + safe-area (green dashed) overlays. Non-selectable
 * helpers that scroll with the canvas; excluded from export.
 */
export function drawBleedAndSafe(
  canvas: Canvas,
  cfg: { trim_w: number; trim_h: number; bleed: number; safe: number },
) {
  const bleed = new Rect({
    left: -cfg.bleed,
    top: -cfg.bleed,
    width: cfg.trim_w + cfg.bleed * 2,
    height: cfg.trim_h + cfg.bleed * 2,
    fill: 'transparent',
    stroke: '#ef4444',
    strokeDashArray: [6, 6],
    selectable: false,
    evented: false,
    excludeFromExport: true,
  });
  const safe = new Rect({
    left: cfg.safe,
    top: cfg.safe,
    width: cfg.trim_w - cfg.safe * 2,
    height: cfg.trim_h - cfg.safe * 2,
    fill: 'transparent',
    stroke: '#10b981',
    strokeDashArray: [4, 4],
    selectable: false,
    evented: false,
    excludeFromExport: true,
  });
  canvas.add(bleed, safe);
  canvas.sendObjectToBack(safe);
  canvas.sendObjectToBack(bleed);
}
