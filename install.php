<?php
// DB 체크 건너뛰기 플래그 (config.php에서 사용)
define('SKIP_DB_CHECK', true);

session_start();

// DB 설정 상수 정의 (config.php 없이도 작동하도록)
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'microboard');
}

// 이미 설치되었는지 확인 (DB 연결 테스트)
$already_installed = false;
if (file_exists('config.php')) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $test_pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        // 테이블 존재 확인
        $stmt = $test_pdo->query("SHOW TABLES LIKE 'mb1_member'");
        if ($stmt->rowCount() > 0) {
            $already_installed = true;
            // 설치 완료 후에는 install.php에 접근할 수 없게 차단
            die('<h1>Installation Already Complete</h1><p>MicroBoard has already been installed. Please access the <a href="index.php">main page</a>.</p>');
        }
    } catch (Exception $e) {
        // 연결 실패 시 설치 계속 진행
        $already_installed = false;
    }
}

$error = '';
$success = '';

// 언어 파일 로드
if (isset($_POST['language'])) {
    $language = $_POST['language'];
} else {
    // 브라우저 언어 감지
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);
    $language = in_array($browser_lang, ['ko', 'en', 'ja', 'zh']) ? $browser_lang : 'en';
}

$lang_file = "lang/{$language}.php";
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    $lang = require 'lang/en.php';
}

// 설치 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $language = $_POST['language'] ?? 'en';
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'microboard';
    $admin_username = $_POST['admin_username'] ?? 'admin';
    $admin_password = $_POST['admin_password'] ?? 'admin';
    $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';
    $license_agreed = $_POST['license_agreed'] ?? '0';
    
    // 라이선스 동의 검증
    if ($license_agreed !== '1') {
        $error = $lang['input_required'];
    } else {
    // 입력값 검증
    $site_title = $_POST['site_title'] ?? 'MicroBoard';
    if (empty($db_host) || empty($db_user) || empty($db_name) || empty($admin_username) || empty($admin_password)) {
        $error = $lang['input_required'];
    } elseif ($admin_password !== $admin_password_confirm) {
        $error = $lang['password_mismatch'];
    } elseif (strlen($admin_password) < 6) {
        $error = $lang['invalid_password'];
    } elseif (!in_array($language, ['ko', 'en', 'ja', 'zh'])) {
        $error = $lang['invalid_format'];
    } else {
            try {
                // 1. 데이터베이스 이름으로 직접 연결 시도
                $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
                try {
                    $pdo = new PDO($dsn, $db_user, $db_pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                } catch (PDOException $e) {
                    // 2. 연결 실패 시 DB 없이 연결 후 생성 시도
                    $dsn_no_db = "mysql:host={$db_host};charset=utf8mb4";
                    $pdo = new PDO($dsn_no_db, $db_user, $db_pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    
                    // 데이터베이스 생성 시도
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$db_name}`");
                }
                
                // 테이블 생성
                $sql = "
                    CREATE TABLE IF NOT EXISTS `mb1_board` (
                        `wr_id` int(11) NOT NULL AUTO_INCREMENT,
                        `wr_subject` varchar(255) NOT NULL,
                        `wr_content` longtext NOT NULL,
                        `wr_name` varchar(50) NOT NULL,
                        `wr_datetime` datetime NOT NULL,
                        `wr_hit` int(11) NOT NULL DEFAULT 0,
                        PRIMARY KEY (`wr_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `mb1_board_config` (
                        `bo_table` varchar(100) NOT NULL,
                        `bo_subject` varchar(255) NOT NULL,
                        `bo_admin` varchar(50) NOT NULL DEFAULT 'admin',
                        `bo_list_count` int(11) NOT NULL DEFAULT 15,
                        `bo_use_comment` tinyint(1) NOT NULL DEFAULT 0,
                        `bo_skin` varchar(50) NOT NULL DEFAULT 'default',
                        `bo_plugins` text,
                        PRIMARY KEY (`bo_table`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `mb1_member` (
                        `mb_id` varchar(50) NOT NULL,
                        `mb_password` varchar(255) NOT NULL,
                        `mb_nickname` varchar(100) NOT NULL,
                        `mb_email` varchar(100) NOT NULL,
                        `mb_email_verified` tinyint(1) NOT NULL DEFAULT 0,
                        `mb_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
                        `mb_point` int(11) NOT NULL DEFAULT 0,
                        `mb_level` tinyint(4) NOT NULL DEFAULT 1,
                        `mb_blocked` tinyint(1) NOT NULL DEFAULT 0,
                        `mb_blocked_reason` varchar(255) DEFAULT NULL,
                        `mb_leave_date` datetime DEFAULT NULL,
                        `oauth_provider` varchar(50) DEFAULT NULL,
                        PRIMARY KEY (`mb_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `mb1_board_file` (
                        `bf_no` int(11) NOT NULL AUTO_INCREMENT,
                        `wr_id` int(11) NOT NULL,
                        `bf_source` varchar(255) NOT NULL,
                        `bf_file` varchar(255) NOT NULL,
                        `bf_download` int(11) NOT NULL DEFAULT 0,
                        `bf_content` text,
                        `bf_filesize` int(11) NOT NULL DEFAULT 0,
                        `bf_datetime` datetime NOT NULL,
                        PRIMARY KEY (`bf_no`),
                        KEY `wr_id` (`wr_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `mb1_comment` (
                        `co_id` int(11) NOT NULL AUTO_INCREMENT,
                        `wr_id` int(11) NOT NULL,
                        `co_content` text NOT NULL,
                        `co_name` varchar(50) NOT NULL,
                        `co_datetime` datetime NOT NULL,
                        PRIMARY KEY (`co_id`),
                        KEY `wr_id` (`wr_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `mb1_config` (
                        `cf_use_point` tinyint(1) NOT NULL DEFAULT 0,
                        `cf_write_point` int(11) NOT NULL DEFAULT 0,
                        `cf_language_mode` varchar(50) NOT NULL DEFAULT 'multilingual',
                        `cf_default_language` varchar(50) NOT NULL DEFAULT 'en',
                        `cf_site_title` varchar(255) NOT NULL DEFAULT 'MicroBoard'
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `mb1_point` (
                        `po_id` int(11) NOT NULL AUTO_INCREMENT,
                        `mb_id` varchar(50) NOT NULL,
                        `po_datetime` datetime NOT NULL,
                        `po_content` varchar(255) NOT NULL,
                        `po_point` int(11) NOT NULL,
                        `po_rel_table` varchar(50) DEFAULT NULL,
                        `po_rel_id` int(11) DEFAULT NULL,
                        `po_rel_action` varchar(50) DEFAULT NULL,
                        PRIMARY KEY (`po_id`),
                        KEY `mb_id` (`mb_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `mb1_oauth_config` (
                        `provider` varchar(50) NOT NULL,
                        `client_id` varchar(255) NOT NULL,
                        `client_secret` varchar(255) NOT NULL,
                        `enabled` tinyint(1) NOT NULL DEFAULT 0,
                        PRIMARY KEY (`provider`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `mb1_oauth_users` (
                        `mb_id` varchar(50) NOT NULL,
                        `provider` varchar(50) NOT NULL,
                        `provider_user_id` varchar(255) NOT NULL,
                        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`provider`, `provider_user_id`),
                        KEY `mb_id` (`mb_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS `mb1_email_settings` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `smtp_host` varchar(255) NOT NULL,
                        `smtp_port` int(11) NOT NULL,
                        `smtp_username` varchar(255) NOT NULL,
                        `smtp_password` varchar(255) NOT NULL,
                        `smtp_encryption` varchar(50) NOT NULL,
                        `sender_email` varchar(255) NOT NULL,
                        `sender_name` varchar(255) NOT NULL,
                        `require_email_verification` tinyint(1) NOT NULL DEFAULT 0,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `mb1_policy` (
                        `policy_type` varchar(50) NOT NULL,
                        `policy_title` varchar(255) NOT NULL,
                        `policy_content` longtext NOT NULL,
                        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`policy_type`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ";
                
                $pdo->exec($sql);
                
                // 기본 관리자 사용자 생성
                $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO mb1_member (mb_id, mb_password, mb_nickname, mb_email, mb_level) VALUES (?, ?, ?, ?, 10)");
                $stmt->execute([$admin_username, $password_hash, $admin_username, 'admin@example.com']);
                
                // 기본 게시판 생성
                $stmt = $pdo->prepare("INSERT INTO mb1_board_config (bo_table, bo_subject) VALUES ('free', ?)");
                $stmt->execute([$lang['free_board']]);

                // 기본 게시판 테이블 생성 (이 부분이 누락되어 있었음)
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `mb1_write_free` (
                        `wr_id` int(11) NOT NULL AUTO_INCREMENT,
                        `wr_subject` varchar(255) NOT NULL,
                        `wr_content` longtext NOT NULL,
                        `wr_name` varchar(50) NOT NULL,
                        `wr_datetime` datetime NOT NULL,
                        `wr_hit` int(11) NOT NULL DEFAULT 0,
                        `wr_comment` int(11) NOT NULL DEFAULT 0,
                        PRIMARY KEY (`wr_id`),
                        KEY `wr_name` (`wr_name`),
                        KEY `wr_datetime` (`wr_datetime`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS `mb1_comment_free` (
                        `co_id` int(11) NOT NULL AUTO_INCREMENT,
                        `wr_id` int(11) NOT NULL,
                        `co_content` text NOT NULL,
                        `co_name` varchar(50) NOT NULL,
                        `co_datetime` datetime NOT NULL,
                        PRIMARY KEY (`co_id`),
                        KEY `wr_id` (`wr_id`),
                        KEY `co_name` (`co_name`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS `mb1_board_file_free` (
                        `bf_no` int(11) NOT NULL AUTO_INCREMENT,
                        `wr_id` int(11) NOT NULL,
                        `bf_source` varchar(255) NOT NULL,
                        `bf_file` varchar(255) NOT NULL,
                        `bf_download` int(11) NOT NULL DEFAULT 0,
                        `bf_content` text,
                        `bf_filesize` int(11) NOT NULL DEFAULT 0,
                        `bf_datetime` datetime NOT NULL,
                        PRIMARY KEY (`bf_no`),
                        KEY `wr_id` (`wr_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                
                // OAuth 기본 설정 추가
                $providers = ['google', 'line', 'apple'];
                foreach ($providers as $provider) {
                    $stmt = $pdo->prepare("INSERT INTO mb1_oauth_config (provider, client_id, client_secret, enabled) VALUES (?, '', '', 0)");
                    $stmt->execute([$provider]);
                }
                
                // 기본 설정 추가
                $stmt = $pdo->prepare("INSERT INTO mb1_config (cf_use_point, cf_write_point, cf_site_title) VALUES (0, 0, ?)");
                $stmt->execute([$site_title]);
                
                // 다국어 정책 데이터 준비
                $policies = [
                    'ko' => [
                        'terms_title' => '이용약관',
                        'privacy_title' => '개인정보 보호정책',
                        'terms' => '<h2>제1조 (목적)</h2>
<p>본 약관은 MicroBoard(이하 "회사"라 함)가 제공하는 서비스의 이용과 관련하여 회사와 회원 간의 권리, 의무 및 책임사항, 기타 필요한 사항을 규정함을 목적으로 합니다.</p>
<h2>제2조 (개인정보보호 의무)</h2>
<p>"회사"는 관련 법령이 정하는 바에 따라 "회원"의 개인정보를 보호하기 위해 노력합니다.</p>
<h2>제3조 (회원의 의무)</h2>
<p>"회원"은 본 약관 및 관계 법령을 준수하여야 하며, 기타 "회사"의 업무에 방해되는 행위를 하여서는 안 됩니다.</p>',
                        'privacy' => '<h2>1. 개인정보의 처리 목적</h2>
<p>MicroBoard는 회원제 서비스 제공을 위해 개인정보를 처리합니다.</p>
<h2>2. 처리하는 개인정보의 항목</h2>
<p>아이디, 비밀번호, 접속 로그, 쿠키, 접속 IP 정보 등</p>
<h2>3. 개인정보의 보유 및 이용 기간</h2>
<p>회원 탈퇴 시까지 보유하며, 관계 법령에 따른 보존 의무가 있는 경우 해당 기간 동안 보존합니다.</p>'
                    ],
                    'en' => [
                        'terms_title' => 'Terms of Service',
                        'privacy_title' => 'Privacy Policy',
                        'terms' => '<h2>Article 1 (Purpose)</h2>
<p>These Terms of Service aim to define the rights, obligations, and responsibilities of the company and members regarding the use of services provided by MicroBoard.</p>
<h2>Article 2 (Privacy Protection)</h2>
<p>The Company strives to protect the personal information of members in accordance with relevant laws and regulations.</p>
<h2>Article 3 (Obligations of Members)</h2>
<p>Members must comply with these Terms and relevant laws and must not engage in any activities that interfere with the Company\'s operations.</p>',
                        'privacy' => '<h2>1. Purpose of Data Processing</h2>
<p>MicroBoard processes personal information to provide membership services.</p>
<h2>2. Collected Items</h2>
<p>Username, password, access logs, cookies, IP address, etc.</p>
<h2>3. Retention and Use Period</h2>
<p>Personal information is retained until membership withdrawal, or as required by relevant laws.</p>'
                    ],
                    'ja' => [
                        'terms_title' => '利用規約',
                        'privacy_title' => 'プライバシーポリシー',
                        'terms' => '<h2>第1条（目的）</h2>
<p>本規約は、MicroBoard（以下「当社」といいます）が提供するサービスの利用に関し、当社と会員との間の権利、義務及び責任事項を定めることを目的とします。</p>
<h2>第2条（個人情報の保護）</h2>
<p>当社は、関連法令の定めに従い、会員の個人情報を保護するために努力します。</p>
<h2>第3条（会員の義務）</h2>
<p>会員は、本規約及び関係法令を遵守しなければならず、その他当社の業務を妨害する行為をしてはなりません。</p>',
                        'privacy' => '<h2>1. 個人情報の処理目的</h2>
<p>MicroBoardは、会員制サービスの提供のために個人情報を処理します。</p>
<h2>2. 収集する個人情報の項目</h2>
<p>ID、パスワード、アクセスログ、Cookie、接続IP情報など</p>
<h2>3. 個人情報の保有及び利用期間</h2>
<p>会員退会時まで保有し、関係法令による保存義務がある場合はその期間中保存します。</p>'
                    ],
                    'zh' => [
                        'terms_title' => '服务条款',
                        'privacy_title' => '隐私政策',
                        'terms' => '<h2>第一条（目的）</h2>
<p>本条款旨在规定MicroBoard（以下简称“公司”）与会员之间关于使用公司提供的服务时的权利、义务及责任事项。</p>
<h2>第二条（个人信息保护义务）</h2>
<p>“公司”根据相关法律法规的规定，致力于保护“会员”的个人信息。</p>
<h2>第三条（会员的义务）</h2>
<p>“会员”必须遵守本条款及相关法律法规，不得从事干扰“公司”业务的行为。</p>',
                        'privacy' => '<h2>1. 个人信息处理目的</h2>
<p>MicroBoard为了提供会员制服务而处理个人信息。</p>
<h2>2. 手记的个人信息项目</h2>
<p>用户名、密码、访问日志、Cookie、访问IP信息等。</p>
<h2>3. 个人信息的持有及使用期间</h2>
<p>保留至会员退会为止，如果根据相关法律法规有保存义务，则在该期间内保存。</p>'
                    ]
                ];

                $sql = "INSERT INTO mb1_policy (policy_type, policy_title, policy_content, updated_at) VALUES (?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);

                foreach ($policies as $pol_lang => $data) {
                    // 각 언어별 삽입
                    $stmt->execute(['terms_' . $pol_lang, $data['terms_title'], $data['terms']]);
                    $stmt->execute(['privacy_' . $pol_lang, $data['privacy_title'], $data['privacy']]);
                    
                    // 기본값(접미사 없는 것)은 사용자가 선택한 언어로 설정하거나 기본적으로 영어 사용
                    // 설치 시 선택한 언어($language)와 일치하면 기본값으로 설정
                    if ($pol_lang === $language) {
                        $stmt->execute(['terms', $data['terms_title'], $data['terms']]);
                        $stmt->execute(['privacy', $data['privacy_title'], $data['privacy']]);
                    }
                }
                
                // 만약 선택된 언어에 대한 정책이 루프에서 처리되지 않았다면(fallback to en)
                // 이미 위에서 처리되지만, 안전을 위해 'terms'가 없으면 영어로 강제 설정
                $check = $pdo->query("SELECT COUNT(*) FROM mb1_policy WHERE policy_type = 'terms'")->fetchColumn();
                if ($check == 0 && isset($policies['en'])) {
                    $stmt->execute(['terms', $policies['en']['terms_title'], $policies['en']['terms']]);
                    $stmt->execute(['privacy', $policies['en']['privacy_title'], $policies['en']['privacy']]);
                }
                
                // config.php 파일 업데이트 (DB 정보만 수정)
                $config_path = __DIR__ . '/config.php';
                $config_content = file_get_contents($config_path);
                
                // DB 설정 부분만 교체
                $config_content = preg_replace(
                    "/define\('DB_HOST',\s*'[^']*'\);/",
                    "define('DB_HOST', '{$db_host}');",
                    $config_content
                );
                $config_content = preg_replace(
                    "/define\('DB_USER',\s*'[^']*'\);/",
                    "define('DB_USER', '{$db_user}');",
                    $config_content
                );
                $config_content = preg_replace(
                    "/define\('DB_PASS',\s*'[^']*'\);/",
                    "define('DB_PASS', '" . addslashes($db_pass) . "');",
                    $config_content
                );
                $config_content = preg_replace(
                    "/define\('DB_NAME',\s*'[^']*'\);/",
                    "define('DB_NAME', '{$db_name}');",
                    $config_content
                );
                
                file_put_contents($config_path, $config_content);
                
                $success = $lang['installation_success'];
                
            } catch (Exception $e) {
                $error = $lang['db_conn_failed'] . ': ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo substr($language, 0, 2); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MicroBoard - <?php echo $lang['install_title']; ?></title>
    <link rel="stylesheet" href="skin/default/style.css">
<link rel="stylesheet" href="skin/default/style.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --secondary-color: #1f2937;
            --bg-color: #f3f4f6;
            --card-bg: #ffffff;
            --text-color: #111827;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
            --radius: 0.5rem;
            --radius-lg: 0.75rem;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .install-container {
            width: 100%;
            max-width: 650px;
            margin: 2rem 1rem;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            background: var(--card-bg);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .install-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--secondary-color);
        }

        .install-title h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
            background: linear-gradient(135deg, var(--primary-color), #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            color: var(--text-color);
            font-size: 0.95rem;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.95rem;
            background-color: #f9fafb;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn {
            background: var(--primary-color);
            color: white;
            padding: 0.85rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            transition: background 0.2s, transform 0.1s;
        }
        
        .btn:hover {
            background: var(--primary-hover);
        }

        .btn:active {
            transform: translateY(1px);
        }
        
        .btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid #ef4444;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error::before {
            content: "⚠️";
        }
        
        .success {
            background: #dcfce7;
            color: #15803d;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            text-align: center;
            border: 1px solid #bbf7d0;
        }
        
        .language-selector {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .language-options {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .language-option {
            padding: 0.6rem 1.2rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .language-option:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: #f5f3ff;
        }
        
        .language-option.selected {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: var(--shadow);
        }
        
        .license-agreement {
            margin-top: 1.5rem;
            padding: 1.25rem;
            background: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            text-align: center;
        }

        .license-agreement input[type="checkbox"] {
            accent-color: var(--primary-color);
            transform: scale(1.1);
        }
        
        .success-message a {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 2rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
        }
        
        .success-message a:hover {
            background: var(--primary-hover);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background-color: var(--card-bg);
            margin: 5vh auto;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 700px;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }
        
        .modal-header {
            padding: 1.25rem 1.5rem;
            background: var(--bg-color);
            border-bottom: 1px solid var(--border-color);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--secondary-color);
        }
        
        .close {
            color: var(--text-light);
            font-size: 1.75rem;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            width: 2rem;
            height: 2rem;
            line-height: 2rem;
            text-align: center;
            border-radius: 50%;
            transition: background 0.2s;
        }
        
        .close:hover {
            background: rgba(0,0,0,0.05);
            color: var(--text-color);
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        
        .modal-body pre {
            background: #1f2937;
            color: #e5e7eb;
            padding: 1.5rem;
            border-radius: var(--radius);
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            text-align: right;
            background: var(--bg-color);
        }

        @media (max-width: 640px) {
            .install-container {
                margin: 0;
                border-radius: 0;
                padding: 1.5rem;
                min-height: 100vh;
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-title">
            <h1>MicroBoard</h1>
            <p style="color: var(--text-light); margin: 0;"><?php echo $lang['install_title']; ?></p>
        </div>

        <div class="language-selector">
            <form method="post" style="display: inline;">
                <input type="hidden" name="language" id="selected_language" value="<?php echo htmlspecialchars($language); ?>">
                <div class="language-options">
                    <div class="language-option <?php echo $language === 'ko' ? 'selected' : ''; ?>" data-lang="ko">
                        🇰🇷 한국어
                    </div>
                    <div class="language-option <?php echo $language === 'en' ? 'selected' : ''; ?>" data-lang="en">
                        🇬🇧 English
                    </div>
                    <div class="language-option <?php echo $language === 'ja' ? 'selected' : ''; ?>" data-lang="ja">
                        🇯🇵 日本語
                    </div>
                    <div class="language-option <?php echo $language === 'zh' ? 'selected' : ''; ?>" data-lang="zh">
                        🇨🇳 中文
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success success-message">
                <h3 style="margin: 0 0 1rem 0; color: #15803d;">🎉 Installation Complete!</h3>
                <p><?php echo htmlspecialchars($success); ?></p>
                <a href="index.php"><?php echo $lang['go_to_main']; ?></a>
            </div>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="install">
                <input type="hidden" name="language" value="<?php echo htmlspecialchars($language); ?>">
                <input type="hidden" name="license_agreed" id="license_agreed" value="0">
                
                <div class="section-title"><?php echo $lang['db_settings']; ?></div>
                <div class="form-group">
                    <label for="db_host"><?php echo $lang['db_host']; ?></label>
                    <input type="text" name="db_host" id="db_host" value="localhost" placeholder="e.g., localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user"><?php echo $lang['username']; ?></label>
                    <input type="text" name="db_user" id="db_user" value="root" placeholder="e.g., root" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass"><?php echo $lang['password']; ?></label>
                    <input type="password" name="db_pass" id="db_pass" value="" placeholder="DB Password">
                </div>
                
                <div class="form-group">
                    <label for="db_name"><?php echo $lang['db_name']; ?></label>
                    <input type="text" name="db_name" id="db_name" value="microboard" placeholder="e.g., microboard" required>
                </div>
                
                <div class="section-title"><?php echo $lang['admin_settings']; ?></div>
                <div class="form-group">
                    <label for="site_title">Site Title</label>
                    <input type="text" name="site_title" id="site_title" value="MicroBoard" placeholder="Your site title" required>
                </div>

                <div class="form-group">
                    <label for="admin_username"><?php echo $lang['username']; ?></label>
                    <input type="text" name="admin_username" id="admin_username" value="admin" placeholder="Admin ID" required>
                </div>

                <div class="form-group">
                    <label for="admin_password"><?php echo $lang['password']; ?></label>
                    <input type="password" name="admin_password" id="admin_password" placeholder="Min. 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="admin_password_confirm"><?php echo $lang['password_confirm']; ?></label>
                    <input type="password" name="admin_password_confirm" id="admin_password_confirm" placeholder="Confirm Password" required>
                </div>
                
                <div class="license-agreement">
                    <input type="checkbox" id="license-checkbox" required>
                    <label for="license-checkbox">
                        <strong><?php echo $lang['license_agreement']; ?></strong><br>
                        <?php echo $lang['agree_message']; ?>
                        <br>
                        <a href="#" onclick="showLicense(); return false;" style="color: #007bff; text-decoration: underline;">
                            <?php echo isset($lang['view_license']) ? $lang['view_license'] : 'View License'; ?>
                        </a>
                    </label>
                </div>
                
                <button type="submit" class="btn" id="install-btn" disabled><?php echo $lang['install_btn']; ?></button>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        // 언어 선택
        document.querySelectorAll('.language-option').forEach(option => {
            option.addEventListener('click', function() {
                const lang = this.getAttribute('data-lang');
                
                // 선택 표시 제거
                document.querySelectorAll('.language-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // 선택 표시 추가
                this.classList.add('selected');
                
                // 폼에 언어 설정
                document.getElementById('selected_language').value = lang;
                
                // 폼 자동 제출
                document.querySelector('.language-selector form').submit();
            });
        });
        
        // 체크박스 변경 감지
        const licenseCheckbox = document.getElementById('license-checkbox');
        const installBtn = document.getElementById('install-btn');
        
        if (licenseCheckbox && installBtn) {
            licenseCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('license_agreed').value = '1';
                    installBtn.disabled = false;
                } else {
                    document.getElementById('license_agreed').value = '0';
                    installBtn.disabled = true;
                }
            });
        }
        
        // 폼 검증
        const installForm = document.querySelector('form[method="post"]');
        if (installForm && installForm.querySelector('input[name="action"]')) {
            installForm.addEventListener('submit', function(e) {
                const password = document.getElementById('admin_password').value;
                const confirmPassword = document.getElementById('admin_password_confirm').value;
                const licenseAgreed = document.getElementById('license_agreed').value;
                
                if (licenseAgreed !== '1') {
                    e.preventDefault();
                    alert('<?php echo $lang['input_required']; ?>');
                    return false;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('<?php echo $lang['password_mismatch']; ?>');
                    return false;
                }
            });
        }
        
        // 라이센스 모달 함수
        function showLicense() {
            document.getElementById('license-modal').style.display = 'block';
        }
        
        function closeLicense() {
            document.getElementById('license-modal').style.display = 'none';
        }
        
        // 모달 외부 클릭 시 닫기
        window.onclick = function(event) {
            const modal = document.getElementById('license-modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <!-- License Modal -->
    <div id="license-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>MIT License</h2>
                <button class="close" onclick="closeLicense()">&times;</button>
            </div>
            <div class="modal-body">
                <pre><?php
                $license_file = __DIR__ . '/LICENSE';
                if (file_exists($license_file)) {
                    echo htmlspecialchars(file_get_contents($license_file));
                } else {
                    echo $lang['mit_license'] . "\n\n";
                    echo $lang['mit_copyright'] . "\n\n";
                    echo $lang['mit_permission'] . "\n\n";
                    echo $lang['mit_notice'] . "\n\n";
                    echo $lang['mit_warranty'];
                }
                ?></pre>
            </div>
            <div class="modal-footer">
                <button onclick="closeLicense()">Close</button>
            </div>
        </div>
    </div>
</body>
</html>
