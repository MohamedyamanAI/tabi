<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Screenshot;

use App\Http\Requests\V1\BaseFormRequest;

class ScreenshotStoreRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'screenshot' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,webp',
                'max:2048',
            ],
            'time_entry_id' => [
                'required',
                'uuid',
                'exists:time_entries,id',
            ],
            'captured_at' => [
                'required',
                'date',
            ],
        ];
    }
}
