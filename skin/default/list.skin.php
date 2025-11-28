<h1><?php echo $board_config['bo_subject'] ?? $lang['board_list']; ?></h1>
<table>
  <tr>
    <th><?php echo $lang['number']; ?></th>
    <th><?php echo $lang['post_subject']; ?></th>
    <th><?php echo $lang['writer']; ?></th>
    <th><?php echo $lang['post_date']; ?></th>
    <th><?php echo $lang['view_count']; ?></th>
  </tr>
  <?php foreach ($list as $post): ?>
  <tr>
    <td><?php echo $post['num']; ?></td>
    <td>
      <a href="view.php?id=<?php echo $post['wr_id']; ?>&bo_table=<?php echo $bo_table; ?>">
        <?php echo htmlspecialchars($post['wr_subject']); ?>
      </a>
    </td>
    <td><?php echo htmlspecialchars($post['wr_name']); ?></td>
    <td><?php echo $post['wr_datetime']; ?></td>
    <td><?php echo $post['wr_hit']; ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<div class="btn-group">
  <a href="write.php?bo_table=<?php echo $bo_table; ?>" class="btn"><?php echo $lang['write_post']; ?></a>
  <a href="index.php" class="btn"><?php echo $lang['list']; ?></a>
</div>
