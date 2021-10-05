<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Factory\b_factory_b;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class ReptDoorInOutToday2Controller extends Controller
{
    use ReptDoorFactoryTrait,ReptDoorMenInOutTodayTrait,ReptDoorCarInOutTodayTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ReptDoorInOutToday2Controller
    |--------------------------------------------------------------------------
    |
    | 當日 廠區 儀表板[IE]
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
        //$this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'rept_doorinout_t';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title1');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list1');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new1');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit1');//編輯

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
        //$this->getBcustParam();
        //$this->getMenuParam();
        //參數
        $contents = $js = '';
        $autoreload = 'Y';
        //ＵＲＬ參數
        $isAuth = $request->has('stop')? 0 : 1; //是否自動刷新
        $local  = $request->has('local')? $request->local : ''; //廠區 （預設 烏材＆觀音）
        $size  = $request->has('size')? $request->size : 1;     //版面大小 1:1920X1080 / 2:1024X768

        //顯示
        $reptSourceType = 'findDoorRept1'; //顯示來源
        $viewSource     = ($size == 2)? 'rept_door2_1024' : 'rept_door2'; //顯示版面大小
        $showSource     = 'reportshowgroup';
        $showSourceMen  = '$( "#total_men" ).load( "findDoorRept4");';
        $showSourceCar  = '$( "#total_car" ).load( "findDoorRept5");';
        $showSourceTIme = 'ShowTime();';

//        $sid            = ($scode)? SHCSLib::decode($scode) : '';
        $urlParam       = '&type='.$stype.'&level='.$level.'&cmp='.$cmp;
        $reptSource     = ($sid > 0)? 'findDoorRept2?'.$urlParam : 'findDoorRept1';
        $viewSource     = ($sid > 0)? 'report' : (($size == 2)? 'rept_door2_1024' : 'rept_door2');
        $showSource     = ($sid > 0)? 'content-div' : 'reportshowgroup';
        $showSource2    = ($sid > 0)? '' : '$( "#total_men" ).load( "findDoorRept4")';
        $showSource3    = ($sid > 0)? '' : '$( "#total_car" ).load( "findDoorRept5")';
        $showSource4    = ($sid > 0)? '' : 'ShowTime();';
        //顯示標題
        $mainTitle  = ($sid > 0)? (($stype == 'C')? $this->pageNewTitle : $this->pageTitleList) : $this->pageTitleMain;
        $today      = date('Y-m-d');
        $storeAry   = sys_param::getParam('REPORT_DEFAULT_STORE',2);
        $storeAry   = explode(',',$storeAry);
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        switch($reptSource)
        {
            case 'findDoorRept1':
                $contents = $this->genDoorInOutFactoryHtml($stype,'','',$size);
                break;
        }
        //-------------------------------------------//
        //  View -> jsavascript
        //-------------------------------------------//
        $js = '$(document).ready(function() {
                       setInterval(loadRept,1000);
                       '.$showSourceTIme.'
                    } );
                    function loadRept(){
                        $( "#'.$showSource.'" ).load( "'.$reptSource.'");
                        '.$showSourceMen.'
                        '.$showSourceCar.'
                    }
                    function ShowTime(){
                        var NowDate=new Date();
                        var Y=NowDate.getFullYear();
                        var m=("0" + (NowDate.getMonth() + 1)).slice(-2);
                        var d=("0" + NowDate.getDate()).slice(-2);
                        var h = (NowDate.getHours()<10 ? "0" : "")+NowDate.getHours();
                        var i = (NowDate.getMinutes()<10 ? "0" : "")+NowDate.getMinutes();
                        var s = (NowDate.getSeconds()<10 ? "0" : "")+NowDate.getSeconds();
                    　  document.getElementById("now_time").innerHTML = Y+"-"+m+"-"+d+" "+h+":"+i+":"+s;
                    　  setTimeout("ShowTime()",1000);
                    }
                    ';


        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray['repot_title']     = Lang::get('sys_base.base_40200').'<span style="font-size: 0.25em">'.$subtitle.'</span>';
        $retArray['title']           = $mainTitle;
        $retArray['repot_sub_title'] = $mainTitle;

        $retArray['info_title1'] = Lang::get('sys_base.base_40208');
        $retArray['info_cnt1']   = rept_doorinout_t::getMenCount($today,$storeAry);
        $retArray['info_unit1']  = Lang::get('sys_base.base_40211');
        $retArray['info_title2'] = Lang::get('sys_base.base_40209');
        $retArray['info_cnt2']   = rept_doorinout_car_t::getCarCount($today,$storeAry);
        $retArray['info_unit2']  = Lang::get('sys_base.base_40212');
        $retArray['info_title3'] = Lang::get('sys_base.base_40210');
        $retArray['info_cnt3']   = date('Y-m-d H:i');
        $retArray['content']     = $contents;
        $retArray['js']          = $js;
        return view($viewSource,$retArray);
    }


}
