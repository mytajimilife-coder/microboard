<?php
require_once '../config.php';
requireLogin();

$username = $_SESSION['user'];

// 내가 쓴 글 조회
$user_posts = getUserPosts($username);

// 내가 쓴 댓글 조회 (기본 구조만 제공, 댓글 기능이 구현되지 않은 경우를 대비)
$user_comments = getUserComments($username);

// 페이지네이션 처리
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = 10;
$comments_per_page = 10;

// 게시물 페이지네이션
$total_posts = count($user_posts);
$total_post_pages = ceil($total_posts / $posts_per_page);
$offset = ($page - 1) * $posts_per_page;
$paginated_posts = array_slice($user_posts, $offset, $posts_per_page);

// 댓글 페이지네이션
$total_comments = count($user_comments);
$total_comment_pages = ceil($total_comments / $comments_per_page);
$comment_offset = ($page - 1) * $comments_per_page;
$paginated_comments = array_slice($user_comments, $comment_offset, $comments_per_page);
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo $lang['mypage']; ?> - MicroBoard</title>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="../skin/default/style.css">
  <style>
    .mypage-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    .user-info {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      border: 1px solid #dee2e6;
    }
    .user-info h2 {
      margin: 0 0 10px 0;
      color: #333;
    }
    .user-stats {
      display: flex;
      gap: 30px;
      margin-top: 15px;
    }
    .stat-item {
      text-align: center;
    }
    .stat-number {
      font-size: 24px;
      font-weight: bold;
      color: #007bff;
    }
    .stat-label {
      font-size: 14px;
      color: #666;
    }
    .tab-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    .tab-header {
      display: flex;
      background: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
    }
    .tab-button {
      flex: 1;
      padding: 15px 20px;
      background: none;
      border: none;
      cursor: pointer;
      font-size: 16px;
      font-weight: 500;
      color: #666;
      transition: all 0.3s ease;
    }
    .tab-button:hover {
      background: #e9ecef;
    }
    .tab-button.active {
      background: white;
      color: #007bff;
      border-bottom: 3px solid #007bff;
    }
    .tab-content {
      display: none;
      padding: 30px;
    }
    .tab-content.active {
      display: block;
    }
    .post-list, .comment-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .post-item, .comment-item {
      border-bottom: 1px solid #eee;
      padding: 15px 0;
      transition: background-color 0.2s ease;
    }
    .post-item:hover, .comment-item:hover {
      background-color: #f9f9f9;
    }
    .post-item:last-child, .comment-item:last-child {
      border-bottom: none;
    }
    .post-title {
      font-size: 18px;
      font-weight: 600;
      margin: 0 0 8px 0;
      color: #333;
    }
    .post-title a {
      color: #333;
      text-decoration: none;
    }
    .post-title a:hover {
      color: #007bff;
      text-decoration: underline;
    }
    .post-meta {
      font-size: 14px;
      color: #666;
      margin-bottom: 8px;
    }
    .post-content {
      font-size: 14px;
      line-height: 1.5;
      color: #888;
      margin-bottom: 10px;
    }
    .post-actions {
      display: flex;
      gap: 10px;
    }
    .post-actions .btn {
      padding: 5px 12px;
      font-size: 12px;
      border-radius: 4px;
    }
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 30px;
      gap: 5px;
    }
    .pagination a, .pagination span {
      padding: 8px 12px;
      border: 1px solid #ddd;
      text-decoration: none;
      color: #007bff;
      border-radius: 4px;
    }
    .pagination a:hover {
      background: #f0f0f0;
    }
    .pagination .current {
      background: #007bff;
      color: white;
      border-color: #007bff;
    }
    .empty-message {
      text-align: center;
      padding: 40px;
      color: #888;
      font-style: italic;
    }
  </style>
</head>
<body>
  <div class="mypage-container">
    <div class="user-info">
      <h2><?php echo htmlspecialchars($username); ?>님의 <?php echo $lang['mypage']; ?></h2>
      <div class="user-stats">
        <div class="stat-item">
          <div class="stat-number"><?php echo $total_posts; ?></div>
          <div class="stat-label"><?php echo $lang['posts_written']; ?></div>
        </div>
        <div class="stat-item">
          <div class="stat-number"><?php echo $total_comments; ?></div>
          <div class="stat-label"><?php echo $lang['comments_written']; ?></div>
        </div>
        <div class="stat-item">
          <div class="stat-number">0</div>
          <div class="stat-label"><?php echo $lang['followers']; ?></div>
        </div>
      </div>
    </div>

        <div class="tab-container">
      <div class="tab-header">
        <button class="tab-button active" onclick="showTab('posts')"><?php echo $lang['posts_written']; ?></button>
        <button class="tab-button" onclick="showTab('comments')"><?php echo $lang['comments_written']; ?></button>
      </div>

      <div id="posts" class="tab-content active">
        <h3><?php echo $lang['posts_written']; ?> (<?php echo $total_posts; ?>)</h3>
        <?php if ($paginated_posts): ?>
          <ul class="post-list">
            <?php foreach ($paginated_posts as $post): ?>
              <li class="post-item">
                <h4 class="post-title">
                  <a href="../view.php?id=<?php echo $post['wr_id']; ?>">
                    <?php echo htmlspecialchars($post['wr_subject']); ?>
                  </a>
                </h4>
                <div class="post-meta">
                  <span><?php echo $lang['post_date']; ?>: <?php echo htmlspecialchars($post['wr_datetime']); ?></span>
                  <span style="margin-left: 20px;"><?php echo $lang['post_hits']; ?>: <?php echo (int)$post['wr_hit']; ?></span>
                </div>
                <div class="post-content">
                  <?php 
                    $content = strip_tags($post['wr_content']);
                    echo mb_substr($content, 0, 100) . (mb_strlen($content) > 100 ? '...' : '');
                  ?>
                </div>
                <div class="post-actions">
                  <a href="../view.php?id=<?php echo $post['wr_id']; ?>" class="btn"><?php echo $lang['read_more']; ?></a>
                  <a href="../write.php?id=<?php echo $post['wr_id']; ?>" class="btn"><?php echo $lang['modify']; ?></a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
          
          <?php if ($total_post_pages > 1): ?>
            <div class="pagination">
              <?php for ($i = 1; $i <= $total_post_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'current' : ''; ?>">
                  <?php echo $i; ?>
                </a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="empty-message">
            <p><?php echo $lang['no_posts']; ?></p>
            <a href="../write.php" style="color: #007bff;"><?php echo $lang['write_new_post']; ?></a>
          </div>
        <?php endif; ?>
      </div>

      <div id="comments" class="tab-content">
        <h3><?php echo $lang['comments_written']; ?> (<?php echo $total_comments; ?>)</h3>
        <?php if ($paginated_comments): ?>
          <ul class="comment-list">
            <?php foreach ($paginated_comments as $comment): ?>
              <li class="comment-item">
                <div class="post-meta">
                  <span>게시물: <a href="../view.php?id=<?php echo $comment['post_id']; ?>"><?php echo htmlspecialchars($comment['post_title']); ?></a></span>
                  <span style="margin-left: 20px;">작성일: <?php echo htmlspecialchars($comment['created_at']); ?></span>
                </div>
                <div class="post-content">
                  <?php echo htmlspecialchars(mb_substr($comment['content'], 0, 100)); ?>
                  <?php echo mb_strlen($comment['content']) > 100 ? '...' : ''; ?>
                </div>
                <div class="post-actions">
                  <a href="../view.php?id=<?php echo $comment['post_id']; ?>" class="btn">게시물 보기</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
          
          <?php if ($total_comment_pages > 1): ?>
            <div class="pagination">
              <?php for ($i = 1; $i <= $total_comment_pages; $i++): ?>
                <a href="?tab=comments&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'current' : ''; ?>">
                  <?php echo $i; ?>
                </a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="empty-message">
            <p><?php echo $lang['no_comments']; ?></p>
            <p><?php echo $lang['comments_coming_soon']; ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    function showTab(tabName) {
      // 모든 탭 버튼과 콘텐츠 초기화
      document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
      
      // 선택된 탭 활성화
      event.target.classList.add('active');
      document.getElementById(tabName).classList.add('active');
    }
  </script>
</body>
</html>
