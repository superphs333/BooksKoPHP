<?php
  //error_reporting( E_ALL );
  //ini_set( "display_errors", 1 );

// 필요파일 include
require_once $_SERVER['DOCUMENT_ROOT']."/root.php";

// json array
    // 구성 : accept, info, result
$result = array();

/*
받은정보
*/
// 테스트용 
//$accept_sort = "Update_Profile_img";
//$email = "superphs333@naver.com";
//$_POST['pw']= "asdf1234!@";
//$nickname = "okok";
/////////////////////////////////////


if($_POST['accept_sort']){$result['accept_sort'] = $_POST['accept_sort']; $accept_sort=$_POST['accept_sort'];}
if($_POST['email']){$result['email'] = $_POST['email']; $email=$_POST['email'];}


/*
dbclass 생성
*/
$object_db = new class_db($connect);
$object_db->set_table('members');




/*
accept_sort에 따라 구분
*/

switch ($accept_sort){
    case "login" :
        $decrypt_pw = $object_encryption->Decrypt($pw);
        $count = $object_db->get_total_count('*',"email='{$email}' and pw='{$pw}'",true);
        if($count['count']>0){$result['result']="yes";}else{$result['result']="no";}
        break;
}



// json 내보내기
echo json_encode($result);

?>  