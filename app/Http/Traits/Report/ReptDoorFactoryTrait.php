<?php

namespace App\Http\Traits\Report;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_d;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use Storage;
use DB;
use Lang;

/**
 * 報表：當日 廠區儀表板
 *
 */
trait ReptDoorFactoryTrait
{



    /**
     * 產生 當日 廠區儀表板html
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genDoorInOutFactoryHtml($mode = 'M',$store = 0,$today = '',$sizetype = 1)
    {
        $html   = '';
        $reptAry= [];
        $no1    = 1;
        $no2    = 4;
        if(!$today) $today = date('Y-m-d');
        if(!$store) $store = 6;

        $storeAry = b_factory_d::getSelect($store,0);
        $listAry1 = $this->getDoorMenData($today,$store);
        $listAry2 = $this->getDoorCarData($today,$store);
        //dd([$store,$storeAry,$listAry1,$listAry2]);
        //取得廠區今日進出紀錄<在廠>
        foreach ($storeAry as $sid => $name)
        {
            if($no1 < 4)
            {
                $reptAry[$no1]['name'] = $name;
                $reptAry[$no1]['url']  = url('rept_doorinout_t?sid=').SHCSLib::encode($sid).'&store='.$sid;
                $reptAry[$no1]['amt']  = isset($listAry1[$sid])? $listAry1[$sid]['amt'] : 0;

                $reptAry[$no2]['name'] = $name;
                $reptAry[$no2]['url']  =url('rept_doorinout_t?type=C&sid=').SHCSLib::encode($sid);
                $reptAry[$no2]['amt']  = isset($listAry2[$sid])? $listAry2[$sid]['amt'] : 0;
                $no1++;
                $no2++;
            }
        }

        if(count($reptAry))
        {
            foreach ($reptAry as $no => $val)
            {
                $title = $val['name'];
                $cont  = $val['amt'];
                $url   = $val['url'];
                if($sizetype == 2)
                {
                    $html .= HtmlLib::genHttcDoorReportDiv_1024($mode,$no,$title,$cont,$url);
                } else {
                    $html .= HtmlLib::genHttcDoorReportDiv_1920($mode,$no,$title,$cont,$url);
                }
            }
        }

        return $html;
    }

    /**
     * 產生 當日 廠區儀表板 資料 2021-01-11
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genDoorInOutFactoryApi($store_id = 0,$door_id = 0,$supply_id = 0,$today = '',$isTest = 0)
    {
        $reptAry= [];
        $no1    = 1;
        if(!$today) $today = date('Y-m-d');
        if(!$store_id) return $reptAry;
        if(!$store_id && !$door_id && !$supply_id) return $reptAry;
        $level = ($store_id && !$door_id)? 2 : (($store_id && $door_id && $supply_id)? 4 : 3);
        $doorType = ($level > 2)? 0 : 1;
        //dd($today,$store_id,$door_id,$supply_id,$doorType,$level);
        $menAry         = $this->getDoorMenInOutTodayFactoryData($today,$store_id,$door_id,$supply_id,$doorType,$level,'',$isTest);
        $carAry         = $this->getDoorCarInOutTodayFactoryData($today,$store_id,$door_id,$supply_id,$doorType,$level,'',$isTest);
        $workAry        = $this->getPermitTodayFactoryData($today,$store_id,$door_id,$supply_id,[],$level);

        //門別
        if($level == 2)
        {
            $doorAry = b_factory_d::getSelect($store_id,0);
            if(!count($doorAry)) return $reptAry;
            foreach ($doorAry as $key => $val)
            {
                $reptAry[$key]['id']   = $key;
                $reptAry[$key]['name'] = $val;
                $reptAry[$key]['men']['in']  = 0;
                $reptAry[$key]['men']['out'] = 0;
                $reptAry[$key]['car']['in']  = 0;
                $reptAry[$key]['car']['out'] = 0;
                $reptAry[$key]['work']       = 0;
            }
            if($isTest == 1) $reptAry0 = $reptAry;
            if(count($menAry))
            {
                foreach ($menAry as $val)
                {
                    $array_key = $val['id'];
                    $array_val = $val['amt'];
                    if(isset($reptAry[$array_key]))
                    {
                        $reptAry[$array_key]['men']['in']  = $array_val;
                    }
                }
            }
            if($isTest == 1) dd($reptAry0,$reptAry,$menAry);
            if(count($carAry))
            {
                foreach ($carAry as $val)
                {
                    $array_key = $val['id'];
                    $array_val = $val['amt'];
                    if(isset($reptAry[$array_key]))
                    {
                        $reptAry[$array_key]['car']['in']  = $array_val;
                    }
                }
            }
            if(count($workAry))
            {
                foreach ($workAry as $val)
                {
                    $array_key = $val['id'];
                    $array_val = $val['amt'];
                    if(isset($reptAry[$array_key]))
                    {
                        $reptAry[$array_key]['work']       = $array_val;
                    }
                }
            }

        }
        //承攬商
        if($level == 3)
        {
            foreach ($menAry as $val)
            {
                $array_key  = $val['id'];
                $array_val  = $val['amt'];
                $array_name = $val['name'];
                $array_inout= $val['door_type'];
                if(!isset($reptAry[$array_key]))
                {
                    $reptAry[$array_key]['id']            = $array_key;
                    $reptAry[$array_key]['name']          = $array_name;
                    $reptAry[$array_key]['men']['in']     = ($array_inout == 1)? $array_val : 0;
                    $reptAry[$array_key]['men']['out']    = ($array_inout == 2)? $array_val : 0;
                    $reptAry[$array_key]['car']['in']     = 0;
                    $reptAry[$array_key]['car']['out']    = 0;
                    $reptAry[$array_key]['work']          = 0;
                } else {
                    if($array_inout == 1) $reptAry[$array_key]['men']['in']     = $array_val;
                    if($array_inout == 2) $reptAry[$array_key]['men']['out']    = $array_val;
                }
            }
            foreach ($carAry as $val)
            {
                $array_key  = $val['id'];
                $array_val  = $val['amt'];
                $array_name = $val['name'];
                $array_inout= $val['door_type'];
                if(!isset($reptAry[$array_key]))
                {
                    $reptAry[$array_key]['id']            = $array_key;
                    $reptAry[$array_key]['name']          = $array_name;
                    $reptAry[$array_key]['car']['in']     = (in_array($array_inout,[1,3]))? $array_val : 0;
                    $reptAry[$array_key]['car']['out']    = (in_array($array_inout,[2,4]))? $array_val : 0;
                    $reptAry[$array_key]['men']['in']     = 0;
                    $reptAry[$array_key]['men']['out']    = 0;
                    $reptAry[$array_key]['work']          = 0;
                } else {
                    if($array_inout == 1) $reptAry[$array_key]['car']['in']     = $array_val;
                    if($array_inout == 2) $reptAry[$array_key]['car']['out']    = $array_val;
                }
            }
            foreach ($workAry as $val)
            {
                $array_key  = $val['id'];
                $array_val  = $val['amt'];
                $array_name = $val['name'];
                if(!isset($reptAry[$array_key]))
                {
                    $reptAry[$array_key]['id']            = $array_key;
                    $reptAry[$array_key]['name']          = $array_name;
                    $reptAry[$array_key]['car']['in']     = 0;
                    $reptAry[$array_key]['car']['out']    = 0;
                    $reptAry[$array_key]['men']['in']     = 0;
                    $reptAry[$array_key]['men']['out']    = 0;
                    $reptAry[$array_key]['work']          = $array_val;
                } else {
                    $reptAry[$array_key]['work'] = $array_val;
                }
            }
        }
        //承攬商成員
        if($level == 4)
        {
            $reptAry['men'] = $menAry;
            $reptAry['car'] = $carAry;
            $reptAry['work'] = $workAry;
        }
        return $reptAry;
    }

    /**
     * 產生 當日 廠區儀表板 資料 2021-01-18
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genDoorInOutFactoryAppApi($store_id = 0,$door_id = 0,$supply_id = 0,$mode = 'M',$level = 3,$today = '')
    {
        $reptAry= [];
        $no1    = 1;
        if(!$today) $today = date('Y-m-d');
        //if(!$store_id) return $reptAry;
        if(!$store_id && !$door_id && !$supply_id) return $reptAry;
        $store      = b_factory::getName($store_id);
        $modeAry    = ['M'=>'人','C'=>'車'];
        $modeName   = isset($modeAry[$mode])? $modeAry[$mode] : '';
        $doorType   = 1;
        $mode       = strtoupper($mode);
        if($mode == 'C')
        {
            $dataAry         = $this->getDoorCarInOutTodayFactoryData($today,$store_id,$door_id,$supply_id,$doorType,$level);

        } else {
            $dataAry         = $this->getDoorMenInOutTodayFactoryData($today,$store_id,$door_id,$supply_id,$doorType,$level);

        }
//        dd(['level'=>$level,'door_type'=>$doorType,'dataAry'=>$dataAry]);


        //承攬商

        foreach ($dataAry as $val)
        {
            $tmp = [];
            if($level == 3) {
                $tmp['headline']    = $store;
                $tmp['title']       = $val['name'];
                $tmp['amt']         = $val['amt'];
                $tmp['unit']        = $modeName;
                $tmp['b_supply_id'] = $val['id'];
            }
            if($level == 4)
            {
                $tmp['headline']        = $val['unit_name'];
                $tmp['title']           = $val['name'];
                $tmp['door_stamp']      = $val['door_stamp'];
                $tmp['door_name']       = $val['door'];
                $tmp['door_type_name']  = $val['door_type_name'];
                $tmp['job_kind']        = $val['job_kind'];
            }

            $reptAry[] = $tmp;
        }
        //承攬商成員

        return $reptAry;
    }
}
