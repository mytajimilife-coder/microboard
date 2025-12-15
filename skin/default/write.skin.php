<div class="content-wrapper">
<div class="write-form">
    <h2><?php echo $page_title; ?></h2>
    <form action="write.php?bo_table=<?php echo $bo_table; ?><?php echo $id ? '&id=' . $id : ''; ?>" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title"><?php echo $lang['subject']; ?></label>
            <input type="text" id="title" name="title" value="<?php echo $post['wr_subject']; ?>" required>
        </div>
        <div class="form-group">
            <label for="content"><?php echo $lang['content']; ?></label>
            <?php if ($use_editor ?? 1): ?>
            <textarea id="summernote" name="content"><?php echo $post['wr_content']; ?></textarea>
            <?php else: ?>
            <textarea id="content" name="content" rows="15" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-family: inherit; font-size: 1rem; resize: vertical;" required><?php echo $post['wr_content']; ?></textarea>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label><?php echo $lang['file_upload'] ?? 'File Upload'; ?></label>
            <input type="file" name="bf_file[]" multiple>
            <p class="help-block" style="font-size: 0.9em; color: var(--text-light); margin-top: 5px;"><?php echo $lang['file_upload_help'] ?? 'You can upload multiple files'; ?></p>
        </div>

        <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn"><?php echo $lang['save']; ?></button>
            <a href="list.php?bo_table=<?php echo $bo_table; ?>" class="btn btn-outline"><?php echo $lang['cancel']; ?></a>
        </div>
    </form>
</div>
</div>
