<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Traits\Report\ReptDoorLogListTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\sys_code;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class ReptDoor3Controller extends Controller
{
    use SessTraits,ReptDoorLogListTrait;
    /*
    |--------------------------------------------------------------------------
    | ReptDoor3Controller
    |--------------------------------------------------------------------------
    |
    | [紀錄]進出廠紀錄_異常_未離場
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
        $this->hrefMain         = 'rept_door_l3';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title6');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list6');//列表

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
        $no        = $listAryCnt = 0;
        $listAry   = [];
        $today     = date('Y-m-d');
        $supplyAry = b_supply::getSelect();
        $storeAry  = b_factory::getSelect();

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $sid       = $request->has('sid')? SHCSLib::decode($request->sid) : 0; //

        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = '';
            Session::forget($this->hrefMain.'.search');
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate',$today);
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
//        if(!$edate)
//        {
//            $edate = Session::get($this->hrefMain.'.search.edate',$today);
//        } else {
//            if(strtotime($edate) > strtotime($today)) $edate = $today;
//            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate;
//            Session::put($this->hrefMain.'.search.edate',$edate);
//        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid',0);
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid',0);
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(($bid || $aid))
        {
            if(!$sid)
            {
                list($listAryCnt,$listAry) = $this->getDoorDayErr2Rept([$aid,$bid,0,$sdate,''],1);
            } else {
                list($listAryCnt,$listAry) = $this->getDoorDayErr2Rept([$aid,$sid,0,$sdate,'']);
            }

            if($request->has('showtest'))
            {
                dd([$aid,$bid,0,$sdate,''],$listAry);
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->date('sdate',$sdate,3,Lang::get($this->langText.'.rept_305'));
        //$html.= $form->date('edate',$edate,3,Lang::get($this->langText.'.rept_9'));
        $form->addRowCnt($html);

        $html = $form->select('aid',$storeAry,$aid,3,Lang::get($this->langText.'.rept_11'));
        $html.= $form->select('bid',$supplyAry,$bid,3,Lang::get($this->langText.'.rept_203'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','','');
        if($sid)
        {
            $html.= $form->linkbtn($hrefBack, $btnBack,2); //返回
        }
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.rept_100001'),'red',1);
        $form->addRow($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_11')]; //廠區

        if(!$sid)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_10')]; //承攬商
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_309')]; //數量
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_10')]; //承攬商
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_202')]; //工程案件
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_230')]; //承商人員
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_310')]; //進出時間
        }
        $isBtn = (!$sid)? 1 : 0;
        $table->addHead($heads,$isBtn);
        if($listAryCnt)
        {
            $userName1 = Lang::get($this->langText.'.rept_203');
            $userName2 = Lang::get($this->langText.'.rept_306');
            $userName3 = Lang::get($this->langText.'.rept_307');
            $Icon      = HtmlLib::genIcon('caret-square-o-right');
            foreach($listAry as $value)
            {
                $no++;

                if(!$sid)
                {
                    $id              = SHCSLib::encode($value->b_supply_id);
                    $rept1           = $value->store;
                    $rept2           = $value->supply;
                    $rept4           = $value->amt;
                    $btn             = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','sid='.$id),Lang::get('sys_btn.btn_27'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $rept1],
                        '2'=>[ 'name'=> $rept2],
                        '4'=>[ 'name'=> $rept4],
                        '99'=>[ 'name'=> $btn],
                    ];
                } else {
                    $id              = $value->id;
                    $rept1           = $value->store;
                    $rept2           = $value->supply;
                    $rept3           = $value->project;
                    $rept4           = $value->name;
                    $rept6           = $value->door_stamp;

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $rept1],
                        '2'=>[ 'name'=> $rept2],
                        '3'=>[ 'name'=> $rept3],
                        '4'=>[ 'name'=> $rept4],
                        '6'=>[ 'name'=> $rept6],
                    ];
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
        $content->rowTo($content->box_table($tbTile,$out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table1").DataTable({
                        "language": {
                        "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
                    }
                    });
                    $("#sdate").datepicker({
                        format: "yyyy-mm-dd",
                        language: "zh-TW"
                    });
                    $( "#did" ).change(function() {
                        var eid = $("#did").val();
                        
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 2, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#uid option").remove();
                             $.each(result, function(key, val) {
                                $("#uid").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("'.Lang::get('sys_base.table_lan').'");
                          }
                        });
             });
                    
                } );';

        $css = '
                .report_table {
                  border-collapse: collapse;
                  width: 100%;
                }
                
                .report_table td, .report_table th {
                  border: 1px solid #ddd;
                  padding: 8px;
                }
                
                .report_table tr:nth-child(even){background-color: #f2f2f2;}
                .report_table tr:nth-child(odd){background-color: #D9DCC6;}
                
                .report_table tr:hover {background-color: #e1f2d5;}
                
                .report_table th {
                  padding-top: 12px;
                  padding-bottom: 12px;
                  text-align: left;
                  background-color: #1B5045;
                  color: white;
                }
             
            ';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js,'css'=>$css];

        return view('index',$retArray);
    }

}
