<?php
date_default_timezone_set('Asia/Tokyo');
session_start();

// XSS対策：HTMLに出力する前に必ずエスケープする
function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// CSRF対策：フォームごとに秘密のトークンを発行して確認する
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('不正なリクエストです');
    }
}

// 画面遷移後に1回だけ表示するメッセージ（success / error）
function flash(?string $msg = null, string $type = 'success'): ?array
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

const CATEGORY_LABELS = [
    'pc'         => 'PC',
    'monitor'    => 'モニター',
    'peripheral' => '周辺機器',
    'software'   => 'ソフトウェア',
];

const STATUS_LABELS = [
    'available' => '在庫',
    'loaned'    => '貸出中',
    'repair'    => '修理中',
    'disposed'  => '廃棄',
];