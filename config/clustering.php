<?php

return [
    // Минимальный балл схожести (0..1), при котором статьи считаются связанными
    'min_similarity' => (float)env('CLUSTER_MIN_SIMILARITY', 0.55),

    // Сколько часов назад учитывать публикации в окне кандидатов
    'candidate_window_hours' => (int)env('CLUSTER_CANDIDATE_WINDOW_HOURS', 120),

    // Полураспад веса при разнице во времени публикации
    'time_decay_hours' => (int)env('CLUSTER_TIME_DECAY_HOURS', 72),

    // Ограничения на длину текста для расчёта схожести
    'limits' => [
        'summary_chars' => (int)env('CLUSTER_SUMMARY_LIMIT', 800),
    ],

    // Весовые коэффициенты компонентов
    'weights' => [
        'title' => 0.6,
        'summary' => 0.25,
        'tags' => 0.1,
        'meta_bonus' => 0.05,
    ],

    // Стоп-слова для грубой токенизации (минимальный набор en/ru)
    'stopwords' => [
        'en' => [
            'the', 'and', 'or', 'a', 'an', 'of', 'to', 'in', 'on', 'for', 'with', 'about', 'at', 'by',
        ],
        'ru' => [
            'и', 'в', 'во', 'на', 'с', 'со', 'о', 'об', 'за', 'для', 'по', 'от', 'что', 'это', 'как',
        ],
    ],
];
