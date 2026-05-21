<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders Middleware
 *
 * Ajoute des en-têtes de sécurité HTTP à toutes les réponses API :
 * - X-Content-Type-Options: nosniff (prévient le MIME sniffing)
 * - X-Frame-Options: DENY (prévient le clickjacking)
 * - X-XSS-Protection: 1; mode=block (protection XSS navigateur)
 * - Strict-Transport-Security: max-age=31536000 (force HTTPS en prod)
 * - Referrer-Policy: strict-origin-when-cross-origin (limite les referrers)
 * - Permissions-Policy: limite les fonctionnalités navigateur
 * - Cache-Control: pas de cache pour les données sensibles
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ── En-têtes de sécurité ──
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS uniquement en production (force HTTPS)
        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Pas de cache pour les réponses API authentifiées
        if ($request->bearerToken()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        // Supprimer les headers qui révèlent des infos serveur
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
