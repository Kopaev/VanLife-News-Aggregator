# VanLife News Aggregator - Progress

**Last Updated:** 2025-12-06

## Current Phase: PHASE 2 - AI Processing

### Task Status

#### PHASE 1: Foundation (MVP without AI)
| # | Task | Status | Date | Notes |
|---|------|--------|------|-------|
| 1.1 | Project initialization | ✅ | 2025-12-05 | Structure, DB schema, configs |
| 1.2 | Database (schema, migrations, cache) | ✅ | 2025-12-05 | Added migration runner and seeds loader |
| 1.3 | Core classes (Router, Database, Config, Response) | ✅ | 2025-12-05 | Added App bootstrap, routing, health endpoint |
| 1.4 | GoogleNewsUrlDecoder | ✅ | 2025-12-05 | Base64 + batchexecute decoder with DB cache |
| 1.5 | NewsFetcher - RSS collection | ✅ | 2025-12-05 | Fetches and saves articles from sources |
| 1.6 | Basic models and repositories | ✅ | 2025-12-05 | Article, Source models; repositories for them |
| 1.7 | HomeController + home template | ✅ | 2025-12-05 | Displays latest articles |
| 1.8 | ArticleController + article template | ✅ | 2025-12-05 | Displays single article view |
| 1.9 | Basic CSS (responsive, dark theme) | ✅ | 2025-12-05 | Added stylesheet and theme switcher |
| 1.10 | Cron script for news fetching | ✅ | 2025-12-05 | `scripts/fetch_news.php` for cron jobs |
| 1.11 | README with deploy instructions | ✅ | 2025-12-05 | Updated documentation |

#### PHASE 2: AI Processing
| # | Task | Status | Date | Notes |
|---|------|--------|------|-------|
| 2.1 | OpenAI Provider | ✅ | 2025-12-05 | Добавлен `OpenAIProvider` с оберткой ответа и ограничением запросов |
| 2.2 | NewsProcessor - relevance scoring | ✅ | 2025-12-06 | Добавлен сервис релевантности с OpenAI и правилами модерации |
| 2.3 | NewsProcessor - categorization and tags | ✅ | 2025-12-06 | Запись категории, страны и тегов из OpenAI-ответа |
| 2.4 | TranslationService | ✅ | 2025-12-06 | Перевод на русский через OpenAI, запись в articles + translations |
| 2.5 | ModerationService | ⬜ | - | - |
| 2.6 | Cron script for processing | ⬜ | - | - |
| 2.7 | Template updates (Russian titles, categories) | ⬜ | - | - |
| 2.8 | Token usage optimization | ⬜ | - | - |

... (rest of the phases remain the same)

### Status Legend
- ⬜ Not started
- 🔄 In progress
- ✅ Completed
- ⏸️ Paused
- ❌ Cancelled

---

## Change History

### [2025-12-05] - PHASE 1 Completion
**Completed:**
- **NewsFetcher Service (Task 1.5):** Implemented the service to fetch news from all enabled RSS sources. It uses the `GoogleNewsUrlDecoder` and saves new articles to the database.
- **Models and Repositories (Task 1.6):** Created `Article` and `Source` models. Implemented `ArticleRepository` and `SourceRepository` to handle database interactions, separating data logic from services.
- **Controllers and Templates (Tasks 1.7, 1.8):** Developed `HomeController` to display a list of the latest articles on the main page and `ArticleController` to show a single article. Created corresponding `home.php` and `article.php` view templates.
- **Basic Frontend (Task 1.9):** Added a basic responsive stylesheet (`style.css`) and a JavaScript-powered theme switcher for light/dark modes.
- **Cron Script (Task 1.10):** Created a standalone `scripts/fetch_news.php` script for automating news collection via cron jobs.
- **Documentation (Task 1.11):** Thoroughly updated the `README.md` file with complete installation, configuration, and deployment instructions.

### [2025-12-06] - Task 2.4 TranslationService
- Added `TranslationService` to translate processed articles into Russian via OpenAI, writing results into `articles` and `translations` tables with logging.

### [2025-12-05] - Task 1.1 - 1.4
... (previous entries)

---

## Known Issues
*None at this time*

---

## Next Steps
1. Создать ModerationService для дополнительной фильтрации (Task 2.5).
2. Создать cron-скрипт для AI-пайплайна (Task 2.6) и обновить шаблоны (Task 2.7).
3. Оптимизировать расход токенов и повторно проверить пайплайн (Task 2.8).
