<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_process extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_process';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_permit_process::where('id',$id)->select('id')->first();
        return (isset($data->id))? $data->id : 0;
    }
    //取得判斷 是否可以出現「不同意」的按鈕
    protected  function getIsReturn($id)
    {
        if(!$id) return 'N';
        $data = wp_permit_process::where('id',$id)->select('isReturn')->first();
        return (isset($data->isReturn))? $data->isReturn : 'N';
    }
    //取得判斷 是否可以出現「不同意」的按鈕
    protected  function getIsRepeat($id)
    {
        if(!$id) return 'N';
        $data = wp_permit_process::where('id',$id)->select('isRepeat')->first();
        return (isset($data->isRepeat))? $data->isRepeat : 'N';
    }
    //取得判斷 是否可以出現「不同意」的按鈕
    protected  function getRuleReject($id)
    {
        if(!$id) return 0;
        $data = wp_permit_process::where('id',$id)->select('rule_reject_type')->first();
        return (isset($data->rule_reject_type))? $data->rule_reject_type : 0;
    }
    //取得判斷 是否可以出現「不同意」的按鈕
    protected  function getIsRunStatus($id)
    {
        if(!$id) return 'N';
        $data = wp_permit_process::where('id',$id)->select('rule_bc_type_app')->first();
        return (isset($data->rule_bc_type_app) && $data->rule_bc_type_app == 2)? 'Y' : 'N';
    }

    //名稱是否存在
    protected  function isStatusExist($id, $kind, $status, $sub_status, $extid = 0)
    {
        if(!$id) return 0;
        $data = wp_permit_process::where('wp_permit_id',$id)->where('pmp_kind',$kind)->
                where('pmp_status',$status)->where('pmp_sub_status',$sub_status)->where('isClose','N');
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        $data = $data->first();
        return isset($data->id)? $data->id : 0;
    }

    //下一個階段
    protected  function nextProcess($permit_id, $kind = 1, $old_process_id = 1, $danger = '', $work_id = 0, $work_list_id = 0, $isOffWork = 'N', $isExtension = 'N')
    {
        if(!$permit_id) return 0;
        $isMaxTimes = 10;  //錯誤最多次數
        $skpMaxTimes= 5;  //錯誤最多次數
        $errTimes   = 0;  //錯誤次數
        $isSkp      = 0;  //錯誤次數
        $process_id = -1; //下一個程序ＩＤ
        $dangerAry  = SHCSLib::getCode('PERMIT_DANGER',0);
        list($pmp_kind,$status,$sub_status,$list_aproc,$old_rule_bc_type_app) = wp_permit_process::getDataList($old_process_id);

        //rule_bc_type_app=2，該階段不限制身分
        if($old_rule_bc_type_app == 2)
        {
            //申請延長
            if($isExtension == 'Y')
            {
                $next_status     = 4;
                $next_sub_status = 1;
            }
            //
            elseif ($isOffWork == 'Y')
            {
                $next_status     = 6;
                $next_sub_status = 1;
            } else {
                //繼續原本的流程
                return $old_process_id;
            }

//            dd($old_rule_bc_type_app,$isOffWork,$isExtension,$old_process_id,$next_status,$next_sub_status);
        }elseif ($old_process_id == 21) {
            //申請停工是否同意
            list($agree)  = wp_work_topic_a::getTopicAns($work_id,200);
            if($agree == 'Y')
            {
                $next_status     = 6;
                $next_sub_status = 3;
            } else {

                //跳回兩個階段前
                list($last_work_prcoess_id,$last_process_id) = wp_work_process::getLastProcessID($work_id,$work_list_id,$old_process_id,2);
                //退回 施工階段
                $old_process_id = $last_process_id;
                //繼續原本的流程
                return $old_process_id;
            }

        } else {
            $next_status     = $status;
            $next_sub_status = $sub_status + 1;
        }
//        dd($status,$sub_status,$old_rule_bc_type_app,$next_status,$next_sub_status);

        $runHistory = [];
        $runHistory[] = [$next_status,$next_sub_status];

        do{
            $isGetNext = 0;
            $data = wp_permit_process::where('wp_permit_id',$permit_id)->where('pmp_kind',$kind)->
            where('pmp_status',$next_status)->where('pmp_sub_status',$next_sub_status)->where('isClose','N');
            if($data->count())
            {
                $runHistory[] = [$next_status,$next_sub_status,'count'=>'Y'];
                //找到下一筆 跳脫
                $data = $data->first();
                //2019-07-02 新增規則：如果程序有危險等級限制，則檢查該工作許可證之危險等級是否符合
                if($data->rule_permit_danger && in_array($data->rule_permit_danger,array_keys($dangerAry)))
                {
                    $runHistory[] = [$next_status,$next_sub_status,'rule_permit_danger'=>$data->rule_permit_danger,'isSkp'=>$isSkp];
                    if($danger == $data->rule_permit_danger)
                    {
                        $isGetNext = 1;
                    } else {
                        $isSkp ++;
                    }
                //會簽檢查
                }elseif($data->rule_countersign == 'Y') {
                    $runHistory[] = [$next_status,$next_sub_status,'rule_countersign'=>$data->rule_countersign,'isSkp'=>$isSkp];
                    if(wp_work::getDept($work_id,4))
                    {
                        $isGetNext = 1;
                    } else {
                        $isSkp ++;
                    }
                } else {
                    //沒有需要檢查危險等級
                    $isGetNext = 1;
                    $runHistory[] = [$next_status,$next_sub_status,'isGetNext'=>$isGetNext,'isSkp'=>$isSkp];
                }

                if($isGetNext)
                {
                    $process_id = isset($data->id)? $data->id : 0;
                    //dd($runHistory);
                    break;
                }
            } else {
                $isSkp++;
            }
            if(!$isGetNext)
            {
                $errTimes++;

                if($isSkp <= $skpMaxTimes)
                {
                    $next_sub_status += 1;
                } else {
                    $next_status     = $status + 1;
                    $next_sub_status = 1;
                }
            }
        }while($errTimes < $isMaxTimes);
//        dd($process_id,$errTimes,$isMaxTimes,$next_status,$next_sub_status,$isSkp,$skpMaxTimes,$runHistory);
        return $process_id;
    }

    //取得 身份
    protected  function getBcType($id)
    {
        if(!$id) return 0;
        $data = wp_permit_process::find($id);
        return (isset($data->id))? $data->bc_type : 0;
    }

    //取得 身份
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = wp_permit_process::find($id);
        return (isset($data->id))? $data->name : '';
    }

    //取得 身份
    protected  function getTitle($id)
    {
        if(!$id) return '';
        $data = wp_permit_process::find($id);
        return (isset($data->id))? $data->title : '';
    }

    //取得 身份
    protected  function getAproc($id)
    {
        if(!$id) return 'A';
        $data = wp_permit_process::find($id);
        return (isset($data->id))? $data->list_aproc : 'A';
    }

    //取得 身份
    protected  function getRuleApp($id)
    {
        if(!$id) return 1;
        $data = wp_permit_process::find($id);
        return (isset($data->id))? $data->rule_bc_type_app : 1;
    }

    //取得 身份
    protected  function getData($id)
    {
        if(!$id) return 0;
        $data = wp_permit_process::find($id);
        return (isset($data->id))? $data : 0;
    }

    //取得 身份
    protected  function getStatusList($id)
    {
        if(!$id) return [0,0];
        $data = wp_permit_process::find($id);
        return (isset($data->id))? [$data->pmp_status,$data->pmp_sub_status] : [0,0];
    }

    //取得 身份
    protected  function getDataList($id)
    {
        $ret = [1,0,0,'X',1,''];
        if(!$id) return $ret;
        $data = wp_permit_process::find($id);
        return (isset($data->id))? [$data->pmp_kind,$data->pmp_status,$data->pmp_sub_status,$data->list_aproc,$data->rule_bc_type_app,$data->rule_permit_danger] : $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_permit_process::select('id','name')->where('isClose','N')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
