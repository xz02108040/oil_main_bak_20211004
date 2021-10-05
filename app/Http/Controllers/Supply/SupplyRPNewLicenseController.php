<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\LicenseTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyRPBcustTrait;
use App\Http\Traits\Supply\SupplyRPNewLicenseTrait;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Lib\CheckLib;
use App\Model\Engineering\e_license;
use App\Model\Supply\b_supply;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;
use Storage;

class SupplyRPNewLicenseController extends Controller
{
    use SupplyRPNewLicenseTrait,LicenseTrait,BcustTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPNewLicenseController
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
        $this->hrefMain         = 'rp_contractornewlicense';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'rp_contractornewlicense/';
        $this->hrefMainDetail2  = 'rp_contractornewlicense2/';
        $this->hrefMainNew      = 'new_rp_contractornewlicense';
        $this->routerPost       = 'postContractorNewlicense';

        $this->pageTitleMain    = Lang::get($this->langText.'.title17');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list17');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new17');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit17');//編輯

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
        //參數
        $no = 0;
        $out = $js = $supply = '';
        $closeAry = SHCSLib::getCode('CLOSE');
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
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiSupplyRPNewLicenseList(0,$aproc);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
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
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_100')]; //專業證照種類
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_75')]; //證照類型
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_76')]; //建議發證有效年
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_77')]; //建議回訓有效年
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_29')]; //申請人
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_28')]; //申請時間
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_52')]; //進度

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name2        = HtmlLib::Color($value->license_name,'red',1); //
                $name3        = HtmlLib::Color($value->license_type_name,'',1); //
                $name4        = $value->edate_limit_year1;
                $name5        = $value->edate_limit_year2;

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
                    '3'=>[ 'name'=> $name3],
                    '4'=>[ 'name'=> $name4],
                    '5'=>[ 'name'=> $name5],
                    '11'=>[ 'name'=> $name7],
                    '12'=>[ 'name'=> $name8],
                    '21'=>[ 'name'=> $name9,'label'=>$aprocColor],
                    '2'=>[ 'name'=> $name2],
                    '99'=>[ 'name'=> $btn ]
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
            $A4         = $getData->license_name; //
            $A5         = $getData->license_type_name; //
            $A6         = $getData->edate_limit_year1; //
            $A7         = $getData->edate_limit_year2; //

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

        //專業證照種類
        $html = $A4;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_100'),1);
        //證照類型
        $html = $A5;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_75'),1);
        //建議發證有效年
        $html = $A6;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_76'),1);
        //建議回訓有效年
        $html = $A7;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_77'),1);
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
        $submitDiv.= $form->hidden('license_name',$A4);
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
        if( !$request->id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        /**
         * 第二階段：參數
         */
        $this->getBcustParam();
        $id   = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;

        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['charge_memo']       = $request->charge_memo;
        /**
         * 第三階段：同意
         */
        if($submitBtn == 'agreeY')
        {

            //重複[專業證照]名稱
            if(e_license::isNameExist($request->license_name)) {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1044'))
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
            $ret = 0;
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSupplyRPNewLicense($id,$upAry,$this->b_cust_id);
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
            //資料明細
            $A1         = $getData->apply_name; //
            $A2         = $getData->apply_stamp; //
            $A3         = $getData->aproc_name; //
            $A4         = $getData->license_name; //
            $A5         = $getData->license_type_name; //
            $A6         = $getData->edate_limit_year1; //
            $A7         = $getData->edate_limit_year2; //
            $A8         = $getData->charge_memo; //

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
        $html = $A8;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_50'),1);

        //成員
        $html = $A4;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_100'),1);
        //成員
        $html = $A5;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_75'),1);
        //成員
        $html = $A6;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_76'),1);
        //成員
        $html = $A7;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_77'),1);

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
