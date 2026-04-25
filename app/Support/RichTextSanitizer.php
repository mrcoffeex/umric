<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Strips dangerous markup from admin-entered rich text (TipTap HTML).
 */
final class RichTextSanitizer
{
    public static function sanitize(string $html): string
    {
        return self::htmlSanitizer()->sanitize($html);
    }

    private static function htmlSanitizer(): HtmlSanitizer
    {
        static $sanitizer;

        if (! $sanitizer instanceof HtmlSanitizer) {
            $config = (new HtmlSanitizerConfig)
                ->allowStaticElements()
                ->allowRelativeLinks()
                ->allowLinkSchemes(['http', 'https', 'mailto'])
                ->withMaxInputLength(100_000);
            $sanitizer = new HtmlSanitizer($config);
        }

        return $sanitizer;
    }
}
