<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\ActivitySample;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ActivitySampleCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = ActivitySampleResource::class;
}
