<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Di produksi, alihkan http:// ke https:// (mengenali header dari Cloudflare/proxy). */
class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (config('app.env') === 'production') {
            $proto = strtolower((string) $request->header('X-Forwarded-Proto'));
            $aman = $request->isSecure() || $proto === 'https';
            if (!$aman && $request->isMethodSafe()) {
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }
        return $next($request);
    }
}
