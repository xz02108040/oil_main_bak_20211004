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
use App\Model\Emp\be_dept;
use App\Model\Factory\b_factory;
use App\Model\sys_code;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class BcustController extends Controller
{
    use BcustTrait,BcustATrait,EmpTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | BcustController
    |--------------------------------------------------------------------------
    |
    | 帳號管理
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
        $this->hrefMain         = 'user';
        $this->hrefEmp          = 'emp';
        $this->hrefPwd          = 'genBcustPwd';
        $this->langText         = 'sys_base';

        $this->hrefMainDetail   = 'user/';
        $this->hrefMainNew      = 'new_user';
        $this->routerPost       = 'postBcust';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_10700');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_10701');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.base_10702');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.base_10703');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_79');//[按鈕]新增
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
        $bctypeAry  = SHCSLib::getCode('BC_TYPE', true);
        $aid      = $request->aid;
        $bid      = $request->bid;

        //清除搜尋紀錄
        if ($request->has('clear')) {
            $aid = $bid = '';
            Session::forget($this->hrefMain . '.search');
        }
        if (!$aid) {
            $aid = Session::get($this->hrefMain . '.search.aid', 0);
        } else {
            Session::put($this->hrefMain . '.search.aid', $aid);
        }
        if (!$bid) {
            $bid = Session::get($this->hrefMain . '.search.bid', '');
        } else {
            Session::put($this->hrefMain . '.search.bid', $bid);
        }
        
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefPwd;
        $btnNew   = $this->pageNewBtn;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = empty($aid) ? [] : $this->getApiCustList([$aid], $bid);
//        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //搜尋
        $html = $form->select('aid', $bctypeAry, $aid, 2, Lang::get($this->langText . '.base_11125'));
        $html .= $form->text('bid', $bid, 2, Lang::get($this->langText . '.base_40229') . '/' . Lang::get($this->langText . '.base_40250') . Lang::get($this->langText . '.base_10307'), '', 0, 2);
        $html .= $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search');
        $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear');
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.base_10741'),'red',1);
        $form->addRowCnt($html);
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10707')]; //姓名
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10708')]; //帳號
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11125')]; //類型
        $heads[] = ['title'=>Lang::get($this->langText . '.base_40229') . '/' . Lang::get($this->langText . '.base_40250') . Lang::get($this->langText . '.base_10307')]; //轄區部門/承攬商名稱
//        $heads[] = ['title'=>Lang::get($this->langText.'.base_10720')]; //ＡＰＰ身分
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10710')]; //群組
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10719')]; //是否允許登入
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10711')]; //停用

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                //如果是 本身不是最高權限群組 ，不得修改 特定帳號
                if($this->b_menu_group_id != 2 && $value->isRoot == 'Y')
                {
                    continue;
                }
                $id           = $value->id;
                $name1        = $value->name; //
                $name2        = $value->account; //
                $name3        = $value->b_menu_group; //
                $name5        = $value->bc_type_name; //
                $name6        = $value->bc_type_unit_name;
//                $name6        = $value->bc_type_app_name; //
                $isLogin      = isset($loginAry[$value->isLogin])? $loginAry[$value->isLogin] : '' ; //停用
                $isLoginColor = $value->isLogin == 'Y' ? 2 : 5 ; //停用顏色
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $id,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name5],
                            '4'=>[ 'name'=> $name6],
//                            '4'=>[ 'name'=> $name6],
                            '11'=>[ 'name'=> $name3],
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
        $isEmp          = ($request->isEmp == 'Y')? 1 : 0;
        //view元件參數
        $hrefBack       = ($isEmp)? $this->hrefEmp : $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $this->getData($id);
        //下拉資料
        $menuGroupAry   = b_menu_group::getSelect($menuGroupExAry);
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
            $A3         = $getData->b_menu_group_id; //
            $A4         = $getData->bc_type; //
//            $A5         = $getData->bc_type_app; //

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = Lang::get($this->langText.'.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]); //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $A91        = ($getData->isLogin == 'Y')? true : false;

//            $bctypeappAry = bc_type_app::getSelect($A4);

            $isRoot     = ($A4 == 1 && $id <= '2100000000')? 1 : 0;
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
        //ＡＰＰ身分
//        $html = $form->select('bc_type_app',$bctypeappAry,$A5);
//        $form->add('nameT5', $html,Lang::get($this->langText.'.base_10720'));
        //權限群組
        $html = ($isRoot)? $menuGroupAry[$A3] : $form->select('b_menu_group_id',$menuGroupAry,$A3);
        $form->add('nameT6', $html,Lang::get($this->langText.'.base_10710'),1);
        //是否可登入
        $html = ($isRoot)? Lang::get($this->langText.'.base_10734') : $form->checkbox('isLogin','Y',$A91);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.base_10719'));
        //停用
        $html = ($isRoot)? Lang::get($this->langText.'.base_10734') : $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.base_10612'));
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
        $submitDiv.= $form->hidden('bc_type',$A4);
        $submitDiv.= $form->hidden('isEmp',$isEmp);
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
        $id = SHCSLib::decode($request->id);
        $isRoot     = ($request->bc_type == 1 && $id <= '2100000000')? 1 : 0;
        if(!$isRoot)
        {
            //資料不齊全
            if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->account || !$request->b_menu_group_id)
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
        }
        $this->getBcustParam();
        $id = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;
        $hrefBack = ($request->isEmp)? $this->hrefEmp : $this->hrefMain;

        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $password = $request->password;
        if($password == '******' || $password == '123456') $password = '';
        //確認密碼規則
        if(($isNew || $password) && strlen($password) < 4)
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
//        $upAry['bc_type_app']       = $request->bc_type_app ? $request->bc_type_app : 0;
        $upAry['account']           = $request->account;
        if($password)
        {
            $upAry['password']      = $password;
        }
        if(!$isRoot)
        {
            $upAry['bc_type']           = $request->bc_type ? $request->bc_type : 1;
            $upAry['b_menu_group_id']   = $request->b_menu_group_id;
            $upAry['isLogin']           = ($request->isLogin == 'Y')? 'Y' : 'N';
            $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';
        }

        if($isNew)
        {
            $upAry['emp_no']            = $request->account;
            $upAry['b_factory_id']      = $request->b_factory_id;
            $upAry['be_dept_id']        = $request->be_dept_id;
            $upAry['be_title_id']       = $request->be_title_id;
            $upAry['boss_id']           = $request->boss_id;
            $upAry['attorney_id']       = $request->attorney_id;
        }
//        dd($upAry);
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
                return \Redirect::to($hrefBack);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get($this->langText.'.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }

    /**
     * 單筆資料 新增[職員/承攬商]
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
        //$menuGroupExAry = ($this->isRoot == 'Y')? [] : [2];//排除 最高權限群組
        $menuGroupExAry = [2];//排除 最高權限群組
        $bctypeAry      = SHCSLib::getCode('BC_TYPE');
        unset($bctypeAry[3]);//移除承攬商
        //權限
        $beGroupAry     = b_menu_group::getSelect($menuGroupExAry);

        $deptAry        = be_dept::getSelect();
        $titleAry       = $empAry = [];
        $storeAry       = b_factory::getSelect();
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
        $form->add('titleT1', $html,Lang::get($this->langText.'.base_10707'),1);
        //帳號身分
        $html = $form->select('bc_type',$bctypeAry);
        $form->add('nameT4', $html,Lang::get($this->langText.'.base_10718'),1);
        //ＡＰＰ身分
//        $form->addHtml('<div id="bctype_div" style="display: none">');
//        $html = $form->select('bc_type_app',[]);
//        $form->add('nameT5', $html,Lang::get($this->langText.'.base_10720'));
//        $form->addHtml('</div>');
        //帳號
        $html = $form->text('account');
        $form->add('titleT2', $html,Lang::get($this->langText.'.base_10708'),1);
        //密碼
        $html = $form->text('password');
        $form->add('titleT3', $html,Lang::get($this->langText.'.base_10709'),1);
        //權限群組
        $html = $form->select('b_menu_group_id',$beGroupAry,'1');
        $form->add('titleT4', $html,Lang::get($this->langText.'.base_10710'),1);
        //是否可登入
        $html = $form->checkbox('isLogin','Y',false);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.base_10719'));
        //====== 職員身分 ======
        $form->addHtml('<div id="emp_div" style="display: none">');
        //部門
        $html = $form->select('b_factory_id', $storeAry);
        $form->add('nameT1', $html,Lang::get('sys_emp.emp_7'),1);
        //部門
        $html = $form->select('be_dept_id', $deptAry);
        $form->add('nameT2', $html,Lang::get('sys_emp.emp_3'),1);
        //職稱
        $html = $form->select('be_title_id', $titleAry);
        $form->add('nameT3', $html,Lang::get('sys_emp.emp_2'),1);
        //主管
        $html = $form->select('boss_id', $empAry);
        $form->add('nameT4', $html,Lang::get('sys_emp.emp_11'));
        //代理人
        $html = $form->select('attorney_id', $empAry);
        $form->add('nameT5', $html,Lang::get('sys_emp.emp_12'));
        $form->addHtml('</div>');

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
        //  View -> JavaScript
        //-------------------------------------------//
        $js = '
        $(document).ready(function() {
            if($( "#bc_type" ).val() == 2 )
            {
                $("#emp_div").show();
            } else {
                $("#emp_div").hide();
            }
            $( "#bc_type" ).change(function() {
                if($( this ).val() == 2 )
                {
                    $("#emp_div").show();
                } else {
                    $("#emp_div").hide();
                }
            });
            $( "#bc_type" ).change(function() {
                        var tid = $("#bc_type").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findBcType').'",
                          data: { type: 2, tid : tid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             var count = Object.keys(result).length;
                             $("#bc_type_app option").remove();
                             if(count > 1)
                             {
                                $("#bctype_div").show();
                                $.each(result, function(key, val) {
                                    $("#bc_type_app").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                });
                             } else {
                                $("#bctype_div").hide();
                             }

                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
            });
            $( "#be_dept_id" ).change(function() {
                        var eid = $("#be_dept_id").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 1, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#be_title_id option").remove();
                             $.each(result, function(key, val) {
                                $("#be_title_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 2, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#boss_id option").remove();
                             $("#attorney_id option").remove();
                             $.each(result, function(key, val) {
                                $("#boss_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                $("#attorney_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
        });
        ';
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
        $ret  = User::find($uid);

        return $ret;
    }

    /**
     * 取得 指定對象的資料內容
     * @param int $uid
     * @return array
     */
    protected function genBcustPwd()
    {
        $ret  = $this->chgBcustHasNotPwd();

        Session::flash('message','補帳號密碼共'.$ret.'筆');
        return \Redirect::to($this->hrefMain);
    }

}
