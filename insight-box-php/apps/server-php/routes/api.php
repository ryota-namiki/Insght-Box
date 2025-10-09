<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{DocumentController, JobController, CardController};
use App\Repositories\EventRepository;

Route::post('/v1/documents', [DocumentController::class, 'store']);
Route::get('/v1/jobs/{id}', [JobController::class, 'show']);
Route::get('/v1/documents/{id}/text', [DocumentController::class, 'text']);
Route::get('/v1/documents/{id}/image', [DocumentController::class, 'image']);
Route::get('/v1/documents/{id}/metadata', [DocumentController::class, 'metadata']);

Route::get('/board', [CardController::class, 'board']);
Route::get('/cards', [CardController::class, 'index']);
Route::post('/cards', [CardController::class, 'store']);
Route::get('/cards/{id}', [CardController::class, 'show']);
Route::put('/cards/{id}', [CardController::class, 'update']);
Route::put('/cards/{id}/position', [CardController::class, 'updatePosition']);
Route::delete('/cards/{id}', [CardController::class, 'destroy']);

// Events API (永続化対応)
Route::get('/events', function(EventRepository $events) {
    return response()->json(['events' => $events->list()]);
})->name('api.events.index');

Route::post('/events', function(\Illuminate\Http\Request $request, EventRepository $events) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'startDate' => 'required|date',
        'endDate' => 'required|date',
        'location' => 'nullable|string|max:255'
    ]);
    
    $event = [
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'name' => $validated['name'],
        'startDate' => $validated['startDate'],
        'endDate' => $validated['endDate'],
        'location' => $validated['location'] ?? null,
        'created_at' => now()->toIso8601String(),
        'updated_at' => now()->toIso8601String()
    ];
    
    // イベントを永続化
    $events->create($event);
    
    return response()->json(['event' => $event]);
})->name('api.events.store');

Route::put('/events/{id}', function(string $id, \Illuminate\Http\Request $request, EventRepository $events) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'startDate' => 'required|date',
        'endDate' => 'required|date',
        'location' => 'nullable|string|max:255'
    ]);
    
    $event = $events->find($id);
    if (!$event) {
        return response()->json(['error' => 'not found'], 404);
    }
    
    $updatedEvent = [
        'id' => $id,
        'name' => $validated['name'],
        'startDate' => $validated['startDate'],
        'endDate' => $validated['endDate'],
        'location' => $validated['location'] ?? null,
        'created_at' => $event['created_at'] ?? now()->toIso8601String(),
        'updated_at' => now()->toIso8601String()
    ];
    
    $events->update($id, $updatedEvent);
    
    return response()->json(['event' => $updatedEvent]);
})->name('api.events.update');

Route::delete('/events/{id}', function(string $id, EventRepository $events) {
    $events->delete($id);
    return response()->json(['deleted' => true]);
})->name('api.events.destroy');

// AI要約API
Route::post('/ai/summarize', function(\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'text' => 'required|string|max:50000',
        'title' => 'nullable|string|max:500'
    ]);
    
    try {
        $service = new \App\Services\AiSummaryService();
        
        if (isset($validated['title'])) {
            // タイトル + 本文の要約
            $summary = $service->summarizeWebClip($validated['title'], $validated['text']);
        } else {
            // テキストのみの要約
            $summary = $service->summarize($validated['text']);
        }
        
        return response()->json([
            'summary' => $summary,
            'success' => true
        ]);
        
    } catch (\Exception $e) {
        \Log::error('AI要約APIエラー: ' . $e->getMessage());
        
        return response()->json([
            'error' => $e->getMessage(),
            'summary' => mb_substr($validated['text'], 0, 247) . '...', // フォールバック
            'success' => false
        ], 500);
    }
});

// Webクリップ API
Route::post('/webclip/fetch', function(\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'url' => 'required|url|max:2000'
    ]);
    
    try {
        // Webページを取得
        $url = $validated['url'];
        $html = file_get_contents($url, false, stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; InsightBox/1.0)'
            ]
        ]));
        
        if (!$html) {
            throw new \Exception('Webページの取得に失敗しました');
        }
        
        // WebClipServiceでメタデータとテキストを抽出
        $webClipService = app(\App\Services\WebClipService::class);
        $metadata = $webClipService->extractMetadata($html);
        $mainText = $webClipService->extractMainText($html);
        
        // OpenAI APIで要約を生成
        $aiService = new \App\Services\AiSummaryService();
        $summary = $aiService->summarizeWebClip($metadata['title'], $mainText);
        
        return response()->json([
            'success' => true,
            'title' => $metadata['title'],
            'description' => $metadata['description'],
            'summary' => $summary,
            'content' => mb_substr($mainText, 0, 1000), // 最初の1000文字
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Webクリップエラー: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// Marketplace API (簡易実装)
Route::get('/marketplace', function() {
    return response()->json([
        'cards' => [],
        'templates' => [],
        'featured' => []
    ]);
});
