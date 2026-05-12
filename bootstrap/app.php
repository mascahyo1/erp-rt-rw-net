<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('operator-perusahaan/*') || $request->is('login-perusahaan*')) {
                return route('operator-perusahaan.login');
            }

            if ($request->is('customer/*') || $request->is('login-pelanggan*')) {
                return route('customer.login');
            }

            if ($request->is('karyawan/*') || $request->is('login-karyawan*')) {
                return route('employee.login');
            }

            return route('operator-saas.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $status = $e->getStatusCode();

            if (! in_array($status, [403, 404, 500, 503])) {
                return null;
            }

            return Inertia::render("Errors/{$status}", [
                'status' => $status,
            ])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
