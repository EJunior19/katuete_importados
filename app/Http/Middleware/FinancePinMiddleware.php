<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FinancePinMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Excepciones: rutas que no piden PIN
        if ($request->routeIs(['finance.pin', 'finance.pin.verify', 'finance.lock'])) {
            return $next($request);
        }

        $ok   = $request->session()->get('finance_pin_ok');
        $time = $request->session()->get('finance_pin_time');
        $ttl  = config('finance.ttl', 30) * 60; // 30 minutos por defecto

        if ($ok && (time() - $time) <= $ttl) {
            return $next($request);
        }

        $request->session()->forget(['finance_pin_ok', 'finance_pin_time']);

        return redirect()->route('finance.pin')->with('warn', 'Ingresá el PIN para acceder.');
    }
}
