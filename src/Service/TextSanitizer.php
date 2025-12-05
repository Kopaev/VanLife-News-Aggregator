<?php

namespace App\Service;

class TextSanitizer
{
    public static function sanitize(string $text): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $stripped = strip_tags($decoded);
        $collapsed = preg_replace('/\s+/u', ' ', $stripped) ?? '';

        return trim($collapsed);
    }

    public static function limit(string $text, int $maxLength): string
    {
        $clean = self::sanitize($text);

        if (mb_strlen($clean) <= $maxLength) {
            return $clean;
        }

        return mb_substr($clean, 0, $maxLength);
    }
}
