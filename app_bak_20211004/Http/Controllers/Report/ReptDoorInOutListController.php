<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorLogTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_d;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class ReptDoorInOutListController extends Controller
{
    use ReptDoorLogTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ReptDoorInOutListController
    |--------------------------------------------------------------------------
    |
    | 當日 門禁 儀表板-列表方式[人員進入＆離場紀錄]
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
        $this->hrefMain         = 'rept_doorinout_t2';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title2');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list2');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new2');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit2');//編輯

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
        //參數
        $js = '';
        $isAuth   = 0;
        //指定取得報表內容ＵＲＬ
        $stype  = $request->has('type')? $request->type : 'M';
        $local  = $request->has('local')? $request->local : '';
        $getStyle = Session::get($this->hrefMain.'.stype');
        if($getStyle) $stype = $getStyle;

        $size   = $request->size == 2 ? 2 : 1;
        $b_factory_d_id = b_factory_d::isIDCodeExist($local);
        $b_factory_id   = b_factory_d::getStoreId($b_factory_d_id);
        Session::put($this->hrefMain.'.b_factory_d_id',$b_factory_d_id);
        Session::put($this->hrefMain.'.local',$local);
        Session::put($this->hrefMain.'.size',$size);
        Session::put($this->hrefMain.'.view',$size);
        if(Auth::check())
        {
            $isAuth         = 1;
        }elseif($local)
        {
            $isAuth = 1;
            $stype  = 'P';
            Auth::loginUsingId(2000000002); //登入警衛室帳號「2019/05/15 解決門禁本地端登入問題」
            Session::put($this->hrefMain.'.local',$local);
            Session::put($this->hrefMain.'.stype',$stype);
            Session::put($this->hrefMain.'.b_factory_d_id',$b_factory_d_id);
        }
        Session::put($this->hrefMain.'.Auth_user',Auth::id());
        //TODO 異常登入失敗
        //if(!$isAuth) return redirect('/login');

        $reptSource     = 'findDoorRept1?k='.SHCSLib::encode(2).'&l='.SHCSLib::encode($b_factory_d_id);
        $viewSource     = (($size == 2)? 'rept_door2_1024' : 'rept_door2');
        $showSource     = 'reportshowgroup';
        $showSource2    = '$( "#total_men" ).load( "findDoorRept4?l='.SHCSLib::encode($b_factory_d_id).'")';
        $showSource3    = '$( "#total_car" ).load( "findDoorRept5?l='.SHCSLib::encode($b_factory_d_id).'")';
        $showSource4    = 'ShowTime();';
        //顯示標題
        $mainTitle  = $this->pageTitleMain;
        $today      = date('Y-m-d');
        $storeAry   = [$b_factory_id];
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $contents = $this->genDoorLogHtml($b_factory_d_id);

        if($stype == 'P')
        {
            //
        } else {
            //-------------------------------------------//
            //  View -> jsavascript
            //-------------------------------------------//
            $js = '$(document).ready(function() {
                       setInterval(loadRept,1000);
                       '.$showSource4.'
                    } );
                    function loadRept(){
                        $( "#'.$showSource.'" ).load( "'.$reptSource.'");
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
        $retArray['repot_title']     = Lang::get('sys_base.base_40200');
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
