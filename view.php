<?php
require_once 'config.php';
requireLogin();

// 페이지 제목 설정
$page_title = $lang['view_post'];

// 헤더 포함
require_once 'inc/header.php';

// 입력값 검증 및 이스케이프
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$action = filter_var($_GET['action'] ?? '', FILTER_SANITIZE_STRING);
$bo_table = filter_var($_GET['bo_table'] ?? '', FILTER_SANITIZE_STRING);

if (!$id || $id <= 0) {
  header('Location: index.php');
  exit;
}

$post = getPost($id);
if (!$post['wr_id']) {
  header('Location: index.php');
  exit;
}

// CSRF 토큰 검증 (삭제 작업 시)
if ($action === 'delete' && $id) {
  if (!isAdmin()) {
    header('Location: index.php');
    exit;
  }
  
  if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_GET['token'])) {
    die($lang['delete_permission_denied']);
  }
  
  deletePost($id);
  header('Location: index.php');
  exit;
}

incrementView($id);
?>

<div class="content-wrapper">
<?php
// XSS 공격 방지를 위한 추가 이스케이프
$post = [
  'wr_subject' => htmlspecialchars($post['wr_subject'], ENT_QUOTES, 'UTF-8'),
  'wr_name' => htmlspecialchars($post['wr_name'], ENT_QUOTES, 'UTF-8'),
  'wr_datetime' => htmlspecialchars($post['wr_datetime'], ENT_QUOTES, 'UTF-8'),
  'wr_hit' => (int)$post['wr_hit'],
  'wr_content' => $post['wr_content'], // HTML 내용은 출력 시 필터링 필요
  'wr_id' => (int)$id
];

// 파일 포함 공격 방지
$board_skin = 'default'; // 기본 스킨 설정
$skin_path = "skin/{$board_skin}/view.skin.php";
if (file_exists($skin_path)) {
  $board_config = ['bo_subject' => $lang['board']];
  include $skin_path;
} else {
  echo '<p>' . $lang['skin_not_found'] . '</p>';
}
?>

</div>

<?php
// 푸터 포함
require_once 'inc/footer.php';
?>
