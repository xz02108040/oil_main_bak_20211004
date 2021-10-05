<?php

namespace App\Http\Controllers;


use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\b_menu_group;
use App\Model\bc_type_app;
use App\Model\c_menu_group;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\sys_code;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class BcustContractorController extends Controller
{
    use BcustTrait,BcustATrait,EmpTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | BcustContractorController
    |--------------------------------------------------------------------------
    |
    | 帳號管理->承攬商
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
        $this->hrefMain         = 'userc';
        $this->hrefSupply       = 'contractor';
        $this->langText         = 'sys_base';

        $this->hrefMainDetail   = 'userc/';
        $this->hrefMainNew      = 'new_userc';
        $this->routerPost       = 'postBcustC';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_10730');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_10731');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.base_10732');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.base_10733');//編輯

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
        $closeAry   = SHCSLib::getCode('CLOSE');
        $loginAry   = SHCSLib::getCode('LOGIN');
        $lastInLimit= sys_param::getParam('DOOR_LAST_IN_DAYS_LIMIT');
        $pid        = SHCSLib::decode($request->pid);
        if(!$pid && is_numeric($pid) && $pid > 0)
        {
            $msg = Lang::get('sys_supply.supply_1000');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = 'pid='.$request->pid;
            $supply= b_supply::getName($pid);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew.$request->pid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefSupply;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiMemberList($pid);
        Session::put($this->hrefMain.'.supply_id',$pid);
        Session::put($this->hrefMain.'.pid',$request->pid);

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
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10707')]; //姓名
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10708')]; //帳號
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10718')]; //帳號身分
//        $heads[] = ['title'=>Lang::get($this->langText.'.base_10720')]; //ＡＰＰ身分
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10710')]; //群組
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10738')]; //最後進廠日
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10739')]; //未進廠天數
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10719')]; //是否允許登入
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10711')]; //停用

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $id           = $value->id;
                $name1        = $value->name; //
                $name2        = SHCSLib::genBCID($value->account); //
                $name3        = $value->c_menu_group; //
                $name5        = $value->bc_type_name; //
//                $name6        = $value->bc_type_app_name; //
                $name7        = $value->last_door_stamp; //最後進廠日
                $name8        = SHCSLib::getBetweenDays($value->last_door_stamp); //最後進廠日
                if(!$name8) {
                    $name8 = '';
                }
                elseif($value->last_door_stamp >= $lastInLimit)
                {
                    $name8    = HtmlLib::Color($name8,'red',1);
                }
                $isLogin      = isset($loginAry[$value->isLogin])? $loginAry[$value->isLogin] : '' ; //停用
                $isLoginColor = $value->isLogin == 'Y' ? 2 : 5 ; //停用顏色
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $id,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '5'=>[ 'name'=> $name5],
                            '3'=>[ 'name'=> $name3],
//                            '4'=>[ 'name'=> $name6],
                            '12'=>[ 'name'=> $name7],
                            '13'=>[ 'name'=> $name8],
                            '20'=>[ 'name'=> $isLogin,'label'=>$isLoginColor],
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
        $menuGroupExAry = ($this->isRoot)? [] : [2];//排除 最高權限群組
        $pid            = SHCSLib::decode($request->pid);
        if(!$pid && is_numeric($pid) && $pid > 0)
        {
            $msg = Lang::get($this->langText.'.supply_1000');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = '?pid='.$request->pid;
            $supply= b_supply::getName($pid);
        }
        //view元件參數
        $hrefBack       = $this->hrefMain.$param;
        $btnBack        = $this->pageBackBtn;
        $Icon           = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle        = $this->pageTitleList.$Icon.$supply;//列表標題
        //資料內容
        $getData        = $this->getData($id);
        //下拉資料
        $menuGroupAry   = c_menu_group::getSelect($menuGroupExAry);
        $bctypeAry      = SHCSLib::getCode('BC_TYPE');

        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get($this->langText.'.base_10102'));
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //資料明細
            $A1         = $getData->name; //
            $A2         = $getData->account; //
            $A3         = $getData->c_menu_group_id; //
            $A4         = $getData->bc_type; ///
            ///

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = Lang::get($this->langText.'.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]); //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $A91        = ($getData->isLogin == 'Y')? true : false;

//            $bctypeappAry = bc_type_app::getSelect($A4);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_10707'),1);
        //帳號
        $html = $form->text('account',$A2);
        $form->add('nameT2', $html,Lang::get($this->langText.'.base_10708'),1);
        //密碼
        $html = $form->pwd('password');
        $form->add('nameT3', $html,Lang::get($this->langText.'.base_10709'));
        //帳號身分
        $html = '<h4>'.$bctypeAry[$A4].'</h4>';// $form->select('bc_type',$bctypeAry,$A4);
        $form->add('nameT4', $html,Lang::get($this->langText.'.base_10718'),1);
        //權限群組
        $html = $form->select('c_menu_group_id',$menuGroupAry,$A3);
        $form->add('nameT6', $html,Lang::get($this->langText.'.base_10710'),1);
        //是否可登入
        $html = $form->checkbox('isLogin','Y',$A91);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.base_10719'));
        //停用
        //$html = $form->checkbox('isClose','Y',$A99);
        //$form->add('isCloseT',$html,Lang::get($this->langText.'.base_10612'));
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get($this->langText.'.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('bc_type',3);
        $submitDiv.= $form->hidden('pid',$request->pid);
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,2));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $js = '$(function () {

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
        if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->account )
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10103'))
                ->withInput();
        }
        //是否已有存在的帳號
        elseif(User::isAccountExist($request->account,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10111'))
                ->withInput();
        }
        //帳號至少三個字
        elseif(strlen($request->account) < 3)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10114'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
            $pid  = $request->pid;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        //確認密碼規則
        if(($isNew || $request->password) && strlen($request->password) < 4)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10112'))
                ->withInput();
        }
        //職員<檢查有沒有部門>
        elseif($isNew && $request->bc_type == 2 && (!$request->be_dept_id || !$request->be_title_id))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10115'))
                ->withInput();
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']              = $request->name;
        $upAry['bc_type']           = $request->bc_type ? $request->bc_type : 1;
//        $upAry['bc_type_app']       = $request->bc_type_app ? $request->bc_type_app : 0;
        $upAry['account']           = $request->account;
        $upAry['password']          = ($request->password && $request->password != '123456')? $request->password : '';
        $upAry['c_menu_group_id']   = $request->c_menu_group_id;
        $upAry['isLogin']           = ($request->isLogin == 'Y')? 'Y' : 'N';
        //$upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';
        //新增
        if($isNew)
        {
            $ret = $this->createBcust($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setBcust($id,$upAry,$this->b_cust_id);
        }
        //2-1. 更新成功
        if($ret)
        {
            //沒有可更新之資料
            if($ret === -1)
            {
                $msg = Lang::get($this->langText.'.base_10109');
                return \Redirect::back()->withErrors($msg);
            } else {
                //動作紀錄
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust',$id);
                if($isNew && $request->bc_type == 2)
                {
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust_e',$id);
                }

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get($this->langText.'.base_10104'));
                return \Redirect::to($this->hrefMain.'?pid='.$pid);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get($this->langText.'.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }

    /**
     * 取得 指定對象的資料內容
     * @param int $uid
     * @return array
     */
    protected function getData($uid = 0)
    {
        $ret  = User::find($uid);

        return $ret;
    }

}
