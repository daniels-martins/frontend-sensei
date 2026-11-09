<?php

namespace App\Services;

use App\Models\AuditResult;
use DOMDocument;
use DOMXPath;

class FrontendAuditor
{
    // ─── Color name map ──────────────────────────────────────────────
    private const NAMED_COLORS = [
        'white' => [255, 255, 255], 'black' => [0, 0, 0],
        'red' => [255, 0, 0], 'green' => [0, 128, 0], 'blue' => [0, 0, 255],
        'yellow' => [255, 255, 0], 'orange' => [255, 165, 0], 'purple' => [128, 0, 128],
        'gray' => [128, 128, 128], 'grey' => [128, 128, 128],
        'navy' => [0, 0, 128], 'teal' => [0, 128, 128], 'maroon' => [128, 0, 0],
        'lime' => [0, 255, 0], 'aqua' => [0, 255, 255], 'cyan' => [0, 255, 255],
        'silver' => [192, 192, 192], 'fuchsia' => [255, 0, 255],
        'olive' => [128, 128, 0], 'coral' => [255, 127, 80],
        'salmon' => [250, 128, 114], 'tomato' => [255, 99, 71],
        'gold' => [255, 215, 0], 'indigo' => [75, 0, 130],
        'violet' => [238, 130, 238], 'pink' => [255, 192, 203],
        'brown' => [165, 42, 42], 'beige' => [245, 245, 220],
        'ivory' => [255, 255, 240], 'khaki' => [240, 230, 140],
        'lavender' => [230, 230, 250], 'plum' => [221, 160, 221],
        'wheat' => [245, 222, 179], 'tan' => [210, 180, 140],
        'crimson' => [220, 20, 60], 'turquoise' => [64, 224, 208],
        'steelblue' => [70, 130, 180], 'slategray' => [112, 128, 144],
        'darkgray' => [169, 169, 169], 'lightgray' => [211, 211, 211],
        'dimgray' => [105, 105, 105], 'whitesmoke' => [245, 245, 245],
    ];

    // ─── Accessibility Audit ─────────────────────────────────────────

    /**
     * @return array{score: int, issues: list<array{severity: string, rule: string, message: string, element: string}>}
     */
    public function auditAccessibility(string $html): array
    {
        $issues = [];
        $doc = new DOMDocument;

        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        // Images without alt
        foreach ($xpath->query('//img') as $img) {
            if (! $img->hasAttribute('alt')) {
                $issues[] = $this->issue('critical', 'img-alt', 'Image missing alt attribute. Every <img> must have an alt attribute for screen readers.', $img);
            }
        }

        // Heading hierarchy
        $headings = [];
        foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $h) {
            $level = (int) substr($h->nodeName, 1);
            $text = trim($h->textContent);

            if ($text === '') {
                $issues[] = $this->issue('warning', 'empty-heading', "Empty <{$h->nodeName}> element. Headings must have text content.", $h);
            }

            $headings[] = $level;
        }

        for ($i = 1; $i < count($headings); $i++) {
            if ($headings[$i] > $headings[$i - 1] + 1) {
                $issues[] = $this->issue('warning', 'heading-order', "Heading level skipped: h{$headings[$i - 1]} to h{$headings[$i]}. Do not skip heading levels.", null);
            }
        }

        // Form inputs without labels
        foreach ($xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button")]|//select|//textarea') as $input) {
            $id = $input->getAttribute('id');
            $hasAriaLabel = $input->hasAttribute('aria-label');
            $hasAriaLabelledby = $input->hasAttribute('aria-labelledby');
            $hasLabel = false;

            if ($id) {
                $labels = $xpath->query("//label[@for='{$id}']");
                $hasLabel = $labels->length > 0;
            }

            if (! $hasLabel && ! $hasAriaLabel && ! $hasAriaLabelledby) {
                $issues[] = $this->issue('critical', 'form-label', 'Form input missing accessible label. Use <label for="...">, aria-label, or aria-labelledby.', $input);
            }
        }

        // Links without text
        foreach ($xpath->query('//a') as $a) {
            $text = trim($a->textContent);
            $ariaLabel = $a->getAttribute('aria-label');
            $hasImg = $xpath->query('.//img[@alt]', $a)->length > 0;

            if ($text === '' && ! $ariaLabel && ! $hasImg) {
                $issues[] = $this->issue('critical', 'link-name', 'Link has no accessible text. Add text content, aria-label, or an image with alt text.', $a);
            }
        }

        // Buttons without accessible name
        foreach ($xpath->query('//button') as $btn) {
            $text = trim($btn->textContent);
            $ariaLabel = $btn->getAttribute('aria-label');

            if ($text === '' && ! $ariaLabel) {
                $issues[] = $this->issue('critical', 'button-name', 'Button has no accessible name. Add text content or aria-label.', $btn);
            }
        }

        // html lang attribute
        $htmlEl = $xpath->query('//html');
        if ($htmlEl->length > 0 && ! $htmlEl->item(0)->hasAttribute('lang')) {
            $issues[] = $this->issue('warning', 'html-lang', 'Missing lang attribute on <html> element. Screen readers need this to select the correct pronunciation.', null);
        }

        // tabindex > 0
        foreach ($xpath->query('//*[@tabindex]') as $el) {
            $val = (int) $el->getAttribute('tabindex');
            if ($val > 0) {
                $issues[] = $this->issue('warning', 'tabindex-positive', "tabindex=\"{$val}\" found. Avoid positive tabindex; it disrupts natural tab order.", $el);
            }
        }

        // Divs with onclick (missing role)
        foreach ($xpath->query('//div[@onclick]|//span[@onclick]') as $el) {
            if (! $el->hasAttribute('role')) {
                $issues[] = $this->issue('warning', 'interactive-role', 'Non-interactive element has onclick without a role attribute. Add role="button" and tabindex="0".', $el);
            }
        }

        // Auto-playing media
        foreach ($xpath->query('//video[@autoplay]|//audio[@autoplay]') as $media) {
            if (! $media->hasAttribute('controls')) {
                $issues[] = $this->issue('warning', 'media-autoplay', 'Auto-playing media without controls. Users must be able to pause or stop media.', $media);
            }
        }

        // Tables without headers
        foreach ($xpath->query('//table') as $table) {
            $ths = $xpath->query('.//th', $table);
            if ($ths->length === 0) {
                $issues[] = $this->issue('warning', 'table-header', 'Table missing <th> header cells. Data tables need headers for screen readers.', $table);
            }
        }

        // Landmark check (only for full-page-looking docs)
        $hasBody = $xpath->query('//body')->length > 0;
        if ($hasBody) {
            $hasMain = $xpath->query('//main|//*[@role="main"]')->length > 0;
            if (! $hasMain) {
                $issues[] = $this->issue('info', 'landmark-main', 'No <main> landmark found. Full pages should have a <main> element for assistive technology navigation.', null);
            }

            $hasNav = $xpath->query('//nav|//*[@role="navigation"]')->length > 0;
            if ($hasNav) {
                $hasSkip = $xpath->query('//a[contains(@class,"skip")]|//a[contains(@href,"#main")]|//a[contains(@href,"#content")]')->length > 0;
                if (! $hasSkip) {
                    $issues[] = $this->issue('info', 'skip-link', 'Navigation found but no skip link detected. Add a "Skip to content" link for keyboard users.', null);
                }
            }
        }

        // outline:none check in inline styles
        foreach ($xpath->query('//*[contains(@style,"outline")]') as $el) {
            $style = $el->getAttribute('style');
            if (preg_match('/outline\s*:\s*(none|0)\b/', $style)) {
                $issues[] = $this->issue('warning', 'outline-none', 'Inline style removes outline. Ensure a visible focus indicator is provided as replacement.', $el);
            }
        }

        return $this->scored($issues);
    }

    // ─── CSS Audit ───────────────────────────────────────────────────

    /**
     * @return array{score: int, issues: list<array{severity: string, rule: string, message: string, element: string}>}
     */
    public function auditCss(string $css): array
    {
        $issues = [];

        // !important count
        $importantCount = preg_match_all('/!important/', $css);
        if ($importantCount > 3) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'important-overuse',
                'message' => "Found {$importantCount} uses of !important. Excessive !important indicates specificity problems. Refactor selectors instead.",
                'element' => '',
            ];
        } elseif ($importantCount > 0) {
            $issues[] = [
                'severity' => 'info',
                'rule' => 'important-usage',
                'message' => "Found {$importantCount} use(s) of !important. Consider if these are truly necessary.",
                'element' => '',
            ];
        }

        // Hardcoded hex colors not using custom properties
        $hexMatches = [];
        preg_match_all('/(?<!var\()#(?:[0-9a-fA-F]{3}){1,2}\b/', $css, $hexMatches);
        $uniqueColors = array_unique($hexMatches[0] ?? []);
        if (count($uniqueColors) > 5) {
            $issues[] = [
                'severity' => 'info',
                'rule' => 'hardcoded-colors',
                'message' => 'Found '.count($uniqueColors).' hardcoded color values. Use CSS custom properties (var(--color-*)) for maintainability and theming.',
                'element' => implode(', ', array_slice($uniqueColors, 0, 6)),
            ];
        }

        // font-size using px
        $pxFonts = [];
        preg_match_all('/font-size\s*:\s*(\d+)px/', $css, $pxFonts);
        if (count($pxFonts[0]) > 0) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'font-size-px',
                'message' => 'Found '.count($pxFonts[0]).' font-size declarations using px. Use rem or em for accessible, scalable typography.',
                'element' => implode(', ', array_slice($pxFonts[0], 0, 3)),
            ];
        }

        // z-index > 100
        $zMatches = [];
        preg_match_all('/z-index\s*:\s*(\d+)/', $css, $zMatches);
        foreach ($zMatches[1] ?? [] as $z) {
            if ((int) $z > 100) {
                $issues[] = [
                    'severity' => 'warning',
                    'rule' => 'z-index-high',
                    'message' => "z-index: {$z} is excessively high. Use a z-index scale (1-10) managed through design tokens.",
                    'element' => "z-index: {$z}",
                ];
            }
        }

        // Deep selectors
        preg_match_all('/^([^{@]+)\{/m', $css, $selMatches);
        foreach ($selMatches[1] ?? [] as $sel) {
            $parts = preg_split('/\s+/', trim($sel));
            $depth = count(array_filter($parts, fn ($p) => $p !== '' && $p !== '>' && $p !== '+' && $p !== '~'));
            if ($depth > 3) {
                $issues[] = [
                    'severity' => 'warning',
                    'rule' => 'deep-selector',
                    'message' => "Selector has {$depth} levels of nesting. Keep selectors shallow (max 3) to reduce specificity and improve maintainability.",
                    'element' => mb_substr(trim($sel), 0, 80),
                ];
            }
        }

        // :hover without :focus
        $hasHover = (bool) preg_match('/:hover/', $css);
        $hasFocus = (bool) preg_match('/:focus/', $css);
        if ($hasHover && ! $hasFocus) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'hover-without-focus',
                'message' => 'Found :hover styles but no :focus styles. Always pair :hover with :focus or :focus-visible for keyboard accessibility.',
                'element' => '',
            ];
        }

        // float for layout
        $floatCount = preg_match_all('/\bfloat\s*:\s*(left|right)\b/', $css);
        if ($floatCount > 0) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'float-layout',
                'message' => "Found {$floatCount} float-based layout(s). Use flexbox or CSS grid for modern, robust layouts.",
                'element' => '',
            ];
        }

        // @import
        if (preg_match('/@import\s/', $css)) {
            $issues[] = [
                'severity' => 'info',
                'rule' => 'css-import',
                'message' => 'Using @import causes extra HTTP requests and blocks rendering. Use bundler imports or <link> tags instead.',
                'element' => '',
            ];
        }

        // outline: none without focus-visible
        if (preg_match('/outline\s*:\s*(none|0)\b/', $css) && ! preg_match('/:focus-visible/', $css)) {
            $issues[] = [
                'severity' => 'critical',
                'rule' => 'outline-removed',
                'message' => 'outline: none/0 found without :focus-visible replacement. This removes the keyboard focus indicator. Always provide a visible alternative.',
                'element' => '',
            ];
        }

        // Animations without prefers-reduced-motion
        $hasAnimation = (bool) preg_match('/animation|transition|@keyframes/', $css);
        $hasReducedMotion = (bool) preg_match('/prefers-reduced-motion/', $css);
        if ($hasAnimation && ! $hasReducedMotion) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'reduced-motion',
                'message' => 'Animations/transitions found but no prefers-reduced-motion query. Respect users who prefer reduced motion.',
                'element' => '',
            ];
        }

        // Universal selector overuse
        $universalCount = preg_match_all('/(?<![a-zA-Z])\*\s*\{/', $css);
        if ($universalCount > 1) {
            $issues[] = [
                'severity' => 'info',
                'rule' => 'universal-selector',
                'message' => "Found {$universalCount} universal (*) selectors. Excessive use impacts performance.",
                'element' => '',
            ];
        }

        return $this->scored($issues);
    }

    // ─── Color Contrast ──────────────────────────────────────────────

    /**
     * @return array{ratio: float, foreground: string, background: string, aa_normal: bool, aa_large: bool, aaa_normal: bool, aaa_large: bool, is_large_text: bool, passes_minimum: bool, recommendation: string}
     */
    public function checkContrast(string $fg, string $bg, float $fontSize = 16, bool $isBold = false): array
    {
        $fgRgb = $this->parseColor($fg);
        $bgRgb = $this->parseColor($bg);

        if (! $fgRgb || ! $bgRgb) {
            return [
                'error' => true,
                'message' => 'Could not parse one or both colors. Use hex (#fff, #ffffff), rgb(r,g,b), or a named CSS color.',
                'ratio' => 0,
                'foreground' => $fg,
                'background' => $bg,
            ];
        }

        $fgL = $this->relativeLuminance($fgRgb[0], $fgRgb[1], $fgRgb[2]);
        $bgL = $this->relativeLuminance($bgRgb[0], $bgRgb[1], $bgRgb[2]);
        $ratio = $this->contrastRatio($fgL, $bgL);

        $isLargeText = $fontSize >= 18 || ($fontSize >= 14 && $isBold);

        $aaNormal = $ratio >= 4.5;
        $aaLarge = $ratio >= 3.0;
        $aaaNormal = $ratio >= 7.0;
        $aaaLarge = $ratio >= 4.5;

        $passesMinimum = $isLargeText ? $aaLarge : $aaNormal;

        $recommendation = '';
        if (! $passesMinimum) {
            $needed = $isLargeText ? 3.0 : 4.5;
            $recommendation = "Contrast ratio {$ratio}:1 fails WCAG AA for ".($isLargeText ? 'large' : 'normal')." text (needs {$needed}:1). Darken the foreground or lighten the background.";
        } elseif (! $aaaNormal && ! $isLargeText) {
            $recommendation = "Passes AA ({$ratio}:1) but not AAA (needs 7:1). Consider increasing contrast for better readability.";
        }

        return [
            'ratio' => $ratio,
            'foreground' => $fg,
            'background' => $bg,
            'foreground_rgb' => $fgRgb,
            'background_rgb' => $bgRgb,
            'is_large_text' => $isLargeText,
            'aa_normal' => $aaNormal,
            'aa_large' => $aaLarge,
            'aaa_normal' => $aaaNormal,
            'aaa_large' => $aaaLarge,
            'passes_minimum' => $passesMinimum,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * @return array{int, int, int}|null
     */
    public function parseColor(string $color): ?array
    {
        $color = strtolower(trim($color));

        if (isset(self::NAMED_COLORS[$color])) {
            return self::NAMED_COLORS[$color];
        }

        // #RGB
        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i', $color, $m)) {
            return [
                hexdec($m[1].$m[1]),
                hexdec($m[2].$m[2]),
                hexdec($m[3].$m[3]),
            ];
        }

        // #RRGGBB
        if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $color, $m)) {
            return [hexdec($m[1]), hexdec($m[2]), hexdec($m[3])];
        }

        // rgb(r, g, b) or rgb(r g b)
        if (preg_match('/^rgba?\s*\(\s*(\d{1,3})\s*[,\s]\s*(\d{1,3})\s*[,\s]\s*(\d{1,3})/', $color, $m)) {
            return [(int) $m[1], (int) $m[2], (int) $m[3]];
        }

        return null;
    }

    public function relativeLuminance(int $r, int $g, int $b): float
    {
        $rs = $r / 255;
        $gs = $g / 255;
        $bs = $b / 255;

        $rl = $rs <= 0.04045 ? $rs / 12.92 : (($rs + 0.055) / 1.055) ** 2.4;
        $gl = $gs <= 0.04045 ? $gs / 12.92 : (($gs + 0.055) / 1.055) ** 2.4;
        $bl = $bs <= 0.04045 ? $bs / 12.92 : (($bs + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $rl + 0.7152 * $gl + 0.0722 * $bl;
    }

    public function contrastRatio(float $l1, float $l2): float
    {
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    // ─── Animation Audit ─────────────────────────────────────────────

    /**
     * @return array{score: int, issues: list<array{severity: string, rule: string, message: string, element: string}>}
     */
    public function auditAnimation(string $code, string $type = 'auto'): array
    {
        $issues = [];

        if ($type === 'auto') {
            $type = preg_match('/requestAnimationFrame|setInterval|setTimeout|\.animate\(/', $code) ? 'js' : 'css';
        }

        // Layout-triggering properties in animations
        $layoutProps = ['width', 'height', 'top', 'left', 'right', 'bottom', 'margin', 'padding'];
        foreach ($layoutProps as $prop) {
            if (preg_match('/@keyframes[^}]*\{[^}]*\b'.$prop.'\s*:/s', $code) ||
                preg_match('/transition[^;]*\b'.$prop.'\b/', $code)) {
                $issues[] = [
                    'severity' => 'critical',
                    'rule' => 'layout-animation',
                    'message' => "Animating '{$prop}' triggers layout recalculation (expensive). Use transform: translate/scale instead for 60fps performance.",
                    'element' => $prop,
                ];
            }
        }

        // box-shadow animation
        if (preg_match('/@keyframes[^}]*\{[^}]*box-shadow/s', $code) ||
            preg_match('/transition[^;]*box-shadow/', $code)) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'box-shadow-animation',
                'message' => 'Animating box-shadow is expensive (triggers paint). Use a pseudo-element with opacity for the shadow transition.',
                'element' => 'box-shadow',
            ];
        }

        // Missing prefers-reduced-motion
        $hasAnimation = (bool) preg_match('/animation|transition|@keyframes/', $code);
        if ($hasAnimation && ! preg_match('/prefers-reduced-motion/', $code)) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'reduced-motion-missing',
                'message' => 'No prefers-reduced-motion media query found. Add @media (prefers-reduced-motion: reduce) to disable or soften animations.',
                'element' => '',
            ];
        }

        // Duration checks
        preg_match_all('/(?:animation-duration|transition-duration|duration)\s*:\s*([\d.]+)(s|ms)/i', $code, $durMatches, PREG_SET_ORDER);
        // Also check shorthand
        preg_match_all('/(?:animation|transition)\s*:[^;]*?([\d.]+)(s|ms)/i', $code, $shortDurMatches, PREG_SET_ORDER);
        $allDurations = array_merge($durMatches, $shortDurMatches);

        foreach ($allDurations as $m) {
            $ms = $m[2] === 's' ? (float) $m[1] * 1000 : (float) $m[1];
            if ($ms > 1000) {
                $issues[] = [
                    'severity' => 'info',
                    'rule' => 'duration-long',
                    'message' => "Animation duration {$m[1]}{$m[2]} is long for UI transitions. Aim for 200–500ms for responsive-feeling interfaces.",
                    'element' => "{$m[1]}{$m[2]}",
                ];
            }
            if ($ms < 100 && $ms > 0) {
                $issues[] = [
                    'severity' => 'info',
                    'rule' => 'duration-short',
                    'message' => "Animation duration {$m[1]}{$m[2]} is too fast to perceive. Minimum perceptible duration is ~100ms.",
                    'element' => "{$m[1]}{$m[2]}",
                ];
            }
        }

        // JS: setInterval for animation
        if ($type === 'js' && preg_match('/setInterval/', $code)) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'setinterval-animation',
                'message' => 'Using setInterval for animation. Use requestAnimationFrame instead — it syncs with the display refresh rate and pauses in background tabs.',
                'element' => 'setInterval',
            ];
        }

        // transition: all
        if (preg_match('/transition\s*:\s*all\b/', $code) || preg_match('/transition-property\s*:\s*all\b/', $code)) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'transition-all',
                'message' => 'transition-property: all is wasteful and can cause unexpected animations. Specify exact properties (e.g. transition: opacity 200ms, transform 200ms).',
                'element' => 'transition: all',
            ];
        }

        // animation-fill-mode
        if (preg_match('/@keyframes/', $code) && ! preg_match('/animation-fill-mode|forwards|backwards|both/', $code)) {
            $issues[] = [
                'severity' => 'info',
                'rule' => 'fill-mode-missing',
                'message' => 'Keyframe animation without animation-fill-mode. Add "forwards", "backwards", or "both" to control element state before/after animation.',
                'element' => '',
            ];
        }

        // Infinite animation
        if (preg_match('/animation[^;]*infinite/', $code) && ! preg_match('/animation-play-state|paused/', $code)) {
            $issues[] = [
                'severity' => 'info',
                'rule' => 'infinite-animation',
                'message' => 'Infinite animation found. Consider providing a pause mechanism, especially for vestibular-sensitive users.',
                'element' => '',
            ];
        }

        // Static will-change
        if (preg_match('/will-change\s*:\s*(?!auto)/', $code) && ! preg_match('/\.addEventListener|:hover|\.classList/', $code)) {
            $issues[] = [
                'severity' => 'info',
                'rule' => 'static-will-change',
                'message' => 'will-change appears to be set statically. Apply it dynamically (on hover/focus) and remove after animation completes to free GPU memory.',
                'element' => '',
            ];
        }

        return $this->scored($issues);
    }

    // ─── Component Audit ─────────────────────────────────────────────

    /**
     * @return array{overall_score: int, quality_grade: string, category_scores: array, all_issues: array, top_recommendations: array}
     */
    public function auditComponent(string $html, ?string $css = null, ?string $js = null): array
    {
        $a11y = $this->auditAccessibility($html);
        $cssResult = $css ? $this->auditCss($css) : ['score' => 100, 'issues' => []];

        $animCode = '';
        if ($css) {
            $animCode .= $css;
        }
        if ($js) {
            $animCode .= "\n".$js;
        }
        $animResult = $animCode ? $this->auditAnimation($animCode) : ['score' => 100, 'issues' => []];

        // Weighted score: accessibility 40%, CSS 30%, animation 15%, general 15%
        $generalScore = 100; // base general score
        $overallScore = (int) round(
            $a11y['score'] * 0.40
            + $cssResult['score'] * 0.30
            + $animResult['score'] * 0.15
            + $generalScore * 0.15
        );

        $allIssues = [];
        foreach ($a11y['issues'] as $i) {
            $i['category'] = 'accessibility';
            $allIssues[] = $i;
        }
        foreach ($cssResult['issues'] as $i) {
            $i['category'] = 'css';
            $allIssues[] = $i;
        }
        foreach ($animResult['issues'] as $i) {
            $i['category'] = 'animation';
            $allIssues[] = $i;
        }

        // Sort by severity
        $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($allIssues, fn ($a, $b) => ($severityOrder[$a['severity']] ?? 3) <=> ($severityOrder[$b['severity']] ?? 3));

        // Top recommendations: take the top 5 non-info issues
        $recommendations = array_values(array_filter(
            array_slice($allIssues, 0, 5),
            fn ($i) => $i['severity'] !== 'info'
        ));

        if (empty($recommendations) && ! empty($allIssues)) {
            $recommendations = array_slice($allIssues, 0, 3);
        }

        $grade = match (true) {
            $overallScore >= 90 => 'A',
            $overallScore >= 80 => 'B',
            $overallScore >= 70 => 'C',
            $overallScore >= 60 => 'D',
            default => 'F',
        };

        return [
            'overall_score' => $overallScore,
            'quality_grade' => $grade,
            'category_scores' => [
                'accessibility' => $a11y['score'],
                'css' => $cssResult['score'],
                'animation' => $animResult['score'],
                'general' => $generalScore,
            ],
            'all_issues' => $allIssues,
            'issues_count' => count($allIssues),
            'top_recommendations' => array_map(fn ($r) => $r['message'], $recommendations),
        ];
    }

    // ─── Storage ─────────────────────────────────────────────────────

    public function storeResult(string $type, int $score, array $issues, ?string $context = null): AuditResult
    {
        return AuditResult::create([
            'audit_type' => $type,
            'score' => $score,
            'issues_count' => count($issues),
            'issues' => $issues,
            'context' => $context ? mb_substr($context, 0, 500) : null,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * @return array{score: int, issues: array}
     */
    private function scored(array $issues): array
    {
        $score = 100;

        foreach ($issues as $issue) {
            $score -= match ($issue['severity']) {
                'critical' => 15,
                'warning' => 8,
                'info' => 3,
                default => 0,
            };
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
        ];
    }

    private function issue(string $severity, string $rule, string $message, mixed $node): array
    {
        $element = '';
        if ($node instanceof \DOMElement) {
            $doc = $node->ownerDocument;
            $element = mb_substr($doc->saveHTML($node), 0, 80);
        }

        return compact('severity', 'rule', 'message', 'element');
    }
}
