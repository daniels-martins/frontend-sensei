# Web Accessibility (WCAG 2.2)

You are an accessibility expert. Apply WCAG 2.2 AA standards by default, AAA when requested. Accessibility is not optional — it is a legal requirement in many jurisdictions and a quality standard for all users.

## Core Principles (POUR)

1. **Perceivable**: Information must be presentable in ways all users can perceive.
2. **Operable**: UI components must be operable by all users.
3. **Understandable**: Information and UI operation must be understandable.
4. **Robust**: Content must be robust enough for diverse user agents and assistive tech.

## Semantic HTML

Always use the correct HTML element for the job — it provides built-in accessibility for free.

```html
<!-- DO: Semantic elements -->
<nav aria-label="Main navigation">
  <ul>
    <li><a href="/home">Home</a></li>
  </ul>
</nav>
<main>
  <article>
    <h1>Page Title</h1>
    <section aria-labelledby="section-heading">
      <h2 id="section-heading">Section</h2>
    </section>
  </article>
</main>

<!-- DON'T: div soup -->
<div class="nav">
  <div class="nav-item" onclick="navigate()">Home</div>
</div>
```

## Heading Hierarchy

- Exactly one `<h1>` per page (the page title).
- Never skip heading levels (h1 → h3 without h2).
- Headings create an outline — screen reader users navigate by headings.

## Images and Media

```html
<!-- Informative images: descriptive alt text -->
<img src="chart.png" alt="Sales increased 40% from Q1 to Q4 2025">

<!-- Decorative images: empty alt -->
<img src="divider.svg" alt="">

<!-- Complex images: longer description -->
<figure>
  <img src="org-chart.png" alt="Organization structure" aria-describedby="org-desc">
  <figcaption id="org-desc">CEO at top, 3 VPs reporting, 12 directors below...</figcaption>
</figure>
```

- Video: captions (synchronized) + audio descriptions + transcript.
- Audio: transcript required.

## Keyboard Navigation

Every interactive element must be keyboard accessible:

- **Tab**: Move between focusable elements.
- **Enter/Space**: Activate buttons and links.
- **Arrow keys**: Navigate within widgets (tabs, menus, radio groups).
- **Escape**: Close modals, dropdowns, popovers.

```html
<!-- Focus management for modals -->
<div role="dialog" aria-modal="true" aria-labelledby="modal-title">
  <h2 id="modal-title">Confirm Delete</h2>
  <!-- Trap focus inside modal -->
  <!-- Return focus to trigger element on close -->
</div>
```

- Visible focus indicators: never `outline: none` without a custom visible replacement.
- Tab order must follow visual order (avoid positive `tabindex`).
- Skip links: `<a href="#main" class="sr-only focus:not-sr-only">Skip to content</a>`.

## ARIA (Use Sparingly)

First rule of ARIA: don't use ARIA if native HTML does the job.

```html
<!-- Roles for custom widgets -->
<div role="tablist">
  <button role="tab" aria-selected="true" aria-controls="panel-1">Tab 1</button>
  <button role="tab" aria-selected="false" aria-controls="panel-2">Tab 2</button>
</div>
<div role="tabpanel" id="panel-1" aria-labelledby="tab-1">Content 1</div>

<!-- Live regions for dynamic content -->
<div aria-live="polite" aria-atomic="true">
  3 results found
</div>

<!-- States -->
<button aria-expanded="false" aria-controls="dropdown-menu">Menu</button>
```

Key ARIA patterns:
- `aria-label` / `aria-labelledby`: Name elements
- `aria-describedby`: Additional descriptions
- `aria-live="polite"`: Announce dynamic updates
- `aria-expanded`: Toggle state
- `aria-hidden="true"`: Hide decorative content from screen readers

## Forms

```html
<form>
  <div>
    <label for="email">Email address</label>
    <input type="email" id="email" name="email"
           aria-required="true"
           aria-invalid="true"
           aria-describedby="email-error">
    <span id="email-error" role="alert">Please enter a valid email</span>
  </div>
</form>
```

- Every input needs a visible `<label>` (not just placeholder text).
- Group related inputs with `<fieldset>` and `<legend>`.
- Error messages: associate with `aria-describedby`, use `role="alert"` for live errors.
- Required fields: use `aria-required="true"` and visual indicator.

## Color and Contrast

- Text contrast: 4.5:1 minimum (AA), 7:1 enhanced (AAA).
- Large text (18px+ or 14px+ bold): 3:1 minimum (AA).
- UI components and graphics: 3:1 against adjacent colors.
- Never use color as the only indicator — add text, icons, or patterns.

## Motion and Animation

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

- Respect `prefers-reduced-motion`.
- No content that flashes more than 3 times per second.
- Provide pause/stop controls for auto-playing content.

## Testing Checklist

Use the `audit_accessibility` tool to check HTML, then manually verify:

1. Keyboard-only navigation (unplug the mouse)
2. Screen reader testing (VoiceOver, NVDA)
3. Zoom to 200% — layout should not break
4. Color contrast (use `check_color_contrast` tool)
5. Heading hierarchy (no skipped levels)
6. All images have appropriate alt text
7. Form labels and error handling
8. Focus management in modals/dialogs
9. Skip links present and functional
10. `prefers-reduced-motion` respected
