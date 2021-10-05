<?php

namespace App\Http\Controllers\Engineering;

use App\Exports\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Engineering\TraningMemberTrait;
use App\Http\Traits\Engineering\TraningTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project_type;
use App\Model\Engineering\et_course;
use App\Model\Engineering\et_traning_m;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_supply_user;
use App\Model\View\view_user;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Excel;
use Storage;

class TraningSupplyMemberController extends Controller
{
    use TraningTrait,TraningMemberTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | TraningSupplyMemberController
    |--------------------------------------------------------------------------
    |
    | 承攬商教育訓練情況
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
        $this->hrefMain         = 'etraningsupplymember';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'etraningsupplymember/';
        $this->hrefMainNew      = 'new_etraningsupplymember';
        $this->routerPost       = 'postERPTraning2';

        $this->pageTitleMain    = Lang::get($this->langText.'.title29');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list29');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new29');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit29');//編輯
        
        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_41');//[按鈕]編輯
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
        $mebmerAry = $listAry = [];
//        $supplyAry  = b_supply::getSelect();
        $cpcTagAry  = SHCSLib::getCode('CPC_TAG');
        $sid        = !is_null($request->sid)? $request->sid : '';
        $sid2       = !is_null($request->sid2)? $request->sid2 : '';
        $nid        = !is_null($request->nid)? $request->nid : '';

        if($request->has('clear'))
        {
            $sid = $sid2 = $nid = '';
            Session::forget($this->langText.'.search');
        }
        if(!$sid && strlen($sid2) > 1){
            $sid2 = SHCSLib::decode($sid2);
            Session::forget($this->langText.'.search');
        }
        if(!$sid)
        {
            $sid = Session::get($this->langText.'.search.sid','');
        } else {
            Session::put($this->langText.'.search.sid',$sid);
        }
        if(!$nid)
        {
            $nid = Session::get($this->langText.'.search.nid','');
        } else {
            Session::put($this->langText.'.search.nid',$nid);
        }
//        dd($sid,$sid2,$nid);
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $listAry = array();
//        $hrefNew  = $this->hrefMainNew;
//        $btnNew   = $this->pageNewBtn;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($sid || ($sid2 > 0) || $nid)
        {
            $b_cust_id = 0;
            $supply_id  = 0;
            if($nid) $b_cust_id = view_user::SearchBCID($nid);
            if($b_cust_id)
            {
                $mebmerAry  = view_supply_user::where('b_cust_id',$b_cust_id)->get();
            } else {
                $supply_id2 = ($sid2 > 0)? $sid2 : b_supply::SearchName($sid);
                $mebmerAry  = view_supply_user::where('b_supply_id',$supply_id2)->get();
                $b_cust_id = [];
                foreach ($mebmerAry as $val)
                {
                    $b_cust_id[] = $val->b_cust_id;
                }
            }
//            dd($nid,$sid,$supply_id,$b_cust_id);
            $listAry = $this->getApiTraningMemberList2(0,0,$supply_id,$b_cust_id);
            Session::put($this->hrefMain.'.Record',$listAry);
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        //$form->addHr();
        $html = $form->text('sid',$sid,2,Lang::get($this->langText.'.engineering_159'));
        $html.= $form->text('nid',$nid,2,Lang::get($this->langText.'.engineering_15'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $form->addHr();

        // Excel
        $excelReport = [];
        $excelReport[] = ['工程案號', '工程案件', '名稱', '身分證', '專案角色', '課程', '開課日期', '進度'];

        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_4')];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_1')]; //工程案件
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_31')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_15')]; //身分證
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_20')]; //專案角色
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_42')]; //課程
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_44')]; //上課時段
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_12')]; //進度

        $table->addHead($heads,0);
        if(count($mebmerAry))
        {
            foreach($mebmerAry as $value)
            {
                //2. 抓是否有無工程案件
                $supplyMemberAry = view_door_supply_member::getData($value->b_cust_id);
                $name1        = $value->name; //
                $name2        = SHCSLib::genBCID($value->bc_id); //
                $no           = isset($supplyMemberAry->project_no)? $supplyMemberAry->project_no : ''; //
                $name3        = isset($supplyMemberAry->project)? $supplyMemberAry->project : ''; //
                $name4        = isset($supplyMemberAry->cpc_tag) && isset($cpcTagAry[$supplyMemberAry->cpc_tag])? $cpcTagAry[$supplyMemberAry->cpc_tag] : ''; //

                //3.
                $traningAry   = isset($listAry[$value->b_cust_id])? $listAry[$value->b_cust_id] : [];
                $name5 = $name6 = $name7 = '';

                if(isset($traningAry->b_cust_id))
                {
                    $name5        = isset($traningAry->b_cust_id)? $traningAry->traning_date.'，'.$traningAry->week : ''; //
                    $name7        = isset($traningAry->b_cust_id) ? $traningAry->course : ''; //
                    if($traningAry->aproc == 'O')
                    {
                        $name6    = $traningAry->aproc_name.'，'.Lang::get($this->langText.'.engineering_103').'：'.$traningAry->valid_date; //
                    } else {
                        $name6    = $traningAry->aproc_name; //
                    }
                }

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '3'=>[ 'name'=> $name3],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '4'=>[ 'name'=> $name4],
                            '7'=>[ 'name'=> $name7],
                            '5'=>[ 'name'=> $name5],
                            '6'=>[ 'name'=> $name6],
                ];

                // Excel
                $tmp    = [];
                $tmp[]  = $no;
                $tmp[]  = $name3;
                $tmp[]  = $name1;
                $tmp[]  = $name2;
                $tmp[]  = $name4;
                $tmp[]  = $name7;
                $tmp[]  = $name5;
                $tmp[]  = $name6;
                $excelReport[] = $tmp;
                unset($tmp);
            }
            $table->addBody($tBody);
        }
        //輸出
        $out .= $table->output();
        unset($table);

        // Excel
        if ($request->has('download')) {
            Session::put('download.exceltoexport', $excelReport);
            return Excel::download(new ExcelExport(), Lang::get($this->langText.'.list29'). '.xlsx');
        }

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTitle,$out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#sdate,#edate").datepicker({
                        format: "yyyy-mm-dd",
                        language: "zh-TW"
                    });
                    $("#table1").DataTable({
                        "order": [[ 4, "desc" ], [ 6, "asc" ]],
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
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $pid      = Session::get('user.supply_id',0);
        if(!$pid || !is_numeric($pid))
        {
            $msg = Lang::get('sys_supply.supply_1000');
            return \Redirect::back()->withErrors($msg);
        }
        $courseAry    = et_course::getSelect();
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
            $A1         = $getData->et_course_id; //
            $A2         = isset($courseAry[$A1])? $courseAry[$A1] : ''; //
            //$A2         = $getData->teacher; //
            $A3         = $getData->sdate; //
            $A4         = $getData->edate; //
            $A5         = $getData->valid_day; //
            $A13        = $getData->memo; //

            //已經報名成員
            $mebmer2    = $this->getApiTraningMemberList($pid,$A1,0,0,[],1);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //課程
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_42'),1);
        //授課區間
        $html = $A3.' - '.$A4;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_37'),1);
        //說明
        //$html = $A13;
        //$form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));

        //已經報名
        $table = new TableLib();
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get('sys_supply.supply_19')]; //成員
        $heads[] = ['title'=>Lang::get('sys_supply.supply_21')]; //身分證
        $heads[] = ['title'=>Lang::get('sys_supply.supply_52')]; //進度
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_80')]; //報名申請

        $table->addHead($heads,0);
        if(count($mebmer2))
        {
            $no = 0;
            foreach($mebmer2 as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->name; //
                $name2        = SHCSLib::genBCID($value->bc_id); //
                $name3        = $value->aproc_name; //
                $name4        = $value->apply_date; //

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $form->add('nameT1', $table->output(),Lang::get($this->langText.'.engineering_86'));
        unset($table);


        //Submit
        //$submitDiv  = $form->submit(Lang::get('sys_btn.btn_41'),'1','agreeY').'&nbsp;';
        $submitDiv  = $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        //$submitDiv.= $form->hidden('et_course_id',$A1);
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
