<?php

namespace App\Model\WorkPermit;

use App\Http\Traits\WorkPermit\WorkOrderCheckRecord1Trait;
use App\Lib\SHCSLib;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_check_topic extends Model
{
    use WorkOrderCheckRecord1Trait;
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_check_topic';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($work_id,$list_id,$work_process_id)
    {
        if(!$work_id || !$list_id || !$work_process_id) return 0;
        $data = wp_work_check_topic::where('wp_work_id',$work_id)->where('wp_work_list_id',$list_id)->
                where('wp_work_process_id',$work_process_id)->where('isClose','N');
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }
    //是否存在
    protected  function isRecordExist($work_id,$check_id,$user_id,$record_stamp)
    {
        if(!$work_id || !$user_id || !$record_stamp) return 0;
        $data = wp_work_check_topic::where('wp_work_id',$work_id)->where('wp_check_id',$check_id)->where('record_user',$user_id)->
                where('record_stamp',$record_stamp)->where('isClose','N');
        $data = $data->first();
        return (isset($data->wp_work_process_id))? $data->wp_work_process_id : 0;
    }

    //取得 下拉選擇全部
    protected  function getCheckTopicAns($work_id, $wp_check_id = 0, $resize = 0)
    {
        // $wp_permit_topic_a_id = ($wp_check_id == 2)? 147 : 135;
        switch ($wp_check_id) {
            case 2:
                //巡邏者簽名
                $wp_permit_topic_a_id = 147;
                break;
            case 3:
                //承攬商簽名
                $wp_permit_topic_a_id = 211;
                break;
            case 4:
                //檢點者簽名
                $wp_permit_topic_a_id = 135;
                break;
            default:
                $wp_permit_topic_a_id = 0;
        }
        $ret    = [];
        $data   = wp_work_check_topic::where('wp_work_id',$work_id)->where('wp_check_id',$wp_check_id)->where('isClose','N');
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $key => $val)
            {
                $id = strtotime($val->record_stamp);
                if(!$id) $id = $val->id;
                $isAgent   = ($wp_check_id == 2)? wp_work_process_topic::getTopicAns($work_id,178,$val->wp_work_process_id) : '';
                $agentMemo = ($isAgent == 'Y')? '代監造巡邏' : '';
                $ret[$id]['record_user']   = User::getName($val->record_user);
                $ret[$id]['record_dept']   = ($agentMemo)? $agentMemo : view_dept_member::getDept($val->record_user,2);
                $ret[$id]['record_sign']   = wp_work_process_topic::getTopicAns($work_id,$wp_permit_topic_a_id,$val->wp_work_process_id);
                //復工前需要氣體偵測 wp_permit_topic_id [209, 217]
                if (!$ret[$id]['record_sign']) {
                    if($wp_check_id == 4){
                        $ret[$id]['record_sign']   = wp_work_process_topic::getTopicAns($work_id,214,$val->wp_work_process_id);
                    }else{
                        $ret[$id]['record_sign']   = wp_work_process_topic::getTopicAns($work_id,135,$val->wp_work_process_id);
                    }
                }
                $ret[$id]['record_stamp']  = $val->record_stamp;
                $ret[$id]['record_isT']    = $val->wp_work_process_id;
                $ret[$id]['ans']           = wp_work_check_topic_a::getCheckDetailTopicAns($val->id,$resize);
            }
        }


        return $ret;
    }

    //取得 下拉選擇全部
    protected  function genCheckTopicRecordAns($wp_work_process_id,$mod_user = 1)
    {
        $ret    = [];
        $data   = wp_work_check_topic::where('wp_work_process_id',$wp_work_process_id)->where('isClose','N');

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $key => $val)
            {
                $recordAry = wp_work_check_topic_a::getRecordData($val->id,[$val->record_user,$val->record_stamp,$wp_work_process_id]);

                if(count($recordAry))
                {
                    $this->createWorkOrderCheckRecord1($recordAry,$mod_user);
                }
            }
        }


        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($wid, $iid, $isFirst = 1, $isApi = 0)
    {
        $ret    = [];
        $data   = wp_work_check_topic::where('wp_work_id',$wid)->select('id','wp_check_kind_id')->where('isClose','N')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            if($isApi)
            {
                $tmp = [];
                $tmp['id']      = $val->id;
                $tmp['name']    = wp_check_kind::getName($val->wp_check_kind_id);
                $ret[] = $tmp;
            } else {
                $ret[$val->id] = wp_check_kind::getName($val->wp_check_kind_id);
            }
        }

        return $ret;
    }
}
