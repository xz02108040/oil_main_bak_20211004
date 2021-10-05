<?php

namespace App\Http\Controllers\Report;

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

class ReptPermit4Controller extends Controller
{
    use ReptPermitListTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ReptPermit4Controller
    |--------------------------------------------------------------------------
    |
    | 報表 - 工作許可證統計
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
        $this->hrefMain         = 'rept_permit_l2';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title13');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list13');//大標題

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
        $aprocAry  = SHCSLib::getCode('PERMIT_APROC');
        array_unshift($aprocAry, '請選擇');

        $aid      = $request->aid; //承攬商
        $bid      = $request->bid; //施工區域
        $cid      = $request->cid; //危險等級
        $did      = $request->did; //工作許可證進度
        $sdate    = $request->sdate;
        $edate    = $request->edate;
        $sid      = $request->has('sid')? SHCSLib::decode($request->sid) : 0; //
        $wdate    = $request->has('date')? ($request->date) : ''; //
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $did = '';
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
        if(!$did)
        {
            $did = Session::get($this->hrefMain.'.search.did','0');
        } else {
            Session::put($this->hrefMain.'.search.did',$did);
        }
        //view元件參數
        $icon     = HtmlLib::genIcon('caret-square-o-right');
        $subTitle = ($sid)? ($icon.$aprocAry[$sid]) : '';
        $tbTitle  = $this->pageTitleList.$subTitle;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($aid || $bid || $cid || ($sdate && $edate))
        {
            if(!$sid)
            {
                $search_aproc = empty($did) ? [] : [$did];
                list($listAryCnt,$listAry) = $this->getPermitWorkDayRept([$aid,$bid,$cid,$sdate,$edate],$search_aproc,1);
            } else {
                list($listAryCnt,$listAry) = $this->getPermitWorkDayRept([$aid,$bid,$cid,$wdate,$wdate],[$sid]);
            }
            //dd([$aid,$bid,$cid,$sdate,$edate],$listAry);
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //搜尋
        if (!$sid) {
            $html = $form->date('sdate', $sdate, 2, Lang::get($this->langText . '.rept_305'));
            $html .= $form->date('edate', $edate, 2, Lang::get($this->langText . '.rept_9'));
            $html .= $form->select('did', $aprocAry, $did, 2, Lang::get($this->langText . '.rept_236'));
            $form->addRowCnt($html);
        }
        
        // $html = $form->select('aid',$supplyAry,$aid,2,Lang::get($this->langText.'.rept_203'));
        // $html.= $form->select('bid',$localAry,$bid,2,Lang::get($this->langText.'.rept_211'));
        // $html.= $form->select('cid',$dangerAry,$cid,2,Lang::get($this->langText.'.rept_221'));
        $html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
        if (!$sid) {
            $html .= $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search');
            $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear');
        } else {
            $html .= $form->linkbtn($hrefBack, $btnBack, 2); //返回
        }
        $html .= '</div>';
        $form->addRow($html, 2, 9);
        $html = HtmlLib::Color(Lang::get($this->langText.'.rept_100002'),'red',1);
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        if(!$sid)
        {

            $heads[] = ['title'=>Lang::get($this->langText.'.rept_228')]; //施工日期
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_236')]; //進度
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_237')]; //數量
        } else {
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
        }

        $isFun = ($sid)? 0 : 1;
        $table->addHead($heads,$isFun);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
//                dd($value);
                $no++;

                if(!$sid)
                {
                    $btn          = '';
                    $id           = SHCSLib::encode($value->aproc);
                    $name1        = $value->sdate; //
                    $name2        = $value->aproc_name; //
                    $name3        = $value->amt; //
                    if($name3 > 0)
                    {
                        $btn      = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','sid='.$id.'&date='.$name1),Lang::get('sys_btn.btn_27'),1); //按鈕
                    }

                    $tBody[] = [
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '99'=>[ 'name'=> $btn],
                    ];
                } else {
                    $name1        = $value->permit_no; //
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
