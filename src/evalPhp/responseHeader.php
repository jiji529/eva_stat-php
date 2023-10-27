<?php
/**
 * <pre>
 * 사용된 php : login.php , auth.php , common.php
 * 원본 login.php header 구성
 *       - Content-Type: application/json
 * 원본 auth.php header 구성
 *       - Access-Control-Allow-Origin: *
 *       - Access-Control-Allow-Headers: Authorization
 *       - Content-Type: application/json
 * 원본 common.php header 구성
 *       - Access-Control-Allow-Origin: *
 *       - Access-Control-Allow-Headers: Authorization
 *       - Content-Type: application/json
 * </pre>
 * User: changmin-Test
 * Date: 2023-05-02
 * Time: 오전 9:38 
 */

// $_SERVER['HTTP_REFERER']을 사용해도 됨.
$http_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "";
// if ($http_origin == "http://192.168.0.104:8080" || 
//    $http_origin == "http://192.168.0.104" ||
//    $http_origin == "http://192.168.0.153:8080" ||
//    $http_origin == "http://192.168.0.153" ||
//    $http_origin == "https://192.168.0.11"
// ) {
//     header("Access-Control-Allow-Origin: $http_origin");
// } /* 보안상 나쁜 코드 */
header("Access-Control-Allow-Origin: $http_origin");
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');