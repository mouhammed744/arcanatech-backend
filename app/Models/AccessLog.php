<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Model AccessLog → table : journaux_acces
 */
class AccessLog extends Model
{
    use HasUuids;

    protected $table = 'journaux_acces';
    const UPDATED_AT = null;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'universite_id',
        'carte_rfid_id',
        'salle_id',
        'scanne_le',
        'statut',
        'raison_refus',
    ];

    protected $casts = [
        'scanne_le'  => 'datetime',
        'created_at' => 'datetime',
    ];

    // ============== RELATIONS ==============

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universite_id');
    }

    public function rfidCard(): BelongsTo
    {
        return $this->belongsTo(RfidCard::class, 'carte_rfid_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'salle_id');
    }

    // ============== SCOPES ==============

    public function scopeGranted($query)
    {
        return $query->where('statut', 'granted');
    }

    public function scopeRefused($query)
    {
        return $query->where('statut', 'refused');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scanne_le', now()->toDateString());
    }
}
