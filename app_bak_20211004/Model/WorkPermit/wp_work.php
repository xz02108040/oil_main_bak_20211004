<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project_s;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_d;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_param;
use App\Model\View\view_log_door_today;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_work::find($id);
        return (isset($data->aproc))? $data->aproc : 0;
    }

    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_work::where('name',$id);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    protected function isActive($wid)
    {
        if(!$wid) return 0;
        $data = wp_work::where('id',$wid)->whereIn('aproc',['P','K','R','O'])->where('isClose','N');
        $data = $data->select('wp_work.id')->first();
        return isset($data->id)? $data->id : 0;
    }


    protected function hasApplyAddmember($myDeptId = 0)
    {
        $today = date('Y-m-d');
        $data = wp_work::join('wp_work_worker as r','r.wp_work_id','=','wp_work.id')->
            where('wp_work.be_dept_id2',$myDeptId)->
        where('sdate',$today)->whereIn('wp_work.aproc', ['W','P'])->where('r.apply_type',2)->where('r.aproc','A')->
        where('wp_work.isClose','N')->where('r.isClose','N')->groupby('wp_work.id');
        return $data->count();
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = wp_work::find($id);
        return (isset($data->id))? $data->name : '';
    }

    //取得 名稱
    protected  function getID($permit_no)
    {
        if(!$permit_no) return 0;
        $data = wp_work::where('permit_no',$permit_no)->select('id')->where('isClose','N')->first();
        return (isset($data->id))? $data->id : 0;
    }
    //取得 名稱
    protected  function getSupply($id)
    {
        if(!$id) return 0;
        $data = wp_work::find($id);
        return (isset($data->id))? $data->b_supply_id : 0;
    }
    //取得 名稱
    protected  function getSupplySubName($id)
    {
        if(!$id) return 0;
        $data = wp_work::find($id);
        $supply_id = (isset($data->id))? $data->b_supply_id : 0;
        $permit_no = (isset($data->id))? $data->permit_no : '';

        return $supply_id? b_supply::getSubName($supply_id) : $permit_no;
    }

    //取得 名稱
    protected  function getNo($id,$showType = 1)
    {
        if(!$id) return '';
        if($showType == 3)
        {
            $data = wp_work::join('b_supply as s','s.id','=','wp_work.b_supply_id')->
                join('b_factory_a as a','a.id','=','wp_work.b_factory_a_id')->
                where('wp_work.id',$id)->select('permit_no','a.name as local','s.name as supply')->first();
            $ret  = (isset($data->permit_no) && $data->permit_no)? $data->local.'，'.$data->supply.'('.$data->permit_no.')' : '';
        }elseif($showType == 2)
        {
            $data = wp_work::join('b_factory_d as d','d.id','=','wp_work.b_factory_d_id')->
            where('wp_work.id',$id)->select('permit_no','d.name')->first();
            $ret  = (isset($data->permit_no) && $data->permit_no)? $data->permit_no.'('.$data->name.')' : '';
        } else {
            $data = wp_work::where('id',$id)->select('permit_no')->first();
            $ret  = (isset($data->permit_no))? $data->permit_no : '';
        }

        return $ret;
    }

    //取得 廠區
    protected  function getStore($id)
    {
        if(!$id) return 0;
        $data = wp_work::where('id',$id)->select('b_factory_id')->first();
        return (isset($data->b_factory_id))? $data->b_factory_id : 0;
    }
    //取得 廠區
    protected  function getLocalInfo($id)
    {
        if(!$id) return [0,'','',0,''];
        $data = wp_work::join('b_factory_a as a','a.id','=','wp_work.b_factory_a_id')->
            join('b_supply as s','s.id','=','wp_work.b_supply_id')->
            where('wp_work.id',$id)->select('a.id','a.name','a.voice_box_ip','wp_work.b_supply_id','s.tax_num')->first();
        return (isset($data->id))? [$data->id,$data->name,$data->voice_box_ip,$data->b_supply_id,$data->tax_num] : [0,'','',0,''];
    }

    //取得 班別
    protected  function getShift($id)
    {
        if(!$id) return 0;
        $data = wp_work::where('id',$id)->select('wp_permit_shift_id')->first();
        return (isset($data->wp_permit_shift_id))? $data->wp_permit_shift_id : 0;
    }

    //取得 工作地點GPS
    protected  function getLocalGPS($id)
    {
        $ret = ['',0,0];
        if(!$id) return $ret;
        $data = wp_work::where('id',$id)->select('b_factory_b_id')->first();
        if(isset($data->b_factory_b_id))
        {
            $ret = b_factory_b::getGPS($data->b_factory_b_id);
        }
        return $ret;
    }

    //取得 名稱
    protected  function getApplyUser($id)
    {
        if(!$id) return 0;
        $data = wp_work::where('id',$id)->where('isClose','N')->select('id','apply_user')->first();
        return (isset($data->id))? $data->apply_user : 0;
    }

    //取得 名稱
    protected  function getAmt($factory_id = 0,$aproc = 'A',$sdate = '')
    {
        if(!$aproc) return 0;
        $sdate = (!$sdate)? date('Y-m-d') : '';
        $data = wp_work::where('sdate',$sdate)->where('isClose','N')->where('aproc',$aproc);
        if($factory_id)
        {
            $data = $data->where('b_factory_id',$factory_id);
        }
        return $data->count();
    }

    //取得 名稱
    protected  function getDept($id,$type = 1)
    {
        $ret = 0;
        if(!$id) return '';
        $data = wp_work::where('id',$id)->where('isClose','N')->first();
        //轄區部門上一層
        if($type == 5)
        {
            if(isset($data->be_dept_id5) && $data->be_dept_id5 > 0)
            {
                $ret = be_dept::getParantDept($data->be_dept_id5);
            }
        }
        elseif($type == 4)
        {
            if(isset($data->be_dept_id4) && $data->be_dept_id4 > 0)
            {
                $ret = $data->be_dept_id4;
            }
        }
        elseif($type == 3)
        {
            if(isset($data->be_dept_id3) && $data->be_dept_id3 > 0)
            {
                $ret = $data->be_dept_id3;
            }
        }
        elseif($type == 2)
        {
            if(isset($data->be_dept_id2) && $data->be_dept_id2 > 0)
            {
                $ret = $data->be_dept_id2;
            }
        }
        elseif($type == 1)
        {
            if(isset($data->be_dept_id1) && $data->be_dept_id1 > 0)
            {
                $ret = $data->be_dept_id1;
            }
        }
        return $ret;
    }

    //取得 工作許可證類型
    protected  function getPermit($id)
    {
        if(!$id) return 0;
        $data = wp_work::where('id',$id)->where('isClose','N')->select('id','wp_permit_id')->first();
        return (isset($data->id))? $data->wp_permit_id : 0;
    }
    //取得 工作許可證之進度
    protected  function getAproc($id)
    {
        if(!$id) return '';
        $data = wp_work::where('id',$id)->where('isClose','N')->select('id','aproc')->first();
        return (isset($data->id))? $data->aproc : '';
    }
    //取得 工作許可證 審查負責人(承攬商申請時指定)
    protected  function getProjectCharge($id)
    {
        if(!$id) return 0;
        $data = wp_work::where('id',$id)->where('isClose','N')->select('id','proejct_charge')->first();
        return (isset($data->id))? $data->proejct_charge : 0;
    }
    //取得 工作許可證 工程案件ＩＤ
    protected  function getProjectId($id)
    {
        if(!$id) return 0;
        $data = wp_work::where('id',$id)->where('isClose','N')->select('id','e_project_id')->first();
        return (isset($data->id))? $data->e_project_id : 0;
    }
    //取得 工作許可證之指定進出門別
    protected  function getDoor($id)
    {
        if(!$id) return [0,''];
        $data = wp_work::where('id',$id)->where('isClose','N')->select('id','b_factory_d_id')->first();
        return (isset($data->id))? [$data->b_factory_d_id,b_factory_d::getName($data->b_factory_d_id)] : [0,''];
    }

    //取得 工作許可證模型
    protected  function getData($id)
    {
        if(!$id) return 0;
        return wp_work::find($id);
    }

    //取得 工作許可證<模型ＩＤ，危險等級>
    protected  function getDataList($id)
    {
        $ret = [0,0,0,0];
        if(!$id) return $ret;
        $data = wp_work::find($id);
        return isset($data->id)? [$data->wp_permit_id,$data->wp_permit_danger] : $ret;
    }

    //取得 工作許可證模型
//    protected  function getDoorRule($project_id)
//    {
//        $ret = [[0],[0]];
//        $workerAry = $saferAry = [];
//        if(!$project_id) return $ret;
//        $data = wp_work::where('e_project_id',$project_id)->where('sdate',date('Y-m-d'))->where('isClose','N')->
//                whereNotIn('aproc',['A','B'])->get();
//        if(count($data))
//        {
//            foreach ($data as $val)
//            {
//                $workerAry[$val->supply_worker] = $val->supply_worker;
//                $saferAry[$val->supply_safer]  = $val->supply_safer;
//            }
//            $ret = [$workerAry,$saferAry];
//        }
//
//        return $ret;
//    }


    //取得 聯繫者 參數「機動」「專任」
    protected  function getContactType($work_id)
    {
        $ret = '';
        if(!$work_id) return $ret;
        $data = wp_work::find($work_id);
        if(isset($data->id))
        {
            $ret = ($data->wp_permit_danger == 'A')? Lang::get('sys_base.base_40236') : Lang::get('sys_base.base_40237');
        }
        return $ret;
    }

    /**
     *  找到 當日 正在執行<未啟動，已啟動，已收工> 且 自己為<工地負責人，安衛人員>的的工作許可證
     * @param $id
     * @return int
     */
    protected function isWorkerExist($project,$store,$uid)
    {
        if(!$project || !$uid || !$store) return '';

        $data  = wp_work::join('wp_work_worker as w','w.wp_work_id','=','wp_work.id')->
        where('w.user_id',$uid)->where('w.isClose','N')->where('wp_work.b_factory_id',$store)->
        where('wp_work.e_project_id',$project)->where('wp_work.sdate',date('Y-m-d'))->where('wp_work.isClose','N')->
        whereNotIn('wp_work.aproc',['A','B'])->select('w.aproc')->first();

        return isset($data->aproc)? $data->aproc : '';
    }

    /**
     *  找到 當日 正在執行<未啟動，已啟動，已收工> 且 自己為<工地負責人，安衛人員>的的工作許可證
     * @param $id
     * @return int
     */
    protected function getTodayWork($project,$store,$door,$uid,$door_type = 1)
    {
        $work_id = $isRoot = $isIn = $identity = '';
        $isAdd      = $isLevel = 0;
        $errCodeNo  = 3;
        $errCode    =  'base_30157'; //無工作許可證
        if(!$project || !$store || !$uid) return [$work_id,$identity,$isRoot,$isIn,$errCode,$errCodeNo,$isAdd];

        $today      = date('Y-m-d');
        $yesterDay  = SHCSLib::addDay(-1);
        $now_limit  = strtotime(date('Y-m-d 09:00:00'));
        $yesterDay_limit = $yesterDay.' 18:00:00';
        $isOverYesterDay = ($now_limit > time())? 1 : 0;
        $identity_A = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $identity_B = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
//        $RootAry    = ($door_type == 2)? [$identity_B] : [$identity_A,$identity_B];
        $RootAry    = [$identity_A,$identity_B];
        $allowWorkOrderAry = ['O'=>[],'R'=>[],'W'=>[],'P'=>[],'F'=>[],'C'=>[]];

        //1. 先確定 是否已經被綁定工作許可證(不限門別)
        //1-1.
        $work_id = rept_doorinout_t::getWorkId($store,$today,$uid);
        //1-2.
        if(!$work_id && $isOverYesterDay)
        {
            $work_id = rept_doorinout_t::getWorkId($store,$yesterDay,$uid,$yesterDay_limit);
        }
//        dd($work_id);
        //1-3. 如果綁定工作許可證
        if($work_id)
        {
            $workOj             = wp_work::getData($work_id);
            $aproc              = isset($workOj->aproc)? $workOj->aproc : 'W';
            $work_project_id    = isset($workOj->e_project_id)? $workOj->e_project_id : 0;
            $danger             = isset($workOj->wp_permit_danger)? $workOj->wp_permit_danger : 'C';
            $identity           = wp_work_worker::getIdentity($work_id,$uid);
            $isRoot             = in_array($identity,$RootAry)? 1 : 0;
            $isIn               = 1;
            $errCodeNo          = 0;
            $errCode            = '';


            //2021-02-26 進廠，如果已經被更換工程案件
            if($door_type == 1 && $work_project_id != $project)
            {
                $work_id = 0;
            }
            //2021-01-29 進廠，如果工單未啟動，則重新選擇工單
            if($door_type == 1 && !in_array($aproc,['P','K','R','O']))
            {
                $work_id = 0;
            }
            //2021-01-31 離廠，如果是工負，不限門別
            if($door_type == 2 && ($identity == $identity_A))
            {
                //$work_id = 0;
            }
        }


        //1-4. 如果尚未綁定工作許可證
        if(!$work_id)
        {
            //2-1. [工安＆工負]先找到今日是否可以進場的工作許可證
            $data  = wp_work::join('wp_work_worker as w','w.wp_work_id','=','wp_work.id')->
            where('w.user_id',$uid)->where('w.isClose','N')->where('wp_work.b_factory_id',$store)->
            where('wp_work.b_factory_d_id',$door)->
            where('wp_work.e_project_id',$project)->where('wp_work.sdate',$today)->
            where('wp_work.isClose','N');
            //2-2. 審查通過/已啟動/施工中/申請收工 + 已停工/已收工
            $data  = $data->whereIn('wp_work.aproc',['P','W','R','O']);
            $data  = $data->whereNotIn('w.aproc',['A','C','L']);
            $data  = $data->whereIn('w.engineering_identity_id',[$identity_A,$identity_B]);
            $data  = $data->select('wp_work.id','w.engineering_identity_id','wp_work.aproc','wp_work.wp_permit_danger','w.aproc as worker_aproc');

            if(!$data->count())
            {
                //2-3. [施工人員]
                $data  = wp_work::join('wp_work_worker as w','w.wp_work_id','=','wp_work.id')->
                where('w.user_id',$uid)->where('w.isClose','N')->where('wp_work.b_factory_id',$store)->
                where('wp_work.b_factory_d_id',$door)->
                where('wp_work.e_project_id',$project)->where('wp_work.sdate',$today)->
                where('wp_work.isClose','N');
                //2-4. 審查通過/已啟動/施工中/申請收工 + 已停工/已收工
                $data  = $data->whereIn('wp_work.aproc',['P','W','R','O']);
                $data  = $data->whereNotIn('w.aproc',['A','C','L']);
                $data  = $data->whereNotIn('w.engineering_identity_id',[$identity_A,$identity_B]);
                $data  = $data->select('wp_work.id','w.engineering_identity_id','wp_work.aproc','wp_work.wp_permit_danger','w.aproc as worker_aproc');

            }

            if($data->count())
            {
                $data = $data->get();
                //3-1
                foreach ($data as $val)
                {
                    $aproc          = $val->aproc;
                    $worker_aproc   = $val->worker_aproc;
                    $identity       = $val->engineering_identity_id;
                    $danger         = $val->wp_permit_danger;
//                    dd($uid,$aproc,$worker_aproc);
                    $tmp = [];
                    $tmp['work_id']     = $val->id;
                    $tmp['identity']    = $identity;
                    $tmp['isRoot']      = in_array($identity,$RootAry)? 1 : 0;
                    $tmp['isAdd']       = (in_array($aproc,['W','R']) && $worker_aproc == 'P')? 1 : 0;
                    $tmp['isRoot']      = in_array($identity,$RootAry)? 1 : 0;
                    if($door_type == 2)
                    {
                        $tmp['isIn']        = 1;
                        $tmp['errCode']     = '';
                        $tmp['errCodeNo']   = 0;
                    } else {
                        $tmp['isIn']        = ($worker_aproc == 'L' || in_array($aproc,['C','F']))? 0 : 1;
                        $tmp['errCode']     = ($worker_aproc == 'L')? 'base_30161' : (in_array($aproc,['C','F'])? 'base_30160' : '');
                        $tmp['errCodeNo']   = ($worker_aproc == 'L')? 6 : (in_array($aproc,['C','F'])? 7 : 0);
                    }

                    $allowWorkOrderAry[$aproc][$val->id] = $tmp;
                }

                //3-2
                foreach ($allowWorkOrderAry as $aproc => $val)
                {
                    if(count($val))
                    {
                        foreach ($val as $val2)
                        {
                            $work_id    = $val2['work_id'];
                            $identity   = $val2['identity'];
                            $isRoot     = $val2['isRoot'];
                            $isIn       = $val2['isIn'];
                            $errCode    = $val2['errCode'];
                            $errCodeNo  = $val2['errCodeNo'];
                            $isAdd      = $val2['isAdd'];
                            break;
                        }
                    }
                    if($work_id) break;
                }
            } else {
                //2-3. [施工人員]
                $data  = wp_work::join('wp_work_worker as w','w.wp_work_id','=','wp_work.id')->
                where('w.user_id',$uid)->where('w.isClose','N')->where('wp_work.b_factory_id',$store)->
                where('wp_work.b_factory_d_id',$door)->
                where('wp_work.e_project_id',$project)->where('wp_work.sdate',$today)->
                where('wp_work.isClose','N');
                //2-4. 審查通過/已啟動/施工中/申請收工 + 已停工/已收工
                $data  = $data->whereIn('wp_work.aproc',['F','C']);
                $data  = $data->whereNotIn('w.aproc',['A','C','L']);
                $data  = $data->whereIn('w.engineering_identity_id',[$identity_A,$identity_B]);
                $data  = $data->select('wp_work.id','w.engineering_identity_id','wp_work.aproc','wp_work.wp_permit_danger','w.aproc as worker_aproc');

                if(!$data->count())
                {
                    //2-3. [施工人員]
                    $data  = wp_work::join('wp_work_worker as w','w.wp_work_id','=','wp_work.id')->
                    where('w.user_id',$uid)->where('w.isClose','N')->where('wp_work.b_factory_id',$store)->
                    where('wp_work.e_project_id',$project)->where('wp_work.sdate',$today)->
                    where('wp_work.isClose','N');
                    //2-4. 審查通過/已啟動/施工中/申請收工 + 已停工/已收工
                    $data  = $data->whereIn('wp_work.aproc',['F','C']);
                    $data  = $data->whereNotIn('w.aproc',['A','C','L']);
                    $data  = $data->whereNotIn('w.engineering_identity_id',[$identity_A,$identity_B]);
                    $data  = $data->select('wp_work.id','w.engineering_identity_id','wp_work.aproc','wp_work.wp_permit_danger','w.aproc as worker_aproc');
                }

                if($data->count())
                {
                    $data = $data->get();
                    //3-1
                    foreach ($data as $val)
                    {
                        $aproc          = $val->aproc;
                        $worker_aproc   = $val->worker_aproc;
                        $identity       = $val->engineering_identity_id;
                        $danger         = $val->wp_permit_danger;
//                    dd($uid,$aproc,$worker_aproc);
                        $tmp = [];
                        $tmp['work_id']     = $val->id;
                        $tmp['identity']    = $identity;
                        $tmp['isRoot']      = in_array($identity,$RootAry)? 1 : 0;
                        $tmp['isAdd']       = (in_array($aproc,['W','R']) && $worker_aproc == 'P')? 1 : 0;
                        $tmp['isRoot']      = in_array($identity,$RootAry)? 1 : 0;
                        if($door_type == 2)
                        {
                            $tmp['isIn']        = 1;
                            $tmp['errCode']     = '';
                            $tmp['errCodeNo']   = 0;
                        } else {
                            $tmp['isIn']        = ($worker_aproc == 'L' || in_array($aproc,['C','F']))? 0 : 1;
                            $tmp['errCode']     = ($worker_aproc == 'L')? 'base_30161' : (in_array($aproc,['C','F'])? 'base_30160' : '');
                            $tmp['errCodeNo']   = ($worker_aproc == 'L')? 6 : (in_array($aproc,['C','F'])? 7 : 0);
                        }

                        $allowWorkOrderAry[$aproc][$val->id] = $tmp;
                    }

                    //3-2
                    foreach ($allowWorkOrderAry as $aproc => $val)
                    {
                        if(count($val))
                        {
                            foreach ($val as $val2)
                            {
                                $work_id    = $val2['work_id'];
                                $identity   = $val2['identity'];
                                $isRoot     = $val2['isRoot'];
                                $isIn       = $val2['isIn'];
                                $errCode    = $val2['errCode'];
                                $errCodeNo  = $val2['errCodeNo'];
                                $isAdd      = $val2['isAdd'];
                                break;
                            }
                        }
                        if($work_id) break;
                    }
                }
            }
        }

        //離場判斷
        if($door_type == 2)
        {

            //待啟動，收工，停工，可釋放工單
            if(!$errCode && in_array($aproc,['W','F','C']))
            {
                $isLevel = 1;
            }
        }

        return [$work_id,$aproc,$danger,$identity,$isRoot,$isIn,$errCode,$errCodeNo,$isAdd,$isLevel];
    }


    protected function getTodayMyWork($b_cust_id)
    {
        $ret        = [];
        $today      = date('Y-m-d');
        $yesterday  = SHCSLib::addDay(-1);
        $isOver1800 = (strtotime($today.' 18:00:00') <= time())? 1 : 0;
        $shifAry    = wp_permit_shift::getSelect(0); //班別
        $aprocAry   = SHCSLib::getCode('PERMIT_APROC');
        $identityAry= b_supply_engineering_identity::getSelect(0);
        $storeAry   = b_factory::getSelect(0);
        $workingAry = [];
        foreach ($storeAry as $store_id => $store_name)
        {
            $working_id = rept_doorinout_t::getWorkId($store_id,$today,$b_cust_id);
            if(!$working_id && !$isOver1800)
            {
                $working_id = rept_doorinout_t::getWorkId($store_id,$yesterday,$b_cust_id);
            }
            if($working_id)  $workingAry[] = $working_id;
        }

        //2-1. [工安＆工負]先找到今日是否可以進場的工作許可證
        $data  = wp_work::join('wp_work_worker as w','w.wp_work_id','=','wp_work.id')->
        where('w.user_id',$b_cust_id)->where('w.isClose','N')->
        where('wp_work.isClose','N');
        $data = $data->join('e_project as p','p.id','=','wp_work.e_project_id');
        $data = $data->join('b_supply as s','s.id','=','wp_work.b_supply_id');
        $data = $data->join('b_factory as f','f.id','=','wp_work.b_factory_id');
        $data = $data->join('b_factory_a as fa','fa.id','=','wp_work.b_factory_a_id');
        $data = $data->join('b_factory_b as fb','fb.id','=','wp_work.b_factory_b_id');
        $data = $data->join('b_factory_d as fd','fd.id','=','wp_work.b_factory_d_id');
        if($isOver1800)
        {
            $data = $data->where('wp_work.sdate', $today);
        } else {
            $data  = $data->where(function ($query) use ($today,$yesterday){
                $query->where(function ($query2) use ($yesterday) {
                    $query2->where('wp_work.sdate', $yesterday)->where('wp_work.wp_permit_shift_id',2);
                })->orWhere('wp_work.sdate', $today);
            });
        }

        //2-2. 審查通過/已啟動/施工中/申請收工 + 已停工/已收工
        $data  = $data->whereNotIn('w.aproc',['C','L']);
        $data  = $data->select('wp_work.id','w.engineering_identity_id','wp_work.aproc','wp_work.wp_permit_shift_id',
            'p.name as project','p.project_no','s.name as supply','f.name as store','fa.name as local',
            'fb.name as device','fd.name as door','wp_work.wp_permit_danger','wp_work.be_dept_id1','wp_work.be_dept_id2',
            'wp_work.aproc as worker_aproc','wp_work.permit_no');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $tmp = [];
                $tmp['id']                  = $val->id;
                $tmp['permit_no']           = $val->permit_no;
                $tmp['aproc']               = isset($aprocAry[$val->aproc])? $aprocAry[$val->aproc] : '';
                $tmp['shift_name']          = isset($shifAry[$val->wp_permit_shift_id])? $shifAry[$val->wp_permit_shift_id] : '';
                $tmp['wp_permit_danger']    = $val->wp_permit_danger;
                $tmp['project']             = $val->project;
                $tmp['supply']              = $val->supply;
                $tmp['store']               = $val->store;
                $tmp['local']               = $val->local;
                $tmp['device']              = $val->device;
                $tmp['door']                = $val->door;
                $tmp['sdate']               = $val->sdate;
                $tmp['be_dept_id1_name']    = be_dept::getName($val->be_dept_id1);
                $tmp['be_dept_id2_name']    = be_dept::getName($val->be_dept_id2);
                $tmp['supply_worker']       = wp_work_worker::getSelect($val->id,1,0,1);
                $tmp['supply_safer']        = wp_work_worker::getSelect($val->id,2,0,1);
                $tmp['isWorking']           = in_array($val->id,$workingAry)? 'Y' : 'N';
                $identityName               = isset($identityAry[$val->engineering_identity_id])? $identityAry[$val->engineering_identity_id] : '';
                if($identityName){

                    if(!isset($ret[$val->id]))
                    {
                        $ret[$val->id] = $tmp;
                    }
                    $tmp2 = [];
                    $tmp2['id']      = $val->engineering_identity_id;
                    $tmp2['name']    = $identityName;
                    $ret[$val->id]['identity'][] = $tmp2;
                }
            }
            sort($ret);
        }
        return $ret;
    }
    /**
     *  找到 當日 正在執行<已啟動> 且 自己為<工地負責人，安衛人員>的的工作許可證
     * @param $id
     * @return int
     */
    protected function getTodayReadyWork($project,$uid,$store)
    {
        if(!$project && !$uid) return 0;
        //1. 取得目前進出紀錄上面綁定的人
        $work_id = rept_doorinout_t::getLockWorkId($uid);
        //2.

        if(!$work_id)
        {
            $data  = wp_work::join('wp_work_worker as w','w.wp_work_id','=','wp_work.id')->
            where('w.user_id',$uid)->where('w.isClose','N')->where('w.isIn','Y')->
            where('wp_work.b_factory_id',$store)->
            where('wp_work.e_project_id',$project)->where('wp_work.sdate',date('Y-m-d'))->
            whereIn('wp_work.aproc',['P','K','R','O'])->where('wp_work.isClose','N');

            $data = $data->select('wp_work.id')->first();
            $work_id = isset($data->id)? $data->id : 0;
        }

        return $work_id;
    }

    /**
     *  找到 當日 正在執行<已收工> 且 自己為<工地負責人，安衛人員>的的工作許可證
     * @param $id
     * @return int
     */
    protected function getTodayFinishWork($project,$uid,$store)
    {
        if(!$project && !$uid) return 0;

        $data  = wp_work::join('wp_work_worker as w','w.wp_work_id','=','wp_work.id')->
                    where('w.user_id',$uid)->where('w.isClose','N')->where('w.isIn','Y')->where('w.isOut','N')->
                    where('wp_work.b_factory_id',$store)->
                    where('wp_work.e_project_id',$project)->where('wp_work.sdate',date('Y-m-d'))->
                    where('wp_work.aproc','F')->where('wp_work.isClose','N');

        $data = $data->select('wp_work.id')->first();
        return isset($data->id)? $data->id : 0;
    }


    protected function getTodayCount($today,$store,$aproc = ['A','P','K','O'])
    {
        $data = wp_work::where('sdate',$today)->where('b_factory_id',$store)->where('isClose','N');
        $data = $data->whereIn('aproc',$aproc);//,'F','R'
        return $data->count();
    }

    protected function getNewPermitNo($permit_head = '')
    {
        if(!$permit_head) $permit_head = date('Ymd');
        if($permit_head) $permit_head = str_replace('-','',$permit_head);
        $ret  = 'T-'.$permit_head;
        $max_length = 6;

        $data = wp_work::where('permit_no','like','T-'.$permit_head.'%')->orderby('id','desc');

        if($data->count())
        {
            $data = $data->first();
            $last_permit_no = substr($data->permit_no,($max_length * -1));
            $new_permit_no  = $last_permit_no + 1;
        } else {
            $new_permit_no = '1';
        }
        $ret .= str_pad($new_permit_no,$max_length,'0',STR_PAD_LEFT);

        return $ret;
    }

    //取得 工作許可證內的人員<監造，監工，轄區，會簽，承攬商>
    protected  function getWorkAllChargeUser($work_id, $type = 1 )
    {
        $ret    = $workerAry = $margeAry = [];
        $data   = wp_work::where('id',$work_id)->where('isClose','N')->first();
        if(isset($data->id))
        {
            //工作許可證上面的人員
            if(in_array($type,[1,3]))
            {
                if($data->apply_user)       $workerAry[$data->apply_user]         = $data->apply_user;
                if($data->supply_worker)    $workerAry[$data->supply_worker]      = $data->supply_worker;
                if($data->supply_safer)     $workerAry[$data->supply_safer]       = $data->supply_safer;
            }
            if(in_array($type,[1,2]))
            {
                if($data->proejct_charge)   $workerAry[$data->proejct_charge]     = $data->proejct_charge;
                if($data->charge_user)      $workerAry[$data->charge_user]        = $data->charge_user;
                if($data->be_dept_charge2)  $workerAry[$data->be_dept_charge2]    = $data->be_dept_charge2;
                if($data->be_dept_charge3)  $workerAry[$data->be_dept_charge3]    = $data->be_dept_charge3;
                if($data->be_dept_charge4)  $workerAry[$data->be_dept_charge4]    = $data->be_dept_charge4;
            }
            if(in_array($type,[1,4]))
            {
                if($data->proejct_charge)   $workerAry[$data->proejct_charge]     = $data->proejct_charge;
                if($data->charge_user)      $workerAry[$data->charge_user]        = $data->charge_user;
            }

            $chargeUserAry = [];
            //全部
            if($type == 1)
            {
                //實際上跑流程的人
                $chargeUserAry = wp_work_process::getChargerSelect($work_id);
            }
            //所有職員
            if($type == 2)
            {
                $chargeUserAry = wp_work_process::getChargerSelect($work_id,2);
            }
            //監造
            if($type == 4)
            {
                $chargeUserAry = wp_work_process::getChargerSelect($work_id,0,1);
            }
            //如果有實際上跑流程的人
            if(count($chargeUserAry))
            {
                $margeAry = array_merge($workerAry,$chargeUserAry);
                foreach ($margeAry as $val)
                {
                    $ret[$val] = $val;
                }
            }
//            dd($workerAry,$chargeUserAry,$ret);
        }
        return $ret;
    }

    protected function getSelect($project_id ,$isFirst = 1, $aprocAry = [])
    {
        $ret    = [];
        $data   = wp_work::where('e_project_id',$project_id)->select('id','permit_no')->where('isClose','N');
        if(is_array($aprocAry) && count($aprocAry))
        {
            $data = $data->whereIn('aproc',$aprocAry);
        }
        $data = $data->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->permit_no;
        }

        return $ret;
    }


}
