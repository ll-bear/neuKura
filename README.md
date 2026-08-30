# neuKura

ブックマークと認証情報（パスワード・Wi-Fiキー・ライセンスキーなど）を、
ひとつのアプリでまとめて管理するための個人開発アプリです。

意味検索によるブックマーク検索、Chrome拡張とiOS Shortcutsを使った
クロスプラットフォームでのログイン自動入力に対応しています。

## 主な機能

- ブックマークの登録・管理
  - AI（多言語対応の埋め込みモデル）による意味検索
  - favicon の自動取得（バックグラウンドジョブ）
- 認証情報の管理
  - URLに紐づく認証情報（`credentials`）と、URLを持たない情報（`secrets`）を分離管理
  - Web Crypto API を使ったブラウザ完結のパスワード生成
  - 1Password からの CSV インポート
- クロスプラットフォーム対応
  - Chrome拡張（Manifest V3）によるログインフォームの自動検知・自動入力
  - iOS Shortcuts からの Safari フォーム自動入力

## 使用技術

| 分類 | 技術 |
|---|---|
| バックエンド | Laravel（Service + Repositoryパターン） |
| データベース | MySQL |
| 認証 | Laravel Sanctum（カスタム ability スコープ） |
| フロントエンド | Alpine.js |
| 意味検索 | multilingual-e5-large（埋め込みモデル）+ コサイン類似度 |
| 拡張機能 | Chrome Extension（Manifest V3） |
| 自動化 | iOS Shortcuts |

## セットアップ

```bash
# リポジトリを取得
git clone https://github.com/ll-bear/neukura.git
cd neukura

# 依存パッケージのインストール
composer install
npm install

# 環境設定
cp .env.example .env
php artisan key:generate

# .env を編集してDB接続情報などを設定してください

# マイグレーション
php artisan migrate

# フロントエンドビルド
npm run build

# 開発サーバー起動
php artisan serve
```

Chrome拡張は `extension/` ディレクトリ以下にあります。
Chromeの「拡張機能」→「デベロッパーモード」→「パッケージ化されていない
拡張機能を読み込む」から読み込んでください。

## ディレクトリ構成（抜粋）

```
app/
  Services/       # ビジネスロジック
  Repositories/   # データアクセス層
extension/        # Chrome拡張（Manifest V3）
```

## ライセンス

個人学習・ポートフォリオ用途のプロジェクトです。