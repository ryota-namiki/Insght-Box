# Insight-Box 認証 & チーム共有機能 実装設計書（Laravel版）

## 0. プロジェクト状況

### 現在の技術スタック
- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Blade templates + Alpine.js + Tailwind CSS (CDN)
- **Database**: SQLite
- **デプロイ**: FileZilla（SSH不可）
- **既存機能**: カード管理、イベント管理、OCR、Webclip

### スコープ（MVP）
✅ Email/Password認証（セッションベース）
✅ ユーザー登録・ログイン・プロフィール
✅ チーム作成・招待・メンバー管理
✅ 役割管理（admin, editor, viewer）
✅ 個人カード⇔チーム共有
✅ チーム共有ボード（検索・フィルター）
✅ コメント機能（任意）

❌ SSO/OAuth（将来拡張）
❌ 2FA（将来拡張）
❌ メール送信（開発中はログ出力）
❌ リアルタイム通知（将来拡張）

---

## 1. アーキテクチャ概要

### 1.1 技術詳細

| レイヤー | 技術 |
|---------|------|
| 認証 | Laravel標準セッション認証 |
| パスワードハッシュ | `Hash::make()` (bcrypt) |
| セッション | `storage/framework/sessions/` |
| バリデーション | FormRequest |
| 認可 | Policy + Gate |
| フロントエンド | Blade + Alpine.js |
| CSS | Tailwind CSS (CDN) |

### 1.2 認証戦略

- **セッションベース認証**：Cookie（`laravel_session`）
- **CSRF保護**：`@csrf` ディレクティブ（Laravel標準）
- **ミドルウェア**：`auth`, `guest`
- **ガード**：`web`（デフォルト）
- **永続化**：SQLite `users`, `sessions` テーブル

---

## 2. データベース設計

### 2.1 ERD

```
users (既存Laravel標準)
├─ id: bigint PK
├─ name: string
├─ email: string UNIQUE
├─ email_verified_at: timestamp NULL
├─ password: string (bcrypt)
├─ remember_token: string NULL
├─ created_at: timestamp
└─ updated_at: timestamp

user_profiles (拡張)
├─ id: bigint PK
├─ user_id: bigint FK → users
├─ department: string NULL
├─ avatar_url: string NULL
├─ interests: text NULL (JSON)
├─ created_at: timestamp
└─ updated_at: timestamp

teams
├─ id: bigint PK
├─ name: string
├─ owner_id: bigint FK → users
├─ description: text NULL
├─ is_open_invite: boolean DEFAULT false
├─ created_at: timestamp
└─ updated_at: timestamp

team_user (pivot)
├─ id: bigint PK
├─ team_id: bigint FK → teams
├─ user_id: bigint FK → users
├─ role: enum('admin', 'editor', 'viewer')
├─ joined_at: timestamp
├─ created_at: timestamp
└─ updated_at: timestamp

cards (既存拡張)
├─ id: string PK (UUID)
├─ owner_user_id: bigint FK → users (追加)
├─ team_id: bigint NULL FK → teams (追加)
├─ visibility: enum('private', 'team') DEFAULT 'private' (追加)
├─ title: string
├─ ... (既存フィールド)
├─ created_at: timestamp
└─ updated_at: timestamp

comments (新規・任意)
├─ id: bigint PK
├─ card_id: string FK → cards
├─ team_id: bigint FK → teams
├─ user_id: bigint FK → users
├─ text: text
├─ created_at: timestamp
└─ updated_at: timestamp

team_invitations (新規・将来拡張用)
├─ id: bigint PK
├─ team_id: bigint FK → teams
├─ email: string
├─ token: string UNIQUE
├─ role: enum('admin', 'editor', 'viewer')
├─ expires_at: timestamp
├─ created_at: timestamp
└─ updated_at: timestamp
```

### 2.2 マイグレーション一覧

```bash
# 既存（Laravel標準）
2014_10_12_000000_create_users_table.php
2014_10_12_100000_create_password_reset_tokens_table.php

# 新規作成
2025_10_17_010000_create_user_profiles_table.php
2025_10_17_020000_create_teams_table.php
2025_10_17_030000_create_team_user_table.php
2025_10_17_040000_add_team_fields_to_cards_table.php
2025_10_17_050000_create_comments_table.php
2025_10_17_060000_create_team_invitations_table.php
```

---

## 3. バックエンド実装

### 3.1 認証API（Web Routes）

| メソッド | エンドポイント | 処理 | ミドルウェア |
|---------|---------------|------|-------------|
| GET | `/register` | 登録画面表示 | `guest` |
| POST | `/register` | ユーザー登録 | `guest` |
| GET | `/login` | ログイン画面表示 | `guest` |
| POST | `/login` | ログイン処理 | `guest` |
| POST | `/logout` | ログアウト | `auth` |
| GET | `/profile` | プロフィール表示 | `auth` |
| PUT | `/profile` | プロフィール更新 | `auth` |

**Controller**: `AuthController.php`

```php
// 登録
public function register(RegisterRequest $request) {
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);
    
    Auth::login($user);
    return redirect('/cards')->with('success', '登録が完了しました');
}

// ログイン
public function login(LoginRequest $request) {
    if (Auth::attempt($request->only('email', 'password'), $request->remember)) {
        $request->session()->regenerate();
        return redirect()->intended('/cards');
    }
    
    return back()->withErrors(['email' => '認証情報が正しくありません']);
}

// ログアウト
public function logout(Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
}
```

### 3.2 チーム管理API

| メソッド | エンドポイント | 処理 | 権限 |
|---------|---------------|------|------|
| GET | `/teams` | チーム一覧 | 認証 |
| POST | `/teams` | チーム作成 | 認証 |
| GET | `/teams/{id}` | チーム詳細 | メンバー |
| PUT | `/teams/{id}` | チーム更新 | admin |
| DELETE | `/teams/{id}` | チーム削除 | owner |
| POST | `/teams/{id}/invite` | メンバー招待 | admin |
| PUT | `/teams/{id}/members/{userId}` | 役割変更 | admin |
| DELETE | `/teams/{id}/members/{userId}` | メンバー削除 | admin |

**Controller**: `TeamController.php`

**Middleware**: `EnsureTeamMember.php`, `EnsureTeamRole.php`

**Policy**: `TeamPolicy.php`

```php
class TeamPolicy
{
    public function view(User $user, Team $team): bool {
        return $team->members()->where('user_id', $user->id)->exists();
    }
    
    public function update(User $user, Team $team): bool {
        return $team->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['admin', 'owner'])
            ->exists();
    }
    
    public function invite(User $user, Team $team): bool {
        return $this->update($user, $team);
    }
}
```

### 3.3 チーム共有ボード

| メソッド | エンドポイント | 処理 | 権限 |
|---------|---------------|------|------|
| GET | `/teams/{id}/board` | 共有ボード表示 | viewer+ |
| GET | `/teams/{id}/cards/{cardId}` | カード詳細 | viewer+ |
| POST | `/teams/{id}/cards` | カード作成 | editor+ |
| PUT | `/teams/{id}/cards/{cardId}` | カード更新 | editor+ |
| DELETE | `/teams/{id}/cards/{cardId}` | カード削除 | admin/作成者 |
| POST | `/cards/{cardId}/share` | チーム共有 | 所有者 |
| DELETE | `/cards/{cardId}/unshare` | 共有解除 | admin/所有者 |

**Controller**: `TeamBoardController.php`, `TeamCardController.php`

**検索・フィルター**:
```php
// TeamBoardController.php
public function index(Team $team, Request $request) {
    $this->authorize('view', $team);
    
    $query = Card::where('team_id', $team->id)
        ->where('visibility', 'team');
    
    // 検索
    if ($q = $request->input('q')) {
        $query->where(function($subQuery) use ($q) {
            $subQuery->where('title', 'like', "%{$q}%")
                ->orWhere('memo', 'like', "%{$q}%")
                ->orWhere('ocr_text', 'like', "%{$q}%");
        });
    }
    
    // タグフィルター
    if ($tags = $request->input('tags')) {
        $query->whereJsonContains('tags', $tags);
    }
    
    // 作成者フィルター
    if ($userId = $request->input('author')) {
        $query->where('owner_user_id', $userId);
    }
    
    // 期間フィルター
    if ($from = $request->input('from')) {
        $query->where('created_at', '>=', $from);
    }
    if ($to = $request->input('to')) {
        $query->where('created_at', '<=', $to);
    }
    
    $cards = $query->with('owner', 'event')
        ->orderBy('updated_at', 'desc')
        ->paginate(20);
    
    return view('teams.board', compact('team', 'cards'));
}
```

### 3.4 コメント機能（任意）

| メソッド | エンドポイント | 処理 | 権限 |
|---------|---------------|------|------|
| GET | `/teams/{teamId}/cards/{cardId}/comments` | コメント一覧 | viewer+ |
| POST | `/teams/{teamId}/cards/{cardId}/comments` | コメント投稿 | editor+ |
| DELETE | `/comments/{id}` | コメント削除 | admin/投稿者 |

---

## 4. フロントエンド実装（Blade）

### 4.1 レイアウト構造

```
resources/views/
├── layouts/
│   ├── app.blade.php           # メインレイアウト（既存拡張）
│   ├── guest.blade.php         # 未認証用レイアウト（新規）
│   └── team.blade.php          # チーム用レイアウト（新規）
├── auth/
│   ├── register.blade.php      # 登録画面
│   ├── login.blade.php         # ログイン画面
│   └── profile.blade.php       # プロフィール
├── teams/
│   ├── index.blade.php         # チーム一覧
│   ├── create.blade.php        # チーム作成
│   ├── show.blade.php          # チーム詳細
│   ├── board.blade.php         # 共有ボード
│   └── settings.blade.php      # チーム設定（メンバー管理）
├── team-cards/
│   ├── show.blade.php          # チームカード詳細
│   └── edit.blade.php          # チームカード編集
└── components/
    ├── team-card-tile.blade.php    # カードタイル
    ├── team-member-list.blade.php  # メンバーリスト
    ├── comment-list.blade.php      # コメントリスト
    └── share-button.blade.php      # 共有ボタン
```

### 4.2 主要画面

#### 4.2.1 ログイン画面（`auth/login.blade.php`）

```blade
@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6">ログイン</h1>
        
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ $errors->first() }}
            </div>
        @endif
        
        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium mb-2">メールアドレス</label>
                <input type="email" id="email" name="email" required 
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500"
                    value="{{ old('email') }}">
            </div>
            
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium mb-2">パスワード</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500">
            </div>
            
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="mr-2">
                    <span class="text-sm">ログイン状態を保持</span>
                </label>
            </div>
            
            <button type="submit" 
                class="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700">
                ログイン
            </button>
        </form>
        
        <div class="mt-4 text-center">
            <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">
                アカウント作成
            </a>
        </div>
    </div>
</div>
@endsection
```

#### 4.2.2 チーム共有ボード（`teams/board.blade.php`）

```blade
@extends('layouts.team')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="teamBoard()">
    <!-- ヘッダー -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">{{ $team->name }}</h1>
            <p class="text-gray-600">共有ボード</p>
        </div>
        
        @can('create', [App\Models\Card::class, $team])
        <a href="{{ route('teams.cards.create', $team) }}" 
            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
            ＋ カード作成
        </a>
        @endcan
    </div>
    
    <!-- 検索・フィルター -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form method="GET" action="{{ route('teams.board', $team) }}" 
            class="flex flex-wrap gap-4">
            
            <!-- 検索 -->
            <input type="text" name="q" placeholder="検索..." 
                value="{{ request('q') }}"
                class="flex-1 min-w-[200px] px-4 py-2 border rounded-md">
            
            <!-- タグ -->
            <select name="tags[]" multiple 
                class="px-4 py-2 border rounded-md">
                @foreach($allTags as $tag)
                    <option value="{{ $tag }}">{{ $tag }}</option>
                @endforeach
            </select>
            
            <!-- 作成者 -->
            <select name="author" class="px-4 py-2 border rounded-md">
                <option value="">全員</option>
                @foreach($team->members as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </select>
            
            <button type="submit" 
                class="bg-gray-800 text-white px-6 py-2 rounded-md hover:bg-gray-900">
                検索
            </button>
        </form>
    </div>
    
    <!-- カードグリッド -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($cards as $card)
            <x-team-card-tile :card="$card" :team="$team" />
        @empty
            <p class="col-span-3 text-center text-gray-500 py-8">
                カードがありません
            </p>
        @endforelse
    </div>
    
    <!-- ページネーション -->
    <div class="mt-6">
        {{ $cards->links() }}
    </div>
</div>

<script>
function teamBoard() {
    return {
        // Alpine.jsでのインタラクション（任意）
    };
}
</script>
@endsection
```

#### 4.2.3 チームカード詳細（`team-cards/show.blade.php`）

```blade
@extends('layouts.team')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- カード情報 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-2xl font-bold mb-2">{{ $card->title }}</h1>
                    <div class="flex gap-4 text-sm text-gray-600">
                        <span>作成者: {{ $card->owner->name }}</span>
                        <span>更新: {{ $card->updated_at->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
                
                @can('update', [$card, $team])
                <a href="{{ route('teams.cards.edit', [$team, $card]) }}"
                    class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    編集
                </a>
                @endcan
            </div>
            
            <!-- タグ -->
            @if($card->tags)
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($card->tags as $tag)
                    <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
            @endif
            
            <!-- 画像プレビュー -->
            @if($card->document_id)
            <div class="mb-4">
                <img src="{{ url("/api/v1/documents/{$card->document_id}/image") }}" 
                    alt="Preview" class="max-w-full h-auto rounded-md">
            </div>
            @endif
            
            <!-- OCRテキスト -->
            @if($card->ocr_text)
            <div class="bg-gray-50 p-4 rounded-md mb-4">
                <h3 class="font-semibold mb-2">抽出テキスト</h3>
                <p class="whitespace-pre-wrap">{{ $card->ocr_text }}</p>
            </div>
            @endif
            
            <!-- メモ -->
            @if($card->memo)
            <div class="mb-4">
                <h3 class="font-semibold mb-2">メモ</h3>
                <p class="whitespace-pre-wrap">{{ $card->memo }}</p>
            </div>
            @endif
        </div>
        
        <!-- コメント -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">コメント</h2>
            
            @can('comment', [$card, $team])
            <form method="POST" action="{{ route('teams.cards.comments.store', [$team, $card]) }}"
                class="mb-6">
                @csrf
                <textarea name="text" rows="3" required
                    class="w-full px-4 py-2 border rounded-md mb-2"
                    placeholder="コメントを入力..."></textarea>
                <button type="submit" 
                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    投稿
                </button>
            </form>
            @endcan
            
            <x-comment-list :comments="$card->comments" />
        </div>
    </div>
</div>
@endsection
```

#### 4.2.4 ヘッダー拡張（`layouts/app.blade.php`）

```blade
<!-- ナビゲーションに追加 -->
@auth
<nav class="flex space-x-6">
    <a href="{{ route('cards.index') }}" class="hover:text-indigo-600">マイカード</a>
    <a href="{{ route('board.index') }}" class="hover:text-indigo-600">マイボード</a>
    <a href="{{ route('events.index') }}" class="hover:text-indigo-600">イベント</a>
    
    <!-- チームドロップダウン -->
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" 
            class="hover:text-indigo-600 flex items-center">
            チーム
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        
        <div x-show="open" @click.away="open = false"
            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
            @foreach(auth()->user()->teams as $team)
                <a href="{{ route('teams.board', $team) }}" 
                    class="block px-4 py-2 hover:bg-gray-100">
                    {{ $team->name }}
                </a>
            @endforeach
            <hr class="my-1">
            <a href="{{ route('teams.index') }}" 
                class="block px-4 py-2 hover:bg-gray-100">
                チーム管理
            </a>
        </div>
    </div>
    
    <!-- プロフィールドロップダウン -->
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" class="hover:text-indigo-600">
            {{ auth()->user()->name }}
        </button>
        
        <div x-show="open" @click.away="open = false"
            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
            <a href="{{ route('profile') }}" 
                class="block px-4 py-2 hover:bg-gray-100">プロフィール</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                    class="block w-full text-left px-4 py-2 hover:bg-gray-100">
                    ログアウト
                </button>
            </form>
        </div>
    </div>
</nav>
@else
<nav class="flex space-x-4">
    <a href="{{ route('login') }}" class="hover:text-indigo-600">ログイン</a>
    <a href="{{ route('register') }}" 
        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
        登録
    </a>
</nav>
@endauth
```

---

## 5. セキュリティ実装

### 5.1 認証・認可

```php
// Middleware: EnsureTeamMember.php
public function handle(Request $request, Closure $next, $teamId) {
    $team = Team::findOrFail($teamId);
    
    if (!$team->members()->where('user_id', auth()->id())->exists()) {
        abort(403, 'このチームのメンバーではありません');
    }
    
    $request->merge(['team' => $team]);
    return $next($request);
}

// Middleware: EnsureTeamRole.php
public function handle(Request $request, Closure $next, ...$roles) {
    $team = $request->route('team') ?? Team::findOrFail($request->route('teamId'));
    $member = $team->members()->where('user_id', auth()->id())->first();
    
    if (!$member || !in_array($member->pivot->role, $roles)) {
        abort(403, '権限がありません');
    }
    
    return $next($request);
}
```

### 5.2 FormRequest バリデーション

```php
// RegisterRequest.php
public function rules(): array {
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
    ];
}

// CreateTeamRequest.php
public function rules(): array {
    return [
        'name' => 'required|string|max:100',
        'description' => 'nullable|string|max:500',
    ];
}

// InviteMemberRequest.php
public function rules(): array {
    return [
        'user_id' => 'required|exists:users,id',
        'role' => 'required|in:admin,editor,viewer',
    ];
}
```

### 5.3 CSRF保護

すべてのフォームに`@csrf`ディレクティブを含める（Laravel標準）。

### 5.4 パスワードポリシー

```php
// config/auth.php（拡張）
'password_rules' => [
    'min' => 8,
    'letters' => true,
    'mixed' => false,  // 大文字小文字混在不要（MVP）
    'numbers' => true,
    'symbols' => false, // 記号不要（MVP）
];
```

---

## 6. 実装手順（3スプリント）

### Sprint 1: 認証基盤（1週間）

#### バックエンド
- [ ] マイグレーション作成
  - `users` (Laravel標準)
  - `user_profiles`
  - `sessions` (Laravel標準)
- [ ] Model作成: `User`, `UserProfile`
- [ ] Controller作成: `AuthController`
- [ ] FormRequest作成: `RegisterRequest`, `LoginRequest`
- [ ] Routes設定: `/register`, `/login`, `/logout`, `/profile`

#### フロントエンド
- [ ] レイアウト作成: `layouts/guest.blade.php`
- [ ] 画面作成:
  - `auth/register.blade.php`
  - `auth/login.blade.php`
  - `auth/profile.blade.php`
- [ ] 既存`layouts/app.blade.php`に認証ヘッダー追加

#### 既存機能への認証追加
- [ ] CardController: `auth`ミドルウェア追加
- [ ] EventController: `auth`ミドルウェア追加
- [ ] Cardに`owner_user_id`追加（マイグレーション）
- [ ] 既存カードに現在のユーザーを割り当て

#### テスト
- [ ] ユーザー登録 → ログイン → プロフィール表示
- [ ] ログアウト → 未認証リダイレクト
- [ ] 既存カード機能が正常動作

---

### Sprint 2: チーム基盤（1週間）

#### バックエンド
- [ ] マイグレーション作成
  - `teams`
  - `team_user`
- [ ] Model作成: `Team`
- [ ] Controller作成: `TeamController`
- [ ] Policy作成: `TeamPolicy`
- [ ] Middleware作成: `EnsureTeamMember`, `EnsureTeamRole`
- [ ] Routes設定: `/teams/*`

#### フロントエンド
- [ ] レイアウト作成: `layouts/team.blade.php`
- [ ] 画面作成:
  - `teams/index.blade.php` (一覧)
  - `teams/create.blade.php` (作成)
  - `teams/show.blade.php` (詳細)
  - `teams/settings.blade.php` (設定・メンバー管理)
- [ ] コンポーネント作成: `team-member-list.blade.php`

#### テスト
- [ ] チーム作成 → 自動でadmin役割
- [ ] メンバー招待 → 役割設定
- [ ] 役割変更
- [ ] メンバー削除
- [ ] 権限チェック（viewer は招待不可など）

---

### Sprint 3: 共有ボード（1週間）

#### バックエンド
- [ ] マイグレーション作成
  - `cards`に`team_id`, `visibility`追加
  - `comments`テーブル作成
- [ ] Controller作成:
  - `TeamBoardController`
  - `TeamCardController`
  - `CommentController`
- [ ] Card共有ロジック実装
- [ ] 検索・フィルター実装

#### フロントエンド
- [ ] 画面作成:
  - `teams/board.blade.php` (共有ボード)
  - `team-cards/show.blade.php` (詳細)
  - `team-cards/edit.blade.php` (編集)
- [ ] コンポーネント作成:
  - `team-card-tile.blade.php`
  - `share-button.blade.php`
  - `comment-list.blade.php`
- [ ] 既存`cards/show.blade.php`に「チームへ共有」ボタン追加
- [ ] Alpine.jsでインタラクション実装

#### 統合
- [ ] 個人カード → チーム共有フロー
- [ ] チームボード → 検索・フィルター
- [ ] カード詳細 → コメント投稿
- [ ] 共有解除機能

#### テスト
- [ ] 個人カードを複数チームに共有
- [ ] チームボードで検索（タイトル、タグ、作成者）
- [ ] editor がカード編集できる
- [ ] viewer がカード閲覧のみ
- [ ] admin がカード削除できる
- [ ] コメント投稿・削除

---

## 7. デプロイ手順（FileZilla）

### 7.1 初回デプロイ

#### 1. ファイルアップロード
```
ローカル → サーバー
insight-box-php/apps/server-php/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php
│   │   ├── TeamController.php
│   │   ├── TeamBoardController.php
│   │   └── TeamCardController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Team.php
│   │   └── Comment.php
│   ├── Policies/
│   │   └── TeamPolicy.php
│   └── Http/Middleware/
│       ├── EnsureTeamMember.php
│       └── EnsureTeamRole.php
├── database/migrations/
│   └── 2025_10_17_*.php
├── resources/views/
│   ├── auth/
│   ├── teams/
│   └── team-cards/
└── routes/web.php
```

#### 2. サーバーで`fix.php`実行
```
https://serviceplanlab.netcoms.ne.jp/namiki/insight-box/public/fix.php
```
→ 新しいマイグレーションを自動実行

### 7.2 更新デプロイ

1. 変更ファイルのみアップロード
2. キャッシュクリア不要（Bladeは自動再コンパイル）
3. マイグレーション追加時のみ`fix.php`実行

---

## 8. テストシナリオ

### 8.1 認証フロー

1. `/register`で新規ユーザー登録
2. 自動ログイン → `/cards`にリダイレクト
3. ヘッダーにユーザー名表示
4. ログアウト → `/login`にリダイレクト
5. ログイン → Remember Me有効でCookie保存
6. ブラウザ再起動後も認証維持

### 8.2 チーム作成・招待

1. `/teams/create`でチーム作成（owner=自分）
2. `/teams/{id}/settings`でメンバー招待
3. 別ユーザーでログイン → チーム一覧に表示
4. 役割を`viewer`に変更 → 編集不可確認
5. 役割を`editor`に変更 → 編集可確認

### 8.3 カード共有

1. 個人カード作成（`visibility=private`）
2. カード詳細で「チームへ共有」ボタン押下
3. チーム選択 → 共有完了
4. `/teams/{id}/board`に表示確認
5. チームメンバーでログイン → 閲覧可確認
6. 共有解除 → チームボードから消える

### 8.4 検索・フィルター

1. チームボードで検索ワード入力
2. タイトル・メモ・OCRテキストから検索
3. タグフィルター適用
4. 作成者フィルター適用
5. 期間フィルター適用
6. 複合条件で正しく絞り込み

### 8.5 権限チェック

1. `viewer`でログイン
   - 閲覧: ✅
   - 編集: ❌ (403)
   - 削除: ❌ (403)
   - 招待: ❌ (403)

2. `editor`でログイン
   - 閲覧: ✅
   - 編集: ✅
   - 削除: ❌ (403、作成者除く)
   - 招待: ❌ (403)

3. `admin`でログイン
   - 閲覧: ✅
   - 編集: ✅
   - 削除: ✅
   - 招待: ✅

---

## 9. エラーハンドリング

### 9.1 バリデーションエラー

```blade
@if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

### 9.2 認証エラー

- 401 Unauthorized → `/login`にリダイレクト
- 403 Forbidden → エラーページ表示

### 9.3 成功メッセージ

```blade
@if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif
```

---

## 10. パフォーマンス最適化

### 10.1 N+1クエリ対策

```php
// チームボード
$cards = Card::where('team_id', $team->id)
    ->with(['owner', 'event', 'comments.user'])  // Eager Loading
    ->paginate(20);

// チーム一覧
$teams = auth()->user()->teams()
    ->withCount('members')  // メンバー数を事前集計
    ->get();
```

### 10.2 インデックス

```php
// マイグレーション
Schema::create('cards', function (Blueprint $table) {
    // ...
    $table->index('owner_user_id');
    $table->index('team_id');
    $table->index(['team_id', 'visibility']);
    $table->index('created_at');
});

Schema::create('team_user', function (Blueprint $table) {
    // ...
    $table->index(['team_id', 'user_id']);
    $table->index('user_id');
});
```

### 10.3 ページネーション

すべての一覧画面で`paginate(20)`を使用。

---

## 11. 将来拡張

### 11.1 メール送信（招待・通知）

- **現状**: ログ出力のみ（`Log::info('Invitation sent to ' . $email)`）
- **将来**: Laravel Mail + SendGrid/Mailgun
- **実装タイミング**: 本番リリース前

### 11.2 外部共有リンク

- **概要**: 未ログインユーザーへの一時共有リンク
- **実装**: 署名付きURL + 有効期限
- **テーブル**: `shared_links` (token, card_id, expires_at)

### 11.3 リアルタイム通知

- **技術**: Laravel Echo + Pusher/WebSocket
- **通知**: コメント、招待、共有

### 11.4 全文検索

- **現状**: `LIKE '%keyword%'`（MVP）
- **将来**: SQLite FTS5 or Meilisearch

### 11.5 DB移行

- **現状**: SQLite
- **将来**: PostgreSQL + RLS（Row Level Security）

---

## 12. ファイル一覧

### 12.1 新規作成ファイル

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── TeamController.php
│   │   ├── TeamBoardController.php
│   │   ├── TeamCardController.php
│   │   └── CommentController.php
│   ├── Middleware/
│   │   ├── EnsureTeamMember.php
│   │   └── EnsureTeamRole.php
│   └── Requests/
│       ├── RegisterRequest.php
│       ├── LoginRequest.php
│       ├── CreateTeamRequest.php
│       ├── InviteMemberRequest.php
│       └── StoreCommentRequest.php
├── Models/
│   ├── User.php (既存拡張)
│   ├── UserProfile.php
│   ├── Team.php
│   └── Comment.php
├── Policies/
│   ├── TeamPolicy.php
│   └── CardPolicy.php (既存拡張)

database/migrations/
├── 2025_10_17_010000_create_user_profiles_table.php
├── 2025_10_17_020000_create_teams_table.php
├── 2025_10_17_030000_create_team_user_table.php
├── 2025_10_17_040000_add_team_fields_to_cards_table.php
├── 2025_10_17_050000_create_comments_table.php
└── 2025_10_17_060000_create_team_invitations_table.php

resources/views/
├── layouts/
│   ├── guest.blade.php
│   └── team.blade.php
├── auth/
│   ├── register.blade.php
│   ├── login.blade.php
│   └── profile.blade.php
├── teams/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   ├── board.blade.php
│   └── settings.blade.php
├── team-cards/
│   ├── show.blade.php
│   └── edit.blade.php
└── components/
    ├── team-card-tile.blade.php
    ├── team-member-list.blade.php
    ├── comment-list.blade.php
    └── share-button.blade.php

routes/
└── web.php (既存拡張)
```

### 12.2 既存修正ファイル

```
app/
├── Http/Controllers/
│   ├── CardController.php (owner_user_id対応)
│   └── EventController.php (auth追加)
├── Models/
│   └── Card.php (team関連リレーション追加)

database/migrations/
└── 2025_10_17_040000_add_owner_user_id_to_cards_table.php

resources/views/
├── layouts/app.blade.php (ヘッダー拡張)
└── cards/show.blade.php (共有ボタン追加)
```

---

## 13. まとめ

### MVP完成時の機能

✅ Email/Password認証（セッション）
✅ ユーザー登録・ログイン・プロフィール
✅ チーム作成・メンバー招待・役割管理
✅ 個人カード⇔チーム共有
✅ チーム共有ボード（検索・フィルター）
✅ 役割ベース権限制御（admin/editor/viewer）
✅ コメント機能
✅ 既存機能との統合（カード・イベント管理）

### 技術的特徴

- **フルスタックPHP**: Laravel + Blade（シンプル・保守性高）
- **セッション認証**: Cookie（CSRF保護・XSS耐性）
- **SQLite**: ファイルベース（バックアップ容易）
- **FileZillaデプロイ**: SSH不要（`fix.php`で自動セットアップ）
- **段階的実装**: 3スプリント（各1週間）

### 次のステップ

1. **Sprint 1開始**: 認証基盤実装
2. **ローカル動作確認**: `php artisan serve`
3. **サーバーデプロイ**: FileZilla + `fix.php`
4. **Sprint 2-3**: チーム機能・共有ボード実装

---

**実装開始の準備が整いました！🚀**

