<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Classroom → table : salles
 */
class Classroom extends Model
{
    use SoftDeletes;

    protected $table = 'salles';

    protected $fillable = [
        'universite_id',
        'nom',
        'batiment',
        'numero_salle',
        'capacite',
        'equipement',
        'type',
        'est_active',
    ];

    protected $casts = [
        'capacite'   => 'integer',
        'est_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============== RELATIONS ==============

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universite_id');
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class, 'salle_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class, 'salle_id');
    }

    // ============== SCOPES ==============

    public function scopeUniversity($query, int $universityId)
    {
        return $query->where('universite_id', $universityId);
    }

    public function scopeActive($query)
    {
        return $query->where('est_active', true);
    }
}
