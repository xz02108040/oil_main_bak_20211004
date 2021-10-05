<?php

namespace App\Http\Controllers\Tmp;

use App\Http\Controllers\Controller;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class PushTestController extends Controller
{
    use PushTraits,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | PushTestController
    |--------------------------------------------------------------------------
    |
    | 推播測試功能
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
        $this->hrefMain         = 'pushtest';
        $this->langText         = 'sys_tmp';

        $this->hrefMainDetail   = 'pushtest/';
        $this->hrefMainNew      = 'new_pushtest';
        $this->routerPost       = 'postPushtest';

        $this->pageTitleMain    = Lang::get($this->langText.'.title3');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list3');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new3');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit3');//編輯

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
        $no  = 0;
        $out = $js ='';
        $pushTo = ($request->pushTo)? $request->pushTo : '';
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 推播測試
        //-------------------------------------------//
        //推播結果

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = $form->text('pushTo','',8,Lang::get($this->langText.'.tmp_21'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $form->addRowCnt($html);
        $form->addHr();

        if($pushTo)
        {
            $memo = $user = $pushKey = '';
            $len = strlen($pushTo);
            if($len > 50)
            {
                //
                $uid = User::gePushToID($pushTo);
            } else {
                //
                $uid = User::isAccountExist($pushTo);
            }

            if($uid)
            {
                $user = User::getName($uid).'('.$uid.')';
                $pushKey = User::getPushID($uid);
                $memo = $this->pushToTestSuccess($uid);
            } else {
                $memo = '查無帳號';
            }


            $uptime = Lang::get($this->langText.'.tmp_22',['name1'=>$pushTo,'name2'=>$user,'name3'=>$pushKey,'name4'=>$memo]);
            $form->addRow(HtmlLib::Color($uptime,'red',1));
        }
        //輸出
        $out .= $form->output(1);





        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTitle,$out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {


                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

}
