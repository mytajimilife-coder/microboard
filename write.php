<?php
require_once 'config.php';
requireLogin();

$id = $_GET['id'] ?? null;
$bo_table = $_GET['bo_table'] ?? '';

// bo_table 필수 체크
if (!$bo_table) {
    echo "<script>alert('{$lang['access_denied']}'); location.href='index.php';</script>";
    exit;
}

// 쓰기 권한 체크
$db = getDB();
$stmt = $db->prepare('SELECT bo_write_level FROM mb1_board_config WHERE bo_table = ?');
$stmt->execute([$bo_table]);
$board_config = $stmt->fetch();

if ($board_config) {
    $bo_write_level = $board_config['bo_write_level'] ?? 1;
    $user_level = $_SESSION['mb_level'] ?? 1;
    
    if ($user_level < $bo_write_level) {
        echo "<script>alert('" . ($lang['insufficient_level_for_write'] ?? '글을 쓸 권한이 없습니다.') . "'); history.back();</script>";
        exit;
    }
}

$post = $id ? getPost($bo_table, $id) : [];
$post['wr_subject'] = $post['wr_subject'] ?? '';
$post['wr_content'] = $post['wr_content'] ?? '';
$post['wr_name'] = $_SESSION['user'];

if ($_POST) {
  // CSRF 토큰 검증
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
      die('CSRF token validation failed');
  }

  $title = filter_content(trim($_POST['title']));
  $content = filter_content($_POST['content']);  // HTML 보존, 필터링 적용
  if ($title && $content) {
    $data = [
      'title' => $title,
      'content' => $content,
      'writer' => $_SESSION['user']
    ];
    if ($id !== null) {
      updatePost($bo_table, $id, $data);
      $wr_id = $id;
    } else {
      $wr_id = insertPost($bo_table, $data);
      
      // 포인트 지급
      $config = get_config();
      if ($config['cf_use_point'] && $config['cf_write_point'] != 0) {
        insert_point(
          $_SESSION['user'], 
          $config['cf_write_point'], 
          $lang['post_write_action'], 
          $bo_table, 
          $wr_id, 
          'write'
        );
        
        // 자동 승급 체크
        check_auto_level_up($_SESSION['user']);
      }
    }

    // 파일 업로드 처리
    if (!empty($_FILES['bf_file']['name'][0])) {
        $files = $_FILES['bf_file'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                // 파일 확장자 검사 (화이트리스트 방식)
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'txt', 'pdf', 'zip', 'hwp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
                if (!in_array($ext, $allowed_ext)) {
                    continue; // 허용되지 않는 확장자 무시
                }

                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                insertFile($bo_table, $wr_id, $file);
            }
        }
    }
    header('Location: list.php?bo_table=' . $bo_table);
    exit;
  }
}
?>
<?php
$page_title = $id ? $lang['edit'] : $lang['write_post'];
require_once 'inc/header.php';

// 게시판 설정 가져오기
$board_skin = 'default';
$use_editor = 1; // 기본값: 에디터 사용
if (!empty($_GET['bo_table'])) {
  $db = getDB();
  $stmt = $db->prepare('SELECT bo_skin, bo_use_editor FROM mb1_board_config WHERE bo_table = ?');
  $stmt->execute([$_GET['bo_table']]);
  $config = $stmt->fetch();
  $board_skin = $config['bo_skin'] ?? 'default';
  $use_editor = $config['bo_use_editor'] ?? 1;
}
?>
<?php if ($use_editor): ?>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<?php endif; ?>
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
<?php if ($use_editor): ?>
<script>
$(document).ready(function() {
  $('#summernote').summernote({
    height: 300,
    lang: '<?php echo substr($_SESSION['lang'] ?? 'ko', 0, 2); ?>',
    fontNames: [
      // 기본 폰트
      'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Helvetica', 'Impact', 'Tahoma', 'Times New Roman', 'Verdana',
      // 한국어 폰트
      '맑은 고딕', '돋움', '굴림', '바탕', '궁서', 'Noto Sans KR', 'Nanum Gothic', 'Nanum Myeongjo',
      // 일본어 폰트
      'メイリオ', 'MS Pゴシック', 'MS P明朝', 'Noto Sans JP', 'Yu Gothic',
      // 중국어 폰트
      '微软雅黑', '宋体', '黑体', 'Noto Sans SC', 'SimSun', 'SimHei',
      // 웹 안전 폰트
      'Georgia', 'Palatino Linotype', 'Book Antiqua', 'Palatino'
    ],
    fontNamesIgnoreCheck: [
      '맑은 고딕', '돋움', '굴림', '바탕', '궁서', 'Noto Sans KR', 'Nanum Gothic', 'Nanum Myeongjo',
      'メイリオ', 'MS Pゴシック', 'MS P明朝', 'Noto Sans JP', 'Yu Gothic',
      '微软雅黑', '宋体', '黑体', 'Noto Sans SC', 'SimSun', 'SimHei'
    ],
    fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '24', '28', '32', '36', '48', '64', '72'],
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'underline', 'clear']],
      ['fontname', ['fontname']],
      ['fontsize', ['fontsize']],
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
<?php endif; ?>
<?php require_once 'inc/footer.php'; ?>
