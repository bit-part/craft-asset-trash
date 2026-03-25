---
name: Manual Test
about: Release pre-check manual test scenarios
title: "Manual Test: v"
labels: testing
---

## Test Environment

| Item | Value |
|------|-------|
| Version | v |
| Environment | Local (DDEV) / Remote |
| URL | |
| PHP | |
| Craft CMS | |

## Prerequisites

- [ ] 管理者アカウントでログイン済み
- [ ] Assets に 2〜3 個のテストファイルをアップロード済み（例: `test.txt`, `test-image.png`）

---

## Test 1: 空のごみ箱の表示

**目的**: プラグインが正しくインストールされ、CP ナビに表示されること

1. コントロールパネルにログイン
2. 左ナビの **Asset Trash** をクリック

**期待結果**:
- [ ] 「Asset Trash」ページが表示される
- [ ] 「The trash is empty.」と表示される
- [ ] ナビにバッジカウントが表示されていない

---

## Test 2: アセット削除 → ごみ箱への移行

**目的**: アセット削除時にファイルがごみ箱にコピーされること

1. 左ナビの **Assets** をクリック
2. テストファイルのチェックボックスをオン
3. **Actions** → **Delete** を選択
4. 確認ダイアログで **OK**

**期待結果**:
- [ ] アセット一覧からファイルが消える
- [ ] 左ナビの **Asset Trash** にバッジカウント「1」が表示される

---

## Test 3: ごみ箱一覧の確認

**目的**: ごみ箱にアイテムが正しく表示されること

1. 左ナビの **Asset Trash** をクリック

**期待結果**:
- [ ] 削除したファイル名が表示される
- [ ] カラム: Preview, Filename, Size, Deleted By, Date Deleted, References
- [ ] Deleted By にログインユーザー名が表示される
- [ ] Date Deleted に削除日時が表示される
- [ ] **Restore** と **Delete** ボタンが表示される

---

## Test 4: 詳細画面

**目的**: ファイル名クリックで詳細情報が表示されること

1. ごみ箱一覧でファイル名のリンクをクリック

**期待結果**:
- [ ] ページタイトルにファイル名が表示される
- [ ] Filename, Kind, Size, Volume, Original Path, Deleted By, Date Deleted, Original Asset ID, Trash Path が表示される
- [ ] **Restore** ボタンが表示される
- [ ] **Permanently Delete** ボタンが表示される
- [ ] **Back to Trash** リンクが表示される

---

## Test 5: 復元

**目的**: ごみ箱のアイテムを元の場所に復元できること

1. ごみ箱一覧（または詳細画面）で **Restore** ボタンをクリック

**期待結果**:
- [ ] 「Asset restored.」の通知が表示される
- [ ] ごみ箱一覧からアイテムが消える
- [ ] **Assets** ページで復元されたファイルが一覧に表示される
- [ ] ナビのバッジカウントが消える

---

## Test 6: 永久削除

**目的**: ごみ箱のアイテムを完全に削除できること

> 準備: もう一度アセットを削除してごみ箱にアイテムを入れる

1. ごみ箱一覧で **Delete** ボタンをクリック
2. 確認ダイアログで **OK**

**期待結果**:
- [ ] 「Asset permanently deleted.」の通知が表示される
- [ ] ごみ箱一覧からアイテムが消える
- [ ] **Assets** ページにも復元されない

---

## Test 7a: 一括復元

**目的**: 複数アイテムを選択して一括復元できること

> 準備: 2つ以上のアセットを削除してごみ箱に入れる

1. ごみ箱一覧で **Select all** チェックボックスをクリック
2. **Restore Selected** ボタンをクリック

**期待結果**:
- [ ] 選択時に Restore Selected / Delete Selected ボタンが表示される
- [ ] 「{count} asset(s) restored.」の通知が表示される
- [ ] すべてのアイテムが復元される

---

## Test 7b: 一括削除

> 準備: もう一度複数アセットを削除してごみ箱に入れる

1. ごみ箱一覧で複数のチェックボックスをオン
2. **Delete Selected** ボタンをクリック
3. 確認ダイアログで **OK**

**期待結果**:
- [ ] 「{count} asset(s) permanently deleted.」の通知が表示される
- [ ] 選択したアイテムがすべて消える

---

## Test 8: ごみ箱を空にする (Empty Trash)

> 準備: 2つ以上のアセットを削除してごみ箱に入れる

1. ごみ箱一覧の右上にある **Empty Trash** ボタンをクリック
2. 確認ダイアログで **OK**

**期待結果**:
- [ ] すべてのアイテムが削除される
- [ ] 「{count} asset(s) permanently deleted.」の通知が表示される
- [ ] 「The trash is empty.」が表示される
- [ ] **Empty Trash** ボタンが消える

---

## Test 9: ボリュームフィルター

> 前提: 2つ以上のボリュームが設定されている場合のみ実施

1. ごみ箱一覧でフィルタードロップダウンをクリック
2. 特定のボリュームを選択

**期待結果**:
- [ ] 選択したボリュームのアイテムのみ表示される
- [ ] 「All Volumes」を選択すると全アイテムが表示される

---

## Test 10: 設定画面

1. **Settings > Plugins > Asset Trash** に移動

**期待結果**:
- [ ] Retention Days（デフォルト: 30）が表示される
- [ ] Auto Purge（デフォルト: ON）が表示される
- [ ] Trash Directory Name（デフォルト: .trash）が表示される

### 10a: 設定変更

1. Retention Days を `60` に変更 → **Save**
2. ページを再読み込み

- [ ] 変更が保存され、`60` のまま表示される
- [ ] 元に戻す: `30` に変更して Save

### 10b: バリデーション

1. Trash Directory Name に `../invalid` を入力 → **Save**

- [ ] バリデーションエラーが表示される（パストラバーサル防止）

---

## Test 11: GC 自動パージ（CLI）

```bash
# DDEV
ddev craft gc

# Remote
ssh bpdev.cfbx.jp "cd /home/swkbdufr/public_html/craft-plugin-dev && ea-php84 craft gc"
```

- [ ] エラーなしで完了する
- [ ] 保持期間内のアイテムはパージされない

---

## Test 12: キーボード操作

1. ごみ箱一覧でチェックボックスに Tab キーでフォーカス
2. Space キーまたは Enter キーを押す

- [ ] チェックボックスがトグルされる
- [ ] Bulk actions バーが表示される

---

## Summary

| # | Test | Result |
|---|------|--------|
| 1 | 空のごみ箱 | |
| 2 | 削除 → ごみ箱移行 | |
| 3 | ごみ箱一覧 | |
| 4 | 詳細画面 | |
| 5 | 復元 | |
| 6 | 永久削除 | |
| 7a | 一括復元 | |
| 7b | 一括削除 | |
| 8 | ごみ箱を空にする | |
| 9 | ボリュームフィルター | |
| 10 | 設定画面 | |
| 10a | 設定変更 | |
| 10b | バリデーション | |
| 11 | GC 自動パージ | |
| 12 | キーボード操作 | |
