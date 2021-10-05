<?php

namespace App\Http\Controllers\Emp;

use App\Http\Controllers\Controller;
use App\Http\Traits\Emp\EmpDeptATrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_dept_a;
use App\Model\Emp\be_title;
use App\Model\sys_code;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class EmpDeptTitleController extends Controller
{
    use EmpDeptATrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | EmpDeptTitleController
    |--------------------------------------------------------------------------
    |
    | 組織部門內的職稱
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
        $this->hrefHome         = '/';
        $this->hrefMain         = 'empdepttitle';
        $this->hrefDept         = 'empdept';
        $this->langText         = 'sys_emp';

        $this->hrefMainDetail   = 'empdepttitle/';
        $this->hrefMainNew      = 'new_empdepttitle';
        $this->routerPost       = 'postEmpDeptTitle';

        $this->pageTitleMain    = Lang::get($this->langText.'.title3');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list3');//列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new3');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit3');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageBackDeptBtn  = Lang::get('sys_btn.btn_5').Lang::get($this->langText.'.title2');//[按鈕]返回

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
        $parent    = ($request->pid)? $request->pid : 0;
        $syscodeAry= sys_code::getSelect();
        $parentName= isset($syscodeAry[$parent])? ' 》'.$syscodeAry[$parent] : '';
        $closeAry  = SHCSLib::getCode('CLOSE');
        //view元件參數
        $tbTile   = $this->pageTitleList.$parentName; //列表標題
        $hrefMain = $this->hrefMain; //路由
        $hrefNew  = $this->hrefMainNew.($parent? '?pid='.$parent : '');
        $btnNew   = $this->pageNewBtn.$parentName;
        $hrefBack = $this->hrefDept;
        $btnBack  = $this->pageBackDeptBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($parent)
        {
            $listAry = $this->getApiBeDeptTitleSubList($parent);
            Session::put($this->hrefMain.'.Record',$listAry);
        } else {
            return \Redirect::to($this->hrefDept);
            //$listAry = $this->getApiBeDeptTitleMainList();
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($parent)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        }
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_3')]; //部門
        if($parent)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.emp_2')]; //職稱
            $heads[] = ['title'=>Lang::get($this->langText.'.emp_4')]; //職等
            $heads[] = ['title'=>Lang::get($this->langText.'.emp_5')]; //主管職
            $heads[] = ['title'=>Lang::get($this->langText.'.emp_10')]; //停用
        }

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id         = $value->id;
                $A1         = $value->dept_name; //部門

                if($parent)
                {
                    $A2         = $value->dept_title; //職稱
                    $A3         = $value->level; //數值
                    $A4           = $value->isAd; //主管職
                    $isA4Color    = $value->isAd == 'Y' ? 1 : 0 ; //顏色

                    $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                    $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                    $btn      = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //審查按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $A1],
                        '2'=>[ 'name'=> $A2],
                        '3'=>[ 'name'=> $A3],
                        '4'=>[ 'name'=> $A4,'label'=>$isA4Color],
                        '10'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    //下一層
                    $childbtn     = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','pid='.$A1),Lang::get('sys_btn.btn_30'),3);
                    $tBody[] = ['0'=>[ 'name'=> $id,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $A1],
                        '99'=>[ 'name'=> $childbtn ]
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
        $content->rowTo($content->box_table($tbTile,$out));
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
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $bedeptAry = be_dept::getSelect();
        $betitleAry = be_title::getSelect();
        //view元件參數
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        $getData  = $this->getData($id);
        $fTile = $this->pageEditTitle ;
        //dd($getCust);
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //參數
            $A1    = $getData->be_dept_id;
            $A2    = $getData->be_title_id;
            //
            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //部門
        $html = $bedeptAry[$A1];//$form->select('A1',$bedeptAry, $A1);
        $form->add('titleT', $html,Lang::get($this->langText.'.emp_3'));
        //職稱
        $html = $form->select('A2',$betitleAry, $A2);
        $form->add('parentT',$html,Lang::get($this->langText.'.emp_2'));
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
        $submitDiv .= $form->linkbtn($hrefBack.'?pid='.$A1, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('A1',$A1);
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($fTile, $out,1));
        $contents = $content->output();

        //-------------------------------------------//
        //  JavaSrcipt
        //-------------------------------------------//
        $js = '
            $( document ).ready(function() {
                
            });
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
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->A2)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif(be_dept_a::isReExist($request->A1,$request->A2,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10113'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
        }
        //是否新增
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        if($isNew && (!$request->A1 || !$request->A2))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10108'))
                ->withInput();
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['be_dept_id']    = ($request->A1)? $request->A1 : 0;
        $upAry['be_title_id']   = ($request->A2)? $request->A2 : 0;
        $upAry['isClose']       = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createBeDeptTitle($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setBeDeptTitle($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'be_dept_a',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?pid='.$request->A1);
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
        //參數
        $js = $contents = '';
        $parent     = ($request->pid)? $request->pid : '';
        $parentName = be_dept::getName($parent);
        $bedeptAry  = be_dept::getSelect();
        $betitleAry = be_title::getSelect();

        //view元件參數
        $hrefBack   = $this->hrefMain.'?pid='.$parent;
        $btnBack    = $this->pageBackBtn;
        $fTile      = $this->pageNewTitle ;
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //部門
        $html = $form->select('A1',$bedeptAry,$parent);
        $form->add('titleT', $html,Lang::get($this->langText.'.emp_3'),1);
        //職稱
        $html = $form->select('A2',$betitleAry,0);
        $form->add('parentT',$html,Lang::get($this->langText.'.emp_2'),1);

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
        $content->rowTo($content->box_form($fTile, $out,1));
        $contents = $content->output();

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
