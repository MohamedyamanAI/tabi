<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\ActivitySample;

use App\Http\Resources\V1\BaseResource;
use App\Models\ActivitySample;
use Illuminate\Http\Request;

/**
 * @property ActivitySample $resource
 */
class ActivitySampleResource extends BaseResource
{
    /**
     * @return array<string, string|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'timestamp' => $this->formatDateTime($this->resource->timestamp),
            'keystrokes' => $this->resource->keystrokes,
            'mouse_clicks' => $this->resource->mouse_clicks,
            'time_entry_id' => $this->resource->time_entry_id,
            'member_id' => $this->resource->member_id,
            'created_at' => $this->formatDateTime($this->resource->created_at),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
