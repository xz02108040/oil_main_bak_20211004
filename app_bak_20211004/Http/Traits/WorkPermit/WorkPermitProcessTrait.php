<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\HTTCLib;
use App\Lib\SHCSLib;
use App\Model\User;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_permit_process_topic;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use Lang;

/**
 * 工作許可證_流程
 *
 */
trait WorkPermitProcessTrait
{
    /**
     * 新增 工作許可證_流程
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitProcess($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_permit_id)) return $ret;

        $INS = new wp_permit_process();
        $INS->wp_permit_id      = ($data->wp_permit_id > 0)? $data->wp_permit_id : 0;
        $INS->pmp_kind          = ($data->pmp_kind > 0)? $data->pmp_kind : 0;
        $INS->name              = $data->name;
        $INS->pmp_status        = ($data->pmp_status > 0)? $data->pmp_status : 0;
        $INS->pmp_sub_status    = ($data->pmp_sub_status > 0)? $data->pmp_sub_status : 0;
        $INS->bc_type           = ($data->bc_type > 0)? $data->bc_type : 0;
        $INS->isReturn          = ($data->isReturn == 'Y')?'Y' : 'N';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證_流程
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitProcess($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_permit_process::find($id);
        if(!isset($UPD->pmp_kind)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //名稱
        if(isset($data->pmp_kind) && $data->pmp_kind && $data->pmp_kind !== $UPD->pmp_kind)
        {
            $isUp++;
            $UPD->pmp_kind = $data->pmp_kind;
        }
        //階段
        if(isset($data->pmp_status) && $data->pmp_status > 0 && $data->pmp_status !== $UPD->pmp_status)
        {
            $isUp++;
            $UPD->pmp_status = $data->pmp_status;
        }
        //步驟
        if(isset($data->pmp_sub_status) && $data->pmp_sub_status > 0 && $data->pmp_sub_status !== $UPD->pmp_sub_status)
        {
            $isUp++;
            $UPD->pmp_sub_status = $data->pmp_sub_status;
        }
        //種類
        if(isset($data->bc_type) && $data->bc_type > 0 && $data->bc_type !== $UPD->bc_type)
        {
            $isUp++;
            $UPD->bc_type = $data->bc_type;
        }
        //限定會簽
        if(isset($data->rule_countersign) && in_array($data->rule_countersign,['Y','N']) && $data->rule_countersign !== $UPD->rule_countersign)
        {
            $isUp++;
            $UPD->rule_countersign = $data->rule_countersign;
        }
        //允許退件
        if(isset($data->isReturn) && in_array($data->isReturn,['Y','N']) && $data->isReturn !== $UPD->isReturn)
        {
            $isUp++;
            $UPD->isReturn = $data->isReturn;
        }
        //允許退件
        if(isset($data->isOnline) && in_array($data->isOnline,['Y','N']) && $data->isOnline !== $UPD->isOnline)
        {
            $isUp++;
            $UPD->isOnline = $data->isOnline;
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
     * 取得 工作許可證_所有_流程
     *
     * @return array
     */
    public function getApiWorkPermitProcessList($permit_id)
    {
        $ret = array();
        $mainAry    = wp_permit::getSelect();
        $bctypeAry  = SHCSLib::getCode('BC_TYPE');
        $typeAry    = SHCSLib::getCode('PERMIT_PROCESS_KIND');

        //取第一層
        $data = wp_permit_process::where('wp_permit_id',$permit_id)->orderby('isClose')->
                orderby('pmp_kind')->orderby('pmp_status')->orderby('pmp_sub_status')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['wp_permit']      = isset($mainAry[$v->wp_permit_id])? $mainAry[$v->wp_permit_id] : '';
                $data[$k]['type']           = isset($typeAry[$v->pmp_kind])? $typeAry[$v->pmp_kind] : '';
                $data[$k]['bc_type_name']   = isset($bctypeAry[$v->bc_type])?  $bctypeAry[$v->bc_type] : '';
                $data[$k]['bc_type_app']    = wp_permit_process_target::getSelect($v->id,0);
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工作許可證_所有流程_題目項目
     *
     * @return array
     */
    public function getApiWorkPermitAllProcessTopic($permit_id = 1, $processAry = [])
    {
        $ret = array();

        //取第一層
        $data = wp_permit_process::where('wp_permit_id',$permit_id)->where('isClose','N')->orderby('pmp_status')->orderby('pmp_sub_status');
        if(count($processAry))
        {
            $data = $data->whereIn('id',$processAry);
        }
        if($data->count())
        {
            foreach ($data->get() as $k => $v)
            {
                $processTmp = [];
                $processTmp['process_id']       = $v->id;
                $processTmp['title']            = $v->title;    //名稱
                $processTmp['name']             = $v->name;    //名稱
                $processTmp['isReturn']         = ($v->isReturn == 'Y' && $v->rule_reject_type == 1)? 'Y' : 'N';
                $processTmp['isStop']           = ($v->isReturn == 'Y' && $v->rule_reject_type == 2)? 'Y' : 'N';    //名稱
                $processTmp['isRepeat']         = $v->isRepeat;    //名稱

                //題目
                $processTmp['topic']            = $this->getApiWorkPermitProcessTopic($permit_id,$v->id,0,'',[1,3,9],'ALL');

                $processTmp['topic_ans']        = [];

                $ret[] = $processTmp;
            }
        }

        return $ret;
    }

    /**
     * 取得 工作許可證_特定工單之針對特定帳號，允許施作的階段與題目
     *
     * @return array
     */
    public function getApiWorkPermitProcessAll($searchAry = [1,0,0,0,[],0,0,0,0,0,0,0], $work_id =0,$permit_danger = 'A', $targetAry = [],$apiKey = '',$isAns = 'N')
    {
        $ret = array();
        list($wp_permit_id,$b_cust_id,$bc_type,$mydept,$workAry,$supply_worker,$supply_safer,$be_dept_id1,$be_dept_id2,$be_dept_id3,$be_dept_id4,$be_dept_id5) = $searchAry;

        $listData = wp_work_list::getData($work_id,$wp_permit_id);
        $list_id  = isset($listData->id)? $listData->id : 0;
        //目前完成到的階段，完整歷程
        $processAry = wp_work_process::getListProcess($list_id,0,2);
        //如果還沒有啟動
        if(!count($processAry)) $processAry = [1];
        //目前階段ＩＤ＆上一個階段ＩＤ
        list($last_process_id,$now_process_id) = wp_work_list::getProcessIDList($list_id);
        //dd($work_id,$list_id,$processAry);

        //取第一層
        $data = wp_permit_process::where('wp_permit_id',$wp_permit_id)->where('isClose','N')->where('isOnline','N')->
                orderby('pmp_kind')->orderby('pmp_status')->orderby('pmp_sub_status')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $target1 = $target2 = 0;
                $isPatrol= 'N';
                $wp_work_id = $work_id;
//                $target2Ary = [];

                $isOk2 = (!$v->rule_permit_danger || ($v->rule_permit_danger == $permit_danger))? true : false;
                $isOk3 = ($v->rule_countersign == 'N' || ($v->rule_countersign == 'Y' && $be_dept_id4 > 0))? true : false;


                //該階段的題目
                $this->getApiWorkPermitProcessTopic();

                if($isOk2 && $isOk3 )
                {
                    $color  = 'gray';
                    $target = wp_permit_process_target::getTarget($v->id);
                    if($target == 6)
                    {
                        //所有承攬商角色
                        if($bc_type == 3)
                        {
                            $title1     = $v->title.'('.Lang::get('sys_base.base_40244').')';
                            $target1    = 3;
                            $target1Ary = [3,4,5];
                            $inExistAry1= $this->getProcessTopicOptionID($v->id,$target1Ary);
                        }

                        //所有部門角色
                        if($bc_type == 2 )
                        {
                            if($mydept == $be_dept_id1)
                            {
                                $title1     = $v->title.'('.Lang::get('sys_base.base_40229').')';
                                $target1    = 1; //TODO 寫死 轄區部門
                                $target1Ary = [1,2,7,8,9];
                                $inExistAry1= $this->getProcessTopicOptionID($v->id,$target1Ary);
                            } else {
                                //巡邏
                                $title1     = $v->title;
                                $target1    = 9;
                                $target1Ary = [9];
                                $inExistAry1= [];
                                $isPatrol   = 'Y';
                                $wp_work_id = 0;
                            }
                        }
                    } else {
                        $title1     = $v->title;
                        $target1    = $target;
                        $target1Ary = $targetAry;
                        $inExistAry1= [];
                    }

                    //判斷是否完成
                    list($isOp,$myTarget,$myAppType) = HTTCLib::genPermitTarget($b_cust_id,$bc_type,$workAry,$target,$supply_worker,$supply_safer,$be_dept_id1,$be_dept_id2,$be_dept_id3,$be_dept_id4,$be_dept_id5);
                    if(!$isOp) continue;


                    if($title1)
                    {
                        $processTmp = [];
                        $processTmp['process_id']       = $v->id;
                        $processTmp['title']            = $title1;   //階段
                        $processTmp['name']             = $v->name;    //名稱
                        $processTmp['color']            = $color;      //顏色
                        $processTmp['target']           = $target1;      //顏色
                        $processTmp['work_process_id']  = 0;
                        $processTmp['isMultiAns']       = $v->rule_multi_ans;
                        $processTmp['isRepeat']         = $v->isRepeat;
                        $processTmp['topic_ans']        = [];
                        //題目
                        $processTmp['topic']      = $this->getApiWorkPermitProcessTopic($wp_permit_id,$v->id,$wp_work_id,'',$target1Ary,$isPatrol);
                        if($isPatrol == 'Y')
                        {
                            //dd($wp_permit_id,$v->id,$wp_work_id,$target1,$target1Ary,$inExistAry1,$isPatrol,$processTmp);
                        }
                        foreach ($processAry as $work_process_id => $process_id)
                        {
                            if($process_id === $v->id)
                            {
                                $color = 'green';
                                if($last_process_id === $work_process_id) $color = 'red';
                                if($now_process_id === $work_process_id)  $color = 'gray';
                                $processTmp['work_process_id']  = $work_process_id;
                                $processTmp['color']            = $color;
                                if(in_array($color,['green','red']) && $isAns == 'Y')
                                {
                                    $ansAry = $this->getProcessTopicAns($work_id,$work_process_id,$apiKey,1,$inExistAry1);
                                    if(count($ansAry))
                                    {
                                        $processTmp['topic_ans'][]['ans_record'] = $ansAry;
                                    }
                                }
                            }
                        }
                        //轉換
                        if(($isAns == 'N' && $color == 'gray') || $isAns == 'Y')
                        {
                            //轉換
                            $ret[] = (object)$processTmp;
                        }
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * 取得 該使用者　針對特定工單＿可執行流程
     *
     * @return array
     */
    public function getApiWorkPermitProcessTarget($work_id,$b_cust_id,$isTopic = 'N')
    {
        $ret = array();
        //取得目前工作許可證 已經執行的情況
        $wp_work          = wp_work::getData($work_id);
        $wp_permit_id     = $wp_work->wp_permit_id;
        $dept4            = $wp_work->be_dept_id4;
        $permit_danger    = $wp_work->wp_permit_danger;
        $listData         = wp_work_list::getData($work_id ,$wp_permit_id);
        if(!isset($listData->id)) return $ret;
        $list_id          = $listData->id;
        $isRenProcessAry  = wp_work_process::getListProcess($list_id,1);
        //dd($work_id,$b_cust_id,$bc_type,$isRenProcessAry);
        //取第一層
        $data = wp_permit_process::where('wp_permit_id',$wp_permit_id)->where('isClose','N')->
                orderby('pmp_kind')->orderby('pmp_status')->orderby('pmp_sub_status')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $isOk1 = ($v->isRepeat == 'N' && in_array($v->id,$isRenProcessAry)) ? false : true;
                $isOk2 = (!$v->rule_permit_danger || ($v->rule_permit_danger == $permit_danger))? true : false;
                $isOk3 = ($v->rule_countersign == 'N' || ($v->rule_countersign == 'Y' && $dept4 > 0))? true : false;
                $isOk4 = ($v->isOnline == 'N')? true : false;
//                if($work_id == 3013 && $v->id == 7) dd($v->id,$v->isRepeat,$isOk1,$isOk2,$isOk3,$isOk4);

                if($isOk1 && $isOk2 && $isOk3 && $isOk4)
                {
                    $tmp = [];
                    $tmp['process_id']  = $v->id;
                    $tmp['process_name']= $v->name;
                    //判斷是否可以執行該階段
                    list($isOp,$myTarget,$myAppType) = HTTCLib::isTargetList($work_id,$b_cust_id,$v->id);
                    if($isOp)
                    {
                        if($isTopic == 'Y')
                        {
                            $tmp['topic'] = $this->getApiWorkPermitProcessTopic($wp_permit_id,$v->id,$work_id,'',$myTarget,'N',1);
                        }
                        $ret[] = $tmp;
                    }
                }
            }
        }

        return $ret;
    }


    public function getProcessTopicOptionID($process_id,$targetAry = [],$option_type = 0)
    {
        $ret = [];
        if(!$process_id) return $ret;

        $data = wp_permit_process_topic::join('wp_permit_topic_a as ta','ta.wp_permit_topic_id','=','wp_permit_process_topic.wp_permit_topic_id')->
            where('wp_permit_process_topic.wp_permit_process_id',$process_id)->where('wp_permit_process_topic.isClose','N')->
            where('ta.isClose','N')->select('ta.id','wp_permit_process_topic.bc_type_app');
        if($option_type)
        {
            $data = $data->where('wp_option_type',$option_type);
        }
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $isOk = 1;
                if(count($targetAry))
                {
                    if(!in_array($val->bc_type_app,$targetAry)) $isOk = 0;
                }
                if($isOk) $ret[] = $val->id;
            }
        }
        return $ret;
    }
}
