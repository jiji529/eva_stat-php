<?php
include_once __DIR__ . '/common.php';
include __DIR__ . '/dbConn.php';

$category_names = array();
$query = "SELECT DISTINCT `category_name` FROM `hnp_news` WHERE `category_name` != '' AND NOT `category_name` IS NULL";
mysqli_set_charset($db_conn, 'utf8');
$result = mysqli_query($db_conn, $query);
if (!$result) {
  header($_SERVER['SERVER_PROTOCOL'].' 500');
  die(json_encode(array('success' => false)));
}
while ($row = mysqli_fetch_assoc($result)) {
  $category_names[] = $row['category_name'];
}
echo json_encode($category_names);
?>
