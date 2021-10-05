<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_car;
use App\Model\Engineering\e_project_f;
use App\Model\Engineering\e_project_license;
use App\Model\Engineering\e_project_s;
use App\Model\Engineering\e_project_type;
use App\Model\Factory\b_car;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_rfid;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\View\view_project_factory;
use App\Model\View\view_user;
use Lang;
use DB;

/**
 * 工程案件
 *
 */
trait EngineeringTrait
{
    /**
     * 新增 工程案件
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineering($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;
        $today = date('Y-m-d');

        $INS = new e_project();
        $INS->name           = $data->name;
        $INS->project_type   = $data->project_type ? $data->project_type : 1;
        $INS->b_factory_id   = $data->b_factory_id ? $data->b_factory_id : 1;
        $INS->b_factory_id2  = $data->b_factory_id2 ? $data->b_factory_id2 : 1;
        $INS->project_no     = $data->project_no;
        $INS->charge_dept    = $data->charge_dept;
        $INS->charge_user    = $data->charge_user;
        $INS->charge_dept2   = $data->charge_dept2;
        $INS->charge_user2   = $data->charge_user2;
        $INS->b_supply_id    = $data->b_supply_id;
        //2021-02-20 門禁規則
        if($INS->project_type == 2)
        {
            $INS->door_check_rule= 2;//依據工程案件
        } else {
            $INS->door_check_rule= 3; //依據工單
        }

        $INS->aproc          = 'P'; //施工階段
        $INS->sdate          = $data->sdate ? $data->sdate : $today;
        $INS->edate          = $data->edate ? $data->edate : $today;
        //$INS->stime          = $data->stime ? $data->stime : '00:00:00';
        //$INS->etime          = $data->etime ? $data->etime : '24:00:00';
        $INS->memo           = $data->memo;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;
        if($ret)
        {
            //2021-02-22
            $upAry[]                    = [];
            $upAry['e_project_id']      = $ret;
            $upAry['b_factory_id']      = 6;
            $upAry['b_factory_a_id']    = 1;

            //新增
            $ret = $this->createEngineeringFactory($upAry,$this->b_cust_id);
        }

        return $ret;
    }

    /**
     * 修改 工程案件
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setEngineering($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now        = date('Y-m-d H:i:s');
        $aprocAry   = SHCSLib::getCode('ENGINEERING_APROC');
        $isUp = 0;

        $UPD = e_project::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !==  $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //工程類別
        if(isset($data->project_type) && is_numeric($data->project_type) && $data->project_type !==  $UPD->project_type)
        {
            $isUp++;
            $UPD->project_type = $data->project_type;
            //2021-02-20 門禁規則
            if($data->project_type == 2)
            {
                $UPD->door_check_rule= 2;//依據工程案件
            } else {
                $UPD->door_check_rule= 3; //依據工單
            }
        }
        //工程編號
        if(isset($data->project_no) && strlen($data->project_no) && $data->project_no !==  $UPD->project_no)
        {
            $isUp++;
            $UPD->project_no = $data->project_no;
        }
        //負責承辦廠區
        if(isset($data->b_factory_id) && is_numeric($data->b_factory_id) && $data->b_factory_id !==  $UPD->b_factory_id)
        {
            $isUp++;
            $UPD->b_factory_id = $data->b_factory_id;
        }
        //負責承辦廠區
        if(isset($data->b_factory_id2) && is_numeric($data->b_factory_id2) && $data->b_factory_id2 !==  $UPD->b_factory_id2)
        {
            $isUp++;
            $UPD->b_factory_id2 = $data->b_factory_id2;
        }
        //負責部門
        if(isset($data->charge_dept) && is_numeric($data->charge_dept) && $data->charge_dept !==  $UPD->charge_dept)
        {
            $isUp++;
            $UPD->charge_dept = $data->charge_dept;
        }
        //負責人
        if(isset($data->charge_user) && is_numeric($data->charge_user) && $data->charge_user !==  $UPD->charge_user)
        {
            $isUp++;
            $UPD->charge_user = $data->charge_user;
        }
        //負責監工部門
        if(isset($data->charge_dept2) && is_numeric($data->charge_dept2) && $data->charge_dept2 !==  $UPD->charge_dept2)
        {
            $isUp++;
            $UPD->charge_dept2 = $data->charge_dept2;
        }
        //監工
        if(isset($data->charge_user2) && is_numeric($data->charge_user2) && $data->charge_user2 !==  $UPD->charge_user2)
        {
            $isUp++;
            $UPD->charge_user2 = $data->charge_user2;
        }
        //承攬商
        if(isset($data->b_supply_id) && is_numeric($data->b_supply_id) && $data->b_supply_id !==  $UPD->b_supply_id)
        {
            $isUp++;
            $UPD->b_supply_id = $data->b_supply_id;
        }
        //門禁規則
        if(isset($data->door_check_rule) && is_numeric($data->door_check_rule) && $data->door_check_rule !==  $UPD->door_check_rule)
        {
            $isUp++;
            $UPD->door_check_rule = $data->door_check_rule;
        }
        //進度
        if(isset($data->aproc) && in_array($data->aproc,array_keys($aprocAry)) )
        {
            $isUp++;
            //產生異動日期
            $data->old_aproc = $UPD->aproc;
            $data->new_aproc = $data->aproc;
            $data->old_edate = $UPD->edate;
            $data->chg_edate = $data->edate ? $data->edate : '1900-01-01';
            //展延
            if($data->aproc == 'R')
            {
                $data->chg_memo = Lang::get('sys_engineering.engineering_125',['old'=>$UPD->edate,'new'=>$data->edate]);
            }
            $this->createEngineeringHistory($id,$data,$mod_user);

            //停工&過期&結案，則停用所有承攬商之工程身分與車輛
            if(in_array($data->aproc,['A','C','O']))
            {
                //停工後 允許復工，但是車輛/工程身分 皆須重新申請
                if (in_array($data->aproc, ['A', 'O'])) {
                    //先抓所有車輛
                    $carAry = e_project_car::getSelect($id, 0, 0);
                    $closeAry = ['isClose' => 'Y', 'close_user' => $mod_user, 'close_stamp' => $now];
                    //工程案件之工程身分
                    e_project_license::where('e_project_id', $UPD->id)->where('isClose', 'N')->update($closeAry);
                    //工程案件之車輛
                    e_project_car::where('e_project_id', $UPD->id)->where('isClose', 'N')->update($closeAry);
                    //改變他的工程身分 讓他們必須重新申請工程身分
                    if ($data->aproc == 'A') {
                        $memberAry = ['job_kind' => 5, 'cpc_tag' => 'C'];
                        e_project_s::where('e_project_id', $UPD->id)->where('isClose', 'N')->update($memberAry);
                    }
                    //結案時，自動停用車輛的基本資料
                    if ($data->aproc == 'O' && count($carAry)) {
                        b_car::whereIn('id', $carAry)->where('isClose', 'N')->update($closeAry);
                    }
                    //2021-05-14 結案時，將裡面的人員轉為停用
                    if ($data->aproc == 'O') {
                        e_project_s::where('e_project_id', $UPD->id)->where('isClose', 'N')->update($closeAry);
                    }
                }

                //配卡資格->鎖卡
                $paricardData = e_project_s::join('view_used_rfid as v','v.b_cust_id','=','e_project_s.b_cust_id')->
                where('e_project_s.e_project_id',$UPD->id)->where('e_project_s.isClose','N')->
                select('v.id');
                if($paricardData->count())
                {
                    $paricardAry = [];
                    foreach ($paricardData as $val)
                    {
                        $paricardAry[] = $val->id;
                    }
                    $lockAry = ['isLock'=>'Y','lock_user'=>$mod_user,'lock_stamp'=>$now];
                    b_rfid::whereIn('id',$paricardAry)->update($lockAry);
                }
            }
            //異動工程案件的階段
            $UPD->aproc = $data->aproc;

        }
        //開始日期
        if(isset($data->sdate) && CheckLib::isDate($data->sdate) && $data->sdate !==  $UPD->sdate)
        {
            $isUp++;
            $UPD->sdate = $data->sdate;
        }
        //結束日期
        if(isset($data->edate) && CheckLib::isDate($data->edate) && $data->edate !==  $UPD->edate)
        {
            $isUp++;
            $UPD->edate = $data->edate;
        }
        //開始時間
        if(isset($data->stime) && CheckLib::isTime($data->stime) && $data->stime !==  $UPD->stime)
        {
            $isUp++;
            $UPD->stime = $data->stime;
        }
        //結束時間
        if(isset($data->etime) && CheckLib::isTime($data->etime) && $data->etime !==  $UPD->etime)
        {
            $isUp++;
            $UPD->etime = $data->etime;
        }
        //備註
        if(isset($data->memo) && strlen($data->memo) && $data->memo !==  $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
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
     * 取得 單一工程案件
     *
     * @return array
     */
    public function getEngineeringData($project_id,$isDetail = 'N')
    {
        $ret        = [];
        $aprocAry   = SHCSLib::getCode('ENGINEERING_APROC',0); //進度
        $typeAry    = e_project_type::getSelect(0);
        $deptAry    = be_dept::getSelect(0,0,0,0,0,0,2);
        $storeAry   = b_factory::getSelect(0,0);

        $data = e_project::
        join('b_supply as s','s.id','=','e_project.b_supply_id')->
        where('e_project.id',$project_id)->select('e_project.*','s.name as supply');
        $data = $data->first();
        if(isset($data->id))
        {
            $data['type']              = isset($typeAry[$data->project_type])? $typeAry[$data->project_type] : '';
            $data['aproc_name']        = isset($aprocAry[$data->aproc])? $aprocAry[$data->aproc] : '';

            list($name, $mobile) = User::getMobileInfo($data->charge_user);
            $data['charge_store_name']      = isset($storeAry[$data->b_factory_id])? $storeAry[$data->b_factory_id] : '';
            $data['charge_dept_name']       = isset($deptAry[$data->charge_dept])? $deptAry[$data->charge_dept] : '';
            $data['charge_user_name']       = $name;
            $data['charge_user_mobile']     = $mobile;
            list($name, $mobile) = User::getMobileInfo($data->charge_user2);
            $data['charge_store_name2']     = isset($storeAry[$data->b_factory_id2])? $storeAry[$data->b_factory_id2] : '';
            $data['charge_dept_name2']      = isset($deptAry[$data->charge_dept2])? $deptAry[$data->charge_dept2] : '';
            $data['charge_user_name2']      = $name;
            $data['charge_user_mobile2']    = $mobile;
            //是否顯示
            if($isDetail == 'Y')
            {
                //場地
                $data['local']      = $this->getApiEngineeringFactory($data->id);
                //限定工程身分
                $data['identity']   = $this->getApiEngineeringLicense($data->id);
                //轄區部門
                $data['dept']       = $this->getApiEngineeringDept($data->id);
                //車輛
                $data['car']        = $this->getApiEngineeringCar($data->id);
                //變更歷程
                $data['history']    = $this->getApiEngineeringHistoryList($data->id);
                //角色人員
                $data['worker']     = $this->getApiEngineeringMember($data->id);
            }
            $ret = (object)$data;
        }
        return $ret;
    }

    /**
     * 取得 工程案件
     *
     * @return array
     */
    public function getApiEngineeringList($searchAry = [0,'','','','','','',0],$isClose = 'N',$isAdcheck = '',$isDetail = 'N')
    {
        $ret = array();
        list($supply, $store , $type , $aproc, $project_no, $sdate, $edate, $chargeDept) = $searchAry;
        $aprocAry   = SHCSLib::getCode('ENGINEERING_APROC',0); //進度
        $typeAry    = e_project_type::getSelect();
        $deptAry    = be_dept::getSelect(0,0,0,0,0,0);
        $storeAry   = b_factory::getSelect();
        $isClose    = in_array($isClose,['N','Y'])? $isClose : 'N';
        //取第一層
        $data = e_project::
        join('b_supply as s','s.id','=','e_project.b_supply_id')->
        where('e_project.isClose',$isClose)->select('e_project.*','s.name as supply','s.boss_name','s.tel1');
        if($chargeDept)
        {
            $data = $data->where('e_project.charge_dept', $chargeDept);
        }
        if($supply)
        {
            $data = $data->where('e_project.b_supply_id', $supply);
        }
        if($store)
        {
            //施作廠區
            //$data = $data->join('e_project_f as f','f.e_project_id','=','e_project.id')->where('f.b_factory_id', $store);
            //改用承辦廠區
            $data = $data->where('e_project.b_factory_id', $store);
        }
        if($type)
        {
            $data = $data->where('e_project.project_type', $type);
        }
        if($project_no)
        {
            $data = $data->where('e_project.project_no', 'like' , '%'.$project_no.'%');
        }
        if(is_array($aproc) && count($aproc))
        {
            $data = $data->whereIn('e_project.aproc', $aproc);
        }
        elseif(is_string($aproc) && $aproc)
        {
            $data = $data->where('e_project.aproc', $aproc);
        }
        if($sdate && $edate)
        {
            $data = $data->where('e_project.sdate', '<=',$sdate)->where('e_project.edate', '>=',$sdate)->where('e_project.edate', '>=',$edate);
        }
        if($sdate && !$edate)
        {
            $data = $data->where('e_project.sdate', '<=',$sdate)->where('e_project.edate', '>=',$sdate);
        }
        if(!$sdate && $edate)
        {
            $data = $data->where('e_project.edate', '<=',$edate);
        }

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                $v = (object)$v;
                $data[$k]['type']              = isset($typeAry[$v->project_type])? $typeAry[$v->project_type] : '';
                $data[$k]['aproc_name']        = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';

                list($name, $mobile) = User::getMobileInfo($v->charge_user);
                $data[$k]['charge_store_name']      = isset($storeAry[$v->b_factory_id])? $storeAry[$v->b_factory_id] : '';
                $data[$k]['charge_dept_name']       = isset($deptAry[$v->charge_dept])? $deptAry[$v->charge_dept] : '';
                $data[$k]['charge_user_name']       = $name;
                $data[$k]['charge_user_mobile']     = $mobile;
                list($name, $mobile) = User::getMobileInfo($v->charge_user2);
                $data[$k]['charge_store_name2']     = isset($storeAry[$v->b_factory_id2])? $storeAry[$v->b_factory_id2] : '';
                $data[$k]['charge_dept_name2']      = isset($deptAry[$v->charge_dept2])? $deptAry[$v->charge_dept2] : '';
                $data[$k]['charge_user_name2']      = $name;
                $data[$k]['charge_user_mobile2']    = $mobile;

                $data[$k]['close_user']        = User::getName($v->close_user);
                $data[$k]['new_user']          = User::getName($v->new_user);
                $data[$k]['mod_user']          = User::getName($v->mod_user);
                //是否顯示
                if($isDetail == 'Y')
                {
                    //場地
                    $data[$k]['local']      = $this->getApiEngineeringFactory($v->id);
                    //限定工程身分
                    $data[$k]['identity']   = $this->getApiEngineeringLicense($v->id);
                    //轄區部門
                    $data[$k]['dept']       = $this->getApiEngineeringDept($v->id,$isDetail);
                    //車輛
                    $data[$k]['car']        = $this->getApiEngineeringCar($v->id);
                    //變更歷程
                    $data[$k]['history']    = $this->getApiEngineeringHistoryList($v->id);
                    //角色人員
                    $data[$k]['worker']     = $this->getApiEngineeringMember($v->id);
                }
                //刪除 沒有設定 工安＆工負<2019.08.05 一定要在最後面>
                if($isAdcheck)
                {
                    $isAdExist = e_project_s::isAdExist($v->id);
                    if($isAdcheck == 'N' && $isAdExist) unset($data[$k]);

                    if($isAdcheck == 'Y' && !$isAdExist) unset($data[$k]);
                }
            }

            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 <承攬商>所屬的工程案件 For App
     *
     * @return array
     */
    public function getApiSupplyEngineering($sid,$isDetail = 'N')
    {
        $ret = [];
        if(!$sid) return $ret;
        $aprocAry   = SHCSLib::getCode('ENGINEERING_APROC',0); //進度

        $data = e_project::where('e_project.b_supply_id',$sid)->where('e_project.isClose','N');
        $data = $data->join('b_supply as s','s.id','=','e_project.b_supply_id');
        $data = $data->whereIn('e_project.aproc',['B','R','P']);
        $data = $data->select('e_project.id','e_project.name','e_project.project_no',
            'e_project.charge_dept','e_project.charge_user','e_project.charge_dept2','e_project.charge_user2',
            'e_project.b_supply_id','e_project.sdate','e_project.edate','e_project.aproc');
        $data = $data->orderby('project_no')->get();

        if(count($data))
        {
            foreach ($data as $val)
            {
                $tmp = [];
                $tmp['id']                  = $val->id;
                $tmp['name']                = $val->name;
                $tmp['project_no']          = $val->project_no;
                list($name, $mobile)        = User::getMobileInfo($val->charge_user);
                $tmp['charge_dept_name']    = be_dept::getName($val->charge_dept);
                $tmp['charge_user_name']    = $name;
                $tmp['charge_mobile']       = $mobile;
                list($name, $mobile)        = User::getMobileInfo($val->charge_user2);
                $tmp['charge_dept_name2']   = be_dept::getName($val->charge_dept2);
                $tmp['charge_user_name2']   = $name;
                $tmp['charge_mobile2']      = $mobile;
                $tmp['sdate']               = $val->sdate;
                $tmp['edate']               = $val->edate;
                $tmp['aproc']               = isset($aprocAry[$val->aproc])? $aprocAry[$val->aproc] :'';
                //是否顯示
                if($isDetail == 'Y')
                {
                    //場地
                    $tmp['local']      = $this->getApiEngineeringFactory($val->id);
                    //限定工程身分
                    //$tmp['identity']   = $this->getApiEngineeringLicense($v->id);
                    //轄區部門
                    $tmp['dept']       = $this->getApiEngineeringDept($val->id);
                    //車輛
                    $tmp['car']        = $this->getApiEngineeringCar($val->id);
                    //變更歷程
                    //$tmp['history']    = $this->getApiEngineeringHistoryList($val->id);
                    //角色人員
                    $tmp['worker']     = $this->getApiEngineeringMember($val->id);
                }
                $ret[] = $tmp;
            }
        }
        return $ret;
    }

    /**
     * 檢查 工程案件 過期者，將其作廢
     *
     * @return array
     */
    public function checkProjectOverDate()
    {
        $result = false;

        //過期
        $UPD = [];
        $UPD['aproc']       = 'C'; //過期作業

        //找到已過期 工程案件(施工階段，延長工期階段)
        $ret = DB::table('e_project')->where('isClose','N')->whereIn('aproc',['P','R'])->where('edate','<',date('Y-m-d'));

        //如果有，則作廢
        if($count = $ret->count())
        {
            $data = $ret->select('id','name','project_no','sdate','edate','charge_user','charge_dept')->get();

            foreach ($data as $val)
            {
                //Log
                $upAry = [];
                $upAry['old_aproc'] = $val->aproc;
                $upAry['new_aproc'] = 'C';
                $upAry['aproc_memo'] = Lang::get('sys_engineering.engineering_1049',['edate'=>$val->edate]);
                $this->createEngineeringHistory($val->id,$upAry,'1000000001');
                //推播
                $project_name  = $val->name.'('.$val->project_no.')';
                $project_edate = $val->edate;
                $this->pushToProjectOverDate($val->charge_user,$project_name,$project_edate);
            }
            //更新：結案階段
            $result = $ret->update($UPD);
        }

        return [$result,Lang::get('sys_base.base_10139',['name'=>$count])];
    }
}
