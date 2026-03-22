<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class NoMembersAvailableApiException extends ApiException
{
    public const string KEY = 'no_members_available';
}
