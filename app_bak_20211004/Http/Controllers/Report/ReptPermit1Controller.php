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
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class ReptPermit1Controller extends Controller
{
    use ReptDoorLogTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ReptDoorInOutListController
    |--------------------------------------------------------------------------
    |
    | 儀表板 - 今日許可證儀表板
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
        $this->hrefMain         = 'rept_permit_t1';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title10');//大標題

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
        $local = $request->has('local')? $request->local : '';
        $auto  = $request->has('stop')? 0 : 1;


        $reptSource     = 'findPermitRept1?l='.$local;
        $viewSource     = 'report';
        $showSource     = 'reportshowgroup';

        $autoJs = ($auto)? 'setInterval(loadRept,60000);' : '';
        //顯示標題
        $mainTitle  = $this->pageTitleMain;
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $contents = '<div id="reportshowgroup" style="font-family:標楷體,微軟正黑體,serif,sans-serif,cursive;font-size: 1.8em"></div>';
        //-------------------------------------------//
        //  View -> jsavascript
        //-------------------------------------------//
        $js = '$(document).ready(function() {
                        loadRept();
                        '.$autoJs.'
                    } );
                    function loadRept(){
                        $( "#'.$showSource.'" ).load( "'.$reptSource.'");
                    }
                    ';
        $css = '
                body {
                    background-color:#CDDAEE;
                }
                table { 
                  border:3px solid #000;
                  text-align:center;
                  border-collapse:collapse;
                } 
                th { 
                  background-color: #009FCC;
                  padding:10px;
                  border:3px solid #000;
                  color:#fff;
                } 
                td { 
                  border:3px solid #000;
                  padding:5px;
                } 
                #table_rept2 th { 
                  background-color: red;
                } 
                .redAlert {
                    background-color: red;
                    color:#fff;
                }
                ';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
//        $retArray['title']     = '<div style="width:100%;font-family:標楷體,微軟正黑體,serif,sans-serif,cursive;">'.$this->pageTitleMain.'</div>';
//        $retArray['title']     = '';


        $retArray['content']     = $contents;
        $retArray['js']          = $js;
        $retArray['css']         = $css;
        return view($viewSource,$retArray);
    }


}
