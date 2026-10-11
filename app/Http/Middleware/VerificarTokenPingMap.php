<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarTokenPingMap
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperado = config('pingmap.api_token');
        $recebido = $request->bearerToken();

        if (
            !is_string($esperado) ||
            $esperado === '' ||
            !is_string($recebido) ||
            !hash_equals($esperado, $recebido)
        ) {
            return response()->json([
                'message' => 'Não autorizado.',
            ], 401);
        }

        return $next($request);
    }
}
