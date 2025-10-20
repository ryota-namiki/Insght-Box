<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AuthController;

// ホームページ（カード一覧にリダイレクト）
Route::get('/', function () {
  return redirect()->route('cards.index');
});

// === 認証関連（未認証ユーザー用） ===
Route::middleware('guest')->group(function () {
  Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
  Route::post('/register', [AuthController::class, 'register']);

  Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
  Route::post('/login', [AuthController::class, 'login']);
});

// === 認証必須ルート ===
Route::middleware('auth')->group(function () {
  // ログアウト
  Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

  // プロフィール
  Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
  Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
});

// === カード関連（認証必須） ===
Route::middleware('auth')->group(function () {
  Route::get('/cards', [CardController::class, 'indexWeb'])->name('cards.index');
  Route::get('/cards/create', [CardController::class, 'createWeb'])->name('cards.create');
  Route::post('/cards', [CardController::class, 'storeWeb'])->name('cards.store');
  Route::get('/cards/{id}', [CardController::class, 'showWeb'])->name('cards.show');
  Route::get('/cards/{id}/edit', [CardController::class, 'editWeb'])->name('cards.edit');
  Route::put('/cards/{id}', [CardController::class, 'updateWeb'])->name('cards.update');
  Route::delete('/cards/{id}', [CardController::class, 'destroyWeb'])->name('cards.destroy');

  // ボードビュー
  Route::get('/board', [BoardController::class, 'index'])->name('board.index');

  // イベント管理
  Route::get('/events', [EventController::class, 'index'])->name('events.index');
  Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
  Route::post('/events', [EventController::class, 'store'])->name('events.store');
  Route::get('/events/{id}/edit', [EventController::class, 'edit'])->name('events.edit');
  Route::put('/events/{id}', [EventController::class, 'update'])->name('events.update');
  Route::delete('/events/{id}', [EventController::class, 'destroy'])->name('events.destroy');
});
