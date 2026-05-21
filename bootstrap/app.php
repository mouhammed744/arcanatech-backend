<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ApiAuthentication;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Web middleware
        $middleware->web(append: [
            HandleAppearance::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // API middleware - sécurité headers sur toutes les réponses API
        $middleware->api(append: [
            SecurityHeaders::class,
        ]);

        // Alias pour le middleware d'authentification JWT
        $middleware->alias([
            'jwt.auth' => ApiAuthentication::class,
        ]);

        // ── CORS Configuration ──
        // Autorise les origines frontend en dev et prod
        $middleware->validateCsrfTokens(except: [
            'api/*',  // Les API JWT n'utilisent pas CSRF (stateless)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Rendre les erreurs JSON pour les requêtes API
        $exceptions->shouldRenderJsonWhen(function ($request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
