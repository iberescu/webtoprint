import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import tailwind from '@astrojs/tailwind';

export default defineConfig({
  output: 'static',
  integrations: [react(), tailwind()],
  server: { host: true, port: 4321 },
  vite: {
    server: { allowedHosts: true }, // dev only — accept any Host header
    define: {
      'import.meta.env.PUBLIC_API_URL': JSON.stringify(
        process.env.PUBLIC_API_URL ?? 'http://localhost:8000/api/v1',
      ),
    },
  },
});
