<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\Report\ReptPermit2Trait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use App\Model\WorkPermit\wp_work;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class ReptPermit2Controller extends Controller
{
    use ReptPermit2Trait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ReptPermit2Controller
    |--------------------------------------------------------------------------
    |
    | 當日 工作許可證 儀表板
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
//        $this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'rept_permit_t2';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title11');//大標題
        $this->pageTitleSub     = Lang::get($this->langText.'.title11');//大標題

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
        $today      = date('Y-m-d');
        //指定取得報表內容ＵＲＬ
        $local = $request->has('local')? $request->local : '';
        $auto  = $request->has('stop')? 0 : 1;
        $b_factory_a_id = b_factory_b::isIDCodeExist($local);
        $store  = ($b_factory_a_id)? b_factory_a::getStoreId($b_factory_a_id) : 2;
        $storeName = '';'('.b_factory::getName($store).')';

        $reptSource     = 'findPermitRept2';
        $viewSource     = 'rept_door2_1024';
        $showSource     = 'reportshowgroup';
        $showSource2    = '$( "#total_men" ).load( "findPermitRept2a?l='.$local.'")';
        $showSource3    = '$( "#total_car" ).load( "findPermitRept2b?l='.$local.'")';
        $showSource4    = 'ShowTime();';
        $autoJs = ($auto)? 'setInterval(loadRept,60000);' : '';
        //顯示標題
        $mainTitle  = $this->pageTitleMain.$storeName;
        $subtitle   = $today;
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        switch($reptSource)
        {
            case 'findPermitRept2':
                $contents = $this->genRermit2Html($store);
                break;
        }
        if($autoreload == 'Y')
        {
            //-------------------------------------------//
            //  View -> jsavascript
            //-------------------------------------------//
            $js = '$(document).ready(function() {
                        loadRept();
                       '.$autoJs.'
                       '.$showSource4.'
                    } );
                    function loadRept(){
                        $( "#'.$showSource.'" ).load( "'.$reptSource.'?l='.$local.'");
                        '.$showSource2.'
                        '.$showSource3.'
                    }
                    function ShowTime(){
                        var NowDate=new Date();
                        var Y=NowDate.getFullYear();
                        var m=("0" + (NowDate.getMonth() + 1)).slice(-2);
                        var d=("0" + NowDate.getDate()).slice(-2);
                        var h = (NowDate.getHours()<10 ? "0" : "")+NowDate.getHours();
                        var i = (NowDate.getMinutes()<10 ? "0" : "")+NowDate.getMinutes();
                        var s = (NowDate.getSeconds()<10 ? "0" : "")+NowDate.getSeconds();
                    　  document.getElementById("now_time").innerHTML = Y+"-"+m+"-"+d+" "+h+":"+i;
                    　  setTimeout("ShowTime()",1000);
                    }
                    ';
        }


        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray['repot_title']     = $mainTitle;
        $retArray['title']           = $subtitle;
        $retArray['repot_sub_title'] = $subtitle;

        $retArray['info_title1'] = Lang::get('sys_base.base_40308');
        $retArray['info_cnt1']   = wp_work::getTodayCount($today,$store);
        $retArray['info_unit1']  = Lang::get('sys_base.base_40311');
        $retArray['info_title2'] = Lang::get('sys_base.base_40309');
        $retArray['info_cnt2']   = rept_doorinout_t::getMenCount($today,$store);
        $retArray['info_unit2']  = Lang::get('sys_base.base_40312');
        $retArray['info_title3'] = Lang::get('sys_base.base_40310');
        $retArray['info_cnt3']   = date('Y-m-d H:i');
        $retArray['content']     = $contents;
        $retArray['js']          = $js;
        return view($viewSource,$retArray);
    }


}
