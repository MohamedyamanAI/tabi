<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (ValidationException $e): void {
            // Log validation failures
            Log::warning('Validation exception occurred', [
                'endpoint' => request()->method() . ' ' . request()->path(),
                'user_id' => auth()->id() ?? 'anonymous',
                'organization_id' => request()->route('organization') ?? 'N/A',
                'errors' => $e->errors(),
                'input_keys' => array_keys(request()->all()),
                'input_size_kb' => round(strlen(json_encode(request()->all(), JSON_THROW_ON_ERROR)) / 1024, 2),
            ]);
        });

        $this->reportable(function (Throwable $e): void {
            //
        });
    }

    public function render($request, Throwable $e): Response|RedirectResponse
    {
        $response = parent::render($request, $e);

        if ($response->getStatusCode() === 419) {
            return back()->with([
                'message' => 'The page expired, please try again.',
            ]);
        }

        return $response;
    }
}
