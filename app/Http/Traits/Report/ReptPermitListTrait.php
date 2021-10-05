<?php

namespace App\Http\Traits\Report;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_log_door_today;
use App\Model\View\view_wp_work;
use App\Model\WorkPermit\wp_check_kind;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_check_record1;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_worker;
use App\Model\WorkPermit\wp_work_workitem;
use Storage;
use DB;
use Lang;
use Session;

/**
 * 報表：工作許可證列表-統計
 *
 */
trait ReptPermitListTrait
{

    /**
     * 搜尋報表資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getPermitWorkDayRept($search = [0,0,0,'',''],$aproc = [],$isGroup = 0)
    {
        list($b_supply_id,$b_factory_a_id,$danger,$sdate,$edate) = $search;
        $ret = [];
        $data = wp_work::where('wp_work.isClose','N');
        //承攬商
        if($b_supply_id)
        {
            $data = $data->where('wp_work.b_supply_id',$b_supply_id);
        }
        //危險等級
        if($danger)
        {
            $data = $data->where('wp_work.wp_permit_danger',$danger);
        }
        //施工地點
        if($b_factory_a_id)
        {
            $data = $data->where('wp_work.b_factory_a_id',$b_factory_a_id);
        }
        //日期
        if($sdate)
        {
            $data = $data->where('wp_work.sdate','>=',$sdate);
        }
        if($edate)
        {
            $data = $data->where('wp_work.edate','<=',$edate);
        }
        //
        if($aproc)
        {
            $data = $data->whereIn('wp_work.aproc',$aproc);
        }
        //
        if($isGroup)
        {
            $data = $data->join('sys_code as s','s.status_key','=','wp_work.aproc')->where('s.status_code','PERMIT_APROC');
            $data = $data->groupby('wp_work.sdate')->groupby('wp_work.aproc');
            $data = $data->selectRaw('COUNT(wp_work.id) as amt, wp_work.aproc, wp_work.sdate');
            $data = $data->orderby('wp_work.sdate');
            $data = $data->orderby('wp_work.aproc');
        } else {
            $data = $data->orderby('permit_no')->orderby('wp_permit_danger');
        }

        if($data->count())
        {
            $data = $data->get();
            $aprocAry = SHCSLib::getCode('PERMIT_APROC');
            foreach( $data as $key => $value)
            {
                $tmp = [];
                if($isGroup)
                {
                    $tmp['amt'] = $value->amt;
                    $tmp['aproc'] = $value->aproc;
                    $tmp['aproc_name'] = isset($aprocAry[$value->aproc])? $aprocAry[$value->aproc] : '';
                    $tmp['sdate'] = $value->sdate;
                } else {
                    $tmp['id'] = $value->id;
                    $tmp['permit_no'] = $value->permit_no;
                    $tmp['apply_stamp'] = $value->apply_stamp;
                    $tmp['apply_user'] = User::getName($value->apply_user);
                    $tmp['danger'] = $value->wp_permit_danger;
                    $tmp['local'] = b_factory_a::getName($value->b_factory_a_id).'('.$value->b_factory_memo.')';
                    $tmp['supply'] = b_supply::getSubName($value->b_supply_id);
                    $tmp['dept1'] = be_dept::getName($value->be_dept_id1);
                    $tmp['dept2'] = be_dept::getName($value->be_dept_id2);
                    $tmp['project_no'] = e_project::getNo($value->e_project_id);
                    $tmp['sdate'] = $value->sdate;
                    $tmp['work'] = $value->wp_permit_workitem_memo;
                    $finisher = wp_work_process::getChargeUser($value->id,9);
                    $tmp['finisher'] = User::getName($finisher);
                }



                $ret[] = (object)$tmp;
            }
        }
//        dd($ret);
        return [count($ret),$ret];
    }

    /**
     * 搜尋報表資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getPermitWorkTodayReptSample($search = [0,0,0,'',''],$aproc = [],$isGroup = 0)
    {
        list($b_supply_id,$b_factory_a_id,$danger,$sdate,$edate) = $search;
        $today          = date('Y-m-d');
        $nowStamp       = time();
        $yesterday      = SHCSLib::addDay(-1);
        $yesterdaySTime = sys_param::getParam('REPORT_DOOR_YESTERDAY_STIME','18:00:00');
        $yesterdayETime = sys_param::getParam('REPORT_DOOR_YESTERDAY_ETIME','06:00:00');
        $todaySTime     = sys_param::getParam('REPORT_DOOR_TODAY_STIME','09:00:00');
        $y_sdate  = date('Y-m-d H:i:s',strtotime($yesterday.' '.$yesterdaySTime));
        $y_edate  = date('Y-m-d H:i:s',strtotime($today.' '.$yesterdayETime));
        $t_sdate  = date('Y-m-d H:i:s',strtotime($today.' '.$todaySTime));
        $t_edate  = date('Y-m-d H:i:s',strtotime($today.' '.$yesterdaySTime));
        $t_Stamp1 = strtotime($t_sdate);
        $t_Stamp2 = strtotime($t_edate);

        $ret = [];
        $data = wp_work::where('wp_work.isClose','N');
        //早上九點前(昨日晚班&今日早班)
        if($t_Stamp1 >= $nowStamp)
        {
            $data = $data->where(function ($query) use ($yesterday,$today) {
                $query->where(function ($query) use ($yesterday) {
                    $query->where('wp_work.sdate',$yesterday)->
                    where('wp_work.wp_permit_shift_id',2);
                })->orWhere(function ($query) use ($today) {
                    $query->where('wp_work.sdate',$today)->
                    where('wp_work.wp_permit_shift_id',1);
                });
            });
        }
        //下午六點後(今日晚班)
        elseif($t_Stamp2 <= $nowStamp)
        {
            $data = $data->where('wp_work.sdate',$today)->
            where('wp_work.wp_permit_shift_id',2);
        }
        else {
            //今日早上六點以後(今日早班)
            $data = $data->where('wp_work.sdate',$today)->
            where('wp_work.wp_permit_shift_id',1);
        }
        //承攬商
        if($b_supply_id)
        {
            $data = $data->where('wp_work.b_supply_id',$b_supply_id);
        }
        //危險等級
        if($danger)
        {
            $data = $data->where('wp_work.wp_permit_danger',$danger);
        }
        //施工地點
        if($b_factory_a_id)
        {
            $data = $data->where('wp_work.b_factory_a_id',$b_factory_a_id);
        }

        //
        if($aproc)
        {
            $data = $data->whereIn('wp_work.aproc',$aproc);
        }
        //
        if($isGroup)
        {
            $data = $data->join('sys_code as s','s.status_key','=','wp_work.aproc')->where('s.status_code','PERMIT_APROC');
            $data = $data->groupby('wp_work.sdate')->groupby('wp_work.aproc');
            $data = $data->selectRaw('COUNT(wp_work.id) as amt, wp_work.aproc, wp_work.sdate');
            $data = $data->orderby('wp_work.sdate');
            $data = $data->orderby('wp_work.aproc');
        } else {
            $data = $data->orderby('permit_no')->orderby('wp_permit_danger');
        }

        if($data->count())
        {
            $data = $data->get();
            $aprocAry = SHCSLib::getCode('PERMIT_APROC');
            foreach( $data as $key => $value)
            {
                $tmp = [];
                if($isGroup)
                {
                    $tmp['amt'] = $value->amt;
                    $tmp['aproc'] = $value->aproc;
                    $tmp['aproc_name'] = isset($aprocAry[$value->aproc])? $aprocAry[$value->aproc] : '';
                    $tmp['sdate'] = $value->sdate;
                } else {
                    $tmp['id'] = $value->id;
                    $tmp['permit_no'] = $value->permit_no;
                    $tmp['apply_stamp'] = $value->apply_stamp;
                    $tmp['apply_user'] = User::getName($value->apply_user);
                    $tmp['danger'] = $value->wp_permit_danger;
                    $tmp['local'] = b_factory_a::getName($value->b_factory_a_id).'('.$value->b_factory_memo.')';
                    $tmp['supply'] = b_supply::getSubName($value->b_supply_id);
                    $tmp['dept1'] = be_dept::getName($value->be_dept_id1);
                    $tmp['dept2'] = be_dept::getName($value->be_dept_id2);
                    $tmp['project_no'] = e_project::getNo($value->e_project_id);
                    $tmp['sdate'] = $value->sdate;
                    $tmp['work'] = $value->wp_permit_workitem_memo;
                    $finisher = wp_work_process::getChargeUser($value->id,9);
                    $tmp['finisher'] = User::getName($finisher);
                }



                $ret[] = (object)$tmp;
            }
        }
//        dd($ret);
        return [count($ret),$ret];
    }

    /**
     * 搜尋報表資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getPermitWorkTodayRept1($b_factory_id,$b_factory_a_id,$b_supply_id,$danger,$isGroup = 'N')
    {
        $ret = [];
        $today = date('Y-m-d');
        $data = view_wp_work::where('view_wp_work.isClose','N')->
        join('b_factory as f','f.id','=','view_wp_work.b_factory_id')->
        join('b_factory_a as fa','fa.id','=','view_wp_work.b_factory_a_id')->
        join('b_supply as s','s.id','=','view_wp_work.b_supply_id')->
        join('e_project as e','e.id','=','view_wp_work.e_project_id')->
        whereNotIn('view_wp_work.aproc',['A','B'])
        ;
        //承攬商
        if($b_supply_id)
        {
            $data = $data->where('view_wp_work.b_supply_id',$b_supply_id);
        }
        //危險等級
        if($danger)
        {
            $data = $data->where('view_wp_work.wp_permit_danger',$danger);
        }
        //廠區
        if($b_factory_id)
        {
            $data = $data->where('view_wp_work.b_factory_id',$b_factory_id);
        }
        //施工地點
        if($b_factory_a_id)
        {
            $data = $data->where('view_wp_work.b_factory_a_id',$b_factory_a_id);
        }

        //
        if($isGroup == 'Y')
        {
            $data = $data->groupby('view_wp_work.b_factory_id')->groupby('f.name')->groupby('view_wp_work.b_supply_id')->groupby('s.sub_name');
            $data = $data->selectRaw('COUNT(view_wp_work.id) as amt, view_wp_work.b_factory_id, f.name as store, view_wp_work.b_supply_id, s.sub_name as supply');

        } else {
            $data = $data->select('view_wp_work.*','e.project_no','e.name as project','fa.name as local','s.sub_name as supply');
            $data = $data->orderby('view_wp_work.permit_no')->orderby('view_wp_work.wp_permit_danger');
        }

        if($data->count())
        {
            $data = $data->get();
            foreach( $data as $key => $value)
            {
                $tmp = [];
                if($isGroup == 'Y')
                {
                    $tmp['store_id']    = $value->b_factory_id;
                    $tmp['store_name']  = $value->store;
                    $tmp['supply_id']   = $value->b_supply_id;
                    $tmp['supply_name'] = $value->supply;
                    $tmp['in_amt']      = $this->getDoorMenInOutTodayFactoryData($today,$value->b_factory_id,0,$value->b_supply_id,1,1,'Y');
                    $tmp['work_amt']    = $value->amt;
                } else {
                    $tmp['supply_name']  = $value->supply;
                    $tmp['permit_no']    = $value->permit_no;
                    $tmp['project_no']   = $value->project_no;
                    $tmp['project_name'] = $value->project;
                    $tmp['local']        = $value->local;
                    $tmp['worker_amt']   = wp_work_worker::getAmt($value->id,'');

                }

                $ret[] = (object)$tmp;
            }
        }
//        dd($ret);
        return $ret;
    }

    /**
     * 搜尋總廠資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getPermitWorkTodayRept2($b_factory_id)
    {
        $ret          = $ret1 = $ret2 = [];
        $now          = time();
        $ExtensionAmt = $overAmt = 0;
        $defKindAry   = wp_permit_kind::getReptSelect();

        $ret1['A']['namr'] = 'A';
        $ret1['A']['amt']  = 0;
        $ret1['A']['kind'] = $defKindAry;
        $ret1['B']['namr'] = 'B';
        $ret1['B']['amt']  = 0;
        $ret1['B']['kind'] = $defKindAry;
        $ret1['C']['namr'] = 'C';
        $ret1['C']['amt']  = 0;
        $ret1['C']['kind'] = $defKindAry;


        $data = view_wp_work::where('view_wp_work.isClose','N')->
        where('b_factory_id',$b_factory_id)->whereNotIn('aproc',['B'])->
        select('view_wp_work.id','view_wp_work.aproc','view_wp_work.wp_permit_danger'
            ,'view_wp_work.eta_time','view_wp_work.wp_work_rp_extension_id')
        ;

        if($data->count())
        {
            $data = $data->get();
            foreach( $data as $value)
            {
                $aproc       = $value->aproc;
                $danger      = $value->wp_permit_danger;
                $etaStamp    = is_null($value->eta_time)? 0 : strtotime($value->eta_time);
                $isExtension = $value->wp_work_rp_extension_id;

                //進度
                if(!isset($ret2[$aproc])) $ret2[$aproc]['amt'] = 0;
                $ret2[$aproc]['amt']++;

                if($aproc != 'A')
                {
                    //危險等級
                    if(!isset($ret1[$danger])) $ret1[$danger]['amt'] = 0;
                    $ret1[$danger]['amt']++;
                    //工作項目
                    $workitemAry = wp_work_workitem::getApiAllSelect($value->id);
                    if(!isset($ret1[$danger]['kind'])) $ret1[$danger]['kind'] = [];
                    if(count($workitemAry))
                    {
                        foreach ($workitemAry as $val)
                        {
                            $kid   = isset($val['id'])? $val['id'] : 0;
                            $item  = isset($val['item'])? $val['item'] : [];
                            if($kid && isset($ret1[$danger]['kind'][$kid]))
                            {
                                $ret1[$danger]['kind'][$kid]['amt']++;

                                foreach ($item as $val2)
                                {
                                    $iid = isset($val2['id'])? $val2['id'] : '';
                                    $ret1[$danger]['kind'][$kid]['item'][$iid]['amt']++;
                                }
                            }
                        }
                    }
                }

                if(in_array($aproc,['P','R','O']) && $now > $etaStamp) $overAmt++;
                if($isExtension) $ExtensionAmt++;
            }
        }

        if(count($ret1))
        {
            foreach ($ret1 as $key1 => $val1)
            {
                foreach ($val1['kind'] as $key2 => $val2)
                {
                    sort($ret1[$key1]['kind'][$key2]['item']);
                }
                sort($ret1[$key1]['kind']);
            }
            sort($ret1);
            $ret['danger'] = $ret1;
        }
        if(count($ret2))
        {
            foreach ($ret2 as $aproc => $val)
            {
                $tmp = [];
                $tmp['name'] = $aproc;
                $tmp['amt']  = isset($val['amt'])? $val['amt'] : 0;
                $ret['aproc'][] = $tmp;
            }
        }
        $ret['status1'] = ['amt'=>$ExtensionAmt];
        $ret['status2'] = ['amt'=>$overAmt];

//        dd($ret);
        return $ret;
    }
}
