<?php

namespace App\Http\Controllers\Factory;


use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\b_menu;
use App\Model\sys_code;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;

class PaitCardLockMenController extends Controller
{
    use SessTraits;
    /*
    |--------------------------------------------------------------------------
    | PaitCardLockMenController
    |--------------------------------------------------------------------------
    |
    | 印卡解鎖
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
        $this->hrefMain         = 'rfidlock';
        $this->langText         = 'sys_rfid';

        $this->hrefMainDetail   = 'rfidlock/';
        $this->hrefMainNew      = 'new_rfidlock';
        $this->routerPost       = 'postRfidlock';

        $this->pageTitleMain    = Lang::get($this->langText.'.title7');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list7');//列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new7');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit7');//編輯

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
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = DB::table('log_paircard_lock as l')->join('view_supply_user as s','s.b_cust_id','=','l.b_cust_id')->
                where('l.isLock','Y')->select('l.id','l.isLock','s.b_supply_id','s.b_cust_id','s.supply','s.name','l.updated_at')->get();
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_21')]; //承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_22')]; //承攬商成員
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_23')]; //鎖定時間

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id         = $value->id;
                $sid        = $value->b_supply_id;
                $uid        = $value->b_cust_id;
                $param      = 'sid='.SHCSLib::encode($sid).'&uid='.SHCSLib::encode($uid);
                $A1         = $value->supply; //承攬商
                $A2         = $value->name; //承攬商成員
                $A3         = substr($value->updated_at,0,16); //鎖定時間

                $childbtn     = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_75'),3);
                $tBody[] = ['0'=>[ 'name'=> $id,'b'=>1,'style'=>'width:5%;'],
                    '1'=>[ 'name'=> $A1],
                    '2'=>[ 'name'=> $A2],
                    '3'=>[ 'name'=> $A3],
                    '99'=>[ 'name'=> $childbtn ]
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
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function show(Request $request,$urlid)
    {
        //資料不齊全
        if( !$urlid || !$request->sid || !$request->uid)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys-base.base_10103'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $sid = SHCSLib::decode($request->sid);
            $uid = SHCSLib::decode($request->uid);

            LogLib::setLogPairCardLock($sid,$uid,'N',$this->b_cust_id);
            Session::flash('message',Lang::get('sys_rfid.rfid_24'));
            return \Redirect::to($this->hrefMain);
        }

    }

}
