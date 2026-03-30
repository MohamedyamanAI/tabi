<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\AppActivity;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Validation\Rule;

class AppActivityIndexRequest extends BaseFormRequest
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
            'sort' => [
                'sometimes',
                'string',
                Rule::in(['timestamp', 'app_name', 'window_title', 'duration_seconds']),
            ],
            'direction' => [
                'sometimes',
                'string',
                Rule::in(['asc', 'desc']),
            ],
            'app_name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
