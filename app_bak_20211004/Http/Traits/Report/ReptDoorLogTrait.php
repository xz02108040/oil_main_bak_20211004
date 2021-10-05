<?php

namespace App\Http\Traits\Report;

use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_d;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use App\Model\View\view_log_door_today;
use App\Model\WorkPermit\wp_work_worker;
use Storage;
use DB;
use Lang;
use Session;

/**
 * 報表：門禁進出詳細紀錄[當日]
 *
 */
trait ReptDoorLogTrait
{
    public function genDoorLogHtml($b_factory_d_id,$isLimit = 100)
    {
        $listAry        = $this->getDoorLogList($b_factory_d_id,$isLimit);
        $local          = b_factory_d::getName($b_factory_d_id);
        $today          = date('Y-m-d');
        $firstTag       = 1;
        $door_total_men = rept_doorinout_t::getLocalMenCount($today,$b_factory_d_id);
        $door_total_men_html = '<span class="btn-success" style="border-radius: 15px; -webkit-border-radius: 15px; -moz-border-radius: 15px;font-size: 1.9em">&emsp;&emsp;<b>'.$door_total_men.'</b>&emsp;&emsp;</span>';
        $door_search_html = '<div class="active-pink-4" style="float:right"><input id="search_btn" class="form-control" type="text" placeholder="搜尋" aria-label="Search"></div>';

        $last_record    = '';
        //table
        $table = new TableLib('#','report_table','table-responsive');
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get('sys_rept.rept_1'),'align'=>'center']; //公司名稱
        $heads[] = ['title'=>Lang::get('sys_rept.rept_2'),'align'=>'center']; //成員姓名
        $heads[] = ['title'=>Lang::get('sys_rept.rept_3'),'align'=>'center']; //身份
        $heads[] = ['title'=>Lang::get('sys_rept.rept_4'),'align'=>'center']; //日期
        $heads[] = ['title'=>Lang::get('sys_rept.rept_5'),'align'=>'center']; //時間
        $heads[] = ['title'=>Lang::get('sys_rept.rept_6'),'align'=>'center']; //結果
        $heads[] = ['title'=>Lang::get('sys_rept.rept_7'),'align'=>'center']; //狀態
        $heads[] = ['title'=>Lang::get('sys_rept.rept_306'),'align'=>'center']; //照片

        $table->addHead($heads,0);
        if(count($listAry))
        {
            $identityA = Lang::get('sys_base.base_40231');
            $identityB = Lang::get('sys_base.base_40232');
            $userName1 = Lang::get($this->langText.'.rept_203');
            $userName2 = Lang::get($this->langText.'.rept_306');
            $userName3 = Lang::get($this->langText.'.rept_307');
            foreach($listAry as $value)
            {
                if($value->job_kind == $identityA)
                {
                    $job_color = 'red';
                }elseif($value->job_kind == $identityB)
                {
                    $job_color = 'red';
                } else {
                    $job_color = '';
                }

                $no           = $value->no;
                $id           = $value->id;
                $name1        = $value->unit_name; //
                $name2        = HtmlLib::Color($value->name,$job_color,1); //
                $name2H       = $value->name; //
                $name3        = HtmlLib::Color($value->job_kind,$job_color,1); //
                $name3H       = $value->job_kind; //
                $name4        = $value->date; //
                $name5        = HtmlLib::Color($value->time,'',1);  //
                $name6        = HtmlLib::Color($value->door_type,'',1); //
                $name7        = HtmlLib::Color($value->door_result,'',1); //

                $param        = 'img1='.$value->img1.'&img2='.$value->img2;
                $name9        = HtmlLib::btn(SHCSLib::url('rept_door_img','',$param),Lang::get('sys_btn.btn_30'),1,'','','','_blank'); //按鈕
                if($firstTag == 1)
                {
                    $last_record = $name1.'，'.$name2H.'，'.$name3H.'，'.$name6.'，'.$name4.' '.$name5;
                    $last_record = '<span class="btn-danger" style="border-radius: 15px; -webkit-border-radius: 15px; -moz-border-radius: 15px;font-size: 1.9em">&emsp;<b>'.$last_record.'</b>&emsp;</span>';
                    $firstTag++;
                }
                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                    '1'=>[ 'name'=> $name1],
                    '2'=>[ 'name'=> $name2,'class'=>'search'],
                    '3'=>[ 'name'=> $name3],
                    '4'=>[ 'name'=> $name4],
                    '5'=>[ 'name'=> $name5],
                    '6'=>[ 'name'=> $name6],
                    '7'=>[ 'name'=> $name7],
                    '9'=>[ 'name'=> $name9],
                ];
            }
            $table->addBody($tBody);
        }

        //輸出ＨＴＭＬ
        $ret  = '<div class="loglist">';
        //最後刷卡人員
        $ret .= '<div class="last_record_div"><span class="last_record_title" style="font-size: 1.2em;font-weight:bold;">'.Lang::get('sys_base.base_40214').'</span>'.$last_record.'</div>';
        $ret .= '<div><span class="door_total_men_title" style="font-size: 1.2em;font-weight:bold;">'.Lang::get('sys_base.base_40215',['store'=>$local]).'</span>'.$door_total_men_html.$door_search_html.'</div>';
        $ret .= '<div style="width:100%;height: 530px;overflow:auto;"><p>'.$table->output().'</div></p></div>';
        return $ret;
    }
    /**
     * 新增 當日 廠區儀表板html
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getDoorLogList($b_factory_d_id,$isLimit = 1,$mode = 'M',$isApi = 0)
    {
        $ret            = [];
        $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
        $doorResultAry  = SHCSLib::getCode('DOOR_INOUT_RESULT');
        $maxLimit       = sys_param::getParam('REPT_DOOR_INOUT_LIST2_MAX',99);
        $table          = ($mode == 'C')? 'view_log_door_today_car as log' : 'view_log_door_today as log';

        $data = DB::table($table)->join('b_factory as f','f.id','=','log.b_factory_id')->
                join('b_factory_d as d','d.id','=','log.b_factory_d_id')->
                where('log.b_factory_d_id',$b_factory_d_id)->
                select('log.*','f.name as store','d.name as local','log.wp_work_id');
        $totalAmt = $data->count();

        if($isLimit)
        {
            $data = $data->limit($maxLimit);
        }
        $data = $data->orderby('log.log_id','desc')->get();
        if(count($data))
        {
            $no = $totalAmt;
            foreach( $data as $key => $value)
            {
                $memo = ($value->door_result != 'Y')? $value->door_memo : '';
                $workRoot = wp_work_worker::getWorkInfo($value->wp_work_id);
                if($isApi)
                {
                    list($project,$project_no) = e_project::getNameList($value->e_project_id);
                    $tmp = [];
                    $tmp['no']          = $no;
                    $tmp['project']     = $project;
                    $tmp['project_no']  = $project_no;
                    $tmp['work_id']     = $value->wp_work_id;
                    $tmp['job_kind']    = $value->job_kind;
                    $tmp['unit_name']   = $value->unit_name;
                    $tmp['name']        = $value->name;
                    $tmp['store']       = $value->store;
                    $tmp['door']        = $value->local;
                    $tmp['door_type']   = isset($doorTypeAry[$value->door_type])? $doorTypeAry[$value->door_type] : '';
                    $tmp['door_result'] = isset($doorResultAry[$value->door_result])? $doorResultAry[$value->door_result].'：'.$memo : '';
                    $tmp['door_memo']   = strlen($value->door_memo)? $value->door_memo : '';
                    $tmp['date']        = substr($value->door_stamp,0,10);
                    $tmp['time']        = substr($value->door_stamp,11,8);
                    $img_url            = (strlen($value->unit_id) > 8)? 'img/User/' : 'img/Car/';
                    $img_param          = (strlen($value->unit_id) > 8)? '' : '?type=C';
                    $isHttp             = strpos($value->img_path,'http') !== false ? 1 : 0;
                    $tmp['img1']        = url($img_url.SHCSLib::encode($value->unit_id));
                    $tmp['img2']        = $isHttp? $value->img_path : url('img/Door/'.SHCSLib::encode($value->log_id).$img_param);
                    $tmp['permit_no']   = isset($workRoot['no'])? $workRoot['no'] : '';
                    $tmp['worker1']     = isset($workRoot['worker1'])? $workRoot['worker1'] : '';
                    $tmp['worker2']     = isset($workRoot['worker2'])? $workRoot['worker2'] : '';
                    $ret[] = $tmp;
                } else {
                    $data[$key]['id']           = $value->log_id;
                    $data[$key]['no']           = $no;
                    $data[$key]['store']        = $value->store;
                    $data[$key]['door']         = $value->local;
                    $data[$key]['door_type']    = isset($doorTypeAry[$value->door_type])? $doorTypeAry[$value->door_type] : '';
                    $data[$key]['door_result']  = isset($doorResultAry[$value->door_result])? $doorResultAry[$value->door_result].$memo : '';
                    $data[$key]['door_memo']    = strlen($value->door_memo)? $value->door_memo : '';
                    $data[$key]['date']         = substr($value->door_stamp,0,10);
                    $data[$key]['time']         = substr($value->door_stamp,11,8);
                    $img_url                    = (strlen($value->unit_id) > 8)? 'img/User/' : 'img/Car/';
                    $img_param                  = (strlen($value->unit_id) > 8)? '' : '?type=C';
                    $isHttp                     = strpos($value->img_path,'http') !== false ? 1 : 0;
                    $data[$key]['img1']        = url($img_url.SHCSLib::encode($value->unit_id));
                    $data[$key]['img2']        = $isHttp? $value->img_path : url('img/Door/'.SHCSLib::encode($value->log_id).$img_param);
                    $data[$key]['permit_no']   = isset($workRoot['no'])? $workRoot['no'] : '';
                    $data[$key]['worker1']     = isset($workRoot['worker1'])? $workRoot['worker1'] : '';
                    $data[$key]['worker2']     = isset($workRoot['worker2'])? $workRoot['worker2'] : '';
                }

                if($no > 1) $no--;
            }
            if(!$isApi) $ret = (object)$data;
        }

        return $ret;
    }
}
