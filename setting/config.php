<?php
 //error_reporting( E_ALL );
 //ini_set( "display_errors", 1 );
 
/*
MYSQL 접속상수
*/
define('DB_HOST','localhost');
define('DB_USER','superjg33');
define('DB_NAME','bookapp');
define('DB_PW','41Asui!@');

/*
주소
*/
$server_url = "https://books.dosymmm.ga/";

/*
경로
*/
//define('setting',);

/*
필요 폴더 include
*/
require_once $_SERVER['DOCUMENT_ROOT']."/setting/dbconfig.php";
require_once $_SERVER['DOCUMENT_ROOT']."/library/class_db.php";
require_once $_SERVER['DOCUMENT_ROOT']."/library/class_encryption.php";
require_once $_SERVER['DOCUMENT_ROOT']."/library/class_book.php";
require_once $_SERVER['DOCUMENT_ROOT']."/library/class_function.php";
require_once $_SERVER['DOCUMENT_ROOT']."/library/class_fcm.php";

/*
curl
*/
// 세션을 초기화하고 다른 curl 함수에 전달 할 수 있는 curl핸들을 반환한다
$ch_fcm = curl_init("https://fcm.googleapis.com/fcm/send");
$header_fcm = array("Content-Type:application/json", "Authorization:key=AAAAgrn30Sw:APA91bG-DK1V5d-d2WZcCW3cgHslwP0uvRiatECUpdzq47B8Y9Kmp5FLL77g-VZHNe99taiUFbGMkIIOgN8vH_YtjWuUUkPdyrGSlr1qRNt-nOz2FbJSkEqkeoWlLuQGFXStf1HenR2Y");
?>