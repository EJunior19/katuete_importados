<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoopMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request); // no hace nada
    }
}
