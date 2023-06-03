<?php
// DB 관련 

class class_db{
    var $connect;
    var $table;

    // 생성자
    function __construct($connect){
        $this->connect = $connect;
    }

    // 테이블 셋팅
    function set_table($table){
        $this->table = $table;
    }

    // 쿼리문 실행
    function excute_query($query){
        return $this->connect->query($query);
    }

    // 데이터 저장
    function insert(){
        
    }

    /*
    컬럼의 개수 구하기
        $r = 컬럼이름
        $w = where 조건
        $d = debug 모드
    */
    function get_total_count($r='*',$w=false,$d=false){
        $result_array = array(); // 결과를 담을 배열
        $query = "select count({$r}) as cnt from {$this->table}";
        if($w){$query .= " WHERE ".$w;}
        if($d){ $result_array['query']=$query;}
        // 쿼리문 실행
        $result = mysqli_query($this->connect,$query);
        $row = mysqli_fetch_array($result);
        $result_array['count'] = $row['cnt'];
        return $result_array;
    }

    /*
    단일 result 구하기
        bool $qry : 쿼리 출력 여부
        string $column : 구하려고 하는 컬럼
        string $where : 조건
    */
    function get_table_result($qry,$column,$where){
        $result_array = array(); // 결과를 담을 배열
        $qry = "SELECT $column FROM {$this->table}";
        if($where){$qry .= " WHERE $where";}
        if($qry){$result_array['query']=$qry;}
        $result = mysqli_query($this->connect,$qry);
        if($result){
            $result_array['row']=mysqli_fetch_assoc($result);
        }else{
            $result_array['error']=true;
        }
        return $result_array;
    }

    /*
    다중 result 구하기
        bool $qry : 쿼리 출력 여부
        string $column : 구하려고 하는 컬럼
        string $where : 조건
        string $orderby : 순서
        boolean $limit : 출력 처음~마지막 사용할 것인지 여부
        int $limit_start : 출력 처음
        int $limit_last : 출력 마지막
    */
    function get_table_results($qry,$column, $where, $orderby,$limit,$limit_start, $limit_last){
        $result_array = array(); // 결과를 담을 배열
        $qry = "SELECT $column FROM {$this->table}";
        if($where){$qry .= " WHERE $where";}
        if($orderby){$qry .= " ORDER BY $orderby";}
        if($limit) {$qry .= " LIMIT $limit_start, $limit_last";}
        if($qry){ $result_array['query']=$qry;}
        $result = mysqli_query($this->connect,$qry);
        if($result){
           // $result_array['results']=$result;

            $menu = array(); // 결과를 담을 배열
            while($row=mysqli_fetch_assoc($result)){
                $menu[] = $row;
            }

            $result_array['rows']=$menu;
            

        }else{$result_array['error']=true;}

        return $result_array;
    }

    /*
    단일 result 구하기
        bool $qry : 쿼리 출력 여부
        string $column : 구하려고 하는 컬럼
        string $where : 조건
    */
    function get_table_result2($qry,$qry_string){
        $result_array = array(); // 결과를 담을 배열
        if($qry){$result_array['query']=$qry_string;}
        $result = mysqli_query($this->connect,$qry_string);
        if($result){
            $result_array['row']=mysqli_fetch_assoc($result);
        }else{
            $result_array['error']=true;
            $result_array['error_cause']=mysqli_error($this->connect);
        }
        return $result_array;
    }


    /*
    다중 result 구하기2
        -> 매개변수 쿼리로만 
        bool $qry : 쿼리 출력 여부
        string $qry_string : 구하려고 하는 컬럼
    */
    function get_table_results2($qry,$qry_string){
        $result_array = array(); // 결과를 담을 배열
        if($qry){ $result_array['query']=$qry;}
        $result = mysqli_query($this->connect,$qry_string);
        if($result){
           // $result_array['results']=$result;

            $menu = array(); // 결과를 담을 배열
            while($row=mysqli_fetch_assoc($result)){
                $menu[] = $row;
            }

            $result_array['rows']=$menu;
            $result_array['count']=count($menu);
            

        }else{$result_array['error']=true;}

        return $result_array;
    }



    
}
?>