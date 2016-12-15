<?php
/**
 * Created by PhpStorm.
 * User: bnie
 * Date: 2016/7/7
 * Time: 17:06
 */

function cal_survey_result($obj_answers) {
    $diet_scores = array(
        '226923X75X812SQ001' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X812SQ002' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X812SQ003' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X812SQ004' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X812SQ005' => array('1' => 0,'2' => 1,'3' => 2,'4' => 3),
        '226923X75X812SQ006' => array('1' => 0,'2' => 1,'3' => 2,'4' => 3),
        '226923X75X812SQ007' => array('1' => 0,'2' => 1,'3' => 2,'4' => 3),
        '226923X75X812SQ008' => array('1' => 0,'2' => 1,'3' => 2,'4' => 3)
    );
    $exercise_scores = array(
        '226923X75X823SQ001' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X823SQ002' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X823SQ003' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X823SQ004' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0)
    );
    $hygiene_scores = array(
        '226923X75X828SQ001' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X828SQ002' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X828SQ003' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X828SQ004' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X828SQ005' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X828SQ006' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X828SQ007' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X828SQ008' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0),
        '226923X75X828SQ009' => array('1' => 3,'2' => 2,'3' => 1,'4' => 0)
    );
    $oral_cavity_scores = array(
        '226923X75X835' => array('0' => 3,'1' => 2,'2' => 1,'3' => 0)
    );
    $vision_scores = array(
        '226923X75X836' => array('1' => 0,'2' => 0,'3' => 1,'4' => 2, '5' => 2, '6' => 3)
    );

    $diet_score = $exercise_score = $hygiene_score = $oral_cavity_score = $vision_score = 0;
    $diet_star = $exercise_star = $hygiene_star = $oral_cavity_star = $vision_star = 0;

    $comments = array();
    $diet_comm = '';
    $diet_conditional_comm = '';
    $D_selected_1_to_4 = false;
    $A_selected_5_to_8 = false;
    $ABC_selected_9_10 = false;
    $has_decayed_teeth = false;
    $has_vision_problem = false;

    foreach($obj_answers as $answer) {
        if(in_array($answer->answer_id, array('226923X75X812SQ001', '226923X75X812SQ002', '226923X75X812SQ003', '226923X75X812SQ004'))) {
            if($answer->answer == '4') {
                $D_selected_1_to_4 = true;
            }
        }

        if(in_array($answer->answer_id, array('226923X75X812SQ005', '226923X75X812SQ006', '226923X75X812SQ007', '226923X75X812SQ008'))) {
            if($answer->answer == '1') {
                $A_selected_5_to_8 = true;
            }
        }

        if(in_array($answer->answer_id, array('226923X75X812SQ009', '226923X75X812SQ010'))) {
            if($answer->answer == '1' OR $answer->answer == '2' OR $answer->answer == '3') {
                $ABC_selected_9_10 = true;
            }
        }

        if($answer->answer_id == '226923X75X835') {
            if((int)$answer->answer > 0) {
                $has_decayed_teeth = true;
            }
        }

        if($answer->answer_id == '226923X75X836') {
            if($answer->answer != '5') {
                $has_vision_problem = true;
            }
        }

        if(isset($diet_scores[$answer->answer_id])) {
            $diet_score += $diet_scores[$answer->answer_id][$answer->answer];
        }else if(isset($exercise_scores[$answer->answer_id])) {
            $exercise_score += $exercise_scores[$answer->answer_id][$answer->answer];
        }else if(isset($hygiene_scores[$answer->answer_id])) {
            $hygiene_score += $hygiene_scores[$answer->answer_id][$answer->answer];
        }else if(isset($oral_cavity_scores[$answer->answer_id])) {
            if((int)$oral_cavity_scores[$answer->answer_id][$answer->answer] > 3) {
                $oral_cavity_score += 0;
            }else {
                $oral_cavity_score += $oral_cavity_scores[$answer->answer_id][$answer->answer];
            }
        }else if(isset($vision_scores[$answer->answer_id])) {
            $vision_score += $vision_scores[$answer->answer_id][$answer->answer];
        }
    }

    $lowest_star = 6;
    $diet_star = get_star_by_score((float)$diet_score/8);
    if($lowest_star > $diet_star) {
        $lowest_star = $diet_star;
    }
    $exercise_star = get_star_by_score((float)$exercise_score/4);
    if($lowest_star > $exercise_star) {
        $lowest_star = $exercise_star;
    }
    $hygiene_star = get_star_by_score((float)$hygiene_score/9);
    if($lowest_star > $hygiene_star) {
        $lowest_star = $hygiene_star;
    }
    $oral_cavity_star = get_star_by_score((float)$oral_cavity_score);
    if($lowest_star > $oral_cavity_star) {
        $lowest_star = $oral_cavity_star;
    }
    $vision_star = get_star_by_score((float)$vision_score);
    if($lowest_star > $vision_star) {
        $lowest_star = $vision_star;
    }

    if($D_selected_1_to_4) {
        $diet_conditional_comm .= '您的孩子有偏食倾向哦，请注意孩子营养的均衡。';
    }

    if($A_selected_5_to_8) {
        $diet_conditional_comm .= '您的孩子饮食习惯有点不太健康，请注意孩子的健康饮食习惯的培养哦。';
    }

    if($ABC_selected_9_10) {
        $diet_conditional_comm .= '测试结果显示，您是一个愿意为了孩子的营养健康付出的家长，请继续关心和爱护你的小宝贝哦。';
    }

    $diet_comm .= $diet_conditional_comm;
    if($diet_star == 3 OR $diet_star == $lowest_star) {
        $diet_comm .= '您的孩子饮食习惯不太健康哦。孩子一旦养成了坏的饮食习惯就很难再改变，但我们可以通过一些生活细节来改善这种状况，比如：（1）家里只准备健康的食物，这样即使是最挑剔的孩子，也能慢慢形成好的饮食习惯。（2）家长要以身作则。不要期望家长每天抱着各类饮料瓶，而孩子却会拒绝饮料。在孩子饮食观念培养过程中，只有家长以身作则的示范，才会对孩子起到最直观最有效的影响。（3）告诉孩子健康的饮食标准以及不良饮食习惯的后果。（4）可以试着改变食材种类、形状、颜色搭配、烹调方法，甚至是餐具的选择，这都可能产生意想不到的效果。';
    }
    if($diet_comm != '') {
        $comments[] = $diet_comm;
    }
    if($exercise_star == 3 OR $exercise_star == $lowest_star) {
        $comments[] = '您的孩子运动习惯还需要加油哦。儿童天生是爱运动的，我们要做的仅仅是提供适当的环境、条件和方法。建议您：（1）条件允许有必要从小每天带孩子都出去走走，让孩子觉得运动好玩。小朋友的好奇心一般都重，探究世界的热情也会越来越高。（2）给孩子提供一个安全的环境，且不干涉孩子自由地发展运动能力。（3）父母必须以身作则。父母是孩子的榜样，孩子就像是镜子会照出你的样子。你的一言一行，一举一动孩子都会模仿。（4）陪孩子一起运动。尽可能的给孩子创造运动环境吧。和孩子一起玩，一起运动，孩子身体健康，充满活力，心胸开阔。';
    }
    if($hygiene_star == 3 OR $hygiene_star == $lowest_star) {
        $comments[] = '您的孩子卫生习惯不太好哦。建议您：（1）多鼓励、多奖励孩子的讲卫生的好习惯。适当的奖励会使宝贝更有学习的热情，当尝到奖励带来的愉快体验时，下次就会表现得更好。（2）及时强化宝宝的好习惯。发现宝贝能自觉遵守卫生规则的行为要立即给予强化，直至形成习惯。（3）父母做好表率。如果父母或宝贝周围的成人没有良好的卫生习惯，要培养宝贝有好习惯就非常困难啦。（4）为宝宝勤洗手和刷牙等创造方便条件。如果水龙头太高，宝贝就可能为了省事而不去洗手。另外给宝宝提供美观又有趣的相应物品（如好看的牙刷、水杯等）会增加宝宝爱卫生的好习惯哦。（5）家里人要能做到一致一贯的要求。如果家里有人要求严，有人要求松，或者忽严忽松，都会增加好习惯养成的难度呢。';
    }
    if($has_decayed_teeth) {
        $comments[] = '您的孩子有龋齿。龋齿是多因素疾病，口腔卫生直接影响龋齿的发病率，发病原因多数是吃甜食过多、睡前不刷牙等。建议加强口腔卫生知识的宣传，除每天早晚刷牙外，还要选用合适的牙刷和正确的刷牙方法，尽量少吃零食、甜食，定期口腔检查，发现龋齿，及早治疗。';
    }
    if($has_vision_problem) {
        $comments[] = '您的孩子视力存在一定的问题。拥有一双清澈明亮的眼睛需要家人的关心与呵护。建议您在家里做到：（1）限制孩子近视距离用眼时间。（2）重视孩子的读写卫生，阅读与书写坐姿端正，距书距离保持在30—35 cm左右。（3）经常带孩子开展体育锻炼，增强室外活动，有助于降眼压。（4）改善家里的照明环境。';
    }

    return array(
        'stars' => array(
            'diet' => "$diet_star",
            'exercise' => "$exercise_star",
            'hygiene' => "$hygiene_star",
            'oral_cavity' => "$oral_cavity_star",
            'vision' => "$vision_star"
        ),
        'comments' => $comments
    );
}

function get_star_by_score($score) {
    if((float)$score == 3) {
        return 5;
    }else if((float)$score >= 2.5) {
        return 4.5;
    }else if((float)$score >= 2 && (float)$score < 2.5) {
        return 4;
    }else if((float)$score >= 1.5 && (float)$score < 2) {
        return 3.5;
    }else if((float)$score < 1.5) {
        return 3;
    }
}