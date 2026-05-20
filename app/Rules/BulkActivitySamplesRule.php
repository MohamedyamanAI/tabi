<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;

class BulkActivitySamplesRule implements ValidationRule
{
    public const MAX_ITEMS = 1000;

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

            if (! array_key_exists('keystrokes', $row)) {
                $fail(__('validation.required', ['attribute' => "{$prefix}.keystrokes"]));
            } elseif (! $this->isNonNegativeInteger($row['keystrokes'])) {
                $fail(__('validation.integer', ['attribute' => "{$prefix}.keystrokes"]));
            }

            if (! array_key_exists('mouse_clicks', $row)) {
                $fail(__('validation.required', ['attribute' => "{$prefix}.mouse_clicks"]));
            } elseif (! $this->isNonNegativeInteger($row['mouse_clicks'])) {
                $fail(__('validation.integer', ['attribute' => "{$prefix}.mouse_clicks"]));
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
