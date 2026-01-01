<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$message = '';
$error = '';

// 포인트 수동 지급/차감 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_point') {
    $mb_id = trim($_POST['mb_id']);
    $point = intval($_POST['point']);
    $reason = trim($_POST['reason']);
    
    if (empty($mb_id) || $point == 0 || empty($reason)) {
        $error = "모든 항목을 올바르게 입력해주세요.";
    } else {
        // 회원 존재 확인
        $stmt = $db->prepare("SELECT COUNT(*) FROM mb1_member WHERE mb_id = ?");
        $stmt->execute([$mb_id]);
        if ($stmt->fetchColumn() == 0) {
            $error = "존재하지 않는 회원 아이디입니다.";
        } else {
            insert_point($mb_id, $point, "[관리자 조정] " . $reason, 'admin', 0, 'adjust');
            $message = "포인트가 성공적으로 반영되었습니다.";
            log_admin_action('point_adjust', "User: $mb_id, Point: $point, Reason: $reason");
        }
    }
}

// 목록 조회
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 30;
$offset = ($page - 1) * $per_page;

$where = "1=1";
$params = [];
if (!empty($_GET['mb_id'])) {
    $where .= " AND mb_id LIKE ?";
    $params[] = '%' . $_GET['mb_id'] . '%';
}

$stmt = $db->prepare("SELECT COUNT(*) FROM mb1_point WHERE $where");
$stmt->execute($params);
$total_count = $stmt->fetchColumn();
$total_pages = ceil($total_count / $per_page);

$stmt = $db->prepare("SELECT * FROM mb1_point WHERE $where ORDER BY po_datetime DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$points = $stmt->fetchAll();

include 'common.php';
?>

<div class="admin-content">
    <h2>💰 <?php echo $lang['point_management'] ?? '포인트 관리'; ?></h2>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-label">전체 포인트 로그</div>
                <div class="stat-value"><?php echo number_format($total_count); ?></div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 2rem;">
        <h3>➕ 포인트 수동 지급/차감</h3>
        <form method="post" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="action" value="adjust_point">
            <div>
                <label style="display:block; font-size:0.8rem; margin-bottom:0.2rem;">회원 아이디</label>
                <input type="text" name="mb_id" required style="padding:0.5rem; border:1px solid var(--border-color); border-radius:4px;">
            </div>
            <div>
                <label style="display:block; font-size:0.8rem; margin-bottom:0.2rem;">포인트 (음수 가능)</label>
                <input type="number" name="point" required style="padding:0.5rem; border:1px solid var(--border-color); border-radius:4px;">
            </div>
            <div>
                <label style="display:block; font-size:0.8rem; margin-bottom:0.2rem;">사유</label>
                <input type="text" name="reason" required style="padding:0.5rem; border:1px solid var(--border-color); border-radius:4px; width:250px;">
            </div>
            <button type="submit" class="btn btn-primary" style="padding:0.55rem 1.5rem;">반영하기</button>
        </form>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3>📂 포인트 내역</h3>
            <form method="get" style="display:flex; gap:0.5rem;">
                <input type="text" name="mb_id" value="<?php echo htmlspecialchars($_GET['mb_id'] ?? ''); ?>" placeholder="아이디 검색" style="padding:0.4rem; border:1px solid var(--border-color); border-radius:4px;">
                <button type="submit" class="btn btn-sm btn-secondary">검색</button>
            </form>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>아이디</th>
                    <th>일시</th>
                    <th>내용</th>
                    <th>포인트</th>
                    <th>관련</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($points as $p): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['mb_id']); ?></strong></td>
                    <td style="font-size:0.9rem; color:#6b7280;"><?php echo $p['po_datetime']; ?></td>
                    <td><?php echo htmlspecialchars($p['po_content']); ?></td>
                    <td style="color: <?php echo $p['po_point'] > 0 ? '#10b981' : '#ef4444'; ?>; font-weight:700;">
                        <?php echo ($p['po_point'] > 0 ? '+' : '') . number_format($p['po_point']); ?>
                    </td>
                    <td style="font-size:0.8rem; color:#9ca3af;"><?php echo $p['po_rel_table']; ?> (<?php echo $p['po_rel_id']; ?>)</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&mb_id=<?php echo urlencode($_GET['mb_id'] ?? ''); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
