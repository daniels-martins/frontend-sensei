# Design Systems

You are a design systems expert. Build and maintain scalable, consistent component libraries with clear design tokens, documentation, and governance.

## Design Tokens

Design tokens are the single source of truth for visual design decisions. They bridge design tools and code.

### Token Hierarchy

```
Global Tokens → Alias Tokens → Component Tokens

Global:   --color-blue-500: #3b82f6
Alias:    --color-primary: var(--color-blue-500)
Component: --button-bg: var(--color-primary)
```

### Token Categories

```css
:root {
  /* === Colors === */
  /* Primitives (palette) */
  --color-gray-50: oklch(0.985 0 0);
  --color-gray-100: oklch(0.967 0 0);
  --color-gray-900: oklch(0.21 0 0);
  --color-blue-500: oklch(0.623 0.214 259);

  /* Semantic (purpose) */
  --color-text-primary: var(--color-gray-900);
  --color-text-secondary: var(--color-gray-600);
  --color-bg-primary: var(--color-gray-50);
  --color-bg-secondary: var(--color-gray-100);
  --color-border: var(--color-gray-200);
  --color-interactive: var(--color-blue-500);
  --color-interactive-hover: var(--color-blue-600);
  --color-error: var(--color-red-500);
  --color-success: var(--color-green-500);
  --color-warning: var(--color-yellow-500);

  /* === Spacing === */
  --space-1: 0.25rem;  /* 4px */
  --space-2: 0.5rem;   /* 8px */
  --space-3: 0.75rem;  /* 12px */
  --space-4: 1rem;     /* 16px */
  --space-6: 1.5rem;   /* 24px */
  --space-8: 2rem;     /* 32px */
  --space-12: 3rem;    /* 48px */
  --space-16: 4rem;    /* 64px */

  /* === Typography === */
  --font-family-sans: 'Inter', system-ui, sans-serif;
  --font-family-mono: 'JetBrains Mono', ui-monospace, monospace;

  --font-size-xs: 0.75rem;   /* 12px */
  --font-size-sm: 0.875rem;  /* 14px */
  --font-size-base: 1rem;    /* 16px */
  --font-size-lg: 1.125rem;  /* 18px */
  --font-size-xl: 1.25rem;   /* 20px */
  --font-size-2xl: 1.5rem;   /* 24px */
  --font-size-3xl: 1.875rem; /* 30px */

  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;

  --line-height-tight: 1.25;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.75;

  /* === Elevation === */
  --shadow-sm: 0 1px 2px oklch(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px oklch(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px oklch(0 0 0 / 0.1);
  --shadow-xl: 0 20px 25px -5px oklch(0 0 0 / 0.1);

  /* === Border Radius === */
  --radius-sm: 0.25rem;
  --radius-md: 0.375rem;
  --radius-lg: 0.5rem;
  --radius-xl: 0.75rem;
  --radius-full: 9999px;

  /* === Motion === */
  --duration-instant: 100ms;
  --duration-fast: 150ms;
  --duration-normal: 250ms;
  --duration-slow: 400ms;
  --ease-default: cubic-bezier(0.4, 0, 0.2, 1);
  --ease-in: cubic-bezier(0.4, 0, 1, 1);
  --ease-out: cubic-bezier(0, 0, 0.2, 1);

  /* === Z-Index Scale === */
  --z-dropdown: 10;
  --z-sticky: 20;
  --z-overlay: 30;
  --z-modal: 40;
  --z-popover: 50;
  --z-toast: 60;
}
```

### Dark Mode Tokens

```css
@media (prefers-color-scheme: dark) {
  :root {
    --color-text-primary: var(--color-gray-50);
    --color-text-secondary: var(--color-gray-400);
    --color-bg-primary: var(--color-gray-900);
    --color-bg-secondary: var(--color-gray-800);
    --color-border: var(--color-gray-700);
  }
}

/* Or class-based toggle */
[data-theme="dark"] {
  --color-text-primary: var(--color-gray-50);
  /* ... */
}
```

## Component API Design

### Props Should Be Explicit

```typescript
// GOOD: Clear, constrained API
interface ButtonProps {
  variant: 'primary' | 'secondary' | 'ghost' | 'danger';
  size: 'sm' | 'md' | 'lg';
  disabled?: boolean;
  loading?: boolean;
  icon?: ReactNode;
  iconPosition?: 'left' | 'right';
  children: ReactNode;
  onClick?: () => void;
}

// BAD: Ambiguous, hard to maintain
interface ButtonProps {
  type?: string;       // What values are valid?
  small?: boolean;     // What about medium? large?
  className?: string;  // Opens up style overrides
  style?: CSSProperties; // Bypass the design system
}
```

### Component Variants

```css
/* Data-attribute-driven variants (CSS) */
.button {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  border-radius: var(--radius-md);
  font-weight: var(--font-weight-medium);
  transition: all var(--duration-fast) var(--ease-default);
}

.button[data-size="sm"] { padding: var(--space-1) var(--space-3); font-size: var(--font-size-sm); }
.button[data-size="md"] { padding: var(--space-2) var(--space-4); font-size: var(--font-size-base); }
.button[data-size="lg"] { padding: var(--space-3) var(--space-6); font-size: var(--font-size-lg); }

.button[data-variant="primary"] {
  background: var(--color-interactive);
  color: white;
}
.button[data-variant="primary"]:hover {
  background: var(--color-interactive-hover);
}
```

## Component Checklist

Every component in a design system should have:

1. **Clear API**: Typed props, sensible defaults, documented variants.
2. **Accessibility**: Keyboard support, ARIA attributes, screen reader testing.
3. **Responsive**: Works from 320px to 2560px.
4. **Dark Mode**: Adapts to color scheme preference.
5. **States**: Default, hover, focus, active, disabled, loading, error.
6. **Documentation**: Usage examples, do/don't, prop table.
7. **Tests**: Unit tests for logic, visual regression for appearance.

## Naming Conventions

### Component Names
- **PascalCase**: `Button`, `DataTable`, `NavigationBar`
- Prefix with system name for scoping: `DsButton`, `AcmeCard`

### Token Names
- **kebab-case**: `--color-primary`, `--space-4`, `--font-size-lg`
- Category first: `--color-*`, `--space-*`, `--font-*`, `--shadow-*`

### CSS Class Names (BEM)
- Block: `.card`
- Element: `.card__title`, `.card__body`
- Modifier: `.card--featured`, `.card--compact`

## Icon System

```html
<!-- SVG sprite approach -->
<svg class="icon" aria-hidden="true">
  <use href="/icons.svg#check" />
</svg>

<!-- Or inline SVG component -->
<Icon name="check" size={20} color="currentColor" aria-hidden="true" />
```

- Use `currentColor` so icons inherit text color.
- Size icons with `width`/`height`, not font-size.
- Decorative icons: `aria-hidden="true"`.
- Meaningful icons: add `aria-label` or accompanying text.

## Documentation Standards

Every component page should include:
- **Overview**: What it is and when to use it.
- **Examples**: Interactive playground with all variants.
- **Props/API**: Table of all props with types and defaults.
- **Accessibility**: Keyboard behavior and ARIA notes.
- **Do/Don't**: Visual examples of correct and incorrect usage.

## Anti-Patterns to Flag

- Hardcoded values instead of tokens (`color: #3b82f6` vs `var(--color-interactive)`).
- One-off components that duplicate system components.
- `!important` overrides on design system components.
- Mixing multiple spacing scales in one project.
- Inconsistent naming (mixing camelCase and kebab-case tokens).
- Components without keyboard support.
- Missing dark mode support on new components.
- `className` or `style` prop escape hatches (break consistency).
