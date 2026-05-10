import { useEffect, useState } from 'react';

type Consent = {
  essential: true;
  analytics: boolean;
  marketing: boolean;
  decided_at: string;
};

const STORAGE_KEY = 'cookie_consent_v1';
const TTL_MS = 365 * 24 * 60 * 60 * 1000;

function loadStored(): Consent | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as Consent;
    if (Date.now() - new Date(parsed.decided_at).getTime() > TTL_MS) return null;
    return parsed;
  } catch { return null; }
}

function publish(c: Consent) {
  (window as any).__consent = { analytics: c.analytics, marketing: c.marketing };
  document.documentElement.dataset.consentAnalytics = String(c.analytics);
  document.documentElement.dataset.consentMarketing = String(c.marketing);
}

export default function CookieConsent() {
  const [open, setOpen] = useState(false);
  const [showDetails, setShowDetails] = useState(false);
  const [analytics, setAnalytics] = useState(false);
  const [marketing, setMarketing] = useState(false);

  useEffect(() => {
    const stored = loadStored();
    if (stored) { publish(stored); return; }
    setOpen(true);
  }, []);

  const persist = (a: boolean, m: boolean) => {
    const c: Consent = { essential: true, analytics: a, marketing: m, decided_at: new Date().toISOString() };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(c));
    publish(c);
    setOpen(false);
  };

  if (!open) return null;

  return (
    <div
      role="dialog"
      aria-labelledby="cookie-title"
      aria-describedby="cookie-desc"
      class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white shadow-xl"
    >
      <div class="mx-auto max-w-7xl px-4 py-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div class="flex-1">
            <h2 id="cookie-title" class="text-base font-bold text-brand-900">
              We value your privacy
            </h2>
            <p id="cookie-desc" class="mt-1 text-sm text-slate-600">
              We use cookies to make this site work, to remember your basket, and — only if you let
              us — to understand how the site is used and to show you relevant content. You can
              change your choice anytime in our{' '}
              <a href="/legal/cookies" class="font-medium text-brand-500 underline">cookie policy</a>.
            </p>

            {showDetails && (
              <div class="mt-4 grid gap-2 rounded border border-slate-200 bg-slate-50 p-3 text-sm sm:grid-cols-3">
                <label class="flex items-start gap-2 opacity-60">
                  <input type="checkbox" checked disabled class="mt-1" />
                  <span>
                    <span class="block font-medium">Essential</span>
                    <span class="text-xs text-slate-500">Required for the cart, checkout and login. Always on.</span>
                  </span>
                </label>
                <label class="flex items-start gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={analytics}
                    onChange={(e) => setAnalytics((e.target as HTMLInputElement).checked)}
                    class="mt-1"
                  />
                  <span>
                    <span class="block font-medium">Analytics</span>
                    <span class="text-xs text-slate-500">Anonymous traffic stats.</span>
                  </span>
                </label>
                <label class="flex items-start gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={marketing}
                    onChange={(e) => setMarketing((e.target as HTMLInputElement).checked)}
                    class="mt-1"
                  />
                  <span>
                    <span class="block font-medium">Marketing</span>
                    <span class="text-xs text-slate-500">Re-targeting and personalised offers.</span>
                  </span>
                </label>
              </div>
            )}
          </div>

          <div class="flex flex-wrap gap-2 md:flex-col md:items-stretch md:gap-2">
            <button
              type="button"
              onClick={() => persist(true, true)}
              class="rounded bg-brand-500 px-4 py-2 text-sm font-bold text-white hover:bg-brand-600"
            >
              Accept all
            </button>
            <button
              type="button"
              onClick={() => persist(false, false)}
              class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              Essential only
            </button>
            {!showDetails ? (
              <button
                type="button"
                onClick={() => setShowDetails(true)}
                class="text-sm font-medium text-brand-500 hover:underline"
              >
                Customize…
              </button>
            ) : (
              <button
                type="button"
                onClick={() => persist(analytics, marketing)}
                class="rounded border border-brand-500 px-4 py-2 text-sm font-bold text-brand-500 hover:bg-brand-50"
              >
                Save preferences
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
