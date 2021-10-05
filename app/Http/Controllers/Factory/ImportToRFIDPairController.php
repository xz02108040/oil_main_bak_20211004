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
use App\Model\Factory\b_factory;
use App\Model\Factory\b_rfid;
use App\Model\Factory\b_rfid_a;
use App\Model\Factory\b_rfid_type;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_used_rfid;
use App\Model\View\view_user;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Excel;

class ImportToRFIDPairController extends Controller
{
    use RFIDTrait, RFIDPairTrait,SessTraits;

    /*
    |--------------------------------------------------------------------------
    | ImportToRFIDPairController
    |--------------------------------------------------------------------------
    |
    | excel 匯入 RFID檔+配對檔
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
        $this->hrefMain         = 'exceltorfidpair1';
        $this->hrefRfid         = 'rfid';
        $this->langText         = 'sys_rfid';

        $this->hrefMainDetail   = 'exceltorfidpair1/';
        $this->hrefMainNew      = 'new_exceltorfidpair1';
        $this->routerPost       = 'ImportToRFID1';

        $this->pageTitleMain    = Lang::get($this->langText.'.title5');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list5');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new5');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit5');//編輯

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
        //$selectAry2 = b_rfid_type::getSelect(2);
        //view元件參數
        $tbTitle    = $this->pageTitleList;//列表標題
        $hrefMain   = $this->hrefMain;
        $hrefExcel  = 'excel/example3.xlsx';
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
        //$html  = $form->select('b_factory_id',$selectAry1,1,2);
        //$html .= $form->hidden('rfid_type',sys_param::getParam('RFID_TYPE_MEN_PARAM',5));
        //$form->add('nameT', $html, Lang::get($this->langText.'.rfid_3'), 1);
        //分類
        //$html = $form->select('rfid_type',$selectAry2,1,2);
        //$form->add('nameT', $html, Lang::get($this->langText.'.rfid_11'), 1);
        //Excel
        $html  = $form->file('excel',Lang::get($this->langText.'.rfid_63'));
        $html .= $form->hidden('rfid_type',sys_param::getParam('RFID_TYPE_MEN_PARAM',5));
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
        if(!$request->rfid_type)
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
                $errMsg = [];
                $isSuc = $isErr = 0;
                $menu = $this->pageTitleMain;
                //Excel資料
                $data = $reader->toArray();
                if ($cnt = count($data)) {
//                    dd($data);
                    foreach ($data as $key => $val) {
                        $isIns = 1;
                        if(isset($val['卡片代碼']) && isset($val['卡片內碼']) && isset($val['身分證']) )
                        {
                            $tmp = [];
                            $tmp['name']            = trim($val['卡片代碼']);
                            $tmp['rfid_code']       = trim($val['卡片內碼']);
                            $tmp['bc_id']           = trim($val['身分證']);

                            $tmp['rfid_type']       = $tid;
                            $tmp['b_rfid_id']       = b_rfid::getID($tmp['rfid_code']);
                            $tmp['b_cust_id']       = CheckLib::checkBCIDExist($tmp['bc_id']);
                            $tmp['b_supply_id']     = ($tmp['b_cust_id'])? view_door_supply_member::getSupplyID($tmp['b_cust_id']) : 0;
                            $tmp['b_factory_id']    = 0; //人不限定在特定廠區

                            //dd([$val,$tmp]);

                            //資料核對
                            //1-1. 該成員不存在
                            if(!$tmp['b_cust_id'])
                            {
                                $isIns = 0;
                                $errMsg[] = Lang::get($this->langText.'.rfid_1010',['use_name'=>$tmp['bc_id']]);
                            }
                            if(!$tmp['b_supply_id'])
                            {
                                $isIns = 0;
                                $errMsg[] = Lang::get($this->langText.'.rfid_1013',['use_name'=>$tmp['bc_id']]);
                            }
                            //1-2.該卡片與該成員 是否已經被使用
                            if($isIns && $tmp['b_rfid_id'] && view_used_rfid::isUsed($tmp['b_rfid_id'],$tmp['b_cust_id']))
                            {
                                $isIns = 0;
                                $errMsg[] = Lang::get($this->langText.'.rfid_1011',['use_name1'=>$tmp['bc_id'],'use_name2'=>$tmp['rfid_code']]);
                            }
                            //1-3.該卡片是否已被使用
                            if($isIns && $tmp['b_rfid_id'] && view_used_rfid::isUsed($tmp['b_rfid_id']))
                            {
                                $isIns = 0;
                                $errMsg[] = Lang::get($this->langText.'.rfid_1012',['use_name'=>$tmp['rfid_code']]);
                            }
                            //1-4.該成員是否已被使用
                            if($isIns && $tmp['b_rfid_id'])
                            {
                                list($used_rfid_id,$used_rfid_name,$used_rfid_code) = view_used_rfid::isUserExist($tmp['b_cust_id']);
                                if($used_rfid_id)
                                {
                                    $isIns = 0;
                                    $errMsg[] = Lang::get($this->langText.'.rfid_1013',['use_name1'=>$tmp['bc_id'],'use_name2'=>$used_rfid_code]);
                                }
                            }

                            if($isIns)
                            {
                                //如果不存在ＲＦＩＤ卡片先新增
                                if(!$tmp['b_rfid_id'])
                                {
                                    $tmp['b_rfid_id'] = $this->createRFID($tmp,$this->b_cust_id);
                                }
                                //配對
                                if($tmp['b_rfid_id'])
                                {
                                    $ret = $this->createRFIDPair($tmp,$this->b_cust_id);
                                }

                                $id  = $ret;
                                //2-1. 更新成功
                                if($ret)
                                {
                                    $isSuc++;
                                    //動作紀錄
                                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,1,'b_rfid',$id);
                                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,1,'b_rfid_pair',$id);
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
                    Session::flash('message',Lang::get($this->langText.'.rfid_62',['amt'=>$cnt,'suc'=>$isSuc,'err'=>$isErr,'err_msg'=>implode('，',$errMsg)]));
                }
            });
        }


        return \Redirect::to($this->hrefMain);
    }
}
