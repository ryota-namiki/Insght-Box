<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardController;
use App\Http\Controllers\BoardController;

// ホームページ（カード一覧にリダイレクト）
Route::get('/', function () {
    return redirect()->route('cards.index');
});

// カード関連のルート
Route::get('/cards', [CardController::class, 'indexWeb'])->name('cards.index');
Route::get('/cards/create', [CardController::class, 'createWeb'])->name('cards.create');
Route::post('/cards', [CardController::class, 'storeWeb'])->name('cards.store');
Route::get('/cards/{id}', [CardController::class, 'showWeb'])->name('cards.show');
Route::get('/cards/{id}/edit', [CardController::class, 'editWeb'])->name('cards.edit');
Route::put('/cards/{id}', [CardController::class, 'updateWeb'])->name('cards.update');
Route::delete('/cards/{id}', [CardController::class, 'destroyWeb'])->name('cards.destroy');

// ボードビュー
Route::get('/board', [BoardController::class, 'index'])->name('board.index');
