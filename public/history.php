<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM assets WHERE id = ?');
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    http_response_code(404);
    exit('資産が見つかりません');
}

$stmt = db()->prepare(
    'SELECT l.*, e.name AS employee_name, e.department
       FROM loans l
       JOIN employees e ON e.id = l.employee_id
      WHERE l.asset_id = ?
      ORDER BY l.loaned_at DESC'
);
$stmt->execute([$id]);
$loans = $stmt->fetchAll();
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>貸出履歴 | IT資産管理</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header><h1><a href="index.php">IT資産管理</a></h1></header>

  <main>
    <div class="toolbar">
      <h2>貸出履歴：<?= h($asset['asset_code']) ?> <?= h($asset['name']) ?></h2>
      <a class="btn btn--ghost" href="index.php">一覧に戻る</a>
    </div>

    <?php if (!$loans): ?>
      <p class="muted">貸出履歴はまだありません。</p>
    <?php else: ?>
      <table>
        <thead>
          <tr><th>貸出日</th><th>使用者</th><th>部署</th><th>返却予定日</th><th>返却日</th></tr>
        </thead>
        <tbody>
          <?php foreach ($loans as $l): ?>
          <tr>
            <td><?= h(date('Y-m-d', strtotime($l['loaned_at']))) ?></td>
            <td><?= h($l['employee_name']) ?></td>
            <td><?= h($l['department']) ?></td>
            <td><?= h($l['due_date']) ?></td>
            <td>
              <?php if ($l['returned_at']): ?>
                <?= h(date('Y-m-d H:i', strtotime($l['returned_at']))) ?>
              <?php elseif ($l['due_date'] < $today): ?>
                <span class="badge badge--loaned">延滞中</span>
              <?php else: ?>
                <span class="badge badge--repair">未返却</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>
</body>
</html>