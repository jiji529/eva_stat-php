<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-12-07
 * Time: 오후 3:57
 * 지면 로고 온라인 로고 다운로드
 */
header('Content-Type: text/html; charset=UTF-8');
$user_id = $_REQUEST['u'];
$paper_name = urldecode($_REQUEST['q']);
$paper_type = $_REQUEST['t'];
if (!$user_id) {
    header("HTTP/1.0 404 Not Found");
    die();
}
$logo_url = getPaperNewsLogo($paper_name);
if (!$logo_url || $paper_type === '1') {
    $logo_url = getOnlineNewsLogo($paper_name);
}

if ($logo_url) {
    $filesize = filesize($logo_url);
    $path_parts = pathinfo($logo_url);
    $filename = $path_parts['basename'];
    $extension = $path_parts['extension'];

    header("Pragma: public");
    header("Expires: 0");
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename='$filename'");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: $filesize");

    ob_clean();
    flush();
    readfile($logo_url);
    exit;
}
header("HTTP/1.0 404 Not Found");
die();

function getPaperNewsLogo($paper_name)
{
    $url = "https://sm3.scrapmaster.co.kr/prescrap/media_list.php";
    $xml = simplexml_load_string(file_get_contents($url));

    foreach ($xml->Paper AS $p) {
        if ($p->paper_name == $paper_name) {
            return "https://filek1.scrapmaster.co.kr/SM3_Logo/{$p->paper_serial}.png";
        }
    }
    return false;
}

function getOnlineNewsLogo($paper_name)
{
    global $user_id;
    $aef_ini = file_get_contents("https://filek1.scrapmaster.co.kr/download_ext.php?f=AEFlogo.ini&uid={$user_id}&service=aef&dummy=2f3f432b54414f28b2765bd56482032e");
    $aef_array = explode("\r\n", $aef_ini);

    foreach ($aef_array as $aef) {
        $aef = explode("◆◆◆", $aef);
        if ($aef[1]) {
            $title_array = explode("=", $aef[0]);

            $title = $title_array[0];
            if ($title == $paper_name) {
                return $aef[1];
            }
        }
    }
    return false;


}

//http://filek1.scrapmaster.co.kr/download_ext.php?f=AEFlogo.ini&uid=serverteam&service=aef&dummy=2f3f432b54414f28b2765bd56482032e

//http://filek1.scrapmaster.co.kr/download_ext.php?service=aef&uid=dauth&f=Logo/betanews.jpg
