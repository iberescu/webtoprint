/**
 * Thin client over the backend Designer endpoints (spec §8).
 * Uses cookies/session implicitly when same-origin; otherwise pass a token.
 */
export class DesignerClient {
  constructor(private base: string, private token?: string) {}

  private headers() {
    const h: Record<string, string> = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    if (this.token) h['Authorization'] = `Bearer ${this.token}`;
    return h;
  }

  async createDesign(productId: string, designJson: unknown) {
    const res = await fetch(`${this.base}/designer/designs`, {
      method: 'POST',
      headers: this.headers(),
      body: JSON.stringify({ product_id: productId, design_json: designJson }),
    });
    if (!res.ok) throw new Error(`Create failed: ${res.status}`);
    return res.json();
  }

  async getDesign(id: string) {
    const res = await fetch(`${this.base}/designer/designs/${id}`, { headers: this.headers() });
    if (!res.ok) throw new Error(`Fetch failed: ${res.status}`);
    return res.json();
  }

  async updateDesign(id: string, designJson: unknown) {
    const res = await fetch(`${this.base}/designer/designs/${id}`, {
      method: 'PUT',
      headers: this.headers(),
      body: JSON.stringify({ design_json: designJson }),
    });
    if (!res.ok) throw new Error(`Update failed: ${res.status}`);
    return res.json();
  }

  async preview(id: string) {
    const res = await fetch(`${this.base}/designer/designs/${id}/preview`, {
      method: 'POST', headers: this.headers(),
    });
    if (!res.ok) throw new Error(`Preview failed: ${res.status}`);
    return res.json();
  }

  async approve(id: string) {
    const res = await fetch(`${this.base}/designer/designs/${id}/approve`, {
      method: 'POST', headers: this.headers(),
    });
    if (!res.ok) throw new Error(`Approve failed: ${res.status}`);
    return res.json();
  }

  /**
   * Upload the client-rendered (PDF-LIB) proof PDF and link it to the design
   * as `print_pdf_file_id`. Returns the file's sha256 so callers can verify.
   */
  async uploadPrintPdf(id: string, pdf: Uint8Array | Blob) {
    const blob = pdf instanceof Blob ? pdf : new Blob([pdf], { type: 'application/pdf' });
    const form = new FormData();
    form.append('pdf', blob, `design-${id}.pdf`);

    const headers: Record<string, string> = { 'Accept': 'application/json' };
    if (this.token) headers['Authorization'] = `Bearer ${this.token}`;

    const res = await fetch(`${this.base}/designer/designs/${id}/upload-print-pdf`, {
      method: 'POST', headers, body: form,
    });
    if (!res.ok) throw new Error(`Upload PDF failed: ${res.status}`);
    return res.json() as Promise<{ design_id: string; print_pdf_file_id: string; size: number; sha256: string }>;
  }
}
