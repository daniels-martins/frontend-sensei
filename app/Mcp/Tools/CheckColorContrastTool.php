<?php

namespace App\Mcp\Tools;

use App\Services\FrontendAuditor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Calculate WCAG 2.2 color contrast ratio between foreground and background colors. Returns pass/fail for AA and AAA compliance at normal and large text sizes.')]
class CheckColorContrastTool extends Tool
{
    public function __construct(protected FrontendAuditor $auditor) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'foreground' => 'required|string|max:50',
            'background' => 'required|string|max:50',
            'font_size' => 'nullable|numeric|min:1|max:200',
            'is_bold' => 'nullable|boolean',
        ], [
            'foreground.required' => 'Provide the foreground (text) color as hex (#fff), rgb(r,g,b), or a named color.',
            'background.required' => 'Provide the background color as hex (#fff), rgb(r,g,b), or a named color.',
        ]);

        $result = $this->auditor->checkContrast(
            $validated['foreground'],
            $validated['background'],
            (float) ($validated['font_size'] ?? 16),
            (bool) ($validated['is_bold'] ?? false),
        );

        $score = isset($result['error']) ? 0 : ($result['passes_minimum'] ? 100 : 30);
        $issues = [];

        if (isset($result['error'])) {
            $issues[] = ['severity' => 'critical', 'rule' => 'color-parse', 'message' => $result['message'], 'element' => ''];
        } elseif (! $result['passes_minimum']) {
            $issues[] = ['severity' => 'critical', 'rule' => 'contrast-fail', 'message' => $result['recommendation'], 'element' => ''];
        }

        $this->auditor->storeResult('contrast', $score, $issues, "{$validated['foreground']} on {$validated['background']}");

        return Response::text(json_encode($result, JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'foreground' => $schema->string()
                ->description('Foreground/text color as hex (#fff or #ffffff), rgb(r,g,b), or named CSS color (e.g. "navy").')
                ->required(),
            'background' => $schema->string()
                ->description('Background color in the same format.')
                ->required(),
            'font_size' => $schema->number()
                ->description('Font size in px (default: 16). Affects whether text qualifies as "large" for WCAG thresholds.'),
            'is_bold' => $schema->boolean()
                ->description('Whether the text is bold. Bold text >= 14px counts as "large text" with relaxed contrast requirements.'),
        ];
    }
}
