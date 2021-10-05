<?php

namespace App\Http\Traits\Report;

use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_d;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\User;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_worker;
use Storage;
use DB;
use Lang;
use Session;

/**
 * 報表：當日 人員 進出報表紀錄
 *
 */
trait ReptDoorMenInOutTodayTrait
{
    /**
     * 新增 當日 人員 進出報表紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createDoorMenInOutToday($data,$logid = 0)
    {
        $ret = false;
        if(!count($data)) return $ret;
        $today = date('Y-m-d');
        list($b_cust_id,$name,$bc_type,$unit_id,$unit_name,$b_rfid_id,$rfid_code,$door_type,$door_stamp,$b_factory_id,$b_factory_d_id,$e_project_id,$door_result,$door_memo,$jobkindName,$work_id) = $data;
        $door_date = substr($door_stamp,0,10);
        if(!$b_cust_id || !$door_date) return $ret;

        $last_door_id = rept_doorinout_t::isDateExist($door_date,$b_factory_id,0,$b_cust_id);
        //dd($last_door_id);
        //如果不存在 紀錄，則新增
        if(!$last_door_id)
        {
            //1. 先回寫 帳號：最後進廠日
            if($door_date == $today && $bc_type < 4)
            {
                $UPD = User::find($b_cust_id);
                $UPD->last_door_date   = $door_date;
                $UPD->save();
            }

            $INS = new rept_doorinout_t();
            $INS->b_cust_id           = $b_cust_id;
            $INS->name                = $name;
            $INS->b_factory_id        = $b_factory_id;
            $INS->b_factory_d_id      = $b_factory_d_id;
            $INS->door_type           = $door_type;
            $INS->door_date           = $door_date;
            $INS->door_stamp          = $door_stamp;
            $INS->log_door_inout_id   = $logid;
            $INS->wp_work_id          = $work_id;
            if($bc_type == 2)
            {
                $INS->be_dept_id      = $unit_id;
            } else {
                $INS->b_supply_id     = $unit_id;
                $INS->e_project_id    = $e_project_id;
            }
            $INS->unit_name           = $unit_name;
            $ret = $INS->save();
        } else {
            $UPD = rept_doorinout_t::find($last_door_id);
            $last_door_stamp = strtotime($UPD->door_stamp);
            //必須大於 最後進出入時間，才更新
            if($last_door_stamp <= strtotime($door_stamp))
            {
                $UPD->b_factory_id        = $b_factory_id;
                $UPD->b_factory_d_id      = $b_factory_d_id;
                $UPD->door_type           = $door_type;
                $UPD->door_date           = $door_date;
                $UPD->door_stamp          = $door_stamp;
                $UPD->log_door_inout_id   = $logid;
                $UPD->wp_work_id          = $work_id;
                $ret = $UPD->save();
            }
        }
        return $ret;
    }


    public function getDoorMenInOutTodayWorkerAmt($b_factory_id,$b_factory_a_id = 0,$be_dept_id1 = 0)
    {

    }

    /**
     * 當日各廠區儀表板(廠區/門別/承攬商)
     * @param $door_date
     * @param $b_factory_id
     * @param $b_factory_d_id
     * @param $supply
     * @param $door_type
     * @return array|object
     */
    public function getDoorMenInOutTodayFactoryData($door_date = '', $b_factory_id = 0, $b_factory_d_id = 0, $suuply_id = 0, $door_type = 0, $level = 0, $isCount = 'N',$isTest = 0)
    {
        $ret = [];
        $door_date = (!$door_date)? date('Y-m-d') : $door_date;
        $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE');
        $nowStamp       = time();
        $yesterday      = SHCSLib::addDay(-1,$door_date);
        $yesterdaySTime = sys_param::getParam('REPORT_DOOR_YESTERDAY_STIME','00:00:00');
        $yesterdayETime = sys_param::getParam('REPORT_DOOR_YESTERDAY_ETIME','08:00:00');
        $todaySTime     = sys_param::getParam('REPORT_DOOR_TODAY_STIME','08:00:00');
        $y_sdate  = date('Y-m-d H:i:s',strtotime($yesterday.' '.$yesterdaySTime));
        $y_edate  = date('Y-m-d H:i:s',strtotime($door_date.' '.$yesterdayETime));
        $t_sdate  = date('Y-m-d H:i:s',strtotime($door_date.' '.$todaySTime));
        $t_edate  = date('Y-m-d H:i:s',strtotime($door_date.' '.$yesterdaySTime));
        $t_Stamp1 = strtotime($t_sdate);
        $t_Stamp2 = strtotime($t_edate);
        //$doorColorAry= [0=>5,1=>2,2=>4];
        //搜尋 當日進出廠紀錄
        $data = rept_doorinout_t::join('b_factory as f','f.id','=','b_factory_id')->
        join('b_factory_d as d','d.id','=','b_factory_d_id');

        //早上九點前
        if($t_Stamp1 >= $nowStamp)
        {
            $data = $data->where('rept_doorinout_t.door_stamp','>=',$y_sdate)->
                            where('rept_doorinout_t.door_stamp','<=',$t_sdate);
        }
        //下午六點後
        elseif($t_Stamp2 <= $nowStamp)
        {
            $data = $data->where('rept_doorinout_t.door_stamp','>=',$t_edate);
        }
        else {
            //今日早上六點以後
            $data = $data->where('rept_doorinout_t.door_stamp','>=',$y_edate);
        }


        if($b_factory_id)
        {
            $data = $data->where('rept_doorinout_t.b_factory_id',$b_factory_id);
        }
        if($b_factory_d_id)
        {
            $data = $data->where('rept_doorinout_t.b_factory_d_id',$b_factory_d_id);
        }
        if($suuply_id)
        {
            $data = $data->where('rept_doorinout_t.b_supply_id',$suuply_id);
        }
        if($door_type)
        {
            $data = $data->where('rept_doorinout_t.door_type',$door_type);
        }
        if($b_factory_id && !$b_factory_d_id && $level == 1)
        {
            $data = $data->selectRaw('rept_doorinout_t.b_factory_id as id,f.name,count(f.name) as amt')->
            groupby('rept_doorinout_t.b_factory_id','f.name');
        }
        elseif($b_factory_id && !$b_factory_d_id && $level == 2)
        {
            $data = $data->selectRaw('rept_doorinout_t.b_factory_d_id as id,d.name,count(d.name) as amt')->
            groupby('rept_doorinout_t.b_factory_d_id','d.name');
        }
        elseif($level == 3)
        {
            $data = $data->selectRaw('rept_doorinout_t.b_supply_id as id,rept_doorinout_t.unit_name as name,rept_doorinout_t.door_type,count(rept_doorinout_t.unit_name) as amt')->
            groupby('rept_doorinout_t.b_supply_id','rept_doorinout_t.unit_name','rept_doorinout_t.door_type');
        }
        elseif($b_factory_id && $suuply_id && $level == 4)
        {
            $data = $data->select('rept_doorinout_t.name','rept_doorinout_t.door_stamp','log_door_inout_id as log_id',
                'rept_doorinout_t.unit_name','rept_doorinout_t.door_type','rept_doorinout_t.e_project_id',
                'rept_doorinout_t.wp_work_id','f.name as store','d.name as door')->orderby('door_stamp');
        }

//        if($level >= 1)dd($isCount,$b_factory_id,$b_factory_d_id,$suuply_id,$level,$data->get());
        if($isCount == 'Y')
        {
            $ret = $data->count();
        } else {
            if($data->count())
            {
                if($level == 4)
                {
                    $repTmpAry = [];
                    foreach ($data->get() as $val)
                    {
                        $workRoot = wp_work_worker::getWorkInfo($val->wp_work_id);
                        $imgPath = LogLib::getLogDoorImgUrl($val->log_id);
                        if($val->log_id)
                        {
                            if(substr($imgPath,0,4) != 'http'){
                                $imgPath = url('img/Door/'.SHCSLib::encode($val->log_id));
                            }
                        }
                        $tmp = [];
                        $tmp['id']              = $val->log_id;
                        $tmp['name']            = $val->name;
                        $tmp['store']           = $val->store;
                        $tmp['door']            = $val->door;
                        $tmp['door_stamp']      = substr($val->door_stamp,0,16);
                        $tmp['unit_name']       = $val->unit_name;
                        $tmp['door_type']       = $val->door_type;
                        $tmp['door_type_name']  = isset($doorTypeAry[$val->door_type])? $doorTypeAry[$val->door_type] : '';
                        $tmp['img']             = $imgPath;
                        $tmp['permit_no']       = isset($workRoot['no'])? $workRoot['no'] : '';
                        $tmp['worker1']         = isset($workRoot['worker1'])? $workRoot['worker1'] : '';
                        $tmp['worker2']         = isset($workRoot['worker2'])? $workRoot['worker2'] : '';
                        $tmp['job_kind']        = LogLib::getJobKind($val->log_id);
                        $repTmpAry[$val->name] = $tmp;
                    }
                    foreach ($repTmpAry as $val)
                    {
                        $ret[] = $val;
                    }
                } else {
                    $ret = $data->get()->toArray();
                }
            }
        }

        return $ret;
    }

    /**
     * 當日各廠區儀表板(廠區/門別/承攬商)
     * @param $door_date
     * @param $b_factory_id
     * @param $b_factory_d_id
     * @param $supply
     * @param $door_type
     * @return array|object
     */
    public function getDoorMenData($door_date = '', $b_factory_id = 0)
    {
        $ret = [];
        $door_date = (!$door_date)? date('Y-m-d') : $door_date;
        //搜尋 當日進出廠紀錄
        $data = rept_doorinout_t::join('b_factory as f','f.id','=','b_factory_id')->
        join('b_factory_d as d','d.id','=','b_factory_d_id')->
        where('rept_doorinout_t.door_date',$door_date);
        $data = $data->where('rept_doorinout_t.door_type',1);
        $data = $data->where('rept_doorinout_t.b_factory_id',$b_factory_id);

        $data = $data->selectRaw('rept_doorinout_t.b_factory_d_id as id,d.name,count(d.name) as amt')->
        groupby('rept_doorinout_t.b_factory_d_id','d.name');

        //dd($data);
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $tmp = [];
                $tmp['id'] = $val->id;
                $tmp['name'] = $val->name;
                $tmp['amt'] = $val->amt;
                $ret[$val->id] = $tmp;
            }
        }

        return $ret;
    }

    /**
     * 廠區當日儀表板 [廠區/人員]
     * @param $b_factory_id
     * @param string $door_date
     * @return array|object
     */
    public function getDoorInOutTodayData($mode, $b_factory_d_id,$cmp = 0, $door_date = '',$door_type = -1)
    {
        $ret = $tmpNoOneAry = [];
        $door_date = (!$door_date)? date('Y-m-d') : $door_date;
        $doorTypeAry = SHCSLib::getCode('DOOR_INOUT_TYPE');
        $localAry    = b_factory_d::getSelect(0);
        $doorColorAry= [0=>5,1=>2,2=>4];
        //搜尋 當日進出廠紀錄
        $data = rept_doorinout_t::where('door_date',$door_date)->where('b_factory_d_id',$b_factory_d_id);
        if($door_type >= 0)
        {
            $data = $data->where('door_type',$door_type);
        }
        if($cmp > 0)
        {
            $data = $data->where('b_supply_id',$cmp);
        }
        if($mode == 1)
        {
            $data = $data->selectRaw('b_factory_id,b_factory_d_id,b_supply_id,unit_name, COUNT(b_supply_id) as amt')->
            groupby('b_factory_id','b_factory_d_id','b_supply_id','unit_name')->get();
        } else {
            $data = $data->orderby('door_stamp')->get();
        }
        //dd([$b_factory_d_id,$door_date,$door_type,$cmp,$data]);
        if(count($data))
        {
            foreach ($data as $key => $val)
            {
                $data[$key]['local']            = isset($localAry[$val->b_factory_d_id])? $localAry[$val->b_factory_d_id] : '';
                $data[$key]['store_id']         = $val->b_factory_id;

                if($mode == 2)
                {
                    $data[$key]['amt'] = 1;
                    $data[$key]['door_type_color']  = isset($doorColorAry[$val->door_type])? $doorColorAry[$val->door_type] : '';
                    $data[$key]['door_type_name']   = isset($doorTypeAry[$val->door_type])? $doorTypeAry[$val->door_type] : '';
                    $data[$key]['door_stamp']       = substr($val->door_stamp,0,16);
                    $data[$key]['job_kind']         = LogLib::getJobKind($val->log_door_inout_id);
                } else {
                    $amt1 = rept_doorinout_t::getMenCount($door_date,0,$b_factory_d_id,$val->b_supply_id,1);
                    $data[$key]['amt1']             = $amt1;
                    $data[$key]['amt2']             = (($val->amt - $amt1) >= 0)? ($val->amt - $amt1) : 0;
                    $data[$key]['door_type']        = 1;
                    $data[$key]['door_type_color']  = 3;
                    $data[$key]['door_type_name']   = Lang::get('sys_base.base_40216',['amt'=>'']);
                }
                $data[$key]['title_id']  = $val->b_factory_id;
                $data[$key]['title']     = ($mode == 2)? $val->name : $val->unit_name;
                $data[$key]['unit']      = Lang::get('sys_base.base_40211');
                $data[$key]['headline']  = ($mode == 2)? $val->unit_name : Lang::get('sys_base.base_40219');
                $data[$key]['sub_title'] = '';

                if($mode == 1)
                {
                    //訪客
                    $gust_supply_id = sys_param::getParam('REPORT_GUST_SUPPLY_ID',0);
                    if($val->b_supply_id == $gust_supply_id)
                    {
                        $tmpNoOneAry = $data[$key];
                        unset($data[$key]);
                    }
                }
            }
            //訪客
            if(count($tmpNoOneAry))
            {
                $ret[] = (object)$tmpNoOneAry;
                foreach ($data as $val2)
                {
                    $ret[] = (object)$val2;
                }
            } else {
                $ret = (object) $data;
            }
        }
        return $ret;
    }

    /**
     * 產生儀表板報表內容 by 廠商/人員
     * @param int $mode
     * @param $b_factory_id
     * @param string $localUrl
     * @return string
     */
    public function genDoorInOutTodayMenHtml($mode = 1, $b_factory_d_id, $cmp = 0, $localUrl = '')
    {
        $html = $unit = '';
        $rept = $personAry = [];
        $icon = HtmlLib::genIcon('chevron-circle-right');
        $urlP = 'sid='.SHCSLib::encode($b_factory_d_id);
        $backUrl = ($mode == 2)? $urlP : '';
        //1. 取得資料
        $listAry = $this->getDoorInOutTodayData($mode,$b_factory_d_id,$cmp);
        Session::put('test.report.men_select',$listAry);
        //2. 產生ＨＴＭＬ
        $out = new ContentLib();
        if(count($listAry))
        {
            foreach ($listAry as $val)
            {
                $in_amt = ($mode == 1) ? $val->amt1 : $val->amt;
                //標題
                if(isset($rept[$val->door_type]))
                {
                    $rept[$val->door_type]['amt']  += $in_amt;
                } else {
                    $rept[$val->door_type]['amt']   = $in_amt;
                    $rept[$val->door_type]['name']  = $val->door_type_name;
                    $rept[$val->door_type]['color'] = $val->door_type_color;
                }

                //底下內容
                if($mode == 1)
                {
                    $title  = '<h4>'.$val->unit_name.'</h4>';
                    //$contxt = ($val->door_type == 1)? 'base_40216' : 'base_40217';
                    $cont   = Lang::get('sys_base.base_40216',['amt'=>$val->amt1]);
                    $cont  .= '，'.Lang::get('sys_base.base_40217',['amt'=>$val->amt2]);
                    $url    = 'rept_doorinout_t?'.$urlP.'&level=2&cmp='.SHCSLib::encode($val->b_supply_id);
                    $unit   = '';
                    $target = '_self';
                } else {
                    $jobkind = (isset($val->job_kind) && $val->job_kind)? HtmlLib::Color($val->job_kind,'blue',1) : '';

                    $title  = $val->unit_name.$icon.$jobkind;
                    $cont   = $val->name;
                    $url    = ($val->log_door_inout_id)? ('img/Door/'.SHCSLib::encode($val->log_door_inout_id)) : '#';
                    $unit   = $val->door_type_name.':'.$val->door_stamp;
                    $target = '_blank';
                }
                $personAry[] = $out->info_box($title,$cont,$unit,'user',$val->door_type_color,$url,$target);
            }
            Session::put('test.report.men_list',$rept);
        }
        //dd([$listAry,$personAry,$rept]);
        //個人儀表板
        $out->rowTo($personAry);
        if(count($rept))
        {
            sort($rept);
            foreach ($rept as $key => $val)
            {
                $name  = $val['name'].'：'.$val['amt'];
                $html .= HtmlLib::genLabel($name, $val['color']);
            }
        }

        $html .= '   '.FormLib::linkbtn('rept_doorinout_t?'.$backUrl.$localUrl,Lang::get('sys_btn.btn_5'),1);
        $html .= '<hr/>';
        $html .= $out->output();

        return $html;
    }
}
