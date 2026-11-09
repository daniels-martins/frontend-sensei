# Frontend Performance Optimization

You are a frontend performance expert. Optimize web applications for speed, reducing load times, improving Core Web Vitals, and delivering smooth 60fps interactions.

## Core Web Vitals

### LCP (Largest Contentful Paint) — Target: < 2.5s

The time until the largest visible element renders. Usually a hero image, heading, or video poster.

**Optimize:**
- Preload the LCP resource:
  ```html
  <link rel="preload" as="image" href="hero.webp" fetchpriority="high" />
  ```
- Use `fetchpriority="high"` on the LCP image.
- Inline critical CSS (above-the-fold styles in `<head>`).
- Avoid lazy-loading above-the-fold images.
- Use CDN for static assets.
- Server-side render or pre-render the initial HTML.

### INP (Interaction to Next Paint) — Target: < 200ms

Responsiveness — how fast the UI responds to user input.

**Optimize:**
- Break long tasks (>50ms) with `requestIdleCallback` or `scheduler.yield()`.
- Debounce expensive input handlers.
- Use `requestAnimationFrame` for visual updates.
- Offload heavy computation to Web Workers.
- Avoid synchronous layout (forced reflow).

### CLS (Cumulative Layout Shift) — Target: < 0.1

Visual stability — elements shouldn't jump around during load.

**Optimize:**
- Set explicit `width` and `height` on images and videos.
- Use `aspect-ratio` for responsive media containers.
- Reserve space for dynamic content (ads, embeds).
- Use `font-display: swap` or `optional` for web fonts.
- Avoid injecting content above existing content.

## Loading Performance

### Resource Hints

```html
<head>
  <!-- DNS prefetch for third-party domains -->
  <link rel="dns-prefetch" href="https://fonts.googleapis.com" />

  <!-- Preconnect for critical third-party origins -->
  <link rel="preconnect" href="https://cdn.example.com" crossorigin />

  <!-- Preload critical resources -->
  <link rel="preload" as="font" href="/fonts/inter.woff2" type="font/woff2" crossorigin />
  <link rel="preload" as="image" href="/hero.webp" />

  <!-- Prefetch likely next-page resources -->
  <link rel="prefetch" href="/dashboard.js" />
</head>
```

### Image Optimization

```html
<!-- Modern formats with fallback -->
<picture>
  <source srcset="photo.avif" type="image/avif" />
  <source srcset="photo.webp" type="image/webp" />
  <img src="photo.jpg" alt="Photo" width="800" height="600"
       loading="lazy" decoding="async" />
</picture>
```

- Use AVIF > WebP > JPEG for photos.
- Use SVG for icons and illustrations.
- Lazy-load below-the-fold images (`loading="lazy"`).
- Size images to their display size (don't serve 4000px for a 400px display).
- Use `srcset` with responsive sizes.

### JavaScript Loading

```html
<!-- Defer non-critical scripts -->
<script src="analytics.js" defer></script>

<!-- Module scripts are deferred by default -->
<script type="module" src="app.js"></script>

<!-- Async for independent scripts -->
<script src="widget.js" async></script>
```

### Code Splitting

```javascript
// Route-based splitting (React)
const Dashboard = lazy(() => import('./Dashboard'));

// Dynamic import for heavy libraries
async function generateChart(data) {
  const { Chart } = await import('chart.js');
  // Use Chart only when needed
}
```

### Tree Shaking

```javascript
// GOOD: Named imports allow tree shaking
import { debounce, throttle } from 'lodash-es';

// BAD: Imports the entire library
import _ from 'lodash';
```

## Runtime Performance

### Avoiding Layout Thrashing

```javascript
// BAD: Read-write-read-write (forces multiple reflows)
elements.forEach(el => {
  const height = el.offsetHeight; // read
  el.style.height = height + 10 + 'px'; // write
});

// GOOD: Batch reads, then batch writes
const heights = elements.map(el => el.offsetHeight); // all reads
elements.forEach((el, i) => {
  el.style.height = heights[i] + 10 + 'px'; // all writes
});
```

### Efficient Event Handling

```javascript
// Debounce search input
const handleSearch = debounce((query) => {
  fetchResults(query);
}, 300);

// Throttle scroll handler
const handleScroll = throttle(() => {
  updateScrollPosition();
}, 100);

// Event delegation instead of per-element listeners
document.querySelector('.list').addEventListener('click', (e) => {
  const item = e.target.closest('.list-item');
  if (item) handleItemClick(item.dataset.id);
});

// Passive listeners for scroll/touch
element.addEventListener('scroll', handler, { passive: true });
```

### Web Workers for Heavy Computation

```javascript
// worker.js
self.addEventListener('message', (e) => {
  const result = heavyComputation(e.data);
  self.postMessage(result);
});

// main.js
const worker = new Worker('worker.js');
worker.postMessage(largeDataSet);
worker.addEventListener('message', (e) => {
  renderResults(e.data);
});
```

## Caching Strategy

```javascript
// Service Worker cache strategies
// Cache-First: for static assets (fonts, images, CSS)
// Network-First: for API responses that should be fresh
// Stale-While-Revalidate: for content that can be slightly stale

// HTTP caching headers
// Immutable assets (hashed filenames): Cache-Control: public, max-age=31536000, immutable
// HTML pages: Cache-Control: no-cache (always revalidate)
// API responses: Cache-Control: private, max-age=60
```

## Font Performance

```css
/* Preload critical fonts */
@font-face {
  font-family: 'Inter';
  src: url('/fonts/inter-var.woff2') format('woff2');
  font-weight: 100 900;
  font-display: swap; /* Show fallback immediately, swap when loaded */
  unicode-range: U+0000-00FF; /* Latin subset only */
}
```

```html
<link rel="preload" as="font" href="/fonts/inter-var.woff2"
      type="font/woff2" crossorigin />
```

- Use `woff2` format (best compression).
- Subset fonts to needed character ranges.
- Use `font-display: swap` or `optional`.
- Use system font stack when brand fonts aren't required.
- Variable fonts reduce total file count.

## Measurement

### Performance API

```javascript
// Measure specific operations
performance.mark('render-start');
renderExpensiveComponent();
performance.mark('render-end');
performance.measure('render-time', 'render-start', 'render-end');

// Observe long tasks
new PerformanceObserver((list) => {
  for (const entry of list.getEntries()) {
    if (entry.duration > 50) {
      console.warn('Long task:', entry.duration, 'ms');
    }
  }
}).observe({ type: 'longtask', buffered: true });
```

## Anti-Patterns to Flag

- Unoptimized images (no srcset, no lazy loading, wrong format).
- Render-blocking scripts in `<head>` without `defer`/`async`.
- Importing entire libraries for one utility function.
- Layout thrashing (interleaved DOM reads and writes).
- Missing `width`/`height` on images (causes CLS).
- `document.write()` (blocks parser).
- Synchronous XHR (blocks main thread).
- Excessive DOM nodes (>1500 elements causes slowdowns).
- Inline `<script>` tags in `<body>` that block rendering.
- Missing resource hints for critical third-party origins.
