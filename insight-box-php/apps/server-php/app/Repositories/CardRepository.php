<?php

namespace App\Repositories;

use App\Models\Card;

class CardRepository
{
  /** @return array<string,mixed> */
  public function read(): array
  {
    $cards = Card::all();
    $result = [];

    foreach ($cards as $card) {
      $result[$card->id] = $this->toArray($card);
    }

    return $result;
  }

  /** @return array<int,array<string,mixed>> */
  public function listSummaries(): array
  {
    $cards = Card::orderBy('created_at', 'desc')
      ->get();

    return $cards->map(function ($card) {
      return [
        'id' => $card->id,
        'title' => $card->title,
        'company' => $card->company,
        'tags' => $card->tags ?? [],
        'eventId' => $card->event_id,
        'authorId' => $card->author_id,
        'status' => $card->status,
        'createdAt' => $card->created_at->toIso8601String(),
        'updatedAt' => $card->updated_at->toIso8601String(),
      ];
    })->toArray();
  }

  /** @return array<string,mixed>|null */
  public function find(string $id): ?array
  {
    $card = Card::where('id', $id)->first();

    if (!$card) {
      return null;
    }

    return $this->toArray($card);
  }

  /** @param array<string,mixed> $record */
  public function upsert(string $id, array $record): void
  {
    $data = [
      'id' => $id,
      'board_id' => $record['board_id'] ?? null,
      'owner_user_id' => $record['owner_user_id'] ?? auth()->id(),
      'team_id' => $record['team_id'] ?? null,
      'visibility' => $record['visibility'] ?? 'private',
      'title' => $record['summary']['title'] ?? '',
      'company' => $record['summary']['company'] ?? null,
      'tags' => $record['summary']['tags'] ?? [],
      'event_id' => $record['summary']['eventId'] ?? '',
      'author_id' => $record['summary']['authorId'] ?? null,
      'status' => $record['summary']['status'] ?? 'draft',
      'memo' => $record['detail']['memo'] ?? null,
      'ocr_text' => $record['detail']['text'] ?? null,
      'raw_text' => $record['detail']['rawText'] ?? null,
      'document_id' => $record['detail']['documentId'] ?? null,
      'camera_image' => $record['detail']['cameraImage'] ?? null,
      'webclip_url' => $record['detail']['webclipUrl'] ?? null,
      'webclip_summary' => $record['detail']['webclipSummary'] ?? null,
      'likes' => $record['reactions']['likes'] ?? 0,
      'comments' => $record['reactions']['comments'] ?? 0,
      'views' => $record['reactions']['views'] ?? 0,
      'position_x' => isset($record['position']) && is_array($record['position']) ? ($record['position']['x'] ?? null) : null,
      'position_y' => isset($record['position']) && is_array($record['position']) ? ($record['position']['y'] ?? null) : null,
    ];

    Card::updateOrCreate(['id' => $id], $data);
  }

  public function delete(string $id): void
  {
    Card::where('id', $id)->delete();
  }

  public function updatePosition(string $id, int $x, int $y): void
  {
    Card::where('id', $id)->update([
      'position_x' => $x,
      'position_y' => $y,
    ]);
  }

  /**
   * カードのboard_idとpositionを更新
   */
  public function updateBoardAndPosition(string $id, ?string $boardId, ?int $x, ?int $y): void
  {
    $data = [];
    
    if ($boardId !== null) {
      $data['board_id'] = $boardId;
    }
    
    if ($x !== null && $y !== null) {
      $data['position_x'] = $x;
      $data['position_y'] = $y;
    }
    
    if (!empty($data)) {
      Card::where('id', $id)->update($data);
    }
  }

  /**
   * カードのboard_idをクリア（positionもクリア）
   */
  public function clearBoardAndPosition(string $id): void
  {
    Card::where('id', $id)->update([
      'board_id' => null,
      'position_x' => null,
      'position_y' => null,
    ]);
  }

  /**
   * Convert Card model to array format matching the old JSON structure
   * @return array<string,mixed>
   */
  private function toArray(Card $card): array
  {
    $position = null;
    if ($card->position_x !== null && $card->position_y !== null) {
      $position = [
        'x' => $card->position_x,
        'y' => $card->position_y,
      ];
    }

    return [
      'id' => $card->id,
      'board_id' => $card->board_id,
      'owner_user_id' => $card->owner_user_id,
      'summary' => [
        'id' => $card->id,
        'title' => $card->title,
        'company' => $card->company,
        'tags' => $card->tags ?? [],
        'eventId' => $card->event_id,
        'authorId' => $card->author_id,
        'status' => $card->status,
        'createdAt' => $card->created_at->toIso8601String(),
        'updatedAt' => $card->updated_at->toIso8601String(),
      ],
      'detail' => [
        'id' => $card->id,
        'memo' => $card->memo,
        'text' => $card->ocr_text,
        'rawText' => $card->raw_text,
        'documentId' => $card->document_id,
        'cameraImage' => $card->camera_image,
        'webclipUrl' => $card->webclip_url,
        'webclipSummary' => $card->webclip_summary,
      ],
      'reactions' => [
        'likes' => $card->likes,
        'comments' => $card->comments,
        'views' => $card->views,
      ],
      'timeseries' => [],
      'audience' => [],
      'position' => $position,
      'updated_at' => $card->updated_at->toIso8601String(),
    ];
  }
}
