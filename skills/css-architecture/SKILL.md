# CSS Architecture & Best Practices

You are a CSS architecture expert. Write maintainable, performant, and scalable stylesheets using modern CSS features and proven methodologies.

## Modern CSS Layout

### Flexbox (1D layouts)

```css
/* Centering */
.center {
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Spacing children */
.stack {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Responsive row that wraps */
.cluster {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

/* Push last item to the end */
.spread {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
```

### Grid (2D layouts)

```css
/* Auto-fit responsive grid */
.auto-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(250px, 100%), 1fr));
  gap: 1.5rem;
}

/* Named areas for page layout */
.page {
  display: grid;
  grid-template-areas:
    "header header"
    "sidebar main"
    "footer footer";
  grid-template-columns: 250px 1fr;
  grid-template-rows: auto 1fr auto;
  min-height: 100dvh;
}

/* Subgrid for aligned children */
.card-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}
.card {
  display: grid;
  grid-template-rows: subgrid;
  grid-row: span 3; /* title, content, footer */
}
```

### Container Queries

```css
.card-container {
  container-type: inline-size;
  container-name: card;
}

@container card (min-width: 400px) {
  .card {
    grid-template-columns: 200px 1fr;
  }
}
```

## Custom Properties (Design Tokens)

```css
:root {
  /* Colors */
  --color-primary: oklch(0.55 0.15 250);
  --color-surface: oklch(0.98 0 0);
  --color-text: oklch(0.15 0 0);
  --color-text-muted: oklch(0.45 0 0);
  --color-border: oklch(0.85 0 0);
  --color-error: oklch(0.55 0.2 25);
  --color-success: oklch(0.55 0.15 150);

  /* Spacing */
  --space-xs: 0.25rem;
  --space-sm: 0.5rem;
  --space-md: 1rem;
  --space-lg: 1.5rem;
  --space-xl: 2rem;
  --space-2xl: 3rem;

  /* Typography */
  --font-sans: system-ui, -apple-system, sans-serif;
  --font-mono: ui-monospace, 'Cascadia Code', monospace;
  --text-sm: clamp(0.8rem, 0.17vw + 0.76rem, 0.89rem);
  --text-base: clamp(1rem, 0.34vw + 0.91rem, 1.19rem);
  --text-lg: clamp(1.25rem, 0.61vw + 1.1rem, 1.58rem);
  --text-xl: clamp(1.56rem, 1vw + 1.31rem, 2.11rem);

  /* Shadows */
  --shadow-sm: 0 1px 2px oklch(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px oklch(0 0 0 / 0.07);
  --shadow-lg: 0 10px 15px oklch(0 0 0 / 0.1);

  /* Radii */
  --radius-sm: 0.25rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;
  --radius-full: 9999px;

  /* Transitions */
  --duration-fast: 150ms;
  --duration-normal: 250ms;
  --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
}
```

## Methodologies

### BEM (Block Element Modifier)

```css
/* Block */
.card { }

/* Element (part of the block) */
.card__title { }
.card__body { }
.card__footer { }

/* Modifier (variation) */
.card--featured { }
.card--compact { }
```

### Utility-First (Tailwind approach)

When using Tailwind CSS, compose utilities in HTML. Extract components only when patterns repeat 3+ times.

```html
<button class="px-4 py-2 bg-blue-600 text-white rounded-lg
               hover:bg-blue-700 focus:outline-none focus:ring-2
               focus:ring-blue-500 focus:ring-offset-2
               transition-colors duration-150">
  Save
</button>
```

## Specificity Management

- Avoid `!important` (exception: utility classes).
- Keep specificity flat — prefer classes over IDs, element selectors.
- Layer styles with `@layer`:

```css
@layer reset, base, components, utilities;

@layer reset {
  *, *::before, *::after { box-sizing: border-box; margin: 0; }
}

@layer components {
  .btn { /* ... */ }
}

@layer utilities {
  .sr-only { /* screen reader only */ }
}
```

## Responsive Design

```css
/* Mobile-first breakpoints */
/* Base styles = mobile */

@media (min-width: 640px) { /* sm: landscape phones */ }
@media (min-width: 768px) { /* md: tablets */ }
@media (min-width: 1024px) { /* lg: laptops */ }
@media (min-width: 1280px) { /* xl: desktops */ }

/* Fluid typography */
h1 { font-size: clamp(1.75rem, 1rem + 2vw, 3rem); }

/* Fluid spacing */
.section { padding: clamp(2rem, 5vw, 6rem); }
```

## Performance

- Avoid animating `width`, `height`, `top`, `left`, `margin`, `padding` — use `transform` and `opacity`.
- Use `will-change` sparingly and only on elements about to animate.
- Minimize reflows: batch DOM reads/writes.
- Use `content-visibility: auto` for offscreen content.
- Prefer `gap` over margins for spacing in flex/grid.

## Common Anti-Patterns to Flag

- `!important` overuse (more than 1-2 in a stylesheet)
- Hardcoded colors instead of design tokens
- `px` for font sizes (use `rem` or `clamp()`)
- High z-index values (>100 suggests z-index wars)
- Deep selector nesting (>.3 levels)
- `:hover` without matching `:focus-visible` styles
- `float` for layout (use flex/grid)
- `outline: none` without replacement focus style
- `@import` in CSS (use bundler imports or `@layer`)
- Missing `prefers-reduced-motion` for animations
