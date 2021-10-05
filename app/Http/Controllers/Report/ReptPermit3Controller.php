<?php

namespace App\Http\Controllers\Report;

use App\Exports\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorLogTrait;
use App\Http\Traits\Report\ReptPermitListTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_rfid_a;
use App\Model\Factory\b_rfid_type;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\sys_code;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Excel;

class ReptPermit3Controller extends Controller
{
    use ReptPermitListTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ReptDoorInOutListController
    |--------------------------------------------------------------------------
    |
    | 報表 - 已回簽工作許可證統計
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
        $this->hrefMain         = 'rept_permit_l1';
        $this->langText         = 'sys_rept';
        $this->hrefPrint        = 'printpermit';

        $this->pageTitleMain    = Lang::get($this->langText.'.title12');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list12');//大標題

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
        $supplyAry = b_supply::getSelect();
        $localAry  = b_factory_a::getSelect();
        $dangerAry = SHCSLib::getCode('PERMIT_DANGER',1);

        $aid      = $request->aid; //承攬商
        $bid      = $request->bid; //施工區域
        $cid      = $request->cid; //危險等級
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
            $bid = Session::get($this->hrefMain.'.search.bid',0);
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if(!$cid)
        {
            $cid = Session::get($this->hrefMain.'.search.cid','');
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        //view元件參數
        $tbTitle    = $this->pageTitleList;//列表標題
        $hrefMain   = $this->hrefMain;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($aid || $bid || $cid || ($sdate && $edate))
        {
            list($listAryCnt,$listAry) = $this->getPermitWorkDayRept([$aid,$bid,$cid,$sdate,$edate],['F']);
            //dd([$aid,$bid,$cid,$sdate,$edate],$listAry);

            if($request->has('excel') && count($listAry)){
                $cellData = [[
                    Lang::get($this->langText.'.rept_200'),
                    Lang::get($this->langText.'.rept_231'),
                    Lang::get($this->langText.'.rept_232'),
                    Lang::get($this->langText.'.rept_221'),
                    Lang::get($this->langText.'.rept_211'),
                    Lang::get($this->langText.'.rept_203'),
                    Lang::get($this->langText.'.rept_207'),
                    Lang::get($this->langText.'.rept_206'),
                    Lang::get($this->langText.'.rept_233'),
                    Lang::get($this->langText.'.rept_228'),
                    Lang::get($this->langText.'.rept_229'),
                    Lang::get($this->langText.'.rept_234')
                ]];

                foreach($listAry as $value)
                {
                    $tmp    = [];
                    $tmp[]  = $value->permit_no;
                    $tmp[]  = $value->apply_stamp;
                    $tmp[]  = $value->apply_user;
                    $tmp[]  = $value->danger;
                    $tmp[]  = $value->local;
                    $tmp[]  = $value->supply;
                    $tmp[]  = $value->dept1;
                    $tmp[]  = $value->dept2;
                    $tmp[]  = $value->project_no;
                    $tmp[]  = $value->sdate;
                    $tmp[]  = $value->work;
                    $tmp[]  = $value->finisher;

                    $cellData[] = $tmp;
                }

                Session::put('download.exceltoexport',$cellData);
                return Excel::download(new ExcelExport(), '已回簽工作許可證統計_'.date('Ymdhis').'.xlsx');
            }
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //搜尋
        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.rept_305'));
        $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.rept_9'));
        $form->addRowCnt($html);

        $html = $form->select('aid',$supplyAry,$aid,2,Lang::get($this->langText.'.rept_203'));
        $html.= $form->select('bid',$localAry,$bid,2,Lang::get($this->langText.'.rept_211'));
        $html.= $form->select('cid',$dangerAry,$cid,2,Lang::get($this->langText.'.rept_221'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $html.= $form->submit(Lang::get('sys_btn.btn_25'),'2','excel');

        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.rept_100002'),'red',1);
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_200')]; //證號
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_231')]; //申請時間
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_232')]; //申請人
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_221')]; //危險等級
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_211')]; //施工地點
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_203')]; //承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_207')]; //轄區部門
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_206')]; //監造部門
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_233')]; //案號
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_228')]; //施工日期
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_229')]; //工作內容
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_234')]; //回簽者

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
//                dd($value);
                $no++;
                $id           = $value->id;
                $name1        = $value->permit_no; //
                $name1        = $form->linkbtn($this->hrefPrint.'?id='.SHCSLib::encode($id), $value->permit_no,5,'','','','_blank'); //新增
                $name2        = $value->apply_stamp; //
                $name3        = $value->apply_user; //
                $name4        = $value->danger; //
                $name5        = $value->local; //
                $name6        = $value->supply; //
                $name7        = $value->dept1; //
                $name8        = $value->dept2; //
                $name9        = $value->project_no; //
                $name10       = $value->sdate; //
                $name11       = $value->work; //
                $name12       = $value->finisher; //

                $tBody[] = [
                    '1'=>[ 'name'=> $name1],
                    '2'=>[ 'name'=> $name2],
                    '3'=>[ 'name'=> $name3],
                    '4'=>[ 'name'=> $name4],
                    '5'=>[ 'name'=> $name5],
                    '6'=>[ 'name'=> $name6],
                    '7'=>[ 'name'=> $name7],
                    '8'=>[ 'name'=> $name8],
                    '9'=>[ 'name'=> $name9],
                    '10'=>[ 'name'=> $name10],
                    '11'=>[ 'name'=> $name11],
                    '12'=>[ 'name'=> $name12],
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
