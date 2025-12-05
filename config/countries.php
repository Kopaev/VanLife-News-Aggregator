<?php
/**
 * VanLife News Aggregator - Countries Configuration
 *
 * This file contains country-related configuration.
 * For full country data, see sql/seeds/countries.sql
 */

declare(strict_types=1);

return [
    // Priority countries (shown first in filters)
    'priority' => [
        'DE', 'FR', 'ES', 'IT', 'PT', 'NL', 'AT', 'CH',
        'GB', 'US', 'AU', 'NZ', 'CA',
        'SE', 'NO', 'DK', 'FI',
        'PL', 'CZ',
        'RU',
    ],

    // Regions for grouping
    'regions' => [
        'western_europe' => [
            'name_ru' => 'Западная Европа',
            'name_en' => 'Western Europe',
            'countries' => ['DE', 'FR', 'NL', 'BE', 'AT', 'CH', 'GB', 'IE'],
        ],
        'southern_europe' => [
            'name_ru' => 'Южная Европа',
            'name_en' => 'Southern Europe',
            'countries' => ['ES', 'IT', 'PT', 'GR', 'HR', 'SI'],
        ],
        'northern_europe' => [
            'name_ru' => 'Северная Европа',
            'name_en' => 'Northern Europe',
            'countries' => ['SE', 'NO', 'DK', 'FI', 'IS'],
        ],
        'eastern_europe' => [
            'name_ru' => 'Восточная Европа',
            'name_en' => 'Eastern Europe',
            'countries' => ['PL', 'CZ', 'SK', 'HU', 'RO', 'BG', 'UA', 'BY', 'RU'],
        ],
        'north_america' => [
            'name_ru' => 'Северная Америка',
            'name_en' => 'North America',
            'countries' => ['US', 'CA', 'MX'],
        ],
        'oceania' => [
            'name_ru' => 'Океания',
            'name_en' => 'Oceania',
            'countries' => ['AU', 'NZ'],
        ],
        'asia' => [
            'name_ru' => 'Азия',
            'name_en' => 'Asia',
            'countries' => ['JP', 'CN', 'KR', 'TH', 'VN', 'MY', 'ID', 'IN', 'TR'],
        ],
    ],

    // Language to country mapping (for news without explicit country)
    'language_to_country' => [
        'de' => 'DE',
        'fr' => 'FR',
        'es' => 'ES',
        'it' => 'IT',
        'pt' => 'PT',
        'nl' => 'NL',
        'sv' => 'SE',
        'no' => 'NO',
        'da' => 'DK',
        'fi' => 'FI',
        'pl' => 'PL',
        'cs' => 'CZ',
        'ja' => 'JP',
        'ko' => 'KR',
        'ru' => 'RU',
        'uk' => 'UA',
        'tr' => 'TR',
    ],
];
