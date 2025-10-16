<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Card extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'owner_user_id',
        'team_id',
        'visibility',
        'title',
        'company',
        'tags',
        'event_id',
        'author_id',
        'status',
        'memo',
        'ocr_text',
        'raw_text',
        'document_id',
        'camera_image',
        'webclip_url',
        'webclip_summary',
        'likes',
        'comments',
        'views',
        'position_x',
        'position_y',
    ];

    protected $casts = [
        'tags' => 'array',
        'likes' => 'integer',
        'comments' => 'integer',
        'views' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 所有者
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * チーム（将来実装）
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * イベント
     */
    public function event()
    {
        return $this->belongsTo(\App\Models\Event::class, 'event_id');
    }
}
