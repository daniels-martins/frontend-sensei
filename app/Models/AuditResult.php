<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditResult extends Model
{
    protected $fillable = [
        'audit_type',
        'score',
        'issues_count',
        'issues',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'issues' => 'array',
            'score' => 'integer',
            'issues_count' => 'integer',
        ];
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('audit_type', $type);
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public static function averageScore(?string $type = null, int $days = 30): float
    {
        $query = static::query()->recent($days);

        if ($type) {
            $query->ofType($type);
        }

        return round((float) $query->avg('score'), 1);
    }
}
