<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\AppActivity;

use App\Http\Resources\PaginatedResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AppActivityCollection extends ResourceCollection implements PaginatedResourceCollection
{
    /**
     * @var string
     */
    public $collects = AppActivityResource::class;
}
