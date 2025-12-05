<?php
define('IN_ADMIN', true);
$admin_title_key = 'policy_management';
require_once 'common.php';

// 관리자 권한 확인
if (!isAdmin()) {
  die('<div class="admin-card"><p>' . $lang['admin_only'] . '</p></div>');
}

$error = '';
$success = '';

// 탭 및 언어 설정
$tab = $_GET['tab'] ?? 'terms'; // terms, privacy
$target_lang = $_GET['target_lang'] ?? 'ko'; // ko, en, ja, zh
$policy_type = $tab . '_' . $target_lang;

// 예외: 기본 호환성을 위해 한국어는 접미사 없이 저장할 수도 있지만,
// 명확한 다국어 지원을 위해 '_ko'를 붙여서 저장하는 것을 권장.
// 단, 기존 데이터(접미사 없는 terms)를 불러와야 할 수도 있음.
// 여기서는 편집 시 무조건 언어 코드를 붙여서 저장하도록 함.

// 정책 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $error = $lang['csrf_token_invalid'];
  } else {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    
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

// 정책 내용 가져오기
$policy = getPolicy($policy_type);

// 만약 해당 언어의 정책이 없으면?
// 1. 기본 정책(접미사 없는 것)에서 가져와서 보여줄지 결정
if (!$policy) {
    $base_policy = getPolicy($tab); // terms, privacy
    if ($base_policy && $target_lang == 'ko') {
        // 한국어 탭인데 데이터가 없으면 기본 데이터(기존 데이터)를 로드
        $policy = $base_policy;
    } else {
        // 기본값
        $policy = [
            'policy_title' => '',
            'policy_content' => '',
            'updated_at' => null
        ];
    }
}
?>

<style>
.tab-container {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 1rem;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    text-decoration: none;
    font-weight: 600;
    color: var(--text-light);
    transition: all 0.2s;
    border: 1px solid transparent;
}

.tab-btn:hover {
    background: var(--bg-tertiary);
    color: var(--text-color);
}

.tab-btn.active {
    background: var(--primary-color);
    color: white;
    box-shadow: var(--shadow-sm);
}

.lang-selector {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    background: var(--bg-secondary);
    padding: 0.5rem;
    border-radius: var(--radius);
    width: fit-content;
}

.lang-btn {
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    text-decoration: none;
    font-size: 0.9rem;
    color: var(--text-color);
    transition: all 0.2s;
}

.lang-btn:hover {
    background: var(--bg-tertiary);
}

.lang-btn.active {
    background: white;
    color: var(--primary-color);
    box-shadow: var(--shadow-sm);
    font-weight: 600;
}

.editor-container {
    margin-top: 1rem;
}
</style>

<?php if ($error): ?>
  <div style="background: var(--danger-color); color: white; padding: 1rem; border-radius: var(--radius); margin-bottom: 2rem;">
    <?php echo htmlspecialchars($error); ?>
  </div>
<?php endif; ?>

<?php if ($success): ?>
  <div style="background: var(--success-color, #28a745); color: white; padding: 1rem; border-radius: var(--radius); margin-bottom: 2rem;">
    <?php echo htmlspecialchars($success); ?>
  </div>
<?php endif; ?>

<div class="admin-card">
    <div class="tab-container">
        <a href="?tab=terms&target_lang=<?php echo $target_lang; ?>" class="tab-btn <?php echo $tab === 'terms' ? 'active' : ''; ?>">
            📜 <?php echo $lang['edit_terms']; ?>
        </a>
        <a href="?tab=privacy&target_lang=<?php echo $target_lang; ?>" class="tab-btn <?php echo $tab === 'privacy' ? 'active' : ''; ?>">
            🔒 <?php echo $lang['edit_privacy']; ?>
        </a>
    </div>
    
    <div class="lang-selector">
        <a href="?tab=<?php echo $tab; ?>&target_lang=ko" class="lang-btn <?php echo $target_lang === 'ko' ? 'active' : ''; ?>">🇰🇷 한국어</a>
        <a href="?tab=<?php echo $tab; ?>&target_lang=en" class="lang-btn <?php echo $target_lang === 'en' ? 'active' : ''; ?>">🇺🇸 English</a>
        <a href="?tab=<?php echo $tab; ?>&target_lang=ja" class="lang-btn <?php echo $target_lang === 'ja' ? 'active' : ''; ?>">🇯🇵 日本語</a>
        <a href="?tab=<?php echo $tab; ?>&target_lang=zh" class="lang-btn <?php echo $target_lang === 'zh' ? 'active' : ''; ?>">🇨🇳 中文</a>
    </div>

    <?php if ($policy['updated_at']): ?>
    <div style="margin-bottom: 1.5rem; color: var(--text-light); font-size: 0.9rem display: flex; align-items: center; gap: 0.5rem;">
        <span>🕒 <?php echo $lang['last_updated']; ?>: <?php echo htmlspecialchars($policy['updated_at']); ?></span>
    </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                <?php echo $lang['policy_title']; ?> (<?php echo strtoupper($target_lang); ?>)
            </label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($policy['policy_title']); ?>" 
                   class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-secondary); color: var(--text-color);" required>
        </div>
        
        <div class="editor-container">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);"><?php echo $lang['policy_content']; ?></label>
            <textarea id="summernote" name="content"><?php echo htmlspecialchars($policy['policy_content']); ?></textarea>
        </div>
        
        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn-primary" style="background: var(--primary-color); color: white; border: none; padding: 0.75rem 2rem; border-radius: var(--radius); font-weight: 600; cursor: pointer;">
                💾 <?php echo $lang['save']; ?>
            </button>
        </div>
    </form>
</div>

<!-- Summernote CSS/JS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<?php if ($lang_code == 'ko'): ?>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-ko-KR.min.js"></script>
<?php endif; ?>

<script>
$(document).ready(function() {
  $('#summernote').summernote({
    height: 500,
    lang: '<?php echo $lang_code == 'ko' ? 'ko-KR' : 'en-US'; ?>',
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'underline', 'clear']],
      ['fontname', ['fontname']],
      ['fontsize', ['fontsize']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['table', ['table']],
      ['insert', ['link', 'picture']],
      ['view', ['fullscreen', 'codeview', 'help']]
    ],
    fontNames: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Merriweather', 'Noto Sans KR', 'Malgun Gothic', 'Dotum', 'Gulim'],
    fontNamesIgnoreCheck: ['Noto Sans KR', 'Malgun Gothic', 'Dotum', 'Gulim']
  });
});
</script>

</main>
</div>
</body>
</html>
