# Insight-Box (PHP版)

📦 **統合型インテリジェンス・ワークスペース**

PHPのみで実装された、シンプルで高速なカード管理システム。Node.js/npm/ビルド不要でサーバーデプロイが超簡単！

---

## 🎯 特徴

- ✅ **PHPのみで完結** - Node.js/npm不要
- ✅ **ビルド不要** - ファイルをアップロードするだけ
- ✅ **高速デプロイ** - 5-10分で本番稼働
- ✅ **3つの入力モード** - ファイル、Webクリップ、カメラ撮影
- ✅ **OpenAI統合** - AI要約機能（300文字）
- ✅ **レスポンシブデザイン** - PC/スマホ対応
- ✅ **データベース対応** - SQLite/MySQL/PostgreSQL

---

## 📁 プロジェクト構成

```
insight-box02/
├── ARCHITECTURE.md              # アーキテクチャ・引き継ぎドキュメント
├── DOCUMENTATION_INDEX.md       # ドキュメント一覧
├── FILEZILLA_SIMPLE_GUIDE.md   # FileZillaデプロイガイド
├── README.md                    # プロジェクト概要（このファイル）
├── docs/
│   └── future/                  # 将来実装予定の機能
│       └── LARAVEL_AUTH_TEAM_DESIGN.md
├── 資料/                        # 設計書・資料
│   ├── insight-box-php-設計書.pdf
│   └── ...
└── insight-box-php/apps/server-php/
    ├── app/                     # アプリケーションコード
    │   ├── Console/
    │   │   └── Commands/        # Artisanコマンド
    │   ├── DTO/                 # データ転送オブジェクト
    │   ├── Http/
    │   │   ├── Controllers/     # HTTPコントローラー
    │   │   └── Requests/        # フォームリクエスト
    │   ├── Jobs/                # 非同期ジョブ
    │   ├── Models/              # Eloquentモデル
    │   ├── Policies/            # 認可ポリシー
    │   ├── Providers/           # サービスプロバイダー
    │   ├── Repositories/        # リポジトリパターン
    │   └── Services/            # ビジネスロジック
    ├── bootstrap/               # ブートストラップ
    │   ├── app.php
    │   ├── cache/               # キャッシュファイル
    │   └── providers.php
    ├── config/                  # 設定ファイル
    ├── database/
    │   ├── factories/           # ファクトリー
    │   ├── migrations/          # マイグレーション
    │   └── seeders/             # シーダー
    ├── public/                  # 公開ディレクトリ
    │   ├── index.php
    │   ├── favicon.ico
    │   └── robots.txt
    ├── resources/views/         # Bladeテンプレート
    │   ├── layouts/             # 共通レイアウト
    │   ├── auth/                # 認証画面
    │   ├── cards/               # カード画面
    │   ├── board/               # ボード画面
    │   ├── events/              # イベント画面
    │   └── favorites/           # お気に入り画面
    ├── routes/
    │   ├── web.php              # Webルート（認証付き）
    │   ├── api.php              # APIルート
    │   └── console.php          # コンソールルート
    ├── storage/                 # ストレージ
    │   ├── app/
    │   │   ├── data/            # データファイル
    │   │   ├── private/         # プライベートファイル
    │   │   ├── public/          # パブリックファイル
    │   │   └── uploads/         # アップロードファイル
    │   ├── framework/
    │   │   ├── cache/           # フレームワークキャッシュ
    │   │   ├── sessions/        # セッションファイル
    │   │   ├── testing/         # テストファイル
    │   │   └── views/           # コンパイル済みビュー
    │   └── logs/                # ログファイル
    ├── tests/                   # テスト
    │   ├── Feature/             # 機能テスト
    │   └── Unit/                # ユニットテスト
    ├── vendor/                  # Composer依存関係
    ├── artisan                  # Artisanコマンド
    ├── composer.json            # Composer設定
    ├── composer.lock            # Composerロックファイル
    └── phpunit.xml              # PHPUnit設定
```

---

## 🚀 ローカル環境での起動

### 必要な環境

- PHP 8.1以上
- Composer
- SQLite/MySQL/PostgreSQL

### セットアップ

```bash
# プロジェクトディレクトリに移動
cd insight-box-php/apps/server-php

# Composer依存関係をインストール
composer install

# 環境設定ファイルをコピー
cp .env.example .env

# アプリケーションキーを生成
php artisan key:generate

# データベースマイグレーション
php artisan migrate

# 開発サーバー起動
php artisan serve
```

ブラウザで http://localhost:8000 にアクセス

---

## 🎨 主な機能

### 1. カード作成（3モード）

#### 📄 ファイルアップロード
- 画像（JPG, PNG）とPDF対応
- ドラッグ&ドロップ対応
- OCRテキスト抽出

#### 🌐 Webクリップ
- URLからWebページ取得
- OpenAI API で自動要約（300文字）
- タイトル自動抽出

#### 📷 カメラ撮影
- ブラウザのカメラAPI使用
- 高画質撮影（最大1920x1080）
- 撮り直し機能

### 2. カード管理

- **一覧表示** - 検索・フィルター機能
- **詳細表示** - プレビュー、テキスト、メモ
- **編集機能** - タイトル、会社名、タグ、メモ
- **削除機能** - 確認ダイアログ付き

### 3. ボード表示

- **ドラッグ&ドロップ** - カードを自由に配置
- **自動保存** - 位置をデータベースに保存
- **グリッド背景** - 見やすいレイアウト

---

## 🛠️ 技術スタック

### バックエンド
- **Laravel 12** - PHPフレームワーク
- **Eloquent ORM** - データベース操作
- **OpenAI PHP Client** - AI要約機能
- **Tesseract OCR** - テキスト抽出
- **Symfony DomCrawler** - HTML解析

### フロントエンド（CDN経由）
- **Laravel Blade** - テンプレートエンジン
- **Tailwind CSS** - スタイリング
- **Alpine.js** - インタラクティブ機能
- **Material Icons** - アイコン

### データベース
- SQLite（開発）
- MySQL/PostgreSQL（本番推奨）

---

## 📦 サーバーデプロイ

### FileZillaでアップロード

詳細は **`FILEZILLA_SIMPLE_GUIDE.md`** を参照

#### 簡潔な手順

1. **FileZillaで接続**
2. **以下をアップロード:**
   ```
   app/, bootstrap/, config/, database/, public/, 
   resources/, routes/, storage/, tests/, 
   artisan, composer.json, composer.lock
   ```
3. **SSHで初期設定:**
   ```bash
   composer install --no-dev
   php artisan key:generate
   php artisan migrate
   php artisan optimize
   ```

**所要時間: 約10分** ⚡

---

## 🔧 環境変数設定

`.env` ファイルで以下を設定：

```env
# アプリケーション
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# データベース
DB_CONNECTION=mysql
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# OpenAI API（Webクリップ要約用）
OPENAI_API_KEY=sk-proj-your-key-here
```

---

## 📊 Node.js版との比較

| 項目 | Node.js版 | PHP版 |
|------|-----------|-------|
| 環境 | PHP + Node.js + npm | **PHPのみ** |
| ビルド | 必要（数分） | **不要** |
| デプロイ | 複雑（30-60分） | **超簡単（10分）** |
| ファイル数 | 数万ファイル | **数百ファイル** |
| サイズ | 500MB+ | **約50MB** |
| 更新 | ビルド→転送 | **転送のみ** |

---

## 🎯 API エンドポイント

| メソッド | パス | 概要 |
|----------|------|------|
| GET | `/api/cards` | カード一覧 |
| POST | `/api/cards` | カード作成 |
| GET | `/api/cards/{id}` | カード詳細 |
| PUT | `/api/cards/{id}` | カード更新 |
| DELETE | `/api/cards/{id}` | カード削除 |
| PUT | `/api/cards/{id}/position` | 位置更新 |
| GET | `/api/board` | ボードカード一覧 |
| GET | `/api/events` | イベント一覧 |
| POST | `/api/webclip/fetch` | Webクリップ取得 |
| POST | `/api/ai/summarize` | AI要約 |

---

## 🔄 データベース

### マイグレーション

```bash
# マイグレーション実行
php artisan migrate

# ロールバック
php artisan migrate:rollback

# リフレッシュ
php artisan migrate:fresh
```

### データ移行（JSON→DB）

既存のJSONデータがある場合：

```bash
php artisan cards:migrate-from-json
```

---

## 🧪 テスト

```bash
# テスト実行
php artisan test

# 特定のテスト
php artisan test --filter CardTest
```

---

## 📝 開発コマンド

```bash
# 開発サーバー起動
php artisan serve

# キャッシュクリア
php artisan optimize:clear

# ログ表示
tail -f storage/logs/laravel.log

# データベース確認
php artisan db:show

# ルート一覧
php artisan route:list
```

---

## 🔐 セキュリティ

- CSRF保護（全フォーム）
- XSS対策（Blade自動エスケープ）
- SQLインジェクション対策（Eloquent ORM）
- 環境変数で機密情報管理

---

## 📚 ドキュメント

### 必須ドキュメント
- **ARCHITECTURE.md** - システム全体のアーキテクチャ・引き継ぎドキュメント
- **FILEZILLA_SIMPLE_GUIDE.md** - サーバーデプロイ完全ガイド（FileZilla手順）
- **README.md** - プロジェクト概要（このファイル）

### ドキュメント一覧
- **DOCUMENTATION_INDEX.md** - ドキュメント一覧・整理状況

### 将来実装予定
- **docs/future/LARAVEL_AUTH_TEAM_DESIGN.md** - 認証・チーム機能の設計書（将来実装予定）

詳細は **ARCHITECTURE.md** の「関連ドキュメント」セクションを参照してください。

---

## 🤝 サポート

問題が発生した場合：

1. **ログ確認**: `storage/logs/laravel.log`
2. **設定確認**: `php artisan about`
3. **環境確認**: `php artisan config:show`

---

## ⚡ まとめ

**完全にPHPだけで動作する、シンプルで強力なカード管理システム**

- Node.js不要
- ビルド不要
- デプロイ超簡単
- 本番運用対応

**FileZillaでアップロードして、すぐに使えます！** 🚀
