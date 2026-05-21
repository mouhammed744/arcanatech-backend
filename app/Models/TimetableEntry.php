<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model TimetableEntry → table : seances
 */
class TimetableEntry extends Model
{
    use SoftDeletes;

    protected $table = 'seances';

    protected $fillable = [
        'universite_id',
        'cours_id',
        'salle_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'type_seance',
        'recurrence',
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'heure_debut' => 'string',
        'heure_fin'   => 'string',
        'date_debut'  => 'date',
        'date_fin'    => 'date',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    // ============== RELATIONS ==============

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universite_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'cours_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'salle_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'seance_id');
    }

    // ============== SCOPES ==============

    public function scopeUniversity($query, int $universityId)
    {
        return $query->where('universite_id', $universityId);
    }

    public function scopeToday($query, string $dayName = null)
    {
        $dayName = $dayName ?? now()->format('l');
        return $query->where('jour_semaine', $dayName);
    }

    public function scopeActive($query)
    {
        return $query->where('date_debut', '<=', now()->toDateString())
                     ->where(function ($q) {
                         $q->whereNull('date_fin')
                           ->orWhere('date_fin', '>=', now()->toDateString());
                     });
    }
}
