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
    } else {
      insertPost($data);
    }
    header('Location: index.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $id ? $lang['edit'] : $lang['write_post']; ?></title>
<meta charset="UTF-8">
<link rel="stylesheet" href="skin/default/style.css">
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
</head>
<body class="write-page">
<?php
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
</body>
</html>
