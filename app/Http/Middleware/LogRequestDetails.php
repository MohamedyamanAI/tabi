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
     * Query keys whose array length correlates with ValidationRuleParser wildcard cost.
     *
     * @var list<string>
     */
    private const FILTER_ARRAY_KEYS = [
        'member_ids',
        'client_ids',
        'project_ids',
        'tag_ids',
        'task_ids',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $diagnostics = $this->requestDiagnosticContext($request);

        $this->logIncomingRequest($request, $diagnostics);

        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000;
        $this->logOutgoingResponse($request, $response, $duration, $diagnostics);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestDiagnosticContext(Request $request): array
    {
        $method = $request->method();
        $path = $request->path();
        $queryString = $request->getQueryString() ?? '';

        $context = [
            'content_length_header' => $request->header('Content-Length'),
            'query_string_length' => strlen($queryString),
        ];

        foreach (self::FILTER_ARRAY_KEYS as $key) {
            if (! $request->has($key)) {
                continue;
            }
            $value = $request->input($key);
            $context[$key.'_count'] = is_array($value) ? count($value) : 1;
        }

        if ($request->isJson()) {
            $context['is_json'] = true;
        }

        if ($method === 'POST' && str_ends_with($path, 'app-activities')) {
            $context = array_merge($context, $this->appActivitiesPayloadContext($request));
        }

        if ($method === 'POST' && str_ends_with($path, 'activity-samples')) {
            $context = array_merge($context, $this->activitySamplesPayloadContext($request));
        }

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    private function appActivitiesPayloadContext(Request $request): array
    {
        $activities = $request->input('activities');
        return [
            'time_entry_id' => $request->input('time_entry_id'),
            'activities_count' => is_array($activities) ? count($activities) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function activitySamplesPayloadContext(Request $request): array
    {
        $samples = $request->input('samples');

        return [
            'time_entry_id' => $request->input('time_entry_id'),
            'samples_count' => is_array($samples) ? count($samples) : null,
        ];
    }



    /**
     * @param  array<string, mixed>  $diagnostics
     */
    private function logIncomingRequest(Request $request, array $diagnostics): void
    {
        $method = $request->method();
        $path = $request->path();
        $userId = auth()->id() ?? 'anonymous';

        $organization = request()->route('organization');
        $organizationId = null;
        if ($organization) {
            $organizationId = is_object($organization) && method_exists($organization, 'getKey')
                ? $organization->getKey()
                : (is_string($organization) ? $organization : null);
        }
        $organizationId = $organizationId ?? 'N/A';

        $input = $diagnostics;

        if ($request->isJson()) {
            $jsonSize = (int) $request->header('Content-Length', 0);
            $input['json_size_bytes'] = $jsonSize;

            if ($jsonSize > 10 * 1024 * 1024) {
                Log::warning('Large JSON payload detected', [
                    'endpoint' => "$method $path",
                    'user_id' => $userId,
                    'organization_id' => $organizationId,
                    'size_mb' => round($jsonSize / (1024 * 1024), 2),
                    'size_bytes' => $jsonSize,
                ]);
            }

            if (str_contains($path, 'import')) {
                $jsonInput = $request->json()->all();
                Log::info('Import request received', [
                    'endpoint' => "$method $path",
                    'user_id' => $userId,
                    'organization_id' => $organizationId,
                    'import_type' => $jsonInput['type'] ?? 'unknown',
                    'data_size_kb' => isset($jsonInput['data']) ? round(strlen($jsonInput['data']) / 1024, 2) : 0,
                    'data_size_mb' => isset($jsonInput['data']) ? round(strlen($jsonInput['data']) / (1024 * 1024), 2) : 0,
                    'content_length_header' => $jsonSize,
                ]);
            }
        }

        if (
            ($method === 'POST' && (str_ends_with($path, 'app-activities') || str_ends_with($path, 'activity-samples')))
            || (isset($diagnostics['activities_count']) && $diagnostics['activities_count'] > 100)
            || (isset($diagnostics['samples_count']) && $diagnostics['samples_count'] > 500)
        ) {
            Log::info('Activity upload request', [
                'method' => $method,
                'path' => $path,
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'diagnostics' => $diagnostics,
            ]);
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

    /**
     * @param  array<string, mixed>  $diagnostics
     */
    private function logOutgoingResponse(Request $request, Response $response, float $duration, array $diagnostics): void
    {
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();
        $userId = auth()->id() ?? 'anonymous';

        if ($duration > 1000) {
            Log::warning('Slow request detected', array_merge([
                'endpoint' => "$method $path",
                'user_id' => $userId,
                'status_code' => $statusCode,
                'duration_ms' => round($duration, 2),
            ], $diagnostics));
        }

        if ($statusCode >= 500) {
            $context = array_merge([
                'method' => $method,
                'path' => $path,
                'user_id' => $userId,
                'status_code' => $statusCode,
                'duration_ms' => round($duration, 2),
            ], $diagnostics);

            Log::error('Server error', $context);
        } elseif ($statusCode >= 400) {
            $context = array_merge([
                'method' => $method,
                'path' => $path,
                'user_id' => $userId,
                'status_code' => $statusCode,
                'duration_ms' => round($duration, 2),
            ], $diagnostics);

            if ($statusCode === 422 && $this->isActivityUploadPath($method, $path)) {
                $context['response_message'] = $this->extractResponseMessage($response);
            }

            if ($statusCode === 404 && str_ends_with($path, 'time-entries/active')) {
                Log::debug('No active timer', $context);
            } else {
                Log::warning('Request failed (client error)', $context);
            }
        }

        Log::debug('Outgoing response', [
            'method' => $method,
            'path' => $path,
            'status_code' => $statusCode,
            'user_id' => $userId,
            'duration_ms' => round($duration, 2),
        ]);
    }

    private function isActivityUploadPath(string $method, string $path): bool
    {
        if ($method !== 'POST') {
            return false;
        }

        return str_ends_with($path, 'app-activities') || str_ends_with($path, 'activity-samples');
    }

    private function extractResponseMessage(Response $response): ?string
    {
        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return null;
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded['message']) && is_string($decoded['message'])) {
            return $decoded['message'];
        }

        return null;
    }
}
