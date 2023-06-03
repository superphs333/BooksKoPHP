<?php
/*
도서 관련 class
*/

class class_book{

    var $object_db; // db클래스

    // 생성자
    function __construct($connect){
        $object_db = new class_db($connect);
        $object_db->set_table('My_Books');
    }

    // 데이터베이스에 책 저장
    function save_in_my_book_list($array)
    {
        // 결과로 리턴할 배열
        $result = array();

        $sql = "INSERT INTO My_Book(`email`, `isbn`, `status`, `rating`,`from_`) VALUES('{$array['email']}','{$array['isbn']}','{$array['status']}','{$array['rating']}','search')";
        $result['qry'] = $sql;

        $result_qry = $object_db->excute_query($sql);
        

        return $result;
    }    
}
?>