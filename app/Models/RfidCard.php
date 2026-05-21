<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Model RfidCard → table : cartes_rfid
 */
class RfidCard extends Model
{
    use HasUuids;

    protected $table = 'cartes_rfid';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'universite_id',
        'etudiant_id',
        'utilisateur_id',
        'numero_carte',
        'type_carte',
        'est_active',
        'assignee_le',
        'desactivee_le',
        'raison_desactivation',
        'dernier_scan_le',
    ];

    protected $casts = [
        'est_active'     => 'boolean',
        'assignee_le'    => 'datetime',
        'desactivee_le'  => 'datetime',
        'dernier_scan_le' => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class, 'carte_rfid_id');
    }

    // ============== SCOPES ==============

    public function scopeActive($query)
    {
        return $query->where('est_active', true);
    }

    public function scopeUniversity($query, int $universityId)
    {
        return $query->where('universite_id', $universityId);
    }

    // ============== HELPERS ==============

    public function isAdminCard(): bool
    {
        return $this->type_carte === 'admin';
    }

    public function isStudentCard(): bool
    {
        return $this->type_carte === 'student';
    }

    public function deactivate(string $reason = ''): void
    {
        $this->update([
            'est_active'           => false,
            'desactivee_le'        => now(),
            'raison_desactivation' => $reason,
        ]);
    }
}
