# 削除予定のファイル・ディレクトリ

## ✅ 安全に削除できるもの（PHP版で不要）

### 1. React/TypeScriptプロジェクト
- `packages/` - 完全に不要

### 2. Node.js依存関係（ルート）
- `node_modules/` - 完全に不要
- `package.json` - 完全に不要
- `package-lock.json` - 完全に不要
- `tsconfig.json` - 完全に不要

### 3. Laravel内のNode.js関連
- `insight-box-php/apps/server-php/package.json` - 不要
- `insight-box-php/apps/server-php/vite.config.js` - 不要

### 4. 使われていないリソース
- `insight-box-php/apps/server-php/resources/views/welcome.blade.php` - 使用されていない
- `insight-box-php/apps/server-php/resources/css/app.css` - CDN版を使用
- `insight-box-php/apps/server-php/resources/js/app.js` - 使用されていない

### 5. 旧Reactアプリの残骸（既に削除済み）
- `insight-box-php/apps/server-php/public/app/` - 削除済み

---

## 現在使用しているもの（CDN経由）

✅ Tailwind CSS - CDN経由
✅ Alpine.js - CDN経由  
✅ Material Icons - CDN経由

---

## 削除後のサイズ削減

削除前: 約500MB
削除後: 約50MB (90%削減)

