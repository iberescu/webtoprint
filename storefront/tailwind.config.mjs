/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{astro,html,js,jsx,ts,tsx,md,mdx}'],
  // Backend metadata stores class names like "from-amber-300 to-orange-500".
  // Safelist them so Tailwind doesn't tree-shake them out.
  safelist: [
    {
      pattern: /(from|to)-(amber|orange|rose|sky|pink|slate|cyan|indigo|emerald|stone|yellow|teal|zinc|purple)-(300|400|500|600|700)/,
    },
  ],
  theme: { extend: {} },
  plugins: [],
};
