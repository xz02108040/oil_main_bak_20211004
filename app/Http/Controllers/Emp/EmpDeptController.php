<?php

namespace App\Http\Controllers\Emp;

use App\Http\Controllers\Controller;
use App\Http\Traits\Emp\EmpDeptTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_factory;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class EmpDeptController extends Controller
{
    use EmpDeptTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | EmpDeptController
    |--------------------------------------------------------------------------
    |
    | 組織部門
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
        $this->hrefMain         = 'empdept';
        $this->hrefDeptTitle    = 'empdepttitle';
        $this->langText         = 'sys_emp';

        $this->hrefMainDetail   = 'empdept/';
        $this->hrefMainNew      = 'new_empdept';
        $this->routerPost       = 'postEmpDept';

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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $no  = 0;
        $out = $js ='';
        $closeAry  = SHCSLib::getCode('CLOSE');
        $yesAry    = SHCSLib::getCode('YES');
        $parent    = ($request->pid)? $request->pid : 0;
        $deptAry   = be_dept::getSelect();
        //view元件參數
        $parentName= isset($deptAry[$parent])? ' 》'.$deptAry[$parent] : '';
        $tbTitle  = $this->pageTitleList.$parentName;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiBeDeptList($parent);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y') $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_3')]; //部門
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_7')]; //部門
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_6')]; //上一層
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_17')]; //實體部門
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_30')]; //負責全廠的部門
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_9')]; //排序
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_103')]; //狀態
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_16')]; //下一層

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->name; //
                $name6        = $value->store; //
                $name2        = $value->parent; //
                $name3        = $value->show_order; //
                $name5        = isset($yesAry[$value->isEmp])? $yesAry[$value->isEmp] : '' ; //實體部門
                $isEmpColor   = $value->isEmp == 'Y' ? 2 : 5 ; //顏色
                $name7        = isset($yesAry[$value->isFullField])? $yesAry[$value->isFullField] : '' ; //負責全廠的部門
                $isFullColor = $value->isFullField == 'Y' ? 5 : 2 ; //停用顏色
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕
//                $btn2         = HtmlLib::btn(SHCSLib::url($this->hrefDeptTitle,'','pid='.$id),Lang::get('sys_btn.btn_31'),3); //按鈕
                $btn3         = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','pid='.$id),Lang::get('sys_btn.btn_27'),3); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $id,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '11'=>[ 'name'=> $name6],
                            '2'=>[ 'name'=> $name2],
                            '4'=>[ 'name'=> $name5,'label'=>$isEmpColor],
                            '7'=>[ 'name'=> $name7,'label'=>$isFullColor],
                            '3'=>[ 'name'=> $name3],
                            '21'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
                            '97'=>[ 'name'=> $btn3 ],
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
        $store   = b_factory::getSelect();
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
            $A2         = $getData->parent_id; //
            $A3         = $getData->show_order; //
            $A5         = $getData->b_factory_id; //
            $A6         = ($getData->isEmp == 'Y')? true : false;
            $A7         = ($getData->isFullField == 'Y')? true : false;

            $A97        = ($getData->closer_user)? Lang::get('sys_base.base_10614',['name'=>$getData->closer_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;

            $deptAry = be_dept::getSelect();
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //部門
        $html = $form->select('b_factory_id',$store,$A5);
        $form->add('nameT1', $html,Lang::get($this->langText.'.emp_7'),1);
        //部門
        $html = $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.emp_3'),1);
        //上層部門
        if($id == 1)
        {
            $html = Lang::get('sys_base.base_10016');
            $html.= $form->hidden('parent_id',0);
        } else {
            $html = $form->select('parent_id',$deptAry,$A2);
        }
        $form->add('nameT2', $html,Lang::get($this->langText.'.emp_6'),1);
        //排序
        $html = $form->text('show_order',$A3);
        $form->add('nameT2', $html,Lang::get($this->langText.'.emp_9'));
        //是否實體部門
        $html = $form->checkbox('isEmp','Y',$A6);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.emp_17'));
        //負責全廠的部門
        $html = $form->checkbox('isFullField','Y',$A7);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.emp_30'));
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.emp_18'));
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
        if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->has('parent_id'))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif($request->parent_id < 0)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.emp_101'))
                ->withInput();
        }
        elseif(be_dept::isNameExist($request->name,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10110'))
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
        $upAry['b_factory_id']      = $request->b_factory_id;
        $upAry['parent_id']         = $request->parent_id;
        $upAry['level']             = $request->level;
        $upAry['show_order']        = $request->show_order;
        $upAry['isEmp']             = ($request->isEmp == 'Y')? 'Y' : 'N';
        $upAry['isFullField']       = ($request->isFullField == 'Y')? 'Y' : 'N';
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createBeDept($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setBeDept($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'be_dept',$id);

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
        $store = b_factory::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header

        $deptAry = be_dept::getSelect();
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //部門
        $html = $form->text('name');
        $form->add('nameT1', $html,Lang::get($this->langText.'.emp_3'),1);
        //廠區
        $html = $form->select('b_factory_id',$store,0);
        $form->add('nameT1', $html,Lang::get($this->langText.'.emp_7'),1);
        //上一層
        $html = $form->select('parent_id',$deptAry,0);
        $form->add('nameT1', $html,Lang::get($this->langText.'.emp_6'),1);
        //排序
        $html = $form->text('show_order',999);
        $form->add('nameT2', $html,Lang::get($this->langText.'.emp_9'));
        //是否實體部門
        $html = $form->checkbox('isEmp','Y',true);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.emp_17'));
        //負責全廠的部門
        $html = $form->checkbox('isFullField','Y');
        $form->add('isCloseT',$html,Lang::get($this->langText.'.emp_30'));

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
            
            $( "#b_factory_id" ).change(function() {
                        var sid  = $("#b_factory_id").val();
                        
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 5, sid : sid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#parent_id option").remove();
                             $.each(result, function(key, val) {
                                $("#parent_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
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
