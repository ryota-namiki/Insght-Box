<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Board extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'owner_user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function cards()
    {
        return $this->hasMany(Card::class, 'board_id');
    }
}
