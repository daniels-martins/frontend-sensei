<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\AuditComponentPrompt;
use App\Mcp\Tools\AuditAccessibilityTool;
use App\Mcp\Tools\AuditAnimationTool;
use App\Mcp\Tools\AuditComponentTool;
use App\Mcp\Tools\AuditCssTool;
use App\Mcp\Tools\CheckColorContrastTool;
use App\Mcp\Tools\FrontendScoreTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Frontend Sensei')]
#[Version('1.0.0')]
#[Instructions('Frontend Sensei is a professional UI/UX expertise plugin. It provides code audit tools for accessibility (WCAG 2.2), CSS quality, color contrast, animation performance, and full component audits. It tracks your frontend quality scores over time. Use audit-accessibility-tool to check HTML, audit-css-tool to check stylesheets, check-color-contrast-tool for WCAG contrast ratios, audit-animation-tool for animation performance, audit-component-tool for a comprehensive audit, and frontend-score-tool to view your quality trends.')]
class FrontendSenseiServer extends Server
{
    protected array $capabilities = [
        self::CAPABILITY_TOOLS => [
            'listChanged' => false,
        ],
        self::CAPABILITY_PROMPTS => [
            'listChanged' => false,
        ],
    ];

    protected array $tools = [
        AuditAccessibilityTool::class,
        AuditCssTool::class,
        CheckColorContrastTool::class,
        AuditAnimationTool::class,
        AuditComponentTool::class,
        FrontendScoreTool::class,
    ];

    protected array $prompts = [
        AuditComponentPrompt::class,
    ];
}
