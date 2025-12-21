<?php
// 이 파일은 직접 실행되지 않고 include 되어 사용됩니다.

// 필요한 경우 여기서 DB 업데이트 로직을 수행할 수 있습니다.
// 예를 들어, 사용자가 관리자 페이지에 접속했을 때 자동으로 DB 스키마를 점검하고 업데이트하는 방식입니다.

require_once 'config.php';
$db = getDB();

try {
    // 1. mb1_page 테이블 생성
    $db->exec("
        CREATE TABLE IF NOT EXISTS `mb1_page` (
            `pg_id` int(11) NOT NULL AUTO_INCREMENT,
            `pg_slug` varchar(255) NOT NULL,
            `pg_title` varchar(255) NOT NULL,
            `pg_content` longtext NOT NULL,
            `pg_view_level` tinyint(4) NOT NULL DEFAULT 0,
            `pg_datetime` datetime NOT NULL,
            PRIMARY KEY (`pg_id`),
            UNIQUE KEY `pg_slug` (`pg_slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. mb1_config 테이블에 cf_copyright 컬럼 추가
    $stmt = $db->query("SHOW COLUMNS FROM mb1_config LIKE 'cf_copyright'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE mb1_config ADD COLUMN cf_copyright VARCHAR(255) DEFAULT ''");
    }

    // 3. mb1_variables 테이블 생성 (커스텀 변수)
    $db->exec("
        CREATE TABLE IF NOT EXISTS `mb1_variables` (
            `va_id` int(11) NOT NULL AUTO_INCREMENT,
            `va_key` varchar(50) NOT NULL,
            `va_value` text,
            `va_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`va_id`),
            UNIQUE KEY `va_key` (`va_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

} catch (Exception $e) {
    // 오류 발생 시 로그 남기기 또는 조용히 실패
    // error_log($e->getMessage());
}
?>
