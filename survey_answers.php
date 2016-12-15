<?php
/**
 * Created by PhpStorm.
 * User: bnie
 * Date: 2016/7/6
 * Time: 9:06
 */
require_once ('config.php');
require_once ('database.php');

//check token exist or not in redis to a set specific with sid : survey_sid_tokens
function check_token_exist($sid, $token) {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $result = $redis->sIsMember('survey_'. $sid .'_tokens', $token);
    $redis->close();
    return $result;
}

//组合answers的insert语句
function make_up_answers_insert_query($sid, $obj_answers) {
    $time_now = date('Y-m-d H:i:s');
    $query = "insert into `". DB_TABLE_NAME_PREFIX ."survey_". $sid ."` ";
    $query .= " (`submitdate`,`lastpage`,`startlanguage`";
    $value = " ('". $time_now ."',1,'zh-Hans'";
    foreach($obj_answers as $answer) {
        $query .= ",`". $answer->answer_id ."`";
        $value .= ",'". $answer->answer ."'";
    }
    $query .= ") values ". $value . ")";
    return $query;
}

//组合智慧树提供的user_info部分的insert语句
function make_up_user_info_insert_query($sid, $user_info, $insert_answer_id) {
    $query = "insert into `". DB_TABLE_NAME_PREFIX ."survey_". $sid ."_bbtree` ";
    $query .= " (`aid`";
    $value = " ('". $insert_answer_id ."'";
    foreach($user_info as $key=>$value) {
        $query .= ",`". $key ."`";
        $value .= ",'". $value ."'";
    }
    $query .= ") values ". $value . ")";
    return $query;
}

//insert answers into db
function insert_answers($sid, $answers, $user_info) {
    global $mysql_database, $mysql_password, $mysql_server_name, $mysql_username;
    $mysqli = new mysqli($mysql_server_name, $mysql_username, $mysql_password, $mysql_database);
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error,500);
    }

    if (!$mysqli->set_charset("utf8mb4")) {
        throw new Exception($mysqli->error,500);
    }

    $survey_answers_query = make_up_answers_insert_query($sid, $answers);
    if ($mysqli->query($survey_answers_query) === FALSE) {
        throw new Exception($mysqli->error,500);
    }

    $insert_answer_id = $mysqli->insert_id;

    //如果user_info非空，则插入相关信息
    if(!empty(get_object_vars($user_info))) {
        $survey_user_info_query = make_up_user_info_insert_query($sid, $user_info, $insert_answer_id);
        if ($mysqli->query($survey_user_info_query) === FALSE) {
            throw new Exception($mysqli->error,500);
        }
    }
    $mysqli->close();
}

//delete token from redis in a set specific with sid : survey_sid_tokens
function delete_token_from_redis($sid, $token) {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $result = $redis->sRem('survey_'. $sid .'_tokens', $token);
    $redis->close();
    return $result;
}

//根据答案计算出结果
function get_survey_result($answers) {
    $data = array();
    if(function_exists('cal_survey_result')) {
        $data = cal_survey_result($answers);
    }
    return $data;
}

try{
    $post_json_str = file_get_contents('php://input');
    $obj_json_str = json_decode($post_json_str);
    if(!property_exists($obj_json_str, 'sid') || !property_exists($obj_json_str, 'token') || !property_exists($obj_json_str, 'answers') || !property_exists($obj_json_str, 'user_info')) {
        throw new Exception('post parameters error',400);
    }
    $sid = $obj_json_str->sid;
    $token = $obj_json_str->token;
    $answers = $obj_json_str->answers;
    $user_info = $obj_json_str->user_info;

    if(file_exists('survey_'. $sid .'_result.php')) {
        require_once ('survey_'. $sid .'_result.php');
    }

    //验证token
    if(!check_token_exist($sid, $token)) {
        throw new Exception('wrong token',400);
    }

    insert_answers($sid, $answers, $user_info);

    $data = get_survey_result($answers);

    delete_token_from_redis($sid, $token);

    echo json_encode(
        array(
            'status' => '200',
            'msg' => 'success',
            'error' => '',
            'data' => $data
        )
    );
}catch (Exception $e){
    echo json_encode(
        array(
            'status' => (string)$e->getCode(),
            'msg' => 'fail',
            'error' => $e->getMessage(),
            'data' => array()
        )
    );
}