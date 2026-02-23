<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Screenshot;

use App\Http\Requests\V1\BaseFormRequest;

class ScreenshotIndexRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|\Illuminate\Contracts\Validation\Rule>>
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
