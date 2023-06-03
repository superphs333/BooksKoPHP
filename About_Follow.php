<?php
 // error_reporting( E_ALL );
 // ini_set( "display_errors", 1 );

// 필요파일 include
require_once $_SERVER['DOCUMENT_ROOT']."/root.php";

// json array
    // 구성 : accept, info, result
$result = array();

/*
받은정보
*/
// 테스트용 
//$accept_sort = "My_Books";
// $_POST['email']= "goo@gmail.com";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
/////////////////////////////////////

//$_POST['isbn']= "8901219948";

$arr = $_POST ? $_POST : $_GET;
foreach($arr as $key => $value) {
    foreach($value as $key1 => $value1) {
        $$key[$key1] = $value;
    }
    $$key = $value;
}

function sendApiResponse($result) {
    global $response;
    $response['status'] = isset($result['result']) && $result['result'] === 'success' ? 'success' : 'fail';
    $response['message'] = $response['status'] === 'success' ? '요청이 성공적으로 처리되었습니다.' : '요청 처리에 실패했습니다.';
    $response['data'] = $result;
    echo json_encode($response);
    exit;
}


/*
dbclass 생성
*/
$object_db = new class_db($connect);
$object_db->set_table('Follow');



/*
함수 관련 클래스
*/
$object_function = new class_function();
$object_fcm = new class_fcm();

/*
accept_sort에 따라 구분
*/
//$accept_sort = "Get_Follow_People";
//$idx = 15;
// $room_idx=16;
//$email = "super@naver.com";
// $state = "true";
//$sort=="follower";
switch ($accept_sort){
    case "following" : // 팔로잉
        $sql = $temp = "INSERT INTO Follow(`from_email`, `to_email`) VALUES('{$from_email}','{$to_email}')";
        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";
        }else{
            $result['sql_error'] = "fail";
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            sendApiResponse($result);
            return;
        }

        // 보내는 이 nickname  
        $forNicknameQuery = "select nickname from members where email='{$from_email}'";
        $row = $object_db->get_table_result2(false,$forNicknameQuery);
        $from_nickname = $row['row']['nickname'];

        // 대상 sender_id 알아내기
        $sql = "select nickname, sender_id from members where email='{$to_email}'";
        $row = $object_db->get_table_result2(false,$sql);
        $sender_id = $row['row']['sender_id'];
        // 알람 보내기
        $data = json_encode(array(
            "to"=>$sender_id,
            "data" => array(
                "sort"   => "For_Follow",
                "title" => "알림",
                "message" => "{$from_nickname}님이 회원님을 팔로우하였습니다!")
                ));
        $fcm_result = $object_fcm->send($data);
        $result['fcm_result'] = $fcm_result;
        sendApiResponse($result);
        return;
    break;

    case "ManagementFollow" : // 팔로잉 관리
        switch($mode) {
            case "following":
                $sql = $temp = "INSERT INTO Follow(`from_email`, `to_email`) VALUES('{$from_email}','{$to_email}')";
                break;
            case "invisible":
                $sql = "UPDATE Follow SET visible=0 WHERE from_email='{$from_email}' AND to_email='{$to_email}'";
                break;
            case "delete_following":
                $sql = "DELETE FROM Follow WHERE from_email='{$from_email}' AND to_email='{$to_email}'";
                break;
            default:
                // Default case action here
                break;
        }
        

        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";
            if($mode != "following"){
                sendApiResponse($result);
                return;
            }
        }else{
            $result['sql_error'] = "fail";
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            sendApiResponse($result);
            return;
        }

        // 알림전송
        if($mode=="following"){
            // 보내는 이 nickname  
            $forNicknameQuery = "select nickname from members where email='{$from_email}'";
            $row = $object_db->get_table_result2(false,$forNicknameQuery);
            $from_nickname = $row['row']['nickname'];

            // 대상 sender_id 알아내기
            $sql = "select nickname, sender_id from members where email='{$to_email}'";
            $row = $object_db->get_table_result2(false,$sql);
            $sender_id = $row['row']['sender_id'];
            // 알람 보내기
            $data = json_encode(array(
                "to"=>$sender_id,
                "data" => array(
                    "sort"   => "For_Follow",
                    "title" => "알림",
                    "message" => "{$from_nickname}님이 회원님을 팔로우하였습니다!")
                    ));
            $fcm_result = $object_fcm->send($data);
            $result['fcm_result'] = $fcm_result;
            sendApiResponse($result);
            return;
        }
    break;

    case "Get_Follow_People" : // 팔로잉  
        if($sort=="follower"){ // email = to
            $sql = "SELECT from_email as email, members.nickname, members.profile_url, visible FROM Follow JOIN members ON Follow.from_email = members.email WHERE to_email='{$email}' AND visible=1 ";
        }else if($sort="following"){ // email = from
            $sql = "SELECT to_email as email, members.nickname, members.profile_url, visible FROM Follow JOIN members ON Follow.to_email = members.email WHERE from_email='{$email}'";
        } 
        $row = $object_db->get_table_results2(false,$sql);
        $result['followList'] = $row['rows'];
        $result['sql'] = $sql;
        $result['result'] = "success";
        sendApiResponse($result);
        return;
    break;


        
    
}


// json 내보내기
echo json_encode($result);

?>  