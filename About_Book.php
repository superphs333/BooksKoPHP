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
// $accept_sort = "Edit_in_my_book";
 //$_POST['email']= "leesotest@gmail.com";
//  $_POST['status']= "4";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
// $_POST['isbn']= "8901219948";
/////////////////////////////////////


// $result = $_POST;

// $accept_sort = $result['accept_sort'] ?? '';
// $sort = $result['sort'] ?? '';
// $input = $result['input'] ?? '';
// $from = $result['from'] ?? '';
// $email = $result['email'] ?? '';
// $book_idx = $result['book_idx'] ?? '';
// $title = $result['title'] ?? '';
// $authors = $result['authors'] ?? '';
// $publisher = $result['publisher'] ?? '';
// $isbn = $result['isbn'] ?? '';
// $contents = $result['contents'] ?? '';
// $thumbnail = $result['thumbnail'] ?? '';
// $rating = $result['rating'] ?? '';
// $status = $result['status'] ?? '';
// $search = $result['search'] ?? '';
// $review = $result['review'] ?? '';
// $idx = $result['idx'] ?? '';
// $memo_idx = $result['memo_idx'] ?? '';
// $open = $result['open'] ?? '';
// $page = $result['page'] ?? '';
// $memo = $result['memo'] ?? '';
// $size = $result['size'] ?? '';
// $book_idx = $result['book_idx'] ?? '';
// $book_cover_img_destination = $result['book_cover_img_destination'] ?? '';

$result = [];
foreach($_POST as $key => $value) {
    $result[$key] = $value;
}
extract($result);

/*
dbclass 생성
*/
$object_db = new class_db($connect);
$object_db->set_table('members');

/*
도서 관련 클래스
*/
$object_book = new class_book($connect);

/*
함수 관련 클래스
*/
$object_function = new class_function();

/*
accept_sort에 따라 구분
*/
switch ($accept_sort){
    case "Check_in_mybook" : // (도서검색에서 추가할 경우 씀)마이 북에 해당 책이 있는지 검색
        $object_db->set_table('My_Book');
        $count = $object_db->get_total_count("*","email='{$email}' and isbn={$isbn}",true);
        $result['query']=$count['query'];
        if($count['count']>0){$result['result']="yes";}else{$result['result']="no";}
    break;

    case "Book_Add_in_Search" : // book에 책 추가 한 후에, My_Book에도 추가
      // book에 책 추가
      $sql = "INSERT INTO book(title,authors,publisher,isbn,thumbnail,contents,from_) VALUES('{$title}','{$authors}','{$publisher}','{$isbn}','{$thumbnail}','{$contents}','{$from}')";
      $result['sql_insert'] = $sql;
      $result_qry = $object_db->excute_query($sql);
      if($result_qry){
          $result['book_add_status'] = "new_book";
          $result['sql_success'] = "success";
          $result['result'] = "success";
      }else{

        // 만약, 에러가 duplicate에 의한 에러라면
        // -> 데이터베이스(My_Books)에 해당책 저장

        // 에러원인
        $sql_error_cause = substr(mysqli_error($connect),0,9);
        $result['sql_error_cause'] = $sql_error_cause;

        if($sql_error_cause!="Duplicate"){
            $result['book_add_status'] = "error";
            $result['sql_error'] = "fail";
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            echo json_encode($result);
            return;
        }else{
            $result['book_add_status'] = "already_add_book";
        }
      }

      /*
      나의 도서에 저장
      */
        // 저장한 책의 idx가져오기
        $object_db->set_table('book');
        $book_idx_result = $object_db->get_table_result(true,"idx","isbn='{$isbn}' and from_='search'");
        $result['qry_book_idx'] =$book_idx_result['query'];
        $book_idx = $book_idx_result['row']['idx'];
        $result['book_idx'] = $book_idx;
      
      // 마이북에 책 저장
      //$save_in_my_book_result = //$object_book->save_in_my_book_list($result);
      // $result['save_my_book_qry'] = $save_in_my_book_result['qry'];
      // 만약 status값이 없으면 0
      
      $sql2 = "INSERT INTO My_Book(book_idx,`email`, `isbn`, `status`, `rating`,`from_`) VALUES({$book_idx},'{$email}','{$isbn}','{$status}','{$rating}','search')";
      $result_qry2 = $object_db->excute_query($sql2);
      $result['save_in_my_book_qry'] =  $sql2;
      if($result_qry2){
        $result['sql_success'] = "success";
        $result['result'] = "success";
    }else{
        $result['sql_error'] = "fail";
        $result['sql_success'] = "fail";
        $result['result'] = "fail";
        echo json_encode($result);
        return;
    }

    break;

    case "My_Books" :
        // 받는 값 : search(검색값), status(상태)
        $sql = "SELECT b.idx,b.title,b.authors,b.thumbnail,b.contents,b.from_,b.isbn,mb.status, mb.rating, mb.review, b.publisher FROM My_Book AS mb LEFT JOIN book as b ON mb.book_idx=b.idx";
        $sql .= " WHERE 1 and mb.email='{$email}'";
        // 검색값이 있는 경우
        if($search){
            $sql .= " and (b.title LIKE '%{$search}%' OR b.authors LIKE '%{$search}%')";
        }
        
        // status 
        if($status!=4){
            $sql .= " and status={$status}";
        }
        $sql .= " ORDER BY b.idx DESC";
        
        $book_Result = $object_db->get_table_results2(false,$sql);
        
        $result['result'] = "success";
        $result['sql'] = $sql;
        $result['bookList'] = $book_Result['rows'];
        sendApiResponse($result);
        return;
    break;

    // (직접도서추가) 책 저장하고 나의 북에도 저장
    case "Save_in_my_book" : 
    case "Edit_in_my_book" :
        /*
        파일받기 
        */
        if(isset($_FILES['uploadedfile']['name'])){ // 파일이 있는 경우에만
            $result['uploadedfile_name'] = $_FILES['uploadedfile']['name'];

            if(!$_FILES['uploadedfile']['error']){ // 파일에러 없는 경우에만
                $result['uploadedfile_error'] = "no";

                // 경로지정
                $book_cover_img_destination = "/Img_Book_Cover/".$_FILES['uploadedfile']['name'];
                $destination = $_SERVER['DOCUMENT_ROOT'].$book_cover_img_destination;
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
                echo json_encode($result);
                $result['uploadedfile_error'] = $_FILES['uploadedfile']['error'];
                $result['result'] = "fail";
                sendApiResponse($result);
                return;
            }
        }

        /*
        도서저장 or 수정
        */
        if($accept_sort=="Save_in_my_book"){ // 추가
            // unique_book_value 값
            $unique_book_value = date('ymdHis')."_".$object_function->getRandStr();
            $sql = "INSERT INTO book(title,authors,publisher,isbn,thumbnail,contents,from_,unique_book_value) VALUES('{$title}','{$authors}','{$publisher}','{$isbn}','{$book_cover_img_destination}','{$contents}','add','{$unique_book_value}')";
            $result['sql_insert'] = $sql;
            $result_qry = $object_db->excute_query($sql);
            if($result_qry){
                $result['sql_success'] = "success";
                $result['result'] = "success";
            }else{
                $result['sql_success'] = "fail";
                $result['result'] = "fail";
                sendApiResponse($result);
                return;
            }

            /*
            나의 도서에 저장
            */
            // 저장한 책의 idx가져오기
            $object_db->set_table('book');
            $book_idx_result = $object_db->get_table_result(true,"idx","unique_book_value='{$unique_book_value}' and from_='add'");
            $result['qry_book_idx'] =$book_idx_result['query'];
            $book_idx = $book_idx_result['row']['idx'];
            $result['book_idx'] = $book_idx;
                
            $sql2 = "INSERT INTO My_Book(book_idx,`email`, `isbn`, `status`, `rating`,`from_`) VALUES({$book_idx},'{$email}','{$isbn}','{$status}','{$rating}','add')";
            $result_qry2 = $object_db->excute_query($sql2);
            $result['save_in_my_book_qry'] =  $sql2;
            if($result_qry2){
                $result['sql_success'] = "success";
                $result['result'] = "success";
                sendApiResponse($result);
            }else{
                $result['sql_error'] = "fail";
                $result['sql_success'] = "fail";
                $result['result'] = "fail";
                sendApiResponse($result);
                return;
            }
        }else if($accept_sort=="Edit_in_my_book"){ // 수정 -> book, My_Book 모두 수정
            //$sql_book = "UPDATE book as b inner join My_Book as mb ON b.idx=mb.book_idx SET b.title='{$title}', b.authors='{$authors}', b.publisher='{$publisher}', b.isbn='{$isbn}',mb.isbn='{$isbn}', b.thumbnail='{$book_cover_img_destination}', b.contents='{$contents}', mb.rating={$rating}, mb.status={$status} ";
            $sql_book .= "UPDATE book as b inner join My_Book as mb ON b.idx=mb.book_idx";
            $sql_book .= " SET b.title='{$title}', b.authors='{$authors}', b.publisher='{$publisher}', b.isbn='{$isbn}',mb.isbn='{$isbn}', b.contents='{$contents}', mb.rating={$rating}, mb.status={$status} ";

            if(isset($_FILES['uploadedfile']['name'])){
                // 파일이 있는 경우에만
                $sql_book .= ", b.thumbnail='{$book_cover_img_destination}'";
            }
            $sql_book .= " WHERE b.idx={$idx}";
            $result['sql_book'] = $sql_book;

            $result_qry = $object_db->excute_query($sql_book);
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


    case "edit_my_book" :
        $sql = "UPDATE My_Book SET {$sort}='{$input}' WHERE email='{$email}' and book_idx={$book_idx}";
        $result['qry'] = $sql;
        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";
        }else{
            $result['sql_error'] = "fail";
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            echo json_encode($result);
            return;
        }
    break;

    case "delete_my_book" :
        $sql = "DELETE FROM My_Book WHERE book_idx={$book_idx}";
        $result['qry'] = $sql;
        $result_qry = $object_db->excute_query($sql);
        if($result_qry){
            $result['sql_success'] = "success";
            $result['result'] = "success";
        }else{
            $result['sql_error'] = "fail";
            $result['sql_success'] = "fail";
            $result['result'] = "fail";
            echo json_encode($result);
            return;
        }
    break;
}



// json 내보내기
echo json_encode($result);

?>  