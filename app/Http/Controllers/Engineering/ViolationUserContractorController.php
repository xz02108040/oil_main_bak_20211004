<?php

namespace App\Http\Controllers\Engineering;

use App\Exports\ExcelExport;
use App\Exports\RfidExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\ViolationContractorTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_violation;
use App\Model\Engineering\e_violation_law;
use App\Model\Engineering\e_violation_punish;
use App\Model\Engineering\e_violation_type;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Excel;

class ViolationUserContractorController extends Controller
{
    use ViolationContractorTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ViolationContractorController
    |--------------------------------------------------------------------------
    |
    | 人員違規 維護
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
        $this->hrefMain         = 'user_eviolationcontractor';
        $this->hrefExcel        = 'user_eviolationcontractorexcel';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'user_eviolationcontractor/';
        $this->hrefMainNew      = 'new_eviolationcontractor';
        $this->routerPost1      = 'postEViolationcontractor';
        $this->routerPost2      = 'eviolationcontractorCreate';

        $this->pageTitleMain    = Lang::get($this->langText.'.title15-1');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list15');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new15');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit15');//編輯
        $this->pageExcelTitle   = Lang::get($this->langText.'.excel15');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageExcelBtn     = Lang::get('sys_btn.btn_29');//[按鈕]下載

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
        //$parent    = ($request->pid)? SHCSLib::decode($request->pid) : 0;
		$parent    = $request->pid;
        Session::put($this->hrefMain.'.search.pid',$parent);
        $supplyAry = b_supply::getSelect();
        $bid       = ($parent)? $parent : $request->bid;
        $sdate     = $request->sdate;
        $edate     = $request->edate;
		//echo "parent:".$parent." bid:".$bid."<br>";
		
		if($request->has('clear'))
        {
            $bid = $sdate = $edate = '';
            Session::forget($this->hrefMain.'.search');
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid',0);
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate','');
        } else {
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate','');
        } else {
            Session::put($this->hrefMain.'.search.edate',$edate);
        }
		
		//echo "parent:".$parent." bid:".$bid."<br>";
//        dd($search,$supplyAry);
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        $hrefExcel= $this->hrefExcel;
        $btnExcel = $this->pageExcelBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$parent)
        {
            //$search = [$bid,$sdate,$edate];
            //$listAry = $this->getApiViolationContractorSupplyList($search);
			$listAry = [];

			if($bid!="")
			{
				$search = [$bid,'',$sdate,$edate,0,0,0];
            	$listAry = $this->getApiViolationContractorList($search);
			}
			
        } else {
            
			$search = [$bid,'',$sdate,$edate,0,0,0];
            $listAry = $this->getApiViolationContractorList($search);

        }
		//echo "數量:".count($listAry);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //搜尋
        $html = $form->select('bid',$supplyAry,$bid,2,Lang::get($this->langText.'.engineering_7'));
        $form->addRowCnt($html);
        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.engineering_8'));
        $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.engineering_9'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.engineering_1045'),'red',1);
        $form->addRow($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_14')]; //成員姓名
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_15')]; //行動電話
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_51')]; //違規
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_60')]; //限制進出
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_52')]; //違規分類
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_53')]; //違規法規
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_55')]; //違規罰則
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_58')]; //再次在廠日期


        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                
				$name1          = $value->user; //
                $name2          = $value->mobile1; //
                $name3          = $value->violation_record1; //
                $name4          = $value->apply_stamp; //
                $name5          = $value->violation_record4; //
                $name6          = $value->violation_record2; //
                $name7          = $value->violation_record3; //
                $name8          = $value->limit_edate; //
                $Color          = 0; //

                $isEdit         = strtotime($value->limit_edate) > strtotime(date('Y-m-d'))? 1 : 0;
                $nameBtn        = ($isEdit)? 'btn_13' : 'btn_30';
                $nameColor      = ($isEdit)? 4 : 1;
                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.'.$nameBtn),$nameColor); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                     '1'=>[ 'name'=> $name1],
                     '2'=>[ 'name'=> $name2],
                     '3'=>[ 'name'=> $name3],
                     '4'=>[ 'name'=> $name4],
                     '5'=>[ 'name'=> $name5],
                     '6'=>[ 'name'=> $name6],
                     '7'=>[ 'name'=> $name7],
                     '8'=>[ 'name'=> $name8,'label'=>$Color],
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
                    $("#sdate,#edate").datepicker({
                        format: "yyyy-mm-dd",
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
     * 單筆資料 編輯
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $selectAry  = e_violation::getSelect();
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $A1         = $getData->project; //
            $A2         = $getData->supply; //
            $A3         = $getData->user; //
            $A4         = $getData->bc_id; //
            $A5         = $getData->e_violation_id; //
            $A6         = $getData->violation_record1; //
            $A7         = $getData->violation_record2; //
            $A8         = $getData->violation_record3; //
            $A9         = $getData->violation_record4; //
            $A10        = $getData->isControl;
            $A11        = $getData->apply_stamp; //
            $A12        = ($getData->isControl == '是')? $getData->limit_sdate.' ～ '.$getData->limit_edate : ''; //
            $A13        = $getData->e_project_id; //
            $A14        = $getData->b_supply_id; //
            $A15        = $getData->b_cust_id; //
            $A16        = $getData->e_violation_complain_id; //
            $A17        = $getData->charge_user; //
            $A18        = $getData->charge_stamp; //
            $A19        = $getData->charge_memo; //
            $A20        = $getData->limit_edate1.' => '.$getData->limit_edate2; //
            $A21        = (strtotime($getData->limit_edate) > strtotime(date('Y-m-d')))? 1 : 0; //
            $pid        = SHCSLib::encode($A14); //

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost1,$id),'POST',1,TRUE);
        //承攬項目
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'));
        //負責承攬商
        $html = $A2.'-'.$A3.' ('.$A4.')';
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_59'));
        //違規時間
        $html = $A11;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_60'));
        //違規事項
        $html = $A6.' ('.$A9.')';
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_51'));
        //法規
        $html = $A7;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_53'));
        //罰條
        $html = $A8;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_55'));
        //是否管制進出
        $html = $A10;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_56'));
        //再次入場時間
        $html = $A12;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_61'));

        //申訴
        if($A16)
        {
            $form->addHr();
            //審查結果
            $html = $A20;
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_68'));
            //審查人
            $html = $A17;
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_69'));
            //審查時間
            $html = $A18;
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_71'));
            //審查事由
            $html = $A19;
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_70'));
        }

        if($A21)
        {
            $form->addHr();
            //違規事項
            $html = $form->select('e_violation_id',$selectAry,$A5);
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_51'),1);
            //違規時間點
            $html  = $form->date('apply_date','',2);
            $html .= $form->time('apply_time','',2);
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_60'),1);
            //停用
            $html = $form->checkbox('isClose','Y',$A99);
            $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_34'));
            if($A99)
            {
                $html = $A97;
                $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
            }
        }

        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv = '';
        if($A21)
        {
            //$submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;'; //編輯人員違規之更新按鈕
        }
        $submitDiv = $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('e_project_id',$A13);
        $submitDiv.= $form->hidden('b_supply_id',$A14);
        $submitDiv.= $form->hidden('b_cust_id',$A15);
		
		//echo "urlid:".$urlid;
		
		
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
            $("#apply_date").datepicker({
                format: "yyyy-mm-dd",
                language: "zh-TW"
            });
            $("#apply_time").timepicker({
                showMeridian: false,
                defaultTime: false,
                timeFormat: "HH:mm"
            })
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
