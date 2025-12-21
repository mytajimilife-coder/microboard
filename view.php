<?php
require_once 'config.php';
requireLogin();

// 입력값 검증
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$action = filter_var($_GET['action'] ?? '', FILTER_SANITIZE_STRING);
$bo_table = filter_var($_GET['bo_table'] ?? '', FILTER_SANITIZE_STRING);

// bo_table 필수 체크
if (!$bo_table) {
    header('Location: index.php');
    exit;
}

// 게시판 권한 체크
$db = getDB();
$stmt = $db->prepare('SELECT bo_read_level FROM mb1_board_config WHERE bo_table = ?');
$stmt->execute([$bo_table]);
$board_config = $stmt->fetch();

if ($board_config) {
    $bo_read_level = $board_config['bo_read_level'] ?? 1;
    $user_level = $_SESSION['mb_level'] ?? 1;
    
    if ($user_level < $bo_read_level) {
        echo "<script>alert('" . ($lang['insufficient_level_for_read'] ?? '글을 읽을 권한이 없습니다.') . "'); history.back();</script>";
        exit;
    }
}

if (!$id || $id <= 0) {
  header('Location: list.php?bo_table=' . $bo_table);
  exit;
}

// 게시글 정보 가져오기
$post = getPost($bo_table, $id); 

if (!$post || !$post['wr_id']) {
  header('Location: list.php?bo_table=' . $bo_table);
  exit;
}

// SEO 메타 데이터 설정
$page_title = htmlspecialchars($post['wr_subject']);
// 본문 내용에서 태그 제거하고 앞부분만 추출하여 설명으로 사용
$plain_content = strip_tags($post['wr_content']);
$meta_description = mb_substr(str_replace(["\r", "\n"], " ", $plain_content), 0, 160, 'utf-8');
$meta_keywords = 'microboard, ' . htmlspecialchars($post['wr_name']);

// 헤더 포함
require_once 'inc/header.php';

// CSRF 토큰 검증 (삭제 작업 시)
if ($action === 'delete' && $id) {
  if (!isAdmin() && $_SESSION['user'] !== $post['wr_name']) {
    header('Location: list.php?bo_table=' . $bo_table);
    exit;
  }
  
  if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_GET['token'])) {
    die($lang['delete_permission_denied']);
  }
  
  deletePost($bo_table, $id);
  header('Location: list.php?bo_table=' . $bo_table);
  exit;
}

incrementView($bo_table, $id);
?>

<div class="content-wrapper">
<?php
// XSS 공격 방지를 위한 추가 이스케이프
// XSS 공격 방지를 위한 추가 이스케이프 및 데이터 준비
$post_view = [
  'wr_id' => $post['wr_id'],
  'wr_subject' => htmlspecialchars($post['wr_subject'], ENT_QUOTES, 'UTF-8'),
  'wr_content' => replace_variables(clean_xss($post['wr_content'])), 
  'wr_name' => htmlspecialchars($post['wr_name'], ENT_QUOTES, 'UTF-8'),
  'wr_datetime' => $post['wr_datetime'],
  'wr_hit' => $post['wr_hit']
];

// 첨부파일 가져오기
$files = getPostFiles($bo_table, $id);

// 댓글 가져오기
$comments = getComments($bo_table, $id);

// 스킨 로드
$board_skin = 'default';
if ($bo_table) {
    $db = getDB();
    $stmt = $db->prepare('SELECT bo_skin FROM mb1_board_config WHERE bo_table = ?');
    $stmt->execute([$bo_table]);
    $config = $stmt->fetch();
    if ($config) $board_skin = $config['bo_skin'];
}

$skin_path = "skin/$board_skin/view.skin.php";
if (file_exists($skin_path)) {
    include $skin_path;
} else {
    echo "Skin not found: $skin_path";
}


</div>

<?php require_once 'inc/footer.php'; ?>
