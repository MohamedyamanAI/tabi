<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\AppActivity;

use App\Http\Resources\V1\BaseResource;
use App\Models\AppActivity;
use Illuminate\Http\Request;

/**
 * @property AppActivity $resource
 */
class AppActivityResource extends BaseResource
{
    /**
     * @return array<string, string|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'timestamp' => $this->formatDateTime($this->resource->timestamp),
            'app_name' => $this->resource->app_name,
            'window_title' => $this->resource->window_title,
            'url' => $this->resource->url,
            'duration_seconds' => $this->resource->duration_seconds,
            'time_entry_id' => $this->resource->time_entry_id,
            'member_id' => $this->resource->member_id,
            'created_at' => $this->formatDateTime($this->resource->created_at),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
