<?php
require_once 'config.php';
// requireLogin(); // Landing page is public

$page_title = "Welcome";
require_once 'inc/header.php';
?>
<link rel="stylesheet" href="skin/default/style.css">

<div class="landing-hero">
    <div class="hero-content">
        <h1><?php echo $lang['welcome_to_microboard']; ?></h1>
        <p><?php echo $lang['landing_description']; ?></p>
        
        <div class="hero-actions">
            <?php if (isLoggedIn()): ?>
                <a href="list.php" class="btn btn-large"><?php echo $lang['go_to_board']; ?></a>
            <?php else: ?>
                <a href="login.php" class="btn btn-large"><?php echo $lang['login']; ?></a>
                <a href="register.php" class="btn btn-large btn-outline"><?php echo $lang['register']; ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'inc/footer.php'; ?>
