<?php

namespace App\Model;

class Cluster
{
    public ?int $id = null;
    public string $title_ru;
    public string $slug;
    public ?string $summary_ru = null;
    public ?int $main_article_id = null;
    public ?string $category_slug = null;
    public int $articles_count = 1;
    public array $countries = [];
    public string $first_published_at;
    public string $last_updated_at;
    public bool $is_active = true;
    public string $created_at;
}
