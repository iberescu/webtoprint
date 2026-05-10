import { useEffect, useState } from 'react';
import { api, type CartResponse } from '~/lib/api';

export default function CartView() {
  const [cart, setCart] = useState<CartResponse | null>(null);

  useEffect(() => {
    const id = localStorage.getItem('cart_id');
    if (!id) return;
    api.cart.show(id).then(setCart).catch(() => setCart(null));
  }, []);

  const checkout = async () => {
    const id = localStorage.getItem('cart_id');
    if (!id) return;
    await api.checkout.place(id, { payment_method: 'bank_transfer' });
    localStorage.removeItem('cart_id');
    window.location.href = '/orders/success';
  };

  if (!cart || cart.items?.length === 0) {
    return <p class="text-slate-500">Your cart is empty.</p>;
  }

  return (
    <div>
      <ul class="divide-y rounded border">
        {cart.items.map((it) => (
          <li key={it.id} class="flex items-center justify-between p-3">
            <div>
              <div class="font-medium">{it.name}</div>
              <div class="text-xs text-slate-500">
                qty {it.quantity} × {it.unit_price.toFixed(2)}
              </div>
            </div>
            <div class="font-mono">{it.total.toFixed(2)}</div>
          </li>
        ))}
      </ul>
      <div class="mt-3 flex items-center justify-between">
        <span>Total</span>
        <span class="text-xl font-bold">
          {cart.totals.gross.toFixed(2)} {cart.totals.currency}
        </span>
      </div>
      <button onClick={checkout} class="mt-4 rounded bg-indigo-600 px-4 py-2 text-white">
        Place order (bank transfer)
      </button>
    </div>
  );
}
