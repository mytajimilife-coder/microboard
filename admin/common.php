<?php
if (!defined('IN_ADMIN')) die();

require_once '../config.php';
requireAdmin();
require_once '../check_db_pages.php';

// 설정 가져오기
$config = get_config();
$default_theme = $config['cf_theme'] ?? 'light';
$bg_type = $config['cf_bg_type'] ?? 'color';
$bg_value = $config['cf_bg_value'] ?? '#ffffff';

// 배경 스타일 생성
$custom_bg_style = '';
if ($bg_type === 'image') {
    // admin 폴더 기준이므로 ../를 붙여야 함
    $custom_bg_style = "url('../" . htmlspecialchars($bg_value) . "')";
} else {
    $custom_bg_style = $bg_value;
}

// 언어 처리
$lang_code = $_SESSION['lang'] ?? 'ko';
$lang_file = "../lang/{$lang_code}.php";
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    $lang = require '../lang/ko.php';
}

if (isset($admin_title_key) && isset($lang[$admin_title_key])) {
    $admin_title = $lang[$admin_title_key];
} elseif (!isset($admin_title)) {
    $admin_title = $lang['admin_page_title'];
}
?><!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>">
<head>
<title><?php echo $admin_title; ?></title>
<meta charset="UTF-8">
<link rel="stylesheet" href="../skin/default/style.css">
<style>
/* 관리자 페이지 전용 스타일 */
:root {
  --admin-sidebar-width: 250px;
  --admin-header-height: 60px;
  --body-bg: <?php echo $custom_bg_style; ?>;
}

body {
  margin: 0;
  padding: 0;
  color: var(--text-color);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  <?php if ($bg_type === 'image'): ?>
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
  <?php else: ?>
    background-color: var(--body-bg);
  <?php endif; ?>
  transition: background-color 0.3s, color 0.3s;
}

.admin-layout {
  display: flex;
  min-height: 100vh;
}

.admin-sidebar {
  width: var(--admin-sidebar-width);
  background: var(--bg-secondary);
  border-right: 1px solid var(--border-color);
  padding: 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  position: fixed;
  height: 100vh;
  top: 0;
  left: 0;
  overflow-y: auto;
  z-index: 100;
  box-shadow: 2px 0 5px rgba(0,0,0,0.05);
}

.admin-logo {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--primary-color);
  margin-bottom: 2rem;
  padding: 0 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.admin-nav {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: var(--text-color);
  text-decoration: none;
  border-radius: var(--radius);
  font-weight: 500;
  transition: all 0.2s;
}

.nav-item:hover {
  background: var(--bg-tertiary);
  color: var(--primary-color);
}

.nav-item.active {
  background: var(--primary-color);
  color: white;
}

.nav-item.logout {
  margin-top: auto;
  color: var(--danger-color);
}

.nav-item.logout:hover {
  background: rgba(239, 68, 68, 0.1);
}

.admin-main {
  flex: 1;
  margin-left: var(--admin-sidebar-width);
  padding: 2rem;
  overflow-x: hidden;
}

.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  background: var(--bg-color);
  padding: 1rem 1.5rem;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
}

.admin-page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--secondary-color);
  margin: 0;
}

.admin-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.lang-select {
  padding: 0.5rem;
  border-radius: var(--radius);
  border: 1px solid var(--border-color);
  background: var(--bg-secondary);
  color: var(--text-color);
  cursor: pointer;
}

.theme-toggle {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.25rem;
  padding: 0.5rem;
  border-radius: 50%;
  transition: background 0.2s;
  color: var(--text-color);
}

.theme-toggle:hover {
  background: var(--bg-tertiary);
}

/* 개별 페이지에서 사용할 카드 스타일 */
.admin-card {
  background: var(--bg-color);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  padding: 2rem;
  margin-bottom: 2rem;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.admin-table th, .admin-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border-color);
}

.admin-table th {
  background: var(--bg-secondary);
  font-weight: 600;
  color: var(--text-muted);
}

.admin-table tr:hover {
  background: var(--bg-tertiary);
}

@media (max-width: 768px) {
  .admin-sidebar {
    transform: translateX(-100%);
    transition: transform 0.3s;
  }
  .admin-sidebar.open {
    transform: translateX(0);
  }
  .admin-main {
    margin-left: 0;
  }
}
</style>
<script>
(function() {
    const savedTheme = localStorage.getItem('theme');
    const defaultTheme = '<?php echo $default_theme; ?>';
    const theme = savedTheme || defaultTheme;
    if (theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    else document.documentElement.removeAttribute('data-theme');
})();
</script>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a href="index.php" class="admin-logo">
           🛡️ MicroAdmin
        </a>
        <nav class="admin-nav">
            <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                🏠 <?php echo $lang['admin_home']; ?>
            </a>
            <a href="users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                👥 <?php echo $lang['user_management']; ?>
            </a>
            <a href="board.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'board.php' ? 'active' : ''; ?>">
                📋 <?php echo $lang['board_management']; ?>
            </a>
            <a href="config.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'config.php' ? 'active' : ''; ?>">
                ⚙️ <?php echo $lang['config_management']; ?>
            </a>
            <a href="pages.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'pages.php' ? 'active' : ''; ?>">
                📄 <?php echo $lang['page_management'] ?? '페이지 관리'; ?>
            </a>
            <a href="oauth.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'oauth.php' ? 'active' : ''; ?>">
                🔑 <?php echo $lang['oauth_settings']; ?>
            </a>
            <a href="policy.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'policy.php' ? 'active' : ''; ?>">
                📜 <?php echo $lang['policy_management']; ?>
            </a>
            <a href="notice.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'notice.php' ? 'active' : ''; ?>">
                📢 <?php echo $lang['notice_management'] ?? '공지사항 관리'; ?>
            </a>
            <a href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                🚨 <?php echo $lang['report_management'] ?? '신고 관리'; ?>
            </a>
            <a href="visit_stats.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'visit_stats.php' ? 'active' : ''; ?>">
                📊 <?php echo $lang['visit_statistics'] ?? '방문 통계'; ?>
            </a>
            <a href="ip_ban.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'ip_ban.php' ? 'active' : ''; ?>">
                🚫 <?php echo $lang['ip_ban_management'] ?? 'IP 차단 관리'; ?>
            </a>
            <a href="email_settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'email_settings.php' ? 'active' : ''; ?>">
                📧 <?php echo $lang['email_settings'] ?? '이메일 설정'; ?>
            </a>
            <a href="seo.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'seo.php' ? 'active' : ''; ?>">
                🔍 <?php echo $lang['seo_settings'] ?? 'SEO 설정'; ?>
            </a>
            <a href="theme_settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'theme_settings.php' ? 'active' : ''; ?>">
                🎨 <?php echo $lang['theme_settings'] ?? '테마 설정'; ?>
            </a>
            <a href="file_manager.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'file_manager.php' ? 'active' : ''; ?>">
                📁 <?php echo $lang['file_manager'] ?? '파일 관리'; ?>
            </a>
            <a href="points.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'points.php' ? 'active' : ''; ?>">
                💰 <?php echo $lang['point_management'] ?? '포인트 관리'; ?>
            </a>
            <a href="logs.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">
                📋 <?php echo $lang['admin_logs'] ?? '관리자 로그'; ?>
            </a>
            <a href="backup.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : ''; ?>">
                💾 <?php echo $lang['backup_restore'] ?? '백업/복원'; ?>
            </a>
            <div style="margin-top: auto; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <a href="../user/mypage.php" class="nav-item">
                    👤 <?php echo $lang['mypage']; ?>
                </a>
                <a href="../index.php" class="nav-item">
                    🌐 <?php echo $lang['go_to_board']; ?>
                </a>
                <a href="../logout.php" class="nav-item logout">
                    🚪 <?php echo $lang['logout']; ?>
                </a>
            </div>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1 class="admin-page-title"><?php echo $admin_title; ?></h1>
            <div class="admin-controls">
                <form method="post" style="display: flex; align-items: center;">
                    <select name="language" onchange="this.form.submit()" class="lang-select">
                        <option value="ko" <?php echo $lang_code == 'ko' ? 'selected' : ''; ?>>🇰🇷 한국어</option>
                        <option value="en" <?php echo $lang_code == 'en' ? 'selected' : ''; ?>>🇺🇸 English</option>
                        <option value="ja" <?php echo $lang_code == 'ja' ? 'selected' : ''; ?>>🇯🇵 日本語</option>
                        <option value="zh" <?php echo $lang_code == 'zh' ? 'selected' : ''; ?>>🇨🇳 中文</option>
                    </select>
                </form>
                <button id="admin-theme-toggle" class="theme-toggle">
                    <span class="icon-sun">☀️</span>
                    <span class="icon-moon" style="display: none;">🌙</span>
                </button>
            </div>
        </header>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('admin-theme-toggle');
            const iconSun = toggleBtn.querySelector('.icon-sun');
            const iconMoon = toggleBtn.querySelector('.icon-moon');
            
            function updateIcon() {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                iconSun.style.display = isDark ? 'none' : 'inline';
                iconMoon.style.display = isDark ? 'inline' : 'none';
            }
            
            updateIcon();
            
            toggleBtn.addEventListener('click', function() {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                const newTheme = isDark ? 'light' : 'dark';
                
                if (newTheme === 'light') {
                    document.documentElement.removeAttribute('data-theme');
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
                localStorage.setItem('theme', newTheme);
                updateIcon();
            });
        });
        </script>
