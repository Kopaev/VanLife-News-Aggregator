# Changelog

All notable changes to the VanLife News Aggregator project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- AI слой: `App\AI\AIProviderInterface`, `OpenAIProvider` и `AIResponse` для работы с OpenAI Chat Completions с учётом лимита запросов
- NewsProcessor для AI-оценки релевантности статей с учётом правил модерации
- NewsProcessor сохраняет AI-определённые категорию (`category_slug`), страну и теги статьи
- TranslationService для перевода статей на русский язык и записи в таблицы `articles` и `translations`
- ModerationService для постобработки контента: автопоиск ключевых слов для отклонения/ручной модерации, отметка `moderated_at` и логирование решений
- Публичные шаблоны на русском: категория/страна, теги, статус статьи и перевод в карточках новостей
- Cron-скрипт `scripts/process_news.php`, объединяющий AI-разметку, переводы и модерацию с конфигурируемыми батчами
- Оптимизация токенов: `TextSanitizer`, лимиты промптов (`PROMPT_*`) и `max_tokens` для AI-сервисов
- Исправлен cron сбора новостей: корректная инициализация `LoggerService` и зависимостей `GoogleNewsUrlDecoder`
- Методы `execute` и `lastInsertId` в `Database` для корректной работы репозиториев
- Migration runner `scripts/migrate.php` with seed loading option
- Initial SQL migration `sql/migrations/001_init_schema.sql` with full schema and decoded URL cache
- Core framework layer: `Config`, `Database`, `Router`, `Response`, `App`
- Entry point wired to router with landing page and `/health` endpoint
- GoogleNewsUrlDecoder service with base64/API/redirect strategies and DB caching
- LoggerService with JSON log output to `logs/app.log`

---

## [0.1.0] - 2025-12-05

### Added
- Initial project structure
- Database schema with 12 tables:
  - `articles` - News articles
  - `sources` - RSS feed sources
  - `clusters` - Article clustering
  - `categories` - News categories
  - `countries` - Country data
  - `languages` - Language data
  - `translations` - Article translations
  - `decoded_urls_cache` - Google News URL cache
  - `logs` - Application logs
  - `metrics` - Performance metrics
  - `admins` - Admin users
  - `admin_sessions` - Admin sessions
  - `settings` - Application settings
- Seed data for countries, languages, and categories
- Configuration system:
  - `.env.example` - Environment variables template
  - `config/config.php` - Main configuration with env loader
  - `config/sources.php` - Google News RSS sources (20+ languages)
  - `config/categories.php` - News categories with multilingual keywords
  - `config/countries.php` - Country regions and mappings
  - `config/moderation.php` - Content moderation rules
- Documentation:
  - `README.md` - Project documentation
  - `PROGRESS.md` - Development progress tracker
  - `CHANGELOG.md` - Version history

### Technical Details
- **Stack:** PHP 8.2+, MySQL 8.0+, Vanilla JS
- **Languages supported:** 20+ (EN, DE, FR, ES, IT, PT, NL, SV, NO, DA, FI, PL, CS, TR, JA, ZH-CN, KO, RU, etc.)
- **Categories:** 10 (law, ban, opening, closing, incident, festival, expo, industry, review, other)

---

## Version History

| Version | Date | Description |
|---------|------|-------------|
| 0.1.0 | 2025-12-05 | Initial project structure |
