-- VanLife News Aggregator - RSS Sources Seed Data
-- Version: 1.0.0
-- These sources are loaded from config/sources.php
-- URL Format: https://news.google.com/rss/search?q={query}&hl={lang}&gl={country}&ceid={country}:{lang}

INSERT INTO `sources` (`name`, `type`, `url`, `query`, `language_code`, `country_code`, `is_enabled`, `fetch_interval_hours`) VALUES

-- ===== РУССКИЙ =====
('Google News RU - Автодом', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28%D0%B0%D0%B2%D1%82%D0%BE%D0%B4%D0%BE%D0%BC+OR+%D0%BA%D0%B5%D0%BC%D0%BF%D0%B5%D1%80+OR+%22%D0%B4%D0%BE%D0%BC+%D0%BD%D0%B0+%D0%BA%D0%BE%D0%BB%D1%91%D1%81%D0%B0%D1%85%22+OR+%D0%B2%D0%B0%D0%BD%D0%BB%D0%B0%D0%B9%D1%84%29+%28%D0%B7%D0%B0%D0%BF%D1%80%D0%B5%D1%82+OR+%D1%88%D1%82%D1%80%D0%B0%D1%84+OR+%D0%BF%D1%80%D0%B0%D0%B2%D0%B8%D0%BB%D0%B0+OR+%D0%BA%D0%B5%D0%BC%D0%BF%D0%B8%D0%BD%D0%B3+OR+%D1%84%D0%B5%D1%81%D1%82%D0%B8%D0%B2%D0%B0%D0%BB%D1%8C%29&hl=ru&gl=RU&ceid=RU:ru',
 '(автодом OR кемпер OR "дом на колёсах" OR ванлайф) (запрет OR штраф OR правила OR кемпинг OR фестиваль)',
 'ru', 'RU', 1, 4),

-- ===== АНГЛИЙСКИЙ (US) =====
('Google News US - RV', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28RV+OR+campervan+OR+motorhome+OR+vanlife%29+%28ban+OR+rules+OR+ordinance+OR+opening+OR+festival%29&hl=en&gl=US&ceid=US:en',
 '(RV OR campervan OR motorhome OR vanlife) (ban OR rules OR ordinance OR opening OR festival)',
 'en', 'US', 1, 4),

-- ===== АНГЛИЙСКИЙ (UK) =====
('Google News UK - Campervan', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28campervan+OR+motorhome+OR+%22wild+camping%22%29+%28ban+OR+rules+OR+opening+OR+festival%29&hl=en&gl=GB&ceid=GB:en',
 '(campervan OR motorhome OR "wild camping") (ban OR rules OR opening OR festival)',
 'en', 'GB', 1, 4),

-- ===== АНГЛИЙСКИЙ (AU) =====
('Google News AU - Caravan', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28caravan+OR+campervan+OR+motorhome%29+%28ban+OR+rules+OR+opening+OR+festival%29&hl=en&gl=AU&ceid=AU:en',
 '(caravan OR campervan OR motorhome) (ban OR rules OR opening OR festival)',
 'en', 'AU', 1, 6),

-- ===== НЕМЕЦКИЙ =====
('Google News DE - Wohnmobil', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28Wohnmobil+OR+Wohnwagen+OR+Reisemobil%29+%28Verbot+OR+Stellplatz+OR+Er%C3%B6ffnung+OR+Messe%29&hl=de&gl=DE&ceid=DE:de',
 '(Wohnmobil OR Wohnwagen OR Reisemobil) (Verbot OR Stellplatz OR Eröffnung OR Messe)',
 'de', 'DE', 1, 4),

-- ===== ФРАНЦУЗСКИЙ =====
('Google News FR - Camping-car', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28%22camping-car%22+OR+%22van+am%C3%A9nag%C3%A9%22+OR+%22fourgon+am%C3%A9nag%C3%A9%22%29+%28interdiction+OR+stationnement+OR+ouverture+OR+salon%29&hl=fr&gl=FR&ceid=FR:fr',
 '("camping-car" OR "van aménagé" OR "fourgon aménagé") (interdiction OR stationnement OR ouverture OR salon)',
 'fr', 'FR', 1, 4),

-- ===== ИСПАНСКИЙ =====
('Google News ES - Autocaravana', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28autocaravana+OR+%22furgoneta+camper%22%29+%28prohibido+OR+pernocta+OR+apertura+OR+feria%29&hl=es&gl=ES&ceid=ES:es',
 '(autocaravana OR "furgoneta camper") (prohibido OR pernocta OR apertura OR feria)',
 'es', 'ES', 1, 4),

-- ===== ИТАЛЬЯНСКИЙ =====
('Google News IT - Camper', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28camper+OR+autocaravan%29+%28divieto+OR+sosta+OR+apertura+OR+fiera%29&hl=it&gl=IT&ceid=IT:it',
 '(camper OR autocaravan) (divieto OR sosta OR apertura OR fiera)',
 'it', 'IT', 1, 4),

-- ===== ПОРТУГАЛЬСКИЙ =====
('Google News PT - Autocaravana', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28autocaravana+OR+motorhome%29+%28proibi%C3%A7%C3%A3o+OR+pernoita+OR+abertura%29&hl=pt&gl=PT&ceid=PT:pt',
 '(autocaravana OR motorhome) (proibição OR pernoita OR abertura)',
 'pt', 'PT', 1, 6),

-- ===== НИДЕРЛАНДСКИЙ =====
('Google News NL - Camper', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28kampeerauto+OR+camper%29+%28verboden+OR+overnachten+OR+opening%29&hl=nl&gl=NL&ceid=NL:nl',
 '(kampeerauto OR camper) (verboden OR overnachten OR opening)',
 'nl', 'NL', 1, 6),

-- ===== ТУРЕЦКИЙ =====
('Google News TR - Karavan', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28karavan+OR+motokaravan%29+%28yasa%C4%9F%C4%B1+OR+a%C3%A7%C4%B1l%C4%B1%C5%9F%C4%B1+OR+festival%29&hl=tr&gl=TR&ceid=TR:tr',
 '(karavan OR motokaravan) (yasağı OR açılışı OR festival)',
 'tr', 'TR', 1, 6),

-- ===== ПОЛЬСКИЙ =====
('Google News PL - Kamper', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28kamper+OR+przyczepa%29+%28zakaz+OR+otwarcie+OR+festiwal%29&hl=pl&gl=PL&ceid=PL:pl',
 '(kamper OR przyczepa) (zakaz OR otwarcie OR festiwal)',
 'pl', 'PL', 1, 6),

-- ===== ШВЕДСКИЙ =====
('Google News SE - Husbil', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28husbil+OR+husvagn%29+%28f%C3%B6rbud+OR+st%C3%A4llplats+OR+%C3%B6ppning%29&hl=sv&gl=SE&ceid=SE:sv',
 '(husbil OR husvagn) (förbud OR ställplats OR öppning)',
 'sv', 'SE', 1, 6),

-- ===== НОРВЕЖСКИЙ =====
('Google News NO - Bobil', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28bobil+OR+bobilplass%29+%28forbud+OR+%C3%A5pning%29&hl=no&gl=NO&ceid=NO:no',
 '(bobil OR bobilplass) (forbud OR åpning)',
 'no', 'NO', 1, 6),

-- ===== ДАТСКИЙ =====
('Google News DK - Autocamper', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28autocamper+OR+campingvogn%29+%28forbud+OR+%C3%A5bning%29&hl=da&gl=DK&ceid=DK:da',
 '(autocamper OR campingvogn) (forbud OR åbning)',
 'da', 'DK', 1, 6),

-- ===== ФИНСКИЙ =====
('Google News FI - Matkailuauto', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28matkailuauto+OR+asuntoauto%29+%28kielto+OR+avaus%29&hl=fi&gl=FI&ceid=FI:fi',
 '(matkailuauto OR asuntoauto) (kielto OR avaus)',
 'fi', 'FI', 1, 6),

-- ===== ЧЕШСКИЙ =====
('Google News CZ - Karavan', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28karavan+OR+obytn%C3%BD+v%C5%AFz%29+%28z%C3%A1kaz+OR+otev%C5%99en%C3%AD%29&hl=cs&gl=CZ&ceid=CZ:cs',
 '(karavan OR obytný vůz) (zákaz OR otevření)',
 'cs', 'CZ', 1, 6),

-- ===== ЯПОНСКИЙ =====
('Google News JP - キャンピングカー', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28%E3%82%AD%E3%83%A3%E3%83%B3%E3%83%94%E3%83%B3%E3%82%B0%E3%82%AB%E3%83%BC+OR+%E8%BB%8A%E4%B8%AD%E6%B3%8A%29+%28%E7%A6%81%E6%AD%A2+OR+%E3%82%AA%E3%83%BC%E3%83%97%E3%83%B3+OR+%E3%82%B7%E3%83%A7%E3%83%BC%29&hl=ja&gl=JP&ceid=JP:ja',
 '(キャンピングカー OR 車中泊) (禁止 OR オープン OR ショー)',
 'ja', 'JP', 1, 12),

-- ===== КИТАЙСКИЙ =====
('Google News CN - 房车', 'google_news_rss',
 'https://news.google.com/rss/search?q=%E6%88%BF%E8%BD%A6+%28%E7%A6%81%E5%81%9C+OR+%E8%90%A5%E5%9C%B0+OR+%E5%B1%95%29&hl=zh-CN&gl=CN&ceid=CN:zh-Hans',
 '房车 (禁停 OR 营地 OR 展)',
 'zh-CN', 'CN', 1, 12),

-- ===== КОРЕЙСКИЙ =====
('Google News KR - 캠핑카', 'google_news_rss',
 'https://news.google.com/rss/search?q=%28%EC%BA%A0%ED%95%91%EC%B9%B4+OR+%EC%B0%A8%EB%B0%95%29+%28%EA%B8%88%EC%A7%80+OR+%EA%B0%9C%EC%9E%A5+OR+%EC%B6%95%EC%A0%9C%29&hl=ko&gl=KR&ceid=KR:ko',
 '(캠핑카 OR 차박) (금지 OR 개장 OR 축제)',
 'ko', 'KR', 1, 12);
