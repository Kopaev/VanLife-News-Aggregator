<?php

namespace App\Model;

class Article
{
    public ?int $id = null;
    public int $source_id;
    public ?int $cluster_id = null;
    public string $external_id;
    public string $original_title;
    public ?string $original_summary = null;
    public ?string $original_content = null;
    public string $original_url;
    public string $original_language;
    public ?string $title_ru = null;
    public ?string $summary_ru = null;
    public ?string $slug = null;
    public ?string $image_url = null;
    public ?string $country_code = null;
    public ?string $category_slug = null;
    public ?array $tags = null;
    public ?int $ai_relevance_score = null;
    public ?string $ai_processed_at = null;
    public string $published_at;
    public string $fetched_at;
    public string $status = 'new';
    public ?string $moderation_reason = null;
    public ?string $moderated_at = null;
    public int $views_count = 0;
    public string $created_at;
    public string $updated_at;
}
