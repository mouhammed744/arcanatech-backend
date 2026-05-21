<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Controleur 2FA — endpoints d activation / verification / desactivation
 *
 *   POST   /api/auth/2fa/setup        -> genere secret TOTP + QR code
 *   POST   /api/auth/2fa/confirm      -> confirme activation TOTP via code
 *   POST   /api/auth/2fa/enable-email -> active la 2FA par email
 *   POST   /api/auth/2fa/send-code    -> renvoie un code OTP par email
 *   POST   /api/auth/2fa/verify       -> verifie un code (challenge post-login)
 *   DELETE /api/auth/2fa              -> desactive la 2FA
 *   GET    /api/auth/2fa/status       -> retourne l etat de la 2FA
 */
class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    /**
     * Initialise la configuration TOTP pour l utilisateur connecte.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $this->twoFactor->generateTotpSecret($user);

        return response()->json([
            'secret'      => $data['secret'],
            'qrCodeUrl'   => $data['qrCodeUrl'],
            'manualEntry' => $data['manualEntry'],
            'message'     => 'Scannez le QR code avec votre application d authentification, puis confirmez avec un code.',
        ]);
    }

    /**
     * Confirme l activation TOTP.
     */
    public function confirm(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $request->validate([
            'code'   => ['required', 'string', 'size:6'],
            'method' => ['sometimes', 'in:totp,both'],
        ]);

        if (!$this->twoFactor->verifyAndEnableTotp($user, $request->code)) {
            throw ValidationException::withMessages([
                'code' => ['Code invalide ou expire. Reessayez.'],
            ]);
        }

        if ($request->filled('method')) {
            $user->forceFill(['two_factor_method' => $request->method])->save();
        }

        AuditLog::create([
            'universite_id'     => $user->universite_id,
            'utilisateur_id'    => $user->id,
            'type_ressource'    => 'User',
            'id_ressource'      => $user->id,
            'action'            => '2fa_enabled_totp',
            'adresse_ip'        => $request->ip(),
            'nouvelles_valeurs' => ['method' => $user->two_factor_method],
        ]);

        return response()->json([
            'enabled' => true,
            'method'  => $user->two_factor_method,
            'message' => 'Authentification a deux facteurs activee avec succes.',
        ]);
    }

    /**
     * Active la 2FA par email uniquement (pas de TOTP).
     */
    public function enableEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $this->twoFactor->enableEmailOnly($user);

        AuditLog::create([
            'universite_id'  => $user->universite_id,
            'utilisateur_id' => $user->id,
            'type_ressource' => 'User',
            'id_ressource'   => $user->id,
            'action'         => '2fa_enabled_email',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json([
            'enabled' => true,
            'method'  => 'email',
            'message' => 'Authentification par email activee. Un code sera envoye a chaque connexion.',
        ]);
    }

    /**
     * Envoie un code OTP par email (etape challenge).
     * Accepte un jeton challenge (pas besoin d etre authentifie).
     */
    public function sendCode(Request $request): JsonResponse
    {
        $request->validate([
            'challengeToken' => ['required_without:authenticated', 'string'],
        ]);

        $user = $request->user();

        if (!$user) {
            $user = $this->twoFactor->resolveChallengeToken($request->challengeToken);
            if (!$user) {
                return response()->json([
                    'message' => 'Jeton de verification invalide ou expire.',
                ], 422);
            }
        }

        $this->twoFactor->sendEmailCode($user);

        return response()->json([
            'sent'    => true,
            'message' => 'Un code a ete envoye a ' . $this->maskEmail($user->email) . '.',
        ]);
    }

    /**
     * Verifie un code 2FA (login challenge).
     * Retourne un indicateur de succes. L appelant devra ensuite appeler /auth/login-finalize.
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'challengeToken' => ['required', 'string'],
            'code'           => ['required', 'string', 'min:6', 'max:6'],
            'method'         => ['sometimes', 'in:totp,email'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = $this->twoFactor->resolveChallengeToken($request->challengeToken);
        if (!$user) {
            return response()->json([
                'message' => 'Jeton de verification invalide ou expire.',
            ], 422);
        }

        if (!$this->twoFactor->verifyCode($user, $request->code, $request->method)) {
            AuditLog::create([
                'universite_id'  => $user->universite_id,
                'utilisateur_id' => $user->id,
                'type_ressource' => 'User',
                'id_ressource'   => $user->id,
                'action'         => '2fa_failed',
                'adresse_ip'     => $request->ip(),
                'nouvelles_valeurs' => ['method' => $request->method ?? $user->two_factor_method],
            ]);

            throw ValidationException::withMessages([
                'code' => ['Code invalide ou expire.'],
            ]);
        }

        AuditLog::create([
            'universite_id'  => $user->universite_id,
            'utilisateur_id' => $user->id,
            'type_ressource' => 'User',
            'id_ressource'   => $user->id,
            'action'         => '2fa_verified',
            'adresse_ip'     => $request->ip(),
        ]);

        // Delegue a AuthController::finalize2faLogin pour emettre les JWT
        return app(AuthController::class)->finalize2faLogin($request, $user);
    }

    /**
     * Desactive la 2FA apres confirmation du mot de passe.
     */
    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Mot de passe incorrect.'],
            ]);
        }

        $this->twoFactor->disable($user);

        AuditLog::create([
            'universite_id'  => $user->universite_id,
            'utilisateur_id' => $user->id,
            'type_ressource' => 'User',
            'id_ressource'   => $user->id,
            'action'         => '2fa_disabled',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json([
            'enabled' => false,
            'message' => 'Authentification a deux facteurs desactivee.',
        ]);
    }

    /**
     * Retourne le statut 2FA de l utilisateur courant.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        return response()->json([
            'enabled'     => $this->twoFactor->isEnabled($user),
            'method'      => $user->two_factor_method,
            'confirmedAt' => $user->two_factor_confirmed_at?->toIso8601String(),
        ]);
    }

    /**
     * Masque une adresse email : j***@example.com.
     */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, 1);
        return $visible . str_repeat('*', max(1, mb_strlen($local) - 1)) . '@' . $domain;
    }
}
