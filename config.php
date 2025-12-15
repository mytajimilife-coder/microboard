<?php
session_start();

// DB 설정 - 웹호스팅에서 수정하세요 (예: cPanel의 MySQL 정보)
// DB 설정 - config_db.php 파일에서 로드
$db_config_file = __DIR__ . '/config_db.php';
if (file_exists($db_config_file)) {
    require_once $db_config_file;
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'microboard');
}

// 버전 정보
define('MICROBOARD_VERSION', '1.0.0');

// 선택된 언어 설정
if (isset($_REQUEST['language']) && in_array($_REQUEST['language'], ['ko', 'en', 'ja', 'zh'])) {
    $_SESSION['lang'] = $_REQUEST['language'];
} elseif (isset($_GET['lang']) && in_array($_GET['lang'], ['ko', 'en', 'ja', 'zh'])) {
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
      // 데이터베이스 연결 실패 시 설치 페이지로 리다이렉트
      // install.php 페이지에서는 리다이렉트하지 않도록 체크
      $current_page = basename($_SERVER['PHP_SELF']);
      if ($current_page !== 'install.php') {
        if (file_exists('install.php')) {
            header('Location: install.php');
            exit;
        }
        die("<h1>DB Connection Error</h1><p>" . $e->getMessage() . "</p><p>Please check your database configuration. If the site is not installed, please upload install.php.</p>");
      }
      // install.php에서 호출된 경우 예외 발생
      throw $e;
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
      `mb_nickname` varchar(100) DEFAULT NULL,
      `mb_email` varchar(255) DEFAULT NULL,
      `oauth_provider` varchar(50) DEFAULT NULL,
      `mb_blocked` tinyint(1) NOT NULL DEFAULT 0,
      `mb_blocked_reason` text DEFAULT NULL,
      `mb_leave_date` datetime DEFAULT NULL,
      `mb_level` int(11) NOT NULL DEFAULT 1,
      `mb_point` int(11) NOT NULL DEFAULT 0,
      `mb_2fa_enabled` tinyint(1) NOT NULL DEFAULT 0,
      `mb_2fa_secret` varchar(255) DEFAULT NULL,
      `mb_2fa_backup_codes` text DEFAULT NULL,
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
function loadPosts($bo_table, $page = 1, $limit = 15, $stx = '', $sfl = '') {
  $db = getDB();
  $write_table = 'mb1_write_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
  
  // 테이블 존재 여부 체크는 생략 (성능) 또는 try-catch
  
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

  try {
      $sql = "SELECT * FROM {$write_table} WHERE $where ORDER BY wr_id DESC LIMIT $limit OFFSET $offset";
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      return $stmt->fetchAll();
  } catch (PDOException $e) {
      return [];
  }
}

function getTotalPostCount($bo_table, $stx = '', $sfl = '') {
  $db = getDB();
  $write_table = 'mb1_write_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
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

  try {
      $stmt = $db->prepare("SELECT COUNT(*) FROM {$write_table} WHERE $where");
      $stmt->execute($params);
      return $stmt->fetchColumn();
  } catch (PDOException $e) {
      return 0;
  }
}

function insertPost($bo_table, $data) {
  $db = getDB();
  $write_table = 'mb1_write_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
  $sql = "INSERT INTO {$write_table} (wr_subject, wr_content, wr_name, wr_datetime, wr_hit) VALUES (?, ?, ?, NOW(), 0)";
  $stmt = $db->prepare($sql);
  $stmt->execute([$data['title'], $data['content'], $data['writer']]);
  return $db->lastInsertId();
}

function updatePost($bo_table, $id, $data) {
  $db = getDB();
  $write_table = 'mb1_write_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
  $sql = "UPDATE {$write_table} SET wr_subject = ?, wr_content = ?, wr_name = ?, wr_datetime = NOW() WHERE wr_id = ?";
  $stmt = $db->prepare($sql);
  $stmt->execute([$data['title'], $data['content'], $data['writer'], $id]);
}

function getPost($bo_table, $id) {
  $db = getDB();
  $write_table = 'mb1_write_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
  // 댓글 수 카운트 추가 (없으면 0)
  try {
      $stmt = $db->prepare("SELECT * FROM {$write_table} WHERE wr_id = ?");
      $stmt->execute([$id]);
      $post = $stmt->fetch();
      if (!$post) return ['wr_subject' => '', 'wr_content' => '', 'wr_name' => '', 'wr_datetime' => '', 'wr_hit' => 0, 'wr_id' => 0];
      
      // 댓글 수 조회 (optional)
      /*
      $comment_table = "mb1_comment_" . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
      $stmt = $db->prepare("SELECT COUNT(*) FROM {$comment_table} WHERE wr_id = ?");
      $stmt->execute([$id]);
      $post['wr_comment'] = $stmt->fetchColumn();
      */
      
      return $post;
  } catch (PDOException $e) {
      return ['wr_subject' => '', 'wr_content' => '', 'wr_name' => '', 'wr_datetime' => '', 'wr_hit' => 0, 'wr_id' => 0];
  }
}

function incrementView($bo_table, $id) {
  $db = getDB();
  $write_table = 'mb1_write_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
  try {
    $stmt = $db->prepare("UPDATE {$write_table} SET wr_hit = wr_hit + 1 WHERE wr_id = ?");
    $stmt->execute([$id]);
  } catch (Exception $e) {}
}

function deletePost($bo_table, $id) {
  $db = getDB();
  $safe_bo_table = preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
  $write_table = 'mb1_write_' . $safe_bo_table;
  $comment_table = 'mb1_comment_' . $safe_bo_table;
  $file_table = 'mb1_board_file_' . $safe_bo_table;
  
  // 포인트 차감 로직 (게시글 작성자 확인 필요)
  $post = getPost($bo_table, $id);
  if ($post['wr_name']) {
      $config = get_config();
      if ($config['cf_use_point'] && $config['cf_write_point'] != 0) {
          // 이미 지급된 포인트만큼 차감 (음수로 지급)
          insert_point(
              $post['wr_name'], 
              $config['cf_write_point'] * -1, 
              '글삭제', 
              $bo_table, 
              $id, 
              'delete'
          );
      }
  }

  // 댓글 삭제
  try {
      $stmt = $db->prepare("DELETE FROM {$comment_table} WHERE wr_id = ?");
      $stmt->execute([$id]);
  } catch(Exception $e) {}
  
  // 파일 삭제 (실제 파일도 삭제해야 함)
  $files = getPostFiles($bo_table, $id);
  foreach ($files as $file) {
      @unlink('data/file/' . $file['bf_file']);
  }
  try {
      $stmt = $db->prepare("DELETE FROM {$file_table} WHERE wr_id = ?");
      $stmt->execute([$id]);
  } catch(Exception $e) {}

  // 본문 삭제
  try {
      $stmt = $db->prepare("DELETE FROM {$write_table} WHERE wr_id = ?");
      $stmt->execute([$id]);
  } catch(Exception $e) {}
}

// 파일 관련 함수
function insertFile($bo_table, $wr_id, $file) {
    $upload_dir = 'data/file/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_table = 'mb1_board_file_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);

    $filename = basename($file['name']);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $ext;
    $dest_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO {$file_table} (wr_id, bf_source, bf_file, bf_filesize, bf_datetime) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$wr_id, $filename, $new_filename, $file['size']]);
        return true;
    }
    return false;
}

function getPostFiles($bo_table, $wr_id) {
    $db = getDB();
    $file_table = 'mb1_board_file_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
    try {
        $stmt = $db->prepare("SELECT * FROM {$file_table} WHERE wr_id = ?");
        $stmt->execute([$wr_id]);
        return $stmt->fetchAll();
    } catch(Exception $e) { return []; }
}

function getFile($bo_table, $bf_no) {
    $db = getDB();
    $file_table = 'mb1_board_file_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
    try {
        $stmt = $db->prepare("SELECT * FROM {$file_table} WHERE bf_no = ?");
        $stmt->execute([$bf_no]);
        return $stmt->fetch();
    } catch(Exception $e) { return null; }
}

// 댓글 관련 함수
function getComments($bo_table, $wr_id) {
    $db = getDB();
    $comment_table = 'mb1_comment_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
    try {
        $stmt = $db->prepare("SELECT * FROM {$comment_table} WHERE wr_id = ? ORDER BY co_id ASC");
        $stmt->execute([$wr_id]);
        return $stmt->fetchAll();
    } catch(Exception $e) { return []; }
}

function insertComment($bo_table, $wr_id, $name, $content) {
    $db = getDB();
    $comment_table = 'mb1_comment_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
    $stmt = $db->prepare("INSERT INTO {$comment_table} (wr_id, co_name, co_content, co_datetime) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$wr_id, $name, $content]);
}

function deleteComment($bo_table, $co_id) {
    $db = getDB();
    $comment_table = 'mb1_comment_' . preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
    $stmt = $db->prepare("DELETE FROM {$comment_table} WHERE co_id = ?");
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
function registerUser($username, $password, $nickname = null, $email = null) {
  // 입력값 검증
  $username = trim($username);
  $password = trim($password);
  $nickname = trim($nickname);
  $email = trim($email);

  if (empty($username) || empty($password) || strlen($username) > 20 || strlen($password) > 255) {
    return false;
  }

  // 비밀번호 해시
  $password_hash = password_hash($password, PASSWORD_DEFAULT);

  $db = getDB();
  try {
    $stmt = $db->prepare("INSERT INTO mb1_member (mb_id, mb_password, mb_nickname, mb_email) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, $password_hash, $nickname, $email]);
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

// 사용자 게시물 조회 함수 (모든 게시판 통합 검색 - 비용이 큼)
function getUserPosts($username) {
  $username = trim($username);
  if (empty($username)) return [];
  
  $db = getDB();
  
  // 모든 게시판 테이블 가져오기
  try {
      $boards = $db->query("SELECT bo_table, bo_subject FROM mb1_board_config")->fetchAll();
      
      $all_posts = [];
      foreach ($boards as $board) {
          $bo_table = $board['bo_table'];
          $write_table = "mb1_write_" . $bo_table;
          
          try {
              // 각 게시판에서 해당 사용자의 글 조회
              $stmt = $db->prepare("SELECT *, ? as bo_table, ? as bo_subject FROM {$write_table} WHERE wr_name = ? ORDER BY wr_id DESC LIMIT 5");
              $stmt->execute([$bo_table, $board['bo_subject'], $username]);
              $posts = $stmt->fetchAll();
              $all_posts = array_merge($all_posts, $posts);
          } catch(Exception $e) { continue; }
      }
      
      // 날짜순 정렬 (최신순)
      usort($all_posts, function($a, $b) {
          return strtotime($b['wr_datetime']) - strtotime($a['wr_datetime']);
      });
      
      return array_slice($all_posts, 0, 20); // 최근 20개만 반환
  } catch(Exception $e) {
      return [];
  }
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
    // 회원이 작성한 게시물도 함께 삭제 (모든 게시판 순회)
    $boards = $db->query("SELECT bo_table FROM mb1_board_config")->fetchAll();
    foreach ($boards as $board) {
        $write_table = "mb1_write_" . $board['bo_table'];
        try {
            $stmt = $db->prepare("DELETE FROM {$write_table} WHERE wr_name = ?");
            $stmt->execute([$username]);
        } catch(Exception $e) {}
    }
    
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

// XSS 방지 함수 (HTML 허용 시 필수)
function clean_xss($content) {
    if (empty($content)) return '';

    // 1. script 스타일 등 위험 태그 제거 (내용 포함)
    $content = preg_replace('/<(script|style|iframe|object|embed|form|applet|meta|link)\b[^>]*>.*?<\/\1>/is', "", $content);
    // 닫는 태그가 없는 경우도 처리
    $content = preg_replace('/<(script|style|iframe|object|embed|form|applet|meta|link)\b[^>]*>/i', "", $content);

    // 2. 이벤트 핸들러 제거 (on... 속성)
    // 따옴표로 감싸진 경우
    $content = preg_replace('/\s(on[a-z]+)\s*=\s*([\'"]).*?\2/i', "", $content);
    // 따옴표 없는 경우
    $content = preg_replace('/\s(on[a-z]+)\s*=\s*[^ >]+/i', "", $content);

    // 3. javascript: 프로토콜 제거
    // href, src, action 속성 등
    $content = preg_replace('/\s(href|src|action)\s*=\s*([\'"])\s*javascript:[^>]*?\2/i', ' $1="#"', $content);
    $content = preg_replace('/\s(href|src|action)\s*=\s*javascript:[^ >]+/i', ' $1="#"', $content);

    return $content;
}

// 2FA 관련 함수
function generateTwoFactorSecret() {
    // 32자 길이의 랜덤 시크릿 키 생성 (base32 인코딩)
    $random_bytes = random_bytes(32);
    $base32 = base32_encode($random_bytes);
    return $base32;
}

function base32_encode($data) {
    $base32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32 = '';

    // 바이트를 5비트 청크로 변환
    $bytes = str_split($data);
    $buffer = 0;
    $buffer_size = 0;

    foreach ($bytes as $byte) {
        $buffer = ($buffer << 8) | ord($byte);
        $buffer_size += 8;

        while ($buffer_size >= 5) {
            $buffer_size -= 5;
            $index = ($buffer >> $buffer_size) & 0x1F;
            $base32 .= $base32_chars[$index];
        }
    }

    // 남은 비트 처리
    if ($buffer_size > 0) {
        $buffer <<= (5 - $buffer_size);
        $index = $buffer & 0x1F;
        $base32 .= $base32_chars[$index];
    }

    // 패딩 추가 (RFC 4648)
    $padding = (8 - (strlen($base32) % 8)) % 8;
    $base32 .= str_repeat('=', $padding);

    return $base32;
}

function generateTwoFactorBackupCodes($count = 10) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $codes[] = strtoupper(substr(hash('sha256', random_bytes(32)), 0, 8));
    }
    return implode("\n", $codes);
}

function enableTwoFactorAuth($username) {
    $db = getDB();

    try {
        // 시크릿 키 생성
        $secret = generateTwoFactorSecret();
        $backup_codes = generateTwoFactorBackupCodes();

        // 데이터베이스 업데이트
        $stmt = $db->prepare("UPDATE mb1_member SET mb_2fa_enabled = 1, mb_2fa_secret = ?, mb_2fa_backup_codes = ? WHERE mb_id = ?");
        $stmt->execute([$secret, $backup_codes, $username]);

        return ['success' => true, 'secret' => $secret, 'backup_codes' => $backup_codes];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to enable 2FA: ' . $e->getMessage()];
    }
}

function disableTwoFactorAuth($username) {
    $db = getDB();

    try {
        // 2FA 비활성화
        $stmt = $db->prepare("UPDATE mb1_member SET mb_2fa_enabled = 0, mb_2fa_secret = NULL, mb_2fa_backup_codes = NULL WHERE mb_id = ?");
        $stmt->execute([$username]);

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to disable 2FA: ' . $e->getMessage()];
    }
}

function verifyTwoFactorCode($username, $code) {
    $db = getDB();

    try {
        // 사용자 정보 가져오기
        $stmt = $db->prepare("SELECT mb_2fa_secret FROM mb1_member WHERE mb_id = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || empty($user['mb_2fa_secret'])) {
            return ['success' => false, 'message' => '2FA is not enabled for this user'];
        }

        // 코드 검증 (간단한 구현 - 실제로는 TOTP 알고리즘 사용)
        // 여기서는 간단히 6자리 숫자 코드를 검증하는 것으로 구현
        if (!preg_match('/^\d{6}$/', $code)) {
            return ['success' => false, 'message' => 'Invalid code format'];
        }

        // 실제 구현에서는 TOTP 알고리즘을 사용해야 함
        // 여기서는 간단히 성공으로 처리 (실제 구현 시에는 라이브러리 사용 권장)
        return ['success' => true];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to verify 2FA code: ' . $e->getMessage()];
    }
}

function isTwoFactorEnabled($username) {
    $db = getDB();

    try {
        $stmt = $db->prepare("SELECT mb_2fa_enabled FROM mb1_member WHERE mb_id = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        return $user && $user['mb_2fa_enabled'] == 1;
    } catch (Exception $e) {
        return false;
    }
}

// 메일 발송 함수
function sendEmail($to, $subject, $message) {
    // 이메일 설정 가져오기
    $db = getDB();
    $email_settings = [];

    try {
        $stmt = $db->query("SELECT * FROM mb1_email_settings LIMIT 1");
        $email_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // 설정 테이블이 없는 경우 기본 설정 사용
        $email_settings = [
            'smtp_host' => 'localhost',
            'smtp_port' => 25,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'none',
            'sender_email' => 'noreply@example.com',
            'sender_name' => 'MicroBoard'
        ];
    }

    // 기본값 설정
    $email_settings = array_merge([
        'smtp_host' => 'localhost',
        'smtp_port' => 25,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'none',
        'sender_email' => 'noreply@example.com',
        'sender_name' => 'MicroBoard'
    ], $email_settings);

    // PHPMailer가 설치되어 있는지 확인
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // PHPMailer가 없는 경우 기본 mail() 함수 사용
        $headers = "From: " . $email_settings['sender_name'] . " <" . $email_settings['sender_email'] . ">\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        return mail($to, $subject, $message, $headers);
    }

    // PHPMailer 사용
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // 서버 설정
        $mail->isSMTP();
        $mail->Host = $email_settings['smtp_host'];
        $mail->Port = $email_settings['smtp_port'];

        // 암호화 설정
        if ($email_settings['smtp_encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($email_settings['smtp_encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        }

        // 인증 설정
        if (!empty($email_settings['smtp_username'])) {
            $mail->SMTPAuth = true;
            $mail->Username = $email_settings['smtp_username'];
            $mail->Password = $email_settings['smtp_password'];
        }

        // 보안 설정
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($email_settings['sender_email'], $email_settings['sender_name']);
        $mail->addAddress($to);

        // 내용 설정
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

// 2FA 설정 완료 메일 발송
function sendTwoFactorSetupEmail($username, $email) {
    $subject = $lang['2fa_setup_email_subject'] ?? 'Two-Factor Authentication Setup Complete';
    $message = sprintf(
        $lang['2fa_setup_email_body'] ?? 'Hello %s,<br><br>Two-factor authentication has been successfully enabled for your account. Your account is now more secure.<br><br>If you did not enable this feature, please contact our support team immediately.<br><br>Thank you,<br>The MicroBoard Team',
        htmlspecialchars($username)
    );

    return sendEmail($email, $subject, $message);
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

// 훅 시스템 (Plugin Support)
global $mb_hooks;
$mb_hooks = [];

function add_event($event_name, $callback, $priority = 10) {
    global $mb_hooks;
    $mb_hooks[$event_name][] = ['callback' => $callback, 'priority' => $priority];
    
    // 우선순위 정렬
    usort($mb_hooks[$event_name], function($a, $b) {
        return $a['priority'] <=> $b['priority'];
    });
}

function run_event($event_name, ...$args) {
    global $mb_hooks;
    if (isset($mb_hooks[$event_name])) {
        foreach ($mb_hooks[$event_name] as $hook) {
            if (is_callable($hook['callback'])) {
                call_user_func_array($hook['callback'], $args);
            }
        }
    }
}

// 스킨 설정
define('SKIN_DIR', './skin/default');

// start 폴더의 모든 PHP 파일 자동 로드 (그누보드 extend 기능과 유사)
$start_dir = __DIR__ . '/start';
if (is_dir($start_dir)) {
    $files = glob($start_dir . '/*.php');
    if ($files) {
        foreach ($files as $file) {
            include_once $file;
        }
    }
}

// 데이터베이스 연결 체크 (install.php, update_db*.php가 아닌 경우에만)
$current_page = basename($_SERVER['PHP_SELF']);
$skip_db_check = (
    $current_page === 'install.php' || 
    strpos($current_page, 'update_db') === 0 ||
    defined('SKIP_DB_CHECK')
);

if (!$skip_db_check) {
    try {
        // 데이터베이스 연결 테스트
        $test_db = getDB();
        // 필수 테이블 존재 여부 확인
        $stmt = $test_db->query("SHOW TABLES LIKE 'mb1_member'");
        if ($stmt->rowCount() === 0) {
            // 테이블이 없으면 설치 페이지로
            header('Location: install.php');
            exit;
        }
    } catch (Exception $e) {
        // 연결 실패 시 설치 페이지로
        header('Location: install.php');
        exit;
    }
}
?>
