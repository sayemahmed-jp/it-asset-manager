<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

$assets = db()->query('SELECT * FROM assets ORDER BY asset_code')->fetchAll();
$message = flash();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IT資産管理</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header><h1><a href="index.php">IT資産管理</a></h1></header>

  <main>
    <?php if ($message): ?>
      <p class="alert"><?= h($message) ?></p>
    <?php endif; ?>

    <div class="toolbar">
      <h2>資産一覧（<?= count($assets) ?>件）</h2>
      <a class="btn" href="create.php">＋ 新規登録</a>
    </div>

    <table>
      <thead>
        <tr><th>管理番号</th><th>種類</th><th>名前</th><th>状態</th><th>ライセンス期限</th></tr>
      </thead>
      <tbody>
        <?php foreach ($assets as $a): ?>
        <tr>
          <td><?= h($a['asset_code']) ?></td>
          <td><?= h(CATEGORY_LABELS[$a['category']]) ?></td>
          <td><?= h($a['name']) ?></td>
          <td><span class="badge badge--<?= h($a['status']) ?>"><?= h(STATUS_LABELS[$a['status']]) ?></span></td>
          <td><?= h($a['license_expiry'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </main>
</body>
</html>