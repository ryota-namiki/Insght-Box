<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\User;

class CardPolicy
{
  /**
   * カードの閲覧権限
   */
  public function view(User $user, Card $card): bool
  {
    return $card->owner_user_id === $user->id;
  }

  /**
   * カードの更新権限
   */
  public function update(User $user, Card $card): bool
  {
    return $card->owner_user_id === $user->id;
  }

  /**
   * カードの削除権限
   */
  public function delete(User $user, Card $card): bool
  {
    return $card->owner_user_id === $user->id;
  }

  /**
   * カードの作成権限（認証済みユーザーなら誰でも可）
   */
  public function create(User $user): bool
  {
    return true;
  }
}
