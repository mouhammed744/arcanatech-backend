<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Student → table : etudiants
 */
class Student extends Model
{
    use SoftDeletes;

    protected $table = 'etudiants';

    protected $fillable = [
        'universite_id',
        'utilisateur_id',
        'numero_matricule',
        'date_naissance',
        'niveau',
        'annee_inscription',
        'filiere_id',
    ];

    protected $casts = [
        'date_naissance'    => 'date',
        'annee_inscription' => 'integer',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
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

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class, 'etudiant_id');
    }

    public function rfidCard(): HasOne
    {
        return $this->hasOne(RfidCard::class, 'etudiant_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'etudiant_id');
    }

    // ============== SCOPES ==============

    public function scopeUniversity($query, int $universityId)
    {
        return $query->where('universite_id', $universityId);
    }

    public function scopeLevel($query, string $level)
    {
        return $query->where('niveau', $level);
    }

    public function scopeFiliere($query, int $filiereId)
    {
        return $query->where('filiere_id', $filiereId);
    }
}
