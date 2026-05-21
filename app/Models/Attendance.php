<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Attendance → table : presences
 */
class Attendance extends Model
{
    use SoftDeletes;

    protected $table = 'presences';

    protected $fillable = [
        'universite_id',
        'seance_id',
        'etudiant_id',
        'statut',
        'scanne_le',
        'methode_verification',
        'notes',
    ];

    protected $casts = [
        'scanne_le'  => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============== RELATIONS ==============

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universite_id');
    }

    public function timetableEntry(): BelongsTo
    {
        return $this->belongsTo(TimetableEntry::class, 'seance_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'etudiant_id');
    }

    // ============== SCOPES ==============

    public function scopeStatus($query, string $status)
    {
        return $query->where('statut', $status);
    }

    public function scopeStudent($query, int $studentId)
    {
        return $query->where('etudiant_id', $studentId);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('scanne_le', now()->year)
                     ->whereMonth('scanne_le', now()->month);
    }
}
