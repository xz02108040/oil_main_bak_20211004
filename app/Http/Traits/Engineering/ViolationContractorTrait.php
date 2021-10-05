<?php

namespace App\Http\Traits\Engineering;

use App\Lib\SHCSLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_violation;
use App\Model\Engineering\e_violation_contractor;
use App\Model\Engineering\e_violation_contractor_history;
use App\Model\Engineering\e_violation_law;
use App\Model\Engineering\e_violation_punish;
use App\Model\Engineering\e_violation_type;
use App\Model\User;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_work;
use Lang;

/**
 * 人員違規維護
 *
 */
trait ViolationContractorTrait
{
    /**
     * 新增 人員違規
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createViolationContractor($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->e_violation_id) && $data->e_violation_id) return $ret;
        $violationAry = e_violation::getData($data->e_violation_id);
        if(!isset($violationAry->name)) return $ret;

        $INS = new e_violation_contractor();
        $INS->e_project_id          = $data->e_project_id;
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->b_supply_id           = $data->b_supply_id;
        $INS->b_cust_id             = $data->b_cust_id;
        $INS->e_violation_id        = $data->e_violation_id;
        $INS->apply_user            = $mod_user;
        $INS->apply_stamp           = $data->apply_stamp;
        $INS->apply_date            = $data->apply_date;
        $INS->apply_time            = $data->apply_time;
        $INS->violation_record1     = $violationAry->name;
        $INS->violation_record2     = $violationAry->law;
        $INS->violation_record3     = $violationAry->punish;
        $INS->violation_record4     = $violationAry->type;
        $INS->isControl             = $violationAry->isControl;
        $INS->memo                  = isset($data->memo)? $data->memo : '';
        if($violationAry->isControl == 'Y')
        {
            $edate              = SHCSLib::addDay($violationAry->limit_day,$data->apply_date);
            $INS->limit_sdate   = $data->apply_date;
            $INS->limit_edate   = $edate;
            $INS->limit_edate1  = $edate;
        }


        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 人員違規
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setViolationContractor($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $violationAry   = e_violation::getData($data->e_violation_id);
        $now            = date('Y-m-d H:i:s');
        $isUp           = $isChg = 0;

        $UPD = e_violation_contractor::find($id);
        if(!isset($UPD->e_violation_id)) return $ret;
        //違規罰則
        if(isset($data->e_violation_id) && $data->e_violation_id && $data->e_violation_id !==  $UPD->e_violation_id)
        {
            $isUp++;
            $isChg++;
            $UPD->e_violation_id        = $data->e_violation_id;
            $UPD->violation_record1     = $violationAry->name;
            $UPD->violation_record2     = $violationAry->law;
            $UPD->violation_record3     = $violationAry->punish;
            $UPD->violation_record4     = $violationAry->type;
            $UPD->isControl             = $violationAry->isControl;
        }
        //違規時間
        if(isset($data->apply_stamp) && strlen($data->apply_stamp) && ($isChg || $data->apply_stamp !==  $UPD->apply_stamp))
        {
            $isUp++;
            $UPD->apply_stamp = $data->apply_stamp;
            $UPD->apply_date  = $data->apply_date;
            $UPD->apply_time  = $data->apply_time;
            if($violationAry->isControl == 'Y')
            {
                $edate  = SHCSLib::addDay($violationAry->limit_day,$data->apply_date);
                $UPD->limit_sdate       = $data->apply_date;
                $UPD->limit_edate       = $edate;
                $UPD->limit_edate1      = $edate;
            } else {
                $UPD->limit_sdate       = '';
                $UPD->limit_edate       = '';
            }
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !==  $UPD->isClose)
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
     * 取得 人員違規之承攬商列表
     *
     * @return array
     */
    public function getApiViolationContractorSupplyList($search = [0,'',''])
    {
        $ret = array();
        list($supply_id,$sdate,$edate) = $search;
        //取第一層
        $data = e_violation_contractor::where('e_violation_contractor.isClose','N')->
        join('b_supply as s', 'e_violation_contractor.b_supply_id','=','s.id');
        if($supply_id)
        {
            $data = $data->where('s.id',$supply_id);
        }
        if($sdate)
        {
            $data = $data->where('e_violation_contractor.apply_date','>=',$sdate);
        }
        if($edate)
        {
            $data = $data->where('e_violation_contractor.apply_date','<=',$edate);
        }
        //Mysql5.6 支援
        $data = $data->selectRaw('e_violation_contractor.b_supply_id,s.id,s.name,s.boss_name,s.tax_num,s.tel1,s.tel2,COUNT(b_supply_id) as amt');

        $data = $data->groupby('e_violation_contractor.b_supply_id','s.id','s.name','s.boss_name','s.tax_num','s.tel1','s.tel2')->get();
        if(is_object($data))
        {
            $ret = (object)$data;
        }
        return $ret;
    }

    /**
     * 取得 人員違規之工程案件
     *
     * @return array
     */
    public function getApiViolationContractorList($search = [0,'','','',0,'','',''],$level = 2,$isAPP=0,$self_user = 0)
    {
        $ret = array();
        list($supply_id,$violation_id,$sdate,$edate,$project_id,$law,$punish,$permit_no) = $search;
        $yesAry = SHCSLib::getCode('YES');
        $work_id= wp_work::getID($permit_no);
        //取第一層
        $data = e_violation_contractor::where('e_violation_contractor.isClose','N')->
        join('b_supply as s', 'e_violation_contractor.b_supply_id','=','s.id')->
        join('b_cust as m', 'e_violation_contractor.b_cust_id','=','m.id')->
        join('b_cust_a as ma', 'e_violation_contractor.b_cust_id','=','ma.b_cust_id')->
        join('e_violation as v', 'e_violation_contractor.e_violation_id','=','v.id')->
        join('e_violation_law as l', 'v.e_violation_law_id','=','l.id')->
        join('e_violation_punish as u', 'v.e_violation_punish_id','=','u.id');

        if($self_user)
        {
            $data = $data->where('e_violation_contractor.apply_user',$self_user);
        }
        if($supply_id)
        {
            $data = $data->where('s.id',$supply_id);
        }
        if($violation_id)
        {
            $data = $data->where('v.id',$violation_id);
        }
        if($law)
        {
            $data = $data->where('l.id',$law);
        }
        if($punish)
        {
            $data = $data->where('u.id',$punish);
        }
        if($project_id)
        {
            $data = $data->where('e_violation_contractor.e_project_id',$project_id);
        }
        if($work_id)
        {
            $data = $data->where('e_violation_contractor.wp_work_id',$work_id);
        }
        if($sdate)
        {
            $data = $data->where('e_violation_contractor.apply_date','>=',$sdate);
        }
        if($edate)
        {
            $data = $data->where('e_violation_contractor.apply_date','<=',$edate);
        }
        //
        if($level == 1)
        {
            $data = $data->selectRaw('v.id,v.name,
            COUNT(v.id) as amt,l.name as law')->
            groupby('v.id')->groupby('v.name')->groupby('l.name');
        } else {
            $data = $data->select('e_violation_contractor.*','s.name as supply','m.name as user',
                'ma.bc_id','v.name','v.isControl','v.limit_day','l.id as title_id')->
            orderby('e_violation_contractor.apply_date','desc');
        }

        $data = $data->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                if($isAPP)
                {
                    $tmp = [];
                    if($level == 1)
                    {
                        $tmp['violation_id']             = $v->id;
                        $tmp['headline']                = $v->name;
                        $tmp['title']                   = $v->law;
                        $tmp['amt']                     = $v->amt;

                    } else {
                        $tmp['apply_name']              = User::getName($v->apply_user);
                        $tmp['apply_date']              = $v->apply_date;
                        $tmp['apply_stamp']             = substr($v->apply_stamp,0,19);
                        $tmp['supply_name']             = $v->supply;
                        $tmp['user_name']               = $v->user;
                        $tmp['project']                 = e_project::getName($v->e_project_id, 2);
                        $tmp['permit_no']               = wp_work::getNo($v->wp_work_id);
                        $tmp['violation_record1']       = $v->violation_record1;
                        $tmp['violation_record2']       = $v->violation_record2;
                        $tmp['violation_record3']       = $v->violation_record3;
                        $tmp['violation_record4']       = $v->violation_record4;
                        $tmp['isControl']               = $v->isControl;
                        $tmp['limit_sdate']             = $v->limit_sdate;
                        $tmp['limit_edate']             = $v->limit_edate;
                    }
                    $ret[] = $tmp;

                } else {
                    $data[$k]['project']     = e_project::getName($v->e_project_id, 2);
                    $data[$k]['permit_no']   = wp_work::getNo($v->wp_work_id);
                    $data[$k]['apply_stamp'] = substr($v->apply_stamp,0,16);
                    $data[$k]['isControl']   = isset($yesAry[$v->isControl])? $yesAry[$v->isControl] : $v->isControl;

                    if($level != 1)
                    {
                        $data[$k]['charge_user'] = User::getName($v->charge_user);
                        $data[$k]['close_user']  = User::getName($v->close_user);
                        $data[$k]['new_user']    = User::getName($v->new_user);
                        $data[$k]['mod_user']    = User::getName($v->mod_user);
                    }
                }

            }
            if(!$isAPP)$ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 人員違規之工程案件申訴歷程清單
     *
     * @return array
     */
    public function getApiViolationContractorHistoryList($e_violation_contractor_id)
    {
        $data = e_violation_contractor_history::where('e_violation_contractor_history.e_violation_contractor_id',$e_violation_contractor_id);
        $data = $data->select('e_violation_contractor_history.*')->
            orderby('e_violation_contractor_history.id','desc');

        $data = $data->get();
        $arr = $data->toArray();
        foreach ($arr as $k => $v) {
            $arr[$k]['charge_user_name'] = User::getName($v['charge_user']);
        }

        return $arr;
    }

    /**
     * 取得 人員違規之工程案件
     *
     * @return array
     */
    public function getApiViolationContractorTodayList($search = [0,'',0,'',''])
    {
        $ret = array();
        list($supply_id,$violation_id,$project_id,$law,$punish) = $search;
        $sdate = date('Y-m-d');
        //取第一層
        $data = e_violation_contractor::where('e_violation_contractor.isClose','N')->
        join('b_supply as s', 'e_violation_contractor.b_supply_id','=','s.id')->
        join('b_cust as m', 'e_violation_contractor.b_cust_id','=','m.id')->
        join('b_cust_a as ma', 'e_violation_contractor.b_cust_id','=','ma.b_cust_id')->
        join('e_violation as v', 'e_violation_contractor.e_violation_id','=','v.id')->
        join('e_violation_law as l', 'v.e_violation_law_id','=','l.id')->
        join('e_violation_punish as u', 'v.e_violation_punish_id','=','u.id')->
        where('e_violation_contractor.isControl','Y');
        $data = $data->where('e_violation_contractor.limit_sdate','<=',$sdate)->
                where('e_violation_contractor.limit_edate','>=',$sdate);

        if($supply_id)
        {
            $data = $data->where('s.id',$supply_id);
        }
        if($violation_id)
        {
            $data = $data->where('v.id',$violation_id);
        }
        if($law)
        {
            $data = $data->where('l.id',$law);
        }
        if($punish)
        {
            $data = $data->where('u.id',$punish);
        }
        if($project_id)
        {
            $data = $data->where('e_violation_contractor.e_project_id',$project_id);
        }
        //
        $data = $data->select('e_violation_contractor.*','s.name as supply','m.name as user',
            'ma.bc_id','v.name','v.isControl','v.limit_day','l.id as title_id')->
        orderby('e_violation_contractor.apply_date','desc');

        $data = $data->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp['supply_name']             = $v->supply;
                $tmp['user_name']               = $v->user;
                $tmp['project']                 = e_project::getName($v->e_project_id);
                $tmp['permit_no']               = wp_work::getNo($v->wp_work_id);
                $tmp['violation_record1']       = $v->violation_record1;
                $tmp['violation_record2']       = $v->violation_record2;
                $tmp['violation_record3']       = $v->violation_record3;
                $tmp['violation_record4']       = $v->violation_record4;
                $tmp['isControl']               = $v->isControl;
                $tmp['limit_sdate']             = $v->limit_sdate;
                $tmp['limit_edate']             = $v->limit_edate;

                $ret[] = $tmp;
            }
        }

        return $ret;
    }



}
