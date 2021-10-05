<?php

namespace App\Http\Controllers;


use App\Http\Traits\MenuAuthTrait;
use App\Http\Traits\MenuTraits;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\b_menu;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_factory;
use App\Model\log_action;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class LogActionController extends Controller
{
    use MenuTraits,MenuAuthTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | LogActionController
    |--------------------------------------------------------------------------
    |
    | [紀錄]職員操作紀錄
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
        $this->hrefMain         = 'rept_activitylog';
        $this->langText         = 'sys_base';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_40100');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_40101');//列表

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
        $no        = 0;
        $bcust     = ($request->uid)? $request->uid : $this->b_cust_id;
        $uName     = $bcust ? ' 》'.User::getName($bcust) : '';
        //$storeAry  = b_factory::getSelect();
        $deptAry   = be_dept::getSelect();
        $actionAry = SHCSLib::getCode('ACTION');
        //view元件參數
        $tbTile   = $this->pageTitleList.$uName; //列表標題
        $hrefMain = $this->hrefMain; //路由
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = log_action::join('b_cust','log_action.b_cust_id','=','b_cust.id')->select('log_action.*','b_cust.name')->
        where('b_cust_id',$bcust)->orderby('id','desc')->get();

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$html = $form->select('sid',$storeAry,0,2,Lang::get($this->langText.'.base_40106'));
        $html = $form->select('did',$deptAry,0,2,Lang::get($this->langText.'.base_40107'));
        $html.= $form->select('uid',[],0,2,Lang::get($this->langText.'.base_40108'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');

        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.base_40102')]; //使用者
        $heads[] = ['title'=>Lang::get($this->langText.'.base_40103')]; //操作功能
        $heads[] = ['title'=>Lang::get($this->langText.'.base_40104')]; //動作
        $heads[] = ['title'=>Lang::get($this->langText.'.base_40105')]; //操作時間

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $action       = isset($actionAry[$value->action])? $actionAry[$value->action] : '';
                $A1           = $value->name;
                $A2           = $value->menu;
                $A3           = $action.'  (ID:'.$value->model_id.')';
                $A4           = $value->created_at;


                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $A1],
                            '2'=>[ 'name'=> $A2],
                            '3'=>[ 'name'=> $A3],
                            '4'=>[ 'name'=> $A4],
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

                    $( "#did" ).change(function() {
                        var eid = $("#did").val();

                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 2, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#uid option").remove();
                             $.each(result, function(key, val) {
                                $("#uid").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });

                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];

        return view('index',$retArray);
    }

}
