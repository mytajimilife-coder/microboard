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
            header('Location: index.php');
            exit;
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
                        `cf_write_point` int(11) NOT NULL DEFAULT 0
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
                $stmt = $pdo->prepare("INSERT INTO mb1_member (mb_id, mb_password, mb_level) VALUES (?, ?, 10)");
                $stmt->execute([$admin_username, $password_hash]);
                
                // 기본 게시판 생성
                $stmt = $pdo->prepare("INSERT INTO mb1_board_config (bo_table, bo_subject) VALUES ('free', ?)");
                $stmt->execute([$lang['free_board']]);
                
                // OAuth 기본 설정 추가
                $providers = ['google', 'line', 'apple'];
                foreach ($providers as $provider) {
                    $stmt = $pdo->prepare("INSERT INTO mb1_oauth_config (provider, client_id, client_secret, enabled) VALUES (?, '', '', 0)");
                    $stmt->execute([$provider]);
                }
                
                // 기본 설정 추가
                $stmt = $pdo->prepare("INSERT INTO mb1_config (cf_use_point, cf_write_point) VALUES (0, 0)");
                $stmt->execute();
                
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
    <style>
        .install-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .language-selector {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .language-selector h2 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .language-options {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .language-option {
            padding: 10px 20px;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .language-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        
        .language-option.selected {
            border-color: #007bff;
            background: #e3f2fd;
        }
        
        .license-agreement {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-align: center;
        }
        
        .success-message {
            text-align: center;
        }
        
        .success-message a {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .success-message a:hover {
            background: #0056b3;
        }
        
        /* License Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            border-radius: 8px;
            width: 80%;
            max-width: 700px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            padding: 20px;
            background: #007bff;
            color: white;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
        }
        
        .close:hover,
        .close:focus {
            color: #ddd;
        }
        
        .modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .modal-body pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #333;
        }
        
        .modal-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
            text-align: right;
            border-top: 1px solid #dee2e6;
        }
        
        .modal-footer button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .modal-footer button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="language-selector">
            <h2>MicroBoard - <?php echo $lang['install_title']; ?></h2>
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
                <p><?php echo htmlspecialchars($success); ?></p>
                <a href="index.php"><?php echo $lang['go_to_main']; ?></a>
            </div>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="install">
                <input type="hidden" name="language" value="<?php echo htmlspecialchars($language); ?>">
                <input type="hidden" name="license_agreed" id="license_agreed" value="0">
                
                <h3><?php echo $lang['db_settings']; ?></h3>
                <div class="form-group">
                    <label for="db_host"><?php echo $lang['db_host']; ?>:</label>
                    <input type="text" name="db_host" id="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user"><?php echo $lang['username']; ?>:</label>
                    <input type="text" name="db_user" id="db_user" value="root" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass"><?php echo $lang['password']; ?>:</label>
                    <input type="password" name="db_pass" id="db_pass" value="">
                </div>
                
                <div class="form-group">
                    <label for="db_name"><?php echo $lang['db_name']; ?>:</label>
                    <input type="text" name="db_name" id="db_name" value="microboard" required>
                </div>
                
                <h3><?php echo $lang['admin_settings']; ?></h3>
                <div class="form-group">
                    <label for="admin_username"><?php echo $lang['username']; ?>:</label>
                    <input type="text" name="admin_username" id="admin_username" value="admin" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password"><?php echo $lang['password']; ?>:</label>
                    <input type="password" name="admin_password" id="admin_password" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password_confirm"><?php echo $lang['password_confirm']; ?>:</label>
                    <input type="password" name="admin_password_confirm" id="admin_password_confirm" required>
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
                    echo "MIT License\n\n";
                    echo "Copyright (c) 2025 YECHANHO\n\n";
                    echo "Permission is hereby granted, free of charge, to any person obtaining a copy\n";
                    echo "of this software and associated documentation files (the \"Software\"), to deal\n";
                    echo "in the Software without restriction, including without limitation the rights\n";
                    echo "to use, copy, modify, merge, publish, distribute, sublicense, and/or sell\n";
                    echo "copies of the Software, and to permit persons to whom the Software is\n";
                    echo "furnished to do so, subject to the following conditions:\n\n";
                    echo "The above copyright notice and this permission notice shall be included in all\n";
                    echo "copies or substantial portions of the Software.\n\n";
                    echo "THE SOFTWARE IS PROVIDED \"AS IS\", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR\n";
                    echo "IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,\n";
                    echo "FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE\n";
                    echo "AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER\n";
                    echo "LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,\n";
                    echo "OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE\n";
                    echo "SOFTWARE.";
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
