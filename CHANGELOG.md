# Changelog

All notable changes to the VanLife News Aggregator project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### In Progress
- PHASE 1: Foundation - Core classes implementation

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
