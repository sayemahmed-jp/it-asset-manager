<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

// 返却はPOSTのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('不正なアクセスです');
}
check_csrf();

$id = (int)($_POST['id'] ?? 0);
$pdo = db();

try {
    $pdo->beginTransaction();

    // 未返却の貸出記録をロックして取得
    $stmt = $pdo->prepare(
        'SELECT l.id, a.name
           FROM loans l
           JOIN assets a ON a.id = l.asset_id
          WHERE l.asset_id = ? AND l.returned_at IS NULL
          FOR UPDATE'
    );
    $stmt->execute([$id]);
    $loan = $stmt->fetch();

    if (!$loan) {
        throw new DomainException('貸出中の記録が見つかりません');
    }

    $pdo->prepare('UPDATE loans SET returned_at = NOW() WHERE id = ?')
        ->execute([$loan['id']]);
    $pdo->prepare("UPDATE assets SET status = 'available' WHERE id = ?")
        ->execute([$id]);

    $pdo->commit();
    flash('「' . $loan['name'] . '」の返却を受け付けました');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (!$e instanceof DomainException) {
        throw $e;
    }
    flash($e->getMessage(), 'error');
}

header('Location: index.php');
exit;