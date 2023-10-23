<?php

include_once __DIR__ . '/common.php';
include __DIR__ . '/dbConn.php';

// $sql = "SELECT `h`.`media_id`, `h`.`media_name`, `h`.`paper_code` AS `media_oid`, `h`.`category_id`, `h2`.`category_name`, `h`.`paper_category`, `h`.`kind_serial`, `h`.`mediaType`, `cfg`.`alias` AS `media_type_name`, `h`.`mapCode` AS `media_oid_map` FROM `hnp_category` AS `h` LEFT JOIN `hnp_category` AS `h2` ON `h`.`category_id` = `h2`.`category_id` LEFT JOIN `hnp_config` AS `cfg` ON `h`.`mediaType` = `cfg`.`value` WHERE `h`.`media_name` NOT IN ('글상자', '이미지', '') AND `h`.`mediaType` NOT IN (-98, -99) AND `h2`.`media_name` = '' AND `h2`.`mediaType` = -99 AND `cfg`.`fKey` = 'MEDIA_TYPE' ORDER BY `h`.`category_id`, `h`.`lsort`, `h`.`media_name`";
//AND `h2`.`paper_category` = '' AND `h2`.`kind_serial` = '' AND `h2`.`mediaType` = -99
$sql = "SELECT `media_id`, `media_name`, `paper_code` AS `media_oid`, `paper_category`, `kind_serial`, `mapCode` AS `media_oid_map`, `mediaCategory` AS `category_id`, `cate`.`alias` AS `category_name`, `mediaType`, `type`.`alias` AS `media_type_name`
FROM `hnp_category` AS `media`
LEFT JOIN `hnp_config` AS `cate` ON `media`.`mediaCategory` = `cate`.`value` AND `cate`.`fKey` = 'MEDIA_CATEGORY'
LEFT JOIN `hnp_config` AS `type` ON `media`.`mediaType` = `type`.`value` AND `type`.`fKey` = 'MEDIA_TYPE'
WHERE `media_name` NOT IN ('글상자', '이미지', '') AND `mediaType` NOT IN (-98, -99)
ORDER BY `category_id`, `lsort`, `media_name`";
mysqli_set_charset($db_conn, "utf8");
$result = mysqli_query($db_conn, $sql);
if (!$result) {
    $error = array(
        "success" => false,
        "notice_code" => "E001",
        "notice_message" => urlencode("Incorrect main-query request (잘못된 쿼리요청입니다.)"),
    );
    echo json_encode($error);
    exit;
}
$rsArray = array();
while ($row = mysqli_fetch_assoc($result)) {
    $rsArray[] = array(
        "media_id" => $row['media_id'],
        "media_name" => $row['media_name'],
        "media_oid"	=> $row['media_oid'],
        "category_id" => $row['category_id'],
        "category_name" => $row['category_name'],
        "paper_category" => $row['paper_category'],
        "kind_serial" => $row['kind_serial'],
        "media_type" => $row['mediaType'],
        "media_type_name" => $row['media_type_name'],
        "media_oid_map" => $row['media_oid_map']
    ); // 1-depth tree for media itself only
}

if ($rsArray == null) {
    $error = array(
        "success" => false,
        "notice_code" => "N000",
        "notice_message" => urlencode("표시할 데이터가 없습니다."),
    );
    echo json_encode($error);
    exit;
}
$rsArray = array_values($rsArray);

echo json_encode($rsArray);
