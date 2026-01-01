<?php
require_once 'config.php';
requireAdmin();

$db = getDB();
$message = "";

try {
    // 점검 모드 설정 추가
    $db->exec("ALTER TABLE mb1_config ADD COLUMN IF NOT EXISTS cf_maintenance_mode tinyint(1) NOT NULL DEFAULT 0");
    $db->exec("ALTER TABLE mb1_config ADD COLUMN IF NOT EXISTS cf_maintenance_text text");
    
    // 금지어 설정 추가
    $db->exec("ALTER TABLE mb1_config ADD COLUMN IF NOT EXISTS cf_bad_words text");
    
    // 자동 승급 설정 추가
    $db->exec("ALTER TABLE mb1_config ADD COLUMN IF NOT EXISTS cf_auto_level_up tinyint(1) NOT NULL DEFAULT 0");
    $db->exec("ALTER TABLE mb1_config ADD COLUMN IF NOT EXISTS cf_level_up_gap int(11) NOT NULL DEFAULT 100"); // 100포인트당 1레벨업

    $message = "데이터베이스가 성공적으로 업데이트되었습니다.";
} catch (Exception $e) {
    $message = "오류 발생: " . $e->getMessage();
}

echo $message;
?>
