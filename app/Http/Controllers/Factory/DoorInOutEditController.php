<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorLogListTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_factory;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\sys_code;
use App\Model\User;
use App\Model\WorkPermit\wp_work;
use DB;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class DoorInOutEditController extends Controller
{
    use SessTraits,ReptDoorLogListTrait,DoorTrait,ReptDoorMenInOutTodayTrait,WorkPermitWorkerTrait;
    /*
    |--------------------------------------------------------------------------
    | DoorInOutEditController
    |--------------------------------------------------------------------------
    |
    | [後門]進出廠紀錄_異常進出_修改功能
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
        $this->hrefMain         = 'door_edit';
        $this->hrefMainDetail   = 'door_edit/';
        $this->routerPost       = 'door_edit_post';
        $this->langText         = 'sys_base';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_30200');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_30201');//列表
        $this->pageEditTitle    = Lang::get($this->langText.'.base_30202');//列表

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
        $out = $js ='';
        $no        = $listAryCnt = 0;
        $listAry   = [];
        $supplyAry = b_supply::getSelect();
        $storeAry  = b_factory::getSelect();
        $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
        unset($doorTypeAry[0]);
        unset($doorTypeAry[3]);
        unset($doorTypeAry['C']);

        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //門禁狀態

        if($request->has('clear'))
        {
            $aid = $bid = $cid = 0;
            Session::forget($this->hrefMain.'.search');
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid',0);
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid',0);
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if(!$cid)
        {
            $cid = Session::get($this->hrefMain.'.search.cid',1);
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($aid)
        {
            list($listAryCnt,$listAry) = $this->getDoorTodayRept([$cid,$aid,$bid,0,'']);

            if($request->has('showtest'))
            {
                dd([$aid,$bid,$cid],$listAry);
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');

        $html = $form->select('aid',$storeAry,$aid,2,Lang::get($this->langText.'.base_40106'));
        $html.= $form->select('bid',$supplyAry,$bid,2,Lang::get($this->langText.'.base_40244'));
        $html.= $form->select('cid',$doorTypeAry,$cid,2,Lang::get($this->langText.'.base_30115'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','','');
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.base_40110'),'red',1);
        $form->addRow($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.base_40106')]; //廠區

        $heads[] = ['title'=>Lang::get($this->langText.'.base_40244')]; //承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30118')]; //承商人員
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30119')]; //進出時間
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30115')]; //進出
        $table->addHead($heads,1);
        if($listAryCnt)
        {
            foreach($listAry as $value)
            {
                $no++;

                $id              = $value->b_cust_id;
                $aid             = SHCSLib::encode($value->b_factory_id);
                $rept1           = $value->store;
                $rept2           = $value->unit_name;
                $rept4           = $value->name;
                $rept5           = $value->door_stamp;
                $rept6           = $value->door_type_name;
                $btn             = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,'aid='.$aid),Lang::get('sys_btn.btn_37'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                    '1'=>[ 'name'=> $rept1],
                    '2'=>[ 'name'=> $rept2],
                    '4'=>[ 'name'=> $rept4],
                    '5'=>[ 'name'=> $rept5],
                    '6'=>[ 'name'=> $rept6],
                    '99'=>[ 'name'=> $btn],
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
        $aid = SHCSLib::decode($request->aid);
        $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
        $btnShow = $btnName = '';
        $btnColor = 1;
        if($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        }

        //是否已有存在的帳號
        if(!User::isExist($id))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10023'))
                ->withInput();
        }
        //廠區不存在
        if(!$aid)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10024'))
                ->withInput();
        }
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header

        //
        $logData = LogLib::getTodayInputLog($id,$aid);
        //
        $lastInOutData = rept_doorinout_t::getLastData($id,$aid);
        $lastInOut = isset($lastInOutData->door_type)? $lastInOutData->door_type : 0;
        if($lastInOut > 0)
        {
            $btnShow         = ($lastInOut == 1)? 'btn_65' : 'btn_64';
            $btnName         = ($lastInOut == 1)? 'doorOut' : 'doorIn';
            $btnColor        = ($lastInOut == 1)? 5 : 4;
        }

        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);

        //table
        $table = new TableLib();
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30119')]; //進出時間

        $heads[] = ['title'=>Lang::get($this->langText.'.base_40244')]; //承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30118')]; //承商人員
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30115')]; //進出
        $table->addHead($heads,0);
        if($logData)
        {
            $isFirst = 0;
            foreach($logData as $value)
            {
                $isFirst++;

                $rept1           = ($isFirst === 1)? HtmlLib::Color($value->door_stamp,'red') : $value->door_stamp;
                $rept4           = ($isFirst === 1)? HtmlLib::Color($value->unit_name,'red') : $value->unit_name;
                $rept5           = ($isFirst === 1)? HtmlLib::Color($value->name,'red') : $value->name;
                $rept6           = isset($doorTypeAry[$value->door_type])? $doorTypeAry[$value->door_type] : '';
                $rept6           = ($isFirst === 1)? HtmlLib::Color($rept6,'red') : $rept6;

                $tBody[] =[
                    '1'=>[ 'name'=> $rept1],
                    '4'=>[ 'name'=> $rept4],
                    '5'=>[ 'name'=> $rept5],
                    '6'=>[ 'name'=> $rept6],
                ];

            }
            $table->addBody($tBody);
        }
        //輸出
        $submitDiv  = $table->output();
        unset($table);
        //Submit
        if($btnShow)
        {
            $submitDiv .= $form->submit(Lang::get('sys_btn.'.$btnShow),$btnColor,$btnName).'&nbsp;';
        }
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('aid',$request->aid);
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,2));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> Javascript
        //-------------------------------------------//
        $js = '$(function () {

        });';

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
        $id  = SHCSLib::decode($request->id);
        $aid = SHCSLib::decode($request->aid);
        //是否已有存在的帳號
        if(!User::isExist($id))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10023'))
                ->withInput();
        }
        //廠區不存在
        if(!$aid)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10024'))
                ->withInput();
        }


        $this->getBcustParam();
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;
        $hrefBack = $this->hrefMainDetail.$request->id.'?aid='.$request->aid;

        //1. 取得上次的紀錄
        $lastRecord = rept_doorinout_t::getLastData($id,$aid);
        if(isset($lastRecord->log_door_inout_id))
        {
            $logData = DB::table('log_door_inout')->where('id',$lastRecord->log_door_inout_id)->first();
//            dd($lastRecord,$logData);

            $door_type = ($logData->door_type == 2)? 1 : 2;
            $retAry[] = $id;               //ID
            $retAry[] = $logData->name;              //人員姓名＆車牌
            $retAry[] = $logData->bc_type;           //類別
            $retAry[] = $logData->b_supply_id;       //承攬商
            $retAry[] = $logData->unit_name;         //承攬商名稱
            $retAry[] = $logData->b_rfid_id;         //RFID ID
            $retAry[] = $logData->rfid_code;         //RFID 內碼
            $retAry[] = $door_type;         //進出模式
            $retAry[] = date('Y-m-d H:i:s');  //進出時間
            $retAry[] = $logData->b_factory_id;
            $retAry[] = $logData->b_factory_d_id;
            $retAry[] = $logData->e_project_id;
            $retAry[] = 'Y';
            $retAry[] = Lang::get($this->langText.'.base_10162');
            $retAry[] = $logData->job_kind;
            $retAry[] = $logData->wp_work_id;           //當日工作許可證
        //    dd([$retAry]);

            $logid = LogLib::putInOutLog($retAry,'',0,'N','N');
            //人員每日 進出報表
            $INS = $this->createDoorMenInOutToday($retAry, $logid, $logData->wp_work_id);

            //2020-01-22 離場：增加離場紀錄 ＋ 解除工作許可證
            if($door_type == 2 && $logData->wp_work_id)
            {
                $this->setWrokPermitWorkerMenOut($logData->wp_work_id,$id);

                $today   = date('Y-m-d');
                $work_id = rept_doorinout_t::getWorkId($logData->b_factory_id,$today,$id);
                $aproc   = wp_work::getAproc($work_id);
                if(in_array($aproc,['F','C']))
                {
                    $this->freedWorkPermitWorkerMen($id);
                }
            }

            //2-1-2 回報 更新成功
            Session::flash('message',Lang::get($this->langText.'.base_10161'));
            return \Redirect::to($hrefBack);
        }
    }
}
