<div class="content-wrapper">
<div class="board-header">
    <h2><?php echo $board_config['bo_subject']; ?></h2>
    <div class="board-actions">
        <a href="write.php?bo_table=<?php echo $bo_table; ?>" class="btn"><?php echo $lang['write']; ?></a>
    </div>
</div>

<!-- 검색 폼 -->
<div class="search-form">
    <form action="list.php" method="get">
        <input type="hidden" name="bo_table" value="<?php echo htmlspecialchars($bo_table); ?>">
        <select name="sfl">
            <option value="wr_subject" <?php echo $sfl === 'wr_subject' ? 'selected' : ''; ?>><?php echo $lang['subject']; ?></option>
            <option value="wr_content" <?php echo $sfl === 'wr_content' ? 'selected' : ''; ?>><?php echo $lang['content']; ?></option>
            <option value="wr_name" <?php echo $sfl === 'wr_name' ? 'selected' : ''; ?>><?php echo $lang['writer']; ?></option>
        </select>
        <input type="text" name="stx" value="<?php echo htmlspecialchars($stx); ?>" placeholder="<?php echo $lang['search_term']; ?>">
        <button type="submit" class="btn btn-sm"><?php echo $lang['search']; ?></button>
    </form>
</div>

<table class="board-list">
    <thead>
        <tr>
            <th width="50"><?php echo $lang['num']; ?></th>
            <th><?php echo $lang['subject']; ?></th>
            <th width="100"><?php echo $lang['writer']; ?></th>
            <th width="150"><?php echo $lang['date']; ?></th>
            <th width="70"><?php echo $lang['hit']; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($list)): ?>
        <tr>
            <td colspan="5" class="empty-list"><?php echo $lang['no_posts']; ?></td>
        </tr>
        <?php else: ?>
            <?php foreach ($list as $post): ?>
            <tr>
                <td><?php echo $post['num']; ?></td>
                <td class="subject">
                    <a href="view.php?id=<?php echo $post['wr_id']; ?>&bo_table=<?php echo $bo_table; ?>">
                        <?php echo $post['wr_subject']; ?>
                    </a>
                </td>
                <td><?php echo $post['wr_name']; ?></td>
                <td><?php echo $post['wr_datetime']; ?></td>
                <td><?php echo $post['wr_hit']; ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- 페이지네이션 -->
<div class="pagination">
    <?php
    $qstr = '&bo_table=' . $bo_table . '&sfl=' . $sfl . '&stx=' . $stx;
    if ($page > 1) {
        echo '<a href="list.php?page=1' . $qstr . '" class="btn btn-sm">&lt;&lt;</a> ';
        echo '<a href="list.php?page=' . ($page - 1) . $qstr . '" class="btn btn-sm">&lt;</a> ';
    }
    
    $start_page = max(1, $page - 4);
    $end_page = min($total_pages, $page + 4);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = ($i == $page) ? 'active' : '';
        $style = ($i == $page) ? 'background: var(--primary-color); color: white;' : '';
        echo '<a href="list.php?page=' . $i . $qstr . '" class="btn btn-sm" style="' . $style . '">' . $i . '</a> ';
    }
    
    if ($page < $total_pages) {
        echo '<a href="list.php?page=' . ($page + 1) . $qstr . '" class="btn btn-sm">&gt;</a> ';
        echo '<a href="list.php?page=' . $total_pages . $qstr . '" class="btn btn-sm">&gt;&gt;</a>';
    }
    ?>
</div>
</div>
