# Frontend Sensei

Professional frontend expertise plugin for [Hermes Agent](https://hermes-agent.nousresearch.com). Gives AI actual UI/UX knowledge through audit tools and curated skill files.

## What It Does

Frontend Sensei provides **6 MCP audit tools** and **10 comprehensive skill files** that turn any AI agent into a frontend expert.

### Audit Tools (MCP Server)

| Tool | Purpose |
|---|---|
| `audit_accessibility` | WCAG 2.2 HTML auditing — checks images, headings, forms, ARIA, landmarks, links, buttons |
| `audit_css` | CSS quality analysis — specificity, accessibility gaps, anti-patterns, modern CSS adoption |
| `check_color_contrast` | WCAG 2.2 contrast calculator — AA/AAA pass/fail for any color pair and text size |
| `audit_animation` | Animation performance checker — compositor properties, reduced motion, timing, best practices |
| `audit_component` | Full component audit — combines all checks with weighted scoring and quality grades (A-F) |
| `frontend_score` | Quality trends dashboard — tracks scores over time, identifies recurring issues |

### Knowledge Base (10 Skills)

| Skill | Coverage |
|---|---|
| UI Fundamentals | Visual hierarchy, Gestalt principles, color theory, typography, layout, interaction design |
| Accessibility | WCAG 2.2, semantic HTML, keyboard nav, ARIA, forms, color contrast, motion |
| CSS Architecture | Modern layout (Grid, Flexbox, Container Queries), custom properties, BEM, specificity, layers |
| Animation | Disney's 12 principles for UI, CSS animations, AOS, GSAP, timing guidelines |
| Three.js & 3D | Scene setup, materials, lighting, model loading, shaders, React Three Fiber, performance |
| React Patterns | Composition, hooks, state management, performance, data fetching, error boundaries |
| Vue Patterns | Composition API, composables, provide/inject, slots, Pinia, transitions |
| Responsive Design | Mobile-first, fluid typography, container queries, touch, viewport units |
| Performance | Core Web Vitals (LCP/INP/CLS), loading, runtime, caching, fonts, measurement |
| Design Systems | Design tokens, component API design, dark mode, naming conventions, documentation |

## Built With

- **Laravel 13** + **laravel/mcp v1** — MCP server via Artisan stdio transport
- **PHP 8.4** — DOMDocument for HTML parsing, WCAG contrast algorithms
- **SQLite** — Audit result history and quality score tracking

## Requirements

- PHP >= 8.4
- Composer

## Installation (Hermes Agent)

```bash
hermes plugins install frontend-sensei
hermes plugins enable frontend-sensei
```

After installation, set up the Laravel backend:

```bash
cd ~/.hermes/plugins/frontend-sensei
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --no-interaction --force
```

The `mcp.json` configures the MCP server automatically — Hermes runs `php artisan mcp:start frontend-sensei` in stdio mode.

## Usage

Once installed, Hermes gains audit tools and knowledge base skills automatically:

- *"Check this HTML for accessibility issues"*
- *"Is white text on #3b82f6 accessible?"*
- *"Run a full frontend audit on this component"*
- *"Show my frontend quality scores"*

## Example Tool Calls

### Check Color Contrast
```json
{
  "foreground": "#333333",
  "background": "#ffffff",
  "font_size": 16,
  "is_bold": false
}
```

### Audit HTML Accessibility
```json
{
  "html": "<img src='photo.jpg'><div onclick='click()'>Click me</div>"
}
```

### Full Component Audit
```json
{
  "html": "<button class='btn'>Save</button>",
  "css": ".btn { background: red; color: white; outline: none; }",
  "js": "document.querySelector('.btn').addEventListener('click', () => {})"
}
```

## License

MIT
