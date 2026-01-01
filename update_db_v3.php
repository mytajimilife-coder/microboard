<?php
require_once 'config.php';
requireAdmin();

$db = getDB();
$message = "";

try {
    // 1. 로그인 기록 테이블 생성
    $db->exec("CREATE TABLE IF NOT EXISTS `mb1_login_log` (
        `lo_id` int(11) NOT NULL AUTO_INCREMENT,
        `mb_id` varchar(50) NOT NULL,
        `lo_ip` varchar(50) NOT NULL,
        `lo_ua` text NOT NULL,
        `lo_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `lo_success` tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`lo_id`),
        KEY `mb_id` (`mb_id`),
        KEY `lo_datetime` (`lo_datetime`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $message = "데이터베이스 v3 업데이트가 완료되었습니다 (로그인 기록 테이블 추가).";
} catch (Exception $e) {
    $message = "오류 발생: " . $e->getMessage();
}

echo $message;
?>
