# FileZillaアップロードチェックリスト

## 📋 アップロード前の準備

### 1. ローカルで最終確認
- [ ] `php artisan config:clear` を実行
- [ ] `php artisan cache:clear` を実行
- [ ] アプリケーションが正常に動作することを確認

### 2. データベースのバックアップ（既存データがある場合）
```bash
# SQLiteの場合
cp insight-box-php/apps/server-php/database/database.sqlite ~/Desktop/database.sqlite.backup

# データ移行コマンドも保存されているので、サーバーでも実行可能
# php artisan cards:migrate-from-json
```

---

## 🔌 FileZilla接続設定

### サーバー情報を入力
1. **ファイル** → **サイトマネージャー** を開く
2. **新しいサイト** をクリック
3. 以下を入力：

```
サイト名: Insight Box Production（任意の名前）
プロトコル: SFTP - SSH File Transfer Protocol（推奨）
         または FTP - File Transfer Protocol
ホスト: your-server.com
ポート: 22（SFTP）または 21（FTP）
ログオンタイプ: 通常
ユーザー: your_username
パスワード: your_password
```

4. **接続** をクリック

---

## 📁 ディレクトリマッピング

### ローカル → サーバー

| ローカルパス | サーバーパス（例） | 説明 |
|------------|-----------------|------|
| `/Users/ryotanamiki/Desktop/insight-box02/insight-box-php/apps/server-php/` | `/home/username/laravel/` | Laravelプロジェクト本体 |
| → `public/` | → `/home/username/public_html/` | 公開ディレクトリ（別途設定）|

### 重要：publicディレクトリの配置

**パターンA: シンボリックリンク（推奨）**
```bash
# SSH接続して実行
ln -s /home/username/laravel/public /home/username/public_html

# または .htaccessでリダイレクト
```

**パターンB: publicの中身をコピー**
```
laravel/public/ の中身
→ public_html/ にコピー

そして public_html/index.php を編集:
require __DIR__.'/../laravel/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel/bootstrap/app.php';
```

---

## 📤 アップロード手順

### ステップ1: 左側（ローカル）で対象フォルダを開く
```
/Users/ryotanamiki/Desktop/insight-box02/insight-box-php/apps/server-php/
```

### ステップ2: 右側（サーバー）でアップロード先を指定
```
/home/username/laravel/
```

### ステップ3: フィルタを設定
1. **表示** → **ディレクトリ比較** → **フィルタを有効化**
2. **フィルタルール**:
   - `vendor` を除外
   - `node_modules` を除外
   - `.git` を除外
   - `.env` を除外
   - `*.log` を除外
   - `database.sqlite` を除外（本番用は別途作成）

### ステップ4: アップロード実行

#### 方法A: 必要なディレクトリのみを選択してアップロード
以下を選択して右クリック → **アップロード**:

```
✅ 必須ディレクトリ:
├── app/
├── bootstrap/
│   ├── app.php
│   ├── cache/        ← 空のディレクトリ構造のみ
│   └── providers.php
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── public/
├── resources/
├── routes/
├── storage/
│   ├── app/          ← 空のディレクトリ構造のみ
│   ├── framework/    ← 空のディレクトリ構造のみ
│   └── logs/         ← 空のディレクトリ構造のみ
├── artisan
├── composer.json
├── composer.lock
└── .env.example

❌ 除外:
├── vendor/           ← サーバーでcomposer install
├── node_modules/     ← 不要
└── .env              ← サーバーで別途作成
```

#### 方法B: すべて選択してアップロード（フィルタ有効）
1. 左側で `server-php` フォルダ内の全ファイル・フォルダを選択
2. 右クリック → **アップロード**
3. フィルタが有効なので、除外対象は自動的にスキップされます

---

## ⚙️ アップロード完了後の作業（SSH必須）

### 1. サーバーにSSH接続
```bash
ssh username@your-server.com
```

### 2. プロジェクトディレクトリに移動
```bash
cd /home/username/laravel
```

### 3. Composer依存関係をインストール
```bash
composer install --no-dev --optimize-autoloader
```

### 4. 環境設定ファイルを作成
```bash
cp .env.example .env
nano .env  # または vim .env
```

以下を編集:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql  # または sqlite
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 5. アプリケーションキー生成
```bash
php artisan key:generate
```

### 6. パーミッション設定
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Webサーバーユーザーに所有権を付与（環境によってユーザー名が異なる）
# Apache: www-data または apache
# Nginx: nginx または www-data
chown -R www-data:www-data storage bootstrap/cache
```

### 7. データベースセットアップ

#### SQLiteを使う場合:
```bash
touch database/database.sqlite
chmod 664 database/database.sqlite
chown www-data:www-data database/database.sqlite
php artisan migrate
```

#### MySQL/PostgreSQLを使う場合:
```bash
# データベースが作成されていることを確認してから
php artisan migrate
```

### 8. 既存データがある場合（JSONからの移行）
```bash
# JSONファイルをアップロードしている場合
php artisan cards:migrate-from-json
```

### 9. 最適化
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ✅ 動作確認

### 1. ブラウザでアクセス
```
https://yourdomain.com
```

### 2. APIエンドポイントを確認
```
https://yourdomain.com/api/cards
```

正常に動作していれば、カードの一覧がJSON形式で返されます。

### 3. エラーが出た場合
```bash
# エラーログを確認
tail -f storage/logs/laravel.log

# パーミッションを再確認
ls -la storage/
ls -la bootstrap/cache/
```

---

## 🔄 更新時のアップロード

コードを更新した場合の手順:

1. **変更したファイルのみアップロード**
   - FileZillaで変更ファイルを選択してアップロード

2. **SSH接続してキャッシュクリア**
   ```bash
   cd /home/username/laravel
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   
   # 本番環境で再キャッシュ
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Composerパッケージを更新した場合**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **データベース構造を変更した場合**
   ```bash
   php artisan migrate
   ```

---

## 💡 よくある質問

### Q1: vendor/をアップロードしないのはなぜ？
A: vendor/は数万のファイルがあり、アップロードに非常に時間がかかります。サーバー上で`composer install`を実行する方が高速で確実です。

### Q2: SSHアクセスがない場合は？
A: 一部の共有ホスティングではSSHが使えません。その場合：
- ローカルで`composer install`して`vendor/`も含めてアップロード（時間がかかります）
- cPanelやプレスクなどの管理画面からComposerを実行
- サポートに連絡してComposerを実行してもらう

### Q3: データベースはどうやって移行する？
A: 
- **新規**: サーバーでマイグレーション実行 (`php artisan migrate`)
- **既存データあり**: JSONバックアップをアップロードして移行コマンド実行

### Q4: アップロード後にエラーが出る
A: 
1. エラーログを確認: `storage/logs/laravel.log`
2. パーミッションを確認: `storage/` と `bootstrap/cache/`
3. `.env`の設定を確認
4. キャッシュをクリア: `php artisan config:clear`

---

## 📞 サポート連絡先

問題が解決しない場合は、以下の情報を準備してサポートに連絡してください：
- サーバーのPHPバージョン
- エラーログの内容
- .envの設定（パスワードは伏せて）
- 実行したコマンドとその結果

