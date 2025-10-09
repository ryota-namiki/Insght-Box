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
        $cards = Card::orderBy('created_at', 'desc')->get();
        
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
        $card = Card::find($id);
        
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
            'position_x' => $record['position']['x'] ?? null,
            'position_y' => $record['position']['y'] ?? null,
        ];
        
        Card::updateOrCreate(['id' => $id], $data);
    }

    public function delete(string $id): void
    {
        Card::destroy($id);
    }

    public function updatePosition(string $id, int $x, int $y): void
    {
        Card::where('id', $id)->update([
            'position_x' => $x,
            'position_y' => $y,
        ]);
    }

    /**
     * Convert Card model to array format matching the old JSON structure
     * @return array<string,mixed>
     */
    private function toArray(Card $card): array
    {
        return [
            'id' => $card->id,
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
            'position' => [
                'x' => $card->position_x ?? 0,
                'y' => $card->position_y ?? 0,
            ],
            'updated_at' => $card->updated_at->toIso8601String(),
        ];
    }
}
