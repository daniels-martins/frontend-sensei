<?php

namespace App\Mcp\Tools;

use App\Services\FrontendAuditor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Audit HTML for accessibility issues against WCAG 2.2 guidelines. Checks images, headings, forms, ARIA, landmarks, links, buttons, tables, and more.')]
class AuditAccessibilityTool extends Tool
{
    public function __construct(protected FrontendAuditor $auditor) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'html' => 'required|string',
            'context' => 'nullable|string|max:500',
        ]);

        $result = $this->auditor->auditAccessibility($validated['html']);
        $this->auditor->storeResult('accessibility', $result['score'], $result['issues'], $validated['context'] ?? null);

        $critical = count(array_filter($result['issues'], fn ($i) => $i['severity'] === 'critical'));
        $warnings = count(array_filter($result['issues'], fn ($i) => $i['severity'] === 'warning'));

        return Response::text(json_encode([
            'score' => $result['score'],
            'grade' => $this->grade($result['score']),
            'summary' => "{$critical} critical, {$warnings} warnings, ".count($result['issues']).' total issues',
            'issues' => $result['issues'],
            'recommendations' => $this->recommendations($result),
        ], JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'html' => $schema->string()
                ->description('The HTML markup to audit for accessibility issues.')
                ->required(),
            'context' => $schema->string()
                ->description('Description of the component being audited (e.g. "login form", "navigation bar").'),
        ];
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    private function recommendations(array $result): array
    {
        $recs = [];

        if ($result['score'] >= 90) {
            $recs[] = 'Excellent accessibility! Minor improvements may still be possible.';
        }

        $rules = array_column($result['issues'], 'rule');

        if (in_array('img-alt', $rules)) {
            $recs[] = 'Add descriptive alt text to images. Use alt="" only for purely decorative images.';
        }
        if (in_array('form-label', $rules)) {
            $recs[] = 'Associate every form input with a label using <label for="id">, aria-label, or aria-labelledby.';
        }
        if (in_array('heading-order', $rules)) {
            $recs[] = 'Fix heading hierarchy — headings should descend in order (h1 → h2 → h3) without skipping levels.';
        }
        if (in_array('outline-none', $rules) || in_array('outline-removed', $rules)) {
            $recs[] = 'Never remove focus outlines without providing a visible alternative (use :focus-visible).';
        }

        return array_slice($recs, 0, 5);
    }
}
