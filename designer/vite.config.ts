import { defineConfig } from 'vite';

// When served behind the public ngrok aggregator nginx, the designer is
// mounted at /designer/. Setting `base` makes Vite emit every asset URL
// with that prefix, and the HMR client uses it too. For direct localhost
// access (no aggregator), pass DESIGNER_BASE=/ when running.
const base = process.env.DESIGNER_BASE ?? '/designer/';

// The storefront-hosted template images at /images/templates/*.jpg are the
// source of truth for the designer's "real image" templates. When the
// designer is loaded behind the aggregator (localhost:8080 or ngrok) they
// resolve naturally through the same origin. When it's hit directly on
// localhost:5173 (Vite dev server), this proxy forwards image requests to
// the storefront so they work there too.
//
// This MUST be the docker-internal hostname (`storefront:4321`), not
// VITE_STOREFRONT_URL — that env var is the public-facing URL (ngrok or
// localhost:8080) and would loop the request back through ngrok edge.
const STOREFRONT_INTERNAL = process.env.INTERNAL_STOREFRONT_URL ?? 'http://storefront:4321';

export default defineConfig({
  base,
  server: {
    host: true,
    port: 5173,
    allowedHosts: true,
    hmr: process.env.PUBLIC_HOST
      ? { protocol: 'wss', host: process.env.PUBLIC_HOST, clientPort: 443 }
      : true,
    proxy: {
      '/images': { target: STOREFRONT_INTERNAL, changeOrigin: true },
    },
  },
  build: { outDir: 'dist' },
});
