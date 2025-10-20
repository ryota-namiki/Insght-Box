<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
  protected $fillable = [
    'user_id',
    'card_id',
  ];

  /**
   * ユーザー
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * カード
   */
  public function card()
  {
    return $this->belongsTo(Card::class, 'card_id', 'id');
  }
}
