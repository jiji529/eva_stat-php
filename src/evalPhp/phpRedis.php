<?php
/**
 * User: cmlim
 * Date: 2023-10-25
 * Time: 오전 11:35
 */
/* Session Env */
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://redis.scrapmaster.co.kr');

session_cache_expire(14400);

/* 세션 생성 및 선언 */
session_start();

function setRedisSessionData($params) {
    if (!session_id() 
        || is_null($params) || !is_array($params))
        return false;
        
    /* set User Info */
    foreach ($params as $key => $value) {
        $_SESSION[$key] = $value;
    }
    
    /* set timeout = 1 hour */
    if (isset($_SESSION['EMBEDDED']) || $_SESSION['EMBEDDED'] != null) {
        $_SESSION['LIFE_TIME'] = 14400;
        /* Pre-2023 code */
        // $DahamiToken->setExp(512640); // exp:minutes
    } else {
        $_SESSION['LIFE_TIME'] = 3600;
    }
    
    return true;
}

function diffHttpUserAgent() {
    if ($_SESSION['AGENT'] == $_SERVER['HTTP_USER_AGENT'])
        return false;
    return true;
}

/* Check Session Timeout */
if (isset($_SESSION['LAST_ACTIVITY']) && isset($_SESSION['LIFE_TIME'])
        && (time() - $_SESSION['LAST_ACTIVITY'] > $_SESSION['LIFE_TIME'])) {
    session_unset();     // unset $_SESSION variable for the run-time
    session_destroy();   // destroy session data in storage
}
$_SESSION['LAST_ACTIVITY'] = time();

/* Set $uid and $premiumID */
if (isset($_SESSION['USER_ID']) && isset($_SESSION['PREMIUM_ID'])) {
    /**
     * <pre> *.php에서 $premiumID를 사용하기 위함이다. </pre>
     */
    $uid = $_SESSION['USER_ID'];
    $premiumID = $_SESSION['PREMIUM_ID'];
}