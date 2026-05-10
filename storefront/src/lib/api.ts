/**
 * Tiny typed API client for the storefront. All Astro pages and React islands
 * use this — never call fetch directly.
 *
 * Endpoints map 1:1 to the spec §14 REST shape.
 */
const BASE = import.meta.env.PUBLIC_API_URL ?? 'http://localhost:8000/api/v1';

export class ApiError extends Error {
  constructor(public status: number, public payload: unknown, message: string) {
    super(message);
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const res = await fetch(`${BASE}${path}`, {
    ...init,
    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', ...(init.headers ?? {}) },
  });
  if (!res.ok) {
    let payload: unknown = null;
    try { payload = await res.json(); } catch { /* ignore */ }
    throw new ApiError(res.status, payload, `${res.status} ${res.statusText}`);
  }
  if (res.status === 204) return undefined as T;
  return (await res.json()) as T;
}

export const api = {
  products: {
    list: (params: Record<string, string> = {}) =>
      request<{ data: { data: Product[] } }>(`/products?${new URLSearchParams(params)}`),
    show: (slug: string) => request<Product>(`/products/${slug}`),
    configurator: (slug: string) => request<ConfiguratorPayload>(`/products/${slug}/configurator`),
    validate: (slug: string, configuration: Record<string, string>) =>
      request<ValidationResult>(`/products/${slug}/validate`, {
        method: 'POST', body: JSON.stringify({ configuration }),
      }),
    price: (slug: string, configuration: Record<string, string | number>) =>
      request<PriceResult>(`/products/${slug}/price`, {
        method: 'POST', body: JSON.stringify({ configuration }),
      }),
  },
  cart: {
    create: () => request<CartResponse>(`/storefront/cart`, { method: 'POST', body: '{}' }),
    show: (id: number | string) => request<CartResponse>(`/storefront/cart/${id}`),
    addItem: (id: number | string, item: NewCartItem) =>
      request<unknown>(`/storefront/cart/${id}/items`, {
        method: 'POST', body: JSON.stringify(item),
      }),
  },
  checkout: {
    place: (id: number | string, body: PlaceOrderBody) =>
      request<unknown>(`/storefront/checkout/${id}`, {
        method: 'POST', body: JSON.stringify(body),
      }),
  },
};

// --- types -----------------------------------------------------------------

export type Product = {
  id: string;
  name: string;
  slug: string;
  description?: string | null;
  requires_design: boolean;
  allows_pdf_upload: boolean;
};

export type OptionValue = {
  code: string;
  label: string;
  value?: string | null;
  metadata?: Record<string, unknown>;
};

export type ConfiguratorOption = {
  code: string;
  label: string;
  type: string;
  required: boolean;
  help_text?: string | null;
  values: OptionValue[];
};

export type ConfiguratorPayload = {
  product: Product;
  options: ConfiguratorOption[];
  defaults: Record<string, string>;
};

export type ValidationResult = {
  valid: boolean;
  errors: { code: string; message: string }[];
  disabled_options: Record<string, string[]>;
};

export type PriceResult = {
  valid: boolean;
  currency: string;
  net_price: number;
  tax: number;
  gross_price: number;
  breakdown: { label: string; amount: number }[];
  errors?: { code: string; message: string }[];
};

export type NewCartItem = {
  product_id: string;
  design_id?: string | null;
  configuration_json: Record<string, unknown>;
  price_json: PriceResult;
  quantity: number;
};

export type CartResponse = {
  id: number;
  user_id: string | null;
  items: {
    id: number;
    product_id: string;
    name: string;
    quantity: number;
    unit_price: number;
    total: number;
    configuration: Record<string, unknown>;
  }[];
  totals: { gross: number; currency: string };
};

export type PlaceOrderBody = {
  shipping_address?: Record<string, string>;
  billing_address?: Record<string, string>;
  payment_method?: 'stripe' | 'paypal' | 'bank_transfer' | 'manual_invoice';
};
