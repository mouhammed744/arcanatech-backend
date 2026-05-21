<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Model TemporaryAccessCode → table : codes_temporaires
 */
class TemporaryAccessCode extends Model
{
    use HasUuids;

    protected $table = 'codes_temporaires';

    protected $fillable = [
        'universite_id',
        'etudiant_id',
        'genere_par',
        'salle_id',
        'seance_id',
        'code',
        'raison',
        'expire_le',
        'utilise_le',
        'est_utilise',
    ];

    protected $casts = [
        'expire_le'   => 'datetime',
        'utilise_le'  => 'datetime',
        'est_utilise' => 'boolean',
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

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'genere_par');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'salle_id');
    }

    public function timetableEntry(): BelongsTo
    {
        return $this->belongsTo(TimetableEntry::class, 'seance_id');
    }

    // ============== HELPERS ==============

    public function isValid(): bool
    {
        return !$this->est_utilise && $this->expire_le->isFuture();
    }

    public function markAsUsed(): void
    {
        $this->update([
            'est_utilise' => true,
            'utilise_le'  => now(),
        ]);
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
            $code = str_replace(['0', 'O', 'I', 'L', '1'], ['X', 'P', 'J', 'K', '2'], $code);
        } while (static::where('code', $code)->where('est_utilise', false)->where('expire_le', '>', now())->exists());

        return $code;
    }
}
