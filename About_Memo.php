<?php 
//error_reporting( E_ALL );
//ini_set( "display_errors", 1 );

// 필요파일 include
require_once $_SERVER['DOCUMENT_ROOT'].
"/root.php";

$object_function = new class_function();

// json array
// 구성 : accept, info, result
$result = array();

/*
받은정보
*/
// 테스트용 
//$_POST['accept_sort'] = "Management_Comment";
// $_POST['email'] = "goo@gmail.com";
// $_POST['view'] = "2";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
/////////////////////////////////////

function sendApiResponse($result) {
    global $response;
    $response['status'] = isset($result['result']) && $result['result'] === 'success' ? 'success' : 'fail';
    $response['message'] = $response['status'] === 'success' ? '요청이 성공적으로 처리되었습니다.' : '요청 처리에 실패했습니다.';
    $response['data'] = $result;
    echo json_encode($response);
    exit;
}


$result = [];
foreach($_POST as $key => $value) {
    $result[$key] = $value;
}
extract($result);




/*
dbclass 생성
*/
$object_db = new class_db($connect);
$object_db -> set_table('memo');

/*
도서 관련 클래스
*/
$object_book = new class_book($connect);

/*
함수 관련 클래스
*/
$object_function = new class_function();
$object_fcm = new class_fcm();

/*
accept_sort에 따라 구분
*/
switch ($accept_sort) {
    case "Save_Memo": // 메모저장
        // 파일 경로를 저장할 배열
        $img_array = array();

        for ($i = 0; $i < $size; $i++) {
            $tempname = "uploaded_file".(string) $i;
            $result[$tempname] = $tempname;

            if ($_FILES[$tempname]['name']) { // 해당 파일 네임 존재
                if (!$_FILES[$tempname]['error']) { // 파일에러

                    $destination = $_SERVER['DOCUMENT_ROOT'].
                    '/Img_Book_Memo/'.$_FILES[$tempname]['name'];

                    // 서버에 저장된 업로드된 파일의 임시 파일 이름
                    $location = $_FILES[$tempname]['tmp_name'];
                    $result['tmp_name'] = $location;

                    // 이동하기
                    $move = move_uploaded_file($location, $destination);
                    if ($move) {
                        $result['move'] = "success";
                    } else {
                        $result['result'] = "fail";

                        sendApiResponse($result);
                        return;
                    }

                    // 이미지 주소
                    $photo_url = '/Img_Book_Memo/'.$_FILES[$tempname]['name'];

                    // array에 저장
                    array_push($img_array, $photo_url);
                }
            } else { // 해당 파일 네임 존재x
                $result[$tempname] = $tempname.
                "->존재x";
            }
        } // end for

        // 이미지 배열 -> 문자열
        $img_urls = json_encode($img_array);
        $result['img_urls'] = $img_urls;

        // 날짜, 시간
        date_default_timezone_set('Asia/Seoul');
        $date_time = date("Y-m-d H:i:s");

        $sql = "INSERT INTO memo(book_idx,email,img_urls,page,memo,open,date_time) VALUES('{$book_idx}','{$email}','{$img_urls}',{$page},'{$memo}','{$open}','{$date_time}')";
        $result['sql_insert'] = $sql;
        $result_qry = $object_db -> excute_query($sql);
        if ($result_qry) {
            $result['sql_success'] = "success";
            $result['result'] = "success";
            sendApiResponse($result);
        } else {
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            sendApiResponse($result);
            return;
        }
        break;

    case "Edit_Memo" : // 메모 데이터 수정

        for ($i = 0; $i < $size; $i++) {
            $tempname = "uploaded_file".(string) $i;
            $result[$tempname] = $tempname;

            if ($_FILES[$tempname]['name']) { // 해당 파일 네임 존재
                if (!$_FILES[$tempname]['error']) { // 파일에러

                    $destination = $_SERVER['DOCUMENT_ROOT'].
                    '/Img_Book_Memo/'.$_FILES[$tempname]['name'];

                    // 서버에 저장된 업로드된 파일의 임시 파일 이름
                    $location = $_FILES[$tempname]['tmp_name'];
                    $result['tmp_name'] = $location;

                    // 이동하기
                    $move = move_uploaded_file($location, $destination);
                    if ($move) {
                        $result['move'] = "success";
                    } else {
                        $result['result'] = "fail";
                        sendApiResponse($result);
                        return;
                    }

                    // 이미지 주소
                    $photo_url = '/Img_Book_Memo/'.$_FILES[$tempname]['name'];

                    // array에 저장
                    array_push($img_array, $photo_url);
                }
            } else { // 해당 파일 네임 존재x
                $result[$tempname] = $tempname.
                "->존재x";
            }
        } // end for

        // 이미지 주소 사용자에게 받아 온 것으로 
        $array = explode(',', $imgOrderJoined);
        $json = json_encode($array);
        $imgs = $json;

        
        $sql = "UPDATE memo SET img_urls='{$imgs}', memo='{$memo}', page={$page}, open='{$open}' WHERE idx={$memo_idx}";
        $result['sql_update'] = $sql;
        $result_qry = $object_db -> excute_query($sql);
        if ($result_qry) {
            $result['sql_success'] = "success";
            $result['result'] = "success";
            sendApiResponse($result);
        } else {
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            sendApiResponse($result);
            return;
        }
        break;
    break;

    case "Get_Data_Book_Memos": // 나의 메모들을 불러옴

        /*
        도서 객체
            - check_hear = 내가 하트를 눌렀는지 체크(눌렀으면1, 누르지 않았으면 0)
        */
        class Book_Memo {
            public $idx, $email, $nickname, $profile_url, $title, $thumbnail, $date_time, $img_urls, $memo, $open, $count_heart, $count_comment, $check_heart, $follow;
        }
        $list = array();

        // $email = "leesotest@gmail.com";
        // $view = 3;
        // $book_idx = 102;


        $sql = "
            SELECT
                m.idx as idx, m.date_time as dateTime, m.img_urls as imgUrls, m.memo as memo, m.open as open, mem.email AS email, mem.profile_url as profileUrl, mem.nickname AS nickname, b.title as title, b.thumbnail as thumbnail, m.book_idx as bookIdx,m.page as page,
                (
                    SELECT COUNT(*)
                    FROM Heart_Memo
                    WHERE idx_memo = m.idx
                ) AS countHeart,
                (
                    SELECT COUNT(*)
                    FROM Comment_Memo
                    WHERE idx_memo = m.idx
                ) AS countComment,
                IF(EXISTS (
                    SELECT *
                    FROM Follow
                    WHERE from_email = '{$email}' AND to_email = m.email
                ), 1, 0) AS follow,
                IF(EXISTS (
                    SELECT *
                    FROM Heart_Memo
                    WHERE idx_memo = m.idx AND email = '{$email}'
                ), 1, 0) AS checkHeart
            FROM memo AS m
            JOIN members AS mem ON m.email = mem.email
            LEFT JOIN book AS b ON m.book_idx = b.idx
            LEFT JOIN Heart_Memo AS hm ON hm.idx_memo = m.idx
            LEFT JOIN Comment_Memo AS cm ON cm.idx_memo = m.idx
            WHERE 1 ";

            if ($view == 3) { // 자신의 메모
                $sql .= "AND m.email = '{$email}'";
            } else if ($view == 1) { // 전체메모 (메모의 open이 all이거나 follow이고 Follow 테이블에서 memo.email이 to_email인 값들에게만)
                $sql .= "AND (
                    m.email = '{$email}' OR
                    m.open = 'all' OR
                    (m.open = 'follow' AND EXISTS (
                        SELECT *
                        FROM Follow
                        WHERE from_email = m.email AND to_email = '{$email}'
                    ))
                )";
            } else if ($view == 2) { // 팔로잉인 경우 (Follow 테이블에서 from_email이 $email인 to_email 값들, 이 경우에도 메모의 open이 all이거나 follow이고 Follow 테이블에서 memo.email이 to_email인 값들에게만)
                $sql .= "AND (
                    m.email IN (
                        SELECT to_email FROM Follow WHERE from_email = '{$email}'
                    ) AND
                    (m.open = 'all' OR (m.open = 'follow' AND EXISTS (
                        SELECT *
                        FROM Follow
                        WHERE from_email = m.email AND to_email = '{$email}'
                    )))
                )";
            }

        // 도서 idx가 존재하는 경우
        if (isset($book_idx)) {
            if($book_idx>0){
                $sql .= " AND m.book_idx = {$book_idx}";
            }      
        }
        // 좋아요 한 게시물
        if($likeChk=="true"){
            $sql .= " AND EXISTS (
                SELECT *
                FROM Heart_Memo
                WHERE idx_memo = m.idx AND email = '{$email}'
            )";
        }
        $sql .= " GROUP BY
        m.idx, m.date_time, m.img_urls, m.memo, m.open, mem.email, mem.profile_url, mem.nickname, b.title, b.thumbnail, m.book_idx";
        $sql .= " ORDER BY m.date_time DESC";

       //  echo $sql."<br><br>";


        $memoResult = $object_db -> get_table_results2(false, $sql);
        // var_dump($memoResult);
        // echo "<br>/";
        $result['result'] = "success";
        $result['sql'] = $sql;
        $result['memoList'] = $memoResult['rows'];
        sendApiResponse($result);
        return;

    break;

    case  "Update_heart_check" : // 좋아요 상태 변경 (데이터 삽입, 삭제)

        //  $p_idx_memo = 5;
        //  $p_email = "leesotest@gmail.com";

        $sql = "CALL InsertOrUpdateHeartMemo($p_idx_memo, '$p_email')";


        // 현재 상태 (true)
        $memoResult = $object_db -> excute_query($sql);
        if ($memoResult !== false) {  // 성공
           $result['result'] = "success";

            // 좋아요 수 
            $object_db -> set_table('Heart_Memo');
            $heartCount = $object_db -> get_total_count("*","idx_memo='$p_idx_memo'",false);
            $result['heartCount'] = $heartCount['count'];

            // 보낸이 nickname
            $senderNicknameResult = $object_db -> get_table_results2(false, "select nickname from members where email='$p_email'");
            $senderNickname = $senderNicknameResult['rows'][0]['nickname'];

            // 받는 이 email에 대한 sender_id
            $receiverSenderIdResult = $object_db -> get_table_results2(false, 
            "SELECT memo.email, members.sender_id
            FROM memo 
            LEFT JOIN members ON memo.email = members.email
            WHERE memo.idx=$p_idx_memo");
            $receiverSenderId = $receiverSenderIdResult['rows'][0]['sender_id'];
            
            if(isset($alarm)){
                // 알림보내기 (좋아요 한 경우에만)
                $data = json_encode(array(
                    "to"=>$receiverSenderId,
                    "data" => array(
                        "sort"   => "For_memo_like",
                        "idx" => "{$p_idx_memo}",
                        "title" => "알림",
                        "message" => "{$senderNickname}님이 회원님의 게시글에 좋아요를 눌렀습니다!")
                        ));
                $fcm_result = $object_fcm->send($data);
                $result['fcm_result'] = $fcm_result;
            }

            sendApiResponse($result);
            return;
        } else { // 실패
           $result['result'] = "fail";
           sendApiResponse($result);
            return;
        }

    break;

    case  "Management_Comment" : // 댓글

        // $sort = "add_comment";
        // $idx_memo = 6;
        // $email = "dada@naver.com";
        // $comment = "hi";
        // $group_idx = 14;
        

        // sort에 따라 mysql문 분기
        switch ($sort) {
            case "add":
                $sql = "INSERT INTO Comment_Memo(email, idx_memo, comment,date_time,depth) VALUES('{$email}','{$idx_memo}','{$comment}','{$date_time}',0)";
                break;
            case "edit":
                $sql = "UPDATE Comment_Memo SET comment='{$comment}' WHERE idx={$idx}";
                break;
            case "delete":
                //$temp = "DELETE FROM Comment_Memo WHERE idx={$_POST['idx']}";
                $sql = "UPDATE Comment_Memo SET visibility=0 WHERE idx={$idx}";
                break;
            case "add_comment":
                $sql = "INSERT INTO Comment_Memo(email, idx_memo, comment, group_idx, depth,date_time, target) VALUES('{$email}','{$idx_memo}','{$comment}','{$group_idx}',1,'{$date_time}', '{$target}')";
                break;
        }
        $result['commentSortSql'] = $sql;

        

        // 현재 상태 (true)
        $commentResult = $object_db -> excute_query($sql);
        if ($commentResult !== false) {  // 성공
            // idx값 가져오기
            $result['idx'] = mysqli_insert_id($connect);
            // add의 경우 group_idx값을 셋팅해준다
            if($sort=="add"){
                $sql = "UPDATE Comment_Memo SET group_idx = {$result['idx']} WHERE idx = {$result['idx']}";
                $object_db -> excute_query($sql);
            }
            

            /*
            댓글 알림(sort = add, add_comment)
                - add -> 메모 작성자 email
                - add_comment -> 부모 댓글 작성자 email
            */
            if($sort=="add" || $sort=="add_comment"){
                if($sort=="add"){
                    // 메모 작성자의 sender_id
                    $object_db->set_table("members");
                    $row = $object_db->get_table_result(true,"sender_id","email='{$memo_writer_email}'"); 
                    $receiverSenderId = $row['row']['sender_id'];
                }else if($sort=="add_comment"){
                    // 부모 댓글 작성자의 sender_id
                    $sql = "select sender_id from members as m left join Comment_Memo as cm on m.email=cm.email where cm.idx={$group_idx}";
                    $row = $object_db->get_table_result2($sql,true); 
                    $receiverSenderId = $row['row']['sender_id'];
                }
                // 알림전송
                $data = json_encode(array(
                    "to"=>$receiverSenderId,
                    "data" => array(
                        "sort"   => "For_Comment",
                        "title" => "알림",
                        "message" => "{$sender_nickname}님이 댓글을 달았습니다",
                        "idx_memo" => "{$idx_memo}"),
                        ));
                $fcm_result = $object_fcm->send($data);
                $result['fcm_result'] = $fcm_result;
            }

                           
            $result['result'] = "success";
            sendApiResponse($result);
            return;
        } else { // 실패
            $result['result'] = "fail";
            $result['failReason'] = mysqli_error($connect);
            sendApiResponse($result);
             return;
        }

    break;


    case "Get_Comments" : // 댓글 데이터 불러오기
        $sql = "
                SELECT
                    Comment_Memo.idx_memo,
                    Comment_Memo.email,
                    members1.nickname AS nickname,
                    members1.profile_url,
                    Comment_Memo.comment,
                    Comment_Memo.date_time,
                    Comment_Memo.group_idx,
                    Comment_Memo.idx,
                    Comment_Memo.depth,
                    members2.nickname AS target,
                    Comment_Memo.visibility
                FROM
                    Comment_Memo
                LEFT JOIN members AS members1 ON Comment_Memo.email = members1.email
                LEFT JOIN members AS members2 ON Comment_Memo.target = members2.email
                WHERE 1 ";
        if($view==0){ // 메모에 대한 댓글
            $sql .= " AND idx_memo={$idx_memo}";
        }else if($view==1){
            $sql .= " AND email={$email}";
        }
        $sql .= " ORDER BY
        group_idx ASC,
        idx ASC";
        // echo $sql;

        $memoCommentsResult = $object_db -> get_table_results2(false, $sql);
        // var_dump($memoResult);
        // echo "<br>/";
        $result['result'] = "success";
        $result['sql'] = $sql;
        $result['memoCommentList'] = $memoCommentsResult['rows'];
        sendApiResponse($result);
        return;

    break;

    case "Delete_Book_Memo" : // 댓글 데이터 불러오기
        $sql = " DELETE FROM memo WHERE idx={$idx}";
        $Result = $object_db -> excute_query($sql);
        // var_dump($memoResult);
        // echo "<br>/";
        $result['sql'] = $sql;
        if ($Result !== false) {  // 성공
            $result['result'] = "success";
        } else { // 실패
            $result['result'] = "fail";
        } 
        sendApiResponse($result);
        return;
    break;

    case "Get_Memo_One" : // idx에 해당하는 memo가져오기
        $sql = "SELECT img_urls, memo, page, open FROM memo WHERE idx={$idx}";
        // echo $sql;

        $memoResult = $object_db -> get_table_result2(false, $sql);
        $result['result'] = "success";
        $result['sql'] = $sql;
        $result['memoData'] = $memoResult['row'];
        sendApiResponse($result);
        return;

    break;



    




}

$result['result'] = "success";

// json 내보내기
//echo json_encode($result);


?>