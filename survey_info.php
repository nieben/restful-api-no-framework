<?php
/**
 * Created by PhpStorm.
 * User: bnie
 * Date: 2016/7/04
 * Time: 9:06
 * 获得某个survey全部信息
 */
require_once ('config.php');
require_once ('database.php');

//检查ticket是否正确
function check_ticket($ticket) {
    if(md5(md5(TICKET)) !== (string)$ticket) {
        return FALSE;
    }else {
        return TRUE;
    }
}

//检查sid是否存在
function check_sid_exist($mysqli, $sid) {
    $sql = "SELECT count(*) as num FROM ". DB_TABLE_NAME_PREFIX ."surveys WHERE sid = ".$sid." ";
    if(($result = $mysqli->query($sql)) === FALSE) {
        throw new Exception('sql error',500);
    }
    $row = $result->fetch_array();
    if((int)$row['num'] == 0) {
        $result->close();
        return FALSE;
    }
    $result->close();
    return TRUE;
}

//获得某一survey信息
function get_survey_info($sid) {
    $token = generate_token();
    //check redis first
    if(($redis_survey_info = check_survey_exist_in_redis($sid)) !== FALSE) {
        $obj_redis_survey_info = json_decode($redis_survey_info);
        $obj_redis_survey_info->token = $token;
        if(write_token_to_redis($sid, $token) != 1) {
            throw new Exception('write token error',500);
        }
        return json_encode($obj_redis_survey_info);
    }else {
        $survey_info = array(
            'status' => '200',
            'msg' => 'success',
            'error' => '',
            'data' => array()
        );
        $data = get_survey_data_from_db($sid);
        $survey_info['data'] = $data;
        write_survey_info_to_redis($survey_info, $sid);
        $survey_info['token'] = $token;
        if(write_token_to_redis($sid, $token) != 1) {
            throw new Exception('write token error',500);
        }
        return json_encode($survey_info);
    }
}

//get survey data from database
function get_survey_data_from_db($sid) {
    global $mysql_database, $mysql_password, $mysql_server_name, $mysql_username;
    $mysqli = new mysqli($mysql_server_name, $mysql_username, $mysql_password, $mysql_database);
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error,500);
    }

    if (!$mysqli->set_charset("utf8mb4")) {
        throw new Exception($mysqli->error,500);
    }

    //先检查sid是否存在
    if(!check_sid_exist($mysqli, $sid)) {
        throw new Exception('sid not exist',400);
    }

    $basic_sql = "SELECT surveyls_title, surveyls_welcometext FROM ". DB_TABLE_NAME_PREFIX ."surveys_languagesettings WHERE surveyls_survey_id = ".$sid." LIMIT 1";
    if(($basic_result = $mysqli->query($basic_sql)) === FALSE) {
        throw new Exception('sql error',500);
    }
    if(empty($basic_row = $basic_result->fetch_array())) {
        throw new Exception('sql error', 500);
    }
    $data = array();
    $data['sid'] = $sid;
    $data['answer_separator'] = ANSWER_SEPARATOR;
    $data['surveyls_title'] = $basic_row['surveyls_title'];
    $data['surveyls_welcometext'] = $basic_row['surveyls_welcometext'];
    $data['groups'] = get_groups_questions_answers_info($mysqli, $sid);
    $mysqli->close();
    return $data;
}

//获得survey_info结构里的data数组
function check_survey_exist_in_redis($sid) {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $result = $redis->get('survey_'. $sid .'_info');
    $redis->close();
    return $result;
}

//write survey info to redis, except the token info
function write_survey_info_to_redis($survey_info, $sid) {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $result = $redis->set('survey_'. $sid .'_info', json_encode($survey_info));
    $redis->close();
    return $result;
}

//write token to redis to a set specific with sid : survey_sid_tokens
function write_token_to_redis($sid, $token) {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $result = $redis->sAdd('survey_'. $sid .'_tokens', $token);
    $redis->close();
    return $result;
}

//获得group，question，answer信息
function get_groups_questions_answers_info($mysqli, $sid) {
    $groups_associative = array();
    $sql = "select g.gid, g.group_name, g.group_order, g.description, q.qid, q.parent_qid, q.type, q.title, q.question, q.mandatory, q.question_order,
                                 q.scale_id as qscale_id, a.code, a.answer, a.sortorder, a.scale_id as ascale_id
                                 from ". DB_TABLE_NAME_PREFIX ."groups g
                                 left join ". DB_TABLE_NAME_PREFIX ."questions q on q.gid = g.gid
                                 left join ". DB_TABLE_NAME_PREFIX ."answers a on a.qid = q.qid
                                 where g.sid = $sid
                                 order by g.group_order asc, q.question_order asc, a.sortorder asc";
    if(($result = $mysqli->query($sql)) === FALSE) {
        throw new Exception('sql error',500);
    }
    foreach($result->fetch_all(MYSQLI_ASSOC) as $row) {
        if(!isset($groups_associative[$row['gid']])) {
            $groups_associative[$row['gid']] = array(
                'gid' => $row['gid'],
                'group_name' => $row['group_name'],
                'group_order' => $row['group_order'],
                'description' => $row['description'],
                'questions' => array()
            );
        }
        if($row['qid'] != NULL && !isset($groups_associative[$row['gid']]['questions'][$row['qid']])) {
            $groups_associative[$row['gid']]['questions'][$row['qid']] = array(
                'qid' => $row['qid'],
                'parent_id' => $row['parent_qid'],
                'type' => $row['type'],
                'title' => $row['title'],
                'question' => $row['question'],
                'mandatory' => $row['mandatory'],
                'question_order' => $row['question_order'],
                'scale_id' => $row['qscale_id'],
                'answers' => array()
            );
        }
        if($row['code'] != NULL && !isset($groups_associative[$row['gid']]['questions'][$row['qid']]['answers'][$row['sortorder']])) {
            $groups_associative[$row['gid']]['questions'][$row['qid']]['answers'][$row['sortorder']] = array(
                'code' => $row['code'],
                'answer' => $row['answer'],
                'sortorder' => $row['sortorder'],
                'scale_id' => $row['ascale_id']
            );
        }
    }
    $groups_numeric = array_assoc_to_numeric($groups_associative);
    return $groups_numeric;
}

function array_assoc_to_numeric($array) {
    $array_numeric = array();
    foreach($array as $key=>$value) {
        if(is_array($value) && !empty($value)) {
            if($key == 'questions' || $key == 'answers') {
                $array_numeric[$key] = array_assoc_to_numeric($value);
            }else {
                $array_numeric[] = array_assoc_to_numeric($value);
            }
        }else {
            $array_numeric[$key] = $value;
        }
    }
    return $array_numeric;
}

//生成token, 32位md5码
function generate_token() {
    return md5(uniqid(mt_rand(),TRUE));
}

try{
    if(!isset($_GET['sid']) || !isset($_GET['ticket'])) {
        throw new Exception('url parameters error',400);
    }
    $sid = $_GET['sid'];
    $ticket = $_GET['ticket'];

    //验证ticket
    if(!check_ticket($ticket)) {
        throw new Exception('wrong ticket',400);
    }

    echo get_survey_info($sid);
}catch (Exception $e){
    echo json_encode(
        array(
            'status' => (string)$e->getCode(),
            'msg' => 'fail',
            'error' => $e->getMessage(),
            'data' => '',
            'token' => ''
        )
    );
}
