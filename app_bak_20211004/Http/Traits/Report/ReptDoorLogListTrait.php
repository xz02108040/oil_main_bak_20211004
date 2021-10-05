<?php

namespace App\Http\Traits\Report;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_d;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_worker;
use Storage;
use DB;
use Lang;
use Session;

/**
 * 報表：門禁進出紀錄-統計
 *
 */
trait ReptDoorLogListTrait
{

    /**
     * 搜尋報表資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getDoorDayRept($search = [0,0,0,'','','',0])
    {
        list($b_factory_id,$b_supply_id,$b_factory_d_id,$sdate,$edate,$supply_name,$b_cust_id) = $search;
        if(!$sdate) $sdate = date('Y-m-d');
        $ret = [];

        $data = DB::table('log_door_inout');
        //承攬商
        if($b_supply_id)
        {
            $data = $data->where('b_supply_id',$b_supply_id);
        }
        if($supply_name)
        {
            $data = $data->where('unit_name','like','%'.$supply_name.'%');
        }
        //廠區
        if($b_factory_id)
        {
            $data = $data->where('b_factory_id',$b_factory_id);
        }
        //廠區-門口
        if($b_factory_d_id)
        {
            $data = $data->where('b_factory_d_id',$b_factory_d_id);
        }
        //人
        if($b_cust_id)
        {
            $data = $data->where('b_cust_id',$b_cust_id);
        }
        //日期
        if($sdate && !$edate)
        {
            $data = $data->where('door_date',$sdate);
        }

        if($sdate && $edate)
        {
            $data = $data->where('door_date','>=',$sdate)->where('door_date','<=',$edate);
        }


        $data = $data->select('id','b_cust_id','name','job_kind','e_project_id','unit_name','door_stamp','door_type',
                              'b_factory_id','b_factory_d_id','wp_work_id','door_result','door_memo','isOnline','img_path',
                              'err_code');
        $data = $data->orderby('door_stamp','desc');
        //dd($b_factory_id,$b_supply_id,$b_factory_d_id,$sdate,$edate,$data->get());
        if($amt = $data->count())
        {
            $storeAry       = b_factory::getSelect(0);
            $doorAry        = b_factory_d::getSelect(0,0);
            $projectlAry    = e_project::getSelect();
            $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
            $doorResultAry  = SHCSLib::getCode('DOOR_INOUT_RESULT');

            $data = $data->get();
            foreach ($data as $key => $value)
            {
                $memo1 = ($value->door_result != 'Y')? $value->door_memo : '';
                $isUrl = (substr($value->img_path,0,4) == 'http')? 1 : 0;
                $workinfo = wp_work_worker::getWorkInfo($value->wp_work_id);
                $tmp = [];
                $tmp['id']                = $value->id;
                $tmp['name']              = $value->name;
                $tmp['b_cust_id']         = $value->b_cust_id;
                $tmp['job_kind']          = $value->job_kind;
                $tmp['unit_name']         = $value->unit_name;
                $tmp['door_type']         = $value->door_type;
                $tmp['wp_work_id']        = $value->wp_work_id;
                $tmp['permit_no']         = isset($workinfo['no'])? $workinfo['no'] : '';
                $tmp['worker1']           = isset($workinfo['worker1'])? $workinfo['worker1'] : '';
                $tmp['worker2']           = isset($workinfo['worker2'])? $workinfo['worker2'] : '';
                $tmp['door_stamp']        = substr($value->door_stamp,0,19);
                $tmp['door_result']       = $value->door_result;
                //$tmp['img1']              = SHCSLib::toImgBase64String('user',$value->b_cust_id,150);
                //$tmp['img2']              = $isUrl? $value->img_path : SHCSLib::toImgBase64String('door_user',$value->id,150);
                $tmp['store']             = isset($storeAry[$value->b_factory_id])? $storeAry[$value->b_factory_id] : '';
                $tmp['door']              = isset($doorAry[$value->b_factory_d_id])? $doorAry[$value->b_factory_d_id] : '';
                $tmp['project']           = isset($projectlAry[$value->e_project_id])? $projectlAry[$value->e_project_id] : '';
                $tmp['door_type_name']    = isset($doorTypeAry[$value->door_type])? $doorTypeAry[$value->door_type] : '';
                $tmp['isOnline_name']     = ($value->isOnline == 'N')? ('('.Lang::get('sys_base.base_40205').')') : '';
                $tmp['door_result_name']  = isset($doorResultAry[$value->door_result])? $doorResultAry[$value->door_result].$memo1 : '';

                $ret[] = (object)$tmp;
            }
        }
//        dd(count($ret),$ret);
        return [$amt,$ret];
    }

    /**
     * 搜尋報表資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getDoorTodayRept($search = [1,0,0,0,''])
    {
        list($door_type,$b_factory_id,$b_supply_id,$b_factory_d_id,$supply_name) = $search;
        $sdate = date('Y-m-d');
        $nowStamp       = time();
        $yesterday      = SHCSLib::addDay(-1);
        $yesterdaySTime = sys_param::getParam('REPORT_DOOR_YESTERDAY_STIME','00:00:00');
        $yesterdayETime = sys_param::getParam('REPORT_DOOR_YESTERDAY_ETIME','08:00:00');
        $todaySTime     = sys_param::getParam('REPORT_DOOR_TODAY_STIME','08:00:00');
        $y_sdate  = date('Y-m-d H:i:s',strtotime($yesterday.' '.$yesterdaySTime));
        $y_edate  = date('Y-m-d H:i:s',strtotime($sdate.' '.$yesterdayETime));
        $t_sdate  = date('Y-m-d H:i:s',strtotime($sdate.' '.$todaySTime));
        $t_edate  = date('Y-m-d H:i:s',strtotime($sdate.' '.$yesterdaySTime));
        $t_Stamp1 = strtotime($t_sdate);
        $t_Stamp2 = strtotime($t_edate);
        $ret = [];
        $amt = 0;

        $data = rept_doorinout_t::where('door_type',$door_type);
        //日期
        $data = $data->where('door_date','<=',$sdate)->where('door_date','>=',$yesterday);

        //早上九點前
//        if($t_Stamp1 >= $nowStamp)
//        {
//            $data = $data->where('rept_doorinout_t.door_stamp','>=',$y_sdate)->
//            where('rept_doorinout_t.door_stamp','<=',$t_sdate);
//        }
//        //下午六點後
//        elseif($t_Stamp2 <= $nowStamp)
//        {
//            $data = $data->where('rept_doorinout_t.door_stamp','>=',$t_edate);
//        }
//        else {
//            //今日早上六點以後
//            $data = $data->where('rept_doorinout_t.door_stamp','>=',$y_edate);
//        }

        //承攬商
        if($b_supply_id)
        {
            $data = $data->where('b_supply_id',$b_supply_id);
        }
        if($supply_name)
        {
            $data = $data->where('unit_name','like','%'.$supply_name.'%');
        }
        //廠區
        if($b_factory_id)
        {
            $data = $data->where('b_factory_id',$b_factory_id);
        }
        //廠區-門口
        if($b_factory_d_id)
        {
            $data = $data->where('b_factory_d_id',$b_factory_d_id);
        }

        $data = $data->select('id','b_cust_id','name','e_project_id','unit_name','door_stamp','door_type',
            'b_factory_id','b_factory_d_id','wp_work_id','log_door_inout_id');
        $data = $data->orderby('door_stamp','desc');

        if($data->count())
        {
            $storeAry       = b_factory::getSelect();
            $localAry       = b_factory_d::getSelect();
            $projectlAry    = e_project::getSelect();
            $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');

            $data = $data->get();
            foreach ($data as $key => $value)
            {

                $tmp = [];
                $tmp['id']                = $value->id;
                $tmp['name']              = $value->name;
                $tmp['b_cust_id']         = $value->b_cust_id;
                $tmp['b_factory_id']      = $value->b_factory_id;
                $tmp['job_kind']          = $value->job_kind;
                $tmp['unit_name']         = $value->unit_name;
                $tmp['door_type']         = $value->door_type;
                $tmp['wp_work_id']        = $value->wp_work_id;
                $tmp['work_no']           = wp_work::getNo($value->wp_work_id);
                $tmp['door_stamp']        = substr($value->door_stamp,0,19);
                $tmp['log_id']            = $value->log_door_inout_id;
                $tmp['store']             = isset($storeAry[$value->b_factory_id])? $storeAry[$value->b_factory_id] : '';
                $tmp['local']             = isset($localAry[$value->b_factory_d_id])? $localAry[$value->b_factory_d_id] : '';
                $tmp['project']           = isset($projectlAry[$value->e_project_id])? $projectlAry[$value->e_project_id] : '';
                $tmp['door_type_name']    = isset($doorTypeAry[$value->door_type])? $doorTypeAry[$value->door_type] : '';
                $tmp['isOnline_name']     = ($value->isOnline == 'N')? ('('.Lang::get('sys_base.base_40205').')') : '';
                $tmp['img1']              = SHCSLib::toImgBase64String('user',$value->b_cust_id,150);
                $tmp['img2']              = SHCSLib::toImgBase64String('door_user',$value->log_door_inout_id,150);

                $ret[$value->b_cust_id] = (object)$tmp;
            }
            $amt = count($ret);
//            dd($ret);
            $ret = (object)$ret;
        }
        return [$amt,$ret];
    }

    /**
     * 搜尋報表資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getDoorCarDayRept($search = [0,0,0,'',''])
    {
        list($b_factory_id,$b_supply_id,$b_factory_a_id,$sdate,$edate) = $search;
        if(!$sdate) $sdate = date('Y-m-d');
        $ret = [];

        $data = DB::table('log_door_inout_car');
        //承攬商
        if($b_supply_id)
        {
            $data = $data->where('b_supply_id',$b_supply_id);
        }
        //廠區
        if($b_factory_id)
        {
            $data = $data->where('b_factory_id',$b_factory_id);
        }
        //廠區-門口
        if($b_factory_a_id)
        {
            $data = $data->where('b_factory_a_id',$b_factory_a_id);
        }
        //日期
        if($sdate && !$edate)
        {
            $data = $data->where('door_date',$sdate);
        }

        if($sdate && $edate)
        {
            $data = $data->where('door_date','<=',$sdate)->where('edate','>=',$edate);
        }


        $data = $data->select('id','b_car_id','car_no','e_project_id','unit_name','door_stamp','door_type',
            'b_factory_id','b_factory_a_id','wp_work_id','door_result','door_memo','isOnline','img_path',
            'err_code');
        $data = $data->orderby('door_stamp','desc');

        if($data->count())
        {
            $storeAry       = b_factory::getSelect();
            $localAry       = b_factory_a::getSelect();
            $projectlAry    = e_project::getSelect();
            $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
            $doorResultAry  = SHCSLib::getCode('DOOR_INOUT_RESULT');

            $data = $data->get();
            foreach ($data as $key => $value)
            {
                $memo1 = ($value->door_result != 'Y')? $value->door_memo : '';

                $tmp = [];
                $tmp['id']                = $value->id;
                $tmp['name']              = $value->car_no;
                $tmp['b_car_id']          = $value->b_car_id;
                $tmp['unit_name']         = $value->unit_name;
                $tmp['door_type']         = $value->door_type;
                $tmp['door_type']         = $value->door_type;
                $tmp['img_path']          = $value->img_path;
                $tmp['wp_work_id']        = $value->wp_work_id;
                $tmp['door_stamp']        = $value->door_stamp;
                $tmp['img1']              = SHCSLib::toImgBase64String('car',$value->b_car_id,150);
                $tmp['img2']              = SHCSLib::toImgBase64String('door_car',$value->id,150);
                $tmp['store']             = isset($storeAry[$value->b_factory_id])? $storeAry[$value->b_factory_id] : '';
                $tmp['local']             = isset($localAry[$value->b_factory_a_id])? $localAry[$value->b_factory_a_id] : '';
                $tmp['project']           = isset($projectlAry[$value->e_project_id])? $projectlAry[$value->e_project_id] : '';
                $tmp['door_type_name']    = isset($doorTypeAry[$value->door_type])? $doorTypeAry[$value->door_type] : '';
                $tmp['isOnline_name']     = ($value->isOnline == 'N')? ('('.Lang::get('sys_base.base_40205').')') : '';
                $tmp['door_result_name']  = isset($doorResultAry[$value->door_result])? $doorResultAry[$value->door_result].$memo1 : '';

                $ret[] = (object)$tmp;
            }
            $ret = (object)$ret;
        }
//        dd(count($ret),$ret);
        return [count($ret),$ret];
    }

    /**
     * 搜尋報表資料:異常刷卡紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getDoorDayErrRept($search = [0,0,0,'','',0],$isGroupBy = 0)
    {
        list($b_factory_id,$b_supply_id,$b_factory_a_id,$sdate,$edate,$err_code) = $search;
        if(!$sdate) $sdate = date('Y-m-d');
        $ret = [];

        $data = DB::table('log_door_inout')->where('err_code','>',0);
        //承攬商
        if($b_supply_id)
        {
            $data = $data->where('b_supply_id',$b_supply_id);
        }
        //廠區
        if($b_factory_id)
        {
            $data = $data->where('b_factory_id',$b_factory_id);
        }
        //廠區-門口
        if($b_factory_a_id)
        {
            $data = $data->where('b_factory_a_id',$b_factory_a_id);
        }
        //刷卡異常原因
        if($err_code)
        {
            $data = $data->where('err_code',$err_code);
        }
        //日期
        if($sdate && !$edate)
        {
            $data = $data->where('door_date',$sdate);
        }

        if($sdate && $edate)
        {
            $data = $data->where('door_date','<=',$sdate)->where('edate','>=',$edate);
        }

        if($isGroupBy)
        {
            $data = $data->select('id','b_factory_id','door_memo','err_code');
            $data = $data->groupby('err_code')->selectRaw('COUNT(id) as amt')->orderby('err_code');
        } else {
            $data = $data->select('id','b_cust_id','name','job_kind','e_project_id','unit_name','door_stamp','door_type',
                'b_factory_id','b_factory_a_id','wp_work_id','door_result','door_memo','isOnline','img_path',
                'err_code');
            $data = $data->orderby('door_stamp','desc');
        }


        if($data->count())
        {
            $storeAry       = b_factory::getSelect();

            if($isGroupBy)
            {
                $data = $data->get();
                foreach ($data as $key => $value)
                {
                    $tmp = [];
                    $tmp['door_memo']           = $value->door_memo;
                    $tmp['err_code']            = $value->err_code;
                    $tmp['amt']                 = $value->amt;
                    $tmp['store']               = isset($storeAry[$value->b_factory_id])? $storeAry[$value->b_factory_id] : '';
                    $ret[] = (object)$tmp;
                }
            } else {
                $localAry       = b_factory_a::getSelect();
                $projectlAry    = e_project::getSelect();
                $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
                $doorResultAry  = SHCSLib::getCode('DOOR_INOUT_RESULT');

                $data = $data->get();
                foreach ($data as $key => $value)
                {
                    $memo1 = ($value->door_result != 'Y')? $value->door_memo : '';

                    $tmp = [];
                    $tmp['id']                = $value->id;
                    $tmp['name']              = $value->name;
                    $tmp['b_cust_id']         = $value->b_cust_id;
                    $tmp['job_kind']          = $value->job_kind;
                    $tmp['unit_name']         = $value->unit_name;
                    $tmp['door_type']         = $value->door_type;
                    $tmp['door_type']         = $value->door_type;
                    $tmp['img_path']          = $value->img_path;
                    $tmp['wp_work_id']        = $value->wp_work_id;
                    $tmp['door_stamp']        = $value->door_stamp;
                    $tmp['img1']              = SHCSLib::toImgBase64String('user',$value->b_cust_id,150);
                    $tmp['img2']              = SHCSLib::toImgBase64String('door_user',$value->id,150);
                    $tmp['store']             = isset($storeAry[$value->b_factory_id])? $storeAry[$value->b_factory_id] : '';
                    $tmp['local']             = isset($localAry[$value->b_factory_a_id])? $localAry[$value->b_factory_a_id] : '';
                    $tmp['project']           = isset($projectlAry[$value->e_project_id])? $projectlAry[$value->e_project_id] : '';
                    $tmp['door_type_name']    = isset($doorTypeAry[$value->door_type])? $doorTypeAry[$value->door_type] : '';
                    $tmp['isOnline_name']     = ($value->isOnline == 'N')? ('('.Lang::get('sys_base.base_40205').')') : '';
                    $tmp['door_result_name']  = isset($doorResultAry[$value->door_result])? $doorResultAry[$value->door_result].$memo1 : '';

                    $ret[] = (object)$tmp;
                }
            }
            $ret = (object)$ret;
        }
//        dd(count($ret),$ret);
        return [count($ret),$ret];
    }

    /**
     * 搜尋報表資料:未離場
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getDoorDayErr2Rept($search = [0,0,0,'',''],$isGroupBy = 0)
    {
        list($b_factory_id,$b_supply_id,$b_factory_a_id,$sdate,$edate) = $search;
        if(!$sdate) $sdate = date('Y-m-d');
        $ret = [];

        $data = rept_doorinout_t::where('door_type',1);
        //承攬商
        if($b_supply_id)
        {
            $data = $data->where('b_supply_id',$b_supply_id);
        }
        //廠區
        if($b_factory_id)
        {
            $data = $data->where('b_factory_id',$b_factory_id);
        }
        //廠區-門口
        if($b_factory_a_id)
        {
            $data = $data->where('b_factory_a_id',$b_factory_a_id);
        }
        //日期
        if($sdate && !$edate)
        {
            $data = $data->where('door_date',$sdate);
        }

        if($sdate && $edate)
        {
            $data = $data->where('door_date','<=',$sdate)->where('edate','>=',$edate);
        }

        if($isGroupBy)
        {
            $data = $data->select('id','b_factory_id','b_supply_id');
            $data = $data->groupby('b_supply_id')->selectRaw('COUNT(id) as amt')->orderby('b_supply_id');
        } else {
            $data = $data->select('id','b_cust_id','name','e_project_id','b_supply_id','door_stamp',
                'b_factory_id','b_factory_a_id','wp_work_id','log_door_inout_id');
            $data = $data->orderby('door_stamp','desc');
        }


        if($data->count())
        {
            $storeAry       = b_factory::getSelect();
            $supplyAry      = b_supply::getSelect();

            if($isGroupBy)
            {
                $data = $data->get();
                foreach ($data as $key => $value)
                {
                    $tmp = [];
                    $tmp['amt']                 = $value->amt;
                    $tmp['b_supply_id']         = $value->b_supply_id;
                    $tmp['store']               = isset($storeAry[$value->b_factory_id])? $storeAry[$value->b_factory_id] : '';
                    $tmp['supply']              = isset($supplyAry[$value->b_supply_id])? $supplyAry[$value->b_supply_id] : '';
                    $ret[] = (object)$tmp;
                }
            } else {
                $localAry       = b_factory_a::getSelect();
                $projectlAry    = e_project::getSelect();

                $data = $data->get();
                foreach ($data as $key => $value)
                {
                    $memo1 = ($value->door_result != 'Y')? $value->door_memo : '';

                    $tmp = [];
                    $tmp['id']                = $value->id;
                    $tmp['name']              = $value->name;
                    $tmp['b_cust_id']         = $value->b_cust_id;
                    $tmp['job_kind']          = $value->job_kind;
                    $tmp['unit_name']         = $value->unit_name;
                    $tmp['door_type']         = $value->door_type;
                    $tmp['door_type']         = $value->door_type;
                    $tmp['wp_work_id']        = $value->wp_work_id;
                    $tmp['door_stamp']        = $value->door_stamp;
                    $tmp['supply']              = isset($supplyAry[$value->b_supply_id])? $supplyAry[$value->b_supply_id] : '';
                    $tmp['store']             = isset($storeAry[$value->b_factory_id])? $storeAry[$value->b_factory_id] : '';
                    $tmp['local']             = isset($localAry[$value->b_factory_a_id])? $localAry[$value->b_factory_a_id] : '';
                    $tmp['project']           = isset($projectlAry[$value->e_project_id])? $projectlAry[$value->e_project_id] : '';

                    $ret[] = (object)$tmp;
                }
            }
            $ret = (object)$ret;
        }
//        dd(count($ret),$ret);
        return [count($ret),$ret];
    }

    /**
     * 搜尋報表資料:Alive Log
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getDoorDayAlive1Rept($reader = '')
    {
        $today      = date('Y-m-d');
        $yesterday  = SHCSLib::addDay(-1);
        $ret = $logDataAry = $logDataAry1 = $logDataAry2 = [];

        $data = \DB::table('log_door_alive')->selectRaw('id,b_factory_id,b_factory_d_id,reader,ip,memo1,memo2,convert(varchar, created_at, 120) as alive_stamp')->
            where('sdate','>=',$yesterday)->where('sdate','<=',$today);
        if($reader)
        {
            $data = $data->where('reader',$reader)->orderby('id','desc')->offset(0)->limit(1000);
        }
        if($data->count())
        {
            $storeAry = b_factory::getSelect(0);
            $doorAry  = b_factory_d::getSelect(0,0);
            foreach ($data->get() as $val)
            {
                $tmp = [];
                $tmp['id']          = $val->id;
                $tmp['store']       = isset($storeAry[$val->b_factory_id])? $storeAry[$val->b_factory_id] : '';
                $tmp['door']        = isset($doorAry[$val->b_factory_d_id])? $doorAry[$val->b_factory_d_id] : '';
                $tmp['reader']      = $val->reader;
                $tmp['ip']          = $val->ip;
                $tmp['memo']        = $val->memo1;
                $tmp['alive_stamp'] = $val->alive_stamp;
                $tmp['isLive']      = ((time() - strtotime($val->alive_stamp)) > 900)? 'N' : 'Y';
                if($reader)
                {
                    $ret[] = $tmp;
                } else {
                    $logDataAry[$val->id] = $tmp;
                }
            }
            if(!$reader)
            {
                $data2 = \DB::table('log_door_alive')->selectRaw('MAX(id) as id,reader')->where('sdate',$today)->
                groupby('reader');

                if($data2->count())
                {
                    foreach ($data2->get() as $val2)
                    {
                        $aliveData = isset($logDataAry[$val2->id])? $logDataAry[$val2->id] : [];
                        if(count($aliveData))
                        {
                            $door = isset($aliveData['door'])? $aliveData['door'] : '';
                            //Door
                            if($door)
                            {
                                if(isset($logDataAry1[$door]))
                                {
                                    $last_id = $logDataAry1[$door]['id'];
                                    if($last_id < $val2->id)
                                    {
                                        $logDataAry1[$door] = $aliveData;
                                    }
                                } else {
                                    $logDataAry1[$door] = $aliveData;
                                }

                            }
                            //reader
                            if($val2->reader)
                            {
                                $logDataAry2[$door][$val2->reader] = $aliveData;
                            }
                        }
                    }
                    //組合
                    if(count($logDataAry1))
                    {
                        foreach ($logDataAry1 as $door_id => $val3)
                        {
                            //$ret[] = $val3;
                            if(isset($logDataAry2[$door_id]))
                            {
                                foreach ($logDataAry2[$door_id] as $val4)
                                {
                                    $ret[] = $val4;
                                }
                            }
                        }
                    }
                }
            }

            //dd($logDataAry1,$logDataAry2,$ret);
        }



        return $ret;
    }
}
