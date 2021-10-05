<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\CourseTrait;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Report\ReptDoorBlackOrderTrait;
use App\Http\Traits\Report\ReptDoorOrderTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\View\view_supply_violation;
use DB;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class DoorWhiteOrderController extends Controller
{
    use ReptDoorOrderTrait,SessTraits,DoorTrait;
    /*
    |--------------------------------------------------------------------------
    | DoorWhiteOrderController
    |--------------------------------------------------------------------------
    |
    | 報表: 門禁白名單報表
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
        $this->hrefMain         = 'doorwhiteorder';
        $this->hrefMain2        = 'doorblackorder';
        $this->langText         = 'sys_rept';

        $this->hrefMainDetail   = 'wpworkorder';
        $this->hrefMainDetail2  = 'engineeringmember';
        $this->hrefMainNew      = '/';
        $this->routerPost       = 'postDoorwhiteorder';

        $this->pageTitleMain    = Lang::get($this->langText.'.title16');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list16');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new16');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit16');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_69');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_77');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_78');//[按鈕]返回

    }
    /**
     * 白名單
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
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefBack = $this->hrefMain2;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//

        $listAry = $this->getDoorOrderList();
        $listAmt = count($listAry);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$form->addLinkBtn($hrefBack, $btnBack,10); //返回
        $form->addHtml(HtmlLib::Color(Lang::get($this->langText.'.rept_100006'),'red',1));
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_203')]; //承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_230')]; //承攬商人員
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_202')]; //工程案件
        $heads[] = ['title'=>Lang::get($this->langText.'.rept_239')]; //卡片內碼

        $table->addHead($heads,0);
        if($listAmt)
        {
            foreach($listAry as $value)
            {
                $no++;

                $name1        = $value->supply; //
                $name2        = $value->b_cust_id.' / '.$value->name; //
                $name3        = $value->project.' ( '.$value->project_no.' )'; //
                $name4        = $value->rfid_code; //

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                    '1'=>[ 'name'=> $name1],
                    '2'=>[ 'name'=> $name2],
                    '3'=>[ 'name'=> $name3],
                    '4'=>[ 'name'=> $name4],
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
