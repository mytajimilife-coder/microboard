<style>
.post-view {
  background: var(--bg-color);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-color);
  overflow: hidden;
  margin-bottom: 2rem;
}

.post-header {
  padding: 2rem;
  border-bottom: 2px solid var(--border-color);
  background: var(--bg-secondary);
}

.post-header h2 {
  margin: 0 0 1rem 0;
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--secondary-color);
  line-height: 1.4;
}

.post-meta {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  font-size: 0.9rem;
  color: var(--text-light);
}

.post-meta span {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.post-meta .writer {
  font-weight: 600;
  color: var(--primary-color);
}

.post-meta .writer::before {
  content: '👤';
}

.post-meta .date::before {
  content: '📅';
}

.post-meta .hit::before {
  content: '👁️';
}

.post-content {
  padding: 2rem;
  line-height: 1.8;
  color: var(--text-color);
  font-size: 1rem;
  min-height: 200px;
  overflow-wrap: break-word; /* 긴 단어 줄바꿈 */
}

/* 에디터 콘텐츠 스타일 호환성 */
.post-content p {
  margin-bottom: 1rem;
}

.post-content h1, .post-content h2, .post-content h3, 
.post-content h4, .post-content h5, .post-content h6 {
  margin-top: 1.5rem;
  margin-bottom: 1rem;
  font-weight: 700;
  color: var(--secondary-color);
  line-height: 1.3;
}

.post-content h1 { font-size: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; }
.post-content h2 { font-size: 1.75rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; }
.post-content h3 { font-size: 1.5rem; }
.post-content h4 { font-size: 1.25rem; }

.post-content ul, .post-content ol {
  margin-bottom: 1rem;
  padding-left: 2rem;
}

.post-content ul { list-style-type: disc; }
.post-content ol { list-style-type: decimal; }

.post-content li {
  margin-bottom: 0.25rem;
}

.post-content table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1rem;
  border: 1px solid var(--border-color);
}

.post-content th, .post-content td {
  border: 1px solid var(--border-color);
  padding: 0.75rem;
  text-align: left;
}

.post-content th {
  background-color: var(--bg-secondary);
  font-weight: 600;
}

.post-content blockquote {
  margin: 1rem 0;
  padding: 1rem 1.5rem;
  background: var(--bg-secondary);
  border-left: 4px solid var(--primary-color);
  color: var(--text-muted);
  font-style: italic;
}

.post-content pre {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: var(--radius);
  overflow-x: auto;
  margin-bottom: 1rem;
  font-family: 'Courier New', Courier, monospace;
  border: 1px solid var(--border-color);
}

.post-content code {
  background: #f1f3f5;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.9em;
  color: #e83e8c;
}

.post-content a {
  color: var(--primary-color);
  text-decoration: underline;
}

.post-content hr {
  margin: 2rem 0;
  border: 0;
  border-top: 1px solid var(--border-color);
}

.post-content img {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius);
  margin: 1rem 0;
  box-shadow: var(--shadow-sm);
}

.post-files {
  margin: 2rem;
  padding: 1.5rem;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
}

.post-files h3 {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--secondary-color);
}

.post-files ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.post-files li {
  padding: 0.75rem;
  margin-bottom: 0.5rem;
  background: var(--bg-color);
  border-radius: var(--radius);
  border: 1px solid var(--border-color);
  transition: var(--transition);
}

.post-files li:hover {
  background: var(--bg-tertiary);
  transform: translateX(4px);
}

.post-files a {
  text-decoration: none;
  color: var(--text-color);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
}

.post-files a:hover {
  color: var(--primary-color);
}

.file-size {
  color: var(--text-light);
  font-size: 0.85rem;
  margin-left: auto;
}

.post-actions {
  padding: 1.5rem 2rem;
  background: var(--bg-secondary);
  border-top: 1px solid var(--border-color);
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-danger {
  background: var(--danger-color);
  color: white !important;
}

.btn-danger:hover {
  background: #dc2626;
}

.comments-section {
  margin-top: 2rem;
  padding: 2rem;
  background: var(--bg-color);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-color);
}

.comments-section h3 {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--secondary-color);
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--primary-color);
}

.comment-list {
  margin-bottom: 2rem;
}

.comment-item {
  padding: 1.25rem;
  margin-bottom: 1rem;
  background: var(--bg-secondary);
  border-radius: var(--radius);
  border: 1px solid var(--border-color);
  transition: var(--transition);
}

.comment-item:hover {
  box-shadow: var(--shadow-sm);
}

.comment-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
  font-size: 0.9rem;
  flex-wrap: wrap;
}

.comment-meta strong {
  color: var(--primary-color);
  font-weight: 600;
}

.comment-meta .date {
  color: var(--text-light);
}

.comment-delete {
  border: none;
  background: none;
  color: var(--danger-color);
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  transition: var(--transition);
}

.comment-delete:hover {
  background: rgba(239, 68, 68, 0.1);
}

.comment-content {
  color: var(--text-color);
  line-height: 1.6;
  white-space: pre-wrap;
}

.comment-form {
  padding: 1.5rem;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
}

.comment-form textarea {
  width: 100%;
  min-height: 100px;
  padding: 1rem;
  border: 2px solid var(--border-color);
  border-radius: var(--radius);
  font-family: inherit;
  font-size: 0.95rem;
  resize: vertical;
  transition: var(--transition);
  background: var(--bg-color);
  color: var(--text-color);
}

.comment-form textarea:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  outline: none;
}

.comment-form textarea::placeholder {
  color: var(--text-muted);
}

.comment-submit {
  margin-top: 1rem;
  text-align: right;
}

@media (max-width: 768px) {
  .post-header {
    padding: 1.5rem;
  }
  
  .post-header h2 {
    font-size: 1.5rem;
  }
  
  .post-content {
    padding: 1.5rem;
  }
  
  .post-meta {
    gap: 1rem;
  }
  
  .post-actions {
    padding: 1rem 1.5rem;
  }
  
  .comments-section {
    padding: 1.5rem;
  }
}
</style>

<div class="post-view">
    <div class="post-header">
        <h2><?php echo $post_view['wr_subject']; ?></h2>
        <div class="post-meta">
            <span class="writer"><?php echo $post_view['wr_name']; ?></span>
            <span class="date"><?php echo $post['wr_datetime']; ?></span>
            <span class="hit"><?php echo $lang['hit']; ?> <?php echo $post['wr_hit']; ?></span>
        </div>
    </div>

    <div class="post-content">
        <?php echo $post_view['wr_content']; ?>
    </div>

    <!-- 첨부파일 -->
    <?php if (!empty($files)): ?>
    <div class="post-files">
        <h3>📎 <?php echo $lang['attachments']; ?></h3>
        <ul>
            <?php foreach ($files as $file): ?>
            <li>
                <a href="download.php?bo_table=<?php echo htmlspecialchars($bo_table); ?>&bf_no=<?php echo $file['bf_no']; ?>">
                    <span>📁 <?php echo $file['bf_source']; ?></span>
                    <span class="file-size"><?php echo number_format($file['bf_filesize']); ?> bytes</span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="post-actions">
        <a href="list.php?bo_table=<?php echo htmlspecialchars($bo_table); ?>" class="btn btn-outline"><?php echo $lang['list']; ?></a>
        <?php if (isLoggedIn() && ($_SESSION['user'] === $post['wr_name'] || isAdmin())): ?>
        <a href="write.php?w=u&id=<?php echo $post['wr_id']; ?>&bo_table=<?php echo htmlspecialchars($bo_table); ?>" class="btn"><?php echo $lang['edit']; ?></a>
        <a href="view.php?id=<?php echo $post['wr_id']; ?>&action=delete&bo_table=<?php echo htmlspecialchars($bo_table); ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
           class="btn btn-danger" 
           onclick="return confirm('<?php echo $lang['delete_confirm']; ?>');"><?php echo $lang['delete']; ?></a>
        <?php endif; ?>
    </div>
</div>

<!-- 댓글 -->
<div class="comments-section">
    <h3>💬 <?php echo $lang['comments']; ?> (<?php echo count($comments); ?>)</h3>
    
    <!-- 댓글 목록 -->
    <div class="comment-list">
        <?php if (empty($comments)): ?>
            <p style="text-align: center; color: var(--text-light); padding: 2rem;">
                <?php echo $lang['no_comments'] ?? '첫 댓글을 작성해보세요!'; ?>
            </p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
            <div class="comment-item">
                <div class="comment-meta">
                    <strong><?php echo htmlspecialchars($comment['co_name']); ?></strong>
                    <span class="date"><?php echo $comment['co_datetime']; ?></span>
                    <?php if (isLoggedIn() && ($_SESSION['user'] === $comment['co_name'] || isAdmin())): ?>
                    <form action="comment_update.php" method="post" style="display: inline; margin-left: auto;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="bo_table" value="<?php echo htmlspecialchars($bo_table); ?>">
                        <input type="hidden" name="wr_id" value="<?php echo $post['wr_id']; ?>">
                        <input type="hidden" name="co_id" value="<?php echo $comment['co_id']; ?>">
                        <button type="submit" class="comment-delete" onclick="return confirm('<?php echo $lang['delete_confirm']; ?>');">
                            🗑️ <?php echo $lang['delete']; ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="comment-content">
                    <?php echo nl2br(htmlspecialchars($comment['co_content'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 댓글 작성 폼 -->
    <?php if (isLoggedIn()): ?>
    <div class="comment-form">
        <form action="comment_update.php" method="post">
            <input type="hidden" name="action" value="insert">
            <input type="hidden" name="bo_table" value="<?php echo htmlspecialchars($bo_table); ?>">
            <input type="hidden" name="wr_id" value="<?php echo $post['wr_id']; ?>">
            <textarea name="co_content" required placeholder="<?php echo $lang['comment_placeholder'] ?? '댓글을 입력하세요...'; ?>"></textarea>
            <div class="comment-submit">
                <button type="submit" class="btn"><?php echo $lang['submit_comment'] ?? '댓글 작성'; ?></button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 2rem; background: var(--bg-secondary); border-radius: var(--radius); border: 1px solid var(--border-color);">
        <p style="margin: 0; color: var(--text-light);">
            <?php echo $lang['login_required_comment'] ?? '댓글을 작성하려면 로그인이 필요합니다.'; ?>
            <a href="login.php" style="color: var(--primary-color); font-weight: 600; margin-left: 0.5rem;"><?php echo $lang['login']; ?></a>
        </p>
    </div>
    <?php endif; ?>
</div>
