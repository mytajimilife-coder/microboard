<?php
define('IN_ADMIN', true);
$admin_title_key = 'page_management';
require_once 'common.php';

$db = getDB();
$action = $_GET['action'] ?? '';
$pg_id = $_GET['pg_id'] ?? 0;

// CSRF 토큰 검증
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($action === 'delete' && $pg_id)) {
    if (!isset($_REQUEST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_REQUEST['csrf_token'])) {
        die('<div class="admin-card"><p>' . $lang['csrf_token_invalid'] . '</p></div>');
    }
}

// 페이지 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'save') {
    $pg_id = (int)($_POST['pg_id'] ?? 0);
    $pg_slug = trim($_POST['pg_slug']);
    $pg_title = trim($_POST['pg_title']);
    $pg_content = $_POST['pg_content'];
    $pg_view_level = (int)($_POST['pg_view_level'] ?? 0);

    // 슬러그 검증 (영문, 숫자, 하이픈만 허용)
    if (!preg_match('/^[a-zA-Z0-9-]+$/', $pg_slug)) {
        die('<div class="admin-card"><p>ID는 영문, 숫자, 하이픈(-)만 사용할 수 있습니다.</p></div>');
    }

    if ($pg_id > 0) {
        // 수정
        $stmt = $db->prepare("UPDATE mb1_page SET pg_slug = ?, pg_title = ?, pg_content = ?, pg_view_level = ? WHERE pg_id = ?");
        $stmt->execute([$pg_slug, $pg_title, $pg_content, $pg_view_level, $pg_id]);
    } else {
        // 생성
        // 중복 체크
        $stmt = $db->prepare("SELECT COUNT(*) FROM mb1_page WHERE pg_slug = ?");
        $stmt->execute([$pg_slug]);
        if ($stmt->fetchColumn() > 0) {
            die('<div class="admin-card"><p>이미 존재하는 ID입니다.</p></div>');
        }

        $stmt = $db->prepare("INSERT INTO mb1_page (pg_slug, pg_title, pg_content, pg_view_level, pg_datetime) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$pg_slug, $pg_title, $pg_content, $pg_view_level]);
    }

    echo "<script>location.href='pages.php';</script>";
    exit;
}

// 페이지 삭제 처리
if ($action === 'delete' && $pg_id) {
    $stmt = $db->prepare("DELETE FROM mb1_page WHERE pg_id = ?");
    $stmt->execute([$pg_id]);

    echo "<script>location.href='pages.php';</script>";
    exit;
}

// 단일 페이지 조회
$page = [];
if ($pg_id) {
    $stmt = $db->prepare("SELECT * FROM mb1_page WHERE pg_id = ?");
    $stmt->execute([$pg_id]);
    $page = $stmt->fetch();
}

if ($action === 'create') {
    $page = [
        'pg_id' => 0,
        'pg_slug' => '',
        'pg_title' => '',
        'pg_content' => '',
        'pg_view_level' => 0 // 기본값 0 (비회원 가능)
    ];
}

// 목록 조회
$pages = [];
if (!$action) {
    $stmt = $db->query("SELECT * FROM mb1_page ORDER BY pg_datetime DESC");
    $pages = $stmt->fetchAll();
}
?>

<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0; color: var(--secondary-color);"><?php echo $lang['page_management'] ?? '페이지 관리'; ?></h3>
        <?php if (!$action): ?>
        <a href="pages.php?action=create" class="action-btn" style="background: var(--primary-color); color: white;">
            ➕ <?php echo $lang['create_page'] ?? '페이지 추가'; ?>
        </a>
        <?php endif; ?>
    </div>

<?php if (!$action): ?>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID (Slug)</th>
                    <th><?php echo $lang['title'] ?? '제목'; ?></th>
                    <th><?php echo $lang['view_level'] ?? '읽기 권한'; ?></th>
                    <th><?php echo $lang['datetime'] ?? '작성일'; ?></th>
                    <th style="min-width: 140px; text-align: center;"><?php echo $lang['function'] ?? '기능'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <tr>
                    <td style="font-weight: 600; color: var(--primary-color);">
                        <a href="../page.php?id=<?php echo htmlspecialchars($p['pg_slug']); ?>" target="_blank" style="text-decoration: none; color: inherit;">
                            <?php echo htmlspecialchars($p['pg_slug']); ?> ↗️
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($p['pg_title']); ?></td>
                    <td>
                        <?php 
                        if ($p['pg_view_level'] == 0) echo $lang['guest_users'] ?? '손님';
                        else echo 'Level ' . $p['pg_view_level']; 
                        ?>
                    </td>
                    <td><?php echo $p['pg_datetime']; ?></td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <a href="pages.php?action=edit&pg_id=<?php echo $p['pg_id']; ?>" class="action-btn btn-edit">
                                ✏️ <?php echo $lang['edit'] ?? '수정'; ?>
                            </a>
                            <a href="pages.php?action=delete&pg_id=<?php echo $p['pg_id']; ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" 
                               class="action-btn btn-delete" onclick="return confirm('<?php echo $lang['delete_confirm'] ?? '정말 삭제하시겠습니까?'; ?>')">
                                🗑️
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pages)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-light);">
                        <?php echo $lang['no_pages'] ?? '등록된 페이지가 없습니다.'; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <form method="post">
        <input type="hidden" name="act" value="save">
        <input type="hidden" name="pg_id" value="<?php echo $page['pg_id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

        <div class="form-group" style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo $lang['page_id'] ?? '페이지 ID (URL Slug)'; ?></label>
            <input type="text" name="pg_slug" class="form-control" value="<?php echo htmlspecialchars($page['pg_slug']); ?>" required <?php echo $page['pg_id'] ? 'readonly' : ''; ?>>
            <small style="color: var(--text-light);"><?php echo $lang['page_id_help'] ?? '영문, 숫자, 하이픈(-)만 사용 가능. 예: about, contact, terms'; ?></small>
        </div>

        <div class="form-group" style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo $lang['title'] ?? '제목'; ?></label>
            <input type="text" name="pg_title" class="form-control" value="<?php echo htmlspecialchars($page['pg_title']); ?>" required>
        </div>
        
        <div class="form-group" style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo $lang['view_level'] ?? '읽기 권한'; ?></label>
            <select name="pg_view_level" class="form-control" style="width: auto;">
                <option value="0" <?php echo ($page['pg_view_level'] == 0) ? 'selected' : ''; ?>>
                    <?php echo $lang['guest_users'] ?? '비회원 포함 (Level 0)'; ?>
                </option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo ($page['pg_view_level'] == $i) ? 'selected' : ''; ?>>
                    Level <?php echo $i; ?>
                    <?php echo $i == 1 ? ' (' . ($lang['all_users'] ?? '모든 회원') . ')' : ($i == 10 ? ' (' . ($lang['admin_only'] ?? '관리자만') . ')' : ''); ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo $lang['content'] ?? '내용'; ?></label>
            <textarea name="pg_content" id="pg_content" class="form-control" style="height: 400px;"><?php echo htmlspecialchars($page['pg_content']); ?></textarea>
            <small style="color: var(--text-light);"><?php echo $lang['content_help'] ?? 'HTML 및 변수 사용 가능 (예: {{site_title}}, {{username}})'; ?></small>
        </div>

        <div style="text-align: right; margin-top: 1rem;">
            <a href="pages.php" class="action-btn" style="background: var(--bg-secondary); color: var(--text-color); border: 1px solid var(--border-color); margin-right: 0.5rem; padding: 0.75rem 1.5rem;"><?php echo $lang['cancel'] ?? '취소'; ?></a>
            <button type="submit" class="btn-primary"><?php echo $lang['save'] ?? '저장'; ?></button>
        </div>
    </form>
<?php endif; ?>

</div>

</main>
</div>
</body>
</html>
