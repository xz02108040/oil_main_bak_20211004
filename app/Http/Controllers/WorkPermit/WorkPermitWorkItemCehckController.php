<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitKindTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemDangerTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\User;
use App\Model\WorkPermit\wp_check_kind;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_permit_workitem_a;
use App\Model\WorkPermit\wp_permit_workitem_b;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkPermitWorkItemCehckController extends Controller
{
    use WorkPermitWorkItemCheckTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitWorkItemCehckController
    |--------------------------------------------------------------------------
    |
    | 工作許可證 工作項目之對應「附加檢點單」維護
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
        $this->hrefMain         = 'workpermitworkitemcheck';
        $this->hrefKind         = 'workpermitkind';
        $this->hrefItem         = 'workpermitworkitem';
        $this->langText         = 'sys_workpermit';

        $this->hrefMainDetail   = 'workpermitworkitemcheck/';
        $this->hrefMainNew      = 'new_workpermitworkitemcheck/';
        $this->routerPost       = 'postWorkpermitworkitemcheck';

        $this->pageTitleMain    = Lang::get($this->langText.'.title26');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list26');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new26');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit26');//編輯

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
        //參數
        $out = $js ='';
        $no  = 0;
        $closeAry = SHCSLib::getCode('CLOSE');
        //種類ＩＤ
        $urlid    = $request->tid ? $request->tid : '';
        $tid      = SHCSLib::decode($urlid);
        if($tid)
        {
            Session::put($this->langText.'.select.tid',$tid);
            Session::put($this->langText.'.select.urltid',$urlid);
        } else {
            $tid = Session::get($this->langText.'.select.tid');
        }
        if(!$tid) return \Redirect::back()->withErrors(Lang::get($this->langText.'.permit_10000'));
        $kindName = wp_permit_workitem::getName($tid);

        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$kindName;//列表標題
        $hrefMain = $this->hrefMain.'?tid='.$urlid;
        $hrefNew  = $this->hrefMainNew.$urlid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefItem;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiWorkPermitWorkItemCheckList($tid);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_1')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_27')]; //危險等級
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_4')]; //排序
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_7')]; //狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->workitem; //
                $name2        = $value->check_name; //
                $name3        = $value->show_order; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
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
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
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
        } else {
            //資料明細
            $A1         = $getData->kind; //
            $A2         = $getData->workitem; //
            $A3         = $getData->show_order; //
            $A4         = $getData->wp_permit_workitem_id; //
            $A5         = $getData->wp_check_kind_id; //
            $tid        = SHCSLib::encode($getData->wp_permit_workitem_id); //


            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;

            $selectAry  = wp_check_kind::getSelect();
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //種類
        $html = $A1.'-'.$A2.$form->hidden('workitem_id',$A4);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_108'),1);
        //種類
        $html = $form->select('wp_check_kind_id',$selectAry,$A5);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_27'),1);
        //排序
        $html = $form->text('show_order',$A3);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_4'));
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.permit_8'));
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack.'?tid='.$tid, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('tid',$tid);
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
            CKEDITOR.replace("context");

            CKEDITOR.replace("cont_tw");
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
        if( !$request->has('agreeY') || !$request->id || !$request->wp_check_kind_id  || !$request->workitem_id )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        //相同數值
        elseif(wp_permit_workitem_b::isExist($request->workitem_id,$request->wp_check_kind_id,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.permit_10028'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id   = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $tid  = $request->tid;
            $menu = $this->pageTitleMain;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['wp_permit_workitem_id'] = $request->workitem_id;
        $upAry['wp_check_kind_id']      = $request->wp_check_kind_id;
        $upAry['show_order']            = $request->show_order ? $request->show_order : 0;
        $upAry['isClose']               = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createWorkPermitWorkItemCheck($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setWorkPermitWorkItemCheck($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_permit_workitem_a',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?tid='.$tid);
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
    public function create(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents = '';
        $tid        = SHCSLib::decode($urlid);
        $kind       = wp_permit_workitem::getName($tid);
        $selectAry  = wp_check_kind::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain.'?tid='.$urlid;;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //種類
        $html = $kind.$form->hidden('workitem_id',$tid);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_108'),1);
        //種類
        $html = $form->select('wp_check_kind_id',$selectAry);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_27'),1);
        //排序
        $html  = $form->text('show_order',999);
        $form->add('nameT2', $html,Lang::get($this->langText.'.permit_4'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('tid',$urlid);
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
            CKEDITOR.replace("context");

            CKEDITOR.replace("cont_tw");
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
