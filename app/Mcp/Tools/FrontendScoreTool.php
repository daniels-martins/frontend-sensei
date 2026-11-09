<?php

namespace App\Mcp\Tools;

use App\Models\AuditResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('View your frontend quality score trends from past audits. Shows average scores by category, common issues, and improvement over time.')]
class FrontendScoreTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
            'type' => 'nullable|string|in:accessibility,css,contrast,animation,component',
        ]);

        $days = $validated['days'] ?? 30;
        $type = $validated['type'] ?? null;

        $query = AuditResult::recent($days);
        if ($type) {
            $query->ofType($type);
        }

        $results = $query->latest()->get();

        if ($results->isEmpty()) {
            return Response::text(json_encode([
                'message' => 'No audits found in the last '.$days.' days. Run some audits first using audit_accessibility, audit_css, audit_animation, or audit_component.',
                'total_audits' => 0,
            ], JSON_PRETTY_PRINT));
        }

        // Category averages
        $byType = $results->groupBy('audit_type')->map(fn ($group) => [
            'average_score' => round($group->avg('score'), 1),
            'total_audits' => $group->count(),
            'best_score' => $group->max('score'),
            'worst_score' => $group->min('score'),
        ]);

        // Trend: first half vs second half
        $midpoint = $results->count() > 1 ? (int) ceil($results->count() / 2) : 0;
        $trend = 'stable';
        if ($midpoint > 0) {
            $recentHalf = $results->take($midpoint)->avg('score');
            $olderHalf = $results->skip($midpoint)->avg('score');
            $diff = $recentHalf - $olderHalf;
            if ($diff > 5) {
                $trend = 'improving (+'.round($diff, 1).' points)';
            } elseif ($diff < -5) {
                $trend = 'declining ('.round($diff, 1).' points)';
            }
        }

        // Most common issues
        $allIssues = $results->pluck('issues')->flatten(1);
        $commonRules = $allIssues->countBy('rule')->sortDesc()->take(5);

        return Response::text(json_encode([
            'period' => "Last {$days} days",
            'total_audits' => $results->count(),
            'overall_average' => round($results->avg('score'), 1),
            'trend' => $trend,
            'by_category' => $byType,
            'most_common_issues' => $commonRules,
            'latest_audits' => $results->take(5)->map(fn ($r) => [
                'type' => $r->audit_type,
                'score' => $r->score,
                'issues' => $r->issues_count,
                'date' => $r->created_at->toDateTimeString(),
            ]),
        ], JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()
                ->description('Number of days to look back (default: 30, max: 365).'),
            'type' => $schema->string()
                ->description('Filter by audit type: accessibility, css, contrast, animation, or component.'),
        ];
    }
}
