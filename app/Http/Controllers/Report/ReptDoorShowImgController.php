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
use App\Model\User;
use App\Model\View\view_log_door_today;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class ReptDoorShowImgController extends Controller
{
    use SessTraits,ReptDoorLogListTrait;
    /*
    |--------------------------------------------------------------------------
    | ReptDoorShowImgController
    |--------------------------------------------------------------------------
    |
    | [紀錄]進出廠 當事人頭像＆進出紀錄照片
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
        $this->hrefMain         = 'rept_door_img';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.rept_307');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.rept_307');//列表

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
        $img1_id = $request->img1? SHCSLib::decode($request->img1) : '';
        $img2_id = $request->img2? SHCSLib::decode($request->img2) : '';

        $img1 = ($img1_id > 0)? SHCSLib::toImgBase64String('user',$img1_id,500) : '';
        $img2 = ($img2_id > 0)? SHCSLib::toImgBase64String('door_user',$img2_id,640) : '';

        $title = view_log_door_today::getMenLog($img2_id);
        //dd($img1_id,$img2_id,$img1,$img2);
        //view元件參數
        $tbTile   = $title; //列表標題
        $hrefMain = $this->hrefMain; //路由
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');

        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain,'report_table');
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_203')]; //承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_306')]; //照片

        $table->addHead($heads,0);
        $tBody[] = [
            '1'=>[ 'name'=> '<img src="'.$img1.'" class="img-fluid">','style'=>'width:50%;','align'=>'center'],
            '2'=>[ 'name'=> '<img src="'.$img2.'" class="img-fluid" style="width:100%">','align'=>'center'],
        ];
        $table->addBody($tBody);

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
                    
                   
                } );';

        $css = '
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
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js,'css'=>$css];

        return view('report',$retArray);
    }

}
