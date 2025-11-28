<?php
if (!defined('IN_ADMIN')) die();

require_once '../config.php';
requireAdmin();

// 언어 처리
$lang_code = $_SESSION['lang'] ?? 'ko';
$lang_file = "../lang/{$lang_code}.php";
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    $lang = require '../lang/ko.php';
}

$admin_title = $lang['admin_page_title'];
?><!DOCTYPE html>
<html>
<head>
<title><?php echo $admin_title; ?></title>
<meta charset="UTF-8">
<link rel="stylesheet" href="../skin/default/style.css">
<style>
.admin-page { max-width: 1200px; margin: 50px auto; padding: 20px; }
.admin-menu { margin-bottom: 20px; border-bottom: 1px solid #ccc; }
.admin-menu .btn { margin-right: 10px; }
</style>
</head>
<body class="admin-page">
<div class="admin-menu">
  <a href="index.php" class="btn"><?php echo $lang['welcome']; ?></a>
  <a href="../user/mypage.php" class="btn"><?php echo $lang['mypage']; ?></a>
  <a href="users.php" class="btn"><?php echo $lang['user_management']; ?></a>
  <a href="board.php" class="btn"><?php echo $lang['board_management']; ?></a>
  <a href="../logout.php" class="btn logout"><?php echo $lang['logout']; ?></a>
</div>

<div style="margin-bottom: 20px; padding: 10px; background: #f5f5f5; border-radius: 5px;">
  <strong><?php echo $lang['select_language']; ?>:</strong>
  <form method="post" style="display: inline;">
    <select name="language" onchange="this.form.submit()">
      <option value="ko" <?php echo $lang_code == 'ko' ? 'selected' : ''; ?>>한국어</option>
      <option value="en" <?php echo $lang_code == 'en' ? 'selected' : ''; ?>>English</option>
      <option value="ja" <?php echo $lang_code == 'ja' ? 'selected' : ''; ?>>日本語</option>
      <option value="zh" <?php echo $lang_code == 'zh' ? 'selected' : ''; ?>>中文</option>
    </select>
    <noscript><input type="submit" value="적용"></noscript>
  </form>
</div>

<?php
// 언어 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $selected_lang = $_POST['language'];
    if (in_array($selected_lang, ['ko', 'en', 'ja', 'zh'])) {
        $_SESSION['lang'] = $selected_lang;
        // 현재 페이지로 리디렉션하여 변경된 언어 적용
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
