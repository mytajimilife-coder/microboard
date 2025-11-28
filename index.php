<?php
require_once 'config.php';
requireLogin();

// 페이지 제목 설정
$page_title = $lang['board_list'];

// 헤더 포함
require_once 'inc/header.php';

$posts = loadPosts();
?>

<?php
$board_skin = 'default'; // 기본 스킨
if (!empty($_GET['bo_table'])) {
  $db = getDB();
  $stmt = $db->prepare('SELECT bo_skin FROM g5_board_config WHERE bo_table = ?');
  $stmt->execute([$_GET['bo_table']]);
  $config = $stmt->fetch();
  $board_skin = $config['bo_skin'] ?? 'default';
}
?>

<link rel="stylesheet" href="skin/<?php echo $board_skin; ?>/style.css">

<div class="content-wrapper">
  <?php
  $skin_path = "skin/$board_skin/list.skin.php";
  if (file_exists($skin_path)) {
    $board_config = ['bo_subject' => $lang['board_list']];
    $list = array_map(function($key, $post) {
      return [
        'num' => $key + 1,
        'wr_id' => $post['wr_id'],
        'wr_subject' => htmlspecialchars($post['wr_subject']),
        'wr_name' => htmlspecialchars($post['wr_name']),
        'wr_datetime' => $post['wr_datetime'],
        'wr_hit' => $post['wr_hit']
      ];
    }, array_keys($posts), $posts);
    $bo_table = $_GET['bo_table'] ?? '';
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
