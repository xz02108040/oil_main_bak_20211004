<?php

namespace App\Http\Controllers;


use App\Http\Traits\MenuAuthTrait;
use App\Http\Traits\MenuContractorAuthTrait;
use App\Http\Traits\MenuContractorTraits;
use App\Http\Traits\MenuTraits;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\b_menu;
use App\Model\c_menu;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class MenuContractorController extends Controller
{
    use MenuContractorTraits,MenuContractorAuthTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | MenuContractorController
    |--------------------------------------------------------------------------
    |
    | 選單 - 承攬商選單
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
        $this->hrefMain         = 'menuc';
        $this->langText         = 'sys_base';

        $this->hrefMainDetail   = 'menuc/';
        $this->hrefMainNew      = 'new_menuc';
        $this->routerPost       = 'postMenuC';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_10330');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_10331');//列表
        $this->pageNewTitle     = Lang::get($this->langText.'.base_10332');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.base_10333');//編輯

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
        $listAry = [];
        $out = $js ='';
        $no        = 0;
        $parent    = ($request->pid)? $request->pid : 0;
        $closeAry  = SHCSLib::getCode('CLOSE');
        $targetAry = SHCSLib::getCode('URL_TARGET');
        $menuAry   = c_menu::getSelect(1,0);
        $parentName= isset($menuAry[$parent])? ' 》'.$menuAry[$parent] : '';
        //view元件參數
        $tbTile   = $this->pageTitleList.$parentName; //列表標題
        $hrefMain = $this->hrefMain; //路由
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = c_menu::where('parent_id',$parent)->orderby('show_order')->get();
        Session::put($this->hrefMain.'.Record',$listAry);

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
        $heads[] = ['title'=>'ID'];
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10306')]; //父層
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10307')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10308')]; //路由
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10309')]; //排序
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10310')]; //目標
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10311')]; //圖示
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10313')]; //顯示
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10312')]; //停用
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10314')]; //下一層

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $parent       = isset($menuAry[$value->parent_id])? $menuAry[$value->parent_id] : ''; //父層ＩＤ
                $mtitle       = $value->name; //名稱
                $order        = $value->show_order; //排序
                $icon         = ($value->icon)? HtmlLib::genIcon($value->icon) : ''; //ICON
                $uri          = $value->uri; //路由
                $target       = isset($targetAry[$value->target])? $targetAry[$value->target] : $targetAry['_self'] ; //停用
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色
                $isShow       = $value->isShow; //是否顯示
                $isShowColor  = $value->isShow == 'Y' ? 2 : 5 ; //顏色

                //下一層
                $childbtn     = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','pid='.$id),Lang::get('sys_btn.btn_27'),3);
                //如果是 本身不是最高權限群組
                if($this->b_menu_group_id != 2 && $value->isRoot == 'Y')
                {
                    $btn      = '';
                } else {
                    $btn      = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //審查按鈕
                }

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '11'=>[ 'name'=> $id],
                            '1'=>[ 'name'=> $parent],
                            '2'=>[ 'name'=> $mtitle],
                            '3'=>[ 'name'=> $uri],
                            '4'=>[ 'name'=> $order],
                            '5'=>[ 'name'=> $icon],
                            '6'=>[ 'name'=> $target],
                            '8'=>[ 'name'=> $isShow,'label'=>$isShowColor],
                            '7'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
                            '10'=>[ 'name'=> $childbtn],
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
        $targetAry  = SHCSLib::getCode('URL_TARGET');
        $iconAry    = SHCSLib::getCode('ICON');
        $menuAry    = c_menu::getSelect();
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
            $menuName    = $getData->name; //MENU名稱
            //服務對象
            $parent      = $getData->parent_id;
            //申請對象
            $uri         = $getData->uri;
            //申請對象
            $order       = $getData->show_order;
            $icon        = $getData->icon;
            $target      = $getData->target;
            //
            $isClose      = ($getData->isClose == 'Y')? true : false;
            $isShow       = ($getData->isShow == 'Y')? true : false;

        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->text('name',$menuName);
        $form->add('titleT', $html,Lang::get($this->langText.'.base_10307'),1);
        //父層
        $html = $form->select('parent',$menuAry,$parent);
        $form->add('parentT',$html,Lang::get($this->langText.'.base_10306'),1);
        //路由
        $html = $form->text('uri',$uri);
        $form->add('uriT',$html,Lang::get($this->langText.'.base_10308'),1);
        //排序
        $html = $form->text('order',$order);
        $form->add('orderT',$html,Lang::get($this->langText.'.base_10309'));
        //圖示
        $html =  $form->select('icon',$iconAry,$icon).HtmlLib::genIcon($icon).'<span id="showIcon"></span>';
        $form->add('iconT',$html,Lang::get($this->langText.'.base_10310'));
        //目標
        $html = $form->select('target',$targetAry,$target);
        $form->add('targetT',$html,Lang::get($this->langText.'.base_10311'));
        //顯示
        $html = $form->checkbox('isShow','Y',$isShow);
        $form->add('isShowT',$html,Lang::get($this->langText.'.base_10313'));
        //停用
        $html = $form->checkbox('isClose','Y',$isClose);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.base_10312'));


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
                $( "#icon" ).change(function() {
                    var icon = "<i class=\'fa fa-fw fa-arrow-right\'></i><i class=\'fa fa-fw fa-"+$(this).val()+"\'></i>";
                    $("#showIcon").html(icon);
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
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->has('parent') || !$request->uri)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10103'))
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

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        //TODO
        // kind 還沒有加入
        $upAry['parent_id']     = $request->parent;
        $upAry['name']          = $request->name;
        $upAry['icon']          = $request->icon;
        $upAry['uri']           = $request->uri;
        $upAry['order']         = $request->order;
        $upAry['target']        = $request->target;
        $upAry['isShow']        = ($request->isShow == 'Y')? 'Y' : 'N';
        $upAry['isClose']       = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createMenu($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setMenu($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'c_menu',$id);
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
        //參數
        $js = $contents = '';
        $targetAry  = SHCSLib::getCode('URL_TARGET');
        $iconAry    = SHCSLib::getCode('ICON');
        $menuAry    = c_menu::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $fTile      = $this->pageNewTitle  ;
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //申請日期
        $html = $form->text('name','');
        $form->add('titleT', $html,Lang::get($this->langText.'.base_10307'));
        //父層
        $html = $form->select('parent',$menuAry,0);
        $form->add('parentT',$html,Lang::get($this->langText.'.base_10306'),1);
        //申請人
        $html = $form->text('uri','#');
        $form->add('uriT',$html,Lang::get($this->langText.'.base_10308'));
        //狀態
        $html = $form->text('order',999);
        $form->add('orderT',$html,Lang::get($this->langText.'.base_10309'));
        //圖示
        $html =  $form->select('icon',$iconAry,'').'<span id="showIcon"></span>';
        $form->add('iconT',$html,Lang::get($this->langText.'.base_10310'));
        //
        $html = $form->select('target',$targetAry,'_self');
        $form->add('targetT',$html,Lang::get($this->langText.'.base_10311'));
        //
        $html = $form->checkbox('isShow','Y',true);
        $form->add('isShowT',$html,Lang::get($this->langText.'.base_10313'));

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
        //  JavaSrcipt
        //-------------------------------------------//
        $js = '
            $( document ).ready(function() {
                $( "#icon" ).change(function() {
                    var icon = "<i class=\'fa fa-fw fa-arrow-right\'></i><i class=\'fa fa-fw fa-"+$(this).val()+"\'></i>";
                    $("#showIcon").html(icon);
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
