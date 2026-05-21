<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model University → table : universites
 */
class University extends Model
{
    use SoftDeletes;

    protected $table = 'universites';

    protected $fillable = [
        'nom',
        'code',
        'fuseau_horaire',
        'adresse',
        'ville',
        'telephone',
        'couleur_principale',
        'couleur_secondaire',
        'preset_accent',
        'nb_chiffres_matricule',
        'prefixe_matricule',
        'url_logo',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============== RELATIONS ==============

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'universite_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'universite_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'universite_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'universite_id');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'universite_id');
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class, 'universite_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'universite_id');
    }

    public function jwtTokens(): HasMany
    {
        return $this->hasMany(JwtToken::class, 'universite_id');
    }
}
