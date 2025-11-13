# Insight Box PHP Server

Laravel 11 ベースの OCR 処理サーバーです。

## 機能

- ファイル・URL アップロードによる OCR 処理
- 非同期ジョブ処理（Laravel Queue）
- JSON ファイルベースの永続化
- カード CRUD 操作
- 多言語対応（日本語、英語）

## セットアップ

### 1. 依存関係のインストール

```bash
# PHP と Composer のインストール（macOS）
brew install php composer

# プロジェクト依存関係のインストール
composer install
```

### 2. システム依存関係

```bash
# macOS
brew install tesseract imagemagick poppler

# Ubuntu/Debian
sudo apt-get install -y tesseract-ocr tesseract-ocr-jpn tesseract-ocr-jpn-vert \
    imagemagick poppler-utils
```

### 3. 環境設定

```bash
# アプリケーションキーの生成
php artisan key:generate

# キューテーブルの作成
php artisan queue:table
php artisan migrate
```

### 4. ストレージの準備

```bash
# データディレクトリの作成
mkdir -p storage/app/data storage/app/uploads

# 初期 JSON ファイルの作成
echo "{}" > storage/app/data/cards.json
echo "{}" > storage/app/data/events.json
echo "{}" > storage/app/data/jobs.json
echo "{}" > storage/app/data/documents.json
```

## 起動

### 開発環境

```bash
# Web サーバーの起動
php artisan serve

# 別ターミナルでキューワーカーの起動
php artisan queue:work
```

### 本番環境

Supervisor を使用してキューワーカーを常駐化：

```bash
# supervisor.conf.sample を参考に設定
sudo cp supervisor.conf.sample /etc/supervisor/conf.d/laravel-queue.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue:*
```

## API エンドポイント

### ドキュメント・ジョブ

- `POST /v1/documents` - ファイル・URL アップロード
- `GET /v1/jobs/{jobId}` - ジョブ進捗確認
- `GET /v1/documents/{documentId}/text` - 抽出テキスト取得

### カード

- `GET /api/board` - ボード一覧
- `GET /api/cards/{id}` - カード詳細
- `PUT /api/cards/{id}` - カード更新
- `DELETE /api/cards/{id}` - カード削除

## アーキテクチャ

- **フロントエンド**: React（既存）
- **バックエンド**: Laravel 11
- **非同期処理**: Laravel Queue（database ドライバ）
- **OCR**: thiagoalessio/tesseract_ocr
- **永続化**: JSON ファイル（将来 DB 移行可能）
- **PDF 処理**: Imagick または pdftoppm

## 設定

### CORS

`config/cors.php` でフロントエンドからのアクセスを許可

### ファイル制限

- 最大ファイルサイズ: 10MB
- 対応形式: JPEG, PNG, GIF, PDF, TXT

### 対応言語

- `eng` - 英語
- `jpn` - 日本語（横書き）
- `jpn_vert` - 日本語（縦書き）

## トラブルシューティング

### OCR が動作しない

```bash
# Tesseract のインストール確認
tesseract --version

# 学習データの確認
ls /usr/share/tesseract-ocr/4.00/tessdata/
```

### キューワーカーが動作しない

```bash
# キューテーブルの確認
php artisan tinker
>>> DB::table('jobs')->count()

# キューワーカーの手動実行
php artisan queue:work --verbose
```

## ライセンス

MIT License