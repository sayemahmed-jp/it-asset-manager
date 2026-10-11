<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

// ===== ダッシュボード用の集計 =====
// 状態ごとの件数（例：['available' => 4, 'loaned' => 1, ...]）
$counts = db()->query('SELECT status, COUNT(*) FROM assets GROUP BY status')
              ->fetchAll(PDO::FETCH_KEY_PAIR);
$total = array_sum($counts);

// ライセンス期限が30日以内、または期限切れのソフトウェア
$licenseAlerts = db()->query(
    "SELECT id, asset_code, name, license_expiry,
            DATEDIFF(license_expiry, CURDATE()) AS days_left
       FROM assets
      WHERE license_expiry IS NOT NULL
        AND status <> 'disposed'
        AND license_expiry <= CURDATE() + INTERVAL 30 DAY
      ORDER BY license_expiry"
)->fetchAll();

// 返却予定日を過ぎても返却されていない貸出
$overdueLoans = db()->query(
    'SELECT a.id, a.asset_code, a.name, e.name AS employee_name, l.due_date,
            DATEDIFF(CURDATE(), l.due_date) AS days_over
       FROM loans l
       JOIN assets a ON a.id = l.asset_id
       JOIN employees e ON e.id = l.employee_id
      WHERE l.returned_at IS NULL AND l.due_date < CURDATE()
      ORDER BY l.due_date'
)->fetchAll();

// ===== 検索 =====
$q        = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$status   = $_GET['status'] ?? '';

// 条件に応じてWHERE句を組み立てる（値はすべてプレースホルダで渡す）
$where  = [];
$params = [];

if ($q !== '') {
    $where[] = '(a.asset_code LIKE :q1 OR a.name LIKE :q2 OR e.name LIKE :q3)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
}
if (array_key_exists($category, CATEGORY_LABELS)) {
    $where[] = 'a.category = :category';
    $params[':category'] = $category;
}
if (array_key_exists($status, STATUS_LABELS)) {
    $where[] = 'a.status = :status';
    $params[':status'] = $status;
}

$sql = 'SELECT a.*, e.name AS employee_name, l.due_date
          FROM assets a
          LEFT JOIN loans l ON l.asset_id = a.id AND l.returned_at IS NULL
          LEFT JOIN employees e ON e.id = l.employee_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY a.asset_code';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll();

$isFiltered = (bool)$where;
$message = flash();
$today = date('Y-m-d');
$in30days = date('Y-m-d', strtotime('+30 days'));
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
      <p class="alert alert--<?= h($message['type']) ?>"><?= h($message['msg']) ?></p>
    <?php endif; ?>

    <!-- 件数カード -->
    <section class="stats">
      <div class="stat">
        <span class="stat__label">総資産</span>
        <span class="stat__value"><?= (int)$total ?></span>
      </div>
      <?php foreach (['available', 'loaned', 'repair'] as $s): ?>
        <div class="stat stat--<?= h($s) ?>">
          <span class="stat__label"><?= h(STATUS_LABELS[$s]) ?></span>
          <span class="stat__value"><?= (int)($counts[$s] ?? 0) ?></span>
        </div>
      <?php endforeach; ?>
    </section>

    <!-- 要対応アラート -->
    <?php if ($licenseAlerts || $overdueLoans): ?>
      <section class="alerts">
        <h3>⚠ 要対応（<?= count($licenseAlerts) + count($overdueLoans) ?>件）</h3>
        <ul>
          <?php foreach ($licenseAlerts as $al): ?>
            <li>
              <?php if ($al['days_left'] < 0): ?>
                <strong>ライセンス期限切れ</strong>：
              <?php elseif ((int)$al['days_left'] === 0): ?>
                <strong>本日ライセンス期限</strong>：
              <?php else: ?>
                <strong>ライセンス期限まで残り<?= (int)$al['days_left'] ?>日</strong>：
              <?php endif; ?>
              <a href="edit.php?id=<?= (int)$al['id'] ?>"><?= h($al['asset_code']) ?> <?= h($al['name']) ?></a>
              （<?= h($al['license_expiry']) ?>）
            </li>
          <?php endforeach; ?>

          <?php foreach ($overdueLoans as $ol): ?>
            <li>
              <strong>返却遅れ<?= (int)$ol['days_over'] ?>日</strong>：
              <a href="history.php?id=<?= (int)$ol['id'] ?>"><?= h($ol['asset_code']) ?> <?= h($ol['name']) ?></a>
              （<?= h($ol['employee_name']) ?>・返却予定 <?= h($ol['due_date']) ?>）
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <div class="toolbar">
      <h2><?= $isFiltered ? '検索結果' : '資産一覧' ?>（<?= count($assets) ?>件）</h2>
      <a class="btn" href="create.php">＋ 新規登録</a>
    </div>

    <form method="get" class="search">
      <input type="search" name="q" value="<?= h($q) ?>" placeholder="管理番号・名前・使用者で検索">
      <select name="category">
        <option value="">すべての種類</option>
        <?php foreach (CATEGORY_LABELS as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $category === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status">
        <option value="">すべての状態</option>
        <?php foreach (STATUS_LABELS as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn--small">検索</button>
      <?php if ($isFiltered): ?>
        <a class="btn btn--small btn--ghost" href="index.php">クリア</a>
      <?php endif; ?>
    </form>

    <table>
      <thead>
        <tr><th>管理番号</th><th>種類</th><th>名前</th><th>状態</th><th>使用者（返却予定）</th><th>ライセンス期限</th><th>操作</th></tr>
      </thead>
      <tbody>
        <?php if (!$assets): ?>
          <tr><td colspan="7" class="empty">該当する資産がありません</td></tr>
        <?php endif; ?>

        <?php foreach ($assets as $a): ?>
        <tr>
          <td><?= h($a['asset_code']) ?></td>
          <td><?= h(CATEGORY_LABELS[$a['category']]) ?></td>
          <td><?= h($a['name']) ?></td>
          <td><span class="badge badge--<?= h($a['status']) ?>"><?= h(STATUS_LABELS[$a['status']]) ?></span></td>
          <td>
            <?php if ($a['employee_name']): ?>
              <?php $overdue = $a['due_date'] < $today; ?>
              <?= h($a['employee_name']) ?><br>
              <small class="<?= $overdue ? 'overdue' : 'muted' ?>">〜<?= h($a['due_date']) ?><?= $overdue ? '（延滞）' : '' ?></small>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <?php $expiring = $a['license_expiry'] && $a['license_expiry'] <= $in30days; ?>
          <td class="<?= $expiring ? 'expiring' : '' ?>"><?= h($a['license_expiry'] ?? '—') ?></td>
          <td class="ops">
            <?php if ($a['status'] === 'available'): ?>
              <a class="btn btn--small" href="loan.php?id=<?= (int)$a['id'] ?>">貸出</a>
            <?php elseif ($a['status'] === 'loaned'): ?>
              <form method="post" action="return.php" onsubmit="return confirm('返却を受け付けますか？');">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button type="submit" class="btn btn--small btn--return">返却</button>
              </form>
            <?php endif; ?>
            <a class="btn btn--small btn--ghost" href="history.php?id=<?= (int)$a['id'] ?>">履歴</a>
            <a class="btn btn--small btn--ghost" href="edit.php?id=<?= (int)$a['id'] ?>">編集</a>
            <form method="post" action="delete.php" onsubmit="return confirm('この資産を削除しますか？');">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <button type="submit" class="btn btn--small btn--danger">削除</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </main>
</body>
</html>