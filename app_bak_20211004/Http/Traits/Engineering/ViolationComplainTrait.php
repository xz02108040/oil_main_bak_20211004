<?php

namespace App\Http\Traits\Engineering;

use App\Model\User;
use App\Lib\SHCSLib;
use App\Model\Supply\b_supply;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_violation;
use App\Model\Engineering\e_violation_law;
use App\Model\Engineering\e_violation_type;
use App\Model\Engineering\e_violation_punish;
use App\Model\Engineering\e_violation_complain;
use App\Model\Engineering\e_violation_contractor;
use App\Model\Engineering\e_violation_contractor_history;

/**
 * 人員違規申訴 維護
 *
 */
trait ViolationComplainTrait
{
    /**
     * 新增 人員違規申訴
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createViolationComplain($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->e_project_id) && $data->e_project_id) return $ret;

        $INS = new e_violation_complain();
        $INS->e_violation_contractor_id = $data->e_violation_contractor_id;
        $INS->e_project_id              = $data->e_project_id;
        $INS->b_supply_id               = $data->b_supply_id;
        $INS->apply_user                = $mod_user;
        $INS->apply_stamp               = date('Y-m-d H:i:s');
        $INS->apply_memo                = $data->apply_memo;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 人員違規申訴
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setViolationComplain($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now  = date('Y-m-d H:i:s');
        $isUp = $isChg = 0;

        $UPD = e_violation_complain::find($id);
        $UPD2 = e_violation_contractor::find($data->vid);

        if(!isset($UPD->id)) return $ret;
        //審查
        if(isset($data->aproc) && in_array($data->aproc,['P','R','O','C']) && $data->aproc !==  $UPD->aproc)
        {
            // 審核通過與不通過都寫入 人員違規_申訴歷程
            if (in_array($data->aproc, ['O', 'C'])) {
                // 記錄違規紀錄歷程
                $INS = new e_violation_contractor_history();
                $INS->e_violation_contractor_id = $data->vid;
                $INS->e_project_id = $UPD2->e_project_id;
                $INS->wp_work_id = $UPD2->wp_work_id;
                $INS->b_supply_id = $UPD2->b_supply_id;
                $INS->b_cust_id = $UPD2->b_cust_id;
                $INS->b_car_id = $UPD2->b_car_id;
                $INS->memo = $UPD2->memo;
                $INS->user_name = $UPD2->user_name;
                $INS->user_no = $UPD2->user_no;
                $INS->e_violation_id = $UPD2->e_violation_id;
                $INS->violation_record1 = $UPD2->violation_record1;
                $INS->violation_record2 = $UPD2->violation_record2;
                $INS->violation_record3 = $UPD2->violation_record3;
                $INS->violation_record4 = $UPD2->violation_record4;
                $INS->isControl = $UPD2->isControl;
                $INS->apply_user = $UPD2->apply_user;
                $INS->apply_stamp = $UPD2->apply_stamp;
                $INS->apply_date = $UPD2->apply_date;
                $INS->apply_time = $UPD2->apply_time;
                $INS->limit_sdate = $UPD2->limit_sdate;
                $INS->limit_edate = $UPD2->limit_edate;
                $INS->e_violation_complain_id = $UPD2->e_violation_complain_id;
                $INS->charge_user = $UPD2->charge_user;
                $INS->charge_stamp = $UPD2->charge_stamp;
                $INS->charge_memo = $UPD2->charge_memo;
                $INS->limit_edate1 = $UPD2->limit_edate1;
                $INS->limit_edate2 = $UPD2->limit_edate2;
                $INS->isClose = $UPD2->isClose;
                $INS->close_user = $UPD2->close_user;
                $INS->close_stamp = $UPD2->close_stamp;
                $INS->new_user = $UPD2->new_user;
                $INS->created_at = $UPD2->created_at;
                $INS->mod_user = $UPD2->mod_user;
                $INS->updated_at = $UPD2->updated_at;
                $INS->save();

                //更新 工程案件＿人員違規申訴
                $isUp++;
                $UPD->aproc         = $data->aproc;
                $UPD->charge_user   = $mod_user;
                $UPD->charge_stamp  = $now;
                $UPD->charge_memo   = $data->memo;

                //受理成功，才回寫 工程案件＿人員違規管理 的相關資料
                if ($data->aproc == 'O') {
                    //變更 罰則
                    $UPD2->e_violation_complain_id  = $id;
                    $UPD2->charge_user              = $mod_user;
                    $UPD2->charge_stamp             = $now;
                    $UPD2->charge_memo              = $data->memo;
                    $UPD2->limit_edate2             = $data->limit_edate2;
                    $UPD2->limit_edate              = $data->limit_edate2;
                    $UPD2->save();
                }
                //非受理成功，從違規申訴complain更新至申訴歷程
                else{
                    $INS->e_violation_complain_id = $id;
                    $INS->charge_user = $mod_user;
                    $INS->charge_stamp = $now;
                    $INS->charge_memo = $data->memo;
                    $INS->limit_edate2 = $data->limit_edate2;
                    $INS->save();
                }
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
     * 取得 人員違規申訴之承攬商列表
     *
     * @return array
     */
    public function getApiViolationComplainSupplyList($search = [0,'',''])
    {
        $ret = array();
        list($supply_id,$aproc,$sdate,$edate) = $search;
        //取第一層
        $data = e_violation_complain::where('e_violation_complain.isClose','N')->
        join('b_supply as s', 'e_violation_complain.b_supply_id','=','s.id')->
        selectRaw('s.id,s.name,COUNT(e_violation_complain.b_supply_id) as amt');

        if($supply_id)
        {
            $data = $data->where('e_violation_complain.b_supply_id',$supply_id);
        }
        if($sdate)
        {
            $data = $data->where('e_violation_complain.apply_date','>=',$sdate.' 00:00:00');
        }
        if($edate)
        {
            $data = $data->where('e_violation_complain.apply_date','<=',$edate.' 23:59:59');
        }
        if($aproc)
        {
            $data = $data->whereIn('e_violation_complain.aproc',$aproc);
        }
        //Mysql5.6 支援
        //$data = $data->selectRaw('MAX(e_violation_complain.b_supply_id) as id');

        $data = $data->groupby('s.id','s.name')->get();
        if(is_object($data))
        {
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 人員違規申訴之工程案件
     *
     * @return array
     */
    public function getApiViolationComplainList($search = [0,0,'',''])
    {
        $ret = array();
        list($pid,$supply_id,$aproc,$sdate,$edate) = $search;
        $aprocAry = SHCSLib::getCode('COMPLAIN_APROC');
        //取第一層
        $data = e_violation_complain::where('e_violation_complain.isClose','N')->
            join('e_violation_contractor as e', 'e_violation_complain.e_violation_contractor_id','=','e.id')->
            join('b_supply as s', 'e_violation_complain.b_supply_id','=','s.id')->
            select('e_violation_complain.*','s.name as b_supply',
                'e.b_cust_id','e.violation_record1','e.violation_record2','e.violation_record3','e.violation_record4',
                'e.apply_stamp as apply_stamp2','e.limit_sdate','e.limit_edate','e.limit_edate1','e.limit_edate2',
                'e.isControl');
        if($pid)
        {
            $data = $data->where('e_violation_complain.e_project_id',$pid);
        }
        if($supply_id)
        {
            $data = $data->where('e_violation_complain.b_supply_id',$supply_id);
        }
        if($sdate)
        {
            $data = $data->where('e_violation_complain.apply_date','>=',$sdate.' 00:00:00');
        }
        if($edate)
        {
            $data = $data->where('e_violation_complain.apply_date','<=',$edate.' 23:59:59');
        }
        if($aproc)
        {
            $data = $data->whereIn('e_violation_complain.aproc',$aproc);
        }
        $data = $data->orderby('id','desc')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['project']     = e_project::getName($v->e_project_id, 2);
                $data[$k]['supply']      = b_supply::getName($v->b_supply_id);
                $data[$k]['aproc_name']  = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['apply_stamp1']= substr($v->apply_stamp,0,16);
                $data[$k]['apply_stamp2']= substr($v->apply_stamp2,0,16);
                $data[$k]['user']        = User::getName($v->b_cust_id);
                $data[$k]['apply_user']  = User::getName($v->apply_user);
                $data[$k]['charge_user'] = User::getName($v->charge_user);
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
