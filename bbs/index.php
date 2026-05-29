<?php
// ── ① データベース接続 ────────────────────────────
$pdo = new PDO(
    'mysql:host=localhost;dbname=mydb;charset=utf8',
    'sampleuser',  // MySQLのユーザー名
    'password'       // MySQLのパスワード（設定していれば入力）
);
// エラーが起きたとき例外を投げる設定
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── ② 投稿処理 ────────────────────────────────────
// フォームが送信されたとき（POSTリクエスト）だけ実行する
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // フォームから送られた値を受け取る
    // trim() で前後の空白を取り除く
    $name = trim($_POST['name']);
    $body = trim($_POST['body']);

    // 名前とメッセージが両方入力されているときだけ保存する
    if ($name !== '' && $body !== '') {

        // SQL文を準備する（:name と :body は後で値を当てはめるプレースホルダー）
        // プレースホルダーを使うことでSQLインジェクション（悪意ある攻撃）を防げる
        $sql  = 'INSERT INTO bbs (name, body) VALUES (:name, :body)';
        $stmt = $pdo->prepare($sql);

        // プレースホルダーに実際の値を入れてSQLを実行する
        $stmt->execute([':name' => $name, ':body' => $body]);
    }

    // 投稿後にページをリロードして、ブラウザの「再送信の確認」を防ぐ
    header('Location: index.php');
    exit;
}

// ── ③ 投稿一覧を取得 ──────────────────────────────
// 新しい投稿が上に来るように id の降順（DESC）で取得する
$stmt  = $pdo->query('SELECT * FROM bbs ORDER BY id DESC');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC); // 連想配列で全行取得
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>掲示板</title>
    <style>
        body       { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 0 16px; }
        h1         { border-bottom: 2px solid #333; padding-bottom: 8px; }
        form       { background: #f5f5f5; padding: 16px; border-radius: 8px; margin-bottom: 32px; }
        form label { display: block; margin-bottom: 8px; }
        form input { width: 100%; padding: 6px; box-sizing: border-box; }
        form button { margin-top: 12px; padding: 8px 24px; background: #4a90e2; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .post      { border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        .post-meta { font-size: 0.85em; color: #888; margin-bottom: 4px; }
        .post-body { margin: 0; }
    </style>
</head>
<body>

<h1>掲示板</h1>

<!-- ── ④ 投稿フォーム ────────────────────────── -->
<!-- action="index.php" でこのファイル自身に送信する -->
<!-- method="post" でPOSTリクエストとして送る -->
<form action="index.php" method="post">
    <label>名前
        <input type="text" name="name" maxlength="32" required placeholder="例：山田太郎">
    </label>
    <label>メッセージ
        <input type="text" name="body" maxlength="128" required placeholder="例：こんにちは！">
    </label>
    <button type="submit">投稿する</button>
</form>

<!-- ── ⑤ 投稿一覧の表示 ──────────────────────── -->
<h2>投稿一覧</h2>

<?php if (empty($posts)): ?>
    <p>まだ投稿がありません。</p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
    <div class="post">
        <div class="post-meta">
            <!-- htmlspecialchars() でXSS（スクリプト埋め込み攻撃）を防ぐ -->
            <strong><?= htmlspecialchars($post['name']) ?></strong>
            &nbsp;<?= htmlspecialchars($post['time']) ?>
        </div>
        <p class="post-body"><?= htmlspecialchars($post['body']) ?></p>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
