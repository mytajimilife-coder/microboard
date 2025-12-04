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
    // mb1_config 테이블에 테마 설정 컬럼 추가
    $stmt = $db->query("SHOW COLUMNS FROM mb1_config LIKE 'cf_theme_mode'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE mb1_config ADD COLUMN cf_theme_mode varchar(10) NOT NULL DEFAULT 'auto'");
        echo sprintf($lang['column_added'], 'mb1_config', 'cf_theme_mode') . "<br>";
    } else {
        echo sprintf($lang['column_exists'], 'mb1_config', 'cf_theme_mode') . "<br>";
    }

    // 배경색 설정 컬럼 추가
    $stmt = $db->query("SHOW COLUMNS FROM mb1_config LIKE 'cf_bg_color_light'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE mb1_config ADD COLUMN cf_bg_color_light varchar(20) NOT NULL DEFAULT '#f5f5f5'");
        echo sprintf($lang['column_added'], 'mb1_config', 'cf_bg_color_light') . "<br>";
    } else {
        echo sprintf($lang['column_exists'], 'mb1_config', 'cf_bg_color_light') . "<br>";
    }

    $stmt = $db->query("SHOW COLUMNS FROM mb1_config LIKE 'cf_bg_color_dark'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE mb1_config ADD COLUMN cf_bg_color_dark varchar(20) NOT NULL DEFAULT '#1a1a1a'");
        echo sprintf($lang['column_added'], 'mb1_config', 'cf_bg_color_dark') . "<br>";
    } else {
        echo sprintf($lang['column_exists'], 'mb1_config', 'cf_bg_color_dark') . "<br>";
    }

    // 기본값 설정
    $db->exec("UPDATE mb1_config SET cf_theme_mode = 'auto', cf_bg_color_light = '#f5f5f5', cf_bg_color_dark = '#1a1a1a' WHERE cf_title = 'MicroBoard'");
    echo "✓ 기본 테마 설정 완료<br>";

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
    <title>테마 설정 업데이트</title>
    <link rel="stylesheet" href="skin/default/style.css">
</head>
<body>
</body>
</html>
