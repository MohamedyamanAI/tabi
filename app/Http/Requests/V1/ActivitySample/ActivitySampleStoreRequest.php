<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\ActivitySample;

use App\Http\Requests\V1\BaseFormRequest;
use App\Rules\BulkActivitySamplesRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Exceptions\HttpResponseException;

class ActivitySampleStoreRequest extends BaseFormRequest
{
    /**
     * Reject oversized payloads before the validator expands wildcard rules.
     */
    protected function prepareForValidation(): void
    {
        $samples = $this->input('samples');

        if (! is_array($samples)) {
            return;
        }

        if (count($samples) > BulkActivitySamplesRule::MAX_ITEMS) {
            throw new HttpResponseException(response()->json([
                'message' => 'The samples must not have more than '.BulkActivitySamplesRule::MAX_ITEMS.' items.',
                'errors' => [
                    'samples' => [
                        'The samples must not have more than '.BulkActivitySamplesRule::MAX_ITEMS.' items.',
                    ],
                ],
            ], 422));
        }
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'time_entry_id' => [
                'required',
                'uuid',
                'exists:time_entries,id',
            ],
            'samples' => [
                'required',
                'array',
                new BulkActivitySamplesRule,
            ],
        ];
    }
}
