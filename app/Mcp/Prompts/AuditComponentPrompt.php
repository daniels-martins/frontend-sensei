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
            new Argument('context', 'Description of the component being audited (e.g. "login form", "checkout modal").'),
        ];
    }

    public function handle(Request $request): Response
    {
        $html = $request->get('html', '');

        if (trim($html) === '') {
            throw new \InvalidArgumentException('The html argument is required and cannot be empty.');
        }

        $css = $request->get('css');
        $js = $request->get('js');
        $context = $request->get('context');

        $parts = ["Audit this frontend component using the tools below:\n"];

        $parts[] = "````html\n{$html}\n````\n";

        if ($css) {
            $parts[] = "````css\n{$css}\n````\n";
        }

        if ($js) {
            $parts[] = "````js\n{$js}\n````\n";
        }

        $steps = [];
        $toolArgs = 'the HTML'.($css ? ', CSS' : '').($js ? ' and JS' : '');
        $contextArg = $context ? " and context \"{$context}\"" : '';
        $steps[] = "`audit-component-tool` with {$toolArgs}{$contextArg} for the overall quality score";
        $steps[] = '`check-color-contrast-tool` for any foreground/background color pairs you find in the '.($css ? 'CSS' : 'HTML');

        $parts[] = 'Run these tools in order:';
        foreach ($steps as $i => $step) {
            $parts[] = ($i + 1).'. '.$step;
        }

        $parts[] = "\nSummarise the results: overall grade, top issues by severity, and specific fixes.";

        return Response::text(implode("\n", $parts));
    }
}
