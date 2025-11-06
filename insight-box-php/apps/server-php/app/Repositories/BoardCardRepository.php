<?php

namespace App\Repositories;

use App\Models\BoardCard;
use Illuminate\Support\Collection;

class BoardCardRepository
{
    /**
     * ボードに配置されているカードを取得
     */
    public function getCardsByBoardId(string $boardId): Collection
    {
        return BoardCard::where('board_id', $boardId)
            ->with('card')
            ->get();
    }

    /**
     * カードをボードに追加
     */
    public function addCardToBoard(string $boardId, string $cardId, int $x, int $y): BoardCard
    {
        return BoardCard::updateOrCreate(
            [
                'board_id' => $boardId,
                'card_id' => $cardId,
            ],
            [
                'position_x' => $x,
                'position_y' => $y,
            ]
        );
    }

    /**
     * ボード上のカードの位置を更新（存在しない場合は作成）
     */
    public function updatePosition(string $boardId, string $cardId, int $x, int $y): BoardCard
    {
        return BoardCard::updateOrCreate(
            [
                'board_id' => $boardId,
                'card_id' => $cardId,
            ],
            [
                'position_x' => $x,
                'position_y' => $y,
            ]
        );
    }

    /**
     * ボードからカードを削除
     */
    public function removeCardFromBoard(string $boardId, string $cardId): bool
    {
        return BoardCard::where('board_id', $boardId)
            ->where('card_id', $cardId)
            ->delete() > 0;
    }

    /**
     * カードが特定のボードに配置されているかチェック
     */
    public function isCardOnBoard(string $boardId, string $cardId): bool
    {
        return BoardCard::where('board_id', $boardId)
            ->where('card_id', $cardId)
            ->exists();
    }
}

