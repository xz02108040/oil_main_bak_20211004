<?php

namespace App\Http\Controllers;


use App\Http\Traits\MenuAuthTrait;
use App\Http\Traits\MenuGroupTrait;
use App\Http\Traits\MenuTraits;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\b_menu;
use App\Model\b_menu_group;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class MenuAuthController extends Controller
{
    use MenuTraits,MenuGroupTrait,MenuAuthTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | authority Controller
    |--------------------------------------------------------------------------
    |
    | 群限 權限設定
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
        $this->hrefMain         = 'menuauth/';
        $this->langText         = 'sys_base';

        $this->hrefBeGroup      = 'menugroup';
        $this->hrefMainNew      = 'new_menuauth';
        $this->routerPost       = 'postMenuAuth';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_10500');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_10501');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.base_10502');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.base_10503');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

    }
    /**
     * 首頁內容
     *
     * @return void
     */
    public function index(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $out = $js ='';
        //view元件參數
        $menu     = $this->sys_menu; //MENU
        $title    = $this->pageTitleMain; //header
        $tbTile   = $this->pageTitleList;
        $hrefMain = $this->hrefMain.$urlid;
        $hrefBack = $this->hrefBeGroup;
        $btnBack  = $this->pageBackBtn;
        $id       = SHCSLib::decode($urlid);
        if(!$id)
        {
            return \Redirect::to($this->hrefBeGroup)
                ->withErrors(Lang::get($this->langText.'.base_10504'))
                ->withInput();
        }
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        $kind     = b_menu_group::getKind($id);
        $listAry1  = $this->getApiMenuList($kind);
        $listAry  = (object)$listAry1;
        $listAry2 = SHCSLib::toArray($this->getApiMenuAuthList($id));

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y')
        {
            $form->addSubmit(Lang::get('sys_btn.btn_14'),'1','agreeY');
        }
        $form->addLinkBtn($hrefBack, $btnBack,2); //返回 權限群組
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10510')]; //觀看權限
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10511')]; //維護權限
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10506')]; //啟用
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10507')]; //父層
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10508')]; //名稱

        $table->addHead($heads,0);
        if($listAry)
        {
            $No = 0;
            foreach($listAry as $value)
            {
                $No++;
                $menu_id      = $value->id;
                $name         = $value->name; //
                $parent_id    = $value->parent_id; //
                $parent       = (isset($listAry1[$parent_id]))? $listAry1[$parent_id]['title'] : HtmlLib::genIcon('caret-square-o-right');
                //選擇按鈕
                $isCheck1     = isset($listAry2[$menu_id])? true : false;
                $isCheck2     = ($isCheck1 && isset($listAry2[$menu_id]['W']) && $listAry2[$menu_id]['W'] == 'Y')? true : false;
                $btn1         = $form->checkbox('menu1['.$menu_id.']',$menu_id, $isCheck1); //審查按鈕
                $btn2         = $form->checkbox('menu2['.$menu_id.']',$menu_id, $isCheck2); //審查按鈕

                $tBody[] = ['0'=>[ 'name'=> $No,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $btn1],
                            '4'=>[ 'name'=> $btn2],
                            '3'=>[ 'name'=> $parent],
                            '2'=>[ 'name'=> $name],

                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $out .= $table->output();
        $out .= $form->hidden('id',$urlid);
        unset($table);


        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTile,$out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table2").DataTable({
                        "language": {
                        "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
                    }
                    });
                    
                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$title,'content'=>$contents,'menu'=>$menu,'js'=>$js];
        return view('index',$retArray);
    }



    /**
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //dd($request->all());
        //資料不齊全
        if( !$request->has('agreeY') || !$request->menu1 || !$request->id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10103'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip     = $request->ip();
            $menu   = $this->pageTitleMain;
            $action = 2;
        }

        //2.修改 權限群組可用選單
        $ret = $this->setMenuAuth($id,$request->menu1,$request->menu2,$this->b_cust_id);

        //2-1. 更新成功
        if($ret)
        {
            //動作紀錄
            LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_menu_auth',$id);
            //2-1-2 回報 更新成功
            Session::flash('message',Lang::get($this->langText.'.base_10104'));
            return \Redirect::to($this->hrefMain.$request->id);
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get($this->langText.'.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }


}
