<h1><?php echo $lang['create_new_post']; ?></h1>
<form method="post">
  <div>
    <label><?php echo $lang['post_subject']; ?>:</label>
    <input type="text" name="title" value="<?php echo htmlspecialchars($post['wr_subject'] ?? ''); ?>" required>
  </div>
  <div>
    <label><?php echo $lang['content']; ?>:</label>
    <div id="summernote"><?php echo htmlspecialchars($post['wr_content'] ?? ''); ?></div>
    <textarea name="content" style="display:none;"></textarea>
  </div>
  <button type="submit" class="btn"><?php echo $lang['save']; ?></button>
</form>
<script>
$(document).ready(function() {
  $('#summernote').summernote({
    height: 300,
    // Summernote 옵션 ...
  });
  $('form').on('submit', function() {
    $('textarea[name="content"]').val($('#summernote').summernote('code'));
  });
});
</script>
