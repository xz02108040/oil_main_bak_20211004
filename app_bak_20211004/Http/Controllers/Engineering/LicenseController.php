<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\LicenseTrait;
use App\Http\Traits\Engineering\EngineeringTypeTrait;
use App\Http\Traits\Factory\FactoryTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\Engineering\e_license_type;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_code;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class LicenseController extends Controller
{
    use LicenseTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | LicenseController
    |--------------------------------------------------------------------------
    |
    | 證照 維護
    |
    */

    /**
     * 環境參數
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     */
    public function __construct(Request $request)
    {
        //身分驗證
        $this->middleware('auth');
        //讀取選限
        $this->uri              = SHCSLib::getUri($request->route()->uri);
        $this->isWirte          = 'N';
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'elicense';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'elicense/';
        $this->hrefMainNew      = 'new_elicense';
        $this->routerPost       = 'postELicense';

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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $out = $js ='';
        $no  = 0;
        $closeAry = SHCSLib::getCode('CLOSE');
        $edateAry = SHCSLib::getCode('EDATE_TYPE');
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiLicenseList();
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_21')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_3')];  //分類
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_22')]; //證號
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_24')]; //期限顯示名稱
        //$heads[] = ['title'=>Lang::get($this->langText.'.engineering_23')]; //證號名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_74')]; //檔案1
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_75')]; //檔案2
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_76')]; //檔案3
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_108')]; //發證方式
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_77')]; //證照有效日年
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_78')]; //證號名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_33')]; //狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->name; //
                $name2        = $value->type; //
                $name3        = $value->license_show_name1; //
                $name4        = $value->license_show_name2; //
                $name5        = $value->license_show_name3; //
                $name6        = $value->license_show_name4; //
                $name7        = $value->license_show_name5; //
                $name12       = $value->license_issuing_kind_name; //
                $name8        = $value->edate_limit_year1; //
                $name9        = $value->edate_limit_year2; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '4'=>[ 'name'=> $name3],
                            '5'=>[ 'name'=> $name4],
                            '6'=>[ 'name'=> $name5],
                            '7'=>[ 'name'=> $name6],
                            '8'=>[ 'name'=> $name7],
                            '12'=>[ 'name'=> $name12],
                            '9'=>[ 'name'=> $name8],
                            '10'=>[ 'name'=> $name9],
                            '21'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
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
     * 單筆資料 編輯
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $typeAry  = e_license_type::getSelect();
        $edateAry = SHCSLib::getCode('EDATE_TYPE',1);
        $typeAry1 = b_supply_engineering_identity::getSelect();
        $typeAry2 = SHCSLib::getCode('LICENSE_ISSUING_KIND');
        $typeAry3= SHCSLib::getCode('LICENSE_ISSUING_FILE_KIND');
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //資料明細
            $A1         = $getData->name; //
            $A2         = $getData->license_type; //
            $A3         = $getData->license_show_name1; //
            $A4         = $getData->license_show_name2; //
            $A5         = $getData->edate_type; //
            $A6         = $getData->license_show_name3; //
            $A7         = $getData->license_show_name4; //
            $A8         = $getData->license_show_name5; //
            $A9         = $getData->edate_limit_year1; //
            $A10        = $getData->edate_limit_year2; //
            $A12        = $getData->license_issuing_kind; //
            $A13        = $getData->license_issuing_kind3; //
            $A14        = $getData->license_issuing_kind4; //
            $A15        = $getData->license_issuing_kind5; //

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_21'),1);
        //分類
        $html = $form->select('license_type',$typeAry,$A2);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_3'),1);
        //顯示名稱:證號
        $html = $form->text('license_show_name1',$A3);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_22'),1);
        //顯示名稱：日期
        $html = $form->text('license_show_name2',$A4);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_24'),1);
        //分類
        //$html = $form->select('edate_type',$edateAry,$A5);
        //$form->add('nameT3', $html,Lang::get($this->langText.'.engineering_23'),1);
        //發證方式
        $html = $form->select('license_issuing_kind',$typeAry2,$A12);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_108'),1);
        //證照有效日年
        $html = $form->text('edate_limit_year1',$A9);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_77'),1);
        //回訓有效年
        $html = $form->text('edate_limit_year2',$A10);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_78'),1);
        //證明文件名稱MEMO
        $html = HtmlLib::Color(Lang::get($this->langText.'.engineering_1044'),'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_79'));
        //證明文件名稱1
        $html = $form->text('license_show_name3',$A6);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_74'));
        $html = $form->select('license_issuing_kind3',$typeAry3,$A13);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_128'));
        //證明文件名稱2
        $html = $form->text('license_show_name4',$A7);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_75'));
        $html = $form->select('license_issuing_kind4',$typeAry3,$A14);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_128'));
        //證明文件名稱3
        $html = $form->text('license_show_name5',$A8);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_76'));
        $html = $form->select('license_issuing_kind5',$typeAry3,$A15);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_128'));
        //開始日期
//        $html = $form->date('sdate',$A3);
//        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_22'),1);
        //結束日期
//        $html = $form->date('edate',$A4);
//        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_23'),1);
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_34'));
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,2));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> Javascript
        //-------------------------------------------//
        $js = '$(function () {
            $("#sdate,#edate").datepicker({
                format: "yyyy-mm-dd",
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
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->license_show_name1 || !$request->license_show_name2 )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        //證照有效日年至少為1
        elseif(!$request->license_issuing_kind){
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1045'))
                ->withInput();
        }
        //工程身分對應
//        elseif(!$request->engineering_identity_id){
//            return \Redirect::back()
//                ->withErrors(Lang::get($this->langText.'.engineering_1046'))
//                ->withInput();
//        }
        //證照有效日年至少為1
        elseif(!$request->edate_limit_year1 || !is_numeric($request->edate_limit_year1)){
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1042'))
                ->withInput();
        }
        //回訓有效年至少為1
        elseif(!$request->edate_limit_year2 || !is_numeric($request->edate_limit_year2)){
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1043'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']              = $request->name;
        $upAry['license_type']            = is_numeric($request->license_type) ? $request->license_type : 1;
        $upAry['license_issuing_kind']    = is_numeric($request->license_issuing_kind) ? $request->license_issuing_kind : 1;
        $upAry['license_show_name1']= strlen($request->license_show_name1) ? trim($request->license_show_name1) : '';
        $upAry['license_show_name2']= strlen($request->license_show_name2) ? trim($request->license_show_name2) : '';
        $upAry['license_show_name3']= isset($request->license_show_name3) ? trim($request->license_show_name3) : '';
        $upAry['license_show_name4']= isset($request->license_show_name4) ? trim($request->license_show_name4) : '';
        $upAry['license_show_name5']= isset($request->license_show_name5) ? trim($request->license_show_name5) : '';
        $upAry['license_issuing_kind3']= isset($request->license_issuing_kind3) ? trim($request->license_issuing_kind3) : 0;
        $upAry['license_issuing_kind4']= isset($request->license_issuing_kind4) ? trim($request->license_issuing_kind4) : 0;
        $upAry['license_issuing_kind5']= isset($request->license_issuing_kind5) ? trim($request->license_issuing_kind5) : 0;
        $upAry['edate_limit_year1']= isset($request->edate_limit_year1) ? $request->edate_limit_year1 : 0;
        $upAry['edate_limit_year2']= isset($request->edate_limit_year2) ? $request->edate_limit_year2 : 0;
        $upAry['edate_type']        = strlen($request->edate_type) ? $request->edate_type : '';
        $upAry['sdate']             = date('Y-m-d');//CheckLib::isDate($request->sdate) ? $request->sdate : '';
        $upAry['edate']             = '9999-12-31';//CheckLib::isDate($request->edate) ? $request->edate : '';
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createLicense($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setLicense($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'e_license',$id);

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
    public function create()
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        if($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        }
        //參數
        $js = $contents = '';
        $typeAry = e_license_type::getSelect();
        $edateAry = SHCSLib::getCode('EDATE_TYPE',1);
        $typeAry1= b_supply_engineering_identity::getSelect();
        $typeAry2= SHCSLib::getCode('LICENSE_ISSUING_KIND');
        $typeAry3= SHCSLib::getCode('LICENSE_ISSUING_FILE_KIND');
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html = $form->text('name');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_21'),1);
        //分類
        $html = $form->select('license_type',$typeAry,1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_3'),1);

        //顯示名稱:證號
        $html = $form->text('license_show_name1',Lang::get($this->langText.'.engineering_72'));
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_22'),1);
        //顯示名稱：日期
        $html = $form->text('license_show_name2',Lang::get($this->langText.'.engineering_73'));
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_24'),1);
        //分類
        $html = $form->select('edate_type',$edateAry,'edate');
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_23'),1);
        //發證方式
        $html = $form->select('license_issuing_kind',$typeAry2,1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_108'),1);
        //證照有效日年
        $html = $form->text('edate_limit_year1',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_77'),1);
        //回訓有效年
        $html = $form->text('edate_limit_year2',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_78'),1);
        //證明文件名稱MEMO
        $html = HtmlLib::Color(Lang::get($this->langText.'.engineering_1044'),'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_79'));
        //證明文件名稱1
        $html = $form->text('license_show_name3','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_74'));
        $html = $form->select('license_issuing_kind3',$typeAry3,0);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_128'));
        //證明文件名稱2
        $html = $form->text('license_show_name4','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_75'));
        $html = $form->select('license_issuing_kind4',$typeAry3,0);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_128'));
        //證明文件名稱3
        $html = $form->text('license_show_name5','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_76'));
        $html = $form->select('license_issuing_kind5',$typeAry3,0);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_128'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,1));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> Javascript
        //-------------------------------------------//
        $js = '$(function () {
           $("#sdate,#edate").datepicker({
                format: "yyyy-mm-dd",
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
