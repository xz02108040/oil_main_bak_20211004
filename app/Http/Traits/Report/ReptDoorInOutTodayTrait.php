<?php

namespace App\Http\Traits\Report;

use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Factory\b_factory_a;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use Storage;
use DB;
use Lang;
use Session;

/**
 * 報表：當日 人員 進出報表紀錄
 *
 */
trait ReptDoorInOutTodayTrait
{
    /**
     * 新增 當日 人員 進出報表紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createDoorInOutToday($data,$logid = 0)
    {
        $ret = false;
        if(!count($data)) return $ret;
        list($b_cust_id,$name,$bc_type,$unit_id,$unit_name,$b_rfid_id,$rfid_code,$door_type,$door_stamp,$b_factory_id,$b_factory_d_id,$e_project_id,$door_result,$door_memo,$jobkindName,$work_id) = $data;
        $door_date = substr($door_stamp,0,10);
        if(!$b_cust_id || !$door_date) return $ret;

        $last_door_id = rept_doorinout_t::isDateExist($door_date,$b_factory_id,$b_cust_id);
        //dd($last_door_id);
        //如果不存在 紀錄，則新增
        if(!$last_door_id)
        {
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
                $ret = $UPD->save();
            }
        }
        return $ret;
    }

    /**
     * 當日各廠區儀表板
     * @param $b_factory_id
     * @param string $door_date
     * @return array|object
     */
    public function getDoorInOutTodayFactoryData($door_date = '', $door_type = 0, $supply = 0)
    {
        $ret = [];
        $door_date = (!$door_date)? date('Y-m-d') : $door_date;
        $doorTypeAry = SHCSLib::getCode('DOOR_INOUT_TYPE');
        $doorColorAry= [0=>5,1=>2,2=>4];
        //搜尋 當日進出廠紀錄
        $data = rept_doorinout_t::where('door_date',$door_date)->selectRaw('b_factory_id,door_type,count(id) as amt');
        if($door_type)
        {
            $data = $data->where('door_type',$door_type);
        }
        if($supply)
        {
            $data = $data->where('b_supply_id',$supply);
        }
        $data = $data->groupby('b_factory_id','door_type')->orderby('b_factory_id')->orderby('door_type')->get();
        //dd($data);
        if(count($data))
        {
            foreach ($data as $key => $val)
            {
                $id = $val->b_factory_id;
                $ret[$id]['amt']             = $val->amt;
                $ret[$id]['door_type_name']  = isset($doorTypeAry[$val->door_type])? $doorTypeAry[$val->door_type] : '';
                $ret[$id]['door_type_color'] = isset($doorColorAry[$val->door_type])? $doorColorAry[$val->door_type] : 0;
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
    public function getDoorInOutTodayData($mode, $b_factory_id,$cmp = 0, $door_date = '',$door_type = -1)
    {
        $ret = $tmpNoOneAry = [];
        $door_date = (!$door_date)? date('Y-m-d') : $door_date;
        $doorTypeAry = SHCSLib::getCode('DOOR_INOUT_TYPE');
        $localAry    = b_factory_a::getSelect(0,0,0);
        $doorColorAry= [0=>5,1=>2,2=>4];
        //搜尋 當日進出廠紀錄
        $data = rept_doorinout_t::where('door_date',$door_date)->where('b_factory_id',$b_factory_id);
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
            $data = $data->selectRaw('rept_doorinout_t.*, COUNT(b_supply_id) as amt')->groupby('b_supply_id')->get();
        } else {
            $data = $data->orderby('door_stamp')->get();
        }
//        dd([$b_factory_id,$door_date,$door_type,$cmp,$data]);
        if(count($data))
        {
            foreach ($data as $key => $val)
            {
                $data[$key]['local']            = isset($localAry[$val->b_factory_a_id])? $localAry[$val->b_factory_a_id] : '';
                $data[$key]['store_id']         = $val->b_factory_id;
                $data[$key]['door_stamp']       = substr($val->door_stamp,0,16);
                $data[$key]['job_kind']         = LogLib::getJobKind($val->log_door_inout_id);

                if($mode == 2)
                {
                    $data[$key]['amt'] = 1;
                    $data[$key]['door_type_color']  = isset($doorColorAry[$val->door_type])? $doorColorAry[$val->door_type] : '';
                    $data[$key]['door_type_name']   = isset($doorTypeAry[$val->door_type])? $doorTypeAry[$val->door_type] : '';
                } else {
                    $amt1 = rept_doorinout_t::getMenCount($door_date,[$b_factory_id],1,$val->b_supply_id);
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
    public function genDoorInOutTodayMenHtml($mode = 1, $b_factory_id, $cmp = 0, $localUrl = '')
    {
        $html = $unit = '';
        $rept = [];
        $icon = HtmlLib::genIcon('chevron-circle-right');
        $urlP = 'sid='.SHCSLib::encode($b_factory_id);
        $backUrl = ($mode == 2)? $urlP : '';
        //1. 取得資料
        $listAry = $this->getDoorInOutTodayData($mode,$b_factory_id,$cmp);
//        dd($mode = 1, $b_factory_id,$listAry);

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
