<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Course → table : cours
 */
class Course extends Model
{
    use SoftDeletes;

    protected $table = 'cours';

    protected $fillable = [
        'universite_id',
        'enseignant_id',
        'code',
        'nom',
        'description',
        'credits',
        'niveau',
        'semestre',
        'minutes_retard_max',
    ];

    protected $casts = [
        'credits'            => 'integer',
        'minutes_retard_max' => 'integer',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    // ============== RELATIONS ==============

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universite_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'enseignant_id');
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class, 'cours_id');
    }

    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class, 'cours_id');
    }

    // ============== SCOPES ==============

    public function scopeUniversity($query, int $universityId)
    {
        return $query->where('universite_id', $universityId);
    }

    public function scopeSemester($query, string $semester)
    {
        return $query->where('semestre', $semester);
    }
}
