<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Screenshot;

use App\Http\Resources\V1\BaseResource;
use App\Models\Screenshot;
use Illuminate\Http\Request;

/**
 * @property Screenshot $resource
 */
class ScreenshotResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID */
            'id' => $this->resource->id,
            /** @var string $time_entry_id Time entry ID */
            'time_entry_id' => $this->resource->time_entry_id,
            /** @var string $member_id Member ID */
            'member_id' => $this->resource->member_id,
            /** @var string $captured_at Captured at */
            'captured_at' => $this->formatDateTime($this->resource->captured_at),
            /** @var string $image_url Temporary image URL */
            'image_url' => $this->resource->getTemporaryUrl(),
            /** @var string|null $created_at Created at */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** @var string|null $updated_at Updated at */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
