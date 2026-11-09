<?php

namespace App\Mcp\Servers;

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
#[Instructions('Frontend Sensei is a professional UI/UX expertise plugin. It provides code audit tools for accessibility (WCAG 2.2), CSS quality, color contrast, animation performance, and full component audits. It tracks your frontend quality scores over time. Use audit_accessibility to check HTML, audit_css to check stylesheets, check_color_contrast for WCAG contrast ratios, audit_animation for animation performance, audit_component for a comprehensive audit, and frontend_score to view your quality trends.')]
class FrontendSenseiServer extends Server
{
    protected array $tools = [
        AuditAccessibilityTool::class,
        AuditCssTool::class,
        CheckColorContrastTool::class,
        AuditAnimationTool::class,
        AuditComponentTool::class,
        FrontendScoreTool::class,
    ];
}
