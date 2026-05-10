import { useEffect, useState } from 'react';
import { api, type CartResponse } from '~/lib/api';

export default function CartView() {
  const [cart, setCart] = useState<CartResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [placing, setPlacing] = useState(false);

  useEffect(() => {
    const id = localStorage.getItem('cart_id');
    if (!id) { setLoading(false); return; }
    api.cart.show(id).then(setCart).catch(() => setCart(null)).finally(() => setLoading(false));
  }, []);

  const checkout = async (method: 'bank_transfer' | 'manual_invoice' | 'stripe' | 'paypal') => {
    const id = localStorage.getItem('cart_id');
    if (!id) return;
    setPlacing(true);
    try {
      await api.checkout.place(id, { payment_method: method });
      localStorage.removeItem('cart_id');
      window.location.href = '/orders/success';
    } catch (e) {
      alert(`Checkout failed: ${e}`);
    } finally {
      setPlacing(false);
    }
  };

  if (loading) return <div class="text-slate-400">Loading your cart…</div>;

  if (!cart || cart.items?.length === 0) {
    return (
      <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
        <div class="text-5xl">🛒</div>
        <h2 class="mt-3 text-xl font-bold text-brand-900">Your cart is empty</h2>
        <p class="mt-1 text-slate-500">Browse our products to start your order.</p>
        <a href="/products" class="mt-5 inline-flex rounded-lg bg-brand-500 px-5 py-2.5 font-semibold text-white hover:bg-brand-600">
          Shop products
        </a>
      </div>
    );
  }

  return (
    <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
      <div>
        <ul class="divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white">
          {cart.items.map((it) => (
            <li key={it.id} class="flex items-center gap-4 p-4">
              <div class="grid h-16 w-16 flex-shrink-0 place-items-center rounded-lg bg-brand-50 text-2xl">📦</div>
              <div class="flex-1">
                <div class="font-semibold text-slate-900">{it.name}</div>
                <div class="mt-0.5 text-xs text-slate-500">
                  Qty {it.quantity} · €{it.unit_price.toFixed(3)}/piece
                </div>
              </div>
              <div class="text-right">
                <div class="font-bold text-brand-900">€{it.total.toFixed(2)}</div>
              </div>
            </li>
          ))}
        </ul>

        <a href="/products" class="mt-4 inline-flex text-sm font-semibold text-brand-500 hover:underline">
          ← Continue shopping
        </a>
      </div>

      <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm h-fit lg:sticky lg:top-24">
        <h2 class="text-xs font-bold uppercase tracking-widest text-slate-500">Order summary</h2>
        <div class="mt-4 space-y-2 text-sm text-slate-600">
          <div class="flex justify-between"><span>Subtotal (incl. VAT)</span><span class="font-mono">€{cart.totals.gross.toFixed(2)}</span></div>
          <div class="flex justify-between"><span>Shipping</span><span class="text-emerald-600 font-semibold">Free (EU)</span></div>
        </div>
        <div class="my-4 border-t border-slate-200" />
        <div class="flex items-baseline justify-between">
          <span class="text-sm font-semibold text-slate-700">Total</span>
          <span class="text-2xl font-extrabold tracking-tight text-brand-900">€{cart.totals.gross.toFixed(2)} <span class="text-xs font-normal text-slate-500">{cart.totals.currency}</span></span>
        </div>

        <div class="mt-5 space-y-2">
          <button
            disabled={placing}
            onClick={() => checkout('bank_transfer')}
            class="w-full rounded-lg bg-brand-500 px-4 py-3 font-bold text-white shadow-brand hover:bg-brand-600 disabled:opacity-50"
          >
            {placing ? 'Placing order…' : 'Checkout (bank transfer) →'}
          </button>
          <button
            disabled={placing}
            onClick={() => checkout('manual_invoice')}
            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
          >
            B2B — pay on invoice
          </button>
        </div>

        <div class="mt-4 space-y-1 text-xs text-slate-500">
          <div>🛡️ Reprint guarantee on every order</div>
          <div>🇪🇺 EU-printed · GDPR-compliant</div>
        </div>
      </aside>
    </div>
  );
}
