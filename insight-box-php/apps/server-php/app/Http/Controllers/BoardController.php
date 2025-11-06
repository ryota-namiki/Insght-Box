<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\CardRepository;
use App\Repositories\BoardRepository;
use App\Repositories\BoardCardRepository;

class BoardController extends Controller
{
  public function list(BoardRepository $boardRepo)
  {
    $userId = auth()->id();
    
    if (!$userId) {
      return redirect()->route('login')->with('error', 'ログインが必要です');
    }
    
    $boards = $boardRepo->getUserBoards($userId);

    return view('board.list', compact('boards'));
  }

  public function create()
  {
    return view('board.create');
  }

  public function store(Request $request, BoardRepository $boardRepo)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string|max:1000',
    ]);

    $board = $boardRepo->create([
      'name' => $validated['name'],
      'description' => $validated['description'] ?? null,
      'owner_user_id' => auth()->id(),
    ]);

    return redirect()->route('board.show', $board['id'])
      ->with('success', 'ボードが作成されました');
  }

  public function show(string $id, CardRepository $cardRepo, BoardRepository $boardRepo, BoardCardRepository $boardCardRepo)
  {
    $board = $boardRepo->find($id);

    if (!$board) {
      abort(404, 'ボードが見つかりません');
    }

    // このボードに配置されているカード情報を取得
    $boardCards = $boardCardRepo->getCardsByBoardId($id);
    $boardCardIds = $boardCards->pluck('card_id')->toArray();

    // 全カードを取得（左カラム用 - ユーザーが所有するすべてのカード）
    $all = $cardRepo->read();
    $cards = [];

    foreach ($all as $card) {
      // ユーザーが所有するカードのみを表示
      if (($card['owner_user_id'] ?? null) === auth()->id()) {
        $summary = $card['summary'] ?? [];
        $summary['reactions'] = $card['reactions'] ?? ['likes' => 0, 'comments' => 0, 'views' => 0];
        
        // このボードに配置されているかどうかをチェック
        $onBoard = in_array($card['id'], $boardCardIds);
        $summary['onThisBoard'] = $onBoard;
        
        // ボードに配置されている場合は、ボード上の位置を設定
        if ($onBoard) {
          $boardCard = $boardCards->firstWhere('card_id', $card['id']);
          $summary['position'] = [
            'x' => $boardCard->position_x,
            'y' => $boardCard->position_y,
          ];
        } else {
          $summary['position'] = null;
        }
        
        $cards[] = $summary;
      }
    }

    return view('board.index', compact('board', 'cards'));
  }

  public function index(CardRepository $repo)
  {
    $all = $repo->read();
    $cards = [];

    foreach ($all as $card) {
      $summary = $card['summary'] ?? [];
      $summary['position'] = $card['position'] ?? null;
      $summary['reactions'] = $card['reactions'] ?? ['likes' => 0, 'comments' => 0, 'views' => 0];
      $cards[] = $summary;
    }

    return view('board.index', compact('cards'));
  }

  public function edit(string $id, BoardRepository $boardRepo)
  {
    $board = $boardRepo->find($id);

    if (!$board) {
      abort(404, 'ボードが見つかりません');
    }

    if ($board['owner_user_id'] !== auth()->id()) {
      abort(403, '権限がありません');
    }

    return view('board.edit', compact('board'));
  }

  public function update(Request $request, string $id, BoardRepository $boardRepo)
  {
    $board = $boardRepo->find($id);

    if (!$board) {
      abort(404, 'ボードが見つかりません');
    }

    if ($board['owner_user_id'] !== auth()->id()) {
      abort(403, '権限がありません');
    }

    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string|max:1000',
    ]);

    $boardRepo->update($id, $validated);

    return redirect()->route('board.show', $id)
      ->with('success', 'ボードが更新されました');
  }

  public function destroy(string $id, BoardRepository $boardRepo)
  {
    $board = $boardRepo->find($id);

    if (!$board) {
      abort(404, 'ボードが見つかりません');
    }

    if ($board['owner_user_id'] !== auth()->id()) {
      abort(403, '権限がありません');
    }

    $boardRepo->delete($id);

    return redirect()->route('board.list')
      ->with('success', 'ボードが削除されました');
  }

  public function updatePosition(Request $request, $boardId, $id, BoardCardRepository $boardCardRepo)
  {
    $validated = $request->validate([
      'x' => 'required|numeric',
      'y' => 'required|numeric',
    ]);

    // 存在しない場合は自動的に作成される（updateOrCreate）
    $boardCardRepo->updatePosition($boardId, $id, (int)$validated['x'], (int)$validated['y']);

    return response()->json(['success' => true]);
  }

  public function addToBoard(Request $request, $boardId, $id, CardRepository $cardRepo, BoardCardRepository $boardCardRepo)
  {
    $validated = $request->validate([
      'x' => 'required|numeric',
      'y' => 'required|numeric',
    ]);

    $card = $cardRepo->find($id);
    if (!$card) {
      return response()->json(['error' => 'Card not found'], 404);
    }

    // ボードにカードを追加
    $boardCardRepo->addCardToBoard($boardId, $id, (int)$validated['x'], (int)$validated['y']);

    return response()->json(['success' => true, 'card' => $card['summary'] ?? []]);
  }

  public function removeFromBoard($boardId, $id, BoardCardRepository $boardCardRepo)
  {
    // ボードからカードを削除
    $boardCardRepo->removeCardFromBoard($boardId, $id);

    return response()->json(['success' => true]);
  }
}
