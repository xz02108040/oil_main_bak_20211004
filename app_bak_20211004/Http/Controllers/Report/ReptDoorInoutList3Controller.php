<?php

namespace App\Http\Controllers\Report;

use App\Exports\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\Report\ReptDoorLogListTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_d;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Excel;

class ReptDoorInoutList3Controller extends Controller
{
    use ReptDoorLogListTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ReptDoorInoutList3Controller
    |--------------------------------------------------------------------------
    |
    | 儀表板 - 廠商進出記錄表
    |
    */

    /**
     * 環境參數
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     */
    public function __construct()
    {
        //身分驗證
        $this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'rept_doorinout_t4';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title14');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list14');//大標題

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

    }
    /**
     * 首頁內容
     *
     * @return void
     */
    public function index(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $out = $js ='';
        $no  = 0;
        $listAry   = [];
        $today     = date('Y-m-d');
//        $supplyAry = b_supply::getSelect();
        $localAry  = b_factory_d::getSelect();

        $aid      = $request->aid; //承攬商
        $bid      = $request->bid; //施工區域
        $cid      = 0;
        $sdate    = $request->sdate;
        $edate    = $request->edate;
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = '';
            Session::forget($this->hrefMain);
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate',$today);
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate',$today);
        } else {
            if(strtotime($edate) > strtotime($today)) $edate = $today;
            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate;
            Session::put($this->hrefMain.'.search.edate',$edate);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid',0);
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid','');
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        //view元件參數
        $tbTitle    = $this->pageTitleList;//列表標題
        $hrefMain   = $this->hrefMain;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($sdate && $edate)
        {
            list($listAryCnt,$listAry) = $this->getDoorDayRept([0,0,$aid,$sdate,$edate,$bid,0]);
            if($request->has('showtest'))
            {
                dd([$aid,$bid,$cid,$sdate,$edate],$listAryCnt,$listAry);
            }
            if($request->has('excel') && count($listAry))
            {
                $cellData = [[Lang::get($this->langText.'.rept_203'),
                            Lang::get($this->langText.'.rept_230'),
                            Lang::get($this->langText.'.rept_301'),
                            Lang::get($this->langText.'.rept_11'),
                            Lang::get($this->langText.'.rept_13'),
                            Lang::get($this->langText.'.rept_300'),
                            Lang::get($this->langText.'.rept_308'),
                            Lang::get($this->langText.'.rept_311'),
                    ]];
                foreach($listAry as $value)
                {
                    $tmp          = [];
                    $tmp[]        = $value->unit_name; //
                    $tmp[]        = $value->b_cust_id.'-'.$value->name.'（'.$value->job_kind.'）'; //
                    $tmp[]        = $value->door_stamp; //
                    $tmp[]        = $value->store; //
                    $tmp[]        = $value->door; //
                    $tmp[]        = $value->door_type_name; //
                    $tmp[]        = $value->door_result_name; //
                    $tmp[]        = Lang::get($this->langText.'.rept_312',['name1'=>$value->permit_no]); //
                    $tmp[]        = Lang::get($this->langText.'.rept_313',['name1'=>$value->worker1]); //
                    $tmp[]        = Lang::get($this->langText.'.rept_314',['name1'=>$value->worker2]); //
                    $cellData[]   = $tmp;
                }
                //dd($cellData);
                Session::put('download.exceltoexport',$cellData);
                return Excel::download(new ExcelExport(), 'Door_'.date('Ymdhis').'.ods');
            }

        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //搜尋
        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.rept_8'));
        $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.rept_9'));
        $form->addRowCnt($html);

        $html = $form->select('aid',$localAry,$aid,2,Lang::get($this->langText.'.rept_13'));
        $html.= $form->text('bid',$bid,2,Lang::get($this->langText.'.rept_203'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_25'),'2','excel');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
//        $html = HtmlLib::Color(Lang::get($this->langText.'.rept_100008'),'red',1);
//        $form->addRow($html,10,1);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_203')]; //承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_230')]; //承攬商人員
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_301')]; //進出時間
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_11')]; //廠區
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_13')]; //門別
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_300')]; //進出
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_308')]; //進出結果
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_311')]; //進廠判斷的工單

        $table->addHead($heads,0);
        if(count($listAry))
        {
            $jogColorAry = ['工地負責人'=>'red','安衛人員'=>'red','特殊人員'=>'blue'];
            foreach($listAry as $value)
            {
//                dd($value);
                $no++;
                $id           = $value->id;
                $name1        = $value->unit_name; //
                $jobColor     = isset($jogColorAry[$value->job_kind])? $jogColorAry[$value->job_kind] : 'black';

                $name2        = $value->b_cust_id.'<br/>'.$value->name.'（'.HtmlLib::Color($value->job_kind,$jobColor,1).'）'; //
                $name3        = $value->door_stamp; //
                $name4        = $value->store; //
                $name5        = HtmlLib::Color($value->door_type_name,'black',1); //
                $name6        = (($value->door_result) == 'N') ? HtmlLib::Color($value->door_result_name,'red',1) : $value->door_result_name; //

                $name8        = $value->door; //

                $name7        = ($value->permit_no)? HtmlLib::Color(Lang::get($this->langText.'.rept_312',['name1'=>$value->permit_no]),0,1) : '';
                $name7       .= ($value->worker1)? '<br/>'.HtmlLib::Color(Lang::get($this->langText.'.rept_313',['name1'=>$value->worker1]),0,1) : '';
                $name7       .= ($value->worker2)? '<br/>'.HtmlLib::Color(Lang::get($this->langText.'.rept_314',['name1'=>$value->worker2]),0,1) : '';

                //$param        = 'img1='.SHCSLib::encode($value->b_cust_id).'&img2='.SHCSLib::encode($id);
                //$name9        = HtmlLib::btn(SHCSLib::url('rept_door_img','',$param),Lang::get('sys_btn.btn_30'),1,'','','','_blank'); //按鈕


                $tBody[] = [
                    '1'=>[ 'name'=> $name1],
                    '2'=>[ 'name'=> $name2],
                    '3'=>[ 'name'=> $name3],
                    '4'=>[ 'name'=> $name4],
                    '8'=>[ 'name'=> $name8],
                    '5'=>[ 'name'=> $name5],
                    '6'=>[ 'name'=> $name6],
                    '7'=>[ 'name'=> $name7],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $out .= $table->output();
        unset($table);


        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTitle,$out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#sdate,#edate").datepicker({
                        format: "yyyy-mm-dd",
                        language: "zh-TW"
                    });
                    $("#table1").DataTable({
                        "language": {
                        "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
                    }
                    });

                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }


}
