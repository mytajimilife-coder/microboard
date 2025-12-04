<?php
session_start();

// DB 설정 - 웹호스팅에서 수정하세요 (예: cPanel의 MySQL 정보)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gnuboard5');

// 버전 정보
define('MICROBOARD_VERSION', '1.0.0');

// 선택된 언어 설정
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ko', 'en', 'ja', 'zh'])) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    // 브라우저 언어 감지
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);
    if (in_array($browser_lang, ['ko', 'en', 'ja', 'zh'])) {
        $_SESSION['lang'] = $browser_lang;
    } else {
        $_SESSION['lang'] = 'en'; // 지원하지 않는 언어일 경우 영어 기본
    }
}

// 언어 파일 로드
$lang_path = __DIR__ . '/lang/';
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
if (file_exists($lang_path . $lang_code . '.php')) {
    $lang = require $lang_path . $lang_code . '.php';
} else {
    $lang = require $lang_path . 'en.php'; // Fallback
}

// DB 연결
function getDB() {
  static $pdo = null;
  if ($pdo === null) {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
      $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
      ]);
    } catch (PDOException $e) {
      global $lang;
      die($lang['db_conn_failed'] . ': ' . $e->getMessage());
    }
  }
  return $pdo;
}

// 테이블 생성 (install.php에서 호출)
function createTables() {
  $db = getDB();
  $db->exec("
    CREATE TABLE IF NOT EXISTS `mb1_board` (
      `wr_id` int(11) NOT NULL AUTO_INCREMENT,
      `wr_subject` varchar(255) NOT NULL,
      `wr_content` longtext NOT NULL,
      `wr_name` varchar(50) NOT NULL,
      `wr_datetime` datetime NOT NULL,
      `wr_hit` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`wr_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  $db->exec("
    CREATE TABLE IF NOT EXISTS `mb1_board_config` (
      `bo_table` varchar(100) NOT NULL,
      `bo_subject` varchar(255) NOT NULL,
      `bo_admin` varchar(50) NOT NULL DEFAULT 'admin',
      `bo_list_count` int(11) NOT NULL DEFAULT 15,
      `bo_use_comment` tinyint(1) NOT NULL DEFAULT 0,
      `bo_skin` varchar(50) NOT NULL DEFAULT 'default',
      PRIMARY KEY (`bo_table`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  $db->exec("
    CREATE TABLE IF NOT EXISTS `mb1_member` (
      `mb_id` varchar(50) NOT NULL,
      `mb_password` varchar(255) NOT NULL,
      `mb_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`mb_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  $db->exec("
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
  ");
  $db->exec("
    CREATE TABLE IF NOT EXISTS `mb1_comment` (
      `co_id` int(11) NOT NULL AUTO_INCREMENT,
      `wr_id` int(11) NOT NULL,
      `co_content` text NOT NULL,
      `co_name` varchar(50) NOT NULL,
      `co_datetime` datetime NOT NULL,
      PRIMARY KEY (`co_id`),
      KEY `wr_id` (`wr_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // 기본 사용자 추가 (admin/admin)
  $stmt = $db->prepare("INSERT IGNORE INTO `mb1_member` (`mb_id`, `mb_password`) VALUES (?, ?)");
  $stmt->execute(['admin', password_hash('admin', PASSWORD_DEFAULT)]);
}

// 게시물 함수들
function loadPosts($page = 1, $limit = 15, $stx = '', $sfl = '') {
  $db = getDB();
  $offset = ($page - 1) * $limit;
  $where = '1';
  $params = [];

  if ($stx) {
    if ($sfl === 'wr_subject') {
      $where .= ' AND wr_subject LIKE ?';
    } elseif ($sfl === 'wr_content') {
      $where .= ' AND wr_content LIKE ?';
    } elseif ($sfl === 'wr_name') {
      $where .= ' AND wr_name LIKE ?';
    } else {
      $where .= ' AND (wr_subject LIKE ? OR wr_content LIKE ?)';
      $params[] = "%$stx%";
    }
    $params[] = "%$stx%";
  }

  $sql = "SELECT * FROM mb1_board WHERE $where ORDER BY wr_id DESC LIMIT $limit OFFSET $offset";
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll();
}

function getTotalPostCount($stx = '', $sfl = '') {
  $db = getDB();
  $where = '1';
  $params = [];

  if ($stx) {
    if ($sfl === 'wr_subject') {
      $where .= ' AND wr_subject LIKE ?';
    } elseif ($sfl === 'wr_content') {
      $where .= ' AND wr_content LIKE ?';
    } elseif ($sfl === 'wr_name') {
      $where .= ' AND wr_name LIKE ?';
    } else {
      $where .= ' AND (wr_subject LIKE ? OR wr_content LIKE ?)';
      $params[] = "%$stx%";
    }
    $params[] = "%$stx%";
  }

  $stmt = $db->prepare("SELECT COUNT(*) FROM mb1_board WHERE $where");
  $stmt->execute($params);
  return $stmt->fetchColumn();
}

function insertPost($data) {
  $db = getDB();
  $sql = 'INSERT INTO mb1_board (wr_subject, wr_content, wr_name, wr_datetime, wr_hit) VALUES (?, ?, ?, NOW(), 0)';
  $stmt = $db->prepare($sql);
  $stmt->execute([$data['title'], $data['content'], $data['writer']]);
  return $db->lastInsertId();
}

function updatePost($id, $data) {
  $db = getDB();
  $sql = 'UPDATE mb1_board SET wr_subject = ?, wr_content = ?, wr_name = ?, wr_datetime = NOW() WHERE wr_id = ?';
  $stmt = $db->prepare($sql);
  $stmt->execute([$data['title'], $data['content'], $data['writer'], $id]);
}

function getPost($id) {
  $db = getDB();
  $stmt = $db->prepare('SELECT * FROM mb1_board WHERE wr_id = ?');
  $stmt->execute([$id]);
  return $stmt->fetch() ?: ['wr_subject' => '', 'wr_content' => '', 'wr_name' => '', 'wr_datetime' => '', 'wr_hit' => 0];
}

function incrementView($id) {
  $db = getDB();
  $stmt = $db->prepare('UPDATE mb1_board SET wr_hit = wr_hit + 1 WHERE wr_id = ?');
  $stmt->execute([$id]);
}

function deletePost($id) {
  $db = getDB();
  
  // 포인트 차감 로직 (게시글 작성자 확인 필요)
  $post = getPost($id);
  if ($post['wr_name']) {
      $config = get_config();
      if ($config['cf_use_point'] && $config['cf_write_point'] != 0) {
          // 이미 지급된 포인트만큼 차감 (음수로 지급)
          insert_point(
              $post['wr_name'], 
              $config['cf_write_point'] * -1, 
              '글삭제', 
              'mb1_board', 
              $id, 
              'delete'
          );
      }
  }

  // 댓글 삭제
  $stmt = $db->prepare('DELETE FROM mb1_comment WHERE wr_id = ?');
  $stmt->execute([$id]);
  
  // 파일 삭제 (실제 파일도 삭제해야 함 - 별도 처리 필요)
  $files = getPostFiles($id);
  foreach ($files as $file) {
      @unlink('data/file/' . $file['bf_file']);
  }
  $stmt = $db->prepare('DELETE FROM mb1_board_file WHERE wr_id = ?');
  $stmt->execute([$id]);

  $stmt = $db->prepare('DELETE FROM mb1_board WHERE wr_id = ?');
  $stmt->execute([$id]);
}

// 파일 관련 함수
function insertFile($wr_id, $file) {
    $upload_dir = 'data/file/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = basename($file['name']);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $ext;
    $dest_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO mb1_board_file (wr_id, bf_source, bf_file, bf_filesize, bf_datetime) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$wr_id, $filename, $new_filename, $file['size']]);
        return true;
    }
    return false;
}

function getPostFiles($wr_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM mb1_board_file WHERE wr_id = ?");
    $stmt->execute([$wr_id]);
    return $stmt->fetchAll();
}

function getFile($bf_no) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM mb1_board_file WHERE bf_no = ?");
    $stmt->execute([$bf_no]);
    return $stmt->fetch();
}

// 댓글 관련 함수
function getComments($wr_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM mb1_comment WHERE wr_id = ? ORDER BY co_id ASC");
    $stmt->execute([$wr_id]);
    return $stmt->fetchAll();
}

function insertComment($wr_id, $name, $content) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO mb1_comment (wr_id, co_name, co_content, co_datetime) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$wr_id, $name, $content]);
}

function deleteComment($co_id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM mb1_comment WHERE co_id = ?");
    $stmt->execute([$co_id]);
}

// 로그인 체크
function isLoggedIn() {
  if (!isset($_SESSION['user'])) {
    return false;
  }
  
  // 세션 타임아웃 체크 (30분)
  if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
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

function verifyUser($id, $pass) {
  // 입력값 검증
  $id = trim($id);
  $pass = trim($pass);
  
  // SQL 인젝션 방지 및 입력값 길이 제한
  if (empty($id) || empty($pass) || strlen($id) > 50 || strlen($pass) > 255) {
    return false;
  }
  
  $db = getDB();
  $stmt = $db->prepare('SELECT mb_password FROM mb1_member WHERE mb_id = ?');
  $stmt->execute([$id]);
  $user = $stmt->fetch();
  return $user && password_verify($pass, $user['mb_password']);
}

// 회원가입 함수
function registerUser($username, $password) {
  // 입력값 검증
  $username = trim($username);
  $password = trim($password);
  
  if (empty($username) || empty($password) || strlen($username) > 20 || strlen($password) > 255) {
    return false;
  }
  
  // 비밀번호 해시
  $password_hash = password_hash($password, PASSWORD_DEFAULT);
  
  $db = getDB();
  try {
    $stmt = $db->prepare("INSERT INTO mb1_member (mb_id, mb_password) VALUES (?, ?)");
    return $stmt->execute([$username, $password_hash]);
  } catch (Exception $e) {
    // 중복 키 등 오류 처리
    return false;
  }
}

// 아이디 중복 체크 함수
function isUsernameExists($username) {
  $username = trim($username);
  
  if (empty($username)) {
    return false;
  }
  
  $db = getDB();
  $stmt = $db->prepare('SELECT COUNT(*) as count FROM mb1_member WHERE mb_id = ?');
  $stmt->execute([$username]);
  $result = $stmt->fetch();
  
  return $result['count'] > 0;
}

// 사용자 게시물 조회 함수
function getUserPosts($username) {
  $username = trim($username);
  
  if (empty($username)) {
    return [];
  }
  
  $db = getDB();
  $stmt = $db->prepare('SELECT * FROM mb1_board WHERE wr_name = ? ORDER BY wr_id DESC');
  $stmt->execute([$username]);
  
  return $stmt->fetchAll();
}

// 사용자 댓글 조회 함수 (댓글 테이블이 없는 경우를 대비한 기본 구조)
function getUserComments($username) {
  $username = trim($username);
  
  if (empty($username)) {
    return [];
  }
  
  // 댓글 기능이 구현되지 않은 경우 빈 배열 반환
  // 댓글 기능 구현 시 여기에 실제 쿼리 추가
  return [];
}

// 모든 회원 조회 함수
function getAllUsers() {
  $db = getDB();
  $stmt = $db->prepare('SELECT mb_id, mb_datetime FROM mb1_member ORDER BY mb_datetime DESC, mb_id ASC');
  $stmt->execute();
  
  return $stmt->fetchAll();
}

// 회원 탈퇴 함수
function deleteUser($username) {
  $username = trim($username);
  
  if (empty($username) || $username === 'admin') {
    return false; // 관리자 계정은 삭제 불가
  }
  
  $db = getDB();
  
  try {
    // 회원이 작성한 게시물도 함께 삭제
    $stmt = $db->prepare('DELETE FROM mb1_board WHERE wr_name = ?');
    $stmt->execute([$username]);
    
    // 회원 삭제
    $stmt = $db->prepare('DELETE FROM mb1_member WHERE mb_id = ?');
    return $stmt->execute([$username]);
  } catch (Exception $e) {
    return false;
  }
}

function isAdmin() {
  return !empty($_SESSION['user']) && $_SESSION['user'] === 'admin';
}

function requireAdmin() {
  if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
  }
}

// 설정 관련 함수
function get_config() {
    $db = getDB();
    // mb1_config 테이블이 존재하는지 확인
    try {
        $stmt = $db->query("SELECT 1 FROM mb1_config LIMIT 1");
    } catch (Exception $e) {
        return ['cf_use_point' => 0, 'cf_write_point' => 0];
    }

    $stmt = $db->query("SELECT * FROM mb1_config LIMIT 1");
    $config = $stmt->fetch();
    if (!$config) {
        return ['cf_use_point' => 0, 'cf_write_point' => 0];
    }
    return $config;
}

function update_config($data) {
    $db = getDB();
    // 기존 설정이 있는지 확인
    $stmt = $db->query("SELECT COUNT(*) FROM mb1_config");
    if ($stmt->fetchColumn() > 0) {
        $sql = "UPDATE mb1_config SET cf_use_point = ?, cf_write_point = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$data['cf_use_point'], $data['cf_write_point']]);
    } else {
        $sql = "INSERT INTO mb1_config (cf_use_point, cf_write_point) VALUES (?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$data['cf_use_point'], $data['cf_write_point']]);
    }
}

// 포인트 관련 함수
function insert_point($mb_id, $point, $content = '', $rel_table = '', $rel_id = '', $rel_action = '') {
    if ($point == 0) return;

    $db = getDB();
    
    // 포인트 사용 여부 확인
    $config = get_config();
    if (!$config['cf_use_point']) return;

    // 포인트 내역 추가
    $sql = "INSERT INTO mb1_point (mb_id, po_datetime, po_content, po_point, po_rel_table, po_rel_id, po_rel_action)
            VALUES (?, NOW(), ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$mb_id, $content, $point, $rel_table, $rel_id, $rel_action]);

    // 회원 포인트 업데이트
    $sql = "UPDATE mb1_member SET mb_point = mb_point + ? WHERE mb_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$point, $mb_id]);
}

// 회원 차단 함수
function blockMember($mb_id, $reason = '') {
    if (empty($mb_id) || $mb_id === 'admin') {
        return false; // 관리자는 차단 불가
    }
    
    $db = getDB();
    try {
        $stmt = $db->prepare("UPDATE mb1_member SET mb_blocked = 1, mb_blocked_reason = ? WHERE mb_id = ?");
        return $stmt->execute([$reason, $mb_id]);
    } catch (Exception $e) {
        return false;
    }
}

// 회원 차단 해제 함수
function unblockMember($mb_id) {
    if (empty($mb_id)) {
        return false;
    }
    
    $db = getDB();
    try {
        $stmt = $db->prepare("UPDATE mb1_member SET mb_blocked = 0, mb_blocked_reason = NULL WHERE mb_id = ?");
        return $stmt->execute([$mb_id]);
    } catch (Exception $e) {
        return false;
    }
}

// 회원 등급 변경 함수
function updateMemberLevel($mb_id, $level) {
    if (empty($mb_id) || $mb_id === 'admin') {
        return false; // 관리자 등급은 변경 불가
    }
    
    // 등급은 1-10 사이
    $level = max(1, min(10, (int)$level));
    
    $db = getDB();
    try {
        $stmt = $db->prepare("UPDATE mb1_member SET mb_level = ? WHERE mb_id = ?");
        return $stmt->execute([$level, $mb_id]);
    } catch (Exception $e) {
        return false;
    }
}

// 회원 정보 조회 함수
function getMemberInfo($mb_id) {
    if (empty($mb_id)) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM mb1_member WHERE mb_id = ?");
    $stmt->execute([$mb_id]);
    return $stmt->fetch();
}

// 회원 차단 여부 확인
function isMemberBlocked($mb_id) {
    $member = getMemberInfo($mb_id);
    return $member && $member['mb_blocked'] == 1;
}

// 회원 탈퇴 함수 (본인만 가능)
function withdrawMember($mb_id, $password) {
    if (empty($mb_id) || $mb_id === 'admin') {
        return false; // 관리자는 탈퇴 불가
    }
    
    // 비밀번호 확인
    if (!verifyUser($mb_id, $password)) {
        return false;
    }
    
    $db = getDB();
    try {
        // 탈퇴일 기록 (실제 삭제하지 않고 표시만)
        $stmt = $db->prepare("UPDATE mb1_member SET mb_leave_date = NOW(), mb_blocked = 1, mb_blocked_reason = '회원 탈퇴' WHERE mb_id = ?");
        return $stmt->execute([$mb_id]);
    } catch (Exception $e) {
        return false;
    }
}

// 로그인 시 차단 여부 확인 (기존 verifyUser 함수 수정 필요)
function verifyUserWithBlock($id, $pass) {
    // 기본 인증
    if (!verifyUser($id, $pass)) {
        return ['success' => false, 'message' => 'login_failed'];
    }
    
    // 차단 여부 확인
    $member = getMemberInfo($id);
    if ($member && $member['mb_blocked'] == 1) {
        $reason = $member['mb_blocked_reason'] ?? '관리자에 의해 차단되었습니다.';
        return ['success' => false, 'message' => 'account_blocked', 'reason' => $reason];
    }
    
    // 탈퇴 여부 확인
    if ($member && $member['mb_leave_date'] !== null) {
        return ['success' => false, 'message' => 'account_withdrawn'];
    }
    
    return ['success' => true];
}

// 정책 페이지 관련 함수
function getPolicy($policy_type) {
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT * FROM mb1_policy WHERE policy_type = ?");
        $stmt->execute([$policy_type]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

function updatePolicy($policy_type, $title, $content) {
    $db = getDB();
    try {
        // 정책이 존재하는지 확인
        $stmt = $db->prepare("SELECT COUNT(*) FROM mb1_policy WHERE policy_type = ?");
        $stmt->execute([$policy_type]);
        
        if ($stmt->fetchColumn() > 0) {
            // 업데이트
            $stmt = $db->prepare("UPDATE mb1_policy SET policy_title = ?, policy_content = ?, updated_at = NOW() WHERE policy_type = ?");
            return $stmt->execute([$title, $content, $policy_type]);
        } else {
            // 삽입
            $stmt = $db->prepare("INSERT INTO mb1_policy (policy_type, policy_title, policy_content) VALUES (?, ?, ?)");
            return $stmt->execute([$policy_type, $title, $content]);
        }
    } catch (Exception $e) {
        return false;
    }
}

// 스킨 설정
define('SKIN_DIR', './skin/default');
?>
