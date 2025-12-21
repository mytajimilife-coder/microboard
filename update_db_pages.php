<?php
require_once 'config.php';

$db = getDB();

try {
    // 1. mb1_config 테이블에 cf_copyright 컬럼 추가
    $stmt = $db->query("SHOW COLUMNS FROM mb1_config LIKE 'cf_copyright'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE mb1_config ADD COLUMN cf_copyright VARCHAR(255) DEFAULT ''");
        echo "Added cf_copyright to mb1_config table.<br>";
    }

    // 2. mb1_page 테이블 생성
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
    echo "Created mb1_page table.<br>";

    echo "Database update completed successfully.";
} catch (Exception $e) {
    die("Error updating database: " . $e->getMessage());
}
