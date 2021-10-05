<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyEngineeringIdentityLicenseTrait;
use App\Http\Traits\Supply\SupplyEngineeringIdentityTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_license_type;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_engineering_identity_a;
use App\Model\Supply\b_supply_member;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class SupplyEngineeringIdentityLicenseController extends Controller
{
    use SupplyEngineeringIdentityTrait,SupplyEngineeringIdentityLicenseTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyEngineeringIdentityLicenseController
    |--------------------------------------------------------------------------
    |
    | 工程身分_證照 維護
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
        $this->hrefMain         = 'engineeringidentitylicense';
        $this->hrefBack         = 'engineeringidentity';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'engineeringidentitylicense/';
        $this->hrefMainNew      = 'new_engineeringidentitylicense';
        $this->routerPost       = 'postEngineeringidentitylicense';

        $this->pageTitleMain    = Lang::get($this->langText.'.title14');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list14');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new14');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit14');//編輯

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
        $iid       = SHCSLib::decode($request->iid);
        if(!$iid || !is_numeric($iid))
        {
            $msg = Lang::get($this->langText.'.supply_1016');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param1 = 'iid='.$request->iid;

            $title = b_supply_engineering_identity::getName($iid);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$title;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew.'?'.$param1;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefBack;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiSupplyEngineeringIdentityLicenseList($iid);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_51')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_54')]; //證照分類
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_53')]; //證照

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->b_supply_engineering_identity; //
                $name2        = $value->e_license; //
                $name3        = $value->e_license_type; //

                //按鈕
                //$btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param1),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name3],
                            '3'=>[ 'name'=> $name2],
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
        $licenseTypeAry = e_license_type::getSelect();
        //view元件參數
        $hrefBack       = $this->hrefMain.'?iid='.$request->iid;
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
            $A1         = $getData->b_supply_engineering_identity; //
            $A2         = $getData->e_license_id; //
            $A3         = e_license::getTypeId($getData->e_license_id); //


            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;

            $licenseAry = e_license::getSelect($A3);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_51'),1);
        //證照分類
        $html = $form->select('license_type',$licenseTypeAry,$A3);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_54'),1);
        //證照
        $html = $form->select('e_license_id',$licenseAry,$A2);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_53'),1);
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.supply_18'));
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
        $submitDiv.= $form->hidden('iid',$request->iid);
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
            $( "#license_type" ).change(function() {
                        var tid = $("#license_type").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findIdentity').'",  
                          data: { type: 1, tid : tid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#e_license_id option").remove();
                             $.each(result, function(key, val) {
                                $("#e_license_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
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
        if( !$request->has('agreeY') || !$request->id || !$request->iid || !$request->e_license_id )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        //名稱重複
        elseif(b_supply_engineering_identity_a::isExist(SHCSLib::decode($request->iid),$request->e_license_id,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10135'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id   = SHCSLib::decode($request->id);
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
        $upAry['b_supply_engineering_identity_id']  = SHCSLib::decode($request->iid);
        $upAry['e_license_id']                      = is_numeric($request->e_license_id) ? $request->e_license_id : 0;
        $upAry['isClose']                           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createSupplyEngineeringIdentityLicense($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSupplyEngineeringIdentityLicense($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_engineering_Identity',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?iid='.$request->iid);
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
    public function create(Request $request)
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
        $iid        = SHCSLib::decode($request->iid);
        $A1         = b_supply_engineering_identity::getName($iid);
        $licenseAry = e_license_type::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain.'?iid='.$request->iid;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //名稱
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_51'),1);
        //證照分類
        $html = $form->select('license_type',$licenseAry);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_54'),1);
        //證照
        $html = $form->select('e_license_id',[]);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_53'),1);

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('iid',$request->iid);
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
           $( "#license_type" ).change(function() {
                        var tid = $("#license_type").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findIdentity').'",  
                          data: { type: 1, tid : tid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#e_license_id option").remove();
                             $.each(result, function(key, val) {
                                $("#e_license_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
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
