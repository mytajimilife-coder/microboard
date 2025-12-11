<?php
require_once '../config.php';
requireLogin();

$username = $_SESSION['user'];

// 사용자 게시물 가져오기
$user_posts = getUserPosts($username);
$total_posts = count($user_posts);

// 사용자 댓글 가져오기
$user_comments = getUserComments($username);
$total_comments = count($user_comments);

// 페이지네이션 설정
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$posts_per_page = 10;
$comments_per_page = 10;

// 게시물 페이지네이션
$total_post_pages = ceil($total_posts / $posts_per_page);
$offset = ($page - 1) * $posts_per_page;
$paginated_posts = array_slice($user_posts, $offset, $posts_per_page);

// 댓글 페이지네이션
$total_comment_pages = ceil($total_comments / $comments_per_page);
$comment_offset = ($page - 1) * $comments_per_page;
$paginated_comments = array_slice($user_comments, $comment_offset, $comments_per_page);

// 페이지 제목 및 헤더 포함
$page_title = $lang['mypage'];
require_once '../inc/header.php';
?>

<style>
/* 마이페이지 전용 스타일 */
.mypage-header {
  background: var(--bg-color);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-color);
  padding: 2rem;
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 2rem;
  flex-wrap: wrap;
}

.profile-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: bold;
}

.profile-info {
  flex: 1;
}

.profile-info h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--secondary-color);
}

.profile-meta {
  display: flex;
  gap: 1.5rem;
  color: var(--text-light);
  font-size: 0.95rem;
}

.profile-meta span {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.mypage-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: var(--bg-color);
  border-radius: var(--radius);
  border: 1px solid var(--border-color);
  padding: 1.5rem;
  text-align: center;
  box-shadow: var(--shadow-sm);
  transition: var(--transition);
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: 0.5rem;
}

.stat-label {
  color: var(--text-light);
  font-size: 0.9rem;
}

.mypage-content {
  background: var(--bg-color);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.tab-header {
  display: flex;
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-color);
}

.tab-button {
  flex: 1;
  padding: 1.25rem 1rem;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-light);
  transition: var(--transition);
  border-bottom: 3px solid transparent;
}

.tab-button:hover {
  background: var(--bg-tertiary);
  color: var(--primary-color);
}

.tab-button.active {
  background: var(--bg-color);
  color: var(--primary-color);
  border-bottom-color: var(--primary-color);
}

.tab-content {
  display: none;
  padding: 2rem;
}

.tab-content.active {
  display: block;
}

.activity-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.activity-item {
  padding: 1rem 0;
  border-bottom: 1px solid var(--border-color);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.activity-title a {
  text-decoration: none;
  color: var(--text-color);
  transition: color 0.2s;
}

.activity-title a:hover {
  color: var(--primary-color);
}

.activity-meta {
  font-size: 0.85rem;
  color: var(--text-light);
  display: flex;
  gap: 1rem;
}

.activity-preview {
  margin-top: 0.5rem;
  color: var(--text-muted);
  font-size: 0.95rem;
  line-height: 1.5;
}

.action-btn {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: var(--radius);
  color: var(--danger-color);
  border: 1px solid var(--danger-color);
  text-decoration: none;
  font-size: 0.9rem;
  transition: var(--transition);
}

.action-btn:hover {
  background: var(--danger-color);
  color: white;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--text-light);
}

.empty-state p {
  margin-bottom: 1.5rem;
  font-size: 1.1rem;
}

/* 페이지네이션 스타일 */
.pagination {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 2rem;
}

.page-link {
  min-width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius);
  text-decoration: none;
  color: var(--text-color);
  font-weight: 500;
  border: 1px solid var(--border-color);
  transition: var(--transition);
}

.page-link:hover {
  background: var(--bg-secondary);
  border-color: var(--border-color);
}

.page-link.current {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}
</style>

<div class="content-wrapper">
    <div class="mypage-header">
        <div class="profile-avatar">
            <?php echo strtoupper(mb_substr($username, 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($username); ?><?php echo $lang['user_suffix']; ?></h2>
            <div class="profile-meta">
                <?php 
                $member_info = getMemberInfo($username);
                $level = $member_info['mb_level'] ?? 1;
                $point = isset($member_info['mb_point']) ? number_format($member_info['mb_point']) : 0;
                ?>
                <span>🎖️ <?php echo $lang['member_level']; ?> Lv.<?php echo $level; ?></span>
                
                <?php 
                $config = get_config();
                if (isset($config['cf_use_point']) && $config['cf_use_point']): 
                ?>
                <span title="<?php echo $lang['point']; ?>">💰 <?php echo $point; ?> P</span>
                <?php endif; ?>
                
                <span>📅 <?php echo $lang['join_date']; ?>: <?php echo substr($member_info['mb_datetime'] ?? '-', 0, 10); ?></span>
            </div>
        </div>
        <a href="withdraw.php" class="action-btn" onclick="return confirm('<?php echo $lang['withdraw_confirm_title']; ?>');">
            🚫 <?php echo $lang['withdraw_account']; ?>
        </a>
        <a href="2fa_settings.php" class="action-btn" style="margin-left: 1rem;">
            🔐 <?php echo $lang['2fa_settings'] ?? '2FA Settings'; ?>
        </a>
    </div>

    <div class="mypage-stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($total_posts); ?></div>
            <div class="stat-label"><?php echo $lang['posts_written']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($total_comments); ?></div>
            <div class="stat-label"><?php echo $lang['comments_written']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number">0</div>
            <div class="stat-label"><?php echo $lang['followers']; ?></div>
        </div>
    </div>

    <div class="mypage-content">
        <div class="tab-header">
            <button class="tab-button <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'posts') ? 'active' : ''; ?>" onclick="location.href='?tab=posts'">
                📝 <?php echo $lang['posts_written']; ?>
            </button>
            <button class="tab-button <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'comments') ? 'active' : ''; ?>" onclick="location.href='?tab=comments'">
                💬 <?php echo $lang['comments_written']; ?>
            </button>
        </div>

        <!-- 게시글 탭 -->
        <div class="tab-content <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'posts') ? 'active' : ''; ?>">
            <?php if ($paginated_posts): ?>
                <ul class="activity-list">
                    <?php foreach ($paginated_posts as $post): ?>
                    <li class="activity-item">
                        <div class="activity-title">
                            <a href="../view.php?id=<?php echo $post['wr_id']; ?>">
                                <?php echo htmlspecialchars($post['wr_subject']); ?>
                            </a>
                        </div>
                        <div class="activity-meta">
                            <span>📅 <?php echo date('Y.m.d H:i', strtotime($post['wr_datetime'])); ?></span>
                            <span>👁️ <?php echo (int)$post['wr_hit']; ?></span>
                        </div>
                        <div class="activity-preview">
                            <?php 
                              $content = strip_tags($post['wr_content']);
                              echo mb_substr($content, 0, 100) . (mb_strlen($content) > 100 ? '...' : '');
                            ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($total_post_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_post_pages; $i++): ?>
                        <a href="?tab=posts&page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'current' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>📭 <?php echo $lang['no_posts']; ?></p>
                    <a href="../write.php" class="btn"><?php echo $lang['write_new_post']; ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- 댓글 탭 -->
        <div class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'comments') ? 'active' : ''; ?>">
            <?php if ($paginated_comments): ?>
                <ul class="activity-list">
                    <?php foreach ($paginated_comments as $comment): ?>
                    <li class="activity-item">
                        <div class="activity-meta" style="margin-bottom: 0.5rem;">
                            <span>🔗 <?php echo $lang['board']; ?>: 
                                <a href="../view.php?id=<?php echo $comment['post_id']; ?>" style="color: var(--primary-color);">
                                    <?php echo htmlspecialchars($comment['post_title']); ?>
                                </a>
                            </span>
                            <span>📅 <?php echo date('Y.m.d H:i', strtotime($comment['created_at'])); ?></span>
                        </div>
                        <div class="activity-preview" style="color: var(--text-color);">
                            <?php echo htmlspecialchars(mb_substr($comment['content'], 0, 100)); ?>
                            <?php echo mb_strlen($comment['content']) > 100 ? '...' : ''; ?>
                        </div>
                        <div style="margin-top: 0.5rem;">
                             <a href="../view.php?id=<?php echo $comment['post_id']; ?>" class="btn btn-sm btn-outline">
                                 <?php echo $lang['view_post']; ?>
                             </a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($total_comment_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_comment_pages; $i++): ?>
                        <a href="?tab=comments&page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'current' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>💬 <?php echo $lang['no_comments']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../inc/footer.php'; ?>
