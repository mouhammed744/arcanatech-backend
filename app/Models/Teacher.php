<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Teacher → table : enseignants
 */
class Teacher extends Model
{
    use SoftDeletes;

    protected $table = 'enseignants';

    protected $fillable = [
        'universite_id',
        'utilisateur_id',
        'specialite',
        'grade',
        'date_embauche',
        'statut',
    ];

    protected $casts = [
        'date_embauche' => 'date',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
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

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'enseignant_id');
    }

    // ============== SCOPES ==============

    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }

    public function scopeUniversity($query, int $universityId)
    {
        return $query->where('universite_id', $universityId);
    }
}
