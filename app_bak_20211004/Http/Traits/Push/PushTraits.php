<?php
namespace App\Http\Traits\Push;

use App\Lib\FcmPusherLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Emp\be_dept;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_rp_member;
use App\Model\Supply\b_supply_rp_project_license;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_check_topic;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;
use Uuid;
use Session;
use Auth;
use Lang;
use DB;
/**
 * 推播模組
 * 1. 個人通知
 * 2. 群組通知
 * 3. 審查通知
 * 4. 工作許可證通知
 * 9. 系統ＪＯＳＮ
 */
trait PushTraits {

    /**
     * 事件：ＴＥＳＴ推播
     */
    protected function pushToTestSuccess($target_cust,$login_time = '')
    {
        if(!$target_cust) return false;
        //所需參數
        $push_type  = 1;
        $name       = User::getName($target_cust);
        if(!$login_time) $login_time = date('Y-m-d H:i:s');
        //組合 推播訊息
        $title = Lang::get('sys_push.P100000_T',['time'=>date('Y-m-d H:i:s')]);
        $cont  = Lang::get('sys_push.P100000_C',['name1'=>$name,'name2'=>$login_time]);

        return $this->push($target_cust,$title,$cont,$push_type,3);
    }
    /**
     * 事件：ＡＰＰ登入成功
     */
    protected function pushToLoginSuccess($target_cust,$login_time = '')
    {
        if(!$target_cust) return false;
        //所需參數
        $push_type  = 1;
        $name       = User::getName($target_cust);
        if(!$login_time) $login_time = date('Y-m-d H:i:s');
        //組合 推播訊息
        $title = Lang::get('sys_push.P100001_T',['time'=>date('Y-m-d H:i:s')]);
        $cont  = Lang::get('sys_push.P100001_C',['name1'=>$name,'name2'=>$login_time]);
        return $this->push($target_cust,$title,$cont,$push_type);
    }

    /**
     * 事件：承攬商申請教育訓練報名<群組：承攬商>
     */
    protected function pushToSupplyApplyTraning($work_id,$b_suuply_id,$target_dept,$project_id = '')
    {
        if(!$target_dept) return false;
        //所需參數
        $push_type      = 2;
        $targetlist     = [0,0,0,$work_id,7,[]];
        $supply_name    = b_supply::getSubName($b_suuply_id);
        $permit_no      = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500001_T',['time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500001_C',['name1'=>$supply_name,'name2'=>$permit_no]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }

    /**
     * 事件：審查成員申請結果<群組：承攬商申請人>
     */
    protected function pushToRPMemberApplyResult($id,$isOk = '')
    {
        if(!$id && !$isOk) return false;
        //所需參數
        $push_type   = 3;
        $target_cust = b_supply_rp_member::getApplyUser($id);
        $permit_no   = b_supply_rp_member::getName($id);
        $hasRootApply= b_supply_rp_project_license::hasRootApply($id);
        if($isOk == 'Y')
        {
            $string_title = 'P501001_T';
            $string_cont  = ($hasRootApply)? 'P501001_Ca' : 'P501001_C';
        } else {
            $string_title = 'P501002_T';
            $string_cont  = 'P501002_C';
        }
        //組合 推播訊息
        $title = Lang::get('sys_push.'.$string_title,['name1'=>$permit_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.'.$string_cont,['name1'=>$permit_no]);

        return $this->push($target_cust,$title,$cont,$push_type);
    }



    /**
     * 事件：承攬商申請工作許可證<群組：監造部門>
     */
    protected function pushToSupplyApplyPermit($work_id,$b_suuply_id,$target_dept,$project_id = '')
    {
        if(!$target_dept) return false;
        //所需參數
        $push_type      = 2;
        $targetlist     = [0,0,0,$work_id,7,[]];
        $supply_name    = b_supply::getSubName($b_suuply_id);
        $permit_no      = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500001_T',['time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500001_C',['name1'=>$supply_name,'name2'=>$permit_no]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }

    /**
     * 事件：監造審查工作許可證結果<群組：承攬商申請人>
     */
    protected function pushToRPPermitApplyResult($work_id,$isOk = '')
    {
        if(!$work_id && !$isOk) return false;
        //所需參數
        $push_type   = 3;
        $target_cust = wp_work::getApplyUser($work_id);
        $permit_no   = wp_work::getNo($work_id,3);
        if($isOk == 'Y')
        {
            $string_title = 'P500002_T';
            $string_cont  = 'P500002_C';
        } else {
            $string_title = 'P500003_T';
            $string_cont  = 'P500003_C';
        }
        //組合 推播訊息
        $title = Lang::get('sys_push.'.$string_title,['name1'=>$permit_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.'.$string_cont,['name1'=>$permit_no]);

        return $this->push($target_cust,$title,$cont,$push_type);
    }

    /**
     * 事件：工作許可證 啟動通知[承攬商－＞監造]
     */
    protected function pushToSupplyPermitWorkReady($work_id,$ready_time)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,7,[]];
        $work_no        = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500007_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500007_C',['name1'=>$work_no,'name2'=>$ready_time]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }

    /**
     * 事件：工作許可證 啟動階段-承商回簽
     */
    protected function pushToSupplyPermitWorkStatus12($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]];
        $work_no        = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500008_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500008_C',['name1'=>$work_no]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：承攬商安全檢點
     */
    protected function pushToSupplyPermitWorkStatus1($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]];
        $work_no        = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500009_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500009_C',['name1'=>$work_no]);

        $this->pushGroup($targetlist,$title,$cont,$push_type);

        //新增通知轄區
        $target_dept    = wp_work::getDept($work_id);//轄區部門　ｔｙｐｅ＝１
        $targetlist     = [0,$target_dept,0,0,0,[1,2]];
        list($local_id,$local,$local_ip,$supply_id,$tax_num) = wp_work::getLocalInfo($work_id);//音訊盒位置
        //voice_box
        LogLib::putPushVoiceBoxLog($local_id,$local_ip,$supply_id,$tax_num,1,'wcheck');
        //組合 推播訊息
        $title = Lang::get('sys_push.P500009_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500009_C',['name1'=>$work_no]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：轄區安全檢點
     */
    protected function pushToSupplyPermitWorkStatus2($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        $target_dept    = wp_work::getDept($work_id);//轄區部門　ｔｙｐｅ＝１
        $targetlist     = [0,$target_dept,0,0,0,[1,2]];

        //組合 推播訊息
        $title = Lang::get('sys_push.P500009_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500009_C',['name1'=>$work_no]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：轄區聯繫者 / 轄區複檢者
     */
    protected function pushToSupplyPermitWorkStatus3($work_id,$type = 1)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        $target_dept    = wp_work::getDept($work_id); //轄區
        $targetlist     = [0,$target_dept,0,0,0,[1,2]];
        $TCdoe          = ($type == 2)? 'P500010_Tb' : 'P500010_Ta';
        $CCdoe          = ($type == 2)? 'P500010_Cb' : 'P500010_Ca';
        //組合 推播訊息
        $title = Lang::get('sys_push.'.$TCdoe,['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.'.$CCdoe,['name1'=>$work_no]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：會簽部門
     */
    protected function pushToSupplyPermitWorkStatus4($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        $target_dept_id = wp_work::getDept($work_id,4);// 會簽
        $target_dept    = be_dept::getName($target_dept_id);// 會簽
        $targetlist     = [0,$target_dept_id,0,0,0,[]];
        //組合 推播訊息
        $title = Lang::get('sys_push.P500011_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500011_C',['name1'=>$work_no,'name2'=>$target_dept]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：現場會勘 監工部門
     */
    protected function pushToSupplyPermitWorkStatus5($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        $target_dept_id = wp_work::getDept($work_id,3);// 監工
        $target_dept    = be_dept::getName($target_dept_id);// 監工
        $targetlist     = [0,$target_dept_id,0,0,0,[]];
        //組合 推播訊息
        $title = Lang::get('sys_push.P500012_T',['name1'=>$work_no,'name2'=>$target_dept,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500012_C',['name1'=>$work_no,'name2'=>$target_dept]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：現場會勘 承攬商
     */
    protected function pushToSupplyPermitWorkStatus6($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        $targetlist     = [0,0,0,$work_id,3,[]];
        //組合 推播訊息
        $title = Lang::get('sys_push.P500027_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500027_C',['name1'=>$work_no]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：現場會勘 轄區部門
     */
    protected function pushToSupplyPermitWorkStatus7($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getSupplySubName($work_id);
        $target_dept_id = wp_work::getDept($work_id);// 轄區
        $target_dept    = be_dept::getName($target_dept_id);// 轄區
        $targetlist     = [0,$target_dept_id,0,0,0,[1,2]];
        //組合 推播訊息
        $title = Lang::get('sys_push.P500028_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500028_C',['name1'=>$work_no,'name2'=>$target_dept]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：轄區主簽者
     */
    protected function pushToSupplyPermitWorkStatus8($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        $target_dept_id = wp_work::getDept($work_id,1);// 轄區
        $target_dept    = be_dept::getName($target_dept_id);// 轄區
        $targetlist     = [0,$target_dept_id,0,0,0,[2,3,4]];
        //組合 推播訊息
        $title = Lang::get('sys_push.P500015_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500015_C',['name1'=>$work_no,'name2'=>$target_dept]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：經理
     */
    protected function pushToSupplyPermitWorkStatus9($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        $target_dept_id = wp_work::getDept($work_id,5);// 轄區部門 上一層
        $target_dept    = be_dept::getName($target_dept_id);// 轄區
        $targetlist     = [0,$target_dept_id,0,0,0,[3,4]];
        //組合 推播訊息
        $title = Lang::get('sys_push.P500016_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500016_C',['name1'=>$work_no,'name2'=>$target_dept]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：施工階段<職員>
     */
    protected function pushToSupplyPermitWorkStatus10($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        $targetlist     = [0,0,0,$work_id,6,[]]; //所有參與過流程的人以及原本在工作許可證上面指定的人員 - 限定職員
        //組合 推播訊息
        $title = Lang::get('sys_push.P500017_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500017_C',['name1'=>$work_no]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：施工階段<承攬商>
     */
    protected function pushToSupplyPermitWorkStatus11($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        //疏散地點，聯繫者，電話，叮嚀備註
        $topic51        = 51;
        $topic52        = 52;
        $topic53        = 53;
        $topic122       = '122,123,124,164';
        $topic122Ary    = explode(',',$topic122);
        list($ans51)    = wp_work_topic_a::getTopicAns($work_id,$topic51);
        list($ans52)    = wp_work_topic_a::getTopicAns($work_id,$topic52);
        list($ans53)    = wp_work_topic_a::getTopicAns($work_id,$topic53);
        $ans122         = '';
        foreach ($topic122Ary as $topicAid)
        {
            list($tmp)  = wp_work_topic_a::getTopicAns($work_id,$topicAid);
            if($ans122 && $tmp) $ans122 .= '，';
            $ans122 .= $tmp;
        }
        $targetlist     = [0,0,0,$work_id,3,[]];
        //組合 推播訊息
        $title = Lang::get('sys_push.P500017_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500017_C',['name1'=>$work_no]);
        $memo  = '';
        if($ans51 && $ans52 && $ans53)
        {
            $memo  = Lang::get('sys_push.P500017_M',['name1'=>$ans51,'name2'=>$ans52,'name3'=>$ans53,'name4'=>$ans122]);
        }
        //dd($title,$cont,$memo,$topic51,$topic52,$topic53);
        return $this->pushGroup($targetlist,$title,$cont.$memo,$push_type);
    }

    /**
     * 事件：工作許可證 停工通知
     */
    protected function pushToSupplyPermitWorkStop($work_id,$reject_user,$reject_memo)
    {
        if(!$work_id) return false;
        if(!is_string($reject_memo)) $reject_memo = '';
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]];
        $work_no        = wp_work::getNo($work_id,3);
        $reject_user    = User::getName($reject_user);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500006_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500006_C',['name1'=>$work_no,'name2'=>$reject_user,'name3'=>$reject_memo]);
        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 承攬商申請停工
     */
    protected function pushToSupplyPermitWorkStop2($work_id,$reject_user,$reject_memo)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,7,[]];
        $work_no        = wp_work::getNo($work_id,3);
        $reject_user    = User::getName($reject_user);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500006_Ta',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500006_Ca',['name1'=>$work_no,'name2'=>$reject_user]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 暫停
     */
    protected function pushToSupplyPermitWorkPause($work_id,$reject_user,$reject_memo)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]];
        $work_no        = wp_work::getNo($work_id,3);
        $reject_user    = User::getName($reject_user);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500013_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500013_C',['name1'=>$work_no,'name2'=>$reject_user,'name3'=>$reject_memo]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 承攬商申請暫停->通知轄區
     */
    protected function pushToSupplyPermitWorkPause2($work_id,$reject_user,$reject_memo)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $target_dept_id = wp_work::getDept($work_id,1);// 轄區
        $targetlist     = [0,$target_dept_id,0,0,0,[]];
        $work_no        = wp_work::getNo($work_id,3);
        $reject_user    = User::getName($reject_user);
        list($local_id,$local,$local_ip,$supply_id,$tax_num) = wp_work::getLocalInfo($work_id);//音訊盒位置
        //voice_box
        LogLib::putPushVoiceBoxLog($local_id,$local_ip,$supply_id,$tax_num,1,'wpush');
        //組合 推播訊息
        $title = Lang::get('sys_push.P500013_Ta',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500013_Ca',['name1'=>$work_no,'name2'=>$reject_user]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 復工通知
     */
    protected function pushToSupplyPermitWorkReWork($work_id,$reject_user)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]];
        $work_no        = wp_work::getNo($work_id,3);
        $reject_user    = User::getName($reject_user);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500014_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500014_C',['name1'=>$work_no,'name2'=>$reject_user]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 承攬商申請復工
     */
    protected function pushToSupplyPermitWorkReWork2($work_id,$reject_user)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $target_dept_id = wp_work::getDept($work_id,1);// 轄區
        $targetlist     = [0,$target_dept_id,0,0,0,[]];
        $work_no        = wp_work::getNo($work_id,3);
        $reject_user    = User::getName($reject_user);
        //voice_box
        list($local_id,$local,$local_ip,$supply_id,$tax_num) = wp_work::getLocalInfo($work_id);//音訊盒位置
        LogLib::putPushVoiceBoxLog($local_id,$local_ip,$supply_id,$tax_num,1,'wrestart');
        //組合 推播訊息
        $title = Lang::get('sys_push.P500014_Ta',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500014_Ca',['name1'=>$work_no,'name2'=>$reject_user]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：收工申請
     */
    protected function pushToSupplyPermitWorkStatus20($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500018_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500018_C',['name1'=>$work_no]);
        //轄區
        $target_dept    = wp_work::getDept($work_id,1);//
        $targetlist     = [0,$target_dept,0,0,0,[]];
        $ret1 = $this->pushGroup($targetlist,$title,$cont,$push_type);
        //監造
        //$target_dept    = wp_work::getDept($work_id,2);//
        $targetlist     = [0,0,0,$work_id,7,[]];
        $ret2 = $this->pushGroup($targetlist,$title,$cont,$push_type);

        list($local_id,$local,$local_ip,$supply_id,$tax_num) = wp_work::getLocalInfo($work_id);//音訊盒位置
        //voice_box
        LogLib::putPushVoiceBoxLog($local_id,$local_ip,$supply_id,$tax_num,1,'wclose');

        $ret = $ret1 + $ret2;
        return $ret;
    }
    /**
     * 事件：工作許可證 申請收工失敗　退回施工階段通知
     */
    protected function pushToSupplyPermitWorkBackToRun($work_id,$reject_user,$reject_memo)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]];
        $work_no        = wp_work::getNo($work_id,3);
        $reject_user    = User::getName($reject_user);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500026_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500026_C',['name1'=>$work_no,'name2'=>$reject_user,'name3'=>$reject_memo]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 申請暫停失敗　退回施工階段通知
     */
    protected function pushToSupplyPermitWorkBackToRun2($work_id,$reject_user,$reject_memo)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]];
        $work_no        = wp_work::getNo($work_id,3);
        $reject_user    = User::getName($reject_user);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500029_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500029_C',['name1'=>$work_no,'name2'=>$reject_user,'name3'=>$reject_memo]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 申請停工失敗，退回兩個階段
     */
    protected function pushToSupplyPermitWorkBackToRun3($work_id,$reject_user,$reject_memo)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]];
        $work_no        = wp_work::getNo($work_id,3);
        $reject_user    = User::getName($reject_user);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500030_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500030_C',['name1'=>$work_no,'name2'=>$reject_user,'name3'=>$reject_memo]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 收工退回通知
     */
    protected function pushToSupplyPermitWorkBackToRun4($work_id,$reject_user,$reject_memo)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]];
        $work_no        = wp_work::getNo($work_id,3);
        $reject_user    = User::getName($reject_user);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500031_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500031_C',['name1'=>$work_no,'name2'=>$reject_user,'name3'=>$reject_memo]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件：工作許可證 階段通知：收工完成<轄區>
     */
    protected function pushToSupplyPermitWorkStatus21($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500019_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500019_C',['name1'=>$work_no]);
        //轄區
        $target_dept    = wp_work::getDept($work_id);//
        $targetlist     = [0,$target_dept,0,0,0,[]];
        $ret1 = $this->pushGroup($targetlist,$title,$cont,$push_type);
        //監造
        $targetlist     = [0,0,0,$work_id,7,[]];
        $ret2 = $this->pushGroup($targetlist,$title,$cont,$push_type);
        //承攬商
        $targetlist     = [0,0,0,$work_id,3,[]];
        $ret3 = $this->pushGroup($targetlist,$title,$cont,$push_type);

        $ret = $ret1 + $ret2;
        return $ret;
    }

    /**
     * 事件：工作許可證 補人成功<承商>
     */
    protected function pushToSupplyPermitWorkAddMen($work_id,$amt)
    {
        if(!$work_id) return false;
        //所需參數
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]]; //工地負責人+安衛人員
        $work_no        = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500020_T',['name1'=>$work_no,'name2'=>$amt,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500020_C',['name1'=>$work_no,'name2'=>$amt]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }

    /**
     * 事件：通知 工作許可證 施工階段，氣體偵測<承商>
     */
    protected function pushToRegular1($work_id)
    {
        //所需參數
        $push_type   = 2;
        $sucTotalAmt = 0;
        $work_no     = wp_work::getNo($work_id,3);

        $targetlist  = [0,0,0,$work_id,3,[]];
        //組合 推播訊息
        $title = Lang::get('sys_push.P500021_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P500021_T',['name1'=>$work_no,'time'=>date('Y-m-d H:i')]);

        $sucAmt= $this->pushGroup($targetlist,$title,$cont,$push_type);
        $sucTotalAmt += $sucAmt;

        return [$sucTotalAmt,Lang::get('sys_base.base_10139',['name'=>$sucTotalAmt])];
    }
    /**
     * 事件：工負離場 <監造>
     */
    protected function pushToSupplyRootLeave($work_id,$name)
    {
        if(!$work_id) return false;
        //所需參數
        $now            = date('Y-m-d H:i');
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500022_T',['name1'=>$work_no,'name2'=>$name,'time'=>$now]);
        $cont  = Lang::get('sys_push.P500022_C',['name1'=>$work_no,'name2'=>$name,'time'=>$now]);

        //轄區
        $target_dept    = wp_work::getDept($work_id,1);//
        $targetlist     = [0,$target_dept,0,0,0,[]];
        $ret1 = $this->pushGroup($targetlist,$title,$cont,$push_type);
        //監造
        $targetlist     = [0,0,0,$work_id,7,[]];
        $ret2 = $this->pushGroup($targetlist,$title,$cont,$push_type);

        $ret = $ret1 + $ret2;
        return $ret;
    }

    /**
     * 事件（排程）：工作許可證 收工儘速離廠通知<承商>
     */
    protected function pushToSupplyLeave1($work_id,$men = '')
    {
        if(!$work_id) return false;
        //所需參數
        $now            = date('Y-m-d H:i');
        $push_type      = 4;
        $targetlist     = [0,0,0,$work_id,3,[]]; //工地負責人+安衛人員
        $work_no        = wp_work::getNo($work_id,3);
        //voice_box
        list($local_id,$local,$local_ip,$supply_id,$tax_num) = wp_work::getLocalInfo($work_id);//音訊盒位置
        LogLib::putPushVoiceBoxLog($local_id,$local_ip,$supply_id,$tax_num,1,'ovedue');
        //組合 推播訊息
        $title = Lang::get('sys_push.P500023_T',['name1'=>$work_no,'time'=>$now]);
        $cont  = Lang::get('sys_push.P500023_C',['name1'=>$work_no,'name2'=>$men,'time'=>$now]);

        return $this->pushGroup($targetlist,$title,$cont,$push_type);
    }
    /**
     * 事件（排程）：工作許可證 收工後尚未離廠人員<轄區>
     */
    protected function pushToSupplyLeave2($work_id,$amt = 0,$men = '')
    {
        if(!$work_id) return false;
        //所需參數
        $now            = date('Y-m-d H:i');
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        $supply         = wp_work::getSupplySubName($work_id);
        $work_no        = $work_no.'('.$supply.')';
        //組合 推播訊息
        $title = Lang::get('sys_push.P500024_T',['name1'=>$work_no,'name2'=>$amt,'time'=>$now]);
        $cont  = Lang::get('sys_push.P500024_C',['name1'=>$work_no,'name2'=>$men,'time'=>$now]);

        //轄區
        $target_dept    = wp_work::getDept($work_id,1);//
        $targetlist     = [0,$target_dept,0,0,0,[]];
        $ret1 = $this->pushGroup($targetlist,$title,$cont,$push_type);
        //監造
        $targetlist     = [0,0,0,$work_id,7,[]];
        $ret2 = $this->pushGroup($targetlist,$title,$cont,$push_type);

        $ret = $ret1 + $ret2;
        return $ret;
    }
    /**
     * 事件（排程）：工作許可證 暫停後尚未離廠人員沒有執行其他工單<監造>
     */
    protected function pushToSupplyLeave3($work_id,$amt = 0,$men = '')
    {
        if(!$work_id) return false;
        //所需參數
        $now            = date('Y-m-d H:i');
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500032_T',['name1'=>$work_no,'name2'=>$amt,'time'=>$now]);
        $cont  = Lang::get('sys_push.P500032_C',['name1'=>$work_no,'name2'=>$men,'time'=>$now]);

        //監造
        $targetlist     = [0,0,0,$work_id,7,[]];
        $ret = $this->pushGroup($targetlist,$title,$cont,$push_type);

        return $ret;
    }
    /**
     * 事件（排程）：工作許可證 暫停後沒有復工
     */
    protected function pushToHowManyInReWork($work_id)
    {
        if(!$work_id) return false;
        //所需參數
        $now            = date('Y-m-d H:i');
        $push_type      = 4;
        $work_no        = wp_work::getNo($work_id,3);
        //組合 推播訊息
        $title = Lang::get('sys_push.P500033_T',['name1'=>$work_no,'time'=>$now]);
        $cont  = Lang::get('sys_push.P500033_C',['name1'=>$work_no,'time'=>$now]);

        //轄區
        $target_dept    = wp_work::getDept($work_id,1);//
        $targetlist     = [0,$target_dept,0,0,0,[]];
        $ret1 = $this->pushGroup($targetlist,$title,$cont,$push_type);
        //監造
        $targetlist     = [0,0,0,$work_id,7,[]];
        $ret2 = $this->pushGroup($targetlist,$title,$cont,$push_type);
        //承攬商
        $targetlist     = [0,0,0,$work_id,3,[]];
        $ret3 = $this->pushGroup($targetlist,$title,$cont,$push_type);
        $ret = $ret1 + $ret2 + $ret3;
        return $ret;
    }

    /**
     * 事件（排程）：今日多少張沒有審查工作許可證
     */
    protected function pushToHowManyRPPermitApply()
    {
        //所需參數
        $push_type   = 2;
        $sucTotalAmt = 0;

        $data = DB::table('wp_work')->where('isClose','N')->where('sdate','>=',date('Y-m-d'))->where('aproc','A');
        $data = $data->selectRaw('be_dept_id2,COUNT(be_dept_id2) as amt')->groupby('be_dept_id2');

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $target_list = [0,$val->be_dept_id2,0,0,0,[]];
                //組合 推播訊息
                $title = Lang::get('sys_push.P500004_T',['time'=>date('Y-m-d H:i:s')]);
                $cont  = Lang::get('sys_push.P500004_C',['name1'=>$val->amt]);
                $sucAmt= $this->pushGroup($target_list,$title,$cont,$push_type);
                $sucTotalAmt += $sucAmt;
            }
        }
        return [$sucTotalAmt,Lang::get('sys_base.base_10139',['name'=>$sucTotalAmt])];
    }
    /**
     * 事件（排程）：今日多少張沒有啟動工作許可證
     */
    protected function pushToHowManyRPPermitReady()
    {
        //所需參數
        $push_type   = 2;
        $sucTotalAmt = 0;

        $data = DB::table('wp_work')->where('isClose','N')->where('sdate',date('Y-m-d'))->where('aproc','W');
        $data = $data->selectRaw('be_dept_id2,COUNT(be_dept_id2) as amt')->groupby('be_dept_id2');

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $target_list = [0,$val->be_dept_id2,0,0,0,[]];
                //組合 推播訊息
                $title = Lang::get('sys_push.P500005_T',['time'=>date('Y-m-d H:i:s')]);
                $cont  = Lang::get('sys_push.P500005_C',['name1'=>$val->amt]);
                $sucAmt= $this->pushGroup($target_list,$title,$cont,$push_type);
                $sucTotalAmt += $sucAmt;
            }
        }
        return [$sucTotalAmt,Lang::get('sys_base.base_10139',['name'=>$sucTotalAmt])];
    }

    /**
     * 事件（排程）：工程案件過期
     */
    protected function pushToProjectOverDate($target_cust,$project_name = '',$project_edate = '')
    {
        if(!$target_cust) return false;
        //所需參數
        $push_type  = 1;
        //組合 推播訊息
        $title = Lang::get('sys_push.P200001_T',['name1'=>$project_name,'time'=>date('Y-m-d H:i')]);
        $cont  = Lang::get('sys_push.P200001_C',['name1'=>$project_name,'name2'=>$project_edate]);

        return $this->push($target_cust,$title,$cont,$push_type);
    }
    /**
     * 推播 給單一對象
     */
    protected function push($target_cust,$title,$cont,$push_type = 1,$push_mode = 1)
    {
        if(!$target_cust || !$title || !$cont) return false;
        //1. 取得該成員推播ＩＤ
        $pusher_id = view_user::getPushID($target_cust);
        if(!$pusher_id) return false;
        //2. 推播
        return FcmPusherLib::pushSingleDevice($target_cust,$pusher_id,$push_type,$title,$cont,$push_mode);
    }

    /**
     * 推播特定部門
     * @param $userlist
     * @param $type
     * @param $title
     * @param $cont
     * @return int
     */
    public function pushGroup($targetlist,  $title , $cont, $push_type = 1)
    {
        $suc = 0;
        //推播對象：廠區 / 部門 / 專案 / 工作許可證 /
        list($store, $dept, $project, $work_id, $selectTargetType, $titleAry) = $targetlist;
        if(!$store && !$dept && !$project && !$work_id) return $suc;
        $userAry = $pushAry = [];
        if(!is_numeric($push_type)) $type = 9;

        /**
         * 找到需要推播者
         */
        //如果 廠區條件＆部門條件
        if($store || $dept)
        {
            $userAry[1] = view_dept_member::getEmpSelect($store,$dept,$titleAry);
        }
        //如果 工程案件條件
        if($project)
        {
            $userAry[2] = view_door_supply_member::getProjectMemberSelect($project);
        }
        //如果 工作許可證條件
        if($work_id)
        {
            //監造部門
            switch ($selectTargetType)
            {
                //工地負責人
                case 1:
                    $iid = sys_param::getParam('PERMIT_SUPPLY_ROOT',-1);
                    $userAry[3] = wp_work_worker::getSelect($work_id,$iid,0,0);
                    break;
                //安衛人員
                case 2:
                    $iid = sys_param::getParam('PERMIT_SUPPLY_SAFER',-1);
                    $userAry[4] = wp_work_worker::getSelect($work_id,$iid,0,0);
                    break;
                //工地負責人+安衛人員
                case 3:
                    $iid = sys_param::getParam('PERMIT_SUPPLY_ROOT',-1);
                    $userAry[3] = wp_work_worker::getSelect($work_id,$iid,0,0);
                    $iid = sys_param::getParam('PERMIT_SUPPLY_SAFER',-1);
                    $userAry[4] = wp_work_worker::getSelect($work_id,$iid,0,0);
                    break;
                //所有簽過名的人
                case 5:
                    $userAry[5] = wp_work::getWorkAllChargeUser($work_id);
                    break;
                //所有簽過名的人 - 限定職員
                case 6:
                    $userAry[6] = wp_work::getWorkAllChargeUser($work_id,2);
                    break;
                //簽名：監造
                case 7:
                    $userAry[7] = wp_work::getWorkAllChargeUser($work_id,4);
                    break;
            }

        }

        //找到需要推播者的推播ＩＤ
        foreach ($userAry as $val)
        {
            foreach ($val as $user_id => $val2)
            {
                $pushid = view_user::getPushID($user_id);
                if($pushid)
                {
                    $pushAry[$user_id] = $pushid;
                }
            }
        }
//        dd($dept,$userAry,$pushAry);
        //推播
        if(count($pushAry))
        {
            foreach ($pushAry as $uid => $pushid)
            {
                if(FcmPusherLib::pushSingleDevice($uid,$pushid,$push_type,$title,$cont))
                {
                    $suc++;
                }
            }
        }

        return $suc;
    }


}
