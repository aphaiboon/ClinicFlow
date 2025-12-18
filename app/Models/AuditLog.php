<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'organization_id',
        'action',
        'resource_type',
        'resource_id',
        'changes',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'changes' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($auditLog) {
            if (! $auditLog->created_at) {
                $auditLog->created_at = now();
            }
        });

        static::updating(function () {
            throw new \RuntimeException('Audit logs are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Audit logs are immutable and cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeByUser($query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeByResource($query, string $resourceType, int $resourceId): void
    {
        $query->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId);
    }

    public function scopeByAction($query, AuditAction $action): void
    {
        $query->where('action', $action);
    }

    public function scopeByDateRange($query, \Carbon\Carbon|\Illuminate\Support\Carbon $start, \Carbon\Carbon|\Illuminate\Support\Carbon $end): void
    {
        $query->whereBetween('created_at', [$start, $end]);
    }
}
