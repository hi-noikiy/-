<?php
class ExPaperModel extends Model{
    var $tableName = 'ex_paper_content pc';
    public function getPaper($id){
    	$score=0;
        $subscript=array("A","B","C","D","E","F","G","H","I","J","K");
        $field="q.question_id,q.question_type,q.question_option_count,q.question_content,q.question_qsn_guide,pc.paper_content_point";
    	$content_list=$this->join(C('DB_PREFIX')."ex_question q ON pc.paper_content_questionid=q.question_id ")->order("RAND()")->where("q.question_is_del = 0 and pc.paper_content_paperid=".$id)->field($field)->select();
        foreach ($content_list as $key => $v) {
            $content_list[$key]["question_content"]=preg_replace("/<[a-z]+ [a-z]+=\"[a-z]+\" [a-z]+=\"[0-9]+\" \/>/", '______', $v["question_content"]);
        	$score+=$v["paper_content_point"];
            $question_answer="";
            $option_list = M('ex_option' )->where('option_question='.$v["question_id"])->field("option_id,option_content,option_item_id,is_right")->order('option_item_id')->findAll();
            foreach ($option_list as $k =>$answer) {
                if($v["question_type"]==3){
                     $question_answer.=$answer["option_content"].","; 
                }else{
                     if($answer["is_right"]==1){
                       $question_answer.=$subscript[$answer["option_item_id"]-1].",";
                    }
                }
            }
            if($v["question_type"]!=5){
                $content_list[$key]["question_answer"] = $question_answer{strlen($question_answer)-1} == ',' ? substr($question_answer, 0, -1) : $question_answer;
                $content_list[$key]["option_list"]=$option_list;
            }else{
              $content_list[$key]["question_answer"] =""; 
              $content_list[$key]["option_list"]=array();
            }
        }
        if($content_list){
            $_data["score"]=$score;
            $_data['paper_id']=$id;
            $_data['question_list']=$content_list;
            $_data["count"]=count($content_list);
        }
		return $_data;
    }
    public function getPaperInfo($paper_id){
        $data=M("ex_paper")->where("paper_id=".$paper_id)->find();
        return $data;
    }
    public function doPaperQuestion($question_type,$paper_id , $uid,$question_category){
        M('ex_paper_content')->where('paper_content_paperid='.$paper_id .' and paper_content_admin='.$uid)->delete();
        $questiontype=explode(",",$question_type);
        $count=0;
        $score=0;
        foreach ($questiontype as $key => $value) {
            $val=explode("-",$value);
            $question_type=$val[0];
            $question_count=$val[1];
            $question_list=M("ex_question")->where("question_type=".$question_type)->where('question_category='.$question_category)->findAll();
            if(count($question_list)<$question_count){
                foreach ($question_list as $k => $v) {
                    $data=array(
                        "paper_content_paperid"=>$paper_id,
                        "paper_content_questionid"=>$v["question_id"],
                        "paper_content_point"=>$v["question_point"],
                        "paper_content_item"=>$k+1,
                        "paper_content_admin"=>$uid,
                        "paper_content_update_date"=>time(),
                        "paper_content_insert_date"=>time(),
                    );
                    $result = M('ex_paper_content')->data($data)->add();
                    if($result){
                        $score+=$v["question_point"];
                        $count++;
                    }
                }
            }else{
                if($question_count==1){
                    $data=array(
                        "paper_content_paperid"=>$paper_id,
                        "paper_content_questionid"=>$question_list[0]["question_id"],
                        "paper_content_point"=>$question_list[0]["question_point"],
                        "paper_content_item"=>1,
                        "paper_content_admin"=>$uid,
                        "paper_content_update_date"=>time(),
                        "paper_content_insert_date"=>time(),
                    );
                    $result = M('ex_paper_content')->data($data)->add();
                    if($result){
                        $count++; 
                        $score+=$question_list[0]["question_point"];
                    }
                }else{
                    //获取随机的问题ID
                    $question_id=array();
                    foreach ($question_list as $v) {
                        $question_id[]=$v["question_id"];
                    }
                    $random_array=array();
                    //第二部分随机问题（不重复）
                    $random_array=array_rand($question_id,$question_count);
                    foreach ($random_array as $r => $random) {
                        $data=array(
                            "paper_content_paperid"=>$paper_id,
                            "paper_content_questionid"=>$question_list[$random]["question_id"],
                            "paper_content_point"=>$question_list[$random]["question_point"],
                            "paper_content_item"=>$r+1,
                            "paper_content_admin"=>$uid,
                            "paper_content_update_date"=>time(),
                            "paper_content_insert_date"=>time(),
                        );
                        $result = M('ex_paper_content')->data($data)->add();
                        if($result){
                            $count++;
                            $score+=$question_list[$random]["question_point"];
                        }
                    }
                } 
            }
        }
        unset($data);
        $data["count"]=$count;
        $data["score"]=$score;
        return $data;
    }	
}
?>