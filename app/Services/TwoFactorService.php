<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Service 2FA — TOTP (Google Authenticator) + Email OTP
 *
 * Methodes :
 *   - totp  : application d authentification (Google Auth, Authy, 1Password...)
 *   - email : code a usage unique envoye par email
 *   - both  : les deux methodes activees, l utilisateur choisit au login
 */
class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ============== TOTP ==============

    /**
     * Genere un nouveau secret TOTP et une URL otpauth pour afficher un QR code.
     */
    public function generateTotpSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey(32);

        $user->forceFill([
            'two_factor_secret'       => Crypt::encryptString($secret),
            'two_factor_confirmed_at' => null,
        ])->save();

        $issuer = config('app.name', 'UniAccess');
        $otpauthUrl = $this->google2fa->getQRCodeUrl(
            $issuer,
            $user->email,
            $secret,
        );

        return [
            'secret'      => $secret,
            'qrCodeUrl'   => $otpauthUrl,
            'manualEntry' => chunk_split($secret, 4, ' '),
        ];
    }

    /**
     * Verifie un code TOTP et confirme l activation.
     */
    public function verifyAndEnableTotp(User $user, string $code): bool
    {
        if (empty($user->two_factor_secret)) {
            return false;
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $code, 2);

        if ($valid) {
            $user->forceFill([
                'two_factor_confirmed_at' => now(),
                'two_factor_method'       => $user->two_factor_method ?: 'totp',
            ])->save();
        }

        return $valid;
    }

    /**
     * Verifie un code TOTP (pour le login).
     */
    public function verifyTotpCode(User $user, string $code): bool
    {
        if (empty($user->two_factor_secret) || empty($user->two_factor_confirmed_at)) {
            return false;
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        return $this->google2fa->verifyKey($secret, $code, 2);
    }

    // ============== EMAIL OTP ==============

    /**
     * Genere un code OTP (6 chiffres) et l envoie par email.
     * Duree de validite : 10 minutes.
     */
    public function sendEmailCode(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'two_factor_email_code'             => $code,
            'two_factor_email_code_expires_at'  => now()->addMinutes(10),
        ])->save();

        $user->notify(new TwoFactorCodeNotification($code));

        return $code;
    }

    /**
     * Verifie un code OTP email et reinitialise le code si valide.
     */
    public function verifyEmailCode(User $user, string $code): bool
    {
        if (empty($user->two_factor_email_code) || empty($user->two_factor_email_code_expires_at)) {
            return false;
        }

        if (now()->greaterThan($user->two_factor_email_code_expires_at)) {
            return false;
        }

        $valid = hash_equals((string) $user->two_factor_email_code, trim($code));

        if ($valid) {
            $user->forceFill([
                'two_factor_email_code'            => null,
                'two_factor_email_code_expires_at' => null,
            ])->save();
        }

        return $valid;
    }

    // ============== METHODE & STATUT ==============

    /**
     * Active la 2FA email uniquement (sans TOTP).
     */
    public function enableEmailOnly(User $user): void
    {
        $user->forceFill([
            'two_factor_method'       => 'email',
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    /**
     * Desactive totalement la 2FA.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret'                => null,
            'two_factor_recovery_codes'        => null,
            'two_factor_confirmed_at'          => null,
            'two_factor_method'                => null,
            'two_factor_email_code'            => null,
            'two_factor_email_code_expires_at' => null,
        ])->save();
    }

    /**
     * L utilisateur a-t-il la 2FA activee (peu importe la methode) ?
     */
    public function isEnabled(User $user): bool
    {
        return !empty($user->two_factor_method) && !empty($user->two_factor_confirmed_at);
    }

    /**
     * Verifie un code selon la methode choisie par l utilisateur.
     */
    public function verifyCode(User $user, string $code, ?string $method = null): bool
    {
        $method = $method ?: $user->two_factor_method;

        return match ($method) {
            'totp'  => $this->verifyTotpCode($user, $code),
            'email' => $this->verifyEmailCode($user, $code),
            'both'  => $this->verifyTotpCode($user, $code) || $this->verifyEmailCode($user, $code),
            default => false,
        };
    }

    /**
     * Genere un jeton challenge temporaire pour l etape 2FA post-login.
     */
    public function generateChallengeToken(User $user): string
    {
        $payload = [
            'user_id'    => $user->id,
            'nonce'      => Str::random(24),
            'expires_at' => now()->addMinutes(10)->timestamp,
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    /**
     * Decode un jeton challenge et retourne l utilisateur associe (ou null).
     */
    public function resolveChallengeToken(string $token): ?User
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable) {
            return null;
        }

        if (empty($payload['user_id']) || empty($payload['expires_at'])) {
            return null;
        }

        if (now()->timestamp > $payload['expires_at']) {
            return null;
        }

        return User::find($payload['user_id']);
    }
}
