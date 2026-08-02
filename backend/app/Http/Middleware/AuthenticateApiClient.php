<?php

namespace App\Http\Middleware;

use App\Services\ApiClientService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    public function __construct(
        private readonly ApiClientService $service
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        ?string $requiredScope = null
    ): Response {
        $publicKey = (string) $request->header('X-API-Key');
        $secret = (string) $request->header('X-API-Secret');

        abort_if(
            $publicKey === '' || $secret === '',
            401,
            'API credentials are required.'
        );

        try {
            $client = $this->service->authenticate(
                $publicKey,
                $secret
            );
        } catch (\RuntimeException $exception) {
            abort(401, $exception->getMessage());
        }

        $scopes = json_decode($client->scopes, true) ?: [];

        if ($requiredScope && ! in_array($requiredScope, $scopes, true)) {
            abort(403, 'API client lacks the required scope.');
        }

        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
