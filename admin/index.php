<?php
define('IN_ADMIN', true);
require_once 'common.php';

$db = getDB();

// 1. 통계 데이터 가져오기
$member_count = $db->query("SELECT COUNT(*) FROM mb1_member")->fetchColumn();
$board_count = $db->query("SELECT COUNT(*) FROM mb1_board_config")->fetchColumn();

// 전체 게시글 수 (모든 테이블 합산)
$total_posts = 0;
$boards = $db->query("SELECT bo_table FROM mb1_board_config")->fetchAll();
foreach ($boards as $board) {
    try {
        $total_posts += $db->query("SELECT COUNT(*) FROM mb1_write_{$board['bo_table']}")->fetchColumn();
    } catch(Exception $e) {}
}

// 파일 통계
$file_stats = ['count' => 0, 'size' => 0];
try {
    $stmt = $db->query("SELECT COUNT(*) as count, SUM(file_size) as total_size FROM mb1_file_manager");
    $file_stats = $stmt->fetch();
} catch(Exception $e) {}

// 최신 로그 (5개)
$recent_logs = [];
try {
    $recent_logs = $db->query("SELECT * FROM mb1_admin_log ORDER BY log_datetime DESC LIMIT 5")->fetchAll();
} catch(Exception $e) {}

// 최신 백업 정보
$latest_backup = null;
$backup_dir = __DIR__ . '/../data/backup';
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '/*.sql');
    if ($files) {
        $latest_backup = date('Y-m-d H:i:s', filemtime(end($files)));
    }
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
?>

<div class="dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-box" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;"><?php echo $lang['all_users'] ?? 'Total Members'; ?></div>
        <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary-color);"><?php echo number_format($member_count); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #10b981;">↑ <?php echo $lang['active'] ?? 'Active'; ?></div>
    </div>
    <div class="stat-box" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;"><?php echo $lang['total_posts'] ?? 'Total Posts'; ?></div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #8b5cf6;"><?php echo number_format($total_posts); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #6b7280;"><?php echo $board_count; ?> Boards</div>
    </div>
    <div class="stat-box" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;"><?php echo $lang['total_size'] ?? 'Storage Used'; ?></div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #f59e0b;"><?php echo formatSize($file_stats['total_size'] ?? 0); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #6b7280;"><?php echo $file_stats['count'] ?? 0; ?> Files</div>
    </div>
    <div class="stat-box" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;"><?php echo $lang['latest_backup'] ?? 'Latest Backup'; ?></div>
        <div style="font-size: 1.1rem; font-weight: 700; color: #3b82f6; margin-top: 0.25rem;">
            <?php echo $latest_backup ?? 'No Backups Found'; ?>
        </div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #6b7280;">Auto backup status: OK</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 3rem;">
    <!-- 최근 활동 로그 -->
    <div class="admin-card" style="margin: 0; padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">📋 <?php echo $lang['admin_logs'] ?? 'Recent Activity'; ?></h3>
            <a href="logs.php" style="font-size: 0.875rem; color: var(--primary-color); text-decoration: none;">View All →</a>
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 1px solid #e5e7eb; text-align: left;">
                    <th style="padding: 0.75rem 0.5rem; color: #6b7280;"><?php echo $lang['user'] ?? 'Admin'; ?></th>
                    <th style="padding: 0.75rem 0.5rem; color: #6b7280;"><?php echo $lang['action'] ?? 'Action'; ?></th>
                    <th style="padding: 0.75rem 0.5rem; color: #6b7280;"><?php echo $lang['datetime'] ?? 'Date'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_logs)): ?>
                    <tr><td colspan="3" style="padding: 2rem; text-align: center; color: #9ca3af;">No recent logs.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_logs as $log): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.75rem 0.5rem;"><strong><?php echo htmlspecialchars($log['mb_id']); ?></strong></td>
                            <td style="padding: 0.75rem 0.5rem;">
                                <span style="display: inline-block; padding: 0.2rem 0.6rem; background: #e0e7ff; color: #4338ca; border-radius: 10px; font-size: 0.75rem;">
                                    <?php echo htmlspecialchars($log['log_action']); ?>
                                </span>
                            </td>
                            <td style="padding: 0.75rem 0.5rem; color: #6b7280;"><?php echo date('H:i:s', strtotime($log['log_datetime'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 빠른 실행 메뉴 -->
    <div class="admin-card" style="margin: 0; padding: 1.5rem;">
        <h3 style="margin: 0 0 1.5rem 0;">⚡ Quick Access</h3>
        <div style="display: grid; gap: 0.75rem;">
            <a href="backup.php" class="quick-link">💾 Backup Database</a>
            <a href="file_manager.php" class="quick-link">📁 Clean Up Files</a>
            <a href="users.php" class="quick-link">👥 Manage Members</a>
            <a href="config.php" class="quick-link">⚙️ System Settings</a>
        </div>
        <style>
            .quick-link {
                display: block;
                padding: 0.75rem 1rem;
                background: var(--bg-secondary);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                text-decoration: none;
                color: var(--text-color);
                font-weight: 500;
                font-size: 0.9rem;
                transition: all 0.2s;
            }
            .quick-link:hover {
                background: var(--primary-color);
                color: white;
                border-color: var(--primary-color);
                transform: translateX(5px);
            }
        </style>
    </div>
</div>

<div class="admin-card">
    <h3 style="margin-top: 0; margin-bottom: 1.5rem;">🚀 <?php echo $lang['all_features'] ?? 'All Features'; ?></h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
        <?php
        $menus = [
            ['users.php', '👥', $lang['user_management']],
            ['board.php', '📋', $lang['board_management']],
            ['config.php', '⚙️', $lang['config_management']],
            ['pages.php', '📄', $lang['page_management'] ?? 'Pages'],
            ['oauth.php', '🔑', $lang['oauth_settings']],
            ['email_settings.php', '✉️', $lang['email_settings']],
            ['theme_settings.php', '🎨', $lang['theme_settings']],
            ['ip_ban.php', '🚫', $lang['ip_ban_management'] ?? 'IP Ban'],
            ['notice.php', '📢', $lang['notice_management'] ?? 'Notice'],
            ['reports.php', '🚨', $lang['report_management'] ?? 'Reports'],
            ['file_manager.php', '📁', $lang['file_manager']],
            ['logs.php', '📋', $lang['admin_logs']],
            ['backup.php', '💾', $lang['backup_restore']],
            ['seo.php', '🔍', $lang['seo_settings']],
        ];

        foreach ($menus as $m):
        ?>
        <a href="<?php echo $m[0]; ?>" style="text-decoration: none; color: inherit;">
            <div style="background: var(--bg-secondary); padding: 1.25rem; border-radius: var(--radius); border: 1px solid var(--border-color); text-align: center; transition: all 0.2s; height: 100%; box-sizing: border-box;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;"><?php echo $m[1]; ?></div>
                <div style="font-weight: 600; font-size: 0.9rem;"><?php echo $m[2]; ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

</main> <!-- admin-main end -->
</div> <!-- admin-layout end -->
<style>
    .admin-card a:hover > div {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
        background: white;
    }
</style>
</body>
</html>

</main> <!-- admin-main end -->
</div> <!-- admin-layout end -->
</body>
</html>
