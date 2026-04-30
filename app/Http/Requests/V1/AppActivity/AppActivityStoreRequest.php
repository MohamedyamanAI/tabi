<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\AppActivity;

use App\Http\Requests\V1\BaseFormRequest;

class AppActivityStoreRequest extends BaseFormRequest
{
    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\Rule>>
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
                'min:1',
                'max:300',
            ],
            'activities.*.timestamp' => [
                'bail',
                'required',
                'date',
            ],
            'activities.*.app_name' => [
                'bail',
                'required',
                'string',
                'max:255',
            ],
            'activities.*.window_title' => [
                'bail',
                'required',
                'string',
            ],
            'activities.*.url' => [
                'bail',
                'nullable',
                'string',
            ],
            'activities.*.duration_seconds' => [
                'bail',
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}
