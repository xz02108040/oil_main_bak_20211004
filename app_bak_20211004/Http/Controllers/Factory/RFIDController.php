<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Http\Traits\Factory\RFIDTrait;
use App\Http\Traits\Factory\RFIDTypeTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_rfid;
use App\Model\Factory\b_rfid_a;
use App\Model\Factory\b_rfid_type;
use App\Model\Supply\b_supply;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_log_door_today;
use App\Model\View\view_used_rfid;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class RFIDController extends Controller
{
    use RFIDTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | RFIDController
    |--------------------------------------------------------------------------
    |
    | RFID 維護
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
        $this->hrefMain         = 'rfid';
        $this->hrefExcel1       = 'exceltorfid';
        $this->hrefExcel2       = 'exceltorfidpair1';
        $this->hrefPair         = 'rfidpair';
        $this->langText         = 'sys_rfid';

        $this->hrefMainDetail   = 'rfid/';
        $this->hrefMainNew      = 'new_rfid';
        $this->routerPost       = 'postRFID';

        $this->pageTitleMain    = Lang::get($this->langText.'.title1');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list1');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new1');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit1');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageExcelBtn1    = Lang::get('sys_btn.btn_43');//[按鈕]匯入
        $this->pageExcelBtn2    = Lang::get('sys_btn.btn_44');//[按鈕]匯入

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
        $no  = 0;
        $typeAry  = b_rfid_type::getSelect();
        $supplyAry = b_supply::getSelect();  //承攬商陣列
        $usedAry  = SHCSLib::getCode('RFID_USED',1);
        $closeAry = SHCSLib::getCode('CLOSE',1);
        $aid      = $request->aid;
        $bid      = $request->bid;
        $cid      = $request->cid;
        $did      = $request->did;
        $eid      = $request->eid;
        $fid      = $request->fid;
        if($request->has('clear'))
        {
            $aid = $bid = $cid = $did = $eid = $fid = 0;
            Session::forget($this->hrefMain);
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
            $cid = Session::get($this->hrefMain.'.search.cid','N');
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        if(!$did)
        {
            $did = Session::get($this->hrefMain.'.search.did',0);
        } else {
            Session::put($this->hrefMain.'.search.did',$did);
        }
        if (!$eid) {
            $eid = Session::get($this->hrefMain . '.search.eid', '');
        } else {
            Session::put($this->hrefMain . '.search.eid', $eid);
        }
        if (!$fid) {
            $fid = Session::get($this->hrefMain . '.search.fid', '');
        } else {
            Session::put($this->hrefMain . '.search.fid', $fid);
        }
        //view元件參數
        $tbTitle    = $this->pageTitleList;//列表標題
        $hrefMain   = $this->hrefMain;
        $hrefNew    = $this->hrefMainNew;
        $btnNew     = $this->pageNewBtn;
        $hrefExcel1 = $this->hrefExcel1;
        $btnExcel1  = $this->pageExcelBtn1;
        $hrefExcel2 = $this->hrefExcel2;
        $btnExcel2  = $this->pageExcelBtn2;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiRFIDList($aid,$bid,$cid,$did,$eid,$fid);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y') $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefExcel1, $btnExcel1,1); //匯入
        $form->addLinkBtn($hrefExcel2, $btnExcel2,8); //匯入
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //搜尋
        $html = $form->select('aid',$typeAry,$aid,2,Lang::get($this->langText.'.rfid_11'));
        $html.= $form->select('did',$supplyAry,$did,2,Lang::get($this->langText.'.rfid_21'));
        $html.= $form->select('bid',$usedAry,$bid,2,Lang::get($this->langText.'.rfid_15'));
        $html.= $form->select('cid',$closeAry,$cid,2,Lang::get($this->langText.'.rfid_13'));
        $form->addRowCnt($html);

        $html = $form->text('eid', $eid, 2, Lang::get('sys_emp.emp_1'));
        $html.= $form->text('fid', $fid, 2, Lang::get($this->langText.'.rfid_2'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.rfid_1016'),'red',1);
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_11')]; //分類
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_1')]; //卡片編碼
        $heads[] = ['title'=>Lang::get('sys_supply.supply_21')]; //身分證
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_2')]; //卡片內容
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_5')];  //開始日期
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_6')];  //結束日期
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_15')]; //使用狀態
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_13')]; //使用狀態
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_16')]; //配對

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->rfid_type_name; //
                $name2        = $value->rfid_code; //
                $name3        = $value->nation_name .' / '.SHCSLib::genBCID($value->bc_id); //
                $name4        = $value->name; //
                $name5        = $value->b_rfid_a_id; //
                $name6        = substr($value->sdate, 0, 11);          //
                $name7        = substr($value->edate, 0, 11);          //
                $usedStr      = b_rfid_a::getUsedCnt($name5);
                $usedStr      = ($usedStr)? ('<span style="font-size: 1.2em">'.$usedStr.'</span>') : '';
                $usedStr1     = isset($usedAry[$value->isUsed])? $usedAry[$value->isUsed] : '';
                $usedStr1    .= $value->close_memo? '  ( '.$value->close_memo.' )' : '';
                $isUsedCnt    = $value->isUsed === 'Y' ? $usedStr : $usedStr1; //
                $isUsedColor  = $value->isUsed === 'Y' ? 2 : 5 ; //顏色
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : ''; //
                $isCloseColor = $value->isClose === 'Y' ? 5 : 2 ; //顏色

                //按鈕
                $btn     = (($this->isWirte == 'Y') && $value->isClose == 'N') ? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail, $id), Lang::get('sys_btn.btn_13'), 1) : ''; //按鈕
                $btnPair      = HtmlLib::btn(SHCSLib::url($this->hrefPair,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_39'),4); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '11'=>[ 'name'=> $name4],
                            '3'=>[ 'name'=> $name3],
                            '2'=>[ 'name'=> $name2],
                            '6'=>[ 'name' => $name6],
                            '7'=>[ 'name' => $name7],
                            '21'=>[ 'name'=> $isUsedCnt,'label'=>$isUsedColor],
                            '22'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
                            '90'=>[ 'name'=> $btnPair],
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
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $typeAry        = b_rfid_type::getSelect();
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //資料明細
            $A1         = $getData->rfid_code; //
            $A2         = $getData->rfid_type; //
            $A4         = $getData->name; //
            $A12        = b_rfid_a::getUsedUser($getData->b_rfid_a_id);

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->text('name',$A4);
        $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_1'),1);
        //名稱
        $html = $form->text('rfid_code',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_2'),1);
        //分類
        $html = $form->select('rfid_type',$typeAry,$A2);
        $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_11'),1);
        //停用
        $isIn = ($A12)? view_log_door_today::isIn($A12) : false ;
        $html = (!$isIn)? $form->checkbox('isClose','Y',$A99) : HtmlLib::Color(Lang::get($this->langText.'.rfid_19'),'red',1);

        $form->add('isCloseT',$html,Lang::get($this->langText.'.rfid_14'));
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

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
        $content->rowTo($content->box_form($tbTitle, $out,2));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> Javascript
        //-------------------------------------------//
        $js = '$(function () {
            $("#sdate,#edate").datepicker({
                format: "yyyy-mm-dd",
                startDate: "today",
                language: "zh-TW"
            });
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
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->rfid_code || !$request->rfid_type )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif( $request->isClose != 'Y' && b_rfid::isExist(0,$request->rfid_code,SHCSLib::decode($request->id)))
        {
            //內碼已經存在
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10165'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']              = $request->name;
        $upAry['rfid_code']         = $request->rfid_code;
        $upAry['rfid_type']         = is_numeric($request->rfid_type) ? $request->rfid_type : 1;
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createRFID($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setRFID($id,$upAry,$this->b_cust_id);
        }
        //2-1. 更新成功
        if($ret)
        {
            //沒有可更新之資料
            if($ret === -1)
            {
                $msg = Lang::get('sys_base.base_10109');
                return \Redirect::back()->withErrors($msg);
            } else {
                //動作紀錄
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_rfid',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.base_10105');
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        if($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        }
        //參數
        $js = $contents = '';
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header
        $typeAry    = b_rfid_type::getSelect();

        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html = $form->text('name','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_1'),1);
        //名稱
        $html = $form->text('rfid_code','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_2'),1);
        //分類
        $html = $form->select('rfid_type',$typeAry,0);
        $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_11'),1);

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
        $content->rowTo($content->box_form($tbTitle, $out,1));
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
