<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;

class BulkAppActivitiesRule implements ValidationRule
{
    public const MAX_ITEMS = 300;

    private const MAX_APP_NAME_LENGTH = 255;

    private const MAX_WINDOW_TITLE_LENGTH = 8192;

    private const MAX_URL_LENGTH = 2048;

    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail(__('validation.array'));

            return;
        }

        $count = count($value);
        if ($count < 1) {
            $fail(__('validation.min.array', ['min' => 1]));

            return;
        }

        if ($count > self::MAX_ITEMS) {
            $fail(__('validation.max.array', ['max' => self::MAX_ITEMS]));

            return;
        }

        foreach ($value as $index => $row) {
            $prefix = "{$attribute}.{$index}";

            if (! is_array($row)) {
                $fail(__('validation.array'));

                continue;
            }

            if (! array_key_exists('timestamp', $row) || $row['timestamp'] === null || $row['timestamp'] === '') {
                $fail(__('validation.required', ['attribute' => "{$prefix}.timestamp"]));

                continue;
            }

            if (! $this->isValidDate($row['timestamp'])) {
                $fail(__('validation.date', ['attribute' => "{$prefix}.timestamp"]));
            }

            if (! array_key_exists('app_name', $row) || ! is_string($row['app_name']) || $row['app_name'] === '') {
                $fail(__('validation.required', ['attribute' => "{$prefix}.app_name"]));
            } elseif (strlen($row['app_name']) > self::MAX_APP_NAME_LENGTH) {
                $fail(__('validation.max.string', [
                    'attribute' => "{$prefix}.app_name",
                    'max' => self::MAX_APP_NAME_LENGTH,
                ]));
            }

            if (! array_key_exists('window_title', $row) || ! is_string($row['window_title']) || $row['window_title'] === '') {
                $fail(__('validation.required', ['attribute' => "{$prefix}.window_title"]));
            } elseif (strlen($row['window_title']) > self::MAX_WINDOW_TITLE_LENGTH) {
                $fail(__('validation.max.string', [
                    'attribute' => "{$prefix}.window_title",
                    'max' => self::MAX_WINDOW_TITLE_LENGTH,
                ]));
            }

            if (array_key_exists('url', $row) && $row['url'] !== null) {
                if (! is_string($row['url'])) {
                    $fail(__('validation.string', ['attribute' => "{$prefix}.url"]));
                } elseif (strlen($row['url']) > self::MAX_URL_LENGTH) {
                    $fail(__('validation.max.string', [
                        'attribute' => "{$prefix}.url",
                        'max' => self::MAX_URL_LENGTH,
                    ]));
                }
            }

            if (! array_key_exists('duration_seconds', $row)) {
                $fail(__('validation.required', ['attribute' => "{$prefix}.duration_seconds"]));
            } elseif (! $this->isNonNegativeInteger($row['duration_seconds'])) {
                $fail(__('validation.integer', ['attribute' => "{$prefix}.duration_seconds"]));
            }
        }
    }

    private function isValidDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        try {
            Carbon::parse($value);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function isNonNegativeInteger(mixed $value): bool
    {
        if (is_int($value)) {
            return $value >= 0;
        }

        if (is_string($value) && ctype_digit($value)) {
            return true;
        }

        return is_numeric($value) && (int) $value == $value && (int) $value >= 0;
    }
}
