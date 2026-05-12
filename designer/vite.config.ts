import { defineConfig } from 'vite';

// When served behind the public ngrok aggregator nginx, the designer is
// mounted at /designer/. Setting `base` makes Vite emit every asset URL
// with that prefix, and the HMR client uses it too. For direct localhost
// access (no aggregator), pass DESIGNER_BASE=/ when running.
const base = process.env.DESIGNER_BASE ?? '/designer/';

export default defineConfig({
  base,
  server: {
    host: true,
    port: 5173,
    allowedHosts: true,
    // HMR over wss when behind ngrok https proxy.
    hmr: process.env.PUBLIC_HOST
      ? { protocol: 'wss', host: process.env.PUBLIC_HOST, clientPort: 443 }
      : true,
  },
  build: { outDir: 'dist' },
});
