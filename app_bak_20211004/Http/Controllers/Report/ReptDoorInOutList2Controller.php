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
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class ReptDoorInOutList2Controller extends Controller
{
    use ReptDoorLogTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ReptDoorInOutList2Controller
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
        $this->hrefMain         = 'rept_doorinout_t3';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title4');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list4');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new4');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit4');//編輯

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
        $b_factory_a_id = b_factory_b::isIDCodeExist($local);
        $store  = ($b_factory_a_id)? b_factory_a::getStoreId($b_factory_a_id) : 2;
        $storeName = '('.b_factory::getName($store).')';
        Session::put($this->hrefMain.'.b_factory_a_id',$b_factory_a_id);
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
            Session::put($this->hrefMain.'.b_factory_a_id',$b_factory_a_id);
        }
        Session::put($this->hrefMain.'.Auth_user',Auth::id());
        //TODO 異常登入失敗
        if(!$isAuth) return redirect('/login');

        $viewSource     = 'report';
        //顯示標題
        $mainTitle  = $this->pageTitleMain;
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $contents = $this->genDoorLogHtml($b_factory_a_id,0);

        if($stype == 'P')
        {
            //
        } else {
            //-------------------------------------------//
            //  View -> jsavascript
            //-------------------------------------------//
            $js = '$(document).ready(function() {
                    $( "#search_btn" ).on("keyup", function() {
                        $("table tr:not(:has(th))").show();
                        var search_text = $( "#search_btn" ).val();
                        if(search_text.length > 1)
                        {
                            $("table tr").each(function(index) {
                                if (index !== 0) {
                        
                                    $row = $(this);
                        
                                    var id = $row.find("td").text();
                                    if (id.indexOf(search_text) === -1) {
                                        $row.hide();
                                    }
                                    else {
                                        $row.show();
                                    }
                                }
                            });
                        } 
                                            
                    });
                    
                } );
                
                    ';
            $css = '
                .myHide {
                    display:hidden;
                }
                #report_table {
                  border-collapse: collapse;
                  width: 100%;
                }
                
                #report_table td, #report_table th {
                  border: 1px solid #ddd;
                  padding: 8px;
                }
                
                #report_table tr:nth-child(even){background-color: #f2f2f2;}
                #report_table tr:nth-child(odd){background-color: #D9DCC6;}
                
                #report_table tr:hover {background-color: #e1f2d5;}
                
                #report_table th {
                  padding-top: 12px;
                  padding-bottom: 12px;
                  text-align: left;
                  background-color: #1B5045;
                  color: white;
                }
             
            ';
        }


        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray['repot_title']     = Lang::get('sys_base.base_40200');
        $retArray['title']           = $mainTitle.$storeName;
        $retArray['repot_sub_title'] = $mainTitle;
        $retArray['content']     = $contents;
        $retArray['js']          = $js;
        $retArray['css']          = $css;
        return view($viewSource,$retArray);
    }


}
