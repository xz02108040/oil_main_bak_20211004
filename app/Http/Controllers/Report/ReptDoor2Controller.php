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

class ReptDoor2Controller extends Controller
{
    use SessTraits,ReptDoorLogListTrait;
    /*
    |--------------------------------------------------------------------------
    | ReptDoor2Controller
    |--------------------------------------------------------------------------
    |
    | [紀錄]進出廠紀錄_異常
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
        $this->hrefMain         = 'rept_door_l2';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title5');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list5');//列表

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
        $errAry    = SHCSLib::getCode('DOOR_ERR_CODE',1);

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $eid       = $request->has('eid')? SHCSLib::decode($request->eid) : 0; //
        $err_code  = $request->err_code; //

        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $err_code = '';
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
        if(!$err_code)
        {
            $err_code = Session::get($this->hrefMain.'.search.err_code','');
        } else {
            Session::put($this->hrefMain.'.search.err_code',$err_code);
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
            if(!$eid)
            {
                list($listAryCnt,$listAry) = $this->getDoorDayErrRept([$aid,$bid,0,$sdate,'',$err_code],1);
            } else {
                list($listAryCnt,$listAry) = $this->getDoorDayErrRept([$aid,$bid,0,$sdate,'',$eid]);
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
        $html.= $form->select('err_code',$errAry,$err_code,3,Lang::get($this->langText.'.rept_235'));
        //$html.= $form->date('edate',$edate,3,Lang::get($this->langText.'.rept_9'));
        $form->addRowCnt($html);

        $html = $form->select('aid',$storeAry,$aid,3,Lang::get($this->langText.'.rept_11'));
        $html.= $form->select('bid',$supplyAry,$bid,3,Lang::get($this->langText.'.rept_203'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','','');
        if($eid)
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

        if(!$eid)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_308')]; //進出結果
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_309')]; //數量
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_10')]; //承攬商
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_202')]; //工程案件
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_230')]; //承商人員
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_300')]; //進出狀態
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_301')]; //進出時間
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_308')]; //進出結果
        }
        $table->addHead($heads,1);
        if($listAryCnt)
        {
            $userName1 = Lang::get($this->langText.'.rept_203');
            $userName2 = Lang::get($this->langText.'.rept_306');
            $userName3 = Lang::get($this->langText.'.rept_307');
            $Icon      = HtmlLib::genIcon('caret-square-o-right');
            foreach($listAry as $value)
            {
                $no++;

                if(!$eid)
                {
                    $id              = SHCSLib::encode($value->err_code);
                    $rept1           = $value->store;
                    $rept2           = $value->door_memo;
                    $rept3           = $value->amt;
                    $btn             = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','eid='.$id),Lang::get('sys_btn.btn_27'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $rept1],
                        '2'=>[ 'name'=> $rept2],
                        '3'=>[ 'name'=> $rept3],
                        '99'=>[ 'name'=> $btn],
                    ];
                } else {
                    $id              = $value->id;
                    $rept1           = $value->store;
                    $rept2           = $value->unit_name;
                    $rept3           = $value->project;
                    $rept4           = $value->name;
                    $rept5           = $value->door_type_name.HtmlLib::color($value->isOnline_name,'red');
                    $rept6           = $value->door_stamp;
                    $rept7           = $value->door_result_name;
                    $userMemo        = $Icon.$value->name.$Icon.$value->door_type_name.$Icon.$value->door_stamp;

                    $hideImgHtml = '<table class="report_table" style="width: 100%"><tr><th align="center" style="width: 50%">'.$userName1.'</th><th  align="center">'.$userName2.'</th></tr>
                                <tr><td align="center"><img src="'.$value->img1.'" class="img-fluid"></td><td align="center"><img width="250" src="'.$value->img2.'" class="img-fluid"></td></tr>
                                </table>';
                    $name9        = FormLib::addButton('showImgBtn'.$id,Lang::get('sys_btn.btn_63'),3,'','#myModal'.$id,'modal');
                    $name9       .= ContentLib::genModal('myModal'.$id,$userName3.$userMemo,$hideImgHtml,2);

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $rept1],
                        '2'=>[ 'name'=> $rept2],
                        '3'=>[ 'name'=> $rept3],
                        '4'=>[ 'name'=> $rept4],
                        '5'=>[ 'name'=> $rept5],
                        '6'=>[ 'name'=> $rept6],
                        '7'=>[ 'name'=> $rept7],
                        '99'=>[ 'name'=> $name9],
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
