<?php
define('IN_ADMIN', true);
require_once 'common.php';
?>
<h1><?php echo $admin_title; ?></h1>
<p><?php echo $lang['welcome']; ?>, <?php echo $_SESSION['user']; ?>! <?php echo $lang['admin_page_desc']; ?></p>
</body>
</html>
