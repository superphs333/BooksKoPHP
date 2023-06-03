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
//   $accept_sort = "Get_Data_Chatting_Rooms";
//   $_POST['email']= "nice@naver.com";
//  $_POST['room_idx']= "20";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
/////////////////////////////////////


$result = $_POST;
extract($result);


/*
dbclass 생성
*/
$object_db = new class_db($connect);
$object_db->set_table('memo');

/*
도서 관련 클래스
*/
$object_book = new class_book($connect);

/*
함수 관련 클래스
*/
$object_function = new class_function();
$object_fcm = new class_fcm();

function sendApiResponse($result) {
    global $response;
    $response['status'] = isset($result['result']) && $result['result'] === 'success' ? 'success' : 'fail';
    $response['message'] = $response['status'] === 'success' ? '요청이 성공적으로 처리되었습니다.' : '요청 처리에 실패했습니다.';
    $response['data'] = $result;
    echo json_encode($response);
    exit;
}

/*
accept_sort에 따라 구분
*/
//$accept_sort = "alarm_for_chatting";
//$idx = 15;
//$room_idx=16;
// $email = "super@naver.com";
// $state = "true";
switch ($accept_sort){
    case "save_chatting_room" : // 채팅룸 저장
        $sql = "INSERT INTO Chatting_Room(title, room_explain, total_count,leader) VALUES('{$title}','{$room_explain}',{$total_count},'{$email}')";
        $result['qry'] = $sql;
        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";

            /*
            데이터베이스 Join_Chatting_Room에 저장
            */
            // 채팅방 idx가져오기
            $room_idx = mysqli_insert_id($connect);
            $result['room_idx'] = $room_idx;
            $sql2 = "INSERT INTO Join_Chatting_Room(email,room_idx,status) VALUES('{$email}',{$room_idx},1)";
            $result['qry2'] = $sql2;
            $result_qry2 = $object_db->excute_query($sql2);
            if($result_qry2){
                $result['sql_success'] = "success";
                $result['result'] = "success";
                sendApiResponse($result);
                return;
            }else{
                $result['sql_error'] = "fail";
                $result['sql_success'] = "fail";
                $result['result'] = "fail";
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
    break;


    case "Get_Data_Chatting_Rooms" : // 채팅룸 리스트
        // 받은값 : email, status
        // 보낼값 : title, room_explain, total_count, idx, leader , join_count
        // 연관 db : Chatting_Room, Join_Chatting_Room

        //$inputStatus = 1;

        if($inputStatus==0){  // 전체
            $sql = "SELECT 
            cr.title, 
            cr.room_explain, 
            cr.total_count, 
            cr.idx, 
            cr.leader, 
            COUNT(jcr.room_idx) as join_count 
            FROM 
                Chatting_Room as cr 
            INNER JOIN 
                (SELECT * FROM Join_Chatting_Room WHERE status = 1) as jcr ON cr.idx=jcr.room_idx 
            GROUP BY 
                cr.idx;
            ";

            $room_Result = $object_db->get_table_results2(false,$sql);
        
            $result['Data_Chatting_Room'] = $room_Result['rows'];
            $result['chattingRoomList'] =  $result['Data_Chatting_Room'];
            
        }else{ // 참여중 or 대기중
            $sql = "SELECT 
            cr.title, 
            cr.room_explain, 
            cr.total_count, 
            cr.idx, 
            cr.leader,
            COUNT(jcr_all.room_idx) as join_count, 
            COALESCE(jcr_status.status, 0) as nice_status
            FROM 
                Chatting_Room as cr 
            LEFT JOIN 
                Join_Chatting_Room as jcr_all ON cr.idx=jcr_all.room_idx
            LEFT JOIN 
                (SELECT * FROM Join_Chatting_Room WHERE email = '{$email}') as jcr_status ON cr.idx=jcr_status.room_idx 
            WHERE jcr_all.email='{$email}' AND COALESCE(jcr_status.status, 0) = {$inputStatus}
            GROUP BY 
                cr.idx;
            ";

           //echo $sql."<br/>";

            // 참여중(status=1) or 대기중(status=2)
            // $sql = "SELECT cr.title, cr.room_explain, cr.total_count, cr.idx, cr.leader FROM Chatting_Room as cr LEFT JOIN Join_Chatting_Room as jcr ON cr.idx=jcr.room_idx WHERE jcr.email='{$email}' and jcr.status={$inputStatus}";

            
            $room_Result = $object_db->get_table_results2(false,$sql);
        
            $result['Data_Chatting_Room'] = $room_Result['rows'];

            for($i=0; $i<count($result['result_array']); $i++){
                
                // 각 room_idx의 카운트 구하기
                $count_sql = "select count(*) as join_count  from Join_Chatting_Room WHERE room_idx={$result['result_array'][$i]['idx']} and Join_Chatting_Room.status=1";
                $count_result = $object_db->excute_query($count_sql);
                $count_row = mysqli_fetch_array($count_result);
                $count = $count_row['join_count'];
                $result['result_array'][$i]['join_count']=$count;
            }
            $result['chattingRoomList'] =  $result['Data_Chatting_Room'];

        }
        // [개선] 분기 나눠야 함(성공, 실패)
        $result['result'] = "success";

        sendApiResponse($result);
        return;
    break;

    case 'delete_rom':
        // [개선] 채팅방 내부에 있는 사람들도 다 삭제
        $sql = "DELETE FROM Chatting_Room WHERE idx={$idx}";
        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";
            sendApiResponse($result);
            return;
        }else{
            $result['sql_error'] = "fail";
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            sendApiResponse($result);
            return;
        }
    break;

    case 'edit_chatting_room' :
        //$sql = "UPDATE Chatting_Room SET title='{$title}', room_explain='{$room_explain}', total_count={$total_count} WHERE idx={$idx}";
        $sql = "UPDATE Chatting_Room SET";
        if(isset($title)){$sql .= " title='{$title}',";}
        if(isset($room_explain)){$sql .= " room_explain='{$room_explain}',";}
        if(isset($total_count)){$sql .= " total_count={$total_count},";}
        if(isset($leader)){$sql .= " leader='{$leader}',";}
        // 아래는 쉼표 때문에 넣은 것
        $sql .= " idx={$idx}";
        $sql .= " WHERE idx={$idx}";

        $result['qry'] =  $sql;
        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";
            sendApiResponse($result);
            return;
        }else{
            $result['sql_error'] = "fail";
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            sendApiResponse($result);
            return;
        }
    break;

    case 'get_chatting_room_info' : 
        $sql = "SELECT cr.title, cr.room_explain, cr.total_count, cr.idx, cr.leader, COUNT(cr.idx) as join_count FROM Chatting_Room as cr LEFT JOIN Join_Chatting_Room as jcr ON cr.idx=jcr.room_idx WHERE cr.idx={$idx} and jcr.status=1 GROUP BY cr.idx";
        $room_info_result=$object_db->get_table_result2(true,$sql);
        $result['sql'] =$room_info_result['query'];
        $room_info = $room_info_result['row'];
        if(!$room_info_result['error']){
            $result['result'] = "success";
            $result['room_info'] = $room_info;
            sendApiResponse($result);
            return;
        }else{
            $result['result'] = "fail";
            $result['error_cause'] = $room_info_result['error_cause'];
            sendApiResponse($result);
            return;
        }
    break;

    case 'Get_join_chatting_room_people' :
        $sql = "SELECT m.email, m.nickname, m.profile_url as profileUrl,
                    (CASE WHEN f.count > 0 THEN true ELSE false END) AS follow
                FROM Join_Chatting_Room AS jcr
                INNER JOIN members AS m ON jcr.email = m.email
                LEFT JOIN (
                    SELECT from_email, to_email, COUNT(*) AS count
                    FROM Follow
                    WHERE from_email = '{$email}'
                    GROUP BY from_email, to_email
                ) AS f ON jcr.email = f.to_email
                WHERE jcr.room_idx = {$room_idx} AND jcr.status = 1
                ORDER BY jcr.datetime ASC";
        $room_people_result = $object_db->get_table_results2(true,$sql);
        $room_people = $room_people_result['rows'];
        // for($i=0; $i<count($room_people); $i++){
        //     // 각 follow 구하기
        //     $sql = "SELECT COUNT(*) as count FROM Follow WHERE from_email='{$email}' AND to_email='{$room_people[$i]['email']}'";
        //     $result2 = $object_db->excute_query($sql);
        //     $follow_row = mysqli_fetch_array($result2);
        //     $follow_count = $follow_row['count'];
        //     //echo "follow=>".$follow;
        //     if($follow_count>0){
        //         $room_people[$i]['follow']=true;
        //     }else{
        //         $room_people[$i]['follow']=false;
        //     }

        //     // 만약 본인이면 -> false
        //     if($email==$room_people[$i]['email']){
        //         $room_people[$i]['follow']=false;
        //     }
            
        // }
        $result['sql'] = $sql;
        $result['result'] = "success";
        $result['dataJoinPeopleList'] = $room_people;
        sendApiResponse($result);
        return;
    break;

    case "get_chatting" : // 채팅 데이터 불러오기

        // $view = 0;
        // $room_idx = 40;

        if($view==0){ // 채팅방에 채팅 데이터
            $sql = "SELECT idx, Chatting.email, sort, room_idx, content, date, order_tag as orderTag, members.nickname, members.profile_url as profileUrl FROM Chatting LEFT JOIN members ON Chatting.email=members.email WHERE room_idx={$room_idx} order by idx asc";
        }
        $chatting_Result = $object_db->get_table_results2(false,$sql);
        $result['result'] = "success";
        $result['chattingList'] = $chatting_Result['rows'];
        sendApiResponse($result);
        return;
    return;

    break;

    case "out_join_room" : // 방에서 나가기or 참여/대기
        // 현재 참여자수
        $count_sql = "select count(*) as join_count, Chatting_Room.total_count as total_count, Chatting_Room.leader  from Join_Chatting_Room LEFT JOIN Chatting_Room ON Chatting_Room.idx=Join_Chatting_Room.room_idx WHERE room_idx={$room_idx} and Join_Chatting_Room.status=1";
        $count_result = $object_db->excute_query($count_sql);
        $count_row = mysqli_fetch_array($count_result);
        $join_count = $count_row['join_count'];
        $total_count = $count_row['total_count'];
        $leader = $count_row['leader'];
        $result['join_count'] = $join_count;
        $result['total_count'] = $total_count;
        $result['leader'] = $leader;


            
        if($joinState=="true"){ // 참여중인 경우

        
            if($join_count==1){
                // 현재 참여인원 1명인 경우 -> 방삭제/나가기
                // [개선] 대기자 어떻게 해야하는지 
                $result['after'] = "out_and_delete";

                // 나가기(Join_Chatting_Room에서 제거)
                $sql = "DELETE FROM Join_Chatting_Room WHERE email='{$email}' and room_idx={$room_idx}";
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

                // 방삭제
                $sql = "DELETE FROM Chatting_Room WHERE idx={$room_idx}";
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

                
            }else{
                
                
                // 참가자 제거
                    // Join_Chatting_Room에서 제거
                    // 리더인 경우 -> 리더 변경
                    // 대기자 있을 경우 -> 대기자에게 알람
                // Join_Chatting_Room에서 제거
                $sql = "DELETE FROM Join_Chatting_Room WHERE email='{$email}' and room_idx={$room_idx}";
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

                // 리더인 경우
                if($leader==$email){

                    $result['after'] = "out_and_change_leader";
                    // 다음 리더 찾기
                    $sql = "SELECT email FROM Join_Chatting_Room WHERE room_idx={$room_idx} ORDER BY datetime ASC LIMIT 1";
                    //$result['next_leader_qry']=$sql;
                    $result_leader = $object_db->get_table_result2(true,$sql);
                    $leader = $result_leader['row']['email'];
                    $result['next_leader'] = $leader;
                    // 다른 대기자에게 리더 넘김
                    $sql = "UPDATE Chatting_Room SET leader='{$leader}' WHERE idx={$room_idx}";
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
                    
                }

                /*
                대기자에게 알람 전송
                */
                // 대기자 첫번째 
                $sql = "select jcr.email as email, m.sender_id, c.title from Join_Chatting_Room as jcr left join members as m on jcr.email=m.email left join Chatting_Room as c on jcr.room_idx=c.idx  where jcr.room_idx={$room_idx} and jcr.status=2 order by jcr.datetime ASC";
                $result['waiting_sql'] = $sql;
                $row_waiting = $object_db->get_table_results2(true,$sql);
                // 대기자수
                $waiting_count = $row_waiting['count'];
                $result['waiting_count'] = $waiting_count;
                
                // 대기자 존재하면 대기자에게 알람 보내기
                if($waiting_count>0){
                    $waiting = $row_waiting['rows'][0]['email'];
                    $result['waiting'] = $waiting;

                    // 채팅방 제목
                    $title = $row_waiting['rows'][0]['title'];
                    $result['room_title']=$title;
                    

                    // 데이터 베이스에 적용
                    $sql = "UPDATE Join_Chatting_Room SET status=1 WHERE email='{$waiting}'";
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


                    // 전송할 sender_id
                    $to = $row_waiting['rows'][0]['sender_id'];
                    $result['sender_id']=$to;

                    /*
                    알람보내기(데이터메세지 형식)
                        형식
                        제목 - 알림
                        내용 - {title}
                    */
                    $data = json_encode(array(
                    "to"=>$to,
                    "data" => array(
                        "sort"   => "For_chatting_room_waiting_list", 
                        "room_idx" => "{$room_idx}",
                        "title" => "알림",
                        "message" => "대기중이셨던 채팅방 {$title}에 입장되셨습니다!")
                        ));
                    $fcm_result = $object_fcm->send($data);
                    $result['fcm_result'] = $fcm_result;
                    sendApiResponse($result);


                }

                
                

            }

            
            

        }else if($joinState=="false"){ // 미참여 -> 참여or대기
            /*
            현재 참여인원 = 총 참가 가능인원 -> 대기로
            현재 참여인원 < 총 참가 가능인원 -> 참가
            */
            if($join_count<$total_count){ // 참가
                $result['after'] = "join";
                $input_status = 1;
            }else if($join_count==$total_count){ // 대기
                $result['after'] = "wait";
                $input_status = 2;
            }
            

            // 참여자 목록에 추가
            $sql = "INSERT INTO Join_Chatting_Room(email, room_idx, status) VALUES('{$email}', {$room_idx},$input_status)";
            $result['join_qry'] = $sql;

            $result_qry = $object_db->excute_query($sql);
            if($result_qry){
                $result['sql_success'] = "success";
                $result['result'] = "success";
                sendApiResponse($result);
                return;
            }else{
                $result['sql_error'] = "fail";
                $result['sql_success'] = "fail";
                $result['result'] = "fail";
                sendApiResponse($result);
                return;
            }


        }
    break;

    case "alarm_for_chatting" : // 채팅내용 알람
        // 해당 room_idx에 참가하는 사용자 데이터를 불러온다 -> 배열에 저장
        $sql = "SELECT sender_id FROM `Join_Chatting_Room` JOIN `members` ON Join_Chatting_Room.email=members.email where Join_Chatting_Room.room_idx={$room_idx}  and members.email  NOT IN('{$writer}')";
        //EEEEEEEEEEEEEE
        $result['sql'] = $sql;
        $rows = $object_db->get_table_results2(true,$sql);
        // 메세지를 보낼 sender_id값들
        $join_array = $rows['rows'];
        $registration_ids_array = array();
        for($i=0; $i<count($join_array); $i++){
            array_push($registration_ids_array,$join_array[$i]['sender_id']);
        }
        // 알림보내기(데이터메세지 방식)
        $data = json_encode(array(
            "registration_ids"=>$registration_ids_array,
            "data" => array(
                "sort"   => "For_Chatting",
                "writer" => "{$writer}",
                "nickname" => "{$nickname}",
                "category" => "{$sort}",
                "title" => "채팅 메세지 알림",
                "room_idx" => "{$room_idx}",
                "room_title" => "{$title}",
                "message" => "{$nickname} : {$content}",
                "content" => "{$content}")
                ));
        $send_result = $object_fcm->send($data);
        $result['result'] = "success";
        
    break;



       
        

        
        
        
    
}


// json 내보내기
sendApiResponse($result);
return;

?>  