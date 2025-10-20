<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory;
  use Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var list<string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  /**
   * ユーザープロフィール
   */
  public function profile()
  {
    return $this->hasOne(UserProfile::class);
  }

  /**
   * 所有カード
   */
  public function cards()
  {
    return $this->hasMany(Card::class, 'owner_user_id');
  }

  /**
   * 所属チーム（将来実装）
   */
  public function teams()
  {
    return $this->belongsToMany(Team::class, 'team_user')
      ->withPivot('role', 'joined_at')
      ->withTimestamps();
  }

  /**
   * お気に入り
   */
  public function favorites()
  {
    return $this->hasMany(Favorite::class);
  }

  /**
   * お気に入りカード
   */
  public function favoriteCards()
  {
    return $this->belongsToMany(Card::class, 'favorites', 'user_id', 'card_id')
      ->withTimestamps();
  }
}
