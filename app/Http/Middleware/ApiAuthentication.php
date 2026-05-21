<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\JwtToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiAuthentication Middleware - JWT HMAC-SHA256
 *
 * SÉCURITÉ :
 * - Vérifie la signature JWT avec JWT_SECRET (ou app.key en fallback)
 * - Vérifie la non-révocation du token en BD
 * - Vérifie que l'utilisateur est actif
 * - Protection contre les tokens expirés, invalides, ou révoqués
 */
class ApiAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'Token d\'authentification manquant.',
            ], 401);
        }

        // ===== Valider la signature JWT =====
        try {
            $secret  = env('JWT_SECRET', config('app.key'));
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (ExpiredException $e) {
            return response()->json([
                'error'   => 'Token expired',
                'message' => 'Votre token a expiré. Veuillez le rafraîchir.',
            ], 401);
        } catch (SignatureInvalidException $e) {
            return response()->json([
                'error'   => 'Invalid signature',
                'message' => 'Signature du token invalide.',
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'Token invalide.',
            ], 401);
        }

        // ===== Vérifier le type de token (doit être 'access') =====
        if (isset($decoded->typ) && $decoded->typ !== 'access') {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'Type de token invalide.',
            ], 401);
        }

        // ===== Vérifier que le token existe en BD et n'est pas révoqué =====
        $jwtToken = JwtToken::where('jeton_jwt', $token)
            ->whereNull('revoque_le')
            ->where('expire_le', '>', now())
            ->first();

        if (!$jwtToken) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'Token révoqué ou introuvable.',
            ], 401);
        }

        // ===== Récupérer l'utilisateur =====
        $user = User::find($decoded->sub);

        if (!$user || !$user->est_actif) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'Utilisateur introuvable ou inactif.',
            ], 401);
        }

        // ===== Authentifier l'utilisateur dans le contexte Laravel =====
        Auth::login($user, false);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);

        if (strlen($token) < 10) {
            return null;
        }

        return $token;
    }
}
