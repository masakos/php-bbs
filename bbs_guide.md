# PHP + MySQL で掲示板アプリをつくろう

---

## 完成イメージ

- ブラウザで名前とメッセージを入力して「投稿する」ボタンを押す
- MySQLに保存されて、一覧に表示される
- 新しい投稿が上に出る

---

## ファイル構成

```
bbs/
└── index.php   ← これ1ファイルだけ（DB接続・投稿処理・一覧表示をすべて含む）
```

---

## STEP 1：DBに接続する（index.php の冒頭）

index.php の一番上でMySQLに接続している。

```php
$pdo = new PDO(
    'mysql:host=localhost;dbname=mydb;charset=utf8',
    'root',  // MySQLのユーザー名
    ''       // MySQLのパスワード（設定していれば入力）
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

### PDO とは？

PDO（PHP Data Objects）はPHPからMySQLを安全に操作するための仕組み。
「安全に」というのは、悪意ある文字列をSQL文に混ぜ込まれる**SQLインジェクション攻撃**を防げるから。

`new PDO(...)` の引数は左から順に：

| 引数 | 内容 | 例 |
|------|------|----|
| 第1引数 | 接続先の情報（DSN） | `mysql:host=localhost;dbname=mydb;charset=utf8` |
| 第2引数 | MySQLのユーザー名 | `'root'` |
| 第3引数 | MySQLのパスワード | `''`（未設定なら空文字） |

### setAttribute とは？

```php
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

接続や SQL に失敗したときにエラー情報を投げてくれる設定。
これを書いておくと、間違いがあったときに原因がわかりやすくなる。

---

## STEP 2：掲示板本体をつくる（index.php）

index.phpは大きく **4つのブロック** に分かれている。

```
①投稿処理（POSTされたデータをMySQLに保存）
      ↓
②投稿一覧の取得（MySQLから全件取得）
      ↓
③フォームのHTML（名前・メッセージの入力欄）
      ↓
④一覧のHTML（取得したデータをループで表示）
```

---

### ① 投稿処理

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
```

`$_SERVER['REQUEST_METHOD']` でリクエストの種類がわかる。
フォームを送信すると`POST`になるので、このブロックだけ実行される。

```php
$name = trim($_POST['name']);
$body = trim($_POST['body']);
```

`$_POST['フォームのname属性']` でフォームの値を受け取れる。
`trim()` は「　こんにちは　」→「こんにちは」のように前後の空白を取り除く関数。

---

### プリペアドステートメント（SQLインジェクション対策）

```php
$sql  = 'INSERT INTO bbs (name, body) VALUES (:name, :body)';
$stmt = $pdo->prepare($sql);
$stmt->execute([':name' => $name, ':body' => $body]);
```

この3行を順番に説明する。

---

#### 1行目：SQL文を「テンプレート」として用意する

```php
$sql = 'INSERT INTO bbs (name, body) VALUES (:name, :body)';
```

MySQLに送るSQL文を文字列として作っている。
ただし値の部分を `:name` `:body` という**仮の名前（プレースホルダー）**にしておく。

イメージとしては穴埋め問題の「＿＿＿」のようなもの。

```
INSERT INTO bbs (name, body) VALUES ( ___ , ___ )
                                      ↑名前  ↑メッセージ
```

---

#### 2行目：MySQLに「こういうSQL送るよ」と予告する

```php
$stmt = $pdo->prepare($sql);
```

`prepare()` でSQL文をMySQLに事前登録する。
この時点ではまだデータは送られない。「準備完了」の状態を `$stmt` に保存している。

---

#### 3行目：穴に値を入れて実行する

```php
$stmt->execute([':name' => $name, ':body' => $body]);
```

`execute()` に配列で「プレースホルダー → 実際の値」の対応を渡す。

```
':name' => $name   ←  :name の穴に $name（例："田中"）を入れる
':body' => $body   ←  :body の穴に $body（例："こんにちは"）を入れる
```

結果的にMySQLには以下が実行される。

```sql
INSERT INTO bbs (name, body) VALUES ('田中', 'こんにちは')
```

---

#### なぜ直接書かないの？

「最初から値を直接埋め込めばいいのでは？」と思うかもしれない。

```php
// 直接埋め込む（危険！）
$sql = "INSERT INTO bbs (name, body) VALUES ('$name', '$body')";
$pdo->query($sql);
```

これだと、悪意あるユーザーが名前欄に以下を入力したとき…

```
'); DROP TABLE bbs; --
```

SQL文がこうなってしまう。

```sql
INSERT INTO bbs (name, body) VALUES (''); DROP TABLE bbs; --', '...')
```

**テーブルが丸ごと消えます。** これが **SQLインジェクション攻撃** 。

`prepare()` + `execute()` を使うと、PDOが値を**ただの文字列**として安全に処理してくれるため、どんな文字列を入力されてもSQL文として実行されることがない。

---

### 投稿後のリダイレクト

```php
header('Location: index.php');
exit;
```

投稿後にページを再読み込みすると「もう一度送信しますか？」と聞かれることがある。
これを防ぐためにリダイレクト（別URLへ移動）させる。これを **PRGパターン** という。

---

### ② 投稿一覧の取得

```php
$stmt  = $pdo->query('SELECT * FROM bbs ORDER BY id DESC');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

- `SELECT * FROM bbs` ：bbsテーブルの全データを取得
- `ORDER BY id DESC` ：idの大きい順（新しい順）に並べる
- `fetchAll(PDO::FETCH_ASSOC)` ：全行を連想配列として取得

**取得後の $posts のイメージ**
```php
$posts = [
    ['id' => 3, 'name' => '田中', 'body' => 'やあ', 'time' => '2026-05-28 ...'],
    ['id' => 2, 'name' => '鈴木', 'body' => 'こんにちは', 'time' => '...'],
    ['id' => 1, 'name' => 'masuda', 'body' => 'Hello', 'time' => '...'],
];
```

---

### ③ フォームのHTML

```html
<form action="index.php" method="post">
    <input type="text" name="name">
    <input type="text" name="body">
    <button type="submit">投稿する</button>
</form>
```

- `method="post"` ：POSTリクエストで送信する
- `action="index.php"` ：このファイル自身に送信する
- `name="name"` や `name="body"` ：PHPで `$_POST['name']` などで受け取るときのキー名

---

### ④ 一覧のHTML

```php
<?php foreach ($posts as $post): ?>
<div>
    <?= htmlspecialchars($post['name']) ?>
    <?= htmlspecialchars($post['body']) ?>
</div>
<?php endforeach; ?>
```

`foreach` でリストを1件ずつ取り出して表示する。

#### htmlspecialchars() とは？（XSS対策）

ユーザーが以下のような文字列を投稿したとき：
```
<script>alert('攻撃！')</script>
```

そのまま表示するとスクリプトが実行されてしまう（**XSS攻撃**）。

`htmlspecialchars()` を使うと `<` や `>` を無害な文字列に変換してくれる：
```
&lt;script&gt;alert('攻撃！')&lt;/script&gt;
```

→ 画面にそのままテキストとして表示されるので安全。

---

## STEP 3：ブラウザで動かす

### ファイルを配置する

```bash
sudo cp -r /path/to/bbs /var/www/html/bbs
```

または最初から `/var/www/html/bbs/` に作成する。

### Apacheを起動する

```bash
sudo service apache2 start
sudo service mysql start
```

### ブラウザでアクセス

```
http://localhost/bbs/index.php
```

---

## まとめ：データの流れ

```
ブラウザ
  │  フォームを送信（POST）
  ▼
index.php（①投稿処理）
  │  INSERT INTO bbs ...
  ▼
MySQL（bbsテーブルに保存）
  │
  ▼
index.php（②一覧取得）
  │  SELECT * FROM bbs ...
  ▼
ブラウザ（一覧を表示）
```

---

## セキュリティのポイントまとめ

| 攻撃の種類 | 対策 | コード |
|-----------|------|--------|
| SQLインジェクション | プリペアドステートメント | `$pdo->prepare()` + `execute()` |
| XSS | HTMLエスケープ | `htmlspecialchars()` |
| 二重送信 | リダイレクト（PRGパターン） | `header('Location: ...')` |
