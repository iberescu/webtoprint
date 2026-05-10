/**
 * Inline SVG illustrations representing each print product. Used inside the
 * gradient hero card on product tiles and the product detail page so every
 * product has visual identity without needing real photography.
 */
type Props = { icon: string; className?: string };

export default function ProductIcon({ icon, className = 'h-32 w-32 text-white/90' }: Props) {
  switch (icon) {
    case 'flyer':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <rect x="14" y="6" width="36" height="48" rx="2" fill="white" fillOpacity="0.18" />
          <path d="M20 16h24M20 22h24M20 28h16M20 36h24M20 42h18" />
        </svg>
      );
    case 'card':
    case 'card-premium':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <rect x="6" y="18" width="52" height="32" rx="3" fill="white" fillOpacity="0.18" />
          <path d="M12 28h22M12 34h28M12 40h16" />
          {icon === 'card-premium' && <path d="M44 26l4 4-4 4-4-4z" fill="currentColor" />}
        </svg>
      );
    case 'postcard':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <rect x="6" y="14" width="52" height="36" rx="2" fill="white" fillOpacity="0.18" />
          <path d="M30 22h22M30 30h22M30 38h14" />
          <rect x="10" y="22" width="14" height="18" rx="1" fill="currentColor" fillOpacity="0.3" />
        </svg>
      );
    case 'greeting':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <path d="M32 12c-4-6-14-4-14 4 0 10 14 18 14 18s14-8 14-18c0-8-10-10-14-4z" fill="white" fillOpacity="0.25" />
          <path d="M14 38h36v14H14z" fill="white" fillOpacity="0.18" />
        </svg>
      );
    case 'brochure':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <path d="M10 14l22-4 22 4v36l-22 4-22-4z" fill="white" fillOpacity="0.18" />
          <path d="M32 10v44" />
          <path d="M16 24h12M16 30h12M36 24h12M36 30h12" />
        </svg>
      );
    case 'booklet':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <path d="M10 14h22v36H10z" fill="white" fillOpacity="0.18" />
          <path d="M32 14h22v36H32z" fill="white" fillOpacity="0.10" />
          <path d="M16 22h12M16 28h10M40 22h10" />
        </svg>
      );
    case 'poster':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <rect x="14" y="8" width="36" height="48" rx="2" fill="white" fillOpacity="0.18" />
          <circle cx="32" cy="22" r="6" fill="currentColor" fillOpacity="0.4" />
          <path d="M20 36h24M20 42h20M20 48h16" />
        </svg>
      );
    case 'banner':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <rect x="20" y="8" width="24" height="40" rx="1" fill="white" fillOpacity="0.18" />
          <path d="M14 50h36" />
          <path d="M30 50v6h4v-6" />
          <path d="M26 16h12M26 22h12M26 30h8" />
        </svg>
      );
    case 'sticker':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <path d="M48 12l-2 22-22 2L12 18l8-8 28 2z" fill="white" fillOpacity="0.18" />
          <path d="M44 32l-12 12" />
          <circle cx="30" cy="26" r="6" fill="currentColor" fillOpacity="0.3" />
        </svg>
      );
    case 'letterhead':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <rect x="12" y="6" width="40" height="52" rx="2" fill="white" fillOpacity="0.18" />
          <rect x="18" y="14" width="14" height="6" fill="currentColor" fillOpacity="0.4" />
          <path d="M18 28h28M18 34h28M18 40h22M18 46h26" />
        </svg>
      );
    case 'envelope':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <rect x="6" y="16" width="52" height="32" rx="2" fill="white" fillOpacity="0.18" />
          <path d="M6 18l26 18 26-18" />
        </svg>
      );
    case 'notepad':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <rect x="12" y="10" width="40" height="48" rx="2" fill="white" fillOpacity="0.18" />
          <path d="M18 8v6M28 8v6M38 8v6M48 8v6" />
          <path d="M18 26h28M18 32h28M18 38h22M18 44h26" />
        </svg>
      );
    case 'folder':
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <path d="M6 18h20l4 4h28v28H6z" fill="white" fillOpacity="0.18" />
          <rect x="36" y="32" width="14" height="10" rx="1" fill="currentColor" fillOpacity="0.4" />
        </svg>
      );
    default:
      return (
        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" strokeWidth="2" class={className}>
          <rect x="12" y="12" width="40" height="40" rx="3" fill="white" fillOpacity="0.18" />
        </svg>
      );
  }
}
