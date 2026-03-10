<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi(); // Middleware pour gérer les requêtes authentifiées à l'API, en utilisant les cookies de session
        $middleware->alias([
            'role' => RoleMiddleware::class, // Alias pour le middleware de gestion des rôles, permettant de l'utiliser facilement dans les routes
        ]);
        $middleware->validateCsrfTokens(except: [
        'api/*',
    ]); // Middleware pour valider les tokens CSRF, en excluant les routes de l'API qui sont généralement utilisées pour les requêtes AJAX et ne nécessitent pas de token CSRF
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
