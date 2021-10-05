<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyRPBcustTrait;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Lib\CheckLib;
use App\Model\Supply\b_supply;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;
use Storage;

class SupplyRPBcustController extends Controller
{
    use SupplyRPBcustTrait,BcustTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPBcustController
    |--------------------------------------------------------------------------
    |
    | 承攬商成員帳號開通申請單
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
        $this->hrefHome         = 'contractor';
        $this->hrefMain         = 'rp_contractorapp';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'rp_contractorapp/';
        $this->hrefMainDetail2  = 'rp_contractorapp/';
        $this->hrefMainNew      = 'new_rp_contractorapp';
        $this->routerPost       = 'postContractorrpapp';

        $this->pageTitleMain    = Lang::get($this->langText.'.title13');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list13');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new13');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit13');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_11');//[按鈕]新增
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
        //允許管理的工程案件
        $allowProjectAry= $this->allowProjectAry;
        //參數
        $no = 0;
        $out = $js = $supply = '';
        $listAry = [];
        $aprocColorAry  = ['A'=>1,'P'=>4,'R'=>4,'O'=>2,'C'=>5];
        $aprocAry       = SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC',1);
        //進度
        $aproc    = ($request->aproc)? $request->aproc : '';

        if($request->has('clear'))
        {
            $aproc = '';
            Session::forget($this->langText.'.search');
        }
        if($aproc)
        {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        } else {
            $aproc = Session::get($this->hrefMain.'.search.aproc','A');
        }
        //承攬商
        $pid      = $request->pid;
        if($pid)
        {
            $supply= b_supply::getName($pid);
            Session::put($this->hrefMain.'.search.pid',$pid);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$pid)
        {
            $listAry = $this->getApiSupplyRPBcustMainList($aproc);

        } else {
            $listAry = $this->getApiSupplyRPBcustList($pid,$aproc);
            Session::put($this->hrefMain.'.Record',$listAry);
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($pid)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        }
        $form->addHr();
        //搜尋
        $html = $form->select('aproc',$aprocAry,$aproc,2,Lang::get($this->langText.'.supply_52'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'No'];
        if(!$pid)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_12')]; //承攬商
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_39')]; //件數
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_30')]; //成員
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_29')]; //申請人
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_28')]; //申請時間
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_52')]; //進度
        }

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                if(!$pid)
                {
                    $id           = $value->b_supply_id;
                    $name1        = $value->b_supply; //
                    $name2        = $value->amt; //
                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($hrefMain,'','pid='.$id),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->id;
                    $name2        = HtmlLib::Color($value->user,'red',1); //

                    $name7        = $value->apply_name; //
                    $name8        = $value->apply_stamp; //
                    $name9        = $value->aproc_name; //
                    $aprocColor   = isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 1; //

                    //按鈕
                    $btnRoute     = ($aproc == 'A')? $this->hrefMainDetail : $this->hrefMainDetail2;
                    $btnName      = ($aproc == 'A')? Lang::get('sys_btn.btn_21') : Lang::get('sys_btn.btn_60');
                    $btn          = HtmlLib::btn(SHCSLib::url($btnRoute,$id),$btnName,1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '2'=>[ 'name'=> $name2],
                        '11'=>[ 'name'=> $name7],
                        '12'=>[ 'name'=> $name8],
                        '21'=>[ 'name'=> $name9,'label'=>$aprocColor],
                        '2'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> $btn ]
                    ];
                }

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

    /**
     * 單筆資料 新增
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js   = $contents = '';
        $id   = SHCSLib::decode($urlid);
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageEditTitle; //table header

        //資料內容
        $getData    = $this->getData($id);

        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            $pid        = $getData->b_supply_id; //
            //資料明細
            $A1         = $getData->apply_name; //
            $A2         = $getData->apply_stamp; //
            $A3         = $getData->aproc_name; //
            $A4         = $getData->user; //

            $A5         = $getData->b_cust_id; //

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //申請人
        $html = $A1;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A2;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = $A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));

        //成員
        $html = $A4;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_19'),1);
        //審查事由
        $html = $form->textarea('charge_memo');
        $html.= HtmlLib::Color(Lang::get($this->langText.'.supply_1021'),'red',1);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_50'));

        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY','','chgSubmit("agreeY")').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN','','chgSubmit("agreeN")').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('b_supply_id',$pid);
        $submitDiv.= $form->hidden('b_cust_id',$A5);
        $submitDiv.= $form->hidden('pid',$pid);
        $submitDiv.= $form->hidden('submitBtn','');
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,1));
        $contents = $content->output();
        //-------------------------------------------//
        //  View -> JavaScript
        //-------------------------------------------//
        $js = '$(function () {
            $("form").submit(function() {
                      $(this).find("input[type=\'submit\']").prop("disabled",true);
                    });
            $("#edate").datepicker({
                format: "yyyy-mm-dd",
                changeYear: true, 
                language: "zh-TW"
            });
        });
        function chgSubmit(btnTitle)
        {
            $("#submitBtn").val(btnTitle);
        }
        ';
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
        /**
         * 第一階段：規則檢核
         */
        //1-1. 取得「事件」按鈕
        $submitBtn = $request->submitBtn;

        //資料不齊全
        if( !$request->id || !$request->b_cust_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        /**
         * 第二階段：參數
         */
        $this->getBcustParam();
        $pid  = $request->b_supply_id;
        $id   = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;

        if(!$pid && is_numeric($pid) && $pid > 0)
        {
            $msg = Lang::get($this->langText.'.supply_1000');
            return \Redirect::back()->withErrors($msg);
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['b_cust_id']         = $request->b_cust_id;
        $upAry['charge_memo']       = $request->charge_memo;
        $upAry['b_supply_id']       = $pid;
        /**
         * 第三階段：同意
         */
        if($submitBtn == 'agreeY')
        {
            if(User::isLogin($request->b_cust_id))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1012'))
                    ->withInput();
            } else {
                $isAgree        = 1;
                $upAry['aproc'] = 'O';
            }
        }
        /**
         * 第三階段：不同意
         */
        if($submitBtn == 'agreeN')
        {
            if (!$request->charge_memo)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1021'))
                    ->withInput();
            } else {
                $isAgree        = 0;
                $upAry['aproc'] = 'C';
            }
        }
        //dd([$isAgree,$upAry]);

        //新增
        if($isNew)
        {
            //$ret = $this->createSupplyRPBcust($upAry,$this->b_cust_id);
            $ret = 0;
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSupplyRPBcust($id,$upAry,$this->b_cust_id);
        }
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
                if($isAgree)
                {
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust',$id);
                }
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_rp_bcust',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }

    /**
     * 單筆資料 新增
     */
    public function show2(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js   = $contents = '';
        $id   = SHCSLib::decode($urlid);
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageEditTitle; //table header

        //資料內容
        $getData    = $this->getData($id);

        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            $pid        = $getData->b_supply_id; //
            //資料明細
            $A1         = $getData->apply_user; //
            $A2         = $getData->apply_stamp; //
            $A3         = $getData->aproc_name; //
            $A4         = $getData->user; //

            $A5         = $getData->b_cust_id; //
            $A6         = $getData->charge_memo; //

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //申請人
        $html = $A1;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A2;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = $A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));
        //審查事由
        $html = $A6;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_50'),1);

        //成員
        $html = $A4;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_19'),1);

        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,1));
        $contents = $content->output();
        //-------------------------------------------//
        //  View -> JavaScript
        //-------------------------------------------//
        $js = '$(function () {
            $("#edate").datepicker({
                format: "yyyy-mm-dd",
                changeYear: true, 
                language: "zh-TW"
            });
        });';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * 取得 指定對象的資料內容
     * @param int $uid
     * @return array
     */
    protected function getData($uid = 0)
    {
        $ret  = array();
        $data = Session::get($this->hrefMain.'.Record');
        //dd($data);
        if( $data && count($data))
        {
            if($uid)
            {
                foreach ($data as $v)
                {
                    if($v->id == $uid)
                    {
                        $ret = $v;
                        break;
                    }
                }
            }
        }
        return $ret;
    }

}
