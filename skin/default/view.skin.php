<h1><?php echo $board_config['bo_subject'] ?? $lang['board']; ?></h1>
<p><?php echo $lang['post_subject']; ?>: <?php echo $post['wr_subject']; ?></p>
<div class="content">
  <?php 
  // HTML 내용 필터링 (XSS 방지)
  $allowed_tags = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6>';
  echo strip_tags($post['wr_content'], $allowed_tags); 
  ?>
</div>
<div class="btn-group">
  <a href="write.php?id=<?php echo $post['wr_id']; ?>" class="btn"><?php echo $lang['edit']; ?></a>
  <a href="view.php?action=delete&id=<?php echo $post['wr_id']; ?>&token=<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" 
     class="btn" onclick="return confirm('<?php echo $lang['delete_confirm_short']; ?>')"><?php echo $lang['delete']; ?></a>
  <a href="index.php?bo_table=<?php echo $bo_table; ?>" class="btn"><?php echo $lang['list']; ?></a>
</div>
