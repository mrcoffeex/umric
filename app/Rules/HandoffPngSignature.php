<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HandoffPngSignature implements ValidationRule
{
    public const MAX_BYTES = 524_288;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('The :attribute must be a valid signature.');

            return;
        }

        if (! str_starts_with($value, 'data:image/png;base64,')) {
            $fail('The :attribute must be a PNG data URL (draw in the box).');

            return;
        }

        $encoded = substr($value, strlen('data:image/png;base64,'));
        $raw = base64_decode($encoded, true);

        if ($raw === false) {
            $fail('The :attribute could not be read.');

            return;
        }

        if (strlen($raw) > self::MAX_BYTES) {
            $fail('The :attribute is too large.');

            return;
        }

        if (strlen($raw) < 8 || $raw[0] !== "\x89" || $raw[1] !== 'P' || $raw[2] !== 'N' || $raw[3] !== 'G') {
            $fail('The :attribute is not a valid PNG image.');

            return;
        }
    }
}
