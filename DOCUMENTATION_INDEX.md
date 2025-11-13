# Insight-Box ドキュメント一覧・整理

## 📚 ドキュメント構成

### 1. **ARCHITECTURE.md** ⭐ 必須
**目的**: システム全体のアーキテクチャとコードの仕組みを説明

**内容**:
- プロジェクト概要
- 技術スタック
- アーキテクチャ概要（MVC、Repository、Serviceパターン）
- ディレクトリ構造
- データフロー
- 主要機能の実装（コード例付き）
- データベース設計
- 認証・セキュリティ
- 外部API連携
- 開発・デプロイフロー（簡潔な手順）
- APIエンドポイント一覧
- トラブルシューティング

**対象読者**: 開発者、引き継ぎ担当者

**必要性**: ✅ **必須** - システム理解に不可欠

---

### 2. **FILEZILLA_SIMPLE_GUIDE.md** ⭐ 必須
**目的**: FileZillaを使用した実際のデプロイ手順を詳細に説明

**内容**:
- FileZilla接続設定（具体的な手順）
- アップロードするファイル・ディレクトリの詳細
- ステップバイステップのアップロード手順
- パーミッション設定方法
- セットアップ方法（ブラウザセットアップ、SSH）
- Webサーバー設定（Apache、Nginx、cPanel）
- トラブルシューティング（具体的なエラーと解決方法）
- チェックリスト
- 更新時のアップロード手順

**対象読者**: デプロイ担当者、運用担当者

**必要性**: ✅ **必須** - ARCHITECTURE.mdには詳細な手順が含まれていない

**ARCHITECTURE.mdとの違い**:
- ARCHITECTURE.md: 簡潔なデプロイフロー（概念的な手順）
- FILEZILLA_SIMPLE_GUIDE.md: 詳細な手順（実際の作業手順）

---

### 3. **docs/future/LARAVEL_AUTH_TEAM_DESIGN.md** ⚠️ 将来実装時のみ必要
**目的**: 認証とチーム機能の設計書（将来実装予定）

**内容**:
- 認証・チーム機能の実装設計
- データベース設計（teams、team_user、comments等）
- 実装手順（3スプリント）
- フロントエンド実装（Bladeテンプレート）
- セキュリティ実装
- テストシナリオ

**対象読者**: 開発者（将来実装時）

**必要性**: ⚠️ **将来実装時のみ必要** - 現在は未実装の機能

**現在の実装状況**:
- ✅ 認証機能: 実装済み（基本的なログイン・ログアウト）
- ❌ チーム機能: 未実装
- ❌ チーム共有ボード: 未実装
- ❌ コメント機能: 未実装

**場所**: `docs/future/LARAVEL_AUTH_TEAM_DESIGN.md` ✅ 移動済み

**状態**: ✅ `docs/future/`ディレクトリに保存済み

---

### 4. **UPLOAD_INSTRUCTIONS.md** ✅ 統合完了
**状態**: ✅ **FILEZILLA_SIMPLE_GUIDE.mdに統合済み**

**統合内容**:
- ボード機能のファイルアップロード手順
- マイグレーション実行手順
- トラブルシューティング
- 機能別アップロード手順セクションとして追加

**新しい場所**: FILEZILLA_SIMPLE_GUIDE.mdの「機能別アップロード手順」セクション

---

## 📊 ドキュメント整理状況

### 現在の構成 ✅

```
insight-box02/
├── ARCHITECTURE.md                    ✅ 保持（必須）
├── FILEZILLA_SIMPLE_GUIDE.md          ✅ 保持（必須・統合済み）
├── DOCUMENTATION_INDEX.md             ✅ 保持（このファイル）
├── docs/
│   └── future/
│       └── LARAVEL_AUTH_TEAM_DESIGN.md ⚠️ 将来実装時のみ保持
└── README.md                          ✅ 保持（プロジェクト概要）
```

### 実施済みの整理

#### ✅ 完了: UPLOAD_INSTRUCTIONS.mdをFILEZILLA_SIMPLE_GUIDE.mdに統合

1. ✅ FILEZILLA_SIMPLE_GUIDE.mdに「機能別アップロード手順」セクションを追加
2. ✅ UPLOAD_INSTRUCTIONS.mdの内容を統合
3. ✅ UPLOAD_INSTRUCTIONS.mdを削除

#### ✅ 完了: LARAVEL_AUTH_TEAM_DESIGN.mdをdocs/future/に移動

1. ✅ `docs/future/`ディレクトリを作成
2. ✅ LARAVEL_AUTH_TEAM_DESIGN.mdを移動
3. ✅ ARCHITECTURE.mdに「将来実装予定の機能」セクションを追加し、リンクを記載

---

## ✅ 結論

### 必須ドキュメント（保持）
1. **ARCHITECTURE.md** - システム全体の理解に必須
2. **FILEZILLA_SIMPLE_GUIDE.md** - 実デプロイ手順に必須
3. **README.md** - プロジェクト概要

### 将来実装時のみ必要
4. **docs/future/LARAVEL_AUTH_TEAM_DESIGN.md** - 将来実装する場合のみ保持（docs/future/に移動済み）

---

## 📝 実施済みアクション

### ✅ 完了
1. ✅ UPLOAD_INSTRUCTIONS.mdの内容をFILEZILLA_SIMPLE_GUIDE.mdに統合
2. ✅ UPLOAD_INSTRUCTIONS.mdを削除
3. ✅ ARCHITECTURE.mdに「関連ドキュメント」セクションを追加し、各ドキュメントへのリンクを記載
4. ✅ LARAVEL_AUTH_TEAM_DESIGN.mdを`docs/future/`に移動
5. ✅ DOCUMENTATION_INDEX.mdを作成

### 将来検討
- ⚠️ 実装予定がない場合、`docs/future/LARAVEL_AUTH_TEAM_DESIGN.md`を削除またはアーカイブ

---

## 🎯 まとめ

**ARCHITECTURE.mdがあっても、他のドキュメントは必要です。**

理由：
1. **ARCHITECTURE.md**: システム全体の理解（概念・設計）
2. **FILEZILLA_SIMPLE_GUIDE.md**: 実デプロイ手順（具体的な作業手順）
3. **LARAVEL_AUTH_TEAM_DESIGN.md**: 将来実装予定の機能の設計書

**役割が異なるため、相互補完的です。**

**UPLOAD_INSTRUCTIONS.mdはFILEZILLA_SIMPLE_GUIDE.mdに統合済みです。** ✅

---

**最終更新日**: 2025年11月13日
**状態**: ✅ 整理完了

