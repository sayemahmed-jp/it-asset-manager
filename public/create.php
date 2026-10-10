<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

$errors = [];
$input = [
    'asset_code'     => '',
    'category'       => 'pc',
    'name'           => '',
    'purchase_date'  => '',
    'license_expiry' => '',
    'note'           => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    foreach ($input as $key => $_) {
        $input[$key] = trim($_POST[$key] ?? '');
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

    // 管理番号の重複チェック
    if (!$errors) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM assets WHERE asset_code = ?');
        $stmt->execute([$input['asset_code']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'この管理番号はすでに登録されています';
        }
    }

    // 登録（プリペアドステートメントでSQLインジェクション対策）
    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO assets (asset_code, category, name, purchase_date, license_expiry, note)
             VALUES (:asset_code, :category, :name, :purchase_date, :license_expiry, :note)'
        );
        $stmt->execute([
            ':asset_code'     => $input['asset_code'],
            ':category'       => $input['category'],
            ':name'           => $input['name'],
            ':purchase_date'  => $input['purchase_date'] ?: null,
            ':license_expiry' => $input['license_expiry'] ?: null,
            ':note'           => $input['note'] ?: null,
        ]);

        flash('「' . $input['name'] . '」を登録しました');
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
  <title>新規登録 | IT資産管理</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header><h1><a href="index.php">IT資産管理</a></h1></header>

  <main>
    <h2>資産の新規登録</h2>

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
        <input type="text" name="asset_code" value="<?= h($input['asset_code']) ?>" placeholder="PC-0003">
      </label>

      <label>種類 <span class="req">必須</span>
        <select name="category">
          <?php foreach (CATEGORY_LABELS as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= $input['category'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>名前 <span class="req">必須</span>
        <input type="text" name="name" value="<?= h($input['name']) ?>" placeholder="MacBook Pro 14インチ">
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
        <button type="submit" class="btn">登録する</button>
      </div>
    </form>
  </main>
</body>
</html>