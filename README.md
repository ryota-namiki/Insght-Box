# Insight-Box MVP

`insight-box-ui-io-schema.json` と要件定義を基に、Insight-Box の **コアサービス層・API サーバー・React フロントエンド** をまとめたワークスペース構成です。

## パッケージ構成

| パッケージ | 役割 |
| --- | --- |
| `packages/core` | 画面 I/O 型定義、入力バリデーション、サービスロジック（Upload / Board / Marketplace / Share / Analytics / Settings）。他パッケージから共有利用します。 |
| `packages/server` | Express ベースの仮想 API。カード／ボード／マーケット／共有／アナリティクス／設定エンドポイントをインメモリで提供。 |
| `packages/web` | Vite + React 実装。Upload → Board → 詳細 → Marketplace → Analytics → Settings の UI を再現し、サーバー API と連携します。 |

## セットアップ

```bash
# 依存ライブラリのインストール
npm install

# コアライブラリのトランスパイル（API/WEB から参照されます）
npm run build --workspace @insight-box/core
```

## 開発サーバー起動

別ターミナルで以下を実行してください。

```bash
# API サーバー (http://localhost:5175)
npm run dev --workspace @insight-box/server

# フロントエンド (http://localhost:5173) – API へ /api プロキシ済み
npm run dev --workspace @insight-box/web
```

- Upload 画面：カード作成フォーム & 最近のカード一覧
- Board 画面：カードキャンバスと関連グラフのダッシュボード
- Marketplace 画面：トレンドカード一覧とリアクションサマリ
- Card Detail：OCR／タグ／履歴表示 + 共有リンク生成
- Analytics：個人 KPI とカード別アナリティクス
- Settings：既定公開範囲・イベント上限の更新

## 型チェック / ビルド

```bash
# すべてのワークスペースで型検証
npm run typecheck --workspaces

# 個別実行
npm run typecheck --workspace @insight-box/core
npm run typecheck --workspace @insight-box/server
npm run typecheck --workspace @insight-box/web

# プロダクションビルド
npm run build --workspaces
```

## サーバー API エンドポイント

| メソッド | パス | 概要 |
| --- | --- | --- |
| GET | `/api/health` | ヘルスチェック |
| GET | `/api/events` | イベント一覧 |
| GET | `/api/cards` / `/api/cards/:id` | カード一覧 / 詳細 |
| POST | `/api/cards` | カード作成（Upload） |
| GET | `/api/board` | ボードキャンバス・関連グラフ |
| POST | `/api/templates/apply` | テンプレ要約生成（擬似） |
| GET | `/api/marketplace` | マーケットプレイスカード |
| POST | `/api/share` | 共有リンク生成（Slack 対応ダミー） |
| GET | `/api/analytics/personal` | 自分の KPI |
| GET | `/api/analytics/cards/:id` | カード別アナリティクス |
| GET/POST | `/api/settings/roles` | 既定公開範囲・上限設定 |

> すべてメモリ上のデータストアを利用しており、アプリ起動間での永続化は行っていません。

## 参考資料

- `packages/core/src/domain` … Insight-Box 画面の I/O 契約
- `packages/core/src/services` … ユースケース別サービスロジック
- `insight-box-ui-io-schema.json` … IaC スキーマ原本
