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
        $middleware->alias([
            'ensure.user.active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        if (env('DUSK_ENABLED', false)) {
            $middleware->web(remove: [
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            ]);
        }

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

            try {
                $configs = \App\Models\SaasConfig::whereIn('key', ['company.email', 'company.phone', 'contact.whatsapp'])
                    ->pluck('value', 'key');
            } catch (\Throwable) {
                $configs = collect();
            }

            return Inertia::render("Errors/{$status}", [
                'status' => $status,
                'contact' => [
                    'email' => $configs['company.email'] ?? 'support@erprtrw.net',
                    'phone' => $configs['company.phone'] ?? '+62 851-2345-6789',
                    'whatsapp' => $configs['contact.whatsapp'] ?? '+62 812-3456-7890',
                ],
            ])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
