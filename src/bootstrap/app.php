<?php

use App\Http\HttpErrorMessages;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\ResolveLocale;
use App\Http\Middleware\ResolveStoreFromSubdomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->web(prepend: [ResolveLocale::class]);
        $middleware->api(prepend: [ResolveLocale::class]);

        // HTTPS enforcement (only active in production)
        $middleware->prepend(ForceHttps::class);

        // Customer auth is on the API/SPA — redirect unauthenticated
        // web visitors to the home page instead of a login route.
        $middleware->redirectGuestsTo('/');

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'store.subdomain' => ResolveStoreFromSubdomain::class,
            'signed' => ValidateSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $e) {
            $code = $e->getStatusCode();

            return response()->view('errors.custom', HttpErrorMessages::toArray($code), $code);
        });
    })->create();
