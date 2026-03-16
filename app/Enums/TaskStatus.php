<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Active = 'active';
    case Review = 'for_review';
    case Later = 'for_later';
    case Cancelled = 'cancelled';
    case Done = 'done';
}
