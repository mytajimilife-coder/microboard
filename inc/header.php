<?php
// 페이지 제목 설정
$page_title = $page_title ?? 'MicroBoard';

// 루트 경로 계산
$root_path = './';
if (file_exists('./config.php')) {
    $root_path = './';
} elseif (file_exists('../config.php')) {
    $root_path = '../';
} elseif (file_exists('../../config.php')) {
    $root_path = '../../';
}

// 설정 가져오기
$config = get_config();
$default_theme = $config['cf_theme'] ?? 'light';
$bg_type = $config['cf_bg_type'] ?? 'color';
$bg_value = $config['cf_bg_value'] ?? '#ffffff';

// SEO 메타 데이터 설정 (기본값)
$page_title = $page_title ?? 'MicroBoard';
$meta_description = $meta_description ?? 'MicroBoard - 가볍고 강력한 PHP 게시판';
$meta_keywords = $meta_keywords ?? 'microboard, php board, community, 게시판';
$og_image = $og_image ?? $root_path . 'img/logo.png';
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$canonical_url = $canonical_url ?? $current_url;

// 배경 스타일 생성
$custom_bg_style = '';
if ($bg_type === 'image') {
    // 이미지 경로에 루트 경로 적용
    $custom_bg_style = "url('" . $root_path . htmlspecialchars($bg_value) . "')";
} else {
    $custom_bg_style = $bg_value;
}
?>
<!DOCTYPE html>
<html lang="<?php echo substr($lang_code ?? 'ko', 0, 2); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($page_title); ?> - MicroBoard</title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="MicroBoard">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">
    
    <link rel="stylesheet" href="<?php echo $root_path; ?>skin/default/style.css">
    <link rel="icon" type="image/svg+xml" href="<?php echo $root_path; ?>img/favicon.svg">
    <link rel="alternate icon" href="<?php echo $root_path; ?>img/favicon.svg">
    <style>
        /* 관리자 설정 배경 적용 */
        :root {
            --body-bg: <?php echo $custom_bg_style; ?>;
        }
        
        <?php if ($bg_type === 'image'): ?>
        body {
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        <?php endif; ?>
    </style>
    <script>
        // 테마 초기화 스크립트
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const defaultTheme = '<?php echo $default_theme; ?>';
            const theme = savedTheme || defaultTheme;
            
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.removeAttribute('data-theme');
            }
        })();
    </script>
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="<?php echo $root_path; ?>index.php" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none;">
                    <?php
                    // 로고 출력 훅 호출
                    $logo = 'img/logo.png'; // 기본 로고
                    $logo = apply_hooks('before_logo_display', $logo);
                    
                    // 사이트명 가져오기
                    $site_title = 'MicroBoard'; // 기본 사이트명
                    if (function_exists('get_config')) {
                        $config = get_config();
                        $site_title = $config['cf_site_title'] ?? 'MicroBoard';
                    }
                    ?>
                    <?php if (file_exists($logo)): ?>
                        <img src="<?php echo $root_path . $logo; ?>" alt="<?php echo htmlspecialchars($site_title); ?> Logo" style="height: 30px;">
                    <?php else: ?>
                        <span style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);"><?php echo htmlspecialchars($site_title); ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <nav class="main-nav">
                <ul class="nav-menu">
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo $root_path; ?>list.php"><?php echo $lang['board_list']; ?></a></li>
                        <li><a href="<?php echo $root_path; ?>user/mypage.php"><?php echo $lang['mypage']; ?></a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="<?php echo $root_path; ?>admin/index.php"><?php echo $lang['admin_home']; ?></a></li>
                            <li><a href="<?php echo $root_path; ?>admin/users.php"><?php echo $lang['user_management']; ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="user-info">
                <!-- 테마 토글 버튼 -->
                <button id="theme-toggle" class="theme-toggle-btn" title="Toggle Theme" style="margin-right: 15px;">
                    <span class="icon-sun">☀️</span>
                    <span class="icon-moon" style="display: none;">🌙</span>
                </button>

                <div class="lang-selector" style="margin-right: 15px; display: flex; gap: 8px; align-items: center;">
                    <?php 
                    $langs = ['ko' => '🇰🇷', 'en' => '🇺🇸', 'ja' => '🇯🇵', 'zh' => '🇨🇳'];
                    $current_lang = $_SESSION['lang'] ?? 'ko';
                    foreach ($langs as $code => $flag) {
                        $params = $_GET;
                        $params['lang'] = $code;
                        $url = '?' . http_build_query($params);
                        $opacity = ($current_lang === $code) ? '1' : '0.4';
                        echo "<a href=\"{$url}\" style=\"text-decoration: none; opacity: {$opacity}; transition: opacity 0.2s; font-size: 1.2em; filter: grayscale(" . ($current_lang === $code ? '0' : '1') . ");\">{$flag}</a>";
                    }
                    ?>
                </div>
                <?php if (isLoggedIn()): ?>
                    <span class="username">
                        <?php echo htmlspecialchars($_SESSION['user']); ?><?php echo $lang['user_suffix']; ?>
                        <?php 
                        // 포인트 시스템 사용 여부 확인
                        $config = get_config();
                        if (isset($config['cf_use_point']) && $config['cf_use_point']) {
                            $db = getDB();
                            $stmt = $db->prepare("SELECT mb_point FROM mb1_member WHERE mb_id = ?");
                            $stmt->execute([$_SESSION['user']]);
                            $member = $stmt->fetch();
                            if ($member && isset($member['mb_point'])) {
                                echo " <span style='font-size: 0.9em; color: #ffc107;'>(" . number_format($member['mb_point']) . " P)</span>";
                            }
                        }
                        ?>
                    </span>
                    <a href="<?php echo $root_path; ?>logout.php" class="btn secondary"><?php echo $lang['logout']; ?></a>
                <?php else: ?>
                    <a href="<?php echo $root_path; ?>login.php" class="btn"><?php echo $lang['login']; ?></a>
                    <a href="<?php echo $root_path; ?>register.php" class="btn secondary"><?php echo $lang['register']; ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('theme-toggle');
        const iconSun = toggleBtn.querySelector('.icon-sun');
        const iconMoon = toggleBtn.querySelector('.icon-moon');
        
        function updateIcon() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (isDark) {
                iconSun.style.display = 'none';
                iconMoon.style.display = 'inline';
            } else {
                iconSun.style.display = 'inline';
                iconMoon.style.display = 'none';
            }
        }
        
        updateIcon();
        
        toggleBtn.addEventListener('click', function() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            
            if (isDark) {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
            
            updateIcon();
        });
    });
    </script>
