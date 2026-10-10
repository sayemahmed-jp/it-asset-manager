<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

// 編集する資産を取得
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM assets WHERE id = ?');
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    http_response_code(404);
    exit('資産が見つかりません');
}

$isLoaned = $asset['status'] === 'loaned';
$editableStatuses = ['available', 'repair', 'disposed'];

$errors = [];
$input = [
    'asset_code'     => $asset['asset_code'],
    'category'       => $asset['category'],
    'name'           => $asset['name'],
    'status'         => $asset['status'],
    'purchase_date'  => $asset['purchase_date'] ?? '',
    'license_expiry' => $asset['license_expiry'] ?? '',
    'note'           => $asset['note'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    foreach ($input as $key => $_) {
        $input[$key] = trim($_POST[$key] ?? '');
    }

    // 貸出中の資産は、状態を変更できない（返却処理で変更する）
    if ($isLoaned) {
        $input['status'] = 'loaned';
    } elseif (!in_array($input['status'], $editableStatuses, true)) {
        $errors[] = '状態を選択してください';
    }

    // 入力チェック
    if ($input['asset_code'] === '') {
        $errors[] = '管理番号を入力してください';
    } elseif (!preg_match('/^[A-Z]{2}-\d{4}$/', $input['asset_code'])) {
        $errors[] = '管理番号は「PC-0001」の形式で入力してください';
    }
    if (!array_key_exists($input['category'], CATEGORY_LABELS)) {
        $errors[] = '種類を選択してください';
    }
    if ($input['name'] === '') {
        $errors[] = '名前を入力してください';
    }

    // 管理番号の重複チェック（自分自身は除く）
    if (!$errors) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM assets WHERE asset_code = ? AND id <> ?');
        $stmt->execute([$input['asset_code'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'この管理番号はすでに登録されています';
        }
    }

    // 更新
    if (!$errors) {
        $stmt = db()->prepare(
            'UPDATE assets
                SET asset_code = :asset_code, category = :category, name = :name, status = :status,
                    purchase_date = :purchase_date, license_expiry = :license_expiry, note = :note
              WHERE id = :id'
        );
        $stmt->execute([
            ':asset_code'     => $input['asset_code'],
            ':category'       => $input['category'],
            ':name'           => $input['name'],
            ':status'         => $input['status'],
            ':purchase_date'  => $input['purchase_date'] ?: null,
            ':license_expiry' => $input['license_expiry'] ?: null,
            ':note'           => $input['note'] ?: null,
            ':id'             => $id,
        ]);

        flash('「' . $input['name'] . '」を更新しました');
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>編集 | IT資産管理</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header><h1><a href="index.php">IT資産管理</a></h1></header>

  <main>
    <h2>資産の編集：<?= h($asset['asset_code']) ?></h2>

    <?php if ($errors): ?>
      <ul class="errors">
        <?php foreach ($errors as $e): ?>
          <li><?= h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

      <label>管理番号 <span class="req">必須</span>
        <input type="text" name="asset_code" value="<?= h($input['asset_code']) ?>">
      </label>

      <label>種類 <span class="req">必須</span>
        <select name="category">
          <?php foreach (CATEGORY_LABELS as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= $input['category'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>名前 <span class="req">必須</span>
        <input type="text" name="name" value="<?= h($input['name']) ?>">
      </label>

      <label>状態
        <?php if ($isLoaned): ?>
          <span class="badge badge--loaned">貸出中（返却処理で変更されます）</span>
        <?php else: ?>
          <select name="status">
            <?php foreach ($editableStatuses as $s): ?>
              <option value="<?= h($s) ?>" <?= $input['status'] === $s ? 'selected' : '' ?>><?= h(STATUS_LABELS[$s]) ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </label>

      <label>購入日
        <input type="date" name="purchase_date" value="<?= h($input['purchase_date']) ?>">
      </label>

      <label>ライセンス期限（ソフトウェアのみ）
        <input type="date" name="license_expiry" value="<?= h($input['license_expiry']) ?>">
      </label>

      <label>備考
        <textarea name="note" rows="3"><?= h($input['note']) ?></textarea>
      </label>

      <div class="actions">
        <a href="index.php" class="btn btn--ghost">キャンセル</a>
        <button type="submit" class="btn">更新する</button>
      </div>
    </form>
  </main>
</body>
</html>