<?php

namespace App\Model;

class Source
{
    public ?int $id = null;
    public string $name;
    public string $type = 'google_news_rss';
    public string $url;
    public ?string $query = null;
    public string $language_code;
    public ?string $country_code = null;
    public ?string $category = null;
    public bool $is_enabled = true;
    public int $fetch_interval_hours = 24;
    public ?string $last_fetched_at = null;
    public ?string $last_error = null;
    public int $articles_count = 0;
    public string $created_at;
    public string $updated_at;
}
