<?php

namespace App\Support;

use App\Models\EvaluationFormat;

/**
 * Merges config defaults with per-format `pdf_settings` JSON.
 */
final class EvaluationPdfSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $out = (array) config('evaluation_pdf.defaults', []);
        $out['rating_scale'] = (array) config('evaluation_pdf.default_rating_scale', []);

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public static function forFormat(?EvaluationFormat $format): array
    {
        $merged = self::defaults();
        if ($format?->pdf_settings === null) {
            return $merged;
        }
        $c = (array) $format->pdf_settings;
        if ($c === []) {
            return $merged;
        }

        foreach ([
            'enabled',
            'header_institution',
            'form_title',
            'form_subtitle',
            'show_rating_scale',
            'show_pass_fail',
            'show_signature_block',
            'passing_score',
        ] as $key) {
            if (array_key_exists($key, $c)) {
                $merged[$key] = $c[$key];
            }
        }

        if (isset($c['document']) && is_array($c['document'])) {
            $merged['document'] = array_replace(
                (array) ($merged['document'] ?? []),
                $c['document'],
            );
        }

        if (isset($c['rating_scale']) && is_array($c['rating_scale']) && $c['rating_scale'] !== []) {
            $merged['rating_scale'] = $c['rating_scale'];
        }

        if (isset($c['branches']) && is_array($c['branches']) && $c['branches'] !== []) {
            $merged['branches'] = $c['branches'];
        }

        return $merged;
    }

    public static function isEnabledForFormat(?EvaluationFormat $format): bool
    {
        if ($format === null) {
            return false;
        }

        $merged = self::forFormat($format);

        return (bool) ($merged['enabled'] ?? false);
    }
}
