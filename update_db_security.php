<?php
require_once 'config.php';
requireAdmin();

$db = getDB();
$message = "";

try {
    // 1. 보안 설정 컬럼 추가
    $db->exec("ALTER TABLE mb1_config ADD COLUMN IF NOT EXISTS cf_login_attempts_limit int(11) NOT NULL DEFAULT 5");
    $db->exec("ALTER TABLE mb1_config ADD COLUMN IF NOT EXISTS cf_login_lockout_time int(11) NOT NULL DEFAULT 10"); // 분 단위
    $db->exec("ALTER TABLE mb1_config ADD COLUMN IF NOT EXISTS cf_admin_ip_whitelist text");
    $db->exec("ALTER TABLE mb1_config ADD COLUMN IF NOT EXISTS cf_use_security_headers tinyint(1) NOT NULL DEFAULT 1");

    $message = "보안 시스템 데이터베이스 업데이트가 완료되었습니다.";
} catch (Exception $e) {
    $message = "오류 발생: " . $e->getMessage();
}

echo $message;
?>
