# FileZilla アップロードガイド（PHP版）

## 🎉 Node.js不要版のシンプルデプロイ！

PHP版になったことで、**ビルド不要、Node.js不要**の超シンプルなデプロイが可能になりました！

---

## 📁 アップロードするディレクトリ

### ローカル（アップロード元）
```
/Users/ryotanamiki/Desktop/insight-box02/insight-box-php/apps/server-php/
```

### サーバー（アップロード先）の例
```
/home/username/laravel/
```

---

## 📤 アップロードするもの・しないもの

### ✅ アップロードする（必須）

```
insight-box-php/apps/server-php/
├── app/                    ✅ PHPコード（コントローラー、モデル、サービス等）
├── bootstrap/              ✅ Laravelブートストラップ
├── config/                 ✅ 設定ファイル（必須）
├── database/
│   ├── migrations/         ✅ マイグレーションファイル
│   ├── seeders/            ✅ シーダーファイル
│   └── factories/          ✅ ファクトリーファイル
├── public/                 ✅ 公開ディレクトリ（Webルート）
├── resources/
│   └── views/              ✅ Bladeテンプレート
├── routes/                 ✅ ルーティング定義
├── storage/                ✅ ストレージ（空のディレクトリ構造）
├── tests/                  ✅ テストファイル（任意）
├── artisan                 ✅ Artisanコマンド
├── composer.json           ✅ PHP依存関係定義
├── composer.lock           ✅ 依存関係ロックファイル
└── .env.example            ✅ 環境設定サンプル
```

### ❌ アップロードしない

```
❌ vendor/              サーバーで composer install する
❌ .env                 サーバーで別途作成する
❌ database.sqlite      サーバーで新規作成する
❌ .git/                不要
❌ .gitignore           任意
❌ storage/logs/*.log   ログファイルは不要
❌ bootstrap/cache/*    キャッシュは不要
```

---

## 🔌 FileZilla 接続設定

### 1. FileZillaを起動

### 2. サイトマネージャーを開く
**ファイル** → **サイトマネージャー** (または `Cmd + S`)

### 3. 新しいサイトを作成
**新しいサイト** をクリック

### 4. 接続情報を入力

```
サイト名: Insight-Box Production（任意）

【一般タブ】
プロトコル: SFTP - SSH File Transfer Protocol
ホスト: your-server.com
ポート: 22
ログオンタイプ: 通常
ユーザー: your_username
パスワード: your_password
```

### 5. 接続
**接続** ボタンをクリック

---

## 📂 ディレクトリマッピング

### 推奨構成

| 場所 | パス | 説明 |
|------|------|------|
| **ローカル（左側）** | `/Users/ryotanamiki/Desktop/insight-box02/insight-box-php/apps/server-php/` | アップロード元 |
| **サーバー（右側）** | `/home/username/laravel/` | Laravelプロジェクト本体 |
| **公開ディレクトリ** | `/home/username/public_html/` | Webサーバーのドキュメントルート |

### 重要：publicディレクトリの設定

**方法A: ドキュメントルートを変更（推奨）**

cPanel/Pleskの設定で：
```
ドキュメントルート: /home/username/laravel/public
```

**方法B: シンボリックリンク（SSH必要）**

```bash
ln -s /home/username/laravel/public /home/username/public_html
```

---

## 🚀 アップロード手順（ステップバイステップ）

### ステップ1: 左側（ローカル）でフォルダを開く

FileZillaの**左側ペイン**で：
```
/Users/ryotanamiki/Desktop/insight-box02/insight-box-php/apps/server-php/
```
に移動

### ステップ2: 右側（サーバー）でアップロード先を開く/作成

FileZillaの**右側ペイン**で：
```
/home/username/
```
に移動し、`laravel` フォルダを作成（なければ）

### ステップ3: アップロードするファイル・フォルダを選択

左側で以下を**Ctrlキー（Cmdキー）を押しながら複数選択**：

```
✅ app/
✅ bootstrap/
✅ config/
✅ database/
✅ public/
✅ resources/
✅ routes/
✅ storage/
✅ tests/
✅ artisan
✅ composer.json
✅ composer.lock
✅ .env.example
✅ phpunit.xml
✅ README.md
```

### ステップ4: ドラッグ＆ドロップ

選択したファイル・フォルダを**右側（サーバー）にドラッグ＆ドロップ**

または、**右クリック → アップロード**

### ステップ5: アップロード完了を待つ

数分でアップロード完了します（Node.js版より遥かに高速！）

---

## ⚙️ サーバー上での初期設定（SSH）

アップロード完了後、SSHで以下を実行：

```bash
# 1. サーバーに接続
ssh username@your-server.com

# 2. プロジェクトディレクトリに移動
cd /home/username/laravel

# 3. Composer依存関係をインストール
composer install --no-dev --optimize-autoloader

# 4. 環境設定ファイルを作成
cp .env.example .env
nano .env

# 以下を編集:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://yourdomain.com
# DB_CONNECTION=mysql (または sqlite)
# DB_DATABASE=your_database_name
# DB_USERNAME=your_db_user
# DB_PASSWORD=your_db_password
# OPENAI_API_KEY=your_openai_key

# 保存して終了: Ctrl+O, Enter, Ctrl+X

# 5. アプリケーションキー生成
php artisan key:generate

# 6. パーミッション設定
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 7. データベースマイグレーション
php artisan migrate

# 8. キャッシュ最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🌐 Webサーバー設定

### cPanel/Pleskの場合

1. **ドメイン設定** を開く
2. **ドキュメントルート** を変更：
   ```
   /home/username/laravel/public
   ```

### Apache VirtualHost の場合

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /home/username/laravel/public

    <Directory /home/username/laravel/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx の場合

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /home/username/laravel/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## ✅ 動作確認

### 1. ブラウザでアクセス
```
https://yourdomain.com
```

カード一覧ページが表示されればOK！

### 2. APIエンドポイントを確認
```
https://yourdomain.com/api/cards
```

JSONデータが返されればOK！

### 3. 機能テスト
- カード作成（3モード）
- カード一覧表示
- カード詳細表示
- ボード表示

---

## 🔄 更新時のアップロード（2回目以降）

### 変更したファイルのみアップロード

1. **FileZillaで接続**
2. **変更したファイルだけ**を選択してアップロード
3. **SSH接続してキャッシュクリア**：
   ```bash
   cd /home/username/laravel
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   
   # 再キャッシュ（本番環境）
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### マイグレーション追加した場合
```bash
php artisan migrate
```

---

## 📊 アップロードサイズ比較

| 項目 | Node.js版 | PHP版 |
|------|-----------|-------|
| **ファイル数** | 数万ファイル | **数百ファイル** |
| **サイズ** | 500MB+ | **約50MB** |
| **アップロード時間** | 30分〜1時間 | **5分以内** |
| **ビルド** | 必要 | **不要** |

---

## 🎯 FileZilla Tips

### フィルタ設定（不要なファイルを除外）

1. **表示** → **ディレクトリ比較** → **フィルタを有効化**
2. 以下を除外：
   ```
   vendor
   .env
   .git
   *.log
   database.sqlite
   bootstrap/cache/*.php
   storage/framework/cache/*
   storage/framework/sessions/*
   storage/framework/views/*
   ```

### 転送設定の最適化

1. **編集** → **設定** → **転送**
2. **同時転送数**: 3-5（サーバーによる）
3. **ファイルサイズ制限**: なし

---

## 🔧 トラブルシューティング

### エラー: "500 Internal Server Error"

```bash
# エラーログを確認
tail -f /home/username/laravel/storage/logs/laravel.log

# パーミッション修正
chmod -R 775 storage bootstrap/cache
```

### エラー: "No application encryption key"

```bash
php artisan key:generate
```

### データベース接続エラー

```bash
# .envの設定を確認
nano .env

# マイグレーション実行
php artisan migrate
```

---

## 📝 チェックリスト

### アップロード前
- [ ] ローカルで動作確認済み
- [ ] データベースのバックアップ取得済み（既存データがある場合）

### アップロード時
- [ ] `vendor/` を除外
- [ ] `.env` を除外
- [ ] ログファイルを除外

### アップロード後
- [ ] `composer install --no-dev` 実行
- [ ] `.env` ファイル作成・編集
- [ ] `php artisan key:generate` 実行
- [ ] パーミッション設定（storage, bootstrap/cache）
- [ ] `php artisan migrate` 実行
- [ ] キャッシュ最適化

### 動作確認
- [ ] ブラウザでアクセスできる
- [ ] カード一覧が表示される
- [ ] カード作成ができる
- [ ] エラーログを確認

---

## 💡 この構成の利点

✅ **Node.js不要** - PHPだけでOK  
✅ **ビルド不要** - ファイルアップロードのみ  
✅ **高速アップロード** - ファイル数が激減  
✅ **簡単更新** - 変更ファイルだけアップロード  
✅ **シンプル** - 環境構築が簡単  

---

## 🚀 初回デプロイ所要時間

- **FileZillaアップロード**: 5分
- **SSH設定**: 5分
- **合計**: **約10分**

Node.js版の1/5以下の時間でデプロイ完了！

---

## 📞 サポート

問題が発生した場合：

1. **ログ確認**: `storage/logs/laravel.log`
2. **権限確認**: `ls -la storage/ bootstrap/cache/`
3. **環境設定確認**: `php artisan config:show`

それでも解決しない場合は、エラーメッセージを確認してください。

