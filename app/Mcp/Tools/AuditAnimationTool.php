<?php

namespace App\Mcp\Tools;

use App\Services\FrontendAuditor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Audit CSS/JS animation code for performance issues and best practices. Checks for compositor-friendly properties, reduced motion support, timing, and animation principles.')]
class AuditAnimationTool extends Tool
{
    public function __construct(protected FrontendAuditor $auditor) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'type' => 'nullable|in:css,js',
        ]);

        $result = $this->auditor->auditAnimation(
            $validated['code'],
            $validated['type'] ?? 'auto',
        );

        $this->auditor->storeResult('animation', $result['score'], $result['issues']);

        return Response::text(json_encode([
            'score' => $result['score'],
            'grade' => $result['grade'],
            'issues' => $result['issues'],
            'summary' => count($result['issues']).' issues found',
            'recommendations' => $result['recommendations'],
        ], JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()
                ->description('The CSS or JavaScript animation code to audit.')
                ->required(),
            'type' => $schema->string()
                ->description('Code type: "css" or "js". Auto-detected if not specified.'),
        ];
    }
}
