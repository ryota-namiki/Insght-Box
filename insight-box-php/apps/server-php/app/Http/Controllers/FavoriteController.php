<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Card;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
  /**
   * お気に入り一覧（マイページ）
   */
  public function index()
  {
    $user = auth()->user();
    $favoriteCards = $user->favoriteCards()
      ->with('event')
      ->orderBy('favorites.created_at', 'desc')
      ->get();

    // Card情報を配列形式に変換
    $cards = $favoriteCards->map(function ($card) {
      return [
        'id' => $card->id,
        'title' => $card->title,
        'company' => $card->company,
        'tags' => $card->tags ?? [],
        'eventId' => $card->event_id,
        'status' => $card->status,
        'createdAt' => $card->created_at->toIso8601String(),
        'updatedAt' => $card->updated_at->toIso8601String(),
        'favoritedAt' => $card->pivot->created_at->toIso8601String(),
      ];
    })->toArray();

    return view('favorites.index', compact('cards'));
  }

  /**
   * お気に入りに追加（API）
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'card_id' => 'required|string|exists:cards,id',
    ]);

    $card = Card::findOrFail($validated['card_id']);

    // 既にお気に入りに追加されているかチェック
    $exists = Favorite::where('user_id', auth()->id())
      ->where('card_id', $card->id)
      ->exists();

    if ($exists) {
      return response()->json([
        'success' => false,
        'message' => '既にお気に入りに追加されています',
      ], 409);
    }

    Favorite::create([
      'user_id' => auth()->id(),
      'card_id' => $card->id,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'お気に入りに追加しました',
    ]);
  }

  /**
   * お気に入りから削除（API）
   */
  public function destroy(string $cardId)
  {
    $favorite = Favorite::where('user_id', auth()->id())
      ->where('card_id', $cardId)
      ->first();

    if (!$favorite) {
      return response()->json([
        'success' => false,
        'message' => 'お気に入りに登録されていません',
      ], 404);
    }

    $favorite->delete();

    return response()->json([
      'success' => true,
      'message' => 'お気に入りから削除しました',
    ]);
  }

  /**
   * お気に入り状態をトグル（API）
   */
  public function toggle(Request $request)
  {
    $validated = $request->validate([
      'card_id' => 'required|string|exists:cards,id',
    ]);

    $favorite = Favorite::where('user_id', auth()->id())
      ->where('card_id', $validated['card_id'])
      ->first();

    if ($favorite) {
      // 削除
      $favorite->delete();
      return response()->json([
        'success' => true,
        'favorited' => false,
        'message' => 'お気に入りから削除しました',
      ]);
    } else {
      // 追加
      Favorite::create([
        'user_id' => auth()->id(),
        'card_id' => $validated['card_id'],
      ]);
      return response()->json([
        'success' => true,
        'favorited' => true,
        'message' => 'お気に入りに追加しました',
      ]);
    }
  }
}
