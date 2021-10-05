<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Http\Traits\Factory\RFIDPairTrait;
use App\Http\Traits\Factory\RFIDTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Supply\b_supply;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_used_rfid;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use \Curl\Curl;

class RFIDPair2Controller extends Controller
{
    use RFIDTrait,RFIDPairTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | RFIDPair2Controller 配卡程式
    |--------------------------------------------------------------------------
    |
    | RFID 配對 維護
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
        $this->hrefHome         = 'rfid';
        $this->hrefMain         = 'rfidpair2';
        $this->langText         = 'sys_rfid';

        $this->hrefMainDetail   = 'rfidpair2/';
        $this->hrefMainNew      = 'new_rfidpair2/';
        $this->routerPost       = 'postRFIDpair2';
        $this->routerPost2      = 'rfidpairList2';

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
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $out = $js ='';
        $no  = 0;
        $supplyAry= b_supply::getSelect();
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $postRoute  = ($request->b_supply_id)? $this->routerPost : $this->routerPost2;
        $postSubmit = ($request->b_supply_id)? 'btn_7' : 'btn_37';

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(1,array($postRoute,-1),'POST',1,TRUE);

        $html = $form->text('ip','127.0.0.1');
        $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_35'),1);
        //廠商
        if($request->b_supply_id)
        {
            $html  = b_supply::getName($request->b_supply_id);
            $html .= $form->hidden('b_supply_id',$request->b_supply_id);
        } else {
            $html = $form->select('b_supply_id',$supplyAry);
        }
        $form->add('nameT3', $html,Lang::get('sys_engineering.engineering_108'),1);
        //說明
        $html = HtmlLib::Color(Lang::get('sys_engineering.engineering_1032'),'red',1);
        $form->add('nameT3', $html,Lang::get('sys_engineering.engineering_101'),1);

        if($request->b_supply_id)
        {
            /**
             * 人員選擇器
             */
            //1.搜尋：分類->承攬商成員
            $mebmer1 = view_door_supply_member::getCoursePassMember($request->b_supply_id);

            //報名
            $table = new TableLib();
            //標題
            $heads = [];
            $heads[] = ['title'=>Lang::get($this->langText.'.rfid_31')]; //
            $heads[] = ['title'=>Lang::get($this->langText.'.rfid_32')]; //成員
            $heads[] = ['title'=>Lang::get($this->langText.'.rfid_33')]; //承攬身份
            $table->addHead($heads,0);


            if(count($mebmer1))
            {
                //2. 取得卡片人員
                $tBody   = [];
                $rfidAry = view_used_rfid::getSelect(5,$request->b_supply_id);
                //dd($mebmer1,$rfidAry);
                foreach($mebmer1 as $value)
                {
                    $uid          = $value['b_cust_id'];
                    $name1        = (!isset($rfidAry[$uid]))? $form->checkbox('member[]',$uid) : HtmlLib::Color(Lang::get($this->langText.'.rfid_34'),'red'); //
                    $name2        = HtmlLib::Color($value['name'],'black',1); //
                    $name3        = isset($rfidAry[$uid])? ($rfidAry[$uid]) : ''; //
                    if(!isset($rfidAry[$uid])) $no++;

                    $tBody[] = ['0'=>[ 'name'=> $name1],
                        '1'=>[ 'name'=> $name2],
                        '2'=>[ 'name'=> $name3],
                    ];
                }
                $table->addBody($tBody);

                //輸出
                $form->add('nameT1', $table->output(),Lang::get('sys_engineering.engineering_94'));
                unset($table,$heads,$tBody);
            }
        }
        //Submit
        $submitDiv  = '';
        if(!$request->b_supply_id || $no)
        {
            $submitDiv  = $form->submit(Lang::get('sys_btn.'.$postSubmit),'1','agreeY').'&nbsp;';
        }
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);
        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $form->boxFoot($submitDiv);

        $out = $form->output();
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


    /**
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->member)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif(!($request->ip))
        {
            //ip
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.rfid_1019'))
                ->withInput();
        }
        elseif(!count($request->member))
        {
            //請填寫承攬商與人員資料
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.rfid_1018'))
                ->withInput();
        }
        else {
            $ret  = 0;
            $this->getBcustParam();
            $id   = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;

            $url  = $request->ip.'\testSendJson\sendCardApi';
        }
        dd($url,$request->member);

        //CURL start
        $curl = new Curl();
        //關閉憑證檢核
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        $url = 'https://shcs1.chuangfu168.com.tw/app';
        $curl->post($url, $postInput);
        $ret = $curl->response;
        $curl->close();
        echo $ret;


        //2-1. 更新成功
        if($ret)
        {
            //沒有可更新之資料
            if($ret === -1)
            {
                $msg = Lang::get('sys_base.base_10109');
                return \Redirect::back()->withErrors($msg);
            } else {
                //動作紀錄
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_rfid_a',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.$param);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }



}
