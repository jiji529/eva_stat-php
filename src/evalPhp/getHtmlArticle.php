<?php
include_once __DIR__ . '/common.php';
// https://view.scrapmaster.co.kr/getSslHtml.do?pid=hgjeon&newsId=12024

$url = $_REQUEST['url'];
$url = 'https://view.scrapmaster.co.kr/getSslHtml.do?pid=' . $_REQUEST['pid'] . '&newsId=' . $_REQUEST['nid'];
$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
);  
$contents =(file_get_contents($url, false, stream_context_create($arrContextOptions)));

//body 태그 css 삭제
$bodyCut = explode("body{",$contents);
$cutWidth = explode("}",$bodyCut[1]);
array_shift($cutWidth);
$addAgain = $bodyCut[0] . implode("}", $cutWidth);


//최대너비 600px 제한 css 삭제
$maxWidthCut = explode("style=\"max-width:600px;\"",$addAgain);
$addAgain = implode('', $maxWidthCut);

//너비 93 제한 css 삭제
$width93Cut = explode("width=\"93\"", $addAgain);
$addAgain = implode('', $width93Cut);

/*$textAlignLeftCut = explode("text-align:left;", $addAgain);

$addAgain = $textAlignLeftCut[0] . 'text-align:left;' . $textAlignLeftCut[1] . 'text-align:center;' . $textAlignLeftCut[2];*/

//이미지 가운데 정렬을 위해 이미지 태그에는 csCenter라는 클래스 추가
$imgCnt = substr_count($addAgain, "<a");
$styleClassAdd = str_replace("</style>" , ".csCenter{text-align:center;} </style>", $addAgain);
$styleClassAdd = str_replace("class=\"cs78FF31A0\"" , "class=\"cs78FF31A0 csCenter\"", $styleClassAdd);
if($imgCnt === 1) {
    $styleClassAdd = str_replace("<p class=\"cs95E872D0\"" , "<p class=\"cs95E872D0 csCenter\"", $styleClassAdd);
}
/*$styleClassAdd = str_replace("<p class=\"cs95E872D0\"" , "<p class=\"cs95E872D0 csCenter\"", $styleClassAdd);*/

$addAgain = $styleClassAdd;
echo $addAgain;


?>
