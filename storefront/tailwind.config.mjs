/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{astro,html,js,jsx,ts,tsx,md,mdx}'],
  safelist: [
    {
      pattern: /(from|to)-(brand|amber|orange|rose|sky|pink|slate|cyan|indigo|emerald|stone|yellow|teal|zinc|purple)-(50|100|300|400|500|600|700|800|900)/,
    },
  ],
  theme: {
    extend: {
      colors: {
        // CloudLab navy from the official logo SVG (#2D5096).
        brand: {
          50:  '#eef2fb',
          100: '#d6deef',
          200: '#abbcdf',
          300: '#7e99cf',
          400: '#5478bf',
          500: '#2D5096',
          600: '#264478',
          700: '#1f3661',
          800: '#172847',
          900: '#0f1a30',
        },
        accent: {
          400: '#fbbf24',
          500: '#f59e0b',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', '"Segoe UI"', 'Roboto', '"Helvetica Neue"', 'sans-serif'],
        display: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        brand: '0 10px 25px -10px rgba(45, 80, 150, 0.35)',
      },
    },
  },
  plugins: [],
};
