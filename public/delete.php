<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

// 削除はPOSTのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('不正なアクセスです');
}
check_csrf();

$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT name FROM assets WHERE id = ?');
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    flash('資産が見つかりません', 'error');
    header('Location: index.php');
    exit;
}

// 貸出履歴がある資産は削除せず、「廃棄」として残す
$stmt = db()->prepare('SELECT COUNT(*) FROM loans WHERE asset_id = ?');
$stmt->execute([$id]);

if ($stmt->fetchColumn() > 0) {
    flash('「' . $asset['name'] . '」は貸出履歴があるため削除できません。編集から状態を「廃棄」に変更してください', 'error');
} else {
    db()->prepare('DELETE FROM assets WHERE id = ?')->execute([$id]);
    flash('「' . $asset['name'] . '」を削除しました');
}

header('Location: index.php');
exit;