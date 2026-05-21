<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Filiere → table : filieres (déjà en français)
 */
class Filiere extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'universite_id',
        'code',
        'nom',
        'description',
        'niveau',
        'departement',
        'est_active',
    ];

    protected $casts = [
        'est_active'  => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    // ============== RELATIONS ==============

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universite_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'filiere_cours', 'filiere_id', 'cours_id')
                     ->withTimestamps();
    }

    // ============== SCOPES ==============

    public function scopeForUniversity($query, int $universityId)
    {
        return $query->where('universite_id', $universityId);
    }

    public function scopeActive($query)
    {
        return $query->where('est_active', true);
    }

    public function scopeLevel($query, string $level)
    {
        return $query->where('niveau', $level);
    }

    public function scopeDepartment($query, string $department)
    {
        return $query->where('departement', $department);
    }

    // ============== HELPERS ==============

    public function activeStudentsCount(): int
    {
        return $this->students()
                     ->whereHas('user', fn($q) => $q->where('est_actif', true))
                     ->count();
    }
}
