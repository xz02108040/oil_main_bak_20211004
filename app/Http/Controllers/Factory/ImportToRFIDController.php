<?php

namespace App\Http\Controllers\Factory;


use App\Http\Controllers\Controller;
use App\Http\Traits\Factory\RFIDTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_rfid;
use App\Model\Factory\b_rfid_type;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Excel;

class ImportToRFIDController extends Controller
{
    use RFIDTrait, SessTraits;

    /*
    |--------------------------------------------------------------------------
    | ImportToRFIDController
    |--------------------------------------------------------------------------
    |
    | excel 匯入 RFID檔
    |
    |
    */

    /**
     * 環境參數
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     *
     * @return void
     */
    public function __construct()
    {
        //身分驗證
        $this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'exceltorfid';
        $this->hrefRfid         = 'rfid';
        $this->langText         = 'sys_rfid';

        $this->hrefMainDetail   = 'exceltorfid/';
        $this->hrefMainNew      = 'new_exceltorfid';
        $this->routerPost       = 'ImportToRFID';

        $this->pageTitleMain    = Lang::get($this->langText.'.title4');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list4');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new4');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit4');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pagedownBtn      = Lang::get('sys_btn.btn_29');//[按鈕]下載
    }

    /**
     * 首頁內容
     *
     * @return void
     */
    public function index()
    {
        set_time_limit(0);
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $out = $js ='';
        $selectAry1 = b_factory::getSelect();
        $selectAry2 = b_rfid_type::getSelect();
        //view元件參數
        $tbTitle    = $this->pageTitleList;//列表標題
        $hrefMain   = $this->hrefMain;
        $hrefExcel  = 'excel/example2.xlsx';
        $btnExcel   = $this->pagedownBtn;
        $hrefBack   = $this->hrefRfid;
        $btnBack    = $this->pageBackBtn;

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(1, array($this->routerPost), 'POST', 1, TRUE);
        $form->addLinkBtn($hrefExcel, $btnExcel,2); //下載
        $form->addLinkBtn($hrefBack, $btnBack,1); //下載
        $form->addHr();
        //廠區
        $html = $form->select('b_factory_id',$selectAry1,1,2);
        $form->add('nameT', $html, Lang::get($this->langText.'.rfid_3'), 1);
        //分類
        $html = $form->select('rfid_type',$selectAry2,1,2);
        $form->add('nameT', $html, Lang::get($this->langText.'.rfid_11'), 1);
        //Excel
        $html = $form->file('excel',Lang::get($this->langText.'.rfid_63'));
        $form->add('nameT', $html, Lang::get($this->langText.'.rfid_61'), 1);

        //匯入excel
        $submitDiv = $form->submit(Lang::get('sys_btn.btn_17'), '1', 'agreeY') . '&nbsp;';
        $form->boxFoot($submitDiv);
        //輸出
        $out .= $form->output(1);

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTitle, $out));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> jsavascript
        //-------------------------------------------//
        $js = '$(document).ready(function() {


                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index', $retArray);
    }

    /**
     * 處理Excel資料，並匯入職員檔
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //資料不齊全
        if(!$request->b_factory_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10123'))
                ->withInput();
        }
        elseif(!$request->rfid_type)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10126'))
                ->withInput();
        }
        elseif (!$request->hasFile('excel')) {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10121'))
                ->withInput();
        } else {
            $tid = $request->rfid_type;
            $fid = $request->b_factory_id;
            $ip  = $request->ip();
            //Excel 匯入
            Excel::load($request->excel, function ($reader) use ($fid,$tid,$ip) {
                $this->getBcustParam();
                //參數
                $isSuc = $isErr = 0;
                $menu = $this->pageTitleMain;
                //Excel資料
                $data = $reader->toArray();
                if ($cnt = count($data)) {
                    foreach ($data as $key => $val) {
                        $isIns = 1;
                        if(isset($val['卡片代碼']) && isset($val['卡片內碼']) )
                        {
                            $tmp = [];
                            $tmp['name']            = $val['卡片代碼'];
                            $tmp['rfid_code']       = $val['卡片內碼'];

                            $tmp['rfid_type']       = $tid;
                            $tmp['b_factory_id']    = $fid;
                            //dd([$val,$tmp]);

                            //重複
                            if($tmp['rfid_code'] && b_rfid::isExist(0,$tmp['rfid_code']))
                            {
                                $isIns = 0;
                            }

                            if($isIns)
                            {
                                $ret = $this->createRFID($tmp,$this->b_cust_id);
                                $id  = $ret;
                                //2-1. 更新成功
                                if($ret)
                                {
                                    $isSuc++;
                                    //動作紀錄
                                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,1,'b_rfid',$id);
                                } else {
                                    $isErr++;
                                }
                            } else {
                                $isErr++;
                            }
                        } else {
                            $isErr++;
                        }
                    }
                    Session::flash('message',Lang::get($this->langText.'.rfid_62',['amt'=>$cnt,'suc'=>$isSuc,'err'=>$isErr]));
                }
            });
        }


        return \Redirect::to($this->hrefMain);
    }
}
