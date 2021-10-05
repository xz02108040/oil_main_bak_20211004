<?php

namespace App\Http\Traits\Report;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_factory;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_wp_work;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_check_record1;
use App\Model\WorkPermit\wp_work_check_record2;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_worker;
use App\Model\WorkPermit\wp_work_workitem;
use Storage;
use DB;
use Lang;

/**
 * 報表：當日 廠區儀表板
 *
 */
trait ReptPermit2Trait
{
    /**
     * 產生 當日 廠區儀表板html
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genRermit2Html($store = 0)
    {
        $html   = '';
        $reptAry  = $this->genPermitReptToday($store);

        if(count($reptAry))
        {
            foreach ($reptAry as $no => $val)
            {
                $title      = $val['name'];
                $sub_title  = $val['sub'];
                $cont       = $val['amt'];
                $url        = $val['url'];
                $html .= HtmlLib::genHttcPermitReportDiv_1024($no,$title,$sub_title,$cont,$url);
            }
        }

        return $html;
    }

    /**
     * 產生 當日 廠區儀表板 資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genPermitReptToday($store = 0,$local_id = 0,$dept_id = 0,$supply = 0)
    {
        $ret = $reptAry= [];
        $today = date('Y-m-d');
        $store = ($store > 0)? $store : sys_param::getParam('REPORT_DEFAULT_STORE',6);

        //1. 先產生 六個區塊
        $arpcoToRowNoAry = ['A'=>1,'W'=>2,'K1'=>3,'K2'=>4,'K3'=>5,'O'=>6];
        $arpcoToSubTitleNoAry = ['A'=>'base_40320','W'=>'base_40321','K1'=>'base_40322','K2'=>'base_40323','K3'=>'base_40324','O'=>'base_40325'];
        $aprocNameAry    = SHCSLib::getCode('PERMIT_APROC');
        $aprocNameAry['K1'] = Lang::get('sys_base.base_40326');
        $aprocNameAry['K2'] = Lang::get('sys_base.base_40327');
        $aprocNameAry['K3'] = Lang::get('sys_base.base_40328');
        foreach ($arpcoToRowNoAry as $key => $rowno)
        {
            $name       = isset($aprocNameAry[$key])? $aprocNameAry[$key] : '';
            $subcode    = isset($arpcoToSubTitleNoAry[$key])? $arpcoToSubTitleNoAry[$key] : '';
            $reptAry[$rowno]['no']   = $rowno;
            $reptAry[$rowno]['name'] = $name;
            $reptAry[$rowno]['sub']  = Lang::get('sys_base.'.$subcode);
            $reptAry[$rowno]['url']  = '';
            $reptAry[$rowno]['amt']  = 0;
        }


        //2. 找出今日開立工作許可證
        //2019-11-05 KEN要求排除 施工階段 改用主管簽核取代
        $data = view_wp_work::where('b_factory_id',$store)->where('isClose','N')->
                whereNotIn('aproc',['B','C','R'])->selectRaw('aproc,COUNT(id) as amt')->groupby('aproc');
        if($local_id)
        {
            if(is_array($local_id) && count($local_id))
            {
                $data = $data->whereIn('b_factory_a_id',$local_id);
            } else {
                $data = $data->where('b_factory_a_id',$local_id);
            }
        }
        if($dept_id)
        {
            if(is_array($dept_id) && count($dept_id))
            {
                $data = $data->whereIn('be_dept_id1',$dept_id);
            } else {
                $data = $data->where('be_dept_id1',$dept_id);
            }
        }
        if($supply)
        {
            $data = $data->where('b_supply_id',$supply);
        }
        if($data->count())
        {
            $data = $data->get();
//            dd($data);
            foreach ($data as $val)
            {
                //2019-10-18 檢點階段 分成兩個階段
                //2019-11-05 檢點階段 在分出子階段
                if($val->aproc == 'P')
                {
                    $tmp = wp_work_list::getReptPermitProcessAmt($store,$today,$dept_id);
                    foreach ($tmp as $k => $v)
                    {
                        $rowno = isset($arpcoToRowNoAry[$k])? $arpcoToRowNoAry[$k] : 0;
                        if(isset($reptAry[$rowno])) $reptAry[$rowno]['amt']  = $v;
                    }
                } else {
                    $rowno = isset($arpcoToRowNoAry[$val->aproc])? $arpcoToRowNoAry[$val->aproc] : 0;
                    if(isset($reptAry[$rowno])) $reptAry[$rowno]['amt']  = $val->amt;
                }
            }
        }
        foreach ($reptAry as $val)
        {
            $ret[] = $val;
        }

        return $ret;
    }

    /**
     * 產生 當日 廠區儀表板 資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genPermitReptToday2($store = 0,$local_id = 0,$supply = 0,$searchId = 0)
    {
        $ret = $reptAry= [];
        //$store = ($store > 0)? $store : sys_param::getParam('REPORT_DEFAULT_STORE',6);

        //1. 先產生 六個區塊
//        $arpcoToRowNoAry = ['A'=>1,'P'=>2,'K'=>3,'R'=>4,'O'=>5,'F'=>6];
        if(!$searchId)
        {
            $arpcoToRowNoAry = ['A'=>1,'B'=>2,'C'=>3,'D'=>4,'E'=>5,'F'=>6];
            $arpcoToSubTitleNoAry = ['A'=>'base_40329','B'=>'base_40330','C'=>'base_40331','D'=>'base_40332','E'=>'base_40333','F'=>'base_40334'];
            foreach ($arpcoToRowNoAry as $key => $rowno)
            {
                $subcode    = isset($arpcoToSubTitleNoAry[$key])? $arpcoToSubTitleNoAry[$key] : '';
                $reptAry[$rowno]['no']   = $rowno;
                $reptAry[$rowno]['name'] = Lang::get('sys_base.'.$subcode);
                $reptAry[$rowno]['sub']  = '';
                $reptAry[$rowno]['url']  = '';
                $reptAry[$rowno]['amt']  = 0;
            }
        }



        //2. 找出今日開立工作許可證
        $data = view_wp_work::
                join('e_project as p','p.id','=','view_wp_work.e_project_id')->
                join('b_supply as s','s.id','=','view_wp_work.b_supply_id')->
                join('b_factory as f','f.id','=','view_wp_work.b_factory_id')->
                join('b_factory_a as fa','fa.id','=','view_wp_work.b_factory_a_id')->
                join('b_factory_b as fb','fb.id','=','view_wp_work.b_factory_b_id')->
                join('be_dept as d','d.id','=','view_wp_work.be_dept_id1')->
                where('view_wp_work.isClose','N')->where('view_wp_work.aproc','R');
        if($store)
        {
            $data = $data->where('view_wp_work.b_factory_id',$store);
        }
        if($local_id)
        {
            if(is_array($local_id) && count($local_id))
            {
                $data = $data->whereIn('view_wp_work.b_factory_a_id',$local_id);
            } else {
                $data = $data->where('view_wp_work.b_factory_a_id',$local_id);
            }
        }
        if($supply)
        {
            $data = $data->where('view_wp_work.b_supply_id',$supply);
        }
        if($searchId)
        {
            $data = $data->select('p.name as project','s.sub_name as supply','f.name as store',
                'fa.name as local','fb.name as area','d.name as dept1','permit_no');
        }
        if($data->count())
        {
            $data = $data->get();
//            dd($data);
            foreach ($data as $val)
            {
                $workKind = wp_work_workitem::getKind($val->id);
                $etaStamp = strtotime($val->eta_time);
                $nowStamp = time();
                $isApplyOvertime = $val->isApplyOvertime;

                if($searchId)
                {
                    $tmp = [];
                    $tmp['project']     = $val->project;
                    $tmp['supply']      = $val->supply;
                    $tmp['store']       = $val->store;
                    $tmp['local']       = $val->local;
                    $tmp['area']        = $val->area;
                    $tmp['dept1']       = $val->dept1;
                    $tmp['permit_no']   = $val->permit_no;
                }

                //1.A級
                if($val->wp_permit_danger == 'A' && isset($reptAry[1]))
                {
                    if($searchId == 1)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[1]['amt']++;
                    }
                }
                //2.動火
                if(isset($reptAry[2]) && isset($workKind[2]))
                {
                    if($searchId == 2)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[2]['amt']++;
                    }
                }
                //3.非動火
                if(isset($reptAry[3]) && isset($workKind[1]))
                {
                    if($searchId == 3)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[3]['amt']++;
                    }
                }
                //4.局限空間
                if(isset($reptAry[4]) && isset($workKind[3]))
                {
                    if($searchId == 4)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[4]['amt']++;
                    }
                }
                //5.逾期收工
                if(isset($reptAry[5]) && $etaStamp < $nowStamp)
                {
                    if($searchId == 5)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[5]['amt']++;
                    }
                }
                //6.延長申請工作許可證
                if(isset($reptAry[6]) && $isApplyOvertime == 'Y')
                {
                    if($searchId == 6)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[6]['amt']++;
                    }
                }

            }
        }
        foreach ($reptAry as $val)
        {
            $ret[] = $val;
        }

        return $ret;
    }

    /**
     * 產生 當日 廠區儀表板 資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genPermitReptToday3($dept = 0,$searchId = 0)
    {
        $ret = $reptAry= [];

        //1. 先產生 六個區塊
        if(!$searchId)
        {
            $arpcoToRowNoAry = ['A'=>1,'B'=>2,'C'=>3,'D'=>4,'E'=>5,'F'=>6,'G'=>7];
            $arpcoToSubTitleNoAry = ['A'=>'base_40329','B'=>'base_40330','C'=>'base_40331','D'=>'base_40332','E'=>'base_40335','F'=>'base_40337','G'=>'base_40336'];
            foreach ($arpcoToRowNoAry as $key => $rowno)
            {
                $subcode    = isset($arpcoToSubTitleNoAry[$key])? $arpcoToSubTitleNoAry[$key] : '';
                $reptAry[$rowno]['no']   = $rowno;
                $reptAry[$rowno]['name'] = Lang::get('sys_base.'.$subcode);
                $reptAry[$rowno]['sub']  = '';
                $reptAry[$rowno]['url']  = '';
                $reptAry[$rowno]['amt']  = 0;
            }
        }



        //2. 找出今日開立工作許可證
        $data = view_wp_work::
        join('e_project as p','p.id','=','view_wp_work.e_project_id')->
        join('b_supply as s','s.id','=','view_wp_work.b_supply_id')->
        join('b_factory as f','f.id','=','view_wp_work.b_factory_id')->
        join('b_factory_a as fa','fa.id','=','view_wp_work.b_factory_a_id')->
        join('b_factory_b as fb','fb.id','=','view_wp_work.b_factory_b_id')->
        join('be_dept as d','d.id','=','view_wp_work.be_dept_id1')->
        where('view_wp_work.isClose','N')->
        where('view_wp_work.aproc','R');
        if(is_array($dept) && count($dept))
        {
            $data = $data->whereIn('view_wp_work.be_dept_id1',$dept);
        } else {
            $data = $data->where('view_wp_work.be_dept_id1',$dept);
        }
        if($searchId)
        {
            $data = $data->select('p.name as project','s.sub_name as supply','f.name as store',
                'fa.name as local','fb.name as area','d.name as dept1','permit_no');
        }
        if($data->count())
        {
            $data = $data->get();
//            dd($data);
            foreach ($data as $val)
            {
                $workKind = wp_work_workitem::getKind($val->id);
                $etaStamp = strtotime($val->eta_time);
                $nowStamp = time();
                $isApplyOvertime = $val->isApplyOvertime;
                $shift           = $val->wp_permit_shift_id;

                if($searchId)
                {
                    $tmp = [];
                    $tmp['project']     = $val->project;
                    $tmp['supply']      = $val->supply;
                    $tmp['store']       = $val->store;
                    $tmp['local']       = $val->local;
                    $tmp['area']        = $val->area;
                    $tmp['dept1']       = $val->dept1;
                    $tmp['permit_no']   = $val->permit_no;
                }

                //1.A級
                if($val->wp_permit_danger == 'A' && isset($reptAry[1]))
                {
                    if($searchId == 1)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[1]['amt']++;
                    }
                }
                //2.動火
                if(isset($reptAry[2]) && isset($workKind[2]))
                {
                    if($searchId == 2)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[2]['amt']++;
                    }
                }
                //3.非動火
                if(isset($reptAry[3]) && isset($workKind[1]))
                {
                    if($searchId == 3)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[3]['amt']++;
                    }
                }
                //4.局限空間
                if(isset($reptAry[4]) && isset($workKind[3]))
                {
                    if($searchId == 4)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[4]['amt']++;
                    }
                }
                //5.未收工
                if(isset($reptAry[5]))
                {
                    if($searchId == 5)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[5]['amt']++;
                    }
                }
                //6.延長申請工作許可證
                if(isset($reptAry[6]) && ($isApplyOvertime == 'Y' || $shift == 2))
                {
                    if($searchId == 6)
                    {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[6]['amt']++;
                    }
                }

            }
        }
        foreach ($reptAry as $val)
        {
            $ret[] = $val;
        }

        return $ret;
    }

    /**
     * 產生 當日 廠區儀表板 資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genPermitReptToday4($b_factory_b_id = 0,$searchId = 0)
    {
        $ret = $reptAry = [];

        //1. 先產生 六個區塊
        if (!$searchId) {
            $arpcoToRowNoAry = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 7];
            $arpcoToSubTitleNoAry = ['A' => 'base_40329', 'B' => 'base_40338', 'C' => 'base_40339', 'D' => 'base_40330', 'E' => 'base_40331', 'F' => 'base_40332', 'G' => 'base_40340', 'H' => 'base_40336'];
            foreach ($arpcoToRowNoAry as $key => $rowno) {
                $subcode = isset($arpcoToSubTitleNoAry[$key]) ? $arpcoToSubTitleNoAry[$key] : '';
                $reptAry[$rowno]['no'] = $rowno;
                $reptAry[$rowno]['name'] = Lang::get('sys_base.' . $subcode);
                $reptAry[$rowno]['sub'] = '';
                $reptAry[$rowno]['url'] = '';
                $reptAry[$rowno]['amt'] = 0;
                $reptAry[$rowno]['men'] = 0;
            }
        }


        //2. 找出今日開立工作許可證
        $data = view_wp_work::
        join('e_project as p', 'p.id', '=', 'view_wp_work.e_project_id')->
        join('b_supply as s', 's.id', '=', 'view_wp_work.b_supply_id')->
        join('b_factory as f', 'f.id', '=', 'view_wp_work.b_factory_id')->
        join('b_factory_a as fa', 'fa.id', '=', 'view_wp_work.b_factory_a_id')->
        join('b_factory_b as fb', 'fb.id', '=', 'view_wp_work.b_factory_b_id')->
        join('be_dept as d', 'd.id', '=', 'view_wp_work.be_dept_id1')->
        where('view_wp_work.isClose', 'N')->
        where('view_wp_work.aproc', 'R');
        if(is_array($b_factory_b_id) && count($b_factory_b_id))
        {
            $data = $data->whereIn('view_wp_work.b_factory_b_id',$b_factory_b_id);
        } else {
            $data = $data->where('view_wp_work.b_factory_b_id',$b_factory_b_id);
        }
        if ($searchId) {
            $data = $data->select('p.name as project', 's.sub_name as supply', 'f.name as store',
                'fa.name as local', 'fb.name as area', 'd.name as dept1', 'permit_no');
        }
        if ($data->count()) {
            $data = $data->get();
//            dd($data);
            foreach ($data as $val) {
                $workKind = wp_work_workitem::getKind($val->id);
                $etaStamp = strtotime($val->eta_time);
                $nowStamp = time();
                $isApplyOvertime = $val->isApplyOvertime;
                $shift = $val->wp_permit_shift_id;
                //氣體偵測<承攬商>
                $isErr = 0;
                $tmpRecordAry = wp_work_check_record1::getLastRecord($val->id, [1, 3]);
                if ($tmpRecordAry['record_alert1'] || (!$tmpRecordAry['record_alert1'] && !$tmpRecordAry['record_stamp1'])) {
                    $isErr = 1;
                }


                if ($searchId) {
                    $tmp = [];
                    $tmp['project'] = $val->project;
                    $tmp['supply'] = $val->supply;
                    $tmp['store'] = $val->store;
                    $tmp['local'] = $val->local;
                    $tmp['area'] = $val->area;
                    $tmp['dept1'] = $val->dept1;
                    $tmp['permit_no'] = $val->permit_no;
                }

                //1.A級
                if ($val->wp_permit_danger == 'A' && isset($reptAry[1])) {
                    if ($searchId == 1) {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[1]['amt']++;
                    }
                }
                //2.B級
                if ($val->wp_permit_danger == 'B' && isset($reptAry[2])) {
                    if ($searchId == 1) {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[2]['amt']++;
                    }
                }
                //3.C級
                if ($val->wp_permit_danger == 'C' && isset($reptAry[3])) {
                    if ($searchId == 1) {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[3]['amt']++;
                    }
                }
                //4.動火
                if (isset($reptAry[4]) && isset($workKind[2])) {
                    if ($searchId == 4) {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[4]['amt']++;
                    }
                }
                //5.非動火
                if (isset($reptAry[5]) && isset($workKind[1])) {
                    if ($searchId == 5) {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[5]['amt']++;
                    }
                }

                //6.局限空間
                if (isset($reptAry[6]) && isset($workKind[3])) {
                    if ($searchId == 6) {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[6]['amt']++;
                        $reptAry[6]['men'] += wp_work_worker::getAmt($val->id);
                    }
                }

                //7.測氣體異常
                if (isset($reptAry[7]) && $isErr) {
                    if ($searchId == 7) {
                        $reptAry[] = $tmp;
                    } else {
                        $reptAry[7]['amt']++;
                    }
                }
            }

        }

        foreach ($reptAry as $val) {
            $ret[] = $val;
        }
        return $ret;
    }

    /**
     * 產生 當日 廠區儀表板 資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genPermitReptToday5($b_factory_id = 0,$b_factory_a_id = 0)
    {
        $ret = $reptAry = [];

        //2. 找出今日開立工作許可證
        $data = view_wp_work::
        join('e_project as p', 'p.id', '=', 'view_wp_work.e_project_id')->
        join('b_supply as s', 's.id', '=', 'view_wp_work.b_supply_id')->
        join('b_factory as f', 'f.id', '=', 'view_wp_work.b_factory_id')->
        join('b_factory_a as fa', 'fa.id', '=', 'view_wp_work.b_factory_a_id')->
        join('b_factory_b as fb', 'fb.id', '=', 'view_wp_work.b_factory_b_id')->
        join('be_dept as d', 'd.id', '=', 'view_wp_work.be_dept_id1')->
        join('be_dept as d2', 'd2.id', '=', 'view_wp_work.be_dept_id2')->
        where('view_wp_work.isClose', 'N')->where('view_wp_work.aproc', 'R');
        if ($b_factory_id) {
            if(is_array($b_factory_id) && count($b_factory_id))
            {
                $data = $data->whereIn('view_wp_work.b_factory_id',$b_factory_id);
            } else {
                $data = $data->where('view_wp_work.b_factory_id',$b_factory_id);
            }
        }
        if ($b_factory_a_id) {
            if(is_array($b_factory_a_id) && count($b_factory_a_id))
            {
                $data = $data->whereIn('view_wp_work.b_factory_a_id',$b_factory_a_id);
            } else {
                $data = $data->where('view_wp_work.b_factory_a_id',$b_factory_a_id);
            }
        }
        $data = $data->select('p.name as project', 's.sub_name as supply', 'f.name as store',
            'fa.name as local', 'fb.name as area', 'd.name as dept1', 'd2.name as dept2', 'permit_no', 'view_wp_work.id',
            'b_factory_memo','wp_permit_workitem_memo','wp_permit_danger');
        if ($data->count()) {
            $data = $data->get();
//            dd($data);
            foreach ($data as $val) {
                //氣體偵測<承攬商>
                $tmpRecordAry = wp_work_check_record1::getLastRecord($val->id, [1, 3]);
                if ($tmpRecordAry['record_alert1'] || (!$tmpRecordAry['record_alert1'] && !$tmpRecordAry['record_stamp1']) || ($tmpRecordAry['record1_check'] || $tmpRecordAry['record2_check'] || $tmpRecordAry['record3_check'] || $tmpRecordAry['record4_check'])){
                    $tmp = [];
                    $tmp['project'] = $val->project;
                    $tmp['supply'] = $val->supply;
                    $tmp['store'] = $val->store;
                    $tmp['local'] = $val->local;
                    $tmp['area'] = $val->area;
                    $tmp['dept1'] = $val->dept1;
                    $tmp['dept2'] = $val->dept2;
                    $tmp['permit_no'] = $val->permit_no;
                    $tmp['danger']          = $val->wp_permit_danger;
                    $tmp['factory_memo']    = $val->b_factory_memo;
                    $tmp['workitem_memo']   = $val->wp_permit_workitem_memo;
                    $tmp['record_user']     = isset($tmpRecordAry['record_user1'])? $tmpRecordAry['record_user1'] : '';
                    $tmp['record_stamp']    = isset($tmpRecordAry['record_stamp1'])? $tmpRecordAry['record_stamp1'] : '';
                    $tmp['record_alert1']   = isset($tmpRecordAry['record_alert1'])? $tmpRecordAry['record_alert1'] : '';
                    $tmp['record1']         = isset($tmpRecordAry['record1'])? $tmpRecordAry['record1'] : '';
                    $tmp['record2']         = isset($tmpRecordAry['record2'])? $tmpRecordAry['record2'] : '';
                    $tmp['record3']         = isset($tmpRecordAry['record3'])? $tmpRecordAry['record3'] : '';
                    $tmp['record4']         = isset($tmpRecordAry['record4'])? $tmpRecordAry['record4'] : '';
                    $tmp['record5']         = isset($tmpRecordAry['record5'])? $tmpRecordAry['record5'] : '';
                    $tmp['record1_check']   = isset($tmpRecordAry['record1_check'])? $tmpRecordAry['record1_check'] : '';
                    $tmp['record2_check']   = isset($tmpRecordAry['record2_check'])? $tmpRecordAry['record2_check'] : '';
                    $tmp['record3_check']   = isset($tmpRecordAry['record3_check'])? $tmpRecordAry['record3_check'] : '';
                    $tmp['record4_check']   = isset($tmpRecordAry['record4_check'])? $tmpRecordAry['record4_check'] : '';
                    $reptAry[] = $tmp;
                }
            }
        }
        return $reptAry;
    }
    /**
     * 產生 當日 廠區儀表板 資料[假日工單]
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genPermitReptToday6($b_factory_id = 0,$b_factory_a_id = 0)
    {
        $ret = $reptAry = [];
        $today = date('Y-m-d');
        //2. 找出今日開立工作許可證
        $data = wp_work::
        join('e_project as p', 'p.id', '=', 'wp_work.e_project_id')->
        join('b_supply as s', 's.id', '=', 'wp_work.b_supply_id')->
        join('b_factory as f', 'f.id', '=', 'wp_work.b_factory_id')->
        join('b_factory_a as fa', 'fa.id', '=', 'wp_work.b_factory_a_id')->
        join('b_factory_b as fb', 'fb.id', '=', 'wp_work.b_factory_b_id')->
        join('be_dept as d', 'd.id', '=', 'wp_work.be_dept_id1')->
        join('be_dept as d2', 'd2.id', '=', 'wp_work.be_dept_id2')->
        where('wp_work.isClose', 'N')->where('wp_work.isHoliday','Y')->
        where('wp_work.sdate','>',$today);
        if ($b_factory_id) {
            if(is_array($b_factory_id) && count($b_factory_id))
            {
                $data = $data->whereIn('wp_work.b_factory_id',$b_factory_id);
            } else {
                $data = $data->where('wp_work.b_factory_id',$b_factory_id);
            }
        }
        if ($b_factory_a_id) {
            if(is_array($b_factory_a_id) && count($b_factory_a_id))
            {
                $data = $data->whereIn('wp_work.b_factory_a_id',$b_factory_a_id);
            } else {
                $data = $data->where('wp_work.b_factory_a_id',$b_factory_a_id);
            }
        }
        $data = $data->select('p.name as project', 's.sub_name as supply', 'f.name as store',
        'fa.name as local', 'fb.name as area', 'd.name as dept1', 'd2.name as dept2', 'permit_no',
            'b_factory_memo','wp_permit_workitem_memo','wp_permit_danger');
        if ($data->count()) {
            $data = $data->get();
//            dd($data);
            foreach ($data as $val) {
                $tmp = [];
                $tmp['project'] = $val->project;
                $tmp['supply'] = $val->supply;
                $tmp['store'] = $val->store;
                $tmp['local'] = $val->local;
                $tmp['area'] = $val->area;
                $tmp['dept1'] = $val->dept1;
                $tmp['dept2'] = $val->dept2;
                $tmp['permit_no'] = $val->permit_no;
                $tmp['danger']          = $val->wp_permit_danger;
                $tmp['factory_memo']    = $val->b_factory_memo;
                $tmp['workitem_memo']   = $val->wp_permit_workitem_memo;
                $reptAry[] = $tmp;
            }
        }
        return $reptAry;
    }

    /**
     * 產生 當日 廠區儀表板 資料[局限空間超時]
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genPermitReptToday7($b_factory_id = 0,$b_factory_a_id = 0)
    {
        $ret = $reptAry = [];
        //2. 找出今日開立工作許可證
        $data = view_wp_work::
        join('e_project as p', 'p.id', '=', 'view_wp_work.e_project_id')->
        join('b_supply as s', 's.id', '=', 'view_wp_work.b_supply_id')->
        join('b_factory as f', 'f.id', '=', 'view_wp_work.b_factory_id')->
        join('b_factory_a as fa', 'fa.id', '=', 'view_wp_work.b_factory_a_id')->
        join('b_factory_b as fb', 'fb.id', '=', 'view_wp_work.b_factory_b_id')->
        join('be_dept as d', 'd.id', '=', 'view_wp_work.be_dept_id1')->
        join('be_dept as d2', 'd2.id', '=', 'view_wp_work.be_dept_id2')->
        join('wp_work_check_record2 as r', 'r.wp_work_id', '=', 'view_wp_work.id')->
        where('r.err_code',1)->where('r.door_type',1)->whereRaw('r.door_stamp1 <= (DATEADD(HOUR, - 1, GETDATE()))')->
        where('view_wp_work.isClose', 'N');
        if ($b_factory_id) {
            if(is_array($b_factory_id) && count($b_factory_id))
            {
                $data = $data->whereIn('view_wp_work.b_factory_id',$b_factory_id);
            } else {
                $data = $data->where('view_wp_work.b_factory_id',$b_factory_id);
            }
        }
        if ($b_factory_a_id) {
            if(is_array($b_factory_a_id) && count($b_factory_a_id))
            {
                $data = $data->whereIn('view_wp_work.b_factory_a_id',$b_factory_a_id);
            } else {
                $data = $data->where('view_wp_work.b_factory_a_id',$b_factory_a_id);
            }
        }
        $data = $data->select('p.name as project', 's.sub_name as supply', 'f.name as store',
            'fa.name as local', 'fb.name as area', 'd.name as dept1', 'd2.name as dept2', 'permit_no',
            'b_factory_memo','wp_permit_workitem_memo','view_wp_work.wp_permit_danger',
            'r.b_cust_id','r.door_stamp1');
        if ($data->count()) {
            $data = $data->get();
//            dd($data);
            foreach ($data as $val) {
                $tmp = [];
                $tmp['project']         = $val->project;
                $tmp['supply']          = $val->supply;
                $tmp['store']           = $val->store;
                $tmp['local']           = $val->local;
                $tmp['area']            = $val->area;
                $tmp['dept1']           = $val->dept1;
                $tmp['dept2']           = $val->dept2;
                $tmp['permit_no']       = $val->permit_no;
                $tmp['danger']          = $val->wp_permit_danger;
                $tmp['factory_memo']    = $val->b_factory_memo;
                $tmp['workitem_memo']   = $val->wp_permit_workitem_memo;
                $tmp['b_cust_id']       = $val->b_cust_id;
                $tmp['door_stamp1']     = substr($val->door_stamp1,0,19);
                $tmp['name']            = User::getName($val->b_cust_id);
                $reptAry[] = $tmp;
            }
        }
        return $reptAry;
    }
}
