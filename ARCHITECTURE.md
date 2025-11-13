# Insight-Box アーキテクチャ・引き継ぎドキュメント

## 📋 目次
1. [プロジェクト概要](#プロジェクト概要)
2. [技術スタック](#技術スタック)
3. [アーキテクチャ概要](#アーキテクチャ概要)
4. [ディレクトリ構造](#ディレクトリ構造)
5. [データフロー](#データフロー)
6. [主要機能の実装](#主要機能の実装)
7. [データベース設計](#データベース設計)
8. [認証・セキュリティ](#認証セキュリティ)
9. [外部API連携](#外部api連携)
10. [開発・デプロイフロー](#開発デプロイフロー)

---

## プロジェクト概要

**Insight-Box**は、統合型インテリジェンス・ワークスペースです。名刺、資料、Webページなどをカード形式で管理し、OCR、AI要約などの機能を提供します。

### 特徴
- **PHPのみで完結** - Node.js/npm不要
- **3つの入力モード** - ファイルアップロード、Webクリップ、カメラ撮影
- **OCR機能** - 画像からテキスト抽出
- **AI要約** - OpenAI APIを使用した自動要約（300文字）
- **ボード機能** - ドラッグ&ドロップでカード配置
- **お気に入り機能** - ユーザーごとのお気に入り管理

---

## 技術スタック

### バックエンド
- **Laravel 12** - PHPフレームワーク
- **Eloquent ORM** - データベース操作
- **SQLite/MySQL/PostgreSQL** - データベース
- **Tesseract OCR** - 画像からテキスト抽出
- **OpenAI PHP Client** - AI要約機能
- **Symfony DomCrawler** - HTML解析

### フロントエンド（CDN経由）
- **Laravel Blade** - テンプレートエンジン
- **Tailwind CSS** - スタイリング
- **Alpine.js** - インタラクティブ機能
- **Lucide Icons** - アイコン

---

## アーキテクチャ概要

### 設計パターン
1. **MVC（Model-View-Controller）** - Laravelの標準パターン
2. **Repository パターン** - データアクセス層の抽象化
3. **Service パターン** - ビジネスロジックの分離

### レイヤー構造
```
┌─────────────────────────────────────┐
│   View (Blade Templates)            │
│   - ユーザーインターフェース        │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   Controller (HTTP Controllers)     │
│   - リクエスト処理                  │
│   - バリデーション                  │
│   - レスポンス生成                  │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   Repository (Data Access Layer)    │
│   - データの永続化                  │
│   - データの取得                    │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   Service (Business Logic)          │
│   - OCR処理                         │
│   - AI要約                          │
│   - Webクリップ処理                 │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   Model (Eloquent ORM)              │
│   - データベースマッピング          │
│   - リレーション定義                │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   Database (SQLite/MySQL)           │
│   - データ永続化                    │
└─────────────────────────────────────┘
```

---

## ディレクトリ構造

```
insight-box-php/apps/server-php/
├── app/
│   ├── Http/
│   │   └── Controllers/          # HTTPコントローラー
│   │       ├── CardController.php      # カード管理
│   │       ├── BoardController.php     # ボード管理
│   │       ├── AuthController.php      # 認証
│   │       ├── FavoriteController.php  # お気に入り
│   │       ├── EventController.php     # イベント管理
│   │       ├── DocumentController.php  # ドキュメント処理
│   │       └── JobController.php       # ジョブ管理
│   │
│   ├── Models/                   # Eloquentモデル
│   │   ├── Card.php              # カードモデル
│   │   ├── Board.php             # ボードモデル
│   │   ├── User.php              # ユーザーモデル
│   │   ├── Favorite.php          # お気に入りモデル
│   │   └── Event.php             # イベントモデル
│   │
│   ├── Repositories/             # リポジトリパターン
│   │   ├── CardRepository.php    # カードデータアクセス
│   │   ├── BoardRepository.php   # ボードデータアクセス
│   │   ├── EventRepository.php   # イベントデータアクセス
│   │   └── DocumentRepository.php # ドキュメントデータアクセス
│   │
│   ├── Services/                 # ビジネスロジック
│   │   ├── OcrService.php        # OCR処理
│   │   ├── PdfService.php        # PDF処理
│   │   ├── WebClipService.php    # Webクリップ処理
│   │   └── AiSummaryService.php  # AI要約処理
│   │
│   ├── Jobs/                     # 非同期ジョブ
│   │   └── OcrProcessJob.php     # OCR処理ジョブ
│   │
│   └── DTO/                      # データ転送オブジェクト
│       ├── CardSummary.php       # カードサマリー
│       └── CardDetailOutputs.php # カード詳細
│
├── routes/
│   ├── web.php                   # Webルート（認証付き）
│   └── api.php                   # APIルート
│
├── resources/views/              # Bladeテンプレート
│   ├── layouts/
│   │   └── app.blade.php         # 共通レイアウト
│   ├── cards/                    # カード画面
│   │   ├── index.blade.php       # 一覧
│   │   ├── create.blade.php      # 作成
│   │   ├── show.blade.php        # 詳細
│   │   └── edit.blade.php        # 編集
│   └── board/                    # ボード画面
│       └── show.blade.php        # ボード表示
│
├── database/
│   ├── migrations/               # マイグレーション
│   │   ├── create_cards_table.php
│   │   ├── create_boards_table.php
│   │   ├── create_favorites_table.php
│   │   └── create_events_table.php
│   └── database.sqlite          # SQLiteデータベース（開発用）
│
├── storage/
│   ├── app/
│   │   ├── private/             # プライベートファイル
│   │   ├── public/              # パブリックファイル
│   │   └── uploads/             # アップロードファイル
│   └── logs/
│       └── laravel.log          # ログファイル
│
└── config/                       # 設定ファイル
    ├── app.php
    ├── database.php
    └── cors.php
```

---

## データフロー

### 1. カード作成フロー（ファイルアップロード）

```
ユーザー → View (create.blade.php)
    ↓
ファイル選択・アップロード
    ↓
Controller (CardController::storeWeb)
    ↓
バリデーション
    ↓
DocumentController::store (ファイル保存)
    ↓
OcrProcessJob (非同期OCR処理)
    ↓
OcrService::imageToText (テキスト抽出)
    ↓
CardRepository::upsert (データベース保存)
    ↓
Model (Card) → Database
    ↓
View (show.blade.php) 表示
```

### 2. Webクリップ作成フロー

```
ユーザー → View (create.blade.php)
    ↓
URL入力
    ↓
Controller (CardController::storeWeb)
    ↓
API Route (/api/webclip/fetch)
    ↓
WebClipService::extractMetadata (メタデータ抽出)
    ↓
WebClipService::extractMainText (本文抽出)
    ↓
AiSummaryService::summarizeWebClip (AI要約)
    ↓
CardRepository::upsert (データベース保存)
    ↓
View (show.blade.php) 表示
```

### 3. カメラ撮影フロー

```
ユーザー → View (create.blade.php)
    ↓
ブラウザカメラAPI (base64画像)
    ↓
Controller (CardController::storeWeb)
    ↓
画像データを保存
    ↓
CardRepository::upsert (データベース保存)
    ↓
View (show.blade.php) 表示
```

---

## 主要機能の実装

### 1. カード管理（CardController）

#### カード一覧表示
```php
// routes/web.php
Route::get('/cards', [CardController::class, 'indexWeb']);

// CardController::indexWeb()
public function indexWeb(CardRepository $repo, EventRepository $eventRepo)
{
    $cards = $repo->listSummaries();  // カード一覧取得
    $events = $eventRepo->list();     // イベント一覧取得
    
    // お気に入り状態を追加
    foreach ($cards as &$card) {
        $cardModel = Card::find($card['id']);
        $card['isFavorited'] = $cardModel->isFavoritedBy(auth()->id());
    }
    
    return view('cards.index', compact('cards', 'events'));
}
```

#### カード作成
```php
// CardController::storeWeb()
public function storeWeb(Request $req, CardRepository $repo)
{
    // 1. バリデーション
    $validated = $req->validate([
        'title' => 'required|string|max:200',
        'companyName' => 'nullable|string|max:200',
        'memo' => 'nullable|string|max:10000',
        'tags' => 'nullable|array|max:50',
        'eventId' => 'required|string',
        'documentId' => 'nullable|string',
        'cameraImage' => 'nullable|string',
        'webclipUrl' => 'nullable|string|max:2000',
        'webclipContent' => 'nullable|string|max:50000',
    ]);
    
    // 2. カードID生成（UUID）
    $cardId = (string) \Illuminate\Support\Str::uuid();
    
    // 3. カードデータ構築
    $card = [
        'id' => $cardId,
        'summary' => [
            'title' => $validated['title'],
            'company' => $validated['companyName'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'eventId' => $validated['eventId'],
            'status' => 'draft',
            'createdAt' => now()->toIso8601String(),
            'updatedAt' => now()->toIso8601String(),
        ],
        'detail' => [
            'memo' => $validated['memo'] ?? null,
            'documentId' => $validated['documentId'] ?? null,
            'cameraImage' => $validated['cameraImage'] ?? null,
            'webclipUrl' => $validated['webclipUrl'] ?? null,
            'webclipSummary' => $validated['webclipContent'] ?? null,
        ],
        'reactions' => [
            'likes' => 0,
            'comments' => 0,
            'views' => 0,
        ],
    ];
    
    // 4. データベースに保存
    $repo->upsert($cardId, $card);
    
    // 5. リダイレクト
    return redirect()->route('cards.show', $cardId);
}
```

### 2. OCR処理（OcrService）

```php
// OcrService::imageToText()
public function imageToText(string $imagePath, string $lang = 'jpn+eng'): string
{
    // Tesseract OCRを使用してテキスト抽出
    $ocr = (new TesseractOCR($imagePath))
        ->lang(...explode('+', $lang))  // 言語指定（日本語+英語）
        ->oem(1)                         // OCRエンジンモード
        ->psm(3);                        // ページセグメンテーションモード
    
    // サーバー環境用にTesseractのパスを指定
    if (file_exists('/usr/bin/tesseract')) {
        $ocr->executable('/usr/bin/tesseract');
    }
    
    return $ocr->run();  // テキスト抽出実行
}
```

### 3. AI要約（AiSummaryService）

```php
// AiSummaryService::summarize()
public function summarize(string $text): string
{
    // OpenAI APIクライアント初期化
    $client = OpenAI::client(env('OPENAI_API_KEY'));
    
    // GPT-4o-miniを使用して要約
    $response = $client->chat()->create([
        'model' => 'gpt-4o-mini',
        'messages' => [
            [
                'role' => 'system',
                'content' => '300文字以内で要約してください。'
            ],
            [
                'role' => 'user',
                'content' => "以下のテキストを要約してください：\n\n{$text}"
            ]
        ],
        'max_tokens' => 500,
        'temperature' => 0.2,
    ]);
    
    $summary = $response->choices[0]->message->content;
    
    // 300文字以内に調整
    if (mb_strlen($summary) > 300) {
        $summary = mb_substr($summary, 0, 300);
    }
    
    return trim($summary);
}
```

### 4. Webクリップ処理（WebClipService）

```php
// WebClipService::extractMetadata()
public function extractMetadata(string $html): array
{
    $crawler = new Crawler($html);
    
    // タイトル抽出
    $title = $crawler->filter('title')->first()->text();
    
    // メタディスクリプション抽出
    $description = $crawler->filter('meta[name="description"]')
        ->first()->attr('content') ?? '';
    
    // OGタグからも取得を試行
    if (empty($description)) {
        $description = $crawler->filter('meta[property="og:description"]')
            ->first()->attr('content') ?? '';
    }
    
    return [
        'title' => trim($title),
        'description' => trim($description),
    ];
}

// WebClipService::extractMainText()
public function extractMainText(string $html): string
{
    $crawler = new Crawler($html);
    $parts = [];
    
    // タイトルを追加
    $title = $crawler->filter('title')->first();
    if ($title->count()) {
        $parts[] = trim($title->text());
    }
    
    // 見出しと段落を抽出
    $crawler->filter('h1,h2,h3,p,article')->each(function ($node) use (&$parts) {
        $text = trim($node->text());
        if (mb_strlen($text) >= 10) {
            $parts[] = $text;
        }
    });
    
    return implode("\n\n", $parts);
}
```

### 5. リポジトリパターン（CardRepository）

```php
// CardRepository::upsert()
public function upsert(string $id, array $record): void
{
    // リポジトリ層でデータを整形
    $data = [
        'id' => $id,
        'title' => $record['summary']['title'] ?? '',
        'company' => $record['summary']['company'] ?? null,
        'tags' => $record['summary']['tags'] ?? [],
        'event_id' => $record['summary']['eventId'] ?? '',
        'memo' => $record['detail']['memo'] ?? null,
        'ocr_text' => $record['detail']['text'] ?? null,
        'document_id' => $record['detail']['documentId'] ?? null,
        'camera_image' => $record['detail']['cameraImage'] ?? null,
        'webclip_url' => $record['detail']['webclipUrl'] ?? null,
        'webclip_summary' => $record['detail']['webclipSummary'] ?? null,
        'owner_user_id' => auth()->id(),  // 現在のユーザーID
    ];
    
    // Eloquentモデルを使用してデータベースに保存
    Card::updateOrCreate(['id' => $id], $data);
}

// CardRepository::toArray() - モデルを配列形式に変換
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
            'status' => $card->status,
            'createdAt' => $card->created_at->toIso8601String(),
        ],
        'detail' => [
            'memo' => $card->memo,
            'text' => $card->ocr_text,
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
    ];
}
```

### 6. ドキュメント処理（DocumentController）

```php
// DocumentController::store() - ファイルアップロード・OCR処理
public function store(Request $req, DocumentRepository $docs, JobRepository $jobs)
{
    // 1. バリデーション
    $validated = $req->validate([
        'file' => 'nullable|file|mimetypes:image/jpeg,image/png,application/pdf|max:10240',
        'url' => 'nullable|url',
        'lang' => 'nullable|string',
    ]);
    
    // 2. ドキュメントID生成
    $documentId = (string) Str::uuid();
    
    // 3. ファイル保存
    if ($req->hasFile('file')) {
        $storedPath = $req->file('file')->store("uploads/{$documentId}", 'local');
    } else {
        // URLから取得
        $content = file_get_contents($validated['url']);
        $storedPath = "uploads/{$documentId}/remote.html";
        Storage::disk('local')->put($storedPath, $content);
    }
    
    // 4. ドキュメント情報を保存
    $docs->upsert($documentId, [
        'id' => $documentId,
        'source_type' => $req->hasFile('file') ? 'upload' : 'url',
        'path' => $storedPath,
        'lang' => $validated['lang'] ?? 'jpn+eng',
    ]);
    
    // 5. OCR処理を実行
    $ocrService = app(OcrService::class);
    $text = $ocrService->imageOrTextToText($path, $validated['lang'] ?? 'jpn+eng');
    
    // 6. テキストを保存
    $docs->updateText($documentId, $text);
    
    return response()->json([
        'document_id' => $documentId,
        'text' => $text,
    ]);
}
```

### 7. お気に入り機能（FavoriteController）

```php
// FavoriteController::toggle() - お気に入り状態をトグル
public function toggle(Request $request)
{
    $validated = $request->validate([
        'card_id' => 'required|string|exists:cards,id',
    ]);
    
    $userId = auth()->id();
    $cardId = $validated['card_id'];
    
    // 既存のお気に入りを確認
    $favorite = Favorite::where('user_id', $userId)
        ->where('card_id', $cardId)
        ->first();
    
    if ($favorite) {
        // 削除
        $favorite->delete();
        return response()->json([
            'success' => true,
            'isFavorited' => false,
        ]);
    } else {
        // 追加
        Favorite::create([
            'user_id' => $userId,
            'card_id' => $cardId,
        ]);
        return response()->json([
            'success' => true,
            'isFavorited' => true,
        ]);
    }
}
```

---

## データベース設計

### cards テーブル

```sql
CREATE TABLE cards (
    id UUID PRIMARY KEY,
    
    -- Summary fields
    title VARCHAR(200) NOT NULL,
    company VARCHAR(200),
    tags JSON,
    event_id VARCHAR(255) NOT NULL,
    author_id VARCHAR(255),
    status VARCHAR(50) DEFAULT 'draft',
    
    -- Detail fields
    memo TEXT,
    ocr_text TEXT,
    raw_text TEXT,
    document_id VARCHAR(255),
    camera_image LONGTEXT,
    webclip_url VARCHAR(2000),
    webclip_summary TEXT,
    
    -- Reactions
    likes INTEGER DEFAULT 0,
    comments INTEGER DEFAULT 0,
    views INTEGER DEFAULT 0,
    
    -- Position (for board)
    position_x INTEGER,
    position_y INTEGER,
    
    -- Relations
    board_id UUID,
    owner_user_id INTEGER,
    team_id UUID,
    visibility VARCHAR(50) DEFAULT 'private',
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_event_id (event_id),
    INDEX idx_owner_user_id (owner_user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

### boards テーブル

```sql
CREATE TABLE boards (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    owner_user_id INTEGER NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### favorites テーブル

```sql
CREATE TABLE favorites (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    user_id INTEGER NOT NULL,
    card_id UUID NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_user_card (user_id, card_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (card_id) REFERENCES cards(id)
);
```

### events テーブル

```sql
CREATE TABLE events (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 認証・セキュリティ

### 認証フロー

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    // 認証が必要なルート
    Route::get('/cards', [CardController::class, 'indexWeb']);
    Route::post('/cards', [CardController::class, 'storeWeb']);
    // ...
});

// AuthController::login()
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);
    
    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/cards');
    }
    
    return back()->withErrors([
        'email' => 'ログイン情報が正しくありません。',
    ]);
}
```

### セキュリティ対策

1. **CSRF保護** - Laravelの標準機能（全フォーム）
2. **XSS対策** - Bladeテンプレートの自動エスケープ
3. **SQLインジェクション対策** - Eloquent ORMのパラメータバインド
4. **認証ミドルウェア** - `auth`ミドルウェアで保護
5. **環境変数管理** - `.env`ファイルで機密情報管理

---

## 外部API連携

### 1. OpenAI API（AI要約）

```php
// AiSummaryService.php
$client = OpenAI::client(env('OPENAI_API_KEY'));
$response = $client->chat()->create([
    'model' => 'gpt-4o-mini',
    'messages' => [...],
]);
```

### 2. Tesseract OCR（テキスト抽出）

```php
// OcrService.php
$ocr = (new TesseractOCR($imagePath))
    ->lang('jpn+eng')
    ->run();
```

### 3. Webページ取得（Webクリップ）

```php
// api.php
$html = file_get_contents($url, false, stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'Mozilla/5.0 (compatible; InsightBox/1.0)'
    ]
]));
```

---

## 開発・デプロイフロー

### ローカル開発

```bash
# 1. 依存関係インストール
composer install

# 2. 環境設定
cp .env.example .env
php artisan key:generate

# 3. データベースマイグレーション
php artisan migrate

# 4. 開発サーバー起動
php artisan serve
```

### 本番デプロイ

```bash
# 1. ファイルアップロード（FileZilla）
# app/, bootstrap/, config/, database/, public/, 
# resources/, routes/, storage/, vendor/, artisan, composer.json

# 2. 依存関係インストール
composer install --no-dev

# 3. 環境設定
# .envファイルを編集

# 4. データベースマイグレーション
php artisan migrate --force

# 5. キャッシュ最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 主要なAPIエンドポイント

### Webルート（認証必須）

| メソッド | パス | コントローラー | 説明 |
|---------|------|--------------|------|
| GET | `/cards` | CardController::indexWeb | カード一覧 |
| GET | `/cards/create` | CardController::createWeb | カード作成画面 |
| POST | `/cards` | CardController::storeWeb | カード作成 |
| GET | `/cards/{id}` | CardController::showWeb | カード詳細 |
| GET | `/cards/{id}/edit` | CardController::editWeb | カード編集画面 |
| PUT | `/cards/{id}` | CardController::updateWeb | カード更新 |
| DELETE | `/cards/{id}` | CardController::destroyWeb | カード削除 |

### APIルート

| メソッド | パス | 説明 |
|---------|------|------|
| POST | `/api/v1/documents` | ドキュメントアップロード |
| GET | `/api/v1/jobs/{id}` | ジョブ進捗確認 |
| GET | `/api/v1/documents/{id}/text` | 抽出テキスト取得 |
| GET | `/api/v1/documents/{id}/image` | 画像取得 |
| POST | `/api/webclip/fetch` | Webクリップ取得 |
| POST | `/api/ai/summarize` | AI要約 |
| GET | `/api/events` | イベント一覧 |
| POST | `/api/favorites/toggle` | お気に入りトグル |

---

## トラブルシューティング

### よくある問題

1. **OCRが動作しない**
   - Tesseractがインストールされているか確認
   - 言語データ（jpn, eng）がインストールされているか確認

2. **AI要約が失敗する**
   - `.env`ファイルに`OPENAI_API_KEY`が設定されているか確認
   - APIキーが有効か確認

3. **ファイルアップロードが失敗する**
   - `storage/app/private/`のパーミッションを確認（775推奨）
   - `php.ini`の`upload_max_filesize`を確認

4. **データベース接続エラー**
   - `.env`ファイルのデータベース設定を確認
   - マイグレーションが実行されているか確認

---

## 次のステップ（拡張案）

1. **チーム機能** - 複数ユーザーでの共有
2. **コメント機能** - カードへのコメント
3. **通知機能** - リアルタイム通知
4. **検索機能** - 全文検索（Elasticsearch）
5. **エクスポート機能** - PDF/Excel出力
6. **API認証** - APIキー認証
7. **バッチ処理** - 大量データ処理

---

## まとめ

Insight-Boxは、**Laravel 12**をベースにした、**PHPのみで動作する**カード管理システムです。

### 主要なポイント

1. **Repository パターン** - データアクセス層の抽象化
2. **Service パターン** - ビジネスロジックの分離
3. **Eloquent ORM** - データベース操作の簡素化
4. **非同期処理** - OCR処理をジョブキューで実行
5. **外部API連携** - OpenAI、Tesseract OCRとの連携

### 引き継ぎ時の確認事項

- [ ] `.env`ファイルの設定確認
- [ ] データベース接続確認
- [ ] 外部API（OpenAI、Tesseract）の設定確認
- [ ] ストレージのパーミッション確認
- [ ] ログファイルの場所確認
- [ ] デプロイ手順の確認

---

## 関連ドキュメント

### 必須ドキュメント

1. **ARCHITECTURE.md** (このファイル)
   - システム全体のアーキテクチャとコードの仕組み
   - 対象: 開発者、引き継ぎ担当者

2. **FILEZILLA_SIMPLE_GUIDE.md**
   - FileZillaを使用した詳細なデプロイ手順
   - 対象: デプロイ担当者、運用担当者
   - 内容: 具体的なアップロード手順、トラブルシューティング、チェックリスト

3. **README.md**
   - プロジェクト概要とクイックスタート
   - 対象: 全員

### 将来実装時のみ必要

4. **docs/future/LARAVEL_AUTH_TEAM_DESIGN.md**
   - 認証とチーム機能の設計書（将来実装予定）
   - 対象: 開発者（将来実装時）
   - 注意: 現在は未実装の機能
   - 場所: `docs/future/` ディレクトリに保存

### ドキュメント整理

詳細は **DOCUMENTATION_INDEX.md** を参照してください。

**注意**: `UPLOAD_INSTRUCTIONS.md` は `FILEZILLA_SIMPLE_GUIDE.md` に統合されました。

---

**最終更新日**: 2025年11月13日
**バージョン**: 1.0.0

