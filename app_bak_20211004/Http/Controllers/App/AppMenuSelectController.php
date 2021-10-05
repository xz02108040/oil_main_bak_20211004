<?php

namespace App\Http\Controllers\App;


use App\Http\Controllers\Controller;
use App\Http\Traits\App\AppMenuSelectTrait;
use App\Http\Traits\App\AppMenuTrait;
use App\Http\Traits\App\AppMenuAuthTrait;
use App\Http\Traits\MenuAuthTrait;
use App\Http\Traits\MenuTraits;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\App\app_menu;
use App\Model\App\app_menu_a;
use App\Model\b_menu;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class AppMenuSelectController extends Controller
{
    use AppMenuTrait,AppMenuSelectTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | AppMenuSelectController
    |--------------------------------------------------------------------------
    |
    | APP MENU選單 的搜尋條件
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
        $this->hrefHome         = 'app_menu';
        $this->hrefMain         = 'app_menu_select';
        $this->langText         = 'sys_base';

        $this->hrefMainDetail   = 'app_menu_select/';
        $this->hrefMainNew      = 'new_app_menu_select';
        $this->routerPost       = 'postAppMenuSelect';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_11140');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_11141');//列表
        $this->pageNewTitle     = Lang::get($this->langText.'.base_11142');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.base_11143');//編輯

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
        $listAry = [];
        $out = $js ='';
        $no        = 0;
        $parent    = ($request->pid)? $request->pid : 0;
        $lastPage  = ($request->fid)? $request->fid : 0;
        $closeAry  = SHCSLib::getCode('CLOSE');
        $bctypeAry = SHCSLib::getCode('BC_TYPE2');
        $searchAry = SHCSLib::getCode('APP_MENU_SEARCH');
        $menuAry   = app_menu::getSelect(0);
        $parentName= isset($menuAry[$parent])? ' 》'.$menuAry[$parent] : '';
        //view元件參數
        $tbTile   = $this->pageTitleList.$parentName; //列表標題
        $hrefMain = $this->hrefMain; //路由
        $hrefNew  = $this->hrefMainNew.'?pid='.$parent;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefHome.'?pid='.$lastPage;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getAppMenuSelectList($parent);
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
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11104')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11144')]; //搜尋參數
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11145')]; //搜尋方式
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11146')]; //使用權限
        $heads[] = ['title'=>Lang::get($this->langText.'.base_11110')]; //停用

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $mtitle       = $value->name; //名稱
                $param        = $value->param; //搜尋參數
                $type         = isset($searchAry[$value->type])? $searchAry[$value->type] : ''; //搜尋方式
                $bc_type      = isset($bctypeAry[$value->bc_type])? $bctypeAry[$value->bc_type] : ''; //帳號權限
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                $btn      = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //審查按鈕


                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $mtitle],
                            '2'=>[ 'name'=> $param],
                            '4'=>[ 'name'=> $type],
                            '7'=>[ 'name'=> $bc_type],
                            '11'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $bctypeAry  = SHCSLib::getCode('BC_TYPE2',1);
        $searchAry  = SHCSLib::getCode('APP_MENU_SEARCH',1);
        //view元件參數
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        $getData  = $this->getData($id);
        $fTile = $this->pageEditTitle ;
        //dd($getCust);
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get($this->langText.'.base_10102'));
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //MENU名稱
            $name           = $getData->name;
            $app_menu_id    = $getData->app_menu_id;
            //上一層
            $param          = $getData->param ;
            //參數
            $type           = $getData->type ;
            $bc_type        = $getData->bc_type;
            //
            $isClose        = ($getData->isClose == 'Y')? true : false;

        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->text('name',$name);
        $form->add('titleT', $html,Lang::get($this->langText.'.base_11104'),1);
        //名稱
        $html = $form->text('param',$param);
        $form->add('titleT', $html,Lang::get($this->langText.'.base_11144'));
        //父層
        $html = $form->select('type',$searchAry,$type);
        $form->add('parentT',$html,Lang::get($this->langText.'.base_11145'),1);
        //父層
        $html = $form->select('bc_type',$bctypeAry,$bc_type);
        $form->add('parentT',$html,Lang::get($this->langText.'.base_11146'),1);
        //停用
        $html = $form->checkbox('isClose','Y',$isClose);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.base_10312'));


        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack.'?pid='.$app_menu_id, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('parent',$app_menu_id);
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
        if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->has('parent') )
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
        $upAry['app_menu_id']    = $request->parent;
        $upAry['name']           = $request->name;
        $upAry['param']          = $request->param ? $request->param : '';
        $upAry['type']           = $request->type;
        $upAry['bc_type']        = $request->bc_type ? $request->bc_type : 1;
        $upAry['isClose']        = ($request->isClose == 'Y')? 'Y' : 'N';
        //新增
        if($isNew)
        {
            $ret = $this->createAppMenuSelect($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setAppMenuSelect($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'app_menu_a',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get($this->langText.'.base_10104'));
                return \Redirect::to($this->hrefMain.'?pid='.$request->parent);
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        if($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        }
        //參數
        $js = $contents = '';
        $bctypeAry  = SHCSLib::getCode('BC_TYPE2',1);
        $searchAry  = SHCSLib::getCode('APP_MENU_SEARCH',1);
        //view元件參數
        $hrefBack   = $this->hrefMain.'?pid='.$request->pid;
        $btnBack    = $this->pageBackBtn;
        $fTile      = $this->pageNewTitle  ;
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html = $form->text('name','');
        $form->add('titleT', $html,Lang::get($this->langText.'.base_11104'),1);
        //名稱
        $html = $form->text('param','');
        $form->add('titleT', $html,Lang::get($this->langText.'.base_11144'));
        //父層
        $html = $form->select('type',$searchAry,1);
        $form->add('parentT',$html,Lang::get($this->langText.'.base_11145'),1);
        //父層
        $html = $form->select('bc_type',$bctypeAry,1);
        $form->add('parentT',$html,Lang::get($this->langText.'.base_11146'),1);

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('parent',$request->pid);
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
