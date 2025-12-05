<?php
/**
 * VanLife News Aggregator - Moderation Rules Configuration
 */

declare(strict_types=1);

return [
    // Words/phrases that require manual moderation
    // Context might be legitimate (e.g., border control)
    'require_moderation' => [
        // Drugs (context might be legitimate - border checks)
        'наркотик', 'drug', 'droga', 'Drogen', 'narcotic',
        'марихуана', 'marijuana', 'cannabis', 'конопля',
        'кокаин', 'cocaine', 'героин', 'heroin',

        // Smuggling
        'контрабанда', 'smuggling', 'contrebande', 'Schmuggel',

        // Sexual content
        'секс', 'sex', 'эротик', 'erotic', 'порно', 'porn',
        'интим', 'intimate', 'проститу', 'prostitut',

        // Violence
        'убийство', 'murder', 'meurtre', 'Mord',
        'изнасилован', 'rape', 'viol',
        'похищен', 'kidnap', 'enlèvement',

        // Weapons
        'оружие', 'weapon', 'arme', 'Waffe',
        'взрывчат', 'explosive', 'bomb',
    ],

    // Words/phrases for automatic rejection
    'auto_reject' => [
        // Clearly irrelevant content
        'рецепт наркотик', 'drug recipe', 'how to make drugs',
        'sex in camper', 'секс в автодоме', 'sex im wohnmobil',
        'escort', 'эскорт',
        'dating', 'знакомства',
    ],

    // Minimum relevance score (0-100)
    // Articles below this score are rejected
    'min_relevance_score' => 30,

    // Auto-publish if score is above this value
    // Articles with score >= auto_publish_score are published automatically
    'auto_publish_score' => 70,

    // Score between min_relevance_score and auto_publish_score
    // goes to manual moderation

    // Maximum articles per source per day (to prevent spam)
    'max_articles_per_source_per_day' => 50,

    // Duplicate detection settings
    'duplicate_detection' => [
        'enabled' => true,
        'title_similarity_threshold' => 0.85,
        'url_check' => true,
    ],
];
