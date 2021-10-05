<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_process_topic;

/**
 * 工作許可證-執行階段
 *
 */
trait WorkPermitWorkOrderProcessTrait
{
    /**
     * 新增 工作許可證-執行單
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkOrderProcess($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;
        $now = date('Y-m-d H:i:s');

        $INS = new wp_work_process();
        $INS->wp_work_id                = $data->wp_work_id;
        $INS->wp_work_list_id           = $data->wp_work_list_id;
        $INS->work_status               = $data->work_status;
        $INS->work_sub_status           = $data->work_sub_status;
        $INS->wp_permit_process_id      = $data->wp_permit_process_id;
        $INS->charge_user               = 0;
        $INS->charge_stamp              = $now;
        $INS->stime                     = $now;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret)
        {
            //更新回list
            $tmp = [];
            $tmp['wp_work_process_id'] = $ret;
            $tmp['now_permit_process_id'] = $data->wp_permit_process_id;
            $tmp['lost_img_amt'] = isset($data->lost_img_amt)? $data->lost_img_amt : 0;
            $this->setWorkPermitWorkOrderList($data->wp_work_list_id,$tmp,$mod_user);
        }

        return $ret;
    }

    /**
     * 修改 工作許可證-執行單
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkOrderProcess($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_process::find($id);
        if(!isset($UPD->wp_work_id)) return $ret;


        //結束時間
        if(isset($data->charge_user) && $data->charge_user && $data->charge_user !== $UPD->charge_user)
        {
            $isUp++;
            $UPD->charge_user  = $data->charge_user;
            $UPD->charge_stamp = $now;
        }
        //結束時間
        if(isset($data->etime) && $data->etime && $data->etime !== $UPD->etime)
        {
            $isUp++;
            $UPD->etime = $data->etime;
        }
        //結束時間
        if(isset($data->charge_memo) && $data->charge_memo && $data->charge_memo !== $UPD->charge_memo)
        {
            $isUp++;
            $UPD->charge_memo = $data->charge_memo;
        }
        //需要補上傳圖片數量
        if(isset($data->lost_img_amt) && $data->lost_img_amt && $data->lost_img_amt !== $UPD->lost_img_amt)
        {
            $isUp++;
            $UPD->lost_img_amt = $data->lost_img_amt;
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
                $UPD->close_stamp   = $now;
            } else {
                $UPD->isClose = 'N';
            }
        }
        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();
        } else {
            $ret = -1;
        }
        return $ret;
    }

    /**
     * 取得 工作許可證-執行單
     *
     * @return array
     */
    public function getApiWorkPermitWorkOrderProcessList($b_supply_id,$e_project_id = 0,$aproc = ['A'],$store = 0,$sdate = '',$isNow = '',$isWorker = 'N')
    {
        $ret = array();
        $aproc          = is_array($aproc)? $aproc : ($aproc ? [$aproc] : []);
        $storeAry       = b_factory::getSelect();
        $localAry       = b_factory_a::getSelect();
        $kindAry        = wp_permit_kind::getSelect();
        $dangerAry      = wp_permit_danger::getSelect();
        $workitemAry    = wp_permit_workitem::getSelect();
        $permitAry      = wp_permit::getSelect();
        $aprocAry       = SHCSLib::getCode('PERMIT_APROC');
        //取第一層
        $data = wp_work::where('isClose','N');

        if($aproc)
        {
            $data = $data->whereIn('aproc',$aproc);
        }
        if($b_supply_id)
        {
            $data = $data->where('b_supply_id',$b_supply_id);
        }
        if($e_project_id)
        {
            $data = $data->where('e_project_id',$e_project_id);
        }
        if($store)
        {
            $data = $data->where('b_factory_id',$store);
        }
        if($sdate)
        {
            $data = $data->where('sdate',$sdate);
        }
        if($isNow == 'Y')
        {
            $data = $data->where('sdate','>=',date('Y-m-d'));
        }
        if($isNow == 'N')
        {
            $data = $data->where('sdate','<',date('Y-m-d'));
        }
        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['store']              = isset($storeAry[$v->b_factory_id])? $storeAry[$v->b_factory_id] : '';
                $data[$k]['local']              = isset($localAry[$v->b_factory_a_id])? $localAry[$v->b_factory_a_id] : '';
                $data[$k]['permit']             = isset($permitAry[$v->wp_permit_id])? $permitAry[$v->wp_permit_id] : '';
                $data[$k]['kind']               = isset($kindAry[$v->wp_permit_kind_id])? $kindAry[$v->wp_permit_kind_id] : '';
                $data[$k]['danger']             = isset($dangerAry[$v->wp_permit_danger_id])? $dangerAry[$v->wp_permit_danger_id] : '';
                $data[$k]['workitem']           = isset($workitemAry[$v->wp_permit_workitem_id])? $workitemAry[$v->wp_permit_workitem_id] : '';
                $data[$k]['workitem_memo']      = $v->wp_permit_workitem_memo;
                $data[$k]['store_memo']         = $v->b_factory_memo;
                $data[$k]['aproc_name']         = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['project']            = e_project::getName($v->e_project_id);
                $data[$k]['project_no']         = e_project::getNo($v->e_project_id);
                $data[$k]['supply']             = b_supply::getName($v->b_supply_id);
                $data[$k]['supply_worker_name'] = User::getName($v->supply_worker);
                $data[$k]['supply_safer_name']  = User::getName($v->supply_safer);
                $data[$k]['apply_user_name']    = User::getName($v->apply_user);
                $data[$k]['charge_user_name']   = User::getName($v->charge_user);
                $data[$k]['close_user']         = User::getName($v->close_user);
                $data[$k]['new_user']           = User::getName($v->new_user);
                $data[$k]['mod_user']           = User::getName($v->mod_user);

                if($isWorker == 'Y')
                {
                    $data[$k]['worker']         = $this->getApiWorkPermitWorker($v->id);
                }
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工作許可證-執行單<簽名>
     *
     * @return array
     */
    public function checkApiWorkPermitWorkOrderProcessSigner($work_id,$work_process_id)
    {
        $ret = array();
        //取第一層
        $data = wp_work_process_topic::join('wp_permit_topic as t','t.id','=','wp_work_process_topic.wp_permit_topic_a_id')->
                where('wp_work_process_topic.wp_work_id',$work_id)->
                where('wp_work_process_topic.wp_work_process_id',$work_process_id)->
                where('wp_work_process_topic.isClose','N')->
                where('t.wp_option_type',6)->
                select('wp_work_process_topic.*','t.wp_option_type','t.bc_type_app')->get();
        //dd($data);
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id'] = $v->id;
                $tmp['name'] = $v->name;

                $ret[$v->wp_permit_kind_id][] = $tmp;
            }
            $ret = (object)$ret;
        }

        return $ret;
    }

    /**
     * 取得 該工作許可證的 尚未沒有上傳圖片 之 前面流程
     *
     * @return array
     */
    public function getPermitWorkOrderProcessNeedImgUpload($work_id,$list_id)
    {
        $total_amt = 0;
        $ret = [];
        if(!$work_id || !$list_id) return [$total_amt,$ret];
        //目前完成到的階段，完整歷程
        $processAry = wp_work_process::getListProcess($list_id,1,2);
        //如果還沒有啟動
        $processAryCnt = count($processAry);
        if(!$processAryCnt) return $ret;
        //dd([$list_id,$processAry]);
        $no = 0;
        //工作許可證-主檔
        foreach ($processAry as $work_process_id => $wp_permit_process_id)
        {
            $no++;

            $processTmp = [];
            $permitTmp  = wp_work_process::join('wp_permit_process as p','p.id','=','wp_work_process.wp_permit_process_id')->
                            where('wp_work_process.id',$work_process_id)->where('wp_work_process.lost_img_amt','>',0)->
                            where('wp_work_process.isClose','N')->select('wp_work_process.*','p.name','p.title')->first();
            if(!isset($permitTmp->id)) continue;

            $processTmp['id']           = $work_process_id;    //ID
            $processTmp['process_id']   = $wp_permit_process_id;//程序ＩＤ
            $processTmp['title']        = $permitTmp->title;   //階段
            $processTmp['name']         = $permitTmp->name;    //名稱
            $processTmp['lost_img_amt'] = $permitTmp->lost_img_amt;    //名稱
            $total_amt += $permitTmp->lost_img_amt;
            //作答紀錄-遺失圖片
            $processTmp['lost']         = $this->getLostImgTopic($work_id,$wp_permit_process_id);    //名稱

            $ret[] = $processTmp;
        }

        return [$total_amt,$ret];
    }

    /**
     * 取得 該工作許可證的 生命週期
     *
     * @return array
     */
    public function getMyPermitWorkOrderProcess($work_id,$list_id,$appkey = '')
    {
        $ret = $process = $retT = array();
        if(!$work_id || !$list_id) return $ret;
        //目前完成到的階段，完整歷程
        $processAry = wp_work_process::getListProcess($list_id,0,2);
        //目前階段ＩＤ＆上一個階段ＩＤ
        list($last_process_id,$now_process_id) = wp_work_list::getProcessIDList($list_id);
        //如果還沒有啟動
        if(!count($processAry)) $processAry = [1];
        $processAryCnt = count($processAry);
        //dd([$list_id,$processAry]);
        $no = 0;
        //工作許可證-主檔
        foreach ($processAry as $work_process_id => $wp_permit_process_id)
        {
            $no++;
            //完成階段不顯示-2019/10/02 ken 要求
            if(!$appkey && $wp_permit_process_id == 10) continue;

            $processTmp = [];
            $permitTmp  = wp_permit_process::find($wp_permit_process_id);
            if(!isset($permitTmp->id)) continue;
            $color = 'green';
            if($last_process_id === $work_process_id) $color = 'red';
            if($now_process_id === $work_process_id) $color = 'gray';
            $colorAry = ['green'=>2,'red'=>5,'gray'=>0];

            $processTmp['id']           = $work_process_id;    //ID
            $processTmp['process_id']   = $wp_permit_process_id;//程序ＩＤ
            $processTmp['title']        = $permitTmp->title;   //階段
            $processTmp['name']         = $permitTmp->name;    //名稱
            $processTmp['color']        = $color;              //顏色
            $processTmp['color_num']    = isset($colorAry[$color])? $colorAry[$color] : 1;
            //1-1. 取得該執行程序的紀錄
            $tmp = wp_work_process::find($work_process_id);
            if(isset($tmp->id))
            {
                if($wp_permit_process_id == 7 && !$tmp->charge_user && $no < $processAryCnt) continue;
                $processTmp['charge_user']  = User::getName($tmp->charge_user) ;
                //如果是施工階段
                if($wp_permit_process_id == 7)
                {
                    $processTmp['name']        .= '-'.$processTmp['charge_user'] ;
                }
                $processTmp['charge_stamp'] = ($tmp->charge_user)? $tmp->charge_stamp : '';
                $processTmp['stime']        = !is_null($tmp->stime)? substr($tmp->stime,0,19) : '' ;
                $processTmp['etime']        = !is_null($tmp->etime)? substr($tmp->etime,0,19) : '' ;
                $processTmp['times']        = SHCSLib::getTime($processTmp['etime'] , $processTmp['stime'] , 3) ;
                $bc_type = User::getBcType($tmp->charge_user);
                $myTarget = ($bc_type == 2)? [1,2,6,7,8,9] : (($bc_type == 3)? [3,4,5] : [1,2,3,4,5,6,7,8,9]);
            } else {
                $processTmp['charge_user']  = '';
                $processTmp['charge_stamp'] = '';
                $processTmp['stime']        = '';
                $processTmp['etime']        = '';
                $processTmp['times']        = 0;
                $myTarget                   = [1,2,3,4,5,6,7,8,9];
            }
            //作答紀錄
            $processTmp['permit']          = $this->getMyWorkPermitProcessTopicAns($work_id,$work_process_id,$appkey,$myTarget,'Y');    //名稱
//            if(in_array($work_process_id,[52023])) dd($processTmp['permit']);
            $ret[] = $processTmp;
        }
//        dd($retT);
        return $ret;
    }

}
