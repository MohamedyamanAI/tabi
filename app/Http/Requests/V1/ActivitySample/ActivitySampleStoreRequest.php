<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\ActivitySample;

use App\Http\Requests\V1\BaseFormRequest;

class ActivitySampleStoreRequest extends BaseFormRequest
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
            'samples' => [
                'required',
                'array',
                'min:1',
            ],
            'samples.*.timestamp' => [
                'required',
                'date',
            ],
            'samples.*.keystrokes' => [
                'required',
                'integer',
                'min:0',
            ],
            'samples.*.mouse_clicks' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}
