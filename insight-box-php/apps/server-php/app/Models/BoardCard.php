<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardCard extends Model
{
    protected $fillable = [
        'board_id',
        'card_id',
        'position_x',
        'position_y',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class, 'board_id');
    }

    public function card()
    {
        return $this->belongsTo(Card::class, 'card_id');
    }
}
