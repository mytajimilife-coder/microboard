<?php
require_once 'config.php';
$page_title = isset($lang['home']) ? $lang['home'] : "MicroBoard";
require_once 'inc/header.php';
?>

<!-- 랜딩 히어로 섹션 -->
<div class="landing-hero">
    <div class="hero-content">
        <div style="margin-bottom: 1rem; display: inline-block;">
            <img src="img/logo.png" alt="MicroBoard Logo" style="width: 120px; height: auto; margin-bottom: 1rem; filter: drop-shadow(0 0 15px rgba(102, 126, 234, 0.5));" />
        </div>
        <h1 class="hero-title"><?php echo $lang['welcome_to_microboard']; ?></h1>
        <p class="hero-desc"><?php echo $lang['landing_description']; ?></p>
        
        <div class="hero-actions">
            <?php if (isLoggedIn()): ?>
                <a href="list.php" class="hero-btn btn-primary-pill">
                    🚀 <?php echo $lang['go_to_board']; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="hero-btn btn-primary-pill">
                    🔐 <?php echo $lang['login']; ?>
                </a>
                <a href="register.php" class="hero-btn btn-outline-pill">
                    📝 <?php echo $lang['register']; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 게시판 목록 섹션 -->
<?php
$db = getDB();
try {
    $boards = $db->query("SELECT * FROM mb1_board_config ORDER BY bo_subject ASC")->fetchAll();
} catch (Exception $e) {
    $boards = [];
}

if (!empty($boards)): 
?>
<div class="content-wrapper" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div class="section-header">
        <h2 class="section-title">
            📋 <?php echo isset($lang['board_list']) ? $lang['board_list'] : 'Boards'; ?>
        </h2>
        <div style="width: 50px; height: 4px; background: var(--primary-color); margin: 1rem auto; border-radius: 2px;"></div>
    </div>
    
    <div class="board-grid">
        <?php foreach ($boards as $board): ?>
        <div class="board-card">
            <div style="flex: 1;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1.5rem; color: var(--text-color);">
                    <a href="list.php?bo_table=<?php echo $board['bo_table']; ?>" style="color: inherit; text-decoration: none;">
                        <?php echo htmlspecialchars($board['bo_subject']); ?>
                    </a>
                </h3>
                <p style="color: var(--text-light); margin-bottom: 1.5rem; font-size: 0.95rem; line-height: 1.6;">
                    <?php echo !empty($board['bo_description']) ? htmlspecialchars($board['bo_description']) : sprintf($lang['join_discussion'] ?? 'Join the discussion in %s', htmlspecialchars($board['bo_subject'])); ?>
                </p>
            </div>
            <div style="margin-top: auto; padding-top: 1.5rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo isset($lang['admin_role']) ? $lang['admin_role'] : 'Admin'; ?>: <?php echo htmlspecialchars($board['bo_admin']); ?></span>
                <a href="list.php?bo_table=<?php echo $board['bo_table']; ?>" style="color: var(--primary-color); font-weight: 600; font-size: 0.9rem; text-decoration: none;">
                    <?php echo isset($lang['explore']) ? $lang['explore'] : 'Explore'; ?> &rarr;
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- 기능 소개 섹션 (Features) -->
<div class="features-section">
    <div class="content-wrapper">
        <div class="section-header">
            <span class="section-tag"><?php echo isset($lang['function']) ? $lang['function'] : 'Features'; ?></span>
            <h2 class="section-title"><?php echo $lang['why_microboard']; ?></h2>
            <p style="color: var(--text-light); font-size: 1.1rem; max-width: 600px; margin: 0 auto;"><?php echo $lang['microboard_desc']; ?></p>
        </div>
        
        <div class="feature-grid">
            <div class="feature-item">
                <div class="feature-icon">⚡</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-color);"><?php echo $lang['fast_performance']; ?></h3>
                <p style="color: var(--text-light); line-height: 1.6;"><?php echo $lang['fast_performance_desc']; ?></p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🎨</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-color);"><?php echo $lang['modern_design']; ?></h3>
                <p style="color: var(--text-light); line-height: 1.6;"><?php echo $lang['modern_design_desc']; ?></p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🛡️</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-color);"><?php echo $lang['secure']; ?></h3>
                <p style="color: var(--text-light); line-height: 1.6;"><?php echo $lang['secure_desc']; ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'inc/footer.php'; ?>
