<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\FcmToken;

/**
 * Model User → table : utilisateurs
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'universite_id',
        'prenom',
        'nom',
        'email',
        'password',
        'telephone',
        'genre',
        'adresse',
        'role',
        'est_actif',
        'derniere_connexion_le',
        'chemin_avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'derniere_connexion_le' => 'datetime',
            'created_at'            => 'datetime',
            'updated_at'            => 'datetime',
            'deleted_at'            => 'datetime',
            'est_actif'             => 'boolean',
        ];
    }

    // ============== RELATIONS ==============

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universite_id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'utilisateur_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'utilisateur_id');
    }

    public function jwtTokens(): HasMany
    {
        return $this->hasMany(JwtToken::class, 'utilisateur_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'utilisateur_id');
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class, 'utilisateur_id');
    }

    // ============== SCOPES ==============

    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeActive($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeUniversity($query, int $universityId)
    {
        return $query->where('universite_id', $universityId);
    }

    // ============== VIRTUAL PROPERTIES ==============

    public function getFullNameAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->chemin_avatar) {
            return null;
        }

        return asset('storage/' . $this->chemin_avatar);
    }

    // ============== ROLE HELPERS ==============

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
