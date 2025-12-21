<?php
require_once 'config.php';

$pg_id = $_GET['id'] ?? '';
if (empty($pg_id)) {
    die('Page ID is missing.');
}

$db = getDB();
try {
    // 페이지 정보 조회
    $stmt = $db->prepare("SELECT * FROM mb1_page WHERE pg_slug = ?");
    $stmt->execute([$pg_id]);
    $page = $stmt->fetch();

    if (!$page) {
        // 페이지가 없을 경우 about, policy 등 기존 파일로 연결되는지 확인 또는 404
        if ($pg_id === 'about') {
            header("Location: about.php");
            exit;
        }
        die('Page not found.');
    }

    // 권한 체크
    $member_level = 0; // 비회원
    if (isLoggedIn()) {
        $member_info = getMemberInfo($_SESSION['user']);
        $member_level = $member_info['mb_level'] ?? 1;
    }

    if ($page['pg_view_level'] > $member_level) {
        if (!isLoggedIn()) {
            echo "<script>alert('로그인이 필요한 페이지입니다.'); location.href='login.php?url=" . urlencode($_SERVER['REQUEST_URI']) . "';</script>";
            exit;
        } else {
            die('Access denied.');
        }
    }

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// 변수 치환 실행
$page_content = replace_variables($page['pg_content']);

// 헤더 로드
require_once 'inc/header.php';
?>

<div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <div class="card">
        <div class="card-header">
            <h1 style="margin: 0; font-size: 1.8rem;"><?php echo htmlspecialchars($page['pg_title']); ?></h1>
            <div style="margin-top: 10px; color: #666; font-size: 0.9rem;">
                Date: <?php echo date('Y-m-d', strtotime($page['pg_datetime'])); ?>
            </div>
        </div>
        <div class="card-body">
            <div class="content-view">
                <?php echo $page_content; ?>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}
.card-header {
    background: var(--bg-secondary);
    padding: 20px 30px;
    border-bottom: 1px solid var(--border-color);
}
.card-body {
    padding: 30px;
    line-height: 1.6;
}
</style>

<?php
require_once 'inc/footer.php';
?>
