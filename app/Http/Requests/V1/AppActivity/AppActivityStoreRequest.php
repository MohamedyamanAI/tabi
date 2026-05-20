<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\AppActivity;

use App\Http\Requests\V1\BaseFormRequest;
use App\Rules\BulkAppActivitiesRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Exceptions\HttpResponseException;

class AppActivityStoreRequest extends BaseFormRequest
{
    /**
     * Reject oversized payloads before the validator expands wildcard rules.
     */
    protected function prepareForValidation(): void
    {
        $activities = $this->input('activities');

        if (! is_array($activities)) {
            return;
        }

        if (count($activities) > BulkAppActivitiesRule::MAX_ITEMS) {
            throw new HttpResponseException(response()->json([
                'message' => 'The activities must not have more than '.BulkAppActivitiesRule::MAX_ITEMS.' items.',
                'errors' => [
                    'activities' => [
                        'The activities must not have more than '.BulkAppActivitiesRule::MAX_ITEMS.' items.',
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
            'activities' => [
                'required',
                'array',
                new BulkAppActivitiesRule,
            ],
        ];
    }
}
