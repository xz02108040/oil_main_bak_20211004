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
use App\Model\Factory\b_factory_b;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_log_door_today;
use App\Model\View\view_wp_work;
use App\Model\WorkPermit\wp_permit_shift;
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
 * 報表：[當日]工作許可證列表
 *
 */
trait ReptPermitTrait
{
    public function getPermitTodayFactoryTotal($b_factory_id = 0,$b_factory_a_id = 0)
    {
        $ret = $aprocAmtAry = [];
        $door_date      = date('Y-m-d');
        $aprocAry       = SHCSLib::getCode('PERMIT_APROC',0);
        foreach ($aprocAry as $key => $val)
        {
            $tmp = [];
            $tmp['name']  = $val;
            $tmp['aproc'] = $key;
            $tmp['amt']   = 0;
            $aprocAmtAry[$key] = $tmp;
        }
        //搜尋 當日工作許可證紀錄
        $data = wp_work::where('wp_work.sdate',$door_date);
        if($b_factory_id)
        {
            $data = $data->where('b_factory_id',$b_factory_id);
        }
        if($b_factory_a_id)
        {
            $data = $data->where('b_factory_a_id',$b_factory_a_id);
        }

        $data = $data->selectRaw('aproc,COUNT(id) as amt')->groupby('aproc');
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                if(isset($aprocAmtAry[$val->aproc]))
                {
                    $aprocAmtAry[$val->aproc]['amt'] = $val->amt;
                }
            }
        }
        foreach ($aprocAmtAry as $val)
        {
            $ret[] = $val;
        }
        return $ret;
    }

    /**
     * @param $store_id
     * @param $aprocType
     */
    public function getPermitTodayFactoryData($door_date = '', $b_factory_id = 0, $b_factory_d_id = 0, $supply_id = 0, $expAproc = [], $level = 0, $isCount = 'N')
    {
        $ret = [];
        $door_date      = (!$door_date)? date('Y-m-d') : $door_date;
        $yesterday      = SHCSLib::addDay(-1,$door_date);
        $aprocNotInAry  = (!count($expAproc))? ['A','B'] : $expAproc;
        $aprocNameAry   = SHCSLib::getCode('PERMIT_APROC');
        //搜尋 當日工作許可證紀錄
        $data = wp_work::join('b_factory as f','f.id','=','b_factory_id')->
        join('b_factory_d as d','d.id','=','b_factory_d_id')->
        join('b_supply as s','s.id','=','b_supply_id')->
        whereNotIn('aproc',$aprocNotInAry)->
        where('wp_work.sdate',$door_date);
        if($b_factory_id)
        {
            $data = $data->where('wp_work.b_factory_id',$b_factory_id);
        }
        if($b_factory_d_id)
        {
            $data = $data->where('wp_work.b_factory_d_id',$b_factory_d_id);
        }
        if($supply_id)
        {
            $data = $data->where('wp_work.b_supply_id',$supply_id);
        }
        if($b_factory_id && !$b_factory_d_id && $level == 1)
        {
            $data = $data->selectRaw('wp_work.b_factory_id as id,f.name,count(f.name) as amt')->
            groupby('wp_work.b_factory_id','f.name');
        }
        if($b_factory_id && !$b_factory_d_id && $level == 2)
        {
            $data = $data->selectRaw('wp_work.b_factory_d_id as id,d.name,count(d.name) as amt')->
            groupby('wp_work.b_factory_d_id','d.name');
        }
        if($b_factory_id && $b_factory_d_id && $level == 3)
        {
            $data = $data->selectRaw('wp_work.b_supply_id as id,s.name,count(s.name) as amt')->
            groupby('wp_work.b_supply_id','s.name');
        }
        if($b_factory_id && $b_factory_d_id && $supply_id && $level == 4)
        {
            $data = $data->select('f.name as store','d.name as door','s.name as supply','wp_work.permit_no',
                'wp_work.aproc','wp_work.id')->
            orderby('wp_work.permit_no');
        }

        //if($level > 1) dd($level,$b_factory_id , $b_factory_d_id , $supply_id , $isCount,$data->get());
        if($isCount == 'Y')
        {
            $ret = $data->count();
        } else {
            if($data->count())
            {
                if($level == 4)
                {
                    foreach ($data->get() as $val)
                    {
                        $tmp = [];
                        $tmp['id']              = $val->id;
                        $tmp['permit_no']       = $val->permit_no;
                        $tmp['store']           = $val->store;
                        $tmp['door']            = $val->door;
                        $tmp['supply']          = $val->supply;
                        $tmp['aproc']           = $val->aproc;
                        $tmp['aproc_name']      = isset($aprocNameAry[$val->aproc])? $aprocNameAry[$val->aproc] : '';
                        $ret[] = $tmp;
                    }
                } else {
                    $ret = $data->get()->toArray();
                }
            }
        }

        return $ret;
    }

    /**
     * @param $store_id
     * @param $aprocType
     */
    public function getPermitTodayFactoryWorkerData($door_date = '', $b_factory_id = 0, $b_factory_d_id = 0, $supply_id = 0, $expAproc = [])
    {
        $door_date      = (!$door_date)? date('Y-m-d') : $door_date;
        $aprocNotInAry  = (!count($expAproc))? ['A','B'] : $expAproc;
        //搜尋 當日工作許可證紀錄
        $data = wp_work::join('wp_work_worker as w','w.wp_work_id','=','wp_work.id')->
        join('view_user as u','u.b_cust_id','=','w.user_id')->
        whereNotIn('wp_work.aproc',$aprocNotInAry)->
        where('wp_work.sdate',$door_date)->
        where('w.isClose','N');
        if($b_factory_id)
        {
            $data = $data->where('wp_work.b_factory_id',$b_factory_id);
        }
        if($b_factory_d_id)
        {
            $data = $data->where('wp_work.b_factory_d_id',$b_factory_d_id);
        }
        if($supply_id)
        {
            $data = $data->where('wp_work.b_supply_id',$supply_id);
        }
        $data = $data->select('u.b_cust_id','u.name')->groupby('u.b_cust_id','u.name');
        $data = $data->get()->toArray();
        //dd($data->get());
        return [count($data),$data];
    }

    /**
     * @param $store_id
     * @param $aprocType
     * @return string
     */
    public function genPermitWorkHtml($store_id,$aprocType)
    {
        $today          = date('Y-m-d');
        //2019-10-06 新增 中午時段不顯示紅色報表
        $noShow_s       = strtotime(date('Y-m-d 11:30:00'));
        $noShow_e       = strtotime(date('Y-m-d 13:00:59'));
        $now            = time();
        $isShowPert2    = 1;
        if($now >= $noShow_s && $noShow_e >= $now)
        {
            $isShowPert2 = 0;
        }

        list($amount,$listAry,$listAry2)  = $this->getPermitWorkTodayList($store_id,0,0,$aprocType,$today);
        Session::put('test.reptpermit1.list1',$listAry);
        Session::put('test.reptpermit1.list2',$listAry2);
        $local          = b_factory::getName($store_id);
        $door_total_men = Lang::get('sys_rept.rept_201',['name'=>$local,'date'=>$today,'amt'=>$amount]);
        $door_total_men_html = '<span class="btn-success" style="border-radius: 15px; -webkit-border-radius: 15px; -moz-border-radius: 15px;font-size: 25pt">&emsp;<b>'.$door_total_men.'</b>&emsp;</span>';
        $door_color_html = '<span class="btn-default" style="border-radius: 15px; -webkit-border-radius: 15px; -moz-border-radius: 15px;font-size: 17pt;float: right;">&emsp;<b>最後更新時間：<span id="door_clock">'.date('Y-m-d H:i').'</span></b>&emsp;</span>';

        $title1 = '<div style="width:100%;font-family:標楷體,微軟正黑體,serif,sans-serif,cursive;">'.Lang::get($this->langText.'.title10a').'</div>';
        $title2 = '<div style="width:100%;font-family:標楷體,微軟正黑體,serif,sans-serif,cursive;">'.Lang::get($this->langText.'.title10b').'</div>';
        $alertTable = '';

        if(count($listAry2) && $isShowPert2)
        {
            //table
            $table2 = new TableLib('','table_rept2','','width:60%;',0);
            $heads = $tBody = [];
            //標題
            $heads[] = ['title'=>Lang::get('sys_rept.rept_203')]; //承商
            $heads[] = ['title'=>Lang::get('sys_rept.rept_202')]; //工程案件
            $heads[] = ['title'=>Lang::get('sys_rept.rept_211')]; //施工地點
            $heads[] = ['title'=>Lang::get('sys_rept.rept_205')]; //安衛
            $heads[] = ['title'=>Lang::get('sys_rept.rept_224')]; //量測時間
            $table2->addHead($heads,0);

            foreach($listAry2 as $value)
            {
                $name2        = '<span style="font-size: 20px;">'.$value['project'].'</span>'; //
                $name3        = '<span style="font-size: 20px;">'.$value['supply'].'</span>'; //
                $name4        = '<span style="font-size: 20px;">'.$value['local'].'</span>'; //
                $name5        = '<span style="font-size: 20px;">'.$value['supply_safer'].'</span>'; //
                $name18       = '<span style="'.$value['record_alert1'].'">'.$value['record_stamp1'].'</span>'; //

                $tBody[] = [
                    '3'=>[ 'name'=> $name3,'b'=>1,'align'=>'center'],
                    '2'=>[ 'name'=> $name2,'b'=>1,'align'=>'center'],
                    '4'=>[ 'name'=> $name4,'b'=>1,'align'=>'center'],
                    '7'=>[ 'name'=> $name5,'style'=>'width:10%','b'=>1,'align'=>'center'],
                    '18'=>[ 'name'=> $name18,'style'=>'width:20%','b'=>1,'align'=>'center'],
                ];
            }
            $table2->addBody($tBody);
            $alertTable = $title1.$table2->output().'<hr/>';
        }

        //table
        $table = new TableLib('','table_rept','','',0);
        $heads = $tBody = [];
        //標題
//        $heads[] = ['title'=>Lang::get('sys_rept.rept_202')]; //工程案件
        $heads[] = ['title'=>Lang::get('sys_rept.rept_203')]; //承商
        $heads[] = ['title'=>Lang::get('sys_rept.rept_211')]; //施工地點
//        $heads[] = ['title'=>Lang::get('sys_rept.rept_210')]; //在廠人數
        $heads[] = ['title'=>Lang::get('sys_rept.rept_226')]; //工作說明
        $heads[] = ['title'=>Lang::get('sys_rept.rept_222')]; //許可項目
        $heads[] = ['title'=>Lang::get('sys_rept.rept_221')]; //危險等級
        $heads[] = ['title'=>Lang::get('sys_rept.rept_212')]; //進度
        $heads[] = ['title'=>Lang::get('sys_rept.rept_213')]; //氧氣
        $heads[] = ['title'=>Lang::get('sys_rept.rept_214')]; //可燃氣體
        $heads[] = ['title'=>Lang::get('sys_rept.rept_215')]; //一氧化碳
        $heads[] = ['title'=>Lang::get('sys_rept.rept_216')]; //氧化硫
//        $heads[] = ['title'=>Lang::get('sys_rept.rept_217')]; //有害氣體
        $heads[] = ['title'=>Lang::get('sys_rept.rept_218')]; //本次量測
        $heads[] = ['title'=>Lang::get('sys_rept.rept_219')]; //上次量測
        $heads[] = ['title'=>Lang::get('sys_rept.rept_200')]; //本證編號
        $heads[] = ['title'=>Lang::get('sys_rept.rept_225')]; //回簽時間
        $heads[] = ['title'=>Lang::get('sys_rept.rept_223')]; //本證編號

        $table->addHead($heads,0);
        if($amount)
        {
            $aprocColorAry = SHCSLib::getPermitAprocColor();
            foreach($listAry as $value)
            {
                $no           = '<span style="font-size: 12px;">'.$value['no'].'</span>';
//                $name2        = $value['project']; //
                $name3        = $value['supply']; //
                $name4        = $value['local']; //
                $danger_color = ($value['danger'] == 'A')? 'red' : 'black';
                $name7        = '<span style="font-color:'.$danger_color.'">'.$value['danger'].'</span>'; //
                $name5        = '<span style="">'.$value['item'].'</span>'; //
                $name6        = '<span style="">'.$value['aproc_name'].'</span>'; //
                $name8        = '<span style="">'.$value['memo'].'</span>'; //
                $isColor6     = isset($aprocColorAry[$value['aproc']])? $aprocColorAry[$value['aproc']] : 1 ; //停用顏色
                $name10       = '<span>'.$value['record1'].'</span>'; //
                $name11       = '<span>'.$value['record2'].'</span>'; //
                $name12       = '<span>'.$value['record3'].'</span>'; //
                $name13       = '<span>'.$value['record4'].'</span>'; //
                $name14       = '<span class="">'.$value['record5'].'</span>'; //
                $name18       = '<span style="">'.$value['record_stamp1'].'</span>'; //
                $name19       = '<span style="">'.$value['record_stamp2'].'</span>'; //
                $name20       = '<span style="font-size: 20px;">'.$value['proejct_charge'].'</span>'; //
                $name21       = '<span style="">'.$value['etime1'].'</span>'; //


                $tBody[] = [
                    '3'=>[ 'name'=> $name3,'style'=>'width:5%','b'=>1,'align'=>'center'],
                    '4'=>[ 'name'=> $name4,'style'=>'width:10%','b'=>1,'align'=>'center'],
                    '8'=>[ 'name'=> $name8,'align'=>'center'],
                    '5'=>[ 'name'=> $name5,'align'=>'center'],
                    '7'=>[ 'name'=> $name7,'b'=>1,'align'=>'center','align'=>'center'],
                    '9'=>[ 'name'=> $name6,'label'=>$isColor6,'align'=>'center'],
                    '10'=>[ 'name'=> $name10,'b'=>1,'align'=>'center','class'=>$value['record1_check']],
                    '11'=>[ 'name'=> $name11,'b'=>1,'align'=>'center','class'=>$value['record2_check']],
                    '12'=>[ 'name'=> $name12,'b'=>1,'align'=>'center','class'=>$value['record3_check']],
                    '13'=>[ 'name'=> $name13,'b'=>1,'align'=>'center','class'=>$value['record4_check']],
                    '18'=>[ 'name'=> $name18,'b'=>1,'align'=>'center','class'=>$value['record_alert1']],
                    '19'=>[ 'name'=> $name19,'b'=>1,'align'=>'center'],
                    '0'=>[ 'name'=> $no,'b'=>1,'width:7%','align'=>'center'],
                    '2'=>[ 'name'=> $name21,'b'=>1,'width:7%','align'=>'center'],
                    '1'=>[ 'name'=> $name20,'b'=>1,'width:7%','align'=>'center'],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出ＨＴＭＬ
        $ret  = '<div class="loglist">';
        //最後刷卡人員
        $ret .= '<div class="last_record_div">';
        $ret .= $door_color_html;
        $ret .= '<div style="width:100%;">'.$door_total_men_html.'</div>';
        $ret .= '<div style="width:100%;height: 80%;overflow:auto;"><p>'.$alertTable.$title2.$table->output().'</p></div></div>';
        return $ret;
    }

    /**
     * 搜尋報表資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getPermitWorkTodayList($store_id,$local_id,$dept_id,$aprocType = '')
    {
        $ret = $ret1 = [];
        if(!$store_id) $store_id = sys_param::getParam('REPORT_DEFAULT_STORE',6);
        $aprocTypeAry = ($aprocType)? [$aprocType] : ['P','W','R','O','F'];//'A',

        $aprocNameAry   = SHCSLib::getCode('PERMIT_APROC');
        $storeAry       = b_factory::getSelect(0);
        $localAry       = b_factory_a::getSelect(0,0,0);
        $deviceAry      = b_factory_b::getSelect(0,0,0);
        $supplyAry      = b_supply::getSelect2();
        $deptAry        = be_dept::getSelect(0,0,0,0,0,0);
        $shifAry        = wp_permit_shift::getSelect(); //班別

        $data = view_wp_work::where('b_factory_id',$store_id)->whereIn('aproc',$aprocTypeAry)->where('isClose','N');
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
        $data = $data->orderby('aproc')->orderby('permit_no');
        if($data->count())
        {
            $data = $data->get();
            foreach( $data as $key => $value)
            {
                $workitem = wp_work_workitem::getKind($value->id);
                if(isset($workitem[2]))
                {
                    unset($workitem[1]);
                }
                //
                [$last_work_process_id,$wp_work_process_id] = wp_work_list::getProcessIDList($value->id);
                $wp_process_id = wp_work_process::getProcess($wp_work_process_id);

                $workitemStr = implode('，',$workitem);
                $tmp = [];
                $tmp['no']              = $value->permit_no;
                $tmp['sdate']           = $value->sdate;
                $tmp['danger']          = $value->wp_permit_danger;
                $tmp['etime1']          = !is_null($value->etime1)? substr($value->etime1,0,5) : '';
                $tmp['project']         = e_project::getName($value->e_project_id);
                $tmp['item']            = $workitemStr;
                $tmp['shift']           = isset($shifAry[$value->wp_permit_shift_id])? $shifAry[$value->wp_permit_shift_id] : '';
                $tmp['supply']          = isset($supplyAry[$value->b_supply_id])? $supplyAry[$value->b_supply_id] : '';
                $tmp['store']           = isset($storeAry[$value->b_factory_id])? $storeAry[$value->b_factory_id] : '';
                $tmp['local']           = isset($localAry[$value->b_factory_a_id])? $localAry[$value->b_factory_a_id] : '';
                $tmp['work_area']       = isset($deviceAry[$value->b_factory_b_id])? $deviceAry[$value->b_factory_b_id] : '';
                $tmp['work_area_memo']  = $value->b_factory_memo;
                $tmp['workitem_memo']   = $value->wp_permit_workitem_memo;
                if($wp_process_id == 8)
                {
                    $value->aproc           = 'R';
                    $tmp['aproc_name']      = isset($aprocNameAry[$value->aproc])? $aprocNameAry[$value->aproc] : '';
                    $tmp['aproc']           = $value->aproc;
                } else {
                    $tmp['aproc_name']      = isset($aprocNameAry[$value->aproc])? $aprocNameAry[$value->aproc] : '';
                    $tmp['aproc']           = $value->aproc;
                }
                $tmp['dept1']           = isset($deptAry[$value->be_dept_id1])? $deptAry[$value->be_dept_id1] : '';
                $tmp['dept2']           = isset($deptAry[$value->be_dept_id2])? $deptAry[$value->be_dept_id2] : '';
                $tmp['dept3']           = isset($deptAry[$value->be_dept_id3])? $deptAry[$value->be_dept_id3] : '';
                $tmp['dept4']           = isset($deptAry[$value->be_dept_id4])? $deptAry[$value->be_dept_id4] : '';
                $tmp['dept5']           = isset($deptAry[$value->be_dept_id5])? $deptAry[$value->be_dept_id5] : '';
//                $tmp['supply_worker']   = User::getName($value->supply_worker);
                $tmp['proejct_charge']  = User::getName($value->proejct_charge);//.'<br/>'.b_cust_a::getMobile($value->proejct_charge);
                $supply_safer           = wp_work_worker::getSelect($value->id,2,0,2);
                $tmp['supply_safer']    = implode('，',$supply_safer);
//                $tmp['inMen']           = rept_doorinout_t::getWorkMenInOutCount($today,$value->id);
                //氣體偵測<承攬商>
                $tmpRecordAry = wp_work_check_record1::getLastRecord($value->id,[1,3]);
                $tmp = array_merge($tmp,$tmpRecordAry);
                //2019-11-13 如果尚未開始量測，顯示「無」
                if(!$tmp['record_stamp2'])
                {
                    $tmp['record_stamp2'] = Lang::get('sys_rept.rept_227');
                }

                if(in_array($value->aproc,['P','R']) && ($tmp['record_alert1'] || (!$tmp['record_alert1'] && !$tmp['record_stamp1']) ) )
                {
                    $ret1[] = $tmp;
                } else {
                    $tmp['record_alert1'] = '';
                }
                $ret[] = $tmp;
            }
        }
//        dd($ret);
        return [count($ret),$ret,$ret1];
    }
}
