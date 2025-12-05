# VanLife News Aggregator

> News aggregator about vanlife and motorhomes from around the world

**Domain:** `news.vanlife.bez.coffee`

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API](#api)
- [Cron Tasks](#cron-tasks)
- [Project Structure](#project-structure)
- [Database](#database)
- [Development](#development)
- [Changelog](#changelog)

---

## Features

### Implemented
- [x] Project structure initialized
- [x] Database schema designed (12 tables)
- [x] Configuration system with .env support
- [x] Google News RSS sources for 20+ languages
- [x] Category system with keywords
- [x] Country and region configuration
- [x] Moderation rules configuration

### Planned
- [ ] News collection from Google News RSS
- [ ] Google News URL decoding
- [ ] AI translation to Russian (OpenAI)
- [ ] Automatic categorization
- [ ] Similar news clustering
- [ ] Light/dark theme
- [ ] Admin panel
- [ ] SEO optimization

---

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Composer 2.x
- PHP Extensions: cURL, DOM, JSON, PDO, mbstring

---

## Installation

### 1. Clone the repository
```bash
git clone https://github.com/YOUR_USERNAME/vanlife-news.git
cd vanlife-news
```

### 2. Install dependencies
```bash
composer install --no-dev
```

### 3. Configure environment
```bash
cp .env.example .env
nano .env
```

### 4. Create database
```bash
mysql -u root -p -e "CREATE DATABASE vanlife_news CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'vanlife'@'localhost' IDENTIFIED BY 'your_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON vanlife_news.* TO 'vanlife'@'localhost';"
```

### 5. Import schema and seed data
```bash
mysql -u vanlife -p vanlife_news < sql/schema.sql
mysql -u vanlife -p vanlife_news < sql/seeds/countries.sql
mysql -u vanlife -p vanlife_news < sql/seeds/languages.sql
mysql -u vanlife -p vanlife_news < sql/seeds/categories.sql
```

### 6. Configure web server

**Nginx:**
```nginx
server {
    listen 80;
    server_name news.vanlife.bez.coffee;
    root /var/www/vanlife-news/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

### 7. Set up cron jobs
```cron
# Fetch news every 6 hours
0 */6 * * * php /var/www/vanlife-news/scripts/fetch_news.php >> /var/log/vanlife-fetch.log 2>&1

# Process news every hour
0 * * * * php /var/www/vanlife-news/scripts/process_news.php >> /var/log/vanlife-process.log 2>&1

# Cluster news every 4 hours
0 */4 * * * php /var/www/vanlife-news/scripts/cluster_news.php >> /var/log/vanlife-cluster.log 2>&1
```

---

## Configuration

### Environment Variables (.env)

| Variable | Description | Required |
|----------|-------------|----------|
| `DB_HOST` | Database host | Yes |
| `DB_PORT` | Database port | No (default: 3306) |
| `DB_NAME` | Database name | Yes |
| `DB_USER` | Database user | Yes |
| `DB_PASS` | Database password | Yes |
| `OPENAI_API_KEY` | OpenAI API key | Yes |
| `OPENAI_MODEL` | Model to use | No (default: gpt-4o-mini) |
| `APP_URL` | Site URL | Yes |
| `APP_DEBUG` | Debug mode | No (default: false) |
| `ADMIN_USERNAME` | Admin username | Yes |
| `ADMIN_PASSWORD` | Admin password | Yes |

### Configuration Files

| File | Description |
|------|-------------|
| `config/config.php` | Main configuration |
| `config/sources.php` | Google News RSS sources |
| `config/categories.php` | News categories |
| `config/countries.php` | Countries and regions |
| `config/moderation.php` | Moderation rules |

---

## Usage

### CLI Commands
```bash
# Fetch news from all sources
php scripts/fetch_news.php

# Process new articles with AI
php scripts/process_news.php

# Cluster similar articles
php scripts/cluster_news.php

# Generate sitemap
php scripts/generate_sitemap.php
```

---

## API

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/news` | List of news articles |
| GET | `/api/news/{id}` | Single article |
| GET | `/api/clusters` | News clusters |
| GET | `/api/filters` | Filter data |
| GET | `/health` | Health check |

*API endpoints will be implemented in future phases.*

---

## Cron Tasks

| Script | Schedule | Description |
|--------|----------|-------------|
| `fetch_news.php` | `0 */6 * * *` | Fetch RSS feeds |
| `process_news.php` | `0 * * * *` | AI processing |
| `cluster_news.php` | `0 */4 * * *` | Clustering |
| `generate_sitemap.php` | `0 3 * * *` | Sitemap generation |

---

## Project Structure

```
vanlife-news/
├── .github/
│   └── workflows/
│       └── deploy.yml          # GitHub Actions
├── public/                     # Document root
│   ├── index.php               # Entry point
│   ├── css/
│   ├── js/
│   └── images/
├── src/
│   ├── Core/                   # Framework classes
│   ├── Controller/             # HTTP controllers
│   ├── Service/                # Business logic
│   ├── Model/                  # Data models
│   ├── Repository/             # Data access
│   ├── AI/                     # AI integration
│   └── Helper/                 # Utilities
├── templates/
│   ├── layout/                 # Base templates
│   ├── pages/                  # Page templates
│   ├── admin/                  # Admin templates
│   └── components/             # Reusable components
├── config/                     # Configuration files
├── scripts/                    # CLI scripts
├── sql/
│   ├── schema.sql             # Database schema
│   ├── seeds/                 # Seed data
│   └── migrations/            # Future migrations
├── logs/                      # Application logs
├── .env.example
├── composer.json
├── README.md
├── PROGRESS.md
└── CHANGELOG.md
```

---

## Database

### Tables

| Table | Description |
|-------|-------------|
| `articles` | News articles |
| `sources` | RSS sources |
| `clusters` | Article clusters |
| `categories` | News categories |
| `countries` | Countries |
| `languages` | Languages |
| `translations` | Article translations |
| `decoded_urls_cache` | Google News URL cache |
| `logs` | Application logs |
| `metrics` | Performance metrics |
| `admins` | Admin users |
| `admin_sessions` | Admin sessions |
| `settings` | Application settings |

---

## Development

### Development Progress
See [PROGRESS.md](PROGRESS.md) for detailed task tracking.

### Current Phase
**PHASE 1: Foundation** - Building core functionality

### Contributing
1. Check PROGRESS.md for current task
2. Follow coding standards
3. Update documentation
4. Create meaningful commits

### Commit Message Format
```
feat: add new feature
fix: fix bug
docs: update documentation
refactor: code refactoring
style: formatting
test: add tests
chore: maintenance
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

---

## License

MIT

---

## Links

- **Production:** https://news.vanlife.bez.coffee
- **Documentation:** See this README
- **Progress:** [PROGRESS.md](PROGRESS.md)
