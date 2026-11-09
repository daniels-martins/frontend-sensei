<?php

namespace App\Mcp\Tools;

use App\Services\FrontendAuditor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Run a comprehensive frontend quality audit on a component. Combines accessibility, CSS quality, and animation performance checks into an overall quality score with prioritized recommendations.')]
class AuditComponentTool extends Tool
{
    public function __construct(protected FrontendAuditor $auditor) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'html' => 'required|string',
            'css' => 'nullable|string',
            'js' => 'nullable|string',
            'context' => 'nullable|string|max:500',
        ]);

        $result = $this->auditor->auditComponent(
            $validated['html'],
            $validated['css'] ?? null,
            $validated['js'] ?? null,
        );

        $this->auditor->storeResult(
            'component',
            $result['overall_score'],
            $result['all_issues'],
            $validated['context'] ?? null,
        );

        return Response::text(json_encode([
            'overall_score' => $result['overall_score'],
            'quality_grade' => $result['quality_grade'],
            'category_scores' => $result['category_scores'],
            'issues_count' => $result['issues_count'],
            'all_issues' => $result['all_issues'],
            'top_recommendations' => $result['top_recommendations'],
        ], JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'html' => $schema->string()
                ->description('The HTML markup of the component to audit.')
                ->required(),
            'css' => $schema->string()
                ->description('The CSS styles for this component.'),
            'js' => $schema->string()
                ->description('Any JavaScript for this component (animation, interaction logic).'),
            'context' => $schema->string()
                ->description('Description of the component being audited (e.g. "product card", "checkout form").'),
        ];
    }
}
