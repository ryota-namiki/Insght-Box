<?php

namespace App\DTO;

class CardSummary
{
    public string $title = '';
    public string $company = '';
    public string $date = '';
    public string $type = '';
    public string $status = '';
    public string $priority = '';
    public string $category = '';
    public string $description = '';
    /** @var string[] */
    public array $tags = [];
    public string $thumbnail = '';
    public string $url = '';
    public int $views = 0;
    public int $likes = 0;
    public int $comments = 0;
    public string $created_at = '';
    public string $updated_at = '';
}
