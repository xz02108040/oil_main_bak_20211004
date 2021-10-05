<?php

namespace App\Http\Controllers\Tmp;

use App\Http\Controllers\Controller;
use App\Http\Traits\Emp\EmpTitleTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyTrait;
use App\Http\Traits\Tmp\TmpProjectTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\be_title;
use App\Model\Engineering\e_project_s;
use App\Model\Supply\b_supply;
use App\Model\Tmp\t_project;
use App\Model\User;
use App\Model\WorkPermit\wp_work_worker;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class TmpProject201912Controller extends Controller
{
    use TmpProjectTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | TmpProject201912Controller
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
        $this->hrefHome         = '/';
        $this->hrefMain         = 'showcpc1';
        $this->langText         = 'sys_tmp';

        $this->hrefMainDetail   = 'showcpc2/';
        $this->hrefMainNew      = 'new_showcpc1';
        $this->routerPost       = 'postShowCpc1';

        $this->pageTitleMain    = Lang::get($this->langText.'.title1');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list1');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new1');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit1');//編輯

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
    public function index()
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $no  = 0;
        $out = $js ='';
        $closeAry = SHCSLib::getCode('CLOSE');
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry    = $this->getApiTmpProjectList();
        $listAry2   = $this->getApiTmpProjectDefList();
        Session::put($this->hrefMain.'.Record',$listAry);
        Session::put($this->hrefMain.'.Record2',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $uptime = Lang::get($this->langText.'.tmp_3',['name'=>t_project::getUpAt()]);
        $form->addRow(HtmlLib::Color($uptime,'red',1));
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads = [];
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_2')]; //工程案號
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_1')]; //工程
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_4')]; //承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_8')]; //開始日期
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_9')]; //結束日期
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_5')]; //工地負責人
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_11')]; //工地負責人
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_6')]; //安衛人員
        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_12')]; //安衛人員
//        $heads[] = ['title'=>Lang::get($this->langText.'.tmp_7')]; //更新時間

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $id           = $value->e_project_id;
                $name1        = $value->project_no; //
                $name2        = $value->name; //
                $name3        = $value->supply.'('.$value->tax_num.')'; //
                $name4        = $value->sdate; //
                $name5        = $value->edate; //
                $pic1         = $value->eng_pic.(($value->eng_pic)? '('.$value->eng_pic_id.')' : ''); //
                $pic2         = $value->eng_pic1.(($value->eng_pic1)? '('.$value->eng_pic_id1.')' : ''); //
                $name11       = $pic1.($pic1? '<br>' : '').$pic2;
                $guard1       = $value->guard.($value->guard ? '('.$value->guard_id.')' : ''); //
                $guard2       = $value->guard1.($value->guard1 ? '('.$value->guard_id1.')' : ''); //
                $name12       = $guard1.($guard1? '<br>' : '').$guard2;

                $name13       = HtmlLib::Color($value->worker1,'blue',1); //
                $name14       = HtmlLib::Color($value->worker2,'blue',1); //

                $name80       = $value->created_at; //

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_66'),1); //按鈕

                $tBody[] = ['1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '11'=>[ 'name'=> $name11],
                            '13'=>[ 'name'=> $name13],
                            '12'=>[ 'name'=> $name12],
                            '14'=>[ 'name'=> $name14],
                            '99'=>[ 'name'=> $btn ]
                ];
            }
            if(count($listAry2))
            {

                foreach($listAry2 as $value)
                {
                    $str          = HtmlLib::Color(Lang::get($this->langText.'.tmp_16'),'red',1);
                    $id           = $value->id;
                    $name1        = $value->project_no; //
                    $name2        = $value->name; //
                    $name3        = $value->supply; //
                    $name4        = $value->sdate; //
                    $name5        = $value->edate; //
                    $name11       = $str; //
                    $name12       = $str; //

                    $name13       = HtmlLib::Color($value->worker1,'blue',1); //
                    $name14       = HtmlLib::Color($value->worker2,'blue',1); //


                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_66'),1); //按鈕

                    $tBody[] = ['1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '5'=>[ 'name'=> $name5],
                        '11'=>[ 'name'=> $name11],
                        '13'=>[ 'name'=> $name13],
                        '12'=>[ 'name'=> $name12],
                        '14'=>[ 'name'=> $name14],
                        '99'=>[ 'name'=> $btn ]
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

}
