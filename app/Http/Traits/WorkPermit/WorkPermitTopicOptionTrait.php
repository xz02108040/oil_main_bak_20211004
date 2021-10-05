<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_param;
use App\Model\User;
use App\Model\WorkPermit\wp_check;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_danger;
use App\Model\WorkPermit\wp_work_topic;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;

/**
 * 工作許可證_題目_選項
 *
 */
trait WorkPermitTopicOptionTrait
{
    /**
     * 新增 工作許可證_題目_選項
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitTopicOption($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_permit_id)) return $ret;

        $INS = new wp_permit_topic_a();
        $INS->wp_permit_id              = $data->wp_permit_id;
        $INS->wp_permit_topic_id        = $data->wp_permit_topic_id;
        $INS->wp_option_type            = $data->wp_option_type;
        $INS->engineering_identity_id   = $data->engineering_identity_id;
        $INS->wp_check_id               = $data->wp_check_id;
        $INS->name                      = $data->name;
        $INS->memo                      = $data->memo;
        $INS->show_order                = ($data->show_order > 0)? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證_題目_選項
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitTopicOption($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_permit_topic_a::find($id);
        if(!isset($UPD->wp_permit_id)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //備註
        if(isset($data->memo) && $data->memo && $data->memo !== $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
        }
        //類型
        if(isset($data->wp_option_type) && $data->wp_option_type > 0 && $data->wp_option_type !== $UPD->wp_option_type)
        {
            $isUp++;
            $UPD->wp_option_type = $data->wp_option_type;
        }
        //工程身份
        if(isset($data->engineering_identity_id) && $data->engineering_identity_id > 0 && $data->engineering_identity_id !== $UPD->engineering_identity_id)
        {
            $isUp++;
            $UPD->engineering_identity_id = $data->engineering_identity_id;
        }
        //檢點單
        if(isset($data->wp_check_id) && $data->wp_check_id > 0 && $data->wp_check_id !== $UPD->wp_check_id)
        {
            $isUp++;
            $UPD->wp_check_id = $data->wp_check_id;
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
     * 取得 工作許可證_題目_選項
     *
     * @return array
     */
    public function getApiWorkPermitTopicOptionList($tid)
    {
        $ret = array();
        $mainAry    = wp_permit::getSelect();
        $typeAry    = SHCSLib::getCode('WP_OPTION_TYPE');
        $identityAry= b_supply_engineering_identity::getSelect(0);
        $checkAry   = wp_check::getSelect(0);
        //取第一層
        $data = wp_permit_topic_a::
                join('wp_permit_topic as t','t.id','=','wp_permit_topic_a.wp_permit_topic_id')->
                select('wp_permit_topic_a.*','t.name as wp_permit_topic')->
                where('wp_permit_topic_a.wp_permit_topic_id',$tid)->
                orderby('wp_permit_topic_a.isClose')->orderby('wp_permit_topic_a.show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['wp_permit']              = isset($mainAry[$v->wp_permit_id])? $mainAry[$v->wp_permit_id] : '';
                $data[$k]['type']                   = isset($typeAry[$v->wp_option_type])? $typeAry[$v->wp_option_type] : '';
                $data[$k]['engineering_identity']   = isset($identityAry[$v->engineering_identity_id])? $identityAry[$v->engineering_identity_id] : '';
                $data[$k]['wp_check']               = isset($checkAry[$v->wp_check_id])? $checkAry[$v->wp_check_id] : '';
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工作許可證_題目_選項
     *
     * @return array
     */
    public function getApiWorkPermitTopicOption($tid, $work_id, $isShowAns = '',$showType = 1)
    {
        $typeAry                = SHCSLib::getCode('WP_OPTION_TYPE');
        $NOTICE                 = SHCSLib::getCode('NOTICE',1,1);
        $PERMIT_SUPPLY_WORKER   = sys_param::getParam('PERMIT_SUPPLY_WORKER');
        $TESET_APP_DANGER_SHOW  = sys_param::getParam('TESET_APP_DANGER_SHOW');
        $store_id               = wp_work::getStore($work_id);
        $shift_id               = wp_work::getShift($work_id);
        $store_tel              = b_factory::getTel($store_id);
        $ret = array();
        //取第一層
        $data = wp_permit_topic_a::
                where('wp_permit_topic_a.wp_permit_topic_id',$tid)->
                where('wp_permit_topic_a.isClose','N');
        $data = $data->orderby('wp_permit_topic_a.show_order');
//        dd($data->get());
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                $isAns = $v->isAns;

                $isShow = ($isShowAns == 'Y')? ( $isAns=='Y'? 1 : 0) : 1;
                if(!$isShow) continue;

                $tmp = [];
                $tmp['topic_a_id']              = $v->id;
                if($showType == 1)
                {
                    $tmp['ans_value']               = '';
                    $tmp['ans_select']              = '';
                }

                //配合2021-01-15 APP D02104格式調整
                if(in_array($v->wp_option_type,[15])) {
                    //如果不是選擇的，不顯示
                    $isDangerExist = wp_work_danger::isExist($work_id, $v->wp_permit_danger_id);
                    if ($work_id && !$isDangerExist) continue;
                }

                //聯繫者屬性
                if(in_array($v->wp_option_type,[14]))
                {
                    if($v->ans_type == 7)
                    {
                        $ansStr = $store_tel;
                    } else {
                        $ansStr = (strlen($v->def_val))? $v->def_val : wp_work::getContactType($work_id);
                        if(!strlen($v->def_val))
                        {
                            $ans1 = \Lang::get('sys_base.base_40236');
                            $ans2 = \Lang::get('sys_base.base_40237');
                            $tmp['ans_select'] = [$ans1=>$ans1,$ans2=>$ans2];
                        }
                    }
                    $tmp['ans_value']           = $ansStr;
                }
                //專業人員
                if(in_array($v->wp_option_type,[10]))
                {
                    $ansStr                     = $this->getApiWorkPermitTopicOptionIdentity($work_id,$v->id,$v->engineering_identity_id);

                    $tmp['ans_value']           = $ansStr;
                }
                //看火者＆從施工人員內挑選(不可同時兼任 工安&工負)
                if($v->wp_option_type == 13)
                {
                    $tmp['ans_select'] = wp_work_worker::getSelect($work_id,$PERMIT_SUPPLY_WORKER,1,1,['R'],[1,2]);

                }

                //檢點單內容
                if($v->wp_option_type == 9 && $v->wp_check_id)
                {
                    $checkAry = $this->getApiWorkCheck($v->wp_check_id,$work_id);
                    $tmp['check'] = $checkAry;//if(isset($checkAry[0]))
                    //if($v->wp_check_id == 6)dd($checkAry,$tmp);
                }

                if($showType == 1)
                {
                    $tmp['wp_option_type']          = $v->wp_option_type;
                    $tmp['wp_option_type_name']     = isset($typeAry[$v->wp_option_type])? $typeAry[$v->wp_option_type] : '';
                    $tmp['name']                    = $v->name;
                    $tmp['wp_check_id']             = $v->wp_check_id;
                    $tmp['ans_type']                = $v->ans_type;
                    $tmp['isAns']                   = $isAns;
                    $tmp['isImg']                   = $v->isImg;
                    $tmp['isGPS']                   = $v->isGPS;
                    $tmp['engineering_identity_id'] = $v->engineering_identity_id;

                    //單選
                    if(in_array($v->wp_option_type,[1,2,17]))
                    {
                        if(isset($v->sys_code) && $v->sys_code)
                        {
                            $selectAry = SHCSLib::getCode($v->sys_code,1,1);
                        } elseif($v->ans_type == 4) {
                            //$param_etime    = sys_param::getParam('PERMIT_TOPIC_A_ID_ETIME',0);
                            //時間
//                            if($v->id == $param_etime)
//                            {
//                                if($shift_id == 2)
//                                {
//                                    $selectAry1 = SHCSLib::genAllTimeAry('23:59','00:00','30',1);
//                                    $selectAry2 = SHCSLib::genAllTimeAry('08:00','00:00','30',1);
//                                    $selectAry = array_merge($selectAry1,$selectAry2);
//                                } else {
//                                    $selectAry = SHCSLib::genTimeAry('23:59',10,1);
//                                }
//                            } else {
//                                $selectAry1 = SHCSLib::genAllTimeAry('07:00','00:00','30',1);
//                                $selectAry2 = SHCSLib::genAllTimeAry(date('H:i',strtotime('+1 hours')),'07:00','10',1);
//
//                                $selectAry = array_merge($selectAry1,$selectAry2);
//                            }
                            $selectAry = SHCSLib::genAllTimeAry('23:59','00:00','10',1);
                        } else {
                            $selectAry = SHCSLib::genYNApiAry($v->yn_type);
                        }
                        $tmp['ans_select']          = $selectAry;
                    }
                    //顯示危險告知
                    if(in_array($v->wp_option_type,[15]))
                    {
                        //危險告知
                        $tmp['context']             = ($TESET_APP_DANGER_SHOW)? $this->getApiWorkPermitDanger($v->wp_permit_danger_id,0) : '';
                        $tmp['ans_select']          = $NOTICE;
                    }

                }

                $ret[] = $tmp;
            }
        }

        return $ret;
    }

    /**
     * 取得 工作許可證 對應工程身份之 <已在廠>承攬商成員
     *
     * @return array
     */
    public function getApiWorkPermitTopicOptionIdentity($wid,$taid ,$identity_id = 0)
    {
        $ret = '';
        if(!$wid || !$taid) return $ret;
        $extAry = [];
        //取第一層
        $identity_id = $identity_id ? $identity_id : wp_permit_topic_a::getIdentity($taid);
        if($identity_id)
        {
            if(in_array($identity_id,[1,2]))
            {
                //$data = wp_work_worker::getRootMen($wid,$identity_id,0,1);
                $data = wp_work_worker::getLockMenSelect($wid,$identity_id,0,1,0,$extAry);
            } else {
                if($identity_id == 10) {
                    $identity_id = 0;
                    $extAry = [1,2,3,4,5,6,8,9];
                }
                if($identity_id == 9) {
                    $identity_id = 0;
                    $extAry = [];
                }
                $data = wp_work_worker::getLockMenSelect($wid,$identity_id,0,1,0,$extAry);
            }

            //dd([$wid,$taid,$identity_id,$data]);

            if(count($data))
            {
                $str = '';
                foreach ($data as $val)
                {
                    if($str) $str .= '，';
                    $str .= isset($val['name'])? $val['name'] : '';
                }
                $ret = $str;
            }
        }

        return $ret;
    }

    /**
     * 取得 工作許可證 對應工程身份之 <已在廠>承攬商成員
     *
     * @return array
     */
    public function getApiWorkPermitOtherIdentity($wid)
    {
        $ret = '';
        if(!$wid) return $ret;
        $mainIdentity    = sys_param::getParam('PERMIT_MAIN_IDENTITY');
        $mainIdentityAry = explode(',',$mainIdentity);

        $data = wp_work_worker::getLockMenSelect($wid,0,0,1,0,$mainIdentityAry);
//            dd([$wid,$mainIdentityAry,$data]);

        if(count($data))
        {
            $str = '';
            foreach ($data as $val)
            {
                if($str) $str .= '，';
                $str .= isset($val['name'])? $val['name'] : '';
            }
            $ret = $str;
        }

        return $ret;
    }

}
