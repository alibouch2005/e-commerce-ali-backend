<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MoroccoTrafficMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! filter_var(env('BLOCK_NON_MOROCCO_TRAFFIC', true), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        $country = strtoupper((string) (
            $request->headers->get('CF-IPCountry')
            ?: $request->headers->get('X-Vercel-IP-Country')
            ?: $request->headers->get('X-AppEngine-Country')
            ?: $request->headers->get('CloudFront-Viewer-Country')
            ?: $request->headers->get('X-Country-Code')
        ));

        if ($country !== '' && $country !== 'MA') {
            return response()->json([
                'message' => 'Acces autorise uniquement depuis le Maroc.',
            ], 403);
        }

        if ($this->isLocalOrPrivateIp($request->ip())) {
            return $next($request);
        }

        return $next($request);
    }

    private function isLocalOrPrivateIp(?string $ip): bool
    {
        if (! $ip) {
            return true;
        }

        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
