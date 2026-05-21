<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Model JwtToken → table : jetons_jwt
 */
class JwtToken extends Model
{
    use HasUuids;

    protected $table = 'jetons_jwt';
    const UPDATED_AT = null;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'utilisateur_id',
        'universite_id',
        'type',
        'jeton',
        'jeton_jwt',
        'expire_le',
        'revoque_le',
        'adresse_ip',
        'agent_utilisateur',
    ];

    protected $casts = [
        'expire_le'  => 'datetime',
        'revoque_le' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'jeton',
        'jeton_jwt',
    ];

    // ============== RELATIONS ==============

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universite_id');
    }

    // ============== SCOPES ==============

    public function scopeActive($query)
    {
        return $query->whereNull('revoque_le');
    }

    public function scopeAccess($query)
    {
        return $query->where('type', 'access');
    }

    public function scopeRefresh($query)
    {
        return $query->where('type', 'refresh');
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expire_le', '>', now());
    }

    // ============== HELPERS ==============

    public function revoke(): void
    {
        $this->update(['revoque_le' => now()]);
    }

    public function isExpired(): bool
    {
        return $this->expire_le < now();
    }

    public function isRevoked(): bool
    {
        return $this->revoque_le !== null;
    }
}
