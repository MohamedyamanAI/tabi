<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestDetails
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log incoming request
        $this->logIncomingRequest($request);

        // Register shutdown handler to catch fatal errors with request context
        register_shutdown_function(function () use ($request) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                Log::error('Fatal error during request', [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'user_id' => auth()->id() ?? 'anonymous',
                    'organization_id' => request()->route('organization') ?? 'N/A',
                    'error_type' => $error['type'],
                    'error_message' => $error['message'],
                    'error_file' => $error['file'],
                    'error_line' => $error['line'],
                ]);
            }
        });

        $response = $next($request);

        // Log response and timing
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $this->logOutgoingResponse($request, $response, $duration);

        return $response;
    }

    private function logIncomingRequest(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();
        $contentLength = $request->header('Content-Length') ?? 'unknown';
        $userId = auth()->id() ?? 'anonymous';
        $organizationId = request()->route('organization') ?? 'N/A';

        $input = [];
        if ($request->isJson()) {
            $jsonInput = $request->json()->all();
            // Get size of serialized data
            $jsonSize = strlen(json_encode($jsonInput, JSON_THROW_ON_ERROR));
            $input = [
                'content_length_header' => $contentLength,
                'json_size_bytes' => $jsonSize,
            ];

            // Log large payloads warning
            if ($jsonSize > 10 * 1024 * 1024) { // 10MB threshold
                Log::warning('Large JSON payload detected', [
                    'endpoint' => "$method $path",
                    'user_id' => $userId,
                    'organization_id' => $organizationId,
                    'size_mb' => round($jsonSize / (1024 * 1024), 2),
                    'size_bytes' => $jsonSize,
                    'has_data_field' => isset($jsonInput['data']),
                    'data_field_size_kb' => isset($jsonInput['data']) ? round(strlen($jsonInput['data']) / 1024, 2) : null,
                ]);
            }

            // Special logging for import endpoint
            if (str_contains($path, 'import')) {
                Log::info('Import request received', [
                    'endpoint' => "$method $path",
                    'user_id' => $userId,
                    'organization_id' => $organizationId,
                    'import_type' => $jsonInput['type'] ?? 'unknown',
                    'data_size_kb' => isset($jsonInput['data']) ? round(strlen($jsonInput['data']) / 1024, 2) : 0,
                    'data_size_mb' => isset($jsonInput['data']) ? round(strlen($jsonInput['data']) / (1024 * 1024), 2) : 0,
                    'content_length_header' => $contentLength,
                ]);
            }
        } else {
            $input['content_length_header'] = $contentLength;
        }

        Log::info('Incoming request', [
            'method' => $method,
            'path' => $path,
            'user_id' => $userId,
            'organization_id' => $organizationId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'input_details' => $input,
        ]);
    }

    private function logOutgoingResponse(Request $request, Response $response, float $duration): void
    {
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();
        $userId = auth()->id() ?? 'anonymous';

        // Log slow requests (>1 second)
        if ($duration > 1000) {
            Log::warning('Slow request detected', [
                'endpoint' => "$method $path",
                'user_id' => $userId,
                'status_code' => $statusCode,
                'duration_ms' => round($duration, 2),
            ]);
        }

        // Log errors
        if ($statusCode >= 400) {
            Log::error('Request failed', [
                'method' => $method,
                'path' => $path,
                'user_id' => $userId,
                'status_code' => $statusCode,
                'duration_ms' => round($duration, 2),
            ]);
        }

        Log::debug('Outgoing response', [
            'method' => $method,
            'path' => $path,
            'status_code' => $statusCode,
            'user_id' => $userId,
            'duration_ms' => round($duration, 2),
        ]);
    }
}
