<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CacheResponse
{
    /**
     * Agrega cabeceras Cache-Control a respuestas GET exitosas,
     * para que el navegador las reutilice sin volver a pedirlas al servidor.
     */
    public function handle(Request $request, Closure $next, string $maxAge = '60')
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $response->headers->set(
                'Cache-Control',
                "public, max-age={$maxAge}, stale-while-revalidate=120"
            );
        }

        return $response;
    }
}