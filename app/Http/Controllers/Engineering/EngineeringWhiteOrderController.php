<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\CourseTrait;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use DB;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class EngineeringWhiteOrderController extends Controller
{
    use CourseTrait,SessTraits,DoorTrait;
    /*
    |--------------------------------------------------------------------------
    | EngineeringWhiteOrderController
    |--------------------------------------------------------------------------
    |
    | 產生白名單 維護
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
        $this->hrefMain         = 'engineeringwhiteorder';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'engineeringwhiteorder';
        $this->hrefMainNew      = 'genengineeringwhiteorder';
        $this->routerPost       = 'postEngineeringwhiteorder';

        $this->pageTitleMain    = Lang::get($this->langText.'.title27');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list27');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new27');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.editl27');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_69');//[按鈕]新增
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
        $passAry = SHCSLib::getCode('PASS');
        $closeAry = SHCSLib::getCode('CLOSE');
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $data    = DB::table('log_project_identity_pass as c')->
                    join('view_supply_user as u','c.b_cust_id','=','u.b_cust_id')->
                    join('e_project as p','p.id','=','c.e_project_id')->
                    where('c.sdate',date('Y-m-d'))->select('c.sdate','c.created_at','u.name','p.name as project','u.supply','u.supply');
        $listAmt = $data->count();
        $listAry = $data->get();
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        $form->addHtml(HtmlLib::Color(Lang::get($this->langText.'.engineering_1043'),'red',1));
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_1')]; //通過名單
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_110')]; //通過名單
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_109')]; //有效日
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_106')]; //通過時間

        $table->addHead($heads,0);
        if($listAmt)
        {
            foreach($listAry as $value)
            {
                $no++;
                $name4        = $value->project; //
                $name1        = $value->supply.'-'.$value->name; //
                $name2        = $value->sdate; //
                $name3        = $value->created_at; //

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '4'=>[ 'name'=> $name4],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
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
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request)
    {
        list($result,$memo) = $this->createProjectIdentityPass();
        //2-1. 更新成功
        if($result)
        {
            //2-1-2 回報 更新成功
            Session::flash('message',Lang::get('sys_base.base_10104'));
            return \Redirect::to($this->hrefMain);
        } else {
            $msg = Lang::get('sys_base.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }



}
