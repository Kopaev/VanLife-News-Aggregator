<?php
/**
 * VanLife News Aggregator - Google News RSS Sources Configuration
 *
 * URL Format: https://news.google.com/rss/search?q={query}&hl={lang}&gl={country}&ceid={country}:{lang}
 */

declare(strict_types=1);

return [
    // ===== РУССКИЙ =====
    [
        'name' => 'Google News RU - Автодом',
        'query' => '(автодом OR кемпер OR "дом на колёсах" OR ванлайф) (запрет OR штраф OR правила OR кемпинг OR фестиваль)',
        'language' => 'ru',
        'country' => 'RU',
    ],

    // ===== АНГЛИЙСКИЙ (US) =====
    [
        'name' => 'Google News US - RV',
        'query' => '(RV OR campervan OR motorhome OR vanlife) (ban OR rules OR ordinance OR opening OR festival)',
        'language' => 'en',
        'country' => 'US',
    ],

    // ===== АНГЛИЙСКИЙ (UK) =====
    [
        'name' => 'Google News UK - Campervan',
        'query' => '(campervan OR motorhome OR "wild camping") (ban OR rules OR opening OR festival)',
        'language' => 'en',
        'country' => 'GB',
    ],

    // ===== АНГЛИЙСКИЙ (AU) =====
    [
        'name' => 'Google News AU - Caravan',
        'query' => '(caravan OR campervan OR motorhome) (ban OR rules OR opening OR festival)',
        'language' => 'en',
        'country' => 'AU',
    ],

    // ===== НЕМЕЦКИЙ =====
    [
        'name' => 'Google News DE - Wohnmobil',
        'query' => '(Wohnmobil OR Wohnwagen OR Reisemobil) (Verbot OR Stellplatz OR Eröffnung OR Messe)',
        'language' => 'de',
        'country' => 'DE',
    ],

    // ===== ФРАНЦУЗСКИЙ =====
    [
        'name' => 'Google News FR - Camping-car',
        'query' => '("camping-car" OR "van aménagé" OR "fourgon aménagé") (interdiction OR stationnement OR ouverture OR salon)',
        'language' => 'fr',
        'country' => 'FR',
    ],

    // ===== ИСПАНСКИЙ =====
    [
        'name' => 'Google News ES - Autocaravana',
        'query' => '(autocaravana OR "furgoneta camper") (prohibido OR pernocta OR apertura OR feria)',
        'language' => 'es',
        'country' => 'ES',
    ],

    // ===== ИТАЛЬЯНСКИЙ =====
    [
        'name' => 'Google News IT - Camper',
        'query' => '(camper OR autocaravan) (divieto OR sosta OR apertura OR fiera)',
        'language' => 'it',
        'country' => 'IT',
    ],

    // ===== ПОРТУГАЛЬСКИЙ =====
    [
        'name' => 'Google News PT - Autocaravana',
        'query' => '(autocaravana OR motorhome) (proibição OR pernoita OR abertura)',
        'language' => 'pt',
        'country' => 'PT',
    ],

    // ===== НИДЕРЛАНДСКИЙ =====
    [
        'name' => 'Google News NL - Camper',
        'query' => '(kampeerauto OR camper) (verboden OR overnachten OR opening)',
        'language' => 'nl',
        'country' => 'NL',
    ],

    // ===== ТУРЕЦКИЙ =====
    [
        'name' => 'Google News TR - Karavan',
        'query' => '(karavan OR motokaravan) (yasağı OR açılışı OR festival)',
        'language' => 'tr',
        'country' => 'TR',
    ],

    // ===== ПОЛЬСКИЙ =====
    [
        'name' => 'Google News PL - Kamper',
        'query' => '(kamper OR przyczepa) (zakaz OR otwarcie OR festiwal)',
        'language' => 'pl',
        'country' => 'PL',
    ],

    // ===== ШВЕДСКИЙ =====
    [
        'name' => 'Google News SE - Husbil',
        'query' => '(husbil OR husvagn) (förbud OR ställplats OR öppning)',
        'language' => 'sv',
        'country' => 'SE',
    ],

    // ===== НОРВЕЖСКИЙ =====
    [
        'name' => 'Google News NO - Bobil',
        'query' => '(bobil OR bobilplass) (forbud OR åpning)',
        'language' => 'no',
        'country' => 'NO',
    ],

    // ===== ДАТСКИЙ =====
    [
        'name' => 'Google News DK - Autocamper',
        'query' => '(autocamper OR campingvogn) (forbud OR åbning)',
        'language' => 'da',
        'country' => 'DK',
    ],

    // ===== ФИНСКИЙ =====
    [
        'name' => 'Google News FI - Matkailuauto',
        'query' => '(matkailuauto OR asuntoauto) (kielto OR avaus)',
        'language' => 'fi',
        'country' => 'FI',
    ],

    // ===== ЧЕШСКИЙ =====
    [
        'name' => 'Google News CZ - Karavan',
        'query' => '(karavan OR obytný vůz) (zákaz OR otevření)',
        'language' => 'cs',
        'country' => 'CZ',
    ],

    // ===== ЯПОНСКИЙ =====
    [
        'name' => 'Google News JP - キャンピングカー',
        'query' => '(キャンピングカー OR 車中泊) (禁止 OR オープン OR ショー)',
        'language' => 'ja',
        'country' => 'JP',
    ],

    // ===== КИТАЙСКИЙ =====
    [
        'name' => 'Google News CN - 房车',
        'query' => '房车 (禁停 OR 营地 OR 展)',
        'language' => 'zh-CN',
        'country' => 'CN',
    ],

    // ===== КОРЕЙСКИЙ =====
    [
        'name' => 'Google News KR - 캠핑카',
        'query' => '(캠핑카 OR 차박) (금지 OR 개장 OR 축제)',
        'language' => 'ko',
        'country' => 'KR',
    ],
];
