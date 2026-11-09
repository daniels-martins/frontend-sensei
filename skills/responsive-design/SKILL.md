# Responsive Design

You are a responsive design expert. Build interfaces that work beautifully across all screen sizes — from 320px phones to 4K monitors — using modern techniques.

## Mobile-First Approach

Start with the smallest screen and progressively enhance. Mobile-first produces cleaner CSS because you add complexity, not undo it.

```css
/* Base: mobile (no media query needed) */
.grid { display: flex; flex-direction: column; gap: 1rem; }

/* Tablet: enhance to 2 columns */
@media (min-width: 768px) {
  .grid { flex-direction: row; flex-wrap: wrap; }
  .grid > * { flex: 1 1 calc(50% - 0.5rem); }
}

/* Desktop: 3 columns */
@media (min-width: 1024px) {
  .grid > * { flex: 1 1 calc(33.333% - 0.67rem); }
}
```

## Breakpoint Strategy

| Name | Width | Target |
|---|---|---|
| xs | < 640px | Small phones |
| sm | 640px | Landscape phones |
| md | 768px | Tablets |
| lg | 1024px | Laptops |
| xl | 1280px | Desktops |
| 2xl | 1536px | Large monitors |

Don't design for devices — design for content. Add breakpoints where your layout breaks, not at arbitrary device widths.

## Fluid Design (No Breakpoints Needed)

### CSS Grid Auto-Fit

```css
/* Cards that auto-fit to available space */
.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(280px, 100%), 1fr));
  gap: 1.5rem;
}
```

### Fluid Typography (clamp)

```css
/* Scales smoothly between viewport sizes */
h1 { font-size: clamp(1.75rem, 1rem + 2.5vw, 3.5rem); }
h2 { font-size: clamp(1.5rem, 0.8rem + 2vw, 2.5rem); }
body { font-size: clamp(1rem, 0.34vw + 0.91rem, 1.19rem); }

/* Fluid spacing */
.section {
  padding-block: clamp(2rem, 5vw, 6rem);
  padding-inline: clamp(1rem, 3vw, 4rem);
}
```

### Container Queries

```css
/* Respond to container size, not viewport */
.card-container {
  container-type: inline-size;
}

@container (min-width: 400px) {
  .card { display: grid; grid-template-columns: 150px 1fr; }
}

@container (max-width: 399px) {
  .card { display: flex; flex-direction: column; }
  .card img { aspect-ratio: 16/9; object-fit: cover; }
}
```

## Images & Media

```html
<!-- Responsive images with srcset -->
<img
  src="photo-800.jpg"
  srcset="photo-400.jpg 400w, photo-800.jpg 800w, photo-1200.jpg 1200w"
  sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
  alt="Description"
  loading="lazy"
  decoding="async"
/>

<!-- Art direction with picture -->
<picture>
  <source media="(min-width: 1024px)" srcset="hero-wide.jpg" />
  <source media="(min-width: 640px)" srcset="hero-medium.jpg" />
  <img src="hero-mobile.jpg" alt="Hero image" />
</picture>
```

```css
/* Responsive video/embed container */
.video-container {
  aspect-ratio: 16 / 9;
  width: 100%;
}
.video-container iframe {
  width: 100%;
  height: 100%;
}

/* Responsive images */
img {
  max-width: 100%;
  height: auto;
  display: block;
}
```

## Touch Considerations

```css
/* Minimum touch target: 44x44px (WCAG) */
.button, .link, .interactive {
  min-height: 44px;
  min-width: 44px;
  padding: 0.75rem 1rem;
}

/* Increase spacing between touch targets */
@media (pointer: coarse) {
  .nav-links { gap: 0.5rem; }
  .nav-link { padding: 0.75rem 1rem; }
}

/* Hover only on devices that support it */
@media (hover: hover) {
  .card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
}
```

## Navigation Patterns

### Mobile Navigation

```html
<!-- Hamburger menu for mobile -->
<nav>
  <button class="menu-toggle" aria-expanded="false" aria-controls="nav-menu">
    <span class="sr-only">Menu</span>
    <!-- Hamburger icon -->
  </button>
  <ul id="nav-menu" class="nav-links">
    <li><a href="/">Home</a></li>
    <li><a href="/about">About</a></li>
  </ul>
</nav>
```

```css
.nav-links {
  display: none; /* Hidden on mobile */
}
.nav-links.is-open {
  display: flex;
  flex-direction: column;
  position: fixed;
  inset: 0;
  background: var(--color-surface);
  z-index: 50;
}

@media (min-width: 768px) {
  .menu-toggle { display: none; }
  .nav-links {
    display: flex !important; /* Always visible on desktop */
    flex-direction: row;
    position: static;
  }
}
```

## Common Layout Patterns

### Holy Grail (Header + Sidebar + Main + Footer)

```css
.layout {
  display: grid;
  grid-template-rows: auto 1fr auto;
  min-height: 100dvh;
}

@media (min-width: 768px) {
  .layout {
    grid-template-columns: 250px 1fr;
    grid-template-rows: auto 1fr auto;
    grid-template-areas:
      "header header"
      "sidebar main"
      "footer footer";
  }
}
```

### Sticky Footer

```css
body {
  display: grid;
  grid-template-rows: auto 1fr auto;
  min-height: 100dvh;
}
```

### Sidebar That Collapses

```css
.sidebar-layout {
  display: grid;
  grid-template-columns: 1fr;
}

@media (min-width: 1024px) {
  .sidebar-layout {
    grid-template-columns: 280px 1fr;
  }
}
```

## Viewport Units

```css
/* dvh accounts for mobile browser chrome */
.hero { min-height: 100dvh; }

/* svh = smallest viewport height (browser chrome visible) */
.modal { max-height: 100svh; }

/* lvh = largest viewport height (browser chrome hidden) */
```

## Testing Checklist

1. Test at 320px, 375px, 768px, 1024px, 1440px widths.
2. Rotate to landscape on mobile/tablet.
3. Zoom to 200% — nothing should overflow or overlap.
4. Test with different font sizes (browser settings).
5. Check touch targets on mobile (44x44px minimum).
6. Verify images don't overflow containers.
7. Test navigation at each breakpoint.
8. Check text readability (line length 45-75 characters).
9. Verify no horizontal scroll at any viewport.

## Anti-Patterns to Flag

- Fixed widths (`width: 960px`) instead of fluid layouts.
- Desktop-first CSS (overriding complex styles for mobile).
- `vh` instead of `dvh` (broken on mobile Safari).
- Text too small on mobile (< 16px body text).
- Touch targets smaller than 44x44px.
- Horizontal scroll at any viewport width.
- Images without `max-width: 100%`.
- Using `px` for all media queries (use `em` for zoom resilience).
- Hiding critical content on mobile (`display: none` on important info).
