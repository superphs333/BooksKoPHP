<?php
  //error_reporting( E_ALL );
  //ini_set( "display_errors", 1 );

// 필요파일 include
require_once $_SERVER['DOCUMENT_ROOT']."/root.php";

// json array
    // 구성 : accept, info, result
$result = array();

function sendApiResponse($result) {
    global $response;
    $response['status'] = isset($result['result']) && $result['result'] === 'success' ? 'success' : 'fail';
    $response['message'] = $response['status'] === 'success' ? '요청이 성공적으로 처리되었습니다.' : '요청 처리에 실패했습니다.';
    $response['data'] = $result;
    echo json_encode($response);
    exit;
}

/*
받은정보
*/

// 테스트용 
// $accept_sort = "sign_up";
//  $email = "superphs333@naver.com";
//  $_POST['pw']= "asdf1234!@";
//  $nickname = "okok";
/////////////////////////////////////

/*
class class_encryption 생성
*/
$object_encryption = new class_encryption();

if($_POST['accept_sort']){$result['accept_sort'] = $_POST['accept_sort']; $accept_sort=$_POST['accept_sort'];}
if($_POST['email']){$result['email'] = $_POST['email']; $email=$_POST['email'];}
if($_POST['pw']){
    $result['pw'] = $_POST['pw']; 
    $pw=$_POST['pw'];



    // pw 암호화 
    $pw = $object_encryption->Encrypt($pw);
}
if($_POST['nickname']){$result['nickname'] = $_POST['nickname']; $nickname=$_POST['nickname'];}
if($_POST['sort']){$result['sort'] = $_POST['sort']; $sort=$_POST['sort'];}
if($_POST['input']){$result['input'] = $_POST['input']; $input=$_POST['input'];}
if($_POST['sns_id']){$result['sns_id'] = $_POST['sns_id']; $sns_id=$_POST['sns_id'];}
if($_POST['profile_url']){$result['profile_url'] = $_POST['profile_url']; $profile_url=$_POST['profile_url'];}
if($_POST['to_get']){$result['to_get'] = $_POST['to_get']; $to_get=$_POST['to_get'];}
if($_POST['sender_id']){$result['sender_id'] = $_POST['sender_id']; $sender_id=$_POST['sender_id'];}
/*
dbclass 생성
*/
$object_db = new class_db($connect);
$object_db->set_table('members');




/*
accept_sort에 따라 구분
    1) login -> email, pw값을 확인하여 해당 회원정보가 있는지 확인
        - 있으면 -> yes
        - 없으면 -> no
        - 오류 -> error
    2) chk_double -> email, nickname값 중복 확인
        - 중복o -> yes
        - 중복x -> no
        - 오류 -> error
    3) sign_up = 회원가입 -> email, nickname, pw, 프로필 사진 저장
    4) validate_new => 구글 로그인시, 기존or신규 회원 구분
    5) sign_up_google = 구글 회원가입 -> email, sns_id, nickname, profile_url 셋팅
    6) Change_Member_Info = 회원 정보 변경
    7) withdrawal = 회원 탈퇴(정보 삭제)
    8) Get_member_info = 이메일 정보로 회원 정보 가져오기
    9) Update_Profile_img = 프로필 이미지 변경하기
*/

switch ($accept_sort){
    case "login" :
        $decrypt_pw = $object_encryption->Decrypt($pw);
        $count = $object_db->get_total_count('*',"email='{$email}' and pw='{$pw}'",true);
        if($count['count']>0){$result['result']="yes";}else{$result['result']="no";}
        break;
    case "chk_double" : 
        $count = $object_db->get_total_count("*","{$sort}='{$input}'",true);
        $result['query']=$count['query'];
        if($count['count']>0){$result['result']="yes";}else{$result['result']="no";}
        break;
    
    case "sign_up" : 

        /*
        파일받기 
        */
        if(isset($_FILES['uploadedfile']['name'])){ // 파일이 있는 경우에만
            $result['uploadedfile_name'] = $_FILES['uploadedfile']['name'];
    
            if(!$_FILES['uploadedfil1e']['error']){ // 파일에러 없는 경우에만
                $result['uploadedfile_error'] = "no";
    
                // 경로지정
                $profile_img_destination = "/Img_Profile/".$_FILES['uploadedfile']['name'];
                $destination = $_SERVER['DOCUMENT_ROOT'].$profile_img_destination;
                $result['destination'] = $destination;
    
                // 서버에 저장된 업로드된 파일의 임시 파일 이름
                $tmp_name = $_FILES['uploadedfile']['tmp_name'];
                $result['tmp_name'] = $tmp_name;
    
                // 이동하기
                $move = move_uploaded_file($tmp_name,$destination);
    
                // 이동 성공/실패 여부
                if($move){
                    $result['move'] = "success";
                }else{
                    $result['move'] = $_FILES['uploadedfile']['error'];
                    $result['result'] = "fail";
                    sendApiResponse($result);
                    return;
                }
            }else{
                $result['uploadedfile_error'] = $_FILES['uploadedfile']['error'];
                $result['result'] = "fail";
                sendApiResponse($result);
                return;
            }
        }
    
        /*
        sql문 적용
        */
    
        $sql = "INSERT INTO members(email,pw,nickname, profile_url) VALUES ('{$email}','{$pw}','{$nickname}','{$profile_img_destination}')";
        $result['sql_insert'] = $sql;
        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";
        }else{
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
        }
        sendApiResponse($result);
        break;
        
        

    case "validate_new" :
        $count = $object_db->get_total_count("*","snsid='{$sns_id}'",true);
        $result['query']=$count['query'];
        if($count['count']>0){$result['result']="yes";}else{$result['result']="no";}
    break;

    case "sign_up_google" :
        $sql = "INSERT INTO members(email,snsid,nickname, profile_url) VALUES ('{$email}','{$sns_id}','{$nickname}','{$profile_url}')";
        $result['sql_insert'] = $sql;
        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";
        }else{
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            echo json_encode($result);
            return;
        }
    break;

    case "Change_Member_Info" :
        if($sort=="pw"){
            $input = $pw;
        }else if($sort=="nickname"){
            $input = $nickname;
        }else if($sort="sender_id"){
            $input = $sender_id;
        }
        $sql = "UPDATE members SET {$sort}='{$input}' WHERE email='{$email}'";
        $result['sql_insert'] = $sql;
        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";
        }else{
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            echo json_encode($result);
            return;
        }
    break;

    case "withdrawal" :
        $sql = "DELETE FROM members WHERE email='{$email}'";
        $result['sql_insert'] = $sql;
        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";
            sendApiResponse($result);
            return;
        }else{
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            sendApiResponse($result);
            return;
        }
    break;

    case "Get_member_info" :
        $row = $object_db->get_table_result(true,"*","email='{$email}'");      
        if(!$row['error']){
            $result['query']=$row['query'];
            $result['row'] = $row['row'];
            
            // 배열에 pw있는 경우 복호화
            if(array_key_exists("pw",$result['row'])){
                $result['row']['pw'] = $object_encryption->Decrypt($result['row']['pw']);
            }

            $result['result'] = "success";
        }else{
            $result['result'] = "fail";
            echo json_encode($result);
            return;
        }
        
    break;

    case "Get_member_info_temp" :
        $results = $object_db->get_table_results(true,$to_get,"email='{$email}'",null,null,null,null);
        if(!$result['error']){
            $result['rows'] = $results['rows'];
            $result['result'] = "success";
        }else{
            $result['result'] = "fail";
            echo json_encode($result);
            return;
        }
        
    break;

    // case "Update_Profile_img" : 

    //     /*
    //     파일받기 
    //     */
    //     if(isset($_FILES['uploadedfile']['name'])){ // 파일이 있는 경우에만
    //         $result['uploadedfile_name'] = $_FILES['uploadedfile']['name'];

    //         if(!$_FILES['uploadedfile']['error']){ // 파일에러 없는 경우에만
    //             $result['uploadedfile_error'] = "no";

    //             // 경로지정
    //             $profile_img_destination = "/Img_Profile/".$_FILES['uploadedfile']['name'];
    //             $destination = $_SERVER['DOCUMENT_ROOT'].$profile_img_destination;
    //             $result['destination'] = $destination;
    //             $result['profile_img_destination'] = $profile_img_destination;

    //             // 서버에 저장된 업로드된 파일의 임시 파일 이름
    //             $tmp_name = $_FILES['uploadedfile']['tmp_name'];
    //             //$result['tmp_name'] = $tmp_name;

    //             // 이동하기
    //             $move = move_uploaded_file($tmp_name,$destination);

    //             // 이동 성공/실패 여부
    //             if($move){
    //                 $result['move'] = "success";
    //             }else{
    //                 $result['move'] = $_FILES['uploadedfile']['error'];
    //                 $result['result'] = "fail";
    //                 echo json_encode($result);
    //                 return;
    //             }
    //         }else{
    //             echo json_encode($result);
    //             $result['uploadedfile_error'] = $_FILES['uploadedfile']['error'];
    //             $result['result'] = "fail";
    //             return;
    //         }
    //     }

    //     //$profile_img_destination = "/Img_Profile/Image_Profile_11290685.jpg";
    //     //$email = "goo@gmail.com";

    //     /*
    //     sql문 적용
    //     */
    //     $sql = "UPDATE members SET profile_url='{$profile_img_destination}' WHERE email='{$email}'";
    //     $result['sql_insert'] = $sql;
    //     $result_qry = $object_db->excute_query($sql);
    //     if($result_qry){
    //         $result['sql_success'] = "success";
    //         $result['result'] = "success";
    //     }else{
    //         $result['sql_success'] = "fail";
    //         $result['result'] = "fail";
    //         echo json_encode($result);
    //         return;
    //     }
        
    // break;


    case "Update_Profile_img" : 

    /*
    파일받기 
    */
    // [개선] 기본 이미지 파일 삭제
    if(isset($_FILES['uploadedfile']['name'])){ // 파일이 있는 경우에만
        $result['uploadedfile_name'] = $_FILES['uploadedfile']['name'];

        if(!$_FILES['uploadedfil1e']['error']){ // 파일에러 없는 경우에만
            $result['uploadedfile_error'] = "no";

            // 경로지정
            $profile_img_destination = "/Img_Profile/".$_FILES['uploadedfile']['name'];
            $destination = $_SERVER['DOCUMENT_ROOT'].$profile_img_destination;
            $result['destination'] = $destination;

            // 서버에 저장된 업로드된 파일의 임시 파일 이름
            $tmp_name = $_FILES['uploadedfile']['tmp_name'];
            $result['tmp_name'] = $tmp_name;

            // 이동하기
            $move = move_uploaded_file($tmp_name,$destination);

            // 이동 성공/실패 여부
            if($move){
                $result['move'] = "success";
            }else{
                $result['move'] = $_FILES['uploadedfile']['error'];
                $result['result'] = "fail";
                sendApiResponse($result);
                return;
            }
        }else{
            $result['uploadedfile_error'] = $_FILES['uploadedfile']['error'];
            $result['result'] = "fail";
            sendApiResponse($result);
            return;
        }
    }

    /*
    sql문 적용
    */
    $sql = "UPDATE members SET profile_url='{$profile_img_destination}' WHERE email='{$email}'";
    $result['sql_insert'] = $sql;
    $result_qry = $object_db->excute_query($sql);
    if($result_qry){
        $result['sql_success'] = "success";
        $result['result'] = "success";
        sendApiResponse($result);
        return;
    }else{
        $result['sql_success'] = "fail";
        $result['result'] = "fail";
        sendApiResponse($result);
        return;
    }

}



// json 내보내기
echo json_encode($result);

?>  