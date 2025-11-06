<?php

namespace App\Repositories;

use App\Models\Board;

class BoardRepository
{
  /**
   * ユーザーのボード一覧を取得
   */
  public function getUserBoards(int $userId): array
  {
    $boards = Board::where('owner_user_id', $userId)
      ->orderBy('created_at', 'desc')
      ->get();

    return $boards->map(function ($board) {
      return [
        'id' => $board->id,
        'name' => $board->name,
        'description' => $board->description,
        'cardCount' => $board->cards()->count(),
        'createdAt' => $board->created_at->toIso8601String(),
        'updatedAt' => $board->updated_at->toIso8601String(),
      ];
    })->toArray();
  }

  /**
   * ボードを取得
   */
  public function find(string $id): ?array
  {
    $board = Board::find($id);

    if (!$board) {
      return null;
    }

    return [
      'id' => $board->id,
      'name' => $board->name,
      'description' => $board->description,
      'owner_user_id' => $board->owner_user_id,
      'cardCount' => $board->cards()->count(),
      'createdAt' => $board->created_at->toIso8601String(),
      'updatedAt' => $board->updated_at->toIso8601String(),
    ];
  }

  /**
   * ボードを作成
   */
  public function create(array $data): array
  {
    $board = Board::create([
      'name' => $data['name'],
      'description' => $data['description'] ?? null,
      'owner_user_id' => $data['owner_user_id'],
    ]);

    return [
      'id' => $board->id,
      'name' => $board->name,
      'description' => $board->description,
      'owner_user_id' => $board->owner_user_id,
      'createdAt' => $board->created_at->toIso8601String(),
      'updatedAt' => $board->updated_at->toIso8601String(),
    ];
  }

  /**
   * ボードを更新
   */
  public function update(string $id, array $data): bool
  {
    $board = Board::find($id);

    if (!$board) {
      return false;
    }

    $board->update([
      'name' => $data['name'] ?? $board->name,
      'description' => $data['description'] ?? $board->description,
    ]);

    return true;
  }

  /**
   * ボードを削除
   */
  public function delete(string $id): bool
  {
    $board = Board::find($id);

    if (!$board) {
      return false;
    }

    // カードのboard_idをnullに設定
    $board->cards()->update(['board_id' => null]);

    $board->delete();

    return true;
  }
}

