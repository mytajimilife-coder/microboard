<?php
require_once './config.php';
$db = getDB();

try {
    $stmt = $db->query("DESCRIBE mb1_board_config");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n";
    
    if (in_array('bo_plugins', $columns)) {
        echo "bo_plugins column exists.\n";
    } else {
        echo "bo_plugins column MISSING.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
