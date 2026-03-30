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
            ],
            'activities.*.timestamp' => [
                'required',
                'date',
            ],
            'activities.*.app_name' => [
                'required',
                'string',
                'max:255',
            ],
            'activities.*.window_title' => [
                'required',
                'string',
            ],
            'activities.*.url' => [
                'nullable',
                'string',
            ],
            'activities.*.duration_seconds' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}
