<?php

namespace App\DTO;

class ReactionSummary
{
    public int $total_likes = 0;
    public int $total_comments = 0;
    public int $total_shares = 0;
    public int $total_views = 0;
    public float $engagement_rate = 0.0;
    public string $last_activity = '';
    public string $trend = '';
    public string $sentiment = '';
    public string $created_at = '';
    public string $updated_at = '';
}
