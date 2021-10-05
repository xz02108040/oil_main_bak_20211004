<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_param;
use App\Model\User;
use App\Model\WorkPermit\wp_check;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_work_topic;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;

/**
 * 檢點單_檢點選項
 *
 */
trait WorkCheckTopicOptionTrait
{
    /**
     * 新增 檢點單_檢點選項
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkCheckTopicOption($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_check_id)) return $ret;

        $INS = new wp_check_topic_a();
        $INS->wp_check_id               = $data->wp_check_id;
        $INS->wp_check_topic_id         = $data->wp_check_topic_id;
        $INS->wp_option_type            = $data->wp_option_type;
        $INS->ans_type                  = $data->ans_type;
        $INS->name                      = $data->name;
        $INS->memo                      = $data->memo;
        $INS->unit                      = $data->unit;
        $INS->safe_val                  = $data->safe_val;
        $INS->defult_val                = $data->defult_val;
        $INS->safe_limit1               = $data->safe_limit1;
        $INS->safe_limit2               = $data->safe_limit2;
        $INS->safe_action               = $data->safe_action;
        $INS->sys_code                  = $data->sys_code;
        $INS->engineering_identity_id   = ($data->engineering_identity_id > 0)? $data->engineering_identity_id : 0;
        $INS->show_order                = ($data->show_order > 0)? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 檢點單_檢點選項
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkCheckTopicOption($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_check_topic_a::find($id);
        if(!isset($UPD->id)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name) {
            $UPD->name = $data->name;
            $isUp++;
        }
        //說明
        if(isset($data->memo) && $data->memo !== $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
        }
        //單位
        if(isset($data->unit) && $data->unit !== $UPD->unit)
        {
            $isUp++;
            $UPD->unit = $data->unit;
        }
        //數值代碼
        if(isset($data->ans_type) && $data->ans_type !== $UPD->ans_type)
        {
            $isUp++;
            $UPD->ans_type = $data->ans_type;
        }
        //預設值
        if(isset($data->defult_val) && $data->defult_val !== $UPD->defult_val)
        {
            $isUp++;
            $UPD->defult_val = $data->defult_val;
        }
        //安全值
        if(isset($data->safe_val) && $data->safe_val !== $UPD->safe_val)
        {
            $isUp++;
            $UPD->safe_val = $data->safe_val;
        }
        //系統代碼
        if(isset($data->sys_code) && $data->sys_code !== $UPD->sys_code)
        {
            $isUp++;
            $UPD->sys_code = $data->sys_code;
        }
        //上限
        if(isset($data->safe_limit1) && $data->safe_limit1 !== $UPD->safe_limit1)
        {
            $isUp++;
            $UPD->safe_limit1 = $data->safe_limit1;
        }
        //下限
        if(isset($data->safe_limit2) && $data->safe_limit2 !== $UPD->safe_limit2)
        {
            $isUp++;
            $UPD->safe_limit2 = $data->safe_limit2;
        }
        //上下職關係
        if(isset($data->safe_action) && $data->safe_action !== $UPD->safe_action)
        {
            $isUp++;
            $UPD->safe_action = $data->safe_action;
        }
        //類型
        if(isset($data->wp_option_type) && $data->wp_option_type > 0 && $data->wp_option_type !== $UPD->wp_option_type)
        {
            $isUp++;
            $UPD->wp_option_type = $data->wp_option_type;
        }
        //排序
        if(isset($data->engineering_identity_id) && $data->engineering_identity_id > 0 && $data->engineering_identity_id !== $UPD->engineering_identity_id)
        {
            $isUp++;
            $UPD->engineering_identity_id = $data->engineering_identity_id;
        }
        //排序
        if(isset($data->show_order) && $data->show_order > 0 && $data->show_order !== $UPD->show_order)
        {
            $isUp++;
            $UPD->show_order = $data->show_order;
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
     * 取得 檢點單_檢點選項
     *
     * @return array
     */
    public function getApiWorkCheckTopicOptionList($tid)
    {
        $ret = array();
        $mainAry    = wp_check::getSelect();
        $typeAry    = SHCSLib::getCode('WP_OPTION_TYPE');
        $ansTypeAry = SHCSLib::getCode('TOPIC_ANS_TYPE');
        $identityAry= b_supply_engineering_identity::getSelect(0);
        //取第一層
        $data = wp_check_topic_a::
                join('wp_check_topic as t','t.id','=','wp_check_topic_a.wp_check_topic_id')->
                select('wp_check_topic_a.*','t.name as wp_check_topic')->
                where('wp_check_topic_a.wp_check_topic_id',$tid)->
                orderby('wp_check_topic_a.isClose')->orderby('wp_check_topic_a.show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['wp_check']       = isset($mainAry[$v->wp_check_id])? $mainAry[$v->wp_check_id] : '';
                $data[$k]['type']           = isset($typeAry[$v->wp_option_type])? $typeAry[$v->wp_option_type] : '';
                $data[$k]['ans_type']       = isset($ansTypeAry[$v->ans_type])? $ansTypeAry[$v->ans_type] : '';
                $data[$k]['identity']       = isset($identityAry[$v->engineering_identity_id])? $identityAry[$v->engineering_identity_id] : '';
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 檢點單_檢點選項
     *
     * @return array
     */
    public function getApiWorkCheckTopicOption($tid,$work_id = 0, $taid = 0)
    {
        $ret = array();
        $typeAry                = SHCSLib::getCode('WP_OPTION_TYPE');
        $PERMIT_SUPPLY_WORKER   = sys_param::getParam('PERMIT_SUPPLY_WORKER');
        //取第一層
        $data = wp_check_topic_a::where('wp_check_topic_id',$tid)->where('isClose','N');
        if($taid)
        {
            $data = $data->where('id',$taid);
        }
        $data = $data->orderby('wp_check_topic_a.show_order')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                //$isAns = in_array($v->wp_option_type,[1,2,3,4,6,7,8,9,13,17]) ? 'Y' : 'N';
                $isAns = isset($v->isAns) ? $v->isAns : 'N';
                $isImg = in_array($v->wp_option_type,[6,7]) ? 'Y' : 'N';
                $isGPS = in_array($v->wp_option_type,[8]) ? 'Y' : 'N';

                $tmp = [];
                $tmp['check_topic_a_id']        = $v->id;
                $tmp['wp_option_type']          = $v->wp_option_type;
                $tmp['wp_option_type_name']     = isset($typeAry[$v->wp_option_type])? $typeAry[$v->wp_option_type] : '';
                $tmp['name']                    = $v->name;
                $tmp['memo']                    = is_null($v->memo)? '' : $v->memo;
                $tmp['unit']                    = is_null($v->unit)? '' : $v->unit;
                $tmp['ans_type']                = $v->ans_type;
                $tmp['safe_val']                = $v->safe_val;
                $tmp['safe_limit1']             = !is_null($v->safe_limit1)? $v->safe_limit1 : 0;
                $tmp['safe_limit2']             = !is_null($v->safe_limit2)? $v->safe_limit2 : 0;
                $tmp['safe_action']             = $v->safe_action;
                $tmp['isAns']                   = $isAns;
                $tmp['isImg']                   = $isImg;
                $tmp['isGPS']                   = $isGPS;
                $tmp['ans_select']              = '';
                $tmp['ans_value']               = (strlen($v->defult_val))? $v->defult_val : '';
                $tmp['value']                   = (strlen($v->defult_val))? $v->defult_val : '';
                //專業人員
                if(in_array($v->wp_option_type,[10]))
                {
                    $ansStr                     = $this->getApiWorkPermitCheckTopicOptionIdentity($work_id,$v->id,$v->engineering_identity_id);
                    $tmp['ans_value']           = $ansStr;
                }
                //專業人員
                if(in_array($v->wp_option_type,[12]))
                {
                    $tmp['ans_select']           = wp_work_worker::getLockMenSelect($work_id,$v->engineering_identity_id,0,1);
                }
                //施工人員
                if(in_array($v->wp_option_type,[13]))
                {
                    $tmp['ans_select'] = wp_work_worker::getSelect($work_id,$PERMIT_SUPPLY_WORKER,1,1,['R'],[1,2]);
                }
                if(in_array($v->wp_option_type,[1,17]))
                {
                    if(isset($v->sys_code) && $v->sys_code)
                    {
                        $tmp['ans_select'] = SHCSLib::getCode($v->sys_code,1,1);
                    } else {
                        $tmp['ans_select'] = SHCSLib::genYNApiAry(0);
                    }
                }
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

}
