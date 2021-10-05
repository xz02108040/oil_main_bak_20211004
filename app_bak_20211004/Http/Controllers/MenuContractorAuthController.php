<?php

namespace App\Http\Controllers;


use App\Http\Traits\MenuContractorAuthTrait;
use App\Http\Traits\MenuContractorGroupTrait;
use App\Http\Traits\MenuContractorTraits;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\b_menu;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class MenuContractorAuthController extends Controller
{
    use MenuContractorTraits,MenuContractorGroupTrait,MenuContractorAuthTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | MenuContractorAuthController
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
    public function __construct()
    {
        //身分驗證
        $this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'menuauthc/';
        $this->langText         = 'sys_base';

        $this->hrefBeGroup      = 'menugroupc';
        $this->hrefMainNew      = 'new_menuauthc';
        $this->routerPost       = 'postMenuAuthC';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_10520');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_10521');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.base_10522');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.base_10523');//編輯

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
        $listAry1  = $this->getApiMenuList();
        $listAry  = (object)$listAry1;
        $listAry2 = SHCSLib::toArray($this->getApiMenuAuthList($id));

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $form->addSubmit(Lang::get('sys_btn.btn_14'),'1','agreeY');
        $form->addLinkBtn($hrefBack, $btnBack,2); //返回 權限群組
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
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
                $isCheck      = isset($listAry2[$menu_id])? true : false;
                $btn          = $form->checkbox('menu['.$menu_id.']',$menu_id, $isCheck); //審查按鈕

                $tBody[] = ['0'=>[ 'name'=> $No,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $btn],
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
        if( !$request->has('agreeY') || !$request->menu || !$request->id)
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
        $ret = $this->setMenuAuth($id,$request->menu,$this->b_cust_id);

        //2-1. 更新成功
        if($ret)
        {
            //動作紀錄
            LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'c_menu_auth',$id);
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
