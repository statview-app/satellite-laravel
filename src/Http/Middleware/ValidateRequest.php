<?php

namespace Statview\Satellite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateRequest
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = config('statview.api_key');

        abort_if(
            boolean: empty($apiKey),
            code: 403
        );

        abort_unless(
            boolean: hash_equals((string) $apiKey, (string) $request->bearerToken()),
            code: 403
        );

        return $next($request);
    }
}
