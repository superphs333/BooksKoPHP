<?php
/*
다양한 함수 class
*/

class class_fcm{

    var $ch_fcm;
    var $header_fcm;


    // 생성자
    function __construct(){

    }

    // 임의의 문자열 생성
    function send($data) {
        
        /*
        기존 : Cloud Messaging API (Legacy)
        */
        // 세션을 초기화하고 다른 curl 함수에 전달 할 수 있는 curl핸들을 반환한다
        $ch_fcm = curl_init("https://fcm.googleapis.com/fcm/send");

        $header_fcm = array("Content-Type:application/json", "Authorization:key=AAAAL0HBuYI:APA91bG0st89QFBIW8apLm314-dY1LHaZ5qw-GETX-GeSrd5oN5L_1OE7-d7XpFpIz2kBiuMq7hc_ttHyn3TuSieyQdhm6d9p4Pwuku84K0BVrI0_7RTsx2ctqIwcSCkrBYbIscHkuOf");   
        // curl 옵션
        curl_setopt($ch_fcm, CURLOPT_HTTPHEADER, $header_fcm);
        curl_setopt($ch_fcm, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch_fcm, CURLOPT_POST, 1);
        curl_setopt($ch_fcm, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch_fcm,CURLOPT_RETURNTRANSFER,true);

        // curl 세션 시작
        $result = curl_exec($ch_fcm);

        // curl 세션 종료
        curl_close($ch_fcm);

        return $result;

        /*
        새로운 버전( Firebase Cloud Messaging (FCM) API V1 )
        */
        // $url = 'https://fcm.googleapis.com/v1/projects/booksko-93d79/messages:send';
        // $projectID = "booksko-93d79";
        // $serverKey = "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDPkUKq5gG0IO/n\noOLaictgFWjsotjdAQfSr8r5RaqKf90DwX/d7aBR9fjNkIvwGYK1h0vVWh6TQgGw\nkmP/WfFOAtb/m7eiqLIA2ZggMSLZY19PclmkA8hQGc0pM+6HP08+wVFT2JfgFXku\ns8uPeu2NTILE89z5B1c4OrjvLZcPihikyg9vWcz1ISAp4ge7mXqLvDlQPYeGcIfZ\nSWOPhxMwaNNwLD4I8UsGrlXkIeOUJ0dGqTsShj0JpvOtpEQSL1qEL5fquGe6CUnI\n1Z81GUTz44DfJt3X09Xv2GsJGaQp8IuDKLW0bAg5tKRS68e4SP/wVENJRA35aEVJ\nSNJqdCgfAgMBAAECggEANBs/JS/AVbu+/eLBkCLvTxuQz3bCAhj0IcvHpHP8r/AR\nR5QXyVqR2IFrCA+1UXzV90QToWeSLV1wlybou32UugS0fE5W9xpqNRRqwAoVuYTc\ntFD8d9QLQTB49FL5GjKmbhaRl/7TgYlidnrkKwys/b+rRE4DtvBE0PTM4B4NwfRx\nJrucOLZPY3Fpr6SMhmPsBF4ct7+ttq9S5vB7qLyl3TfZoL9wWFlaEhTiSai0FPSC\nuL38cX1raGdNFegi41l2XhC2GsTT/T2IliMWicxb63a+c3D1xXdzvASj6s4xTOtM\nSzJEM/GGoiNaCoFFpQiUVO/gVyZXws0rMBzSdE8BQQKBgQDnRqaG7y0Phmz/fdXx\n/SihPwwZT8zYbqo+/trh0a5nG+BI8kqLDGCBjIOPu53Uo55LqA86T74PiSuui2KH\n1bo/TYgbY3CgEtCKBbNhqbSYHnG4RDlhK75jZB6Ct9J1E8zQ0TkRAGsOpwUAqzzJ\nkWTa2diLWyc+viuAtzOmPbj2+QKBgQDlwcafZo3V3br05lU3XvSDxmZKHEGLVFHO\nQ6ExfjsJKiqy0KV+lRfwM0pDOXYW0YeObZs6t0zs3d03HFMmjY2KYmyqWK2QeZMo\nZUdDgYOVH1ibseduQbGX1jlBRPvOwAzOMzwF89Y8rLwFx/9oHRWrAiCDuXoAkjyc\nxzyn9LDl1wKBgHL7vjlh2k5YsAJKRr8b9UJNvS8sbJTCWGQfgyU4gXQD+PtrcsI0\n8hoWiSZByhN5EW9d68w67yx8LzqFVARir0lfu6aaRtle4U1tziRlIkNrB3Dsgnac\nL/jsQvsMd1b79B1xl+SrB47uXN9bQ0qXvcPNAQsv05AvLiO9cbFaCIbJAoGAPZ7o\nD45o0ghDATXZex1LhSAsBQppBd5ahnCbBfQuDzow836ENFv2bKTE8RyzMFGIAsog\nzPGmfwzOLN666mcipA/bxyA7hLkmn7nyEAfna5JZqIBhaq/R2sBI4NmIk53skU0q\ndwo71lAZqY9HT/wk+JV8dPfE4exWt1G0UfONkkcCgYBnsmodR07xSEeE3HJSYoyk\nnRBLkpQ2gYBS2FPu6yxa7yEur5upsK43osKbsmf8qWT9t+kgP7Alli6XwyB5KIf4\nrZCCERowAuzNNj8iZXDrSTNmPepjzDuG3T9dXrvhsOBqxAw/3djvnHifMSnkmkHR\nj3SxJCb4UL3gjCLUrz7q8A==\n-----END PRIVATE KEY-----\n";
    
        // $headers = [
        //     'Content-Type: application/json',
        //     'Authorization: Bearer '.$serverKey
        // ];
    
        // $payload = [
        //     'message' => [
        //         'token' => $data['token'], // 수신자의 FCM 등록 토큰
        //         'data' => $data['data'] // 메시지 데이터
        //     ]
        // ];
    
        // $ch = curl_init($url);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
        // $response = curl_exec($ch);
        // curl_close($ch);
    
        // return $response;
    }
    
 
}
?>