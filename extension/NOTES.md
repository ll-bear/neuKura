# Chrome拡張機能 セットアップ手順

## Chromeへの読み込み手順

1. `chrome://extensions` を開く
2. 右上の「デベロッパー モード」をONにする
3. 「パッケージ化されていない拡張機能を読み込む」をクリック
4. `extension/` フォルダ(このファイルがある階層)を選択
5. ツールバーにneuKuraのアイコンが表示されればOK

## 動作確認の流れ

1. 拡張機能アイコンをクリック → ログイン画面が出る
2. neuKuraのメールアドレス/パスワードでログイン
3. ログイン成功 → 保存済み一覧画面に切り替わる
4. ログインフォームのあるサイトでアイコンをクリックし、カードをタップ → フォームに自動入力

コードを変更した後は `chrome://extensions` の当該拡張機能で
🔄(再読み込み)ボタンを押すと反映されます。

## 前提条件(バックエンド側、既にマージ済みのはず)

- `ExtensionAuthController`(ログイン/ログアウト、Sanctumトークン発行)
- `SecretPickerApiController` または同等のJSON API(`/api/secrets/picker` 等、`auth:sanctum` + `ability:credentials:*`)
- `config/cors.php` で `api/*` にCORSが適用されている(`allowed_methods` にGET/POST両方、`max_age`は0以外を推奨)

## よくあるハマりどころ

- **CORS**: 拡張機能からのfetchは `chrome-extension://` オリジンになるため、`allowed_origins` を `['*']` にするか拡張機能IDを許可リストに追加
- **content script が動かない**: 拡張機能を読み込んだ後に開いていたタブには反映されないため、対象ページをリロード
- **アイコン**: 現状は仮の紫丸アイコンです。お好きな画像に差し替えてください(`icons/icon16.png` / `icon48.png` / `icon128.png`、正方形PNG)
- **`NEUKURA_BASE_URL`**(`popup.js` 内): ローカル開発環境で試す場合は書き換えてください
