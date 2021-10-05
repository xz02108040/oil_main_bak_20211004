<?php

namespace App\Http\Controllers;


use App\Http\Traits\MenuAuthTrait;
use App\Http\Traits\MenuTraits;
use App\Http\Traits\SessTraits;
use App\Http\Traits\SysParam;
use App\Http\Traits\SysCodeTrait;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\b_menu;
use App\Model\Emp\be_dept;
use App\Model\sys_code;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class SysParamController extends Controller
{
    use SysParam,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SysParamController
    |--------------------------------------------------------------------------
    |
    | 系統參數維護
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
        $this->hrefMain         = 'sysparam';
        $this->langText         = 'sys_base';

        $this->hrefMainDetail   = 'sysparam/';
        $this->hrefMainNew      = 'new_sysparam';
        $this->routerPost       = 'postSysparam';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_10650');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_10651');//列表
        $this->pageNewTitle     = Lang::get($this->langText.'.base_10652');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.base_10653');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

        $this->deptAry          = [66,67,75];
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
        $closeAry  = SHCSLib::getCode('CLOSE');
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $aWhere = array();
        switch ($_SERVER['SERVER_NAME']) {
            case 'demo.httc.com.tw':
                $aWhere[] = 'CPC1_VERSION';
                $aWhere[] = 'CPC2_VERSION';
                $aWhere[] = 'HTTC1_VERSION';
                $aWhere[] = 'HTTC2_VERSION';
                break;
            case 'doortalin.cpc.com.tw':
                $aWhere[] = 'OIL1_VERSION';
                $aWhere[] = 'OIL2_VERSION';
                break;
            default:
            $id_Ary= array();
                break;
        }
        //增加系統版本參數的選項
        $id_Ary = sys_param::whereIn('param_code',$aWhere)->where('isClose','N')->select('id')->get()->toArray();
        $id_Ary = collect($id_Ary)->pluck('id')->toArray();
        
        $allowAry = array_merge([1, 2, 3, 15, 17, 18, 20, 58, 63, 64, 67, 70, 71, 72, 75, 76, 77, 78, 79, 80, 81], $id_Ary);
        $listAry = sys_param::whereIn('id',$allowAry)->where('isClose','N')->get();
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
//        $form->addLinkBtn($hrefNew, $btnNew,2); //新增
//        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10608')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10609')]; //數值
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10018')]; //說明
//        $heads[] = ['title'=>Lang::get($this->langText.'.base_10612')]; //停用

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id         = $value->id;
                $A1         = $value->param_name; //名稱
                $A2         = ($id == 67)? be_dept::getName($value->param_value) : $value->param_value; //數值
                $A3         = $value->memo; //說明

                //如果是 本身不是最高權限群組
                if(!$this->isSuperUser)
                {
                    $btn      = '';
                } else {
                    $btn      = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //按鈕
                }

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                    '1'=>[ 'name'=> $A1],
                    '2'=>[ 'name'=> $A2],
                    '3'=>[ 'name'=> $A3],
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
        //view元件參數
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        $getData  = $this->getData($id);
        $fTile = $this->pageEditTitle ;
        //dd($getCust);
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get($this->langText.'.base_10102'));
        } else {
            //參數
            $A1    = $getData->param_name;
            $A2    = $getData->param_value;
            $A3    = $getData->memo;
            //
            $isClose      = ($getData->isClose == 'Y')? true : false;

        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $A1;
        $form->add('titleT', $html,Lang::get($this->langText.'.base_10607'));
        //數值
        if(in_array($getData->id,[65]))
        {
            $selectAry = be_dept::getSelect(1,1,'','Y',0,2);
            $html = $form->select('A2',$selectAry,$A2);
        } else {
            $html = $form->text('A2',$A2);
        }
        $form->add('uriT',$html,Lang::get($this->langText.'.base_10609'),1);
        //數值說明
        $html = $form->text('A3',$A3);
        $form->add('orderT',$html,Lang::get($this->langText.'.base_10018'));
        //停用
//        $html = $form->checkbox('isClose','Y',$isClose);
//        $form->add('isCloseT',$html,Lang::get($this->langText.'.base_10612'));


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
        if( !$request->has('agreeY') || !$request->id || !strlen($request->A2))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10103'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $urlParam = ($request->pid)? '?pid='.$request->pid : "";

            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
        }
        //是否新增
        $isNew  = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        if($isNew && (!strlen($request->A2)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10108'))
                ->withInput();
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['param_value']   = ($request->A2)? $request->A2 : '';
        $upAry['memo']          = ($request->A3)? $request->A3 : '';
        $upAry['isClose']       = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = 0;//$this->createSysCode($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSysParam($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'sys_code',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get($this->langText.'.base_10104'));
                return \Redirect::to($this->hrefMain.$urlParam);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get($this->langText.'.base_10105');
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
        $parentName = sys_code::getCodeName($parent);
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $fTile      = $this->pageNewTitle ;
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html = $parent ? $parent : $form->text('A1','');
        $form->add('titleT', $html,Lang::get($this->langText.'.base_10607'),1);
        //父層
        $html = $parentName ? $parentName : $form->text('A2','');
        $form->add('parentT',$html,Lang::get($this->langText.'.base_10608'),1);
        //數值
        $html = $form->text('A3','');
        $form->add('uriT',$html,Lang::get($this->langText.'.base_10609'),1);
        //數值名稱
        $html = $form->text('A4','');
        $form->add('orderT',$html,Lang::get($this->langText.'.base_10605'),1);
        //數值說明
        $html = $form->text('A5','');
        $form->add('orderT',$html,Lang::get($this->langText.'.base_10610'));
        //排序
        $html = $form->text('A6','999');
        $form->add('orderT',$html,Lang::get($this->langText.'.base_10611'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('pid',$parent);
        if($parent)
        {
            $submitDiv.= $form->hidden('A1',$parent);
            $submitDiv.= $form->hidden('A2',$parentName);
        }
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
