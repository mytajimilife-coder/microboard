<?php
session_start();

// DB 설정 - 웹호스팅에서 수정하세요 (예: cPanel의 MySQL 정보)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gnuboard5');

// 버전 정보
define('MICROBOARD_VERSION', '1.0.0');

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
      die('DB 연결 실패: ' . $e->getMessage());
    }
  }
  return $pdo;
}

// 테이블 생성 (install.php에서 호출)
function createTables() {
  $db = getDB();
  $db->exec("
    CREATE TABLE IF NOT EXISTS `g5_board` (
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
    CREATE TABLE IF NOT EXISTS `g5_board_config` (
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
    CREATE TABLE IF NOT EXISTS `g5_member` (
      `mb_id` varchar(50) NOT NULL,
      `mb_password` varchar(255) NOT NULL,
      PRIMARY KEY (`mb_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  // 기본 사용자 추가 (admin/admin)
  $stmt = $db->prepare("INSERT IGNORE INTO `g5_member` (`mb_id`, `mb_password`) VALUES (?, ?)");
  $stmt->execute(['admin', password_hash('admin', PASSWORD_DEFAULT)]);
}

// 게시물 함수들
function loadPosts() {
  $db = getDB();
  $stmt = $db->query('SELECT * FROM g5_board ORDER BY wr_id DESC');
  return $stmt->fetchAll();
}

function insertPost($data) {
  $db = getDB();
  $sql = 'INSERT INTO g5_board (wr_subject, wr_content, wr_name, wr_datetime, wr_hit) VALUES (?, ?, ?, NOW(), 0)';
  $stmt = $db->prepare($sql);
  $stmt->execute([$data['title'], $data['content'], $data['writer']]);
  return $db->lastInsertId();
}

function updatePost($id, $data) {
  $db = getDB();
  $sql = 'UPDATE g5_board SET wr_subject = ?, wr_content = ?, wr_name = ?, wr_datetime = NOW() WHERE wr_id = ?';
  $stmt = $db->prepare($sql);
  $stmt->execute([$data['title'], $data['content'], $data['writer'], $id]);
}

function getPost($id) {
  $db = getDB();
  $stmt = $db->prepare('SELECT * FROM g5_board WHERE wr_id = ?');
  $stmt->execute([$id]);
  return $stmt->fetch() ?: ['wr_subject' => '', 'wr_content' => '', 'wr_name' => '', 'wr_datetime' => '', 'wr_hit' => 0];
}

function incrementView($id) {
  $db = getDB();
  $stmt = $db->prepare('UPDATE g5_board SET wr_hit = wr_hit + 1 WHERE wr_id = ?');
  $stmt->execute([$id]);
}

function deletePost($id) {
  $db = getDB();
  $stmt = $db->prepare('DELETE FROM g5_board WHERE wr_id = ?');
  $stmt->execute([$id]);
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
  $stmt = $db->prepare('SELECT mb_password FROM g5_member WHERE mb_id = ?');
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
    $stmt = $db->prepare("INSERT INTO g5_member (mb_id, mb_password) VALUES (?, ?)");
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
  $stmt = $db->prepare('SELECT COUNT(*) as count FROM g5_member WHERE mb_id = ?');
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
  $stmt = $db->prepare('SELECT * FROM g5_board WHERE wr_name = ? ORDER BY wr_id DESC');
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
  $stmt = $db->prepare('SELECT mb_id, mb_datetime FROM g5_member ORDER BY mb_datetime DESC, mb_id ASC');
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
    $stmt = $db->prepare('DELETE FROM g5_board WHERE wr_name = ?');
    $stmt->execute([$username]);
    
    // 회원 삭제
    $stmt = $db->prepare('DELETE FROM g5_member WHERE mb_id = ?');
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

// 스킨 설정
define('SKIN_DIR', './skin/default');
?>
