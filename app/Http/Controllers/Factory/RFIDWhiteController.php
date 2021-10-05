<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Supply\b_supply_member;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class RFIDWhiteController extends Controller
{
    use SessTraits;
    /*
    |--------------------------------------------------------------------------
    | RFIDWhiteController
    |--------------------------------------------------------------------------
    |
    | 白名單 維護
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
        $this->hrefMain         = 'rfidwhiteorder';
        $this->langText         = 'sys_rfid';

        $this->pageTitleMain    = Lang::get($this->langText.'.title7');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list7');//標題列表

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

        //view元件參數
        $tbTitle    = $this->pageTitleList;//列表標題
        $hrefMain   = $this->hrefMain;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = b_supply_member::getAllPassMember();
        Session::put($this->hrefMain.'.Record',$listAry);
		//print_r($listAry);
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_1')]; //卡片號碼
		$heads[] = ['title'=>Lang::get($this->langText.'.rfid_36')]; //承攬商名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_32')]; //承攬商成員
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_37')]; //成員手機

        $table->addHead($heads,0);
        
		if(count($listAry))
        {
            foreach($listAry as $value)
            {

				$no++;
                $id           = $value['uid'];  //
                $name         = $value['name']; //
                $memo1        = $value['memo1'];//
                $memo2        = $value['memo2'];//
                

                $tBody[] = ['0'=>['name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>['name'=> $id],
                            '3'=>['name'=> $memo1],
                            '11'=>['name'=> $name],
                            '2'=>['name'=> $memo2]
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
