<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\ActivitySample;

use App\Http\Requests\V1\BaseFormRequest;

class ActivitySampleIndexRequest extends BaseFormRequest
{
    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'member_id' => [
                'uuid',
            ],
            'time_entry_id' => [
                'uuid',
            ],
            'start' => [
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            'end' => [
                'date_format:Y-m-d\TH:i:s\Z',
            ],
        ];
    }
}
