<?php

namespace App\Mcp\Tools;

use App\Services\FrontendAuditor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Audit CSS for quality issues, anti-patterns, and best practice violations. Checks for specificity problems, accessibility gaps, maintainability issues, and modern CSS adoption.')]
class AuditCssTool extends Tool
{
    public function __construct(protected FrontendAuditor $auditor) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'css' => 'required|string',
            'context' => 'nullable|string|max:500',
        ]);

        $result = $this->auditor->auditCss($validated['css']);
        $this->auditor->storeResult('css', $result['score'], $result['issues'], $validated['context'] ?? null);

        return Response::text(json_encode([
            'score' => $result['score'],
            'grade' => match (true) {
                $result['score'] >= 90 => 'A',
                $result['score'] >= 80 => 'B',
                $result['score'] >= 70 => 'C',
                $result['score'] >= 60 => 'D',
                default => 'F',
            },
            'issues' => $result['issues'],
            'summary' => count($result['issues']).' issues found',
        ], JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'css' => $schema->string()
                ->description('The CSS code to audit for quality issues.')
                ->required(),
            'context' => $schema->string()
                ->description('Description of the stylesheet being audited.'),
        ];
    }
}
