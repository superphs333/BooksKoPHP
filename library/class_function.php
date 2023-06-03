<?php
/*
다양한 함수 class
*/

class class_function{


    // 생성자
    function __construct(){
        
    }

    // 임의의 문자열 생성
    function getRandStr($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    




    
}
?>