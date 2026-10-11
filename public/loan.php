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
if ($asset['status'] !== 'available') {
    flash('「' . $asset['name'] . '」は現在貸出できません（状態：' . STATUS_LABELS[$asset['status']] . '）', 'error');
    header('Location: index.php');
    exit;
}

$employees = db()->query('SELECT id, name, department FROM employees ORDER BY id')->fetchAll();
$employeeIds = array_column($employees, 'id');
$today = date('Y-m-d');

$errors = [];
$input = [
    'employee_id' => '',
    'due_date'    => date('Y-m-d', strtotime('+14 days')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $input['employee_id'] = (int)($_POST['employee_id'] ?? 0);
    $input['due_date']    = trim($_POST['due_date'] ?? '');

    // 入力チェック
    if (!in_array($input['employee_id'], $employeeIds)) {
        $errors[] = '使用者を選択してください';
    }
    $d = DateTime::createFromFormat('Y-m-d', $input['due_date']);
    if (!$d || $d->format('Y-m-d') !== $input['due_date']) {
        $errors[] = '返却予定日を正しく入力してください';
    } elseif ($input['due_date'] < $today) {
        $errors[] = '返却予定日は今日以降の日付を指定してください';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            // 同時に貸し出されないよう、資産の行をロックしてから状態を確認
            $stmt = $pdo->prepare('SELECT status FROM assets WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() !== 'available') {
                throw new DomainException('この資産はすでに貸出中です');
            }

            $pdo->prepare('INSERT INTO loans (asset_id, employee_id, due_date) VALUES (?, ?, ?)')
                ->execute([$id, $input['employee_id'], $input['due_date']]);
            $pdo->prepare("UPDATE assets SET status = 'loaned' WHERE id = ?")
                ->execute([$id]);

            $pdo->commit();

            flash('「' . $asset['name'] . '」を貸し出しました');
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (!$e instanceof DomainException) {
                throw $e;
            }
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>貸出 | IT資産管理</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header><h1><a href="index.php">IT資産管理</a></h1></header>

  <main>
    <h2>貸出：<?= h($asset['asset_code']) ?> <?= h($asset['name']) ?></h2>

    <?php if ($errors): ?>
      <ul class="errors">
        <?php foreach ($errors as $e): ?>
          <li><?= h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

      <label>使用者 <span class="req">必須</span>
        <select name="employee_id">
          <option value="">選択してください</option>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= (int)$emp['id'] ?>" <?= (int)$input['employee_id'] === (int)$emp['id'] ? 'selected' : '' ?>>
              <?= h($emp['name']) ?>（<?= h($emp['department']) ?>）
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>返却予定日 <span class="req">必須</span>
        <input type="date" name="due_date" value="<?= h($input['due_date']) ?>" min="<?= h($today) ?>">
      </label>

      <div class="actions">
        <a href="index.php" class="btn btn--ghost">キャンセル</a>
        <button type="submit" class="btn">貸し出す</button>
      </div>
    </form>
  </main>
</body>
</html>