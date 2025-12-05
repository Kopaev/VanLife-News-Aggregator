<?php

namespace App\Model;

class Translation
{
    public ?int $id = null;
    public int $article_id;
    public string $target_language;
    public string $title;
    public ?string $summary = null;
    public string $provider = 'openai';
    public ?string $created_at = null;
}
