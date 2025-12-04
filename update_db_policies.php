<?php
require_once 'config.php';

// 언어 파일 로드
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ko';
$lang_file = "lang/{$lang_code}.php";
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    $lang = require 'lang/ko.php';
}

// 관리자 확인
if (!isAdmin()) {
    die($lang['admin_only_exec']);
}

$db = getDB();

try {
    // 정책 페이지 테이블 생성
    $db->exec("
        CREATE TABLE IF NOT EXISTS `mb1_policy` (
            `policy_type` varchar(50) NOT NULL,
            `policy_title` varchar(255) NOT NULL,
            `policy_content` longtext NOT NULL,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`policy_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo sprintf($lang['table_created'], 'mb1_policy') . "<br>";

    // 기본 정책 데이터 삽입
    $stmt = $db->query("SELECT COUNT(*) FROM mb1_policy WHERE policy_type = 'terms'");
    if ($stmt->fetchColumn() == 0) {
        $default_terms = "<h2>이용약관</h2><p>본 약관은 MicroBoard 서비스 이용에 관한 기본적인 사항을 규정합니다.</p><h3>제1조 (목적)</h3><p>이 약관은 MicroBoard가 제공하는 서비스의 이용조건 및 절차에 관한 사항을 규정함을 목적으로 합니다.</p>";
        
        $stmt = $db->prepare("INSERT INTO mb1_policy (policy_type, policy_title, policy_content) VALUES (?, ?, ?)");
        $stmt->execute(['terms', '이용약관', $default_terms]);
        echo "✓ 기본 이용약관 추가 완료<br>";
    }

    $stmt = $db->query("SELECT COUNT(*) FROM mb1_policy WHERE policy_type = 'privacy'");
    if ($stmt->fetchColumn() == 0) {
        $default_privacy = "<h2>개인정보 보호정책</h2><p>MicroBoard는 이용자의 개인정보를 중요시하며, 관련 법규를 준수합니다.</p><h3>제1조 (수집하는 개인정보 항목)</h3><p>회원가입 시 아이디, 비밀번호를 수집합니다.</p>";
        
        $stmt = $db->prepare("INSERT INTO mb1_policy (policy_type, policy_title, policy_content) VALUES (?, ?, ?)");
        $stmt->execute(['privacy', '개인정보 보호정책', $default_privacy]);
        echo "✓ 기본 개인정보 보호정책 추가 완료<br>";
    }

    echo "<br><strong>" . $lang['db_update_complete'] . "</strong>";
    echo "<br><br><a href='admin/index.php' class='btn'>관리자 페이지로 돌아가기</a>";

} catch (PDOException $e) {
    echo $lang['error_occurred'] . ": " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>정책 페이지 업데이트</title>
    <link rel="stylesheet" href="skin/default/style.css">
</head>
<body>
</body>
</html>
