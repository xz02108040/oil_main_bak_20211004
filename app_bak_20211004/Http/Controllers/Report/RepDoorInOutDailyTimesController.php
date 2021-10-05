<?php

namespace App\Http\Controllers\Report;

use App\Exports\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\Report\ReptDoorInOutDailyTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Report\rept_doorinout_time;
use App\Model\Supply\b_supply;
use App\Model\sys_code;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Excel;

class RepDoorInOutDailyTimesController extends Controller
{
    use ReptDoorInOutDailyTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | RepDoorInOutDailyTimesController
    |--------------------------------------------------------------------------
    |
    | 報表 - 每日承攬商成員進出時數累計
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
        $this->hrefMain         = 'rept_doorinout_day';
        $this->hrefMain2        = 'rept_doorinout_gen';
        $this->hrefExcel        = 'rept_doorinout_dayexcel';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title15');//大標題
        $this->pageTitleMain2   = Lang::get($this->langText.'.gen15');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list15');//大標題
        $this->pageTitleList2   = Lang::get($this->langText.'.genlist15');//大標題
        $this->pageExcelTitle   = Lang::get($this->langText.'.title15');//大標題

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_76');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageExcelBtn     = Lang::get('sys_btn.btn_29');//[按鈕]下載

    }

    /**
     * 下載Excel
     * @return excel
     */
    protected function downExcel()
    {
        $aid   = Session::get($this->hrefMain.'.search.aid',0);
        $sdate = Session::get($this->hrefMain.'.search.sdate','');
        $edate = Session::get($this->hrefMain.'.search.edate','');
        if(!$aid)
        {
            \Redirect::back()->withErrors(Lang::get('sys_base.base_10122'));
        }

        [$listCnt,$listAry] = $this->getDoorInOutDailyList(1,$sdate,$edate,$aid);
        //dd($bid,$aid,$sdate,$edate,$pid,$listAry);
        if(count($listAry))
        {
            $excelAry = $header = [];
            $header[] = Lang::get($this->langText.'.rept_203');
            $header[] = Lang::get($this->langText.'.rept_305');
            $header[] = Lang::get($this->langText.'.rept_230');
            $header[] = Lang::get($this->langText.'.rept_311');
            $header[] = Lang::get($this->langText.'.rept_319');
            $excelAry[] = $header;
            foreach ($listAry as $value)
            {
                $total_min  = SHCSLib::getHoursMinutes($value->total_times);
                $excelAry[] = [$value->supply,$value->door_date,$value->name,$total_min,$value->total_times];
            }
//            dd($excelAry);
            Session::put('download.exceltoexport',$excelAry);

            if(Session::has('download.exceltoexport'))
            {
                return Excel::download(new ExcelExport(), $this->pageExcelTitle.'_'.date('Ymdhis').'.xlsx');
            }
            \Redirect::back()->withErrors(Lang::get('sys_base.base_10122'));
        }
        //\Redirect::back()->withErrors(Lang::get('sys_base.base_10122'));
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
        $today     = date('Y-m-01');
        $today2    = date('Y-m-t');
        $supplyAry = b_supply::getSelect();

        $aid      = $request->aid; //承攬商
        $sid      = $request->sid; //使用者
        $type     = $request->type; //使用者
        if(!$type) $type = 1;
        $tdate    = $request->today; //使用者
        if($sid) $sid = SHCSLib::decode($sid);
        $sdate    = $request->sdate;
        $edate    = $request->edate;
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = '';
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
            $edate = Session::get($this->hrefMain.'.search.edate',$today2);
        } else {
            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate;
            Session::put($this->hrefMain.'.search.edate',$edate);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid',0);
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        //view元件參數
        $tbTitle    = $this->pageTitleList;//列表標題
        $hrefMain   = $this->hrefMain;
        $hrefNew    = $this->hrefMain2;
        $btnNew     = $this->pageEditBtn;
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $hrefExcel  = $this->hrefExcel;
        $btnExcel   = $this->pageExcelBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($aid && ($sdate && $edate))
        {
            if(!$sid)
            {
                $isGroup   = ($type == 2)? 'N' : 'Y';
                $startDate = ($type == 2)? $tdate : $sdate;
                $endDate   = ($type == 2)? $tdate : $edate;
                list($listAryCnt,$listAry) = $this->getDoorInOutDailyList(1,$startDate,$endDate,$aid,0,$isGroup);

            } else {
                list($listAryCnt,$listAry) = $this->getDoorInOutTimesList(1,$tdate,$tdate,$aid,$sid);
            }
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if(!$sid) $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //搜尋
        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.rept_8'));
        $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.rept_9'));
        $html.= $form->select('aid',$supplyAry,$aid,2,Lang::get($this->langText.'.rept_203'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        if($type == 1 && count($listAry))
        {
            $html.= $form->linkbtn($hrefExcel, $btnExcel,6); //下載
        }
        if($sid)
        {
            $html.= $form->linkbtn($hrefBack.'?type=2&today='.$today, $btnBack,2); //返回
        }elseif($type == 2)
        {
            $html.= $form->linkbtn($hrefBack, $btnBack,2); //返回
        }
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.rept_100001'),'red',1);
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        $totalTimeStr = ($sid)? 'rept_317' : 'rept_311';
        //標題

        $heads[] = ['title'=>Lang::get($this->langText.'.rept_203')]; //承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_305')]; //進出日期
        if($type == 1 && !$sid)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_318')]; //進出人數
            $heads[] = ['title'=>Lang::get($this->langText.'.'.$totalTimeStr)]; //累計時數(分)
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_230')]; //承攬商成員
            $heads[] = ['title'=>Lang::get($this->langText.'.'.$totalTimeStr)]; //累計時數(分)
            if($sid)
            {
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_302')]; //進廠時間
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_303')]; //離廠時間
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_308')]; //進出結果
            } else {
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_316')]; //進廠時間
            }
        }

        $isFun = ($sid)? 0 : 1;
        $table->addHead($heads,$isFun);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $total_min    = ($sid)? round($value->door_times/60,1) : SHCSLib::getHoursMinutes($value->total_times);

                $name1        = $value->supply; //
                $name2        = $value->door_date; //
                if($type == 1 && !$sid)
                {
                    $name3        = $value->amt; //
                    $name4        = HtmlLib::Color($total_min,'',1).' ('.$value->total_times.' sec)'; //
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','type=2&today='.$name2),Lang::get('sys_btn.btn_27'),1); //按鈕

                } else {
                    $id           = SHCSLib::encode($value->b_cust_id);
                    $name3        = $value->name; //
                    $name4        = HtmlLib::Color($total_min,'',1).' ('.$value->total_times.' sec)'; //
                    $name5        = (!$sid)? rept_doorinout_time::getErrCnt(1,$value->b_cust_id,$name2,$name2) : '';
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','type=2&sid='.$id.'&today='.$name2),Lang::get('sys_btn.btn_27'),1); //按鈕

                }
//                dd($value);
                if($sid)
                {
                    $name2        = $value->door_date1; //
                    $name3        = $value->name; //
                    $name4        = HtmlLib::Color($total_min,'',1).' ('.$value->door_times.' sec)';; //
                    $name6        = $value->door_stamp1; //
                    $name7        = $value->door_stamp2; //
                    $name8        = ($value->result == 'Y')? Lang::get($this->langText.'.rept_312') : Lang::get($this->langText.'.rept_313'); //
                    $name8_color  = ($value->result == 'Y')? 2 : 5; //
                    $tBody[] = [
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '6'=>[ 'name'=> $name6],
                        '7'=>[ 'name'=> $name7],
                        '8'=>[ 'name'=> $name8,'label'=>$name8_color],
                    ];
                } else {
                    if($type == 1)
                    {
                        $tBody[] = [
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '99'=>[ 'name'=> $btn],
                        ];
                    } else {
                        $tBody[] = [
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '99'=>[ 'name'=> $btn],
                        ];
                    }

                }


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

    /**
     * 首頁內容
     *
     * @return void
     */
    public function gen(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $out = $js ='';
        $retMemo = '';
        $listAry   = [];
        $today     = date('Y-m-d');
        $supplyAry = b_supply::getSelect();

        $aid      = $request->aid; //承攬商
        $sdate    = $request->sdate;
        $edate    = $request->edate;
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = '';
            Session::forget($this->hrefMain);
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate1','');
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain.'.search.sdate1',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate1','');
        } else {
            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate;
            Session::put($this->hrefMain.'.search.edate1',$edate);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid1',0);
        } else {
            Session::put($this->hrefMain.'.search.aid1',$aid);
        }
        //view元件參數
        $tbTitle    = $this->pageTitleList2;//列表標題
        $hrefMain   = $this->hrefMain2;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(($sdate && $edate))
        {
            $cntDate = $sdate;
            do{
                list($ret1,$reply) = $this->genDoorInOutTimes(1,$cntDate,$aid);
                list($ret2,$reply) = $this->genDoorInOutDaily(1,$cntDate,$aid);

                $retMemo .= Lang::get($this->langText.'.rept_314',['date'=>$cntDate,'amt'=>$ret1]).'<br/>';
                $cntDate = SHCSLib::addDay(1,$cntDate);
            }while(strtotime($cntDate) < strtotime($edate));
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //搜尋
        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.rept_8'));
        $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.rept_9'));
        $html.= $form->select('aid',$supplyAry,$aid,2,Lang::get($this->langText.'.rept_203'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $html.= $form->linkbtn($hrefBack, $btnBack,2); //返回
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.rept_100005'),'red',1);
        $form->addRowCnt($html);
        $form->addHr();
        if($retMemo)
        {
            $form->addRowCnt(Lang::get($this->langText.'.rept_315',['memo'=>$retMemo]));
        }
        //輸出
        $out .= $form->output(1);
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
        $retArray = ["title"=>$this->pageTitleMain2,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }
}
