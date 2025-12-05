<?php
require_once 'config.php';
requireLogin();

$id = $_GET['id'] ?? null;
$post = getPost($id);
$post['wr_subject'] = $post['wr_subject'] ?? '';
$post['wr_content'] = $post['wr_content'] ?? '';
$post['wr_name'] = $_SESSION['user'];

if ($_POST) {
  $title = trim($_POST['title']);
  $content = $_POST['content'];  // HTML 보존, trim 제거
  if ($title && $content) {
    $data = [
      'title' => $title,
      'content' => $content,
      'writer' => $_SESSION['user']
    ];
    if ($id !== null) {
      updatePost($id, $data);
      $wr_id = $id;
    } else {
      $wr_id = insertPost($data);
      
      // 포인트 지급
      $config = get_config();
      if ($config['cf_use_point'] && $config['cf_write_point'] != 0) {
        insert_point(
          $_SESSION['user'], 
          $config['cf_write_point'], 
          $lang['post_write_action'], 
          'mb1_board', 
          $wr_id, 
          'write'
        );
      }
    }

    // 파일 업로드 처리
    if (!empty($_FILES['bf_file']['name'][0])) {
        $files = $_FILES['bf_file'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                insertFile($wr_id, $file);
            }
        }
    }
    header('Location: list.php');
    exit;
  }
}
?>
<?php
$page_title = $id ? $lang['edit'] : $lang['write_post'];
require_once 'inc/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<?php
// 스킨 설정
$board_skin = 'default'; // 기본 스킨
if (!empty($_GET['bo_table'])) {
  $db = getDB();
  $stmt = $db->prepare('SELECT bo_skin FROM mb1_board_config WHERE bo_table = ?');
  $stmt->execute([$_GET['bo_table']]);
  $config = $stmt->fetch();
  $board_skin = $config['bo_skin'] ?? 'default';
}

$skin_path = "skin/$board_skin/write.skin.php";
if (file_exists($skin_path)) {
  $board_config = ['bo_subject' => $lang['write_post']];
  $post = [
    'wr_subject' => htmlspecialchars($post['wr_subject'] ?? ''),
    'wr_content' => htmlspecialchars($post['wr_content'] ?? '')
  ];
  $page_title = $id ? $lang['post_edit'] : $lang['write_post'];
  include $skin_path;
} else {
  echo '<p>' . $lang['skin_not_found'] . '</p>';
}
?>
<script>
$(document).ready(function() {
  $('#summernote').summernote({
    height: 300,
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'underline', 'clear']],
      ['fontname', ['fontname']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['table', ['table']],
      ['insert', ['link', 'picture', 'video']],
      ['view', ['fullscreen', 'codeview', 'help']]
    ],
    callbacks: {
      onImageUpload: function(files) {
        var formData = new FormData();
        formData.append('file', files[0]);
        $.ajax({
          url: 'upload_image.php',
          data: formData,
          cache: false,
          contentType: false,
          processData: false,
          type: 'POST',
          success: function(response) {
            if (response.url) {
              $('#summernote').summernote('insertImage', response.url);
            } else {
              alert('<?php echo $lang['image_upload_failed']; ?>: ' + (response.error || '<?php echo $lang['unknown_error']; ?>'));
            }
          },
          error: function() {
            alert('<?php echo $lang['image_upload_failed']; ?>');
          }
        });
      }
    }
  });
});
</script>
<?php require_once 'inc/footer.php'; ?>
