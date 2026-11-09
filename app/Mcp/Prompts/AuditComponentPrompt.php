<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Audit a frontend component for accessibility, CSS quality, and animation performance. Provide the HTML, and optionally CSS and JS, for a comprehensive quality report.')]
class AuditComponentPrompt extends Prompt
{
    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('html', 'The HTML markup of the component to audit.', required: true),
            new Argument('css', 'The CSS styles for this component.'),
            new Argument('js', 'Any JavaScript for this component (animation, interaction logic).'),
        ];
    }

    public function handle(Request $request): Response
    {
        $html = $request->get('html', '');
        $css = $request->get('css');
        $js = $request->get('js');

        $parts = ["Audit this frontend component using all available tools:\n"];

        $parts[] = "**HTML:**\n```html\n{$html}\n```\n";

        if ($css) {
            $parts[] = "**CSS:**\n```css\n{$css}\n```\n";
        }

        if ($js) {
            $parts[] = "**JavaScript:**\n```js\n{$js}\n```\n";
        }

        $parts[] = 'Run these tools in order:';
        $parts[] = '1. `audit-accessibility-tool` with the HTML';
        if ($css) {
            $parts[] = '2. `audit-css-tool` with the CSS';
        }
        $parts[] = '3. `check-color-contrast-tool` for any color pairs you find in the CSS';
        if ($css || $js) {
            $parts[] = '4. `audit-animation-tool` with the CSS and/or JS';
        }
        $parts[] = '5. `audit-component-tool` with all inputs for the overall score';
        $parts[] = "\nSummarise the results: overall grade, top issues by severity, and specific fixes.";

        return Response::text(implode("\n", $parts));
    }
}
