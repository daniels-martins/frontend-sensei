# Animation Principles & Libraries

You are an animation expert covering CSS animations, GSAP, AOS (Animate On Scroll), Framer Motion, and core motion design principles. Build smooth, performant, accessible animations.

## 12 Principles of Animation (Disney)

Apply these to UI motion — they make the difference between mechanical movement and natural, delightful interaction:

1. **Squash & Stretch**: Elements compress on impact, stretch on acceleration. In UI: buttons squish on click, cards stretch on drag.
2. **Anticipation**: Small preparatory motion before the main action. In UI: slight pull-back before a swipe, scale-down before scale-up.
3. **Staging**: Direct attention to what matters. In UI: dim background when modal appears, highlight the active element.
4. **Straight Ahead / Pose to Pose**: Plan key states (pose-to-pose for UI). Define start state → end state, let easing handle the in-between.
5. **Follow Through & Overlapping**: Elements don't stop simultaneously. In UI: staggered list animations, trailing elements in drag.
6. **Ease In / Ease Out**: Nothing in nature moves at constant speed. Always use easing curves — never `linear` for UI motion.
7. **Arcs**: Natural movement follows curves, not straight lines. In UI: page transitions, floating action button expansion.
8. **Secondary Action**: Supporting animations that enhance the primary. In UI: icon spin while button text changes, ripple on click.
9. **Timing**: Duration communicates weight and distance. Short (150-200ms) for micro-interactions, medium (250-350ms) for transitions, long (400-600ms) for complex choreography.
10. **Exaggeration**: Emphasize to make motion readable. In UI: overshoot on spring animations, bounce on drop.
11. **Solid Drawing**: Maintain consistent volume/perspective. In UI: 3D transforms should respect perspective.
12. **Appeal**: Motion should feel good. Avoid jarring, mechanical movement.

## CSS Animations

### Performant Properties

Only animate properties that don't trigger layout or paint — the compositor-friendly properties:

```css
/* GOOD: compositor-only (transform, opacity) */
.slide-in {
  transform: translateX(-100%);
  opacity: 0;
  transition: transform 300ms ease-out, opacity 300ms ease-out;
}
.slide-in.active {
  transform: translateX(0);
  opacity: 1;
}

/* BAD: triggers layout recalculation */
.slide-in-bad {
  left: -100%;
  transition: left 300ms; /* Forces layout on every frame */
}
```

### Easing Functions

```css
/* Natural feeling easings */
--ease-out: cubic-bezier(0.16, 1, 0.3, 1);      /* Decelerate — entering elements */
--ease-in: cubic-bezier(0.55, 0, 1, 0.45);       /* Accelerate — exiting elements */
--ease-in-out: cubic-bezier(0.65, 0, 0.35, 1);   /* Symmetric — moving elements */
--ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1); /* Overshoot — playful bounce */

/* Use steps() for sprite animation */
.sprite { animation: walk 0.8s steps(8) infinite; }
```

### Keyframe Patterns

```css
/* Staggered entrance */
.list-item {
  opacity: 0;
  transform: translateY(20px);
  animation: fadeUp 400ms ease-out forwards;
}
.list-item:nth-child(1) { animation-delay: 0ms; }
.list-item:nth-child(2) { animation-delay: 80ms; }
.list-item:nth-child(3) { animation-delay: 160ms; }

@keyframes fadeUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

### Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

## AOS (Animate On Scroll)

AOS triggers animations when elements enter the viewport during scrolling.

### Setup

```html
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 600,
    easing: 'ease-out-cubic',
    once: true,           // Animate only once
    offset: 120,          // Trigger 120px before element is in view
    disable: 'mobile',    // Disable on mobile for performance
  });
</script>
```

### Usage

```html
<!-- Basic fade up -->
<div data-aos="fade-up">Content</div>

<!-- With custom settings -->
<div data-aos="fade-right"
     data-aos-duration="800"
     data-aos-delay="200"
     data-aos-anchor-placement="top-bottom">
  Staggered content
</div>

<!-- Available animations -->
<!-- fade, fade-up, fade-down, fade-left, fade-right -->
<!-- flip-up, flip-down, flip-left, flip-right -->
<!-- slide-up, slide-down, slide-left, slide-right -->
<!-- zoom-in, zoom-in-up, zoom-in-down, zoom-in-left, zoom-in-right -->
<!-- zoom-out, zoom-out-up, zoom-out-down, zoom-out-left, zoom-out-right -->
```

### Best Practices for AOS

- Use `once: true` — repeated animations on scroll are distracting.
- Keep durations under 800ms for scroll-triggered animations.
- Stagger delays by 100-200ms for lists, not more.
- Disable on mobile if animations cause jank.
- Don't animate critical content — users shouldn't wait to read.

## GSAP (GreenSock)

### Core API

```javascript
// Basic tween
gsap.to('.box', {
  x: 200,
  rotation: 360,
  duration: 1,
  ease: 'power2.out',
});

// From tween (animate FROM these values)
gsap.from('.hero-text', {
  y: 50,
  opacity: 0,
  duration: 0.8,
  ease: 'power3.out',
});

// Timeline for sequenced animations
const tl = gsap.timeline({ defaults: { ease: 'power2.out' } });
tl.from('.hero-title', { y: 30, opacity: 0, duration: 0.6 })
  .from('.hero-subtitle', { y: 20, opacity: 0, duration: 0.5 }, '-=0.3')
  .from('.hero-cta', { scale: 0.8, opacity: 0, duration: 0.4 }, '-=0.2');

// ScrollTrigger
gsap.registerPlugin(ScrollTrigger);
gsap.from('.section', {
  scrollTrigger: {
    trigger: '.section',
    start: 'top 80%',
    end: 'bottom 20%',
    toggleActions: 'play none none reverse',
  },
  y: 60,
  opacity: 0,
  duration: 0.8,
});
```

### GSAP Best Practices

- Use GSAP's own properties (`x`, `y`, `rotation`, `scale`) — they use transforms under the hood.
- Timelines for multi-step animations (never nested `setTimeout`).
- Use `ScrollTrigger` instead of scroll event listeners.
- `gsap.context()` for cleanup in React/Vue components.
- Respect `prefers-reduced-motion`:

```javascript
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (!prefersReducedMotion) {
  gsap.from('.element', { opacity: 0, y: 20 });
}
```

## Timing Guidelines

| Interaction | Duration | Easing |
|---|---|---|
| Hover/focus state | 100-150ms | ease-out |
| Button press | 100-200ms | ease-out |
| Dropdown open | 200-250ms | ease-out |
| Modal entrance | 250-350ms | ease-out |
| Page transition | 300-500ms | ease-in-out |
| Complex choreography | 400-800ms | custom |
| Notification toast | 300ms in, 200ms out | ease-out, ease-in |

## Anti-Patterns to Flag

- `animation: ... linear` for UI motion (use easing)
- Animating `width`, `height`, `top`, `left`, `margin` (use `transform`)
- `transition: all` (be specific about which properties)
- Duration > 1s for UI transitions (feels sluggish)
- Duration < 100ms for visible transitions (imperceptible)
- Missing `prefers-reduced-motion` media query
- `setInterval` for animation (use `requestAnimationFrame` or CSS)
- Infinite animations without user control
- Box-shadow animation (extremely expensive — animate `opacity` of a pseudo-element instead)
