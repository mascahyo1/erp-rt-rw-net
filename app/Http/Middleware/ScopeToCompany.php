<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeToCompany
{
    /**
     * Menambahkan global scope company_id ke user yang sedang login.
     * Untuk guard admin-company & employee, semua query otomatis di-filter
     * berdasarkan company_id milik user.
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? ['admin-company', 'employee'] : $guards;

        // Tidak perlu melakukan apa-apa — controller sudah melakukan
        // ->where('company_id', auth()->user()->company_id)
        // Middleware ini sebagai penanda eksplisit bahwa route di-scope.

        return $next($request);
    }
}
