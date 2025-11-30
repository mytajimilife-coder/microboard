<?php
session_start();
require_once '../config.php';

// 언어 처리
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ko';
$lang_file = "../lang/{$lang_code}.php";
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    $lang = require '../lang/ja.php';
}

// 페이지 제목 설정
$page_title = $page_title ?? 'MicroBoard';
?>
<!DOCTYPE html>
<html lang="<?php echo substr($lang_code, 0, 2); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - MicroBoard</title>
    <link rel="stylesheet" href="../skin/inc/header.css">
    <link rel="icon" href="../img/favicon.svg" type="image/svg+xml">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <a href="../index.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: white;">
                    <img src="../img/logo.svg" alt="MicroBoard Logo" style="height: 32px; width: 32px;">
                    MicroBoard
                </a>
            </div>
            
            <nav class="main-nav">
                <ul class="nav-menu">
                    <?php if (isLoggedIn()): ?>
                        <li><a href="../index.php"><?php echo $lang['board_list']; ?></a></li>
                        <li><a href="../user/mypage.php"><?php echo $lang['mypage']; ?></a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="../admin/index.php"><?php echo $lang['welcome']; ?></a></li>
                            <li><a href="../admin/users.php"><?php echo $lang['user_management']; ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="user-info">
                <?php if (isLoggedIn()): ?>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['user']); ?><?php echo $lang['user_suffix']; ?></span>
                    <a href="../logout.php" class="btn secondary"><?php echo $lang['logout']; ?></a>
                <?php else: ?>
                    <a href="../login.php" class="btn"><?php echo $lang['login']; ?></a>
                    <a href="../register.php" class="btn secondary"><?php echo $lang['register']; ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
