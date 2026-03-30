<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\AppActivity;

use App\Http\Requests\V1\BaseFormRequest;

class AppActivitySummaryRequest extends BaseFormRequest
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
            'start' => [
                'required',
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            'end' => [
                'required',
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            'time_entry_id' => [
                'sometimes',
                'uuid',
            ],
        ];
    }
}
