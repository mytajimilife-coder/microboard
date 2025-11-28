<?php
session_start();

// 이미 설치된 경우 리디렉션
if (file_exists('config.php')) {
    require_once 'config.php';
    if (function_exists('getDB')) {
        header('Location: index.php');
        exit;
    }
}

$error = '';
$success = '';

// 언어 파일 로드
$language = $_POST['language'] ?? 'ja';
$lang_file = "lang/{$language}.php";
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    $lang = require 'lang/ja.php';
}

// 설치 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $language = $_POST['language'] ?? 'ja';
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
                // 1. 데이터베이스 이름으로 직접 연결 시도 (일반 호스팅 호환성)
                // Shared hosting often restricts users to specific databases and disallows connecting without a DB name.
                $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
                try {
                    $pdo = new PDO($dsn, $db_user, $db_pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                } catch (PDOException $e) {
                    // 2. 연결 실패 시 (DB가 없거나 접근 불가), DB 없이 연결 후 생성 시도
                    // If the DB doesn't exist or we can't connect to it directly, try connecting to the server root and creating it.
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
                    CREATE TABLE IF NOT EXISTS `g5_board` (
                        `wr_id` int(11) NOT NULL AUTO_INCREMENT,
                        `wr_subject` varchar(255) NOT NULL,
                        `wr_content` longtext NOT NULL,
                        `wr_name` varchar(50) NOT NULL,
                        `wr_datetime` datetime NOT NULL,
                        `wr_hit` int(11) NOT NULL DEFAULT 0,
                        PRIMARY KEY (`wr_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `g5_board_config` (
                        `bo_table` varchar(100) NOT NULL,
                        `bo_subject` varchar(255) NOT NULL,
                        `bo_admin` varchar(50) NOT NULL DEFAULT 'admin',
                        `bo_list_count` int(11) NOT NULL DEFAULT 15,
                        `bo_use_comment` tinyint(1) NOT NULL DEFAULT 0,
                        `bo_skin` varchar(50) NOT NULL DEFAULT 'default',
                        PRIMARY KEY (`bo_table`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    
                    CREATE TABLE IF NOT EXISTS `g5_member` (
                        `mb_id` varchar(50) NOT NULL,
                        `mb_password` varchar(255) NOT NULL,
                        PRIMARY KEY (`mb_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ";
                
                $pdo->exec($sql);
                
                // 기본 관리자 사용자 생성
                $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO g5_member (mb_id, mb_password) VALUES (?, ?)");
                $stmt->execute([$admin_username, $password_hash]);
                
                // 기본 게시판 생성
                $stmt = $pdo->prepare("INSERT INTO g5_board_config (bo_table, bo_subject) VALUES ('free', '자유게시판')");
                $stmt->execute();
                
                // config.php 파일 생성
                $config_content = "<?php
session_start();

// 선택된 언어 설정
if (!isset(\$_SESSION['lang'])) {
    \$_SESSION['lang'] = '{$language}';
}

// 언어 파일 로드
\$lang_path = __DIR__ . '/lang/';
\$lang_code = isset(\$_SESSION['lang']) ? \$_SESSION['lang'] : 'ko';
if (file_exists(\$lang_path . \$lang_code . '.php')) {
    \$lang = require \$lang_path . \$lang_code . '.php';
} else {
    \$lang = require \$lang_path . 'ja.php';
}

// DB 설정
define('DB_HOST', '{$db_host}');
define('DB_USER', '{$db_user}');
define('DB_PASS', '" . addslashes($db_pass) . "');
define('DB_NAME', '{$db_name}');

// DB 연결
function getDB() {
  static \$pdo = null;
  if (\$pdo === null) {
    \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
      \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
      ]);
    } catch (PDOException \$e) {
      global \$lang;
      die(\$lang['db_conn_failed'] . ' : ' . \$e->getMessage());
    }
  }
  return \$pdo;
}

// 테이블 생성 (install.php에서 호출)
function createTables() {
  \$db = getDB();
  \$db->exec(\"
    CREATE TABLE IF NOT EXISTS \`g5_board\` (
      \`wr_id\` int(11) NOT NULL AUTO_INCREMENT,
      \`wr_subject\` varchar(255) NOT NULL,
      \`wr_content\` longtext NOT NULL,
      \`wr_name\` varchar(50) NOT NULL,
      \`wr_datetime\` datetime NOT NULL,
      \`wr_hit\` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (\`wr_id\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  \");
  \$db->exec(\"
    CREATE TABLE IF NOT EXISTS \`g5_board_config\` (
      \`bo_table\` varchar(100) NOT NULL,
      \`bo_subject\` varchar(255) NOT NULL,
      \`bo_admin\` varchar(50) NOT NULL DEFAULT 'admin',
      \`bo_list_count\` int(11) NOT NULL DEFAULT 15,
      \`bo_use_comment\` tinyint(1) NOT NULL DEFAULT 0,
      \`bo_skin\` varchar(50) NOT NULL DEFAULT 'default',
      PRIMARY KEY (`bo_table`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  \");
  \$db->exec(\"
    CREATE TABLE IF NOT EXISTS \`g5_member\` (
      \`mb_id\` varchar(50) NOT NULL,
      \`mb_password\` varchar(255) NOT NULL,
      PRIMARY KEY (\`mb_id\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  \");
}

// 게시물 함수들
function loadPosts() {
  \$db = getDB();
  \$stmt = \$db->query('SELECT * FROM g5_board ORDER BY wr_id DESC');
  return \$stmt->fetchAll();
}

function insertPost(\$data) {
  \$db = getDB();
  \$sql = 'INSERT INTO g5_board (wr_subject, wr_content, wr_name, wr_datetime, wr_hit) VALUES (?, ?, ?, NOW(), 0)';
  \$stmt = \$db->prepare(\$sql);
  \$stmt->execute([\$data['title'], \$data['content'], \$data['writer']]);
  return \$db->lastInsertId();
}

function updatePost(\$id, \$data) {
  \$db = getDB();
  \$sql = 'UPDATE g5_board SET wr_subject = ?, wr_content = ?, wr_name = ?, wr_datetime = NOW() WHERE wr_id = ?';
  \$stmt = \$db->prepare(\$sql);
  \$stmt->execute([\$data['title'], \$data['content'], \$data['writer'], \$id]);
}

function getPost(\$id) {
  \$db = getDB();
  \$stmt = \$db->prepare('SELECT * FROM g5_board WHERE wr_id = ?');
  \$stmt->execute([\$id]);
  return \$stmt->fetch() ?: ['wr_subject' => '', 'wr_content' => '', 'wr_name' => '', 'wr_datetime' => '', 'wr_hit' => 0];
}

function incrementView(\$id) {
  \$db = getDB();
  \$stmt = \$db->prepare('UPDATE g5_board SET wr_hit = wr_hit + 1 WHERE wr_id = ?');
  \$stmt->execute([\$id]);
}

function deletePost(\$id) {
  \$db = getDB();
  \$stmt = \$db->prepare('DELETE FROM g5_board WHERE wr_id = ?');
  \$stmt->execute([\$id]);
}

// 로그인 체크
function isLoggedIn() {
  if (!isset(\$_SESSION['user'])) {
    return false;
  }
  
  // 세션 타임아웃 체크 (30분)
  if (isset(\$_SESSION['login_time']) && (time() - \$_SESSION['login_time'] > 1800)) {
    session_unset();
    session_destroy();
    return false;
  }
  
  return true;
}

function requireLogin() {
  if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
  }
}

function verifyUser(\$id, \$pass) {
  // 입력값 검증
  \$id = trim(\$id);
  \$pass = trim(\$pass);
  
  // SQL 인젝션 방지 및 입력값 길이 제한
  if (empty(\$id) || empty(\$pass) || strlen(\$id) > 50 || strlen(\$pass) > 255) {
    return false;
  }
  
  \$db = getDB();
  \$stmt = \$db->prepare('SELECT mb_password FROM g5_member WHERE mb_id = ?');
  \$stmt->execute([\$id]);
  \$user = \$stmt->fetch();
  return \$user && password_verify(\$pass, \$user['mb_password']);
}

// 회원가입 함수
function registerUser(\$username, \$password) {
  // 입력값 검증
  \$username = trim(\$username);
  \$password = trim(\$password);
  
  if (empty(\$username) || empty(\$password) || strlen(\$username) > 20 || strlen(\$password) > 255) {
    return false;
  }
  
  // 비밀번호 해시
  \$password_hash = password_hash(\$password, PASSWORD_DEFAULT);
  
  \$db = getDB();
  try {
    \$stmt = \$db->prepare(\"INSERT INTO g5_member (mb_id, mb_password) VALUES (?, ?)\");
    return \$stmt->execute([\$username, \$password_hash]);
  } catch (Exception \$e) {
    // 중복 키 등 오류 처리
    return false;
  }
}

// 아이디 중복 체크 함수
function isUsernameExists(\$username) {
  \$username = trim(\$username);
  
  if (empty(\$username)) {
    return false;
  }
  
  \$db = getDB();
  \$stmt = \$db->prepare('SELECT COUNT(*) as count FROM g5_member WHERE mb_id = ?');
  \$stmt->execute([\$username]);
  \$result = \$stmt->fetch();
  
  return \$result['count'] > 0;
}

// 사용자 게시물 조회 함수
function getUserPosts(\$username) {
  \$username = trim(\$username);
  
  if (empty(\$username)) {
    return [];
  }
  
  \$db = getDB();
  \$stmt = \$db->prepare('SELECT * FROM g5_board WHERE wr_name = ? ORDER BY wr_id DESC');
  \$stmt->execute([\$username]);
  
  return \$stmt->fetchAll();
}

// 사용자 댓글 조회 함수 (댓글 테이블이 없는 경우를 대비한 기본 구조)
function getUserComments(\$username) {
  \$username = trim(\$username);
  
  if (empty(\$username)) {
    return [];
  }
  
  // 댓글 기능이 구현되지 않은 경우 빈 배열 반환
  // 댓글 기능 구현 시 여기에 실제 쿼리 추가
  return [];
}

// 모든 회원 조회 함수
function getAllUsers() {
  \$db = getDB();
  \$stmt = \$db->prepare('SELECT mb_id, mb_datetime FROM g5_member ORDER BY mb_datetime DESC, mb_id ASC');
  \$stmt->execute();
  
  return \$stmt->fetchAll();
}

// 회원 탈퇴 함수
function deleteUser(\$username) {
  \$username = trim(\$username);
  
  if (empty(\$username) || \$username === 'admin') {
    return false; // 관리자 계정은 삭제 불가
  }
  
  \$db = getDB();
  
  try {
    // 회원이 작성한 게시물도 함께 삭제
    \$stmt = \$db->prepare('DELETE FROM g5_board WHERE wr_name = ?');
    \$stmt->execute([\$username]);
    
    // 회원 삭제
    \$stmt = \$db->prepare('DELETE FROM g5_member WHERE mb_id = ?');
    return \$stmt->execute([\$username]);
  } catch (Exception \$e) {
    return false;
  }
}

function isAdmin() {
  return !empty(\$_SESSION['user']) && \$_SESSION['user'] === 'admin';
}

function requireAdmin() {
  if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
  }
}

// 스킨 설정
define('SKIN_DIR', './skin/default');
?>";
                
                file_put_contents('config.php', $config_content);
                
                $success = $lang['installation_success'];
                
        } catch (Exception $e) {
            $error = $lang['register_success'] . $e->getMessage();
        }
        }
    }
}

// 언어 파일 로드 (상단으로 이동됨)
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
    </style>
</head>
<body>
    <div class="install-container">
        <div class="language-selector">
            <h2>MicroBoard - <?php echo $lang['install_title']; ?></h2>
            <form method="post" style="display: inline;">
                <input type="hidden" name="language" id="selected_language" value="<?php echo htmlspecialchars($language); ?>">
                <div class="language-options">
                    <div class="language-option <?php echo $language === 'ja' ? 'selected' : ''; ?>" data-lang="ja">
                        🇯🇵 日本語
                    </div>
                    <div class="language-option <?php echo $language === 'ko' ? 'selected' : ''; ?>" data-lang="ko">
                        🇰🇷 한국어
                    </div>
                    <div class="language-option <?php echo $language === 'en' ? 'selected' : ''; ?>" data-lang="en">
                        🇺🇸 English
                    </div>
                    <div class="language-option <?php echo $language === 'zh' ? 'selected' : ''; ?>" data-lang="zh">
                        🇨🇳 中文
                    </div>
                </div>
            </form>
            
            <div style="margin-top: 15px;">
                <button type="button" class="btn-secondary" onclick="showLicense()" style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                    📄 <?php echo $lang['license']; ?>
                </button>
            </div>
        </div>
        
        <!-- 라이선스 모달 -->
        <div id="license-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
            <div style="background-color: white; margin: 5% auto; padding: 20px; border-radius: 8px; width: 80%; max-height: 80vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3>MicroBoard License</h3>
                    <button type="button" onclick="hideLicense()" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">×</button>
                </div>
                <div style="white-space: pre-line; font-family: monospace; font-size: 12px; line-height: 1.4; max-height: 60vh; overflow-y: auto;">
<?php
$license_file = 'LICENSE.txt';
if (file_exists($license_file)) {
    echo htmlspecialchars(file_get_contents($license_file));
} else {
    echo "MIT License

Copyright (c) 2025 YECHANHO

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the \"Software\"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED \"AS IS\", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.";
}
?>
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <button type="button" onclick="agreeToLicense()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                        <?php echo $lang['agree']; ?>
                    </button>
                    <button type="button" onclick="hideLicense()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
                        <?php echo $lang['close']; ?>
                    </button>
                </div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <a href="login.php" class="btn"><?php echo $lang['go_to_login']; ?></a>
        <?php else: ?>
            <form method="post" id="install-form">
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
                    <label for="db_name"><?php echo $lang['db_name']; ?> :</label>
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
                document.querySelector('input[name="language"]').value = lang;
                
                // 폼 자동 제출
                document.querySelector('.language-selector form').submit();
            });
        });
        
        // 라이선스 모달
        function showLicense() {
            document.getElementById('license-modal').style.display = 'block';
        }
        
        function hideLicense() {
            document.getElementById('license-modal').style.display = 'none';
        }
        
        // 라이선스 동의 처리
        function agreeToLicense() {
            document.getElementById('license_agreed').value = '1';
            document.getElementById('license-checkbox').checked = true;
            document.getElementById('install-btn').disabled = false;
            document.getElementById('license-modal').style.display = 'none';
        }
        
        // 체크박스 변경 감지
        document.getElementById('license-checkbox').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('license_agreed').value = '1';
                document.getElementById('install-btn').disabled = false;
            } else {
                document.getElementById('license_agreed').value = '0';
                document.getElementById('install-btn').disabled = true;
            }
        });
        
        // 폼 검증
        document.querySelector('form').addEventListener('submit', function(e) {
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
    </script>
</body>
</html>
