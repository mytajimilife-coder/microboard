<?php
define('IN_ADMIN', true);
require_once 'common.php';
?>

<div class="admin-card">
    <h2 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color);">👋 <?php echo $lang['welcome']; ?></h2>
    <p style="font-size: 1.1rem; color: var(--text-color); margin-bottom: 2rem;">
        <strong><?php echo $_SESSION['user']; ?></strong>님, <?php echo $lang['admin_page_desc']; ?>
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
        <a href="users.php" style="text-decoration: none; color: inherit;">
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: var(--radius); border: 1px solid var(--border-color); text-align: center; transition: transform 0.2s; box-shadow: var(--shadow-sm);">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">👥</div>
                <div style="font-weight: 600; font-size: 1.1rem;"><?php echo $lang['user_management']; ?></div>
            </div>
        </a>
        <a href="board.php" style="text-decoration: none; color: inherit;">
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: var(--radius); border: 1px solid var(--border-color); text-align: center; transition: transform 0.2s; box-shadow: var(--shadow-sm);">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📋</div>
                <div style="font-weight: 600; font-size: 1.1rem;"><?php echo $lang['board_management']; ?></div>
            </div>
        </a>
        <a href="config.php" style="text-decoration: none; color: inherit;">
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: var(--radius); border: 1px solid var(--border-color); text-align: center; transition: transform 0.2s; box-shadow: var(--shadow-sm);">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚙️</div>
                <div style="font-weight: 600; font-size: 1.1rem;"><?php echo $lang['config_management']; ?></div>
            </div>
        </a>
        <a href="oauth.php" style="text-decoration: none; color: inherit;">
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: var(--radius); border: 1px solid var(--border-color); text-align: center; transition: transform 0.2s; box-shadow: var(--shadow-sm);">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔑</div>
                <div style="font-weight: 600; font-size: 1.1rem;"><?php echo $lang['oauth_settings']; ?></div>
            </div>
        </a>
        <a href="email_settings.php" style="text-decoration: none; color: inherit;">
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: var(--radius); border: 1px solid var(--border-color); text-align: center; transition: transform 0.2s; box-shadow: var(--shadow-sm);">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">✉️</div>
                <div style="font-weight: 600; font-size: 1.1rem;"><?php echo $lang['email_settings'] ?? 'Email Settings'; ?></div>
            </div>
        </a>
    </div>
</div>

</main> <!-- admin-main end -->
</div> <!-- admin-layout end -->
</body>
</html>
