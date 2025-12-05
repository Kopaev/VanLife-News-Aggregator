<?php

namespace App\Helper;

use App\Service\TextSanitizer;

/**
 * Helper class for generating URL-friendly slugs
 *
 * Supports Cyrillic transliteration and ensures uniqueness
 */
class SlugHelper
{
    /**
     * Cyrillic to Latin transliteration map
     */
    private const TRANSLITERATION_MAP = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
        'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
        'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
        'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch',
        'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        // Ukrainian
        'і' => 'i', 'ї' => 'yi', 'є' => 'ye', 'ґ' => 'g',
        'І' => 'I', 'Ї' => 'Yi', 'Є' => 'Ye', 'Ґ' => 'G',
        // German special characters
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
        'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue',
    ];

    /**
     * Maximum slug length
     */
    private const MAX_LENGTH = 200;

    /**
     * Generate a URL-friendly slug from text
     *
     * @param string $text Source text (can be in any language)
     * @param string $fallback Fallback slug if text is empty
     * @return string Generated slug
     */
    public static function slugify(string $text, string $fallback = 'article'): string
    {
        // Sanitize: remove HTML entities and extra spaces
        $text = TextSanitizer::sanitize($text);

        // Transliterate Cyrillic and special characters
        $text = strtr($text, self::TRANSLITERATION_MAP);

        // Try iconv for remaining characters
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }

        // Convert to lowercase
        $text = strtolower($text);

        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';

        // Remove leading/trailing hyphens
        $text = trim($text, '-');

        // Collapse multiple hyphens
        $text = preg_replace('/-+/', '-', $text) ?? '';

        // Truncate to max length at word boundary
        if (strlen($text) > self::MAX_LENGTH) {
            $text = substr($text, 0, self::MAX_LENGTH);
            // Try to cut at last hyphen to avoid cutting words
            $lastHyphen = strrpos($text, '-');
            if ($lastHyphen !== false && $lastHyphen > self::MAX_LENGTH / 2) {
                $text = substr($text, 0, $lastHyphen);
            }
        }

        // Return fallback if empty
        if ($text === '') {
            return $fallback;
        }

        return $text;
    }

    /**
     * Generate slug with article ID prefix for guaranteed uniqueness
     *
     * Format: {id}-{slug}
     * Example: "123-munich-bans-overnight-rv-parking"
     *
     * @param int $articleId Article ID
     * @param string $title Article title
     * @return string Generated slug with ID prefix
     */
    public static function generateWithId(int $articleId, string $title): string
    {
        $baseSlug = self::slugify($title);

        // Ensure the combined length doesn't exceed max
        $idPrefix = $articleId . '-';
        $maxBaseLength = self::MAX_LENGTH - strlen($idPrefix);

        if (strlen($baseSlug) > $maxBaseLength) {
            $baseSlug = substr($baseSlug, 0, $maxBaseLength);
            // Try to cut at last hyphen
            $lastHyphen = strrpos($baseSlug, '-');
            if ($lastHyphen !== false && $lastHyphen > $maxBaseLength / 2) {
                $baseSlug = substr($baseSlug, 0, $lastHyphen);
            }
        }

        return $idPrefix . $baseSlug;
    }

    /**
     * Check if a string is a valid slug format
     *
     * @param string $slug Slug to validate
     * @return bool True if valid slug format
     */
    public static function isValidSlug(string $slug): bool
    {
        // Must be non-empty, only lowercase letters, numbers, and hyphens
        // Cannot start or end with hyphen, no consecutive hyphens
        return (bool) preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug);
    }
}
