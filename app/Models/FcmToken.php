<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model FcmToken — stocke les tokens FCM des appareils mobiles
 * Table : fcm_tokens
 */
class FcmToken extends Model
{
    protected $table = 'fcm_tokens';

    protected $fillable = [
        'utilisateur_id',
        'token',
        'platform',
    ];

    // ── Relations ──────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
