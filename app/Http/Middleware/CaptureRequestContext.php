<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CaptureRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->has('request_id')) {
            $request->attributes->set('request_id', (string) Str::ulid());
        }

        if (! $request->attributes->has('trace_id')) {
            $request->attributes->set('trace_id', (string) Str::ulid());
        }

        if ($request->hasSession()) {
            $request->attributes->set('session_id', $request->session()->getId());
        }

        return $next($request);
    }
}
