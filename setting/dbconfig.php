<?php
/*
mysql 연결 관련 파일
*/
$connect = new mysqli(DB_HOST, DB_USER, DB_PW, DB_NAME);
$connect->set_charset("utf8");



?>