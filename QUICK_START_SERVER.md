# 🚀 サーバーデプロイ クイックスタートガイド

## 📝 概要

このガイドでは、FileZillaを使ってInsight-Boxをサーバーにアップロードする手順を**最短ステップ**で説明します。

---

## ✅ 前提条件

- [ ] サーバーにFTP/SFTPでアクセスできる
- [ ] サーバーにSSHでアクセスできる（Composer実行のため）
- [ ] PHP 8.1以上がインストールされている
- [ ] Composerがインストールされている
- [ ] MySQLまたはPostgreSQLが利用可能（またはSQLite）

---

## 🎯 3ステップデプロイ

### ステップ1️⃣: FileZillaでファイルアップロード（10分）

1. **FileZillaを起動して接続**
   ```
   ホスト: your-server.com
   ユーザー名: your_username
   パスワード: your_password
   ポート: 22 (SFTP推奨)
   ```

2. **左側（ローカル）でプロジェクトを開く**
   ```
   /Users/ryotanamiki/Desktop/insight-box02/insight-box-php/apps/server-php/
   ```

3. **右側（サーバー）でアップロード先を開く**
   ```
   /home/username/laravel/  （新規作成）
   ```

4. **以下のフォルダ・ファイルを選択してドラッグ＆ドロップ**
   ```
   ✅ app/
   ✅ bootstrap/
   ✅ config/
   ✅ database/
   ✅ public/
   ✅ resources/
   ✅ routes/
   ✅ storage/
   ✅ artisan
   ✅ composer.json
   ✅ composer.lock
   ✅ .env.example
   
   ❌ vendor/      ← これはアップロードしない
   ❌ node_modules/ ← これはアップロードしない
   ❌ .env         ← これはアップロードしない
   ```

5. **アップロードを待つ** （数分かかります）

---

### ステップ2️⃣: SSH接続してセットアップ（5分）

ターミナル（Macの場合）またはPuTTY（Windowsの場合）を開く：

```bash
# 1. サーバーに接続
ssh username@your-server.com

# 2. プロジェクトディレクトリに移動
cd /home/username/laravel

# 3. Composer依存関係をインストール
composer install --no-dev --optimize-autoloader

# 4. 環境設定ファイルを作成
cp .env.example .env
nano .env  # エディタで編集（次の設定例を参照）

# 5. アプリケーションキー生成
php artisan key:generate

# 6. パーミッション設定
chmod -R 775 storage bootstrap/cache

# 7. データベースマイグレーション
php artisan migrate

# 8. キャッシュ最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### .env 設定例（nano/vimで編集）

```env
APP_NAME="Insight Box"
APP_ENV=production
APP_KEY=  # ← php artisan key:generateで自動入力される
APP_DEBUG=false
APP_URL=https://yourdomain.com

# データベース（MySQL例）
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# または SQLite を使う場合
# DB_CONNECTION=sqlite
# DB_DATABASE=/home/username/laravel/database/database.sqlite
```

保存して終了: `Ctrl + O`, `Enter`, `Ctrl + X`

---

### ステップ3️⃣: Webサーバー設定（5分）

#### パターンA: Apache + cPanel/Plesk（共有ホスティング）

1. **cPanel/Pleskにログイン**
2. **「ドメイン」または「Webサイト」設定を開く**
3. **ドキュメントルートを変更**:
   ```
   変更前: /home/username/public_html
   変更後: /home/username/laravel/public
   ```

#### パターンB: Apache（VPS/専用サーバー）

`/etc/apache2/sites-available/yourdomain.conf` を編集:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /home/username/laravel/public

    <Directory /home/username/laravel/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/laravel-error.log
    CustomLog ${APACHE_LOG_DIR}/laravel-access.log combined
</VirtualHost>
```

```bash
sudo a2ensite yourdomain.conf
sudo systemctl reload apache2
```

#### パターンC: Nginx（VPS/専用サーバー）

`/etc/nginx/sites-available/yourdomain` を編集:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /home/username/laravel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/yourdomain /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🎉 完了！動作確認

ブラウザで以下にアクセス:

1. **トップページ**: `https://yourdomain.com`
2. **API確認**: `https://yourdomain.com/api/cards`

正常に動作していれば、カードのデータがJSON形式で表示されます！

---

## 🔧 トラブルシューティング

### エラー: "500 Internal Server Error"

```bash
# エラーログを確認
tail -f /home/username/laravel/storage/logs/laravel.log

# よくある原因:
# 1. パーミッション問題
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 2. .envファイルの設定ミス
nano .env  # 再確認

# 3. キャッシュの問題
php artisan config:clear
php artisan cache:clear
```

### エラー: "No application encryption key"

```bash
cd /home/username/laravel
php artisan key:generate
```

### データベース接続エラー

```bash
# MySQLの場合、データベースが存在するか確認
mysql -u your_db_user -p
> SHOW DATABASES;
> CREATE DATABASE your_database_name;
> exit;

# マイグレーション再実行
php artisan migrate
```

### ページが表示されない（404エラー）

- Webサーバーの設定を確認（DocumentRootが正しいか）
- `.htaccess`がアップロードされているか確認
- `mod_rewrite`が有効になっているか確認（Apache）

---

## 📊 デプロイ状態の確認コマンド

```bash
# 現在地確認
pwd

# ファイル一覧
ls -la

# PHPバージョン
php -v

# Composerバージョン
composer --version

# データベース接続テスト
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB Connected!' . PHP_EOL;"

# カード数確認
php artisan tinker --execute="echo 'Cards: ' . App\Models\Card::count() . PHP_EOL;"
```

---

## 🔄 更新手順（コード変更時）

1. **FileZillaで変更ファイルをアップロード**
2. **SSHでキャッシュクリア**:
   ```bash
   cd /home/username/laravel
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   
   # 再キャッシュ
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **DBスキーマ変更があった場合**:
   ```bash
   php artisan migrate
   ```

---

## 💡 便利なコマンド集

```bash
# アプリケーション情報
php artisan about

# ルート一覧
php artisan route:list

# 設定確認
php artisan config:show database

# ログをリアルタイム表示
tail -f storage/logs/laravel.log

# ディスク使用量
du -sh *

# プロセス確認
ps aux | grep php
```

---

## 📞 困ったときは

1. **エラーログを確認**: `storage/logs/laravel.log`
2. **パーミッションを確認**: `ls -la storage/ bootstrap/cache/`
3. **環境設定を確認**: `php artisan config:show`
4. **データベース接続を確認**: `php artisan db:show`

それでも解決しない場合は、サーバー管理者またはホスティングサポートに連絡してください。

