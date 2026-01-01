<?php
require_once 'config.php';
requireLogin();

$action = $_POST['action'] ?? '';
$wr_id = filter_var($_POST['wr_id'] ?? 0, FILTER_VALIDATE_INT);
$bo_table = $_POST['bo_table'] ?? '';

// CSRF 토큰 검증
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    die('CSRF token validation failed');
}

if (!$wr_id || !$bo_table) {
    die('잘못된 요청입니다.');
}

if ($action === 'insert') {
    $content = filter_content(trim($_POST['co_content']));
    if (!$content) {
        die('내용을 입력해주세요.');
    }
    
    // XSS 방지 (HTMLspecialchars는 출력(view) 시점에 수행하지만, DB 저장 전에도 스크립트 태그 등 위험 요소 제거 가능)
    // 여기서는 기본적으로 텍스트로 취급하여 저장
    insertComment($bo_table, $wr_id, $_SESSION['user'], $content);

    // 알림 서비스 연동
    $post = getPost($bo_table, $wr_id);
    if ($post && $post['wr_name'] !== $_SESSION['user']) {
        $noti_content = sprintf("[%s] 게시글에 새 댓글이 달렸습니다: %s", $post['wr_subject'], mb_strimwidth($content, 0, 50, "..."));
        $noti_link = "view.php?id=" . $wr_id . "&bo_table=" . $bo_table;
        create_notification($post['wr_name'], 'comment', $noti_content, $noti_link);
    }
} elseif ($action === 'delete') {
    $co_id = filter_var($_POST['co_id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$co_id) {
        die('잘못된 요청입니다.');
    }
    
    // 댓글 작성자 확인 (보안 강화)
    $comment = getComment($bo_table, $co_id);
    if (!$comment || (!isAdmin() && $comment['co_name'] !== $_SESSION['user'])) {
         die('삭제 권한이 없습니다.');
    }
    
    deleteComment($bo_table, $co_id);
}

header('Location: view.php?id=' . $wr_id . '&bo_table=' . $bo_table);
exit;
?>
