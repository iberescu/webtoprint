import { useEffect, useMemo, useState } from 'react';
import { api, type ConfiguratorPayload, type PriceResult, type ValidationResult } from '~/lib/api';

type Props = { slug: string };

export default function ProductConfigurator({ slug }: Props) {
  const [data, setData] = useState<ConfiguratorPayload | null>(null);
  const [config, setConfig] = useState<Record<string, string>>({});
  const [validation, setValidation] = useState<ValidationResult | null>(null);
  const [price, setPrice] = useState<PriceResult | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [adding, setAdding] = useState(false);

  useEffect(() => {
    api.products.configurator(slug).then((p) => {
      setData(p);
      setConfig(p.defaults);
    }).catch((e) => setError(String(e)));
  }, [slug]);

  useEffect(() => {
    if (!data) return;
    let cancelled = false;
    setBusy(true);
    Promise.all([
      api.products.validate(slug, config).catch((e) => e?.payload ?? null) as Promise<ValidationResult | null>,
      api.products.price(slug, { ...config, quantity: Number(config.quantity ?? 1) }).catch((e) => e?.payload ?? null),
    ]).then(([v, p]) => {
      if (cancelled) return;
      setValidation(v);
      setPrice(p as PriceResult);
    }).finally(() => !cancelled && setBusy(false));
    return () => { cancelled = true; };
  }, [slug, data, JSON.stringify(config)]);

  const disabled = useMemo(() => validation?.disabled_options ?? {}, [validation]);

  if (error) return <div class="text-red-600">Couldn't load product: {error}</div>;
  if (!data) return <div class="animate-pulse text-slate-400">Loading configurator…</div>;

  const setOption = (code: string, value: string) =>
    setConfig((prev) => ({ ...prev, [code]: value }));

  // If the user came back from the designer, the URL carries ?design=<id>.
  // Attach it to the cart item so the production job has the artwork.
  const designId = typeof window !== 'undefined'
    ? new URLSearchParams(window.location.search).get('design')
    : null;

  const addToCart = async () => {
    if (!validation?.valid || !price?.valid) return;
    setAdding(true);
    try {
      let cartId = localStorage.getItem('cart_id');
      if (!cartId) {
        const cart = await api.cart.create();
        cartId = String(cart.id);
        localStorage.setItem('cart_id', cartId);
      }
      await api.cart.addItem(cartId, {
        product_id: data.product.id,
        design_id: designId ?? null,
        configuration_json: config,
        price_json: price,
        quantity: Number(config.quantity ?? 1),
      });
      window.location.href = '/cart';
    } finally {
      setAdding(false);
    }
  };

  const qty = Number(config.quantity ?? 1);
  const unit = price?.valid ? price.gross_price / qty : 0;

  return (
    <div class="space-y-5">
      {designId && (
        <div class="flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
          <div class="flex items-center gap-2">
            <span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-500 text-white">✓</span>
            <div>
              <div class="font-semibold">Design attached</div>
              <div class="text-xs text-emerald-700">Design <span class="font-mono">#{designId.slice(0, 8)}</span> from the editor will be printed on this order.</div>
            </div>
          </div>
          <a href={`http://localhost:5173/?design=${designId}&product=${data.product.slug}`} class="text-xs font-semibold text-emerald-700 underline hover:text-emerald-900">Edit design</a>
        </div>
      )}
      {data.options.map((opt) => (
        <div key={opt.code}>
          <label class="mb-2 flex items-center justify-between text-sm font-semibold text-slate-700">
            <span>
              {opt.label}
              {opt.required ? <span class="text-rose-500"> *</span> : null}
            </span>
            {config[opt.code] && (
              <span class="text-xs font-normal text-slate-500">
                {opt.values.find((v) => v.code === config[opt.code])?.label}
              </span>
            )}
          </label>
          <div class="flex flex-wrap gap-2">
            {opt.values.map((v) => {
              const isDisabled = disabled[opt.code]?.includes(v.code);
              const isSelected = config[opt.code] === v.code;
              return (
                <button
                  key={v.code}
                  type="button"
                  disabled={isDisabled}
                  onClick={() => setOption(opt.code, v.code)}
                  class={[
                    'rounded-lg border px-3 py-2 text-sm transition',
                    isSelected
                      ? 'border-brand-500 bg-brand-50 font-semibold text-brand-700 ring-1 ring-brand-500'
                      : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400',
                    isDisabled ? 'cursor-not-allowed opacity-40 line-through hover:border-slate-300' : '',
                  ].join(' ')}
                >
                  {v.label}
                </button>
              );
            })}
          </div>
          {opt.help_text && <p class="mt-1 text-xs text-slate-500">{opt.help_text}</p>}
        </div>
      ))}

      {validation && !validation.valid && (
        <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
          {validation.errors.map((e, i) => <div key={i}>⚠️ {e.message}</div>)}
        </div>
      )}

      <div class="rounded-xl bg-brand-50 p-5">
        {busy && <div class="text-sm text-slate-500">Updating price…</div>}
        {price?.valid ? (
          <>
            <div class="flex items-baseline justify-between">
              <div>
                <div class="text-xs uppercase tracking-widest text-slate-500">Total ({qty} pieces)</div>
                <div class="mt-1 text-3xl font-extrabold tracking-tight text-brand-900">
                  €{price.gross_price.toFixed(2)}
                </div>
                <div class="text-xs text-slate-500">
                  €{unit.toFixed(3)} per piece · {price.note ?? `incl. €${price.tax.toFixed(2)} VAT`}
                </div>
              </div>
              {price.breakdown.some((b) => b.label.includes('discount')) && (
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                  Discount applied
                </span>
              )}
            </div>

            <details class="mt-4 text-sm">
              <summary class="cursor-pointer font-medium text-slate-700">See price breakdown</summary>
              <ul class="mt-2 space-y-1 text-slate-600">
                {price.breakdown.map((b, i) => (
                  <li key={i} class="flex justify-between">
                    <span>{b.label}</span>
                    <span class="font-mono">€{b.amount.toFixed(2)}</span>
                  </li>
                ))}
                <li class="flex justify-between border-t border-slate-200 pt-1 text-slate-700">
                  <span>Net</span><span class="font-mono">€{price.net_price.toFixed(2)}</span>
                </li>
                <li class="flex justify-between text-slate-600">
                  <span>VAT</span><span class="font-mono">€{price.tax.toFixed(2)}</span>
                </li>
                <li class="flex justify-between font-semibold text-brand-900">
                  <span>Gross total</span><span class="font-mono">€{price.gross_price.toFixed(2)}</span>
                </li>
              </ul>
            </details>
          </>
        ) : price ? (
          <div class="text-sm text-rose-700">
            {price.errors?.[0]?.message ?? 'Price unavailable for this configuration.'}
          </div>
        ) : null}

        <button
          type="button"
          disabled={!validation?.valid || !price?.valid || adding}
          onClick={addToCart}
          class="mt-5 w-full rounded-lg bg-brand-500 px-4 py-3 font-bold text-white shadow-brand transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
        >
          {adding ? 'Adding to cart…' : 'Add to cart →'}
        </button>

        <div class="mt-3 grid grid-cols-2 gap-2 text-center text-xs text-slate-500">
          <div>🚚 Ships in 24h</div>
          <div>↩️ Reprint guarantee</div>
        </div>
      </div>
    </div>
  );
}
