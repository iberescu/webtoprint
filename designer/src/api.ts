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
}
