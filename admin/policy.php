<?php
define('IN_ADMIN', true);
require_once 'common.php';

// 관리자 권한 확인
if (!isAdmin()) {
  die('<p>' . $lang['admin_only'] . '</p>');
}

$error = '';
$success = '';
$policy_type = $_GET['type'] ?? 'terms'; // terms 또는 privacy

// 정책 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF 토큰 검증
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $error = $lang['csrf_token_invalid'];
  } else {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // HTML 보존
    
    if (empty($title) || empty($content)) {
      $error = $lang['input_required'];
    } else {
      if (updatePolicy($policy_type, $title, $content)) {
        $success = $lang['policy_updated'];
      } else {
        $error = $lang['policy_update_failed'];
      }
    }
  }
}

// 현재 정책 내용 가져오기
$policy = getPolicy($policy_type);
if (!$policy) {
  // 기본값 설정
  $policy = [
    'policy_title' => $policy_type === 'terms' ? $lang['terms_of_service'] : $lang['privacy_policy'],
    'policy_content' => '',
    'updated_at' => null
  ];
}
?>
<h1><?php echo $lang['policy_management']; ?></h1>

<?php if ($error): ?>
  <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
    <?php echo htmlspecialchars($error); ?>
  </div>
<?php endif; ?>

<?php if ($success): ?>
  <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
    <?php echo htmlspecialchars($success); ?>
  </div>
<?php endif; ?>

<div style="margin-bottom: 20px;">
  <a href="?type=terms" class="btn <?php echo $policy_type === 'terms' ? 'active' : ''; ?>" style="margin-right: 10px; <?php echo $policy_type === 'terms' ? 'background: #007bff;' : 'background: #6c757d;'; ?>"><?php echo $lang['edit_terms']; ?></a>
  <a href="?type=privacy" class="btn <?php echo $policy_type === 'privacy' ? 'active' : ''; ?>" style="<?php echo $policy_type === 'privacy' ? 'background: #007bff;' : 'background: #6c757d;'; ?>"><?php echo $lang['edit_privacy']; ?></a>
</div>

<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
  <?php if ($policy['updated_at']): ?>
    <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
      <?php echo $lang['last_updated']; ?>: <?php echo htmlspecialchars($policy['updated_at']); ?>
    </p>
  <?php endif; ?>
  
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    
    <div style="margin-bottom: 20px;">
      <label style="display: block; margin-bottom: 5px; font-weight: bold;"><?php echo $lang['policy_title']; ?>:</label>
      <input type="text" name="title" value="<?php echo htmlspecialchars($policy['policy_title']); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
    </div>
    
    <div style="margin-bottom: 20px;">
      <label style="display: block; margin-bottom: 5px; font-weight: bold;"><?php echo $lang['policy_content']; ?>:</label>
      <textarea id="summernote" name="content" style="display: none;"><?php echo htmlspecialchars($policy['policy_content']); ?></textarea>
    </div>
    
    <div style="display: flex; gap: 10px;">
      <button type="submit" class="btn" style="background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer;"><?php echo $lang['save']; ?></button>
      <a href="index.php" class="btn" style="background: #6c757d; color: white; padding: 12px 24px; border-radius: 4px; text-decoration: none;"><?php echo $lang['cancel']; ?></a>
    </div>
  </form>
</div>

<!-- Summernote CSS/JS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<script>
$(document).ready(function() {
  $('#summernote').summernote({
    height: 500,
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'underline', 'clear']],
      ['fontname', ['fontname']],
      ['fontsize', ['fontsize']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['height', ['height']],
      ['table', ['table']],
      ['insert', ['link']],
      ['view', ['fullscreen', 'codeview', 'help']]
    ]
  });
});
</script>

<style>
  .btn {
    display: inline-block;
    text-decoration: none;
    transition: opacity 0.2s;
  }
  .btn:hover {
    opacity: 0.9;
  }
</style>
