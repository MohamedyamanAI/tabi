<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectLargeActivityPayloads
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        if ($request->method() === 'POST' && (str_ends_with($path, 'app-activities') || str_ends_with($path, 'activity-samples'))) {
            $contentLength = (int) $request->header('Content-Length', 0);

            // Reject if Content-Length is larger than 500KB (~512000 bytes)
            if ($contentLength > 512000) {
                return response()->json([
                    'message' => 'The payload is too large. Please upgrade to a newer client version.',
                ], 422);
            }
        }

        return $next($request);
    }
}
