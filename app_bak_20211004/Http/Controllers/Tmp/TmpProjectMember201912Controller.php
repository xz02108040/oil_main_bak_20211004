<?php

namespace App\Http\Controllers\Tmp;

use App\Http\Controllers\Controller;
use App\Http\Traits\Emp\EmpTitleTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyTrait;
use App\Http\Traits\Tmp\TmpProjectMemberTrait;
use App\Http\Traits\Tmp\TmpProjectTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\be_title;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_s;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_work_worker;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class TmpProjectMember201912Controller extends Controller
{
    use TmpProjectMemberTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | TmpProjectMember201912Controller
    |--------------------------------------------------------------------------
    |
    | 承攬商公司
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
        $this->hrefHome         = 'showcpc1';
        $this->hrefMain         = 'showcpc2/';
        $this->langText         = 'sys_tmp';

        $this->hrefMainDetail   = 'showcpc2/';
        $this->hrefMainNew      = 'new_showcpc2';
        $this->routerPost       = 'postShowCpc2';

        $this->pageTitleMain    = Lang::get($this->langText.'.title2');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list2');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new2');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit2');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageExcelBtn     = Lang::get('sys_btn.btn_17');//[按鈕]匯入

    }
    /**
     * 首頁內容
     *
     * @return void
     */
    public function index($project_id)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $no  = 0;
        $out = $js ='';
        $closeAry = SHCSLib::getCode('CLOSE');
        $project_id = SHCSLib::decode($project_id);
        $project    = e_project::getName($project_id);
        //view元件參數
        $tbTitle  = $this->pageTitleList.$project;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefBack = $this->hrefHome;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiTmpProjectMemberList($project_id);
        Session::put($this->hrefMain.'.Record',$listAry);
//        dd($listAry1,$listAry2);
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_2')]; //工程案號
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_17')]; //原姓名
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_14')]; //成員姓名
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_18')]; //原身分證
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_15')]; //身分證
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_10')]; //原始日期資料
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_13')]; //存在帳號

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $color1 = 'black';
                if($value->name && $value->name1 && $value->name != $value->name1)
                {
                    $color1 = 'red';
                }
                if(!$value->name1 && $value->name)
                {
                    $color1 = 'red';
                    $value->name1 = '補人';
                }
                $color2 = 'black';
                if($value->bc_id && $value->bc_id1 && $value->bc_id != $value->bc_id1)
                {
                    $color2 = 'red';
                }


                $name1        = $value->project_no; //
                $name2        = $value->name; //
                $name3        = $value->bc_id; //
                $name4        = HtmlLib::Color($value->name1,$color1); //
                $name5        = HtmlLib::Color($value->bc_id1,$color2);
                $name6        = ($value->sdate_str)? ($value->sdate_str.'~'.($value->edate_str ? $value->edate_str : '9999-12-31')) : ''; //
                $name11       = $value->source; //


                $tBody[] = ['1'=>[ 'name'=> $name1],
                            '4'=>[ 'name'=> $name4],
                            '2'=>[ 'name'=> $name2],
                            '5'=>[ 'name'=> $name5],
                            '3'=>[ 'name'=> $name3],
                            '6'=>[ 'name'=> $name6],
                            '11'=>[ 'name'=> $name11],
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
                    
                    
                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

}
