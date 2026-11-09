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

        if (! empty($headings) && $headings[0] !== 1) {
            $issues[] = $this->issue('warning', 'heading-order', "Document should start with <h1>, found <h{$headings[0]}>.", null);
        }

        for ($i = 1; $i < count($headings); $i++) {
            if ($headings[$i] > $headings[$i - 1] + 1) {
                $issues[] = $this->issue('warning', 'heading-order', "Heading level skipped: h{$headings[$i - 1]} to h{$headings[$i]}. Do not skip heading levels.", null);
            }
            if ($headings[$i] < $headings[$i - 1] - 1) {
                $issues[] = $this->issue('warning', 'heading-order', "Heading jumps backward: h{$headings[$i - 1]} to h{$headings[$i]}. Maintain a logical heading hierarchy.", null);
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

            if (! $hasLabel) {
                $hasLabel = $xpath->query('ancestor::label', $input)->length > 0;
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

        // Invalid ARIA roles
        $validRoles = ['alert', 'alertdialog', 'application', 'article', 'banner', 'blockquote', 'button', 'caption', 'cell', 'checkbox', 'code', 'columnheader', 'combobox', 'complementary', 'contentinfo', 'definition', 'deletion', 'dialog', 'directory', 'document', 'emphasis', 'feed', 'figure', 'form', 'generic', 'grid', 'gridcell', 'group', 'heading', 'img', 'insertion', 'link', 'list', 'listbox', 'listitem', 'log', 'main', 'marquee', 'math', 'menu', 'menubar', 'menuitem', 'menuitemcheckbox', 'menuitemradio', 'meter', 'navigation', 'none', 'note', 'option', 'paragraph', 'presentation', 'progressbar', 'radio', 'radiogroup', 'region', 'row', 'rowgroup', 'rowheader', 'scrollbar', 'search', 'searchbox', 'separator', 'slider', 'spinbutton', 'status', 'strong', 'subscript', 'superscript', 'switch', 'tab', 'table', 'tablist', 'tabpanel', 'term', 'textbox', 'timer', 'toolbar', 'tooltip', 'tree', 'treegrid', 'treeitem'];
        foreach ($xpath->query('//*[@role]') as $el) {
            $role = strtolower(trim($el->getAttribute('role')));
            if ($role !== '' && ! in_array($role, $validRoles)) {
                $issues[] = $this->issue('critical', 'invalid-role', "Invalid ARIA role \"{$role}\". Use a valid WAI-ARIA role.", $el);
            }
        }

        // aria-hidden on focusable elements
        foreach ($xpath->query('//*[@aria-hidden="true"]') as $el) {
            $tag = strtolower($el->nodeName);
            $isFocusable = in_array($tag, ['a', 'button', 'input', 'select', 'textarea']) || $el->hasAttribute('tabindex');
            if ($isFocusable) {
                $issues[] = $this->issue('critical', 'aria-hidden-focusable', 'Focusable element has aria-hidden="true". It is invisible to screen readers but reachable by keyboard, creating a confusing experience.', $el);
            }
        }

        // aria-labelledby pointing to nonexistent ID
        foreach ($xpath->query('//*[@aria-labelledby]') as $el) {
            $ids = preg_split('/\s+/', trim($el->getAttribute('aria-labelledby')));
            foreach ($ids as $refId) {
                if ($refId !== '' && $xpath->query("//*[@id='{$refId}']")->length === 0) {
                    $issues[] = $this->issue('warning', 'aria-labelledby-missing', "aria-labelledby references id \"{$refId}\" which does not exist in the document.", $el);
                }
            }
        }

        // Duplicate IDs
        $idCounts = [];
        foreach ($xpath->query('//*[@id]') as $el) {
            $id = $el->getAttribute('id');
            if ($id !== '') {
                $idCounts[$id] = ($idCounts[$id] ?? 0) + 1;
            }
        }
        foreach ($idCounts as $id => $count) {
            if ($count > 1) {
                $issues[] = [
                    'severity' => 'critical',
                    'rule' => 'duplicate-id',
                    'message' => "Duplicate id \"{$id}\" found {$count} times. IDs must be unique — duplicates break aria-labelledby, label[for], and fragment links.",
                    'element' => "id=\"{$id}\"",
                ];
            }
        }

        // iframes without title
        foreach ($xpath->query('//iframe') as $iframe) {
            if (! $iframe->hasAttribute('title') || trim($iframe->getAttribute('title')) === '') {
                $issues[] = $this->issue('warning', 'iframe-title', 'iframe missing a title attribute. Screen readers use it to describe the embedded content.', $iframe);
            }
        }

        // Links whose only content is an image with empty alt (no accessible name)
        foreach ($xpath->query('//a') as $a) {
            $text = trim($a->textContent);
            $ariaLabel = trim($a->getAttribute('aria-label'));
            if ($text === '' && $ariaLabel === '') {
                $imgs = $xpath->query('.//img', $a);
                if ($imgs->length > 0) {
                    $allEmpty = true;
                    foreach ($imgs as $img) {
                        if (trim($img->getAttribute('alt')) !== '') {
                            $allEmpty = false;
                            break;
                        }
                    }
                    if ($allEmpty) {
                        $issues[] = $this->issue('critical', 'link-name', 'Link has no accessible name. Its only content is an image with empty or missing alt text.', $a);
                    }
                }
            }
        }

        // Generic link text
        $genericTexts = ['click here', 'here', 'read more', 'more', 'link', 'learn more'];
        foreach ($xpath->query('//a') as $a) {
            $text = strtolower(trim($a->textContent));
            if (in_array($text, $genericTexts)) {
                $issues[] = $this->issue('warning', 'link-text-generic', "Link text \"{$text}\" is not descriptive. Use text that describes the destination or purpose.", $a);
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

        $css = $this->stripCssCommentsAndStrings($css);

        // !important count
        $importantCount = preg_match_all('/!important/', $css);
        $importantContext = [];
        preg_match_all('/([a-z-]+)\s*:[^;]*!important/i', $css, $impMatches);
        $importantContext = array_slice(array_unique($impMatches[1] ?? []), 0, 5);
        $importantEl = $importantContext ? implode(', ', $importantContext).' !important' : '!important';
        if ($importantCount > 3) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'important-overuse',
                'message' => "Found {$importantCount} uses of !important. Excessive !important indicates specificity problems. Refactor selectors instead.",
                'element' => $importantEl,
            ];
        } elseif ($importantCount > 0) {
            $issues[] = [
                'severity' => 'info',
                'rule' => 'important-usage',
                'message' => "Found {$importantCount} use(s) of !important. Consider if these are truly necessary.",
                'element' => $importantEl,
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
        $floatMatches = [];
        preg_match_all('/\bfloat\s*:\s*(left|right)\b/', $css, $floatMatches);
        $floatCount = count($floatMatches[0]);
        if ($floatCount > 0) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'float-layout',
                'message' => "Found {$floatCount} float-based layout(s). Use flexbox or CSS grid for modern, robust layouts.",
                'element' => 'float: '.($floatMatches[1][0] ?? 'left'),
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

        // outline: none without visible focus replacement
        $outlineMatch = [];
        preg_match('/([^{}]*)\{[^}]*outline\s*:\s*(none|0)\b/', $css, $outlineMatch);
        if ($outlineMatch && ! preg_match('/:focus-visible/', $css) && ! preg_match('/box-shadow\s*:/', $css)) {
            $issues[] = [
                'severity' => 'critical',
                'rule' => 'outline-removed',
                'message' => 'outline: none/0 found without :focus-visible or box-shadow replacement. This removes the keyboard focus indicator.',
                'element' => trim($outlineMatch[1] ?? '').' { outline: '.($outlineMatch[2] ?? 'none').' }',
            ];
        }

        // Animations without prefers-reduced-motion
        $hasAnimation = (bool) preg_match('/animation|transition|@keyframes/', $css);
        $hasReducedMotion = (bool) preg_match('/prefers-reduced-motion/', $css);
        if ($hasAnimation && ! $hasReducedMotion) {
            $animMatch = [];
            preg_match('/(animation|transition|@keyframes)[^;{]*/', $css, $animMatch);
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'reduced-motion',
                'message' => 'Animations/transitions found but no prefers-reduced-motion query. Respect users who prefer reduced motion.',
                'element' => mb_substr(trim($animMatch[0] ?? 'animation/transition'), 0, 80),
            ];
        }

        // transition: all
        if (preg_match('/transition\s*:\s*all\b/', $css) || preg_match('/transition-property\s*:\s*all\b/', $css)) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'transition-all',
                'message' => 'transition-property: all is wasteful and can cause unexpected animations. Specify exact properties (e.g. transition: opacity 200ms, transform 200ms).',
                'element' => 'transition: all',
            ];
        }

        // Duplicate properties in same rule
        preg_match_all('/\{([^}]+)\}/s', $css, $ruleBlocks);
        foreach ($ruleBlocks[1] ?? [] as $block) {
            $props = [];
            preg_match_all('/([\w-]+)\s*:/', $block, $propMatches);
            foreach ($propMatches[1] as $p) {
                $props[$p] = ($props[$p] ?? 0) + 1;
            }
            foreach ($props as $p => $count) {
                if ($count > 1) {
                    $issues[] = [
                        'severity' => 'warning',
                        'rule' => 'duplicate-property',
                        'message' => "Property \"{$p}\" is declared {$count} times in the same rule. This is usually unintentional — remove the duplicate.",
                        'element' => $p,
                    ];
                }
            }
        }

        // Obsolete vendor prefixes
        $obsoletePrefixes = ['-webkit-border-radius', '-moz-border-radius', '-webkit-box-shadow', '-moz-box-shadow', '-webkit-transform', '-moz-transform', '-webkit-transition', '-moz-transition', '-webkit-animation', '-moz-animation', '-webkit-box-sizing', '-moz-box-sizing', '-webkit-opacity', '-ms-filter'];
        foreach ($obsoletePrefixes as $prefix) {
            if (str_contains($css, $prefix)) {
                $issues[] = [
                    'severity' => 'info',
                    'rule' => 'vendor-prefix-obsolete',
                    'message' => "\"{$prefix}\" is no longer needed. Modern browsers support the unprefixed version.",
                    'element' => $prefix,
                ];
                break;
            }
        }

        // Fixed pixel widths (layout-breaking)
        preg_match_all('/(?:^|[;\s{])width\s*:\s*(\d+)px/m', $css, $fixedWidths);
        foreach ($fixedWidths[1] ?? [] as $w) {
            if ((int) $w > 400) {
                $issues[] = [
                    'severity' => 'warning',
                    'rule' => 'fixed-width',
                    'message' => "width: {$w}px is a fixed pixel width. Use max-width, percentage, or viewport units for responsive layouts.",
                    'element' => "width: {$w}px",
                ];
            }
        }

        // Non-CSS input detection
        if (! preg_match('/[{:;]/', $css) && strlen(trim($css)) > 10) {
            $issues[] = [
                'severity' => 'warning',
                'rule' => 'not-css',
                'message' => 'Input does not appear to be valid CSS. No selectors, properties, or rules detected.',
                'element' => mb_substr(trim($css), 0, 60),
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
        $bgRgb = $this->parseColor($bg);
        $fgRgb = $this->parseColor($fg, $bg);

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

        $isLargeText = $fontSize >= 24 || ($fontSize >= 18.66 && $isBold);

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
    public function parseColor(string $color, ?string $backdropColor = null): ?array
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

        // #RRGGBBAA (8-digit hex)
        if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $color, $m)) {
            $r = hexdec($m[1]);
            $g = hexdec($m[2]);
            $b = hexdec($m[3]);
            $a = hexdec($m[4]) / 255;

            return $this->compositeAlpha($r, $g, $b, $a, $backdropColor);
        }

        // #RGBA (4-digit hex)
        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])$/i', $color, $m)) {
            $r = hexdec($m[1].$m[1]);
            $g = hexdec($m[2].$m[2]);
            $b = hexdec($m[3].$m[3]);
            $a = hexdec($m[4].$m[4]) / 255;

            return $this->compositeAlpha($r, $g, $b, $a, $backdropColor);
        }

        // rgba(r, g, b, a) or rgb(r g b / a)
        if (preg_match('/^rgba?\s*\(\s*(\d{1,3})\s*[,\s]\s*(\d{1,3})\s*[,\s]\s*(\d{1,3})\s*[,\/]\s*([\d.]+)\s*\)/', $color, $m)) {
            $r = (int) $m[1];
            $g = (int) $m[2];
            $b = (int) $m[3];
            $a = (float) $m[4];

            return $this->compositeAlpha($r, $g, $b, $a, $backdropColor);
        }

        // rgb(r, g, b) or rgb(r g b) — no alpha
        if (preg_match('/^rgba?\s*\(\s*(\d{1,3})\s*[,\s]\s*(\d{1,3})\s*[,\s]\s*(\d{1,3})\s*\)/', $color, $m)) {
            return [(int) $m[1], (int) $m[2], (int) $m[3]];
        }

        // hsl(h, s%, l%) or hsla(h, s%, l%, a)
        if (preg_match('/^hsla?\s*\(\s*([\d.]+)\s*[,\s]\s*([\d.]+)%\s*[,\s]\s*([\d.]+)%\s*(?:[,\/]\s*([\d.]+))?\s*\)/', $color, $m)) {
            $h = fmod((float) $m[1], 360);
            if ($h < 0) {
                $h += 360;
            }
            $s = min(100, max(0, (float) $m[2])) / 100;
            $l = min(100, max(0, (float) $m[3])) / 100;
            $a = isset($m[4]) && $m[4] !== '' ? (float) $m[4] : 1.0;

            [$r, $g, $b] = $this->hslToRgb($h, $s, $l);

            if ($a < 1.0) {
                return $this->compositeAlpha($r, $g, $b, $a, $backdropColor);
            }

            return [$r, $g, $b];
        }

        return null;
    }

    /**
     * @return array{int, int, int}
     */
    private function compositeAlpha(int $r, int $g, int $b, float $a, ?string $backdropColor): array
    {
        $a = min(1.0, max(0.0, $a));
        if ($a >= 1.0) {
            return [$r, $g, $b];
        }

        $bg = $backdropColor ? $this->parseColor($backdropColor) : [255, 255, 255];
        if (! $bg) {
            $bg = [255, 255, 255];
        }

        return [
            (int) round($r * $a + $bg[0] * (1 - $a)),
            (int) round($g * $a + $bg[1] * (1 - $a)),
            (int) round($b * $a + $bg[2] * (1 - $a)),
        ];
    }

    /**
     * @return array{int, int, int}
     */
    private function hslToRgb(float $h, float $s, float $l): array
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 60) {
            [$r1, $g1, $b1] = [$c, $x, 0];
        } elseif ($h < 120) {
            [$r1, $g1, $b1] = [$x, $c, 0];
        } elseif ($h < 180) {
            [$r1, $g1, $b1] = [0, $c, $x];
        } elseif ($h < 240) {
            [$r1, $g1, $b1] = [0, $x, $c];
        } elseif ($h < 300) {
            [$r1, $g1, $b1] = [$x, 0, $c];
        } else {
            [$r1, $g1, $b1] = [$c, 0, $x];
        }

        return [
            (int) round(($r1 + $m) * 255),
            (int) round(($g1 + $m) * 255),
            (int) round(($b1 + $m) * 255),
        ];
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

        $code = $this->stripCssCommentsAndStrings($code);

        if ($type === 'auto') {
            $type = preg_match('/requestAnimationFrame|setInterval|setTimeout|\.animate\(/', $code) ? 'js' : 'css';
        }

        // Layout-triggering properties in animations
        $layoutProps = ['width', 'height', 'top', 'left', 'right', 'bottom', 'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left', 'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left'];
        $flagged = [];
        foreach ($layoutProps as $prop) {
            $escaped = preg_quote($prop, '/');
            if (preg_match('/@keyframes[^}]*\{[^}]*(?:^|[;\s{])'.$escaped.'\s*:/sm', $code) ||
                preg_match('/transition[^;]*(?:^|[\s,])'.$escaped.'(?:\s|,|;|$)/', $code)) {
                // Avoid double-counting: skip shorthand if a longhand already matched
                $isLonghand = str_contains($prop, '-');
                $shorthand = $isLonghand ? explode('-', $prop)[0] : null;
                if ($shorthand && isset($flagged[$shorthand])) {
                    continue;
                }
                if (! $isLonghand) {
                    // Skip shorthand if any longhand of it was already flagged
                    $longhandFlagged = false;
                    foreach ($flagged as $f => $_) {
                        if (str_starts_with($f, $prop.'-')) {
                            $longhandFlagged = true;
                            break;
                        }
                    }
                    if ($longhandFlagged) {
                        continue;
                    }
                }
                $flagged[$prop] = true;
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
            // Universal will-change is worse than just static
            if (preg_match('/\*\s*\{[^}]*will-change/', $code)) {
                $issues[] = [
                    'severity' => 'warning',
                    'rule' => 'universal-will-change',
                    'message' => 'will-change applied to * (all elements) defeats its purpose and wastes GPU memory. Apply only to elements that will actually animate.',
                    'element' => '* { will-change }',
                ];
            } else {
                $issues[] = [
                    'severity' => 'info',
                    'rule' => 'static-will-change',
                    'message' => 'will-change appears to be set statically. Apply it dynamically (on hover/focus) and remove after animation completes to free GPU memory.',
                    'element' => '',
                ];
            }
        }

        // Layout thrashing in JS (read-then-write pattern)
        if ($type === 'js') {
            $readsLayout = (bool) preg_match('/offset(?:Top|Left|Width|Height)|client(?:Top|Left|Width|Height)|getComputedStyle|getBoundingClientRect|scroll(?:Top|Left|Width|Height)/', $code);
            $writesLayout = (bool) preg_match('/style\s*\.\s*(?:top|left|right|bottom|width|height|margin|padding)/', $code);
            if ($readsLayout && $writesLayout) {
                $issues[] = [
                    'severity' => 'critical',
                    'rule' => 'layout-thrashing',
                    'message' => 'Potential layout thrashing: reading layout properties and writing layout styles in the same code. Batch reads before writes, or use requestAnimationFrame to separate them.',
                    'element' => '',
                ];
            }

            // jQuery .animate with layout properties
            if (preg_match('/\.animate\s*\(\s*\{[^}]*(?:width|height|top|left|right|bottom|margin|padding)/', $code)) {
                $issues[] = [
                    'severity' => 'warning',
                    'rule' => 'jquery-layout-animation',
                    'message' => 'jQuery .animate() with layout properties (width/height/top/left) causes layout recalculation per frame. Use CSS transitions with transform instead.',
                    'element' => '.animate()',
                ];
            }
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

        // Deduplicate reduced-motion between CSS and animation audits
        $seenRules = [];
        $deduped = function (array $issues) use (&$seenRules): array {
            $result = [];
            foreach ($issues as $i) {
                $key = $i['rule'];
                if (in_array($key, ['reduced-motion', 'reduced-motion-missing']) && isset($seenRules[$key])) {
                    continue;
                }
                $seenRules[$key] = true;
                $result[] = $i;
            }

            return $result;
        };

        // Weighted score: accessibility 50%, CSS 30%, animation 20%
        $overallScore = (int) round(
            $a11y['score'] * 0.50
            + $cssResult['score'] * 0.30
            + $animResult['score'] * 0.20
        );

        $allIssues = [];
        foreach ($a11y['issues'] as $i) {
            $i['category'] = 'accessibility';
            $allIssues[] = $i;
        }
        foreach ($deduped($cssResult['issues']) as $i) {
            $i['category'] = 'css';
            $allIssues[] = $i;
        }
        foreach ($deduped($animResult['issues']) as $i) {
            $i['category'] = 'animation';
            $allIssues[] = $i;
        }

        // Sort by severity
        $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($allIssues, fn ($a, $b) => ($severityOrder[$a['severity']] ?? 3) <=> ($severityOrder[$b['severity']] ?? 3));

        // Cap score harder when criticals exist
        $criticalCount = count(array_filter($allIssues, fn ($i) => $i['severity'] === 'critical'));
        if ($criticalCount >= 2 && $overallScore > 60) {
            $overallScore = min($overallScore, 60);
        }
        if ($criticalCount >= 3 && $overallScore > 50) {
            $overallScore = min($overallScore, 50);
        }

        // Recommendations from all issues
        $recommendations = array_map(fn ($i) => $i['message'], array_filter(
            $allIssues,
            fn ($i) => $i['severity'] !== 'info'
        ));
        if (empty($recommendations) && ! empty($allIssues)) {
            $recommendations = array_map(fn ($i) => $i['message'], array_slice($allIssues, 0, 3));
        }

        $grade = match (true) {
            $overallScore >= 90 => 'A',
            $overallScore >= 80 => 'B',
            $overallScore >= 70 => 'C',
            $overallScore >= 60 => 'D',
            default => 'F',
        };

        return [
            'score' => $overallScore,
            'grade' => $grade,
            'category_scores' => [
                'accessibility' => $a11y['score'],
                'css' => $cssResult['score'],
                'animation' => $animResult['score'],
            ],
            'issues' => $allIssues,
            'issues_count' => count($allIssues),
            'recommendations' => array_values($recommendations),
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

        $recommendations = array_values(array_map(
            fn ($i) => $i['message'],
            array_filter($issues, fn ($i) => $i['severity'] !== 'info')
        ));

        $grade = match (true) {
            max(0, $score) >= 90 => 'A',
            max(0, $score) >= 80 => 'B',
            max(0, $score) >= 70 => 'C',
            max(0, $score) >= 60 => 'D',
            default => 'F',
        };

        return [
            'score' => max(0, $score),
            'grade' => $grade,
            'issues' => $issues,
            'issues_count' => count($issues),
            'recommendations' => $recommendations,
        ];
    }

    private function stripCssCommentsAndStrings(string $code): string
    {
        // Remove /* ... */ comments
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);
        // Remove // line comments (JS and SCSS)
        $code = preg_replace('/\/\/[^\n]*/', '', $code);
        // Replace quoted string contents with placeholder to avoid false matches
        $code = preg_replace('/(["\'`])(?:(?!\1|\\\\).|\\\\.)*\1/', '$1$1', $code);

        return $code;
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
