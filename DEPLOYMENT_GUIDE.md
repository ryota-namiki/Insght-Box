# サーバーへのデプロイ手順

## 📋 必要な環境
- PHP 8.1以上
- Composer
- データベース（MySQL/PostgreSQL推奨、またはSQLite）
- SSH/FTP/FileZillaアクセス

## 方法1: 最小限アップロード + サーバー上でビルド（推奨）

### ステップ1: アップロードするファイル・ディレクトリ

```
insight-box-php/apps/server-php/
├── app/                    ✅ アップロード
├── bootstrap/
│   ├── app.php            ✅ アップロード
│   └── providers.php      ✅ アップロード
├── config/                ✅ アップロード
├── database/
│   ├── migrations/        ✅ アップロード
│   ├── seeders/          ✅ アップロード
│   └── factories/        ✅ アップロード
├── public/               ✅ アップロード（重要）
├── resources/            ✅ アップロード
├── routes/               ✅ アップロード
├── storage/
│   ├── app/              ✅ アップロード（空のディレクトリ構造）
│   ├── framework/        ✅ アップロード（空のディレクトリ構造）
│   └── logs/             ✅ アップロード（空のディレクトリ構造）
├── artisan               ✅ アップロード
├── composer.json         ✅ アップロード
├── composer.lock         ✅ アップロード
├── .env.example          ✅ アップロード
└── package.json          ✅ アップロード

❌ アップロードしないもの:
├── vendor/               ❌ サーバー上でcomposer installする
├── node_modules/         ❌ 不要
├── .env                  ❌ サーバーで別途作成
├── database/database.sqlite  ❌ サーバーで新規作成
└── storage/app/data/backup/  ❌ 必要に応じて
```

### ステップ2: FileZillaでアップロード

1. **FileZillaを開く**
2. **サーバーに接続**
   - ホスト: あなたのサーバーアドレス
   - ユーザー名: FTPユーザー名
   - パスワード: FTPパスワード
   - ポート: 21（FTP）または 22（SFTP）

3. **アップロード先ディレクトリを確認**
   - 通常: `/home/ユーザー名/public_html/` または `/var/www/html/`
   - ドキュメントルートの**1階層上**にアップロードするのが理想

4. **ファイルをアップロード**
   ```
   ローカル:
   /Users/ryotanamiki/Desktop/insight-box02/insight-box-php/apps/server-php/
   
   サーバー側（例）:
   /home/ユーザー名/laravel/
   ```

5. **publicディレクトリをドキュメントルートにリンク**
   ```
   サーバー上の構造:
   /home/ユーザー名/
   ├── laravel/           ← Laravelプロジェクト本体
   │   ├── app/
   │   ├── public/        ← この中身をpublic_htmlからアクセス可能にする
   │   └── ...
   └── public_html/       ← Webからアクセスされるルート
       └── (publicの内容をここに配置またはシンボリックリンク)
   ```

### ステップ3: サーバー上での設定（SSHで実行）

```bash
# 1. プロジェクトディレクトリに移動
cd /home/ユーザー名/laravel

# 2. Composer依存関係をインストール
composer install --no-dev --optimize-autoloader

# 3. .envファイルを作成
cp .env.example .env
nano .env  # または vi .env で編集

# 4. アプリケーションキーを生成
php artisan key:generate

# 5. ストレージのパーミッション設定
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 6. ストレージディレクトリ構造を確認/作成
php artisan storage:link

# 7. データベースマイグレーション
php artisan migrate

# 8. キャッシュクリア＆最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### ステップ4: .env設定例（サーバー用）

```env
APP_NAME="Insight Box"
APP_ENV=production
APP_KEY=  # php artisan key:generateで自動生成
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# データベース設定（MySQL例）
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# または SQLite を使う場合
# DB_CONNECTION=sqlite
# DB_DATABASE=/home/ユーザー名/laravel/database/database.sqlite

# その他の設定はローカルと同じでOK
```

---

## 方法2: 全ファイルアップロード（簡単だが非推奨）

FileZillaで全てアップロードする場合：

### アップロード対象
```
insight-box-php/apps/server-php/ の全内容
（vendor/とnode_modules/は除く - 巨大すぎる）
```

### 手順

1. **vendor/を除外してアップロード**
   ```
   FileZillaで右クリック → 「フィルタを追加」
   除外するディレクトリ: vendor, node_modules, .git
   ```

2. **アップロード後、SSHでvendor/を生成**
   ```bash
   cd /path/to/server-php
   composer install --no-dev
   ```

3. **データベースファイル**
   - SQLiteを使う場合: `database/database.sqlite`もアップロード
   - MySQL/PostgreSQLを使う場合: サーバーで新規作成し、マイグレーション実行

---

## 🔐 セキュリティチェックリスト

- [ ] `.env`ファイルがWebからアクセスできないことを確認
- [ ] `APP_DEBUG=false` に設定
- [ ] `APP_ENV=production` に設定
- [ ] `storage/` と `bootstrap/cache/` のパーミッションが正しいことを確認
- [ ] データベース接続情報が正しいことを確認
- [ ] publicディレクトリのみがWebからアクセス可能なことを確認

---

## 📝 トラブルシューティング

### エラー: "500 Internal Server Error"
```bash
# エラーログを確認
tail -f storage/logs/laravel.log

# パーミッション修正
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache  # ユーザー名は環境による
```

### エラー: "No application encryption key has been specified"
```bash
php artisan key:generate
```

### データベース接続エラー
- `.env`のDB設定を確認
- データベースが作成されているか確認
- マイグレーションが実行されているか確認: `php artisan migrate`

---

## 🚀 デプロイ後の確認

1. ブラウザでアクセス: `https://yourdomain.com`
2. APIエンドポイントを確認: `https://yourdomain.com/api/cards`
3. カード作成・取得ができることを確認

---

## 📞 サポート

問題が発生した場合は、以下を確認してください：
- PHPバージョン: `php -v`
- Composerバージョン: `composer --version`
- パーミッション: `ls -la storage/ bootstrap/cache/`
- エラーログ: `storage/logs/laravel.log`

