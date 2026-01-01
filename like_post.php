<?php
/**
 * 게시글 추천/좋아요 API
 * AJAX 요청을 처리합니다
 */

require_once 'config.php';

header('Content-Type: application/json');

// 로그인 확인
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => $lang['login_required'] ?? '로그인이 필요합니다.']);
    exit;
}

$action = $_POST['action'] ?? '';
$bo_table = $_POST['bo_table'] ?? '';
$wr_id = (int)($_POST['wr_id'] ?? 0);
$mb_id = $_SESSION['user'];

if (empty($bo_table) || $wr_id <= 0) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

// 테이블명 검증
if (!preg_match('/^[a-zA-Z0-9_]+$/', $bo_table)) {
    echo json_encode(['success' => false, 'message' => '잘못된 게시판입니다.']);
    exit;
}

$db = getDB();
$write_table = 'mb1_write_' . $bo_table;

try {
    if ($action === 'like') {
        // 추천하기
        // 이미 추천했는지 확인
        $stmt = $db->prepare("SELECT id FROM mb1_post_likes WHERE bo_table = ? AND wr_id = ? AND mb_id = ?");
        $stmt->execute([$bo_table, $wr_id, $mb_id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => $lang['already_liked'] ?? '이미 추천하셨습니다.']);
            exit;
        }
        
        // 추천 기록 추가
        $stmt = $db->prepare("INSERT INTO mb1_post_likes (bo_table, wr_id, mb_id) VALUES (?, ?, ?)");
        $stmt->execute([$bo_table, $wr_id, $mb_id]);
        
        // 게시글 추천 수 증가
        $stmt = $db->prepare("UPDATE `{$write_table}` SET wr_likes = wr_likes + 1 WHERE wr_id = ?");
        $stmt->execute([$wr_id]);
        
        // 현재 추천 수 조회
        $stmt = $db->prepare("SELECT wr_likes FROM `{$write_table}` WHERE wr_id = ?");
        $stmt->execute([$wr_id]);
        $post = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => $lang['like_success'] ?? '추천하였습니다.',
            'likes' => $post['wr_likes'] ?? 1
        ]);

        // 알림 서비스 연동
        $full_post = getPost($bo_table, $wr_id);
        if ($full_post && $full_post['wr_name'] !== $mb_id) {
            $noti_content = sprintf("누군가 [%s] 게시글을 추천했습니다! ❤️", $full_post['wr_subject']);
            $noti_link = "view.php?id=" . $wr_id . "&bo_table=" . $bo_table;
            create_notification($full_post['wr_name'], 'like', $noti_content, $noti_link);
        }
        
    } elseif ($action === 'unlike') {
        // 추천 취소
        $stmt = $db->prepare("DELETE FROM mb1_post_likes WHERE bo_table = ? AND wr_id = ? AND mb_id = ?");
        $stmt->execute([$bo_table, $wr_id, $mb_id]);
        
        if ($stmt->rowCount() > 0) {
            // 게시글 추천 수 감소
            $stmt = $db->prepare("UPDATE `{$write_table}` SET wr_likes = GREATEST(wr_likes - 1, 0) WHERE wr_id = ?");
            $stmt->execute([$wr_id]);
            
            // 현재 추천 수 조회
            $stmt = $db->prepare("SELECT wr_likes FROM `{$write_table}` WHERE wr_id = ?");
            $stmt->execute([$wr_id]);
            $post = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'message' => $lang['unlike_success'] ?? '추천을 취소하였습니다.',
                'likes' => $post['wr_likes'] ?? 0
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '추천 기록을 찾을 수 없습니다.']);
        }
        
    } elseif ($action === 'check') {
        // 추천 여부 확인
        $stmt = $db->prepare("SELECT id FROM mb1_post_likes WHERE bo_table = ? AND wr_id = ? AND mb_id = ?");
        $stmt->execute([$bo_table, $wr_id, $mb_id]);
        $liked = $stmt->fetch() ? true : false;
        
        // 현재 추천 수 조회
        $stmt = $db->prepare("SELECT wr_likes FROM `{$write_table}` WHERE wr_id = ?");
        $stmt->execute([$wr_id]);
        $post = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'likes' => $post['wr_likes'] ?? 0
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '잘못된 액션입니다.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()]);
}
