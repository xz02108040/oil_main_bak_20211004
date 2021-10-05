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
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_d;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class ReptDoorInOutTodayController extends Controller
{
    use ReptDoorFactoryTrait,ReptDoorMenInOutTodayTrait,ReptDoorCarInOutTodayTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ReptDoorInOutTodayController
    |--------------------------------------------------------------------------
    |
    | 當日 廠區 儀表板
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
        $isAuth   = 0;
        $autoreload = 'Y';
        //指定取得報表內容ＵＲＬ
        $scode  = $request->has('sid')? $request->sid : '';      //廠區
        $stype  = $request->has('type')? $request->type : 'M';  //顯示人＆車
        $local  = $request->has('local')? $request->local : '';
        $level  = $request->has('level')? $request->level : 1;
        $cmp    = $request->has('cmp')? $request->cmp : 0;
        $supply = SHCSLib::decode($cmp);
        $size   = $request->size == 2 ? 2 : 1;
        $localUrl = ($local)? '&local='.$local : '';
        Session::put($this->hrefMain.'.view',$size);
        if(Auth::check())
        {
            //使用帳密登入
            $isAuth = 1;
            $subtitle = 'a';
        }
        elseif(b_factory_b::isIDCodeExist($local))
        {
            //沒有登入，給一個虛擬帳號登入
            $isAuth = 1;
            $autoreload  = 'N';
            $subtitle = 'n';
            Auth::loginUsingId(2000000002); //登入警衛室帳號「2019/05/15 解決門禁本地端登入問題」
            Session::put($this->hrefMain.'.local',$local);
            Session::put($this->hrefMain.'.autoreload',$autoreload); //是否自動重載
        } else {
            $subtitle = 't';
        }
        //如果沒有登入，則登出
//        if(!$isAuth) return redirect('/login');
        Session::put($this->hrefMain.'.sid',$request->sid);
        Session::put($this->hrefMain.'.type',$request->type);

        $sid            = ($scode)? SHCSLib::decode($scode) : 0;
        $urlParam       = '?sid='.$scode.'&type='.$stype.'&level='.$level.'&cmp='.$cmp;
        $reptSource     = ($sid > 0)? 'findDoorRept2' : 'findDoorRept1';
        $viewSource     = ($sid > 0)? 'report' : (($size == 2)? 'rept_door2_1024' : 'rept_door2');
        $showSource     = ($sid > 0)? 'content-div' : 'reportshowgroup';
        $showSource2    = ($sid > 0)? '' : '$( "#total_men" ).load( "findDoorRept4")';
        $showSource3    = ($sid > 0)? '' : '$( "#total_car" ).load( "findDoorRept5")';
        $showSource4    = ($sid > 0)? '' : 'ShowTime();';
        $store_name     = ($sid > 0)? b_factory_d::getName($sid).'：' : '';
        //顯示標題
        $mainTitle  = ($sid > 0)? (($stype == 'C')? $store_name.$this->pageNewTitle : $store_name.$this->pageTitleList) : $this->pageTitleMain;
        $today      = date('Y-m-d');
        $storeAry   = sys_param::getParam('REPORT_DEFAULT_STORE',2);
        $storeAry   = explode(',',$storeAry);
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        switch($reptSource)
        {
            case 'findDoorRept2':
                $reptSource = $reptSource.$urlParam;
                if($stype == 'C')
                {
                    $contents = $this->genDoorInOutTodayCarHtml($level,$sid, $supply, $localUrl);
                } else {
                    $contents = $this->genDoorInOutTodayMenHtml($level,$sid, $supply, $localUrl);
                }
                break;
            case 'findDoorRept1':
                $contents = $this->genDoorInOutFactoryHtml($stype,0,'',$size,$localUrl);
                break;
        }
        if($autoreload == 'Y')
        {
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
                    　  document.getElementById("now_time").innerHTML = Y+"-"+m+"-"+d+" "+h+":"+i+":"+s;
                    　  setTimeout("ShowTime()",1000);
                    }
                    ';
        }


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
        //dd($viewSource,$retArray);
        return view($viewSource,$retArray);
    }


}
