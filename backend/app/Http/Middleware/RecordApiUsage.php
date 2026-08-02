<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RecordApiUsage
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $started = hrtime(true);
        $response = $next($request);

        $client = $request->attributes->get('api_client');

        if ($client) {
            DB::table('developer.api_usage')->insert([
                'api_client_id' => $client->id,
                'method' => $request->method(),
                'route' => $request->route()?->uri() ?? $request->path(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                'request_bytes' => strlen((string) $request->getContent()),
                'response_bytes' => strlen((string) $response->getContent()),
                'ip_hash' => hash('sha256', (string) $request->ip()),
                'occurred_at' => now(),
            ]);
        }

        return $response;
    }
}
