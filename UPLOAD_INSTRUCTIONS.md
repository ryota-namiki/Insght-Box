# ボード機能の本番環境へのアップロード手順

## FileZillaでアップロードするファイル

### 1. マイグレーションファイル（最重要）
ローカル: `insight-box-php/apps/server-php/database/migrations/`
サーバー: `/var/www/html/namiki/insight-box/database/migrations/`

アップロードするファイル:
- `2025_11_04_023848_create_boards_table.php`
- `2025_11_04_023859_add_board_id_to_cards_table.php`

### 2. アプリケーションファイル
ローカル: `insight-box-php/apps/server-php/app/`
サーバー: `/var/www/html/namiki/insight-box/app/`

アップロードするフォルダ/ファイル:
- `app/Models/Board.php` (新規)
- `app/Repositories/BoardRepository.php` (新規)
- `app/Http/Controllers/BoardController.php` (上書き)
- `app/Models/Card.php` (上書き)
- `app/Repositories/CardRepository.php` (上書き)

### 3. ビューファイル
ローカル: `insight-box-php/apps/server-php/resources/views/`
サーバー: `/var/www/html/namiki/insight-box/resources/views/`

アップロードするフォルダ/ファイル:
- `resources/views/board/` フォルダごと
- `resources/views/layouts/app.blade.php` (上書き)

### 4. ルート設定
ローカル: `insight-box-php/apps/server-php/routes/`
サーバー: `/var/www/html/namiki/insight-box/routes/`

アップロードするファイル:
- `routes/web.php` (上書き)
- `routes/api.php` (上書き)

### 5. マイグレーション実行スクリプト
ローカル: `insight-box-php/apps/server-php/public/run-migrations.php`
サーバー: `/var/www/html/namiki/insight-box/public/run-migrations.php`

## アップロード後の手順

1. ブラウザで以下にアクセス:
   ```
   https://serviceplanlab.netcoms.ne.jp/namiki/insight-box/public/run-migrations.php
   ```

2. 「マイグレーション成功」と表示されたら、セキュリティのため以下のファイルを削除:
   - `public/run-migrations.php`

3. ボード機能が使えるようになります:
   ```
   https://serviceplanlab.netcoms.ne.jp/namiki/insight-box/public/boards
   ```

## トラブルシューティング

### run-migrations.phpが404になる場合
- FileZillaで正しくアップロードされているか確認
- ファイルのパーミッションを確認（644または755）

### マイグレーションが失敗する場合
- データベースの接続設定を確認
- ログファイル `storage/logs/laravel.log` を確認

### ボード一覧が表示されない場合
- キャッシュをクリア（もしくはサーバー再起動）
- ブラウザのキャッシュもクリア

## 注意事項
- アップロード前に必ずバックアップを取ってください
- 本番環境での作業は慎重に行ってください

