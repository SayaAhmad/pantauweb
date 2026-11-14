<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotAdmin
{
    /**
     * Admin hanya mengelola pengguna, bukan monitoring website.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->is_admin, 403);

        return $next($request);
    }
}
