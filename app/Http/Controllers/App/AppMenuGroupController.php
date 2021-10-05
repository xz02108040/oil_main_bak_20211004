<?php

namespace App\Http\Controllers\App;


use App\Http\Controllers\Controller;
use App\Http\Traits\App\AppMenuGroupTrait;
use App\Http\Traits\MenuGroupTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\App\app_menu_group;
use App\Model\b_menu_group;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class AppMenuGroupController extends Controller
{
    use AppMenuGroupTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | AppMenuGroupController
    |--------------------------------------------------------------------------
    |
    | APP群限群組
    |
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
        $this->hrefMain         = 'appmenugroup';
        $this->langText         = 'sys_base';

        $this->hrefBeAuth       = 'appmenuauth/';
        $this->hrefMainDetail   = 'appmenugroup/';
        $this->hrefMainNew      = 'new_appmenugroup';
        $this->routerPost       = 'postAppMenugroup';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_11120');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_11121');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.base_11122');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.base_11123');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//新增
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//返回

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
        $closeAry = SHCSLib::getCode('CLOSE');
        $syskind   = SHCSLib::getCode('BC_TYPE',1);
        $kind      = $request->kind;
        if(!$kind)
        {
            $kind = Session::get($this->hrefMain.'.search.kind',2);
        } else {
            Session::put($this->hrefMain.'.search.kind',$kind);
        }
        //view元件參數
        $tbTile   = $this->pageTitleList;
        $hrefMain = $this->hrefMain;
        $hrefBack = $this->hrefMainNew;
        $btnBack  = $this->pageNewBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($kind)
        {
            $listAry = $this->getApiAppMenuGroupList($kind);
            Session::put($this->hrefMain.'.Record',$listAry);
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefBack, $btnBack,2); //首頁
        $form->addHr();
        //搜尋
        $html = $form->select('kind',$syskind,$kind,2,Lang::get($this->langText.'.base_10320'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11125')]; //類別
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11104')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11106')]; //排序
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11110')]; //停用
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11126')]; //設定

        $table->addHead($heads,1);
        if(count($listAry))
        {
            $no = 0;
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $kind         = isset($syskind[$value->bc_type])? $syskind[$value->bc_type] : ''; //
                $name         = $value->name; //
                $order        = $value->show_order; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //審查按鈕
                $btn2         = HtmlLib::btn(SHCSLib::url($this->hrefBeAuth,$id),Lang::get('sys_btn.btn_26'),3); //審查按鈕


                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '11'=>[ 'name'=> $kind],
                            '1'=>[ 'name'=> $name],
                            '2'=>[ 'name'=> $order],
                            '7'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
                            '8'=>[ 'name'=> $btn2],
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
        //會員基本資料
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $js = $contents = '';
        $id = SHCSLib::decode($urlid);
        $syskind   = SHCSLib::getCode('BC_TYPE',1);

        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $getData    = $this->getData($id);
        $fTile      = $this->pageEditTitle ;
        //dd($getCust);
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get($this->langText.'.base_10102'));
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //參數
            $groupName    = $getData->name; //名稱
            $order        = $getData->show_order;
            $bc_type      = $getData->bc_type;
            //
            $isClose      = ($getData->isClose == 'Y')? true : false;

        }

        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->select('bc_type',$syskind,$bc_type);
        $form->add('nameT', $html,Lang::get($this->langText.'.base_10405'));
        //名稱
        $html = $form->text('groupName',$groupName);
        $form->add('nameT', $html,Lang::get($this->langText.'.base_11104'));
        //排序
        $html = $form->text('order',$order);
        $form->add('orderT',$html,Lang::get($this->langText.'.base_11125'));
        //停用
        $html = $form->checkbox('isClose','Y',$isClose);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.base_11110'));


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
        if( !$request->has('agreeY') || !$request->id || !$request->groupName || !$request->bc_type)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10103'))
                ->withInput();
        }
        elseif(app_menu_group::isNameExist($request->groupName,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10110'))
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
        $upAry['bc_type']       = $request->bc_type;
        $upAry['name']          = $request->groupName;
        $upAry['show_order']    = $request->order;
        $upAry['isClose']       = ($request->isClose == 'Y')? 'Y' : 'N';
        //dd($upAry);
        //新增
        if($isNew)
        {
            $ret = $this->createAppMenuGroup($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setAppMenuGroup($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'app_menu_group',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get($this->langText.'.base_10104'));
                return \Redirect::to($this->hrefMain);
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
        $syskind    = SHCSLib::getCode('BC_TYPE');
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
        $html = $form->select('bc_type',$syskind,2);
        $form->add('titleT', $html,Lang::get($this->langText.'.base_11125'));
        //名稱
        $html = $form->text('groupName','');
        $form->add('titleT', $html,Lang::get($this->langText.'.base_11104'));
        //排序
        $html = $form->text('order',999);
        $form->add('orderT',$html,Lang::get($this->langText.'.base_11106'));

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
