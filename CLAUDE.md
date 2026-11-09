# Frontend Sensei — Claude Code Guide

## What This Is

Frontend Sensei is a Hermes Agent plugin that provides professional frontend expertise via MCP tools and skill files. It is built with Laravel and laravel/mcp.

## Project Setup (First Time)

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --no-interaction
```

## How to Test

This plugin is an MCP server — it does NOT have a web UI, routes, or pages. **Do not suggest writing unit tests to verify it works.** The primary way to test is the MCP Inspector.

### Visual Testing with MCP Inspector (Recommended)

Launch the inspector:

```sh
php artisan mcp:inspector frontend-sensei
```

This opens a browser UI (usually http://127.0.0.1:6274) where you can:

1. **Connect** to the `frontend-sensei` server using the toggle switch
2. Click the **Tools** tab to see all 6 tools
3. Select any tool, fill in the input fields, and click **Execute Tool**
4. See the JSON results immediately

### Quick CLI Test (No Browser)

Pipe JSON-RPC messages to the server via stdio:

```sh
printf '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-03-26","capabilities":{},"clientInfo":{"name":"test","version":"1.0.0"}}}\n{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"check-color-contrast-tool","arguments":{"foreground":"#333","background":"#fff"}}}\n' | php artisan mcp:start frontend-sensei
```

You should see two JSON responses — the server capabilities and the contrast check result.

### Tool Test Examples

**Check Color Contrast** — easiest to verify:
- foreground: `#333333`, background: `#ffffff` → ratio should be ~12.63, all WCAG levels pass
- foreground: `#aaaaaa`, background: `#ffffff` → ratio should be ~2.32, should fail AA

**Audit Accessibility** — pass HTML with deliberate issues:
```
<div><img src="photo.jpg"><h3>Title</h3><h1>Main</h1><div onclick="go()">Click me</div></div>
```
Should return score < 50 with critical issues for missing alt text, heading order, non-interactive element with onclick.

**Audit CSS** — pass CSS with anti-patterns:
```
.foo { font-size: 12px !important; outline: none; z-index: 9999; color: red; }
```
Should flag !important abuse, hardcoded colors, px font sizes, outline:none.

**Audit Animation** — pass code with performance issues:
```
.box { transition: all 0.5s linear; animation: move 2s infinite; } @keyframes move { to { left: 100px; } }
```
Should flag `transition: all`, `linear` easing, animating `left`, long duration, infinite without user control.

**Audit Component** — pass html + css + js for a combined score and A-F grade.

**Frontend Score** — returns quality trends from past audits stored in SQLite.

## Available MCP Tools (6)

| Tool | Input | Purpose |
|---|---|---|
| `audit-accessibility-tool` | html, context? | WCAG 2.2 HTML audit |
| `audit-css-tool` | css, context? | CSS quality analysis |
| `check-color-contrast-tool` | foreground, background, font_size?, is_bold? | WCAG contrast check |
| `audit-animation-tool` | code, type?, context? | Animation performance |
| `audit-component-tool` | html, css?, js?, context? | Full component audit (A-F grade) |
| `frontend-score-tool` | type?, days? | Quality trends over time |

## Skill Files (10)

Located in `skills/` — these are knowledge base files that Hermes Agent reads to gain frontend expertise. They are markdown reference docs, not executable code.

## Architecture

- `app/Services/FrontendAuditor.php` — core audit logic (all tools delegate here)
- `app/Mcp/Servers/FrontendSenseiServer.php` — MCP server registration
- `app/Mcp/Tools/` — 6 tool classes
- `app/Models/AuditResult.php` — stores audit history in SQLite
- `routes/ai.php` — MCP server route
- `plugin.json` + `mcp.json` — Hermes Agent plugin config
- `skills/` — 10 knowledge base skill files
