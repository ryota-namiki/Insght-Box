<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\CardRepository;

class CardController extends Controller
{
    public function index(CardRepository $repo)
    {
        return response()->json(['cards' => $repo->listSummaries()]);
    }

    public function store(Request $req, CardRepository $repo)
    {
        // デバッグログ
        \Log::info('Card creation request', [
            'all_data' => $req->all(),
            'memo' => $req->input('memo'),
            'cameraImage' => $req->input('cameraImage') ? 'present' : 'not present'
        ]);
        
        $validated = $req->validate([
            'title' => 'required|string|max:200',
            'companyName' => 'nullable|string|max:200',
            'memo' => 'nullable|string|max:10000',
            'tags' => 'nullable|array|max:50',
            'ocrText' => 'nullable|string|max:50000',
            'eventId' => 'required|string',
            'authorId' => 'nullable|string',
            'documentId' => 'nullable|string',
            'cameraImage' => 'nullable|string',
        ]);
        
        // バリデーション後のデバッグログ
        \Log::info('Validated data', [
            'validated' => $validated,
            'memo' => $validated['memo'] ?? 'not set'
        ]);

        $cardId = (string) \Illuminate\Support\Str::uuid();
        
        $card = [
            'id' => $cardId,
            'summary' => [
                'id' => $cardId,
                'title' => $validated['title'],
                'company' => $validated['companyName'] ?? null,
                'tags' => $validated['tags'] ?? [],
                'eventId' => $validated['eventId'],
                'authorId' => $validated['authorId'] ?? null,
                'status' => 'draft',
                'createdAt' => now()->toIso8601String(),
                'updatedAt' => now()->toIso8601String(),
            ],
            'detail' => [
                'id' => $cardId,
                'memo' => $validated['memo'] ?? null,
                'text' => $validated['ocrText'] ?? null,
                'rawText' => $validated['ocrText'] ?? null,
                'documentId' => $validated['documentId'] ?? null,
                'cameraImage' => $validated['cameraImage'] ?? null,
            ],
            'reactions' => [
                'likes' => 0,
                'comments' => 0,
                'views' => 0,
            ],
            'timeseries' => [],
            'audience' => [],
        ];

        $repo->upsert($cardId, $card);
        return response()->json([
            'card' => $card,
            'summary' => $card['summary']
        ]);
    }

    public function board(CardRepository $repo)
    {
        $all = $repo->read();
        $cardsWithPosition = [];
        foreach ($all as $card) {
            $summary = $card['summary'] ?? [];
            $summary['position'] = $card['position'] ?? ['x' => 0, 'y' => 0];
            $cardsWithPosition[] = $summary;
        }
        return response()->json($cardsWithPosition);
    }

    public function show(string $id, CardRepository $repo)
    {
        $card = $repo->find($id);
        return $card ? response()->json($card) : response()->json(['error' => 'not found'], 404);
    }

    public function update(string $id, Request $req, CardRepository $repo)
    {
        $validated = $req->validate([
            'title' => 'nullable|string|max:200',
            'companyName' => 'nullable|string|max:200',
            'memo' => 'nullable|string|max:10000',
            'tags' => 'nullable|array|max:50',
            'eventId' => 'nullable|string',
        ]);
        
        $card = $repo->find($id);
        if (!$card) {
            return response()->json(['error' => 'not found'], 404);
        }
        
        // フロントエンドのペイロードをカードの構造に変換
        if (isset($validated['title'])) {
            $card['summary']['title'] = $validated['title'];
        }
        if (isset($validated['companyName'])) {
            $card['summary']['company'] = $validated['companyName'];
        }
        if (isset($validated['tags'])) {
            $card['summary']['tags'] = $validated['tags'];
        }
        if (isset($validated['eventId'])) {
            $card['summary']['eventId'] = $validated['eventId'];
        }
        if (isset($validated['memo'])) {
            $card['detail']['memo'] = $validated['memo'];
        }
        
        $card['summary']['updatedAt'] = now()->toIso8601String();
        
        $repo->upsert($id, $card);
        return response()->json($card);
    }

    public function destroy(string $id, CardRepository $repo)
    {
        $repo->delete($id);
        return response()->json(['deleted' => true]);
    }

    public function updatePosition(string $id, Request $req, CardRepository $repo)
    {
        \Log::info('Position update request', ['card_id' => $id, 'request' => $req->all()]);
        
        $validated = $req->validate([
            'x' => 'required|integer|min:0',
            'y' => 'required|integer|min:0',
        ]);

        $card = $repo->find($id);
        if (!$card) {
            \Log::error('Card not found', ['card_id' => $id]);
            return response()->json(['error' => 'not found'], 404);
        }

        \Log::info('Updating position', ['card_id' => $id, 'x' => $validated['x'], 'y' => $validated['y']]);
        $repo->updatePosition($id, $validated['x'], $validated['y']);
        return response()->json(['success' => true]);
    }
}
