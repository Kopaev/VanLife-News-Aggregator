<?php
/**
 * VanLife News Aggregator - Categories Configuration
 */

declare(strict_types=1);

return [
    'law' => [
        'name_ru' => 'Законы и правила',
        'name_en' => 'Laws & Rules',
        'icon' => '📜',
        'color' => '#3498db',
        'priority' => 10,
        'keywords' => [
            'en' => ['law', 'legislation', 'regulation', 'rule', 'policy', 'ordinance'],
            'ru' => ['закон', 'законодательство', 'регулирование', 'правило', 'политика'],
            'de' => ['Gesetz', 'Verordnung', 'Regelung', 'Vorschrift'],
            'fr' => ['loi', 'législation', 'réglementation', 'règle'],
            'es' => ['ley', 'legislación', 'regulación', 'norma'],
        ],
    ],

    'ban' => [
        'name_ru' => 'Запреты и штрафы',
        'name_en' => 'Bans & Fines',
        'icon' => '🚫',
        'color' => '#e74c3c',
        'priority' => 9,
        'keywords' => [
            'en' => ['ban', 'fine', 'penalty', 'prohibition', 'forbidden', 'illegal'],
            'ru' => ['запрет', 'штраф', 'запрещено', 'нарушение', 'нельзя'],
            'de' => ['Verbot', 'Strafe', 'Bußgeld', 'verboten'],
            'fr' => ['interdiction', 'amende', 'interdit', 'sanction'],
            'es' => ['prohibición', 'multa', 'prohibido', 'sanción'],
        ],
    ],

    'opening' => [
        'name_ru' => 'Открытия',
        'name_en' => 'Openings',
        'icon' => '🎉',
        'color' => '#2ecc71',
        'priority' => 8,
        'keywords' => [
            'en' => ['opening', 'open', 'new', 'launch', 'debut', 'inaugurate'],
            'ru' => ['открытие', 'открывается', 'новый', 'запуск'],
            'de' => ['Eröffnung', 'eröffnet', 'neu', 'Start'],
            'fr' => ['ouverture', 'ouvre', 'nouveau', 'inauguration'],
            'es' => ['apertura', 'abre', 'nuevo', 'inauguración'],
        ],
    ],

    'closing' => [
        'name_ru' => 'Закрытия',
        'name_en' => 'Closings',
        'icon' => '🔒',
        'color' => '#95a5a6',
        'priority' => 7,
        'keywords' => [
            'en' => ['closing', 'closed', 'shutdown', 'closure'],
            'ru' => ['закрытие', 'закрывается', 'закрыто'],
            'de' => ['Schließung', 'geschlossen', 'schließt'],
            'fr' => ['fermeture', 'fermé', 'ferme'],
            'es' => ['cierre', 'cerrado', 'cierra'],
        ],
    ],

    'incident' => [
        'name_ru' => 'Происшествия',
        'name_en' => 'Incidents',
        'icon' => '⚠️',
        'color' => '#f39c12',
        'priority' => 6,
        'keywords' => [
            'en' => ['incident', 'accident', 'crash', 'fire', 'theft', 'emergency'],
            'ru' => ['происшествие', 'авария', 'пожар', 'кража', 'ДТП'],
            'de' => ['Unfall', 'Brand', 'Diebstahl', 'Vorfall'],
            'fr' => ['accident', 'incendie', 'vol', 'incident'],
            'es' => ['accidente', 'incendio', 'robo', 'incidente'],
        ],
    ],

    'festival' => [
        'name_ru' => 'Фестивали',
        'name_en' => 'Festivals',
        'icon' => '🎪',
        'color' => '#9b59b6',
        'priority' => 5,
        'keywords' => [
            'en' => ['festival', 'event', 'rally', 'gathering', 'meetup', 'convention'],
            'ru' => ['фестиваль', 'событие', 'слёт', 'встреча', 'конвенция'],
            'de' => ['Festival', 'Treffen', 'Veranstaltung'],
            'fr' => ['festival', 'événement', 'rassemblement'],
            'es' => ['festival', 'evento', 'encuentro'],
        ],
    ],

    'expo' => [
        'name_ru' => 'Выставки',
        'name_en' => 'Exhibitions',
        'icon' => '🏛️',
        'color' => '#1abc9c',
        'priority' => 4,
        'keywords' => [
            'en' => ['expo', 'exhibition', 'show', 'trade fair', 'display'],
            'ru' => ['выставка', 'экспо', 'шоу', 'ярмарка'],
            'de' => ['Messe', 'Ausstellung', 'Caravan Salon'],
            'fr' => ['salon', 'exposition', 'foire'],
            'es' => ['feria', 'exposición', 'salón'],
        ],
    ],

    'industry' => [
        'name_ru' => 'Индустрия',
        'name_en' => 'Industry',
        'icon' => '🏭',
        'color' => '#34495e',
        'priority' => 3,
        'keywords' => [
            'en' => ['industry', 'manufacturer', 'production', 'market', 'sales', 'company'],
            'ru' => ['индустрия', 'производитель', 'рынок', 'продажи', 'компания'],
            'de' => ['Industrie', 'Hersteller', 'Markt', 'Verkauf'],
            'fr' => ['industrie', 'fabricant', 'marché', 'ventes'],
            'es' => ['industria', 'fabricante', 'mercado', 'ventas'],
        ],
    ],

    'review' => [
        'name_ru' => 'Обзоры',
        'name_en' => 'Reviews',
        'icon' => '🔍',
        'color' => '#e67e22',
        'priority' => 2,
        'keywords' => [
            'en' => ['review', 'test', 'comparison', 'analysis', 'evaluation'],
            'ru' => ['обзор', 'тест', 'сравнение', 'анализ', 'оценка'],
            'de' => ['Test', 'Vergleich', 'Bewertung', 'Analyse'],
            'fr' => ['test', 'comparaison', 'analyse', 'évaluation'],
            'es' => ['prueba', 'comparación', 'análisis', 'evaluación'],
        ],
    ],

    'other' => [
        'name_ru' => 'Прочее',
        'name_en' => 'Other',
        'icon' => '📰',
        'color' => '#7f8c8d',
        'priority' => 1,
        'keywords' => [],
    ],
];
