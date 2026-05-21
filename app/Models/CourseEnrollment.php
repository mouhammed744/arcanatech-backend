<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model CourseEnrollment → table : inscriptions
 */
class CourseEnrollment extends Model
{
    use SoftDeletes;

    protected $table = 'inscriptions';

    protected $fillable = [
        'universite_id',
        'etudiant_id',
        'cours_id',
        'statut',
        'inscrit_le',
        'complete_le',
    ];

    protected $casts = [
        'inscrit_le'  => 'datetime',
        'complete_le' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    // ============== RELATIONS ==============

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universite_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'etudiant_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'cours_id');
    }

    // ============== SCOPES ==============

    public function scopeStatus($query, string $status)
    {
        return $query->where('statut', $status);
    }
}
