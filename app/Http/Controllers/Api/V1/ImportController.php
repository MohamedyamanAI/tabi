<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Import\ImportRequest;
use App\Models\Organization;
use App\Service\Import\Importers\ImporterContract;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\ImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    /**
     * Get information about available importers
     *
     * @operationId getImporters
     *
     * @throws AuthorizationException
     *
     * @response array{data: array<array{ key: string, name: string, description: string }>}
     */
    public function index(Organization $organization, ImporterProvider $importerProvider): JsonResponse
    {
        $this->checkPermission($organization, 'import');

        $importers = $importerProvider->getImporters();

        /** @var array<array{ key: string, name: string, description: string }> $importersResponse */
        $importersResponse = [];

        foreach ($importers as $key => $importerClass) {
            /** @var ImporterContract $importer */
            $importer = new $importerClass;
            $importersResponse[] = [
                'key' => $key,
                'name' => $importer->getName(),
                'description' => $importer->getDescription(),
            ];
        }

        return new JsonResponse([
            'data' => $importersResponse,
        ], 200);
    }

    /**
     * Import data into the organization
     *
     * @throws AuthorizationException
     *
     * @operationId importData
     */
    public function import(Organization $organization, ImportRequest $request, ImportService $importService): JsonResponse
    {
        $this->checkPermission($organization, 'import');

        $startTime = microtime(true);
        $userId = $this->user()->id;
        $importType = $request->input('type');
        $encodedDataSize = strlen($request->input('data'));
        
        try {
            Log::info('Import process started', [
                'organization_id' => $organization->id,
                'user_id' => $userId,
                'import_type' => $importType,
                'encoded_data_size_kb' => round($encodedDataSize / 1024, 2),
                'encoded_data_size_mb' => round($encodedDataSize / (1024 * 1024), 2),
            ]);

            $importData = base64_decode($request->input('data'), true);
            if ($importData === false) {
                Log::warning('Invalid base64 data received', [
                    'organization_id' => $organization->id,
                    'user_id' => $userId,
                    'import_type' => $importType,
                ]);

                return new JsonResponse([
                    'message' => 'Invalid base64 encoded data',
                ], 400);
            }

            $decodedSize = strlen($importData);
            Log::debug('Base64 data decoded successfully', [
                'organization_id' => $organization->id,
                'user_id' => $userId,
                'import_type' => $importType,
                'decoded_size_kb' => round($decodedSize / 1024, 2),
                'decoded_size_mb' => round($decodedSize / (1024 * 1024), 2),
            ]);

            $timezone = $this->user()->timezone;
            
            Log::debug('Starting import service', [
                'organization_id' => $organization->id,
                'user_id' => $userId,
                'import_type' => $importType,
                'timezone' => $timezone,
            ]);

            $report = $importService->import(
                $organization,
                $importType,
                $importData,
                $timezone
            );

            $duration = (microtime(true) - $startTime) * 1000;

            Log::info('Import process completed successfully', [
                'organization_id' => $organization->id,
                'user_id' => $userId,
                'import_type' => $importType,
                'duration_ms' => round($duration, 2),
                'report' => $report->toArray(),
            ]);

            return new JsonResponse([
                /** @var array{
                 *   clients: array{
                 *     created: int,
                 *   },
                 *   projects: array{
                 *     created: int,
                 *   },
                 *   tasks: array{
                 *     created: int,
                 *   },
                 *   time_entries: array{
                 *     created: int,
                 *   },
                 *   tags: array{
                 *     created: int,
                 *   },
                 *   users: array{
                 *     created: int,
                 *   }
                 * } $report Import report */
                'report' => $report->toArray(),
            ], 200);
        } catch (ImportException $exception) {
            $duration = (microtime(true) - $startTime) * 1000;

            Log::error('Import process failed with ImportException', [
                'organization_id' => $organization->id,
                'user_id' => $userId,
                'import_type' => $importType,
                'error_message' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
                'duration_ms' => round($duration, 2),
                'trace' => $exception->getTraceAsString(),
            ]);

            report($exception);

            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], 400);
        } catch (\Throwable $exception) {
            $duration = (microtime(true) - $startTime) * 1000;

            Log::error('Import process failed with unexpected exception', [
                'organization_id' => $organization->id,
                'user_id' => $userId,
                'import_type' => $importType,
                'error_message' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
                'error_class' => get_class($exception),
                'duration_ms' => round($duration, 2),
                'trace' => $exception->getTraceAsString(),
            ]);

            report($exception);

            return new JsonResponse([
                'message' => 'An unexpected error occurred during import',
            ], 500);
        }
    }
}
