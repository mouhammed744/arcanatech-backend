<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Model AuditLog → table : journaux_audit
 */
class AuditLog extends Model
{
    use HasUuids;

    protected $table = 'journaux_audit';
    const UPDATED_AT = null;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'universite_id',
        'utilisateur_id',
        'type_ressource',
        'id_ressource',
        'action',
        'anciennes_valeurs',
        'nouvelles_valeurs',
        'adresse_ip',
    ];

    protected $casts = [
        'anciennes_valeurs' => 'array',
        'nouvelles_valeurs' => 'array',
        'created_at'        => 'datetime',
    ];

    // ============== RELATIONS ==============

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universite_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id')->withTrashed();
    }

    // ============== SCOPES ==============

    public function scopeResource($query, string $resourceType, int $resourceId)
    {
        return $query->where('type_ressource', $resourceType)
                     ->where('id_ressource', $resourceId);
    }

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeUniversityBetween($query, int $universityId, $start, $end)
    {
        return $query->where('universite_id', $universityId)
                     ->whereBetween('created_at', [$start, $end]);
    }

    public function scopeUser($query, int $userId)
    {
        return $query->where('utilisateur_id', $userId);
    }

    // ============== HELPERS ==============

    public function formatChanges(): array
    {
        $changes = [];
        if ($this->anciennes_valeurs && $this->nouvelles_valeurs) {
            foreach ($this->nouvelles_valeurs as $key => $newValue) {
                $oldValue = $this->anciennes_valeurs[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'from' => $oldValue,
                        'to'   => $newValue,
                    ];
                }
            }
        }
        return $changes;
    }
}
