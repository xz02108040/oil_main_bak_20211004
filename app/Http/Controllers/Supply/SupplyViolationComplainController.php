<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\ViolationComplainTrait;
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
use App\Model\Engineering\e_violation_contractor;
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

class SupplyViolationComplainController extends Controller
{
    use ViolationContractorTrait,ViolationComplainTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyViolationComplainController
    |--------------------------------------------------------------------------
    |
    | 人員違規 申訴
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
        $this->hrefMain         = 'rp_eviolationcomplain';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'rp_eviolationcomplain/';
        $this->hrefMainNew      = 'new_rp_eviolationcomplain';
        $this->routerPost1      = 'postContractorVcomplain';
        $this->routerPost2      = 'contractorVcomplainCreate';

        $this->pageTitleMain    = Lang::get($this->langText.'.title20');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list20');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new20');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit20');//編輯

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
        $parent    = ($request->pid)? SHCSLib::decode($request->pid) : 0;
        Session::put($this->hrefMain.'.search.pid',$parent);
        $storeAry  = b_factory::getSelect();
        $supplyAry = b_supply::getSelect();
        $bid       = $request->bid;
        if($request->has('clear'))
        {
            $bid = '';
            Session::forget($this->hrefMain.'.search');
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid',0);
        } else {
            Session::put($this->hrefMain.'.search.aid',$bid);
        }
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$parent)
        {
            $search = [$bid,['A'],'',''];
            $listAry = $this->getApiViolationComplainSupplyList($search);

        } else {
            $search = [0,$parent,['A'],'',''];
            $listAry = $this->getApiViolationComplainList($search);
        }
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($parent)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        }
        $form->addHr();
        //搜尋
        $html = $form->select('bid',$supplyAry,$bid,2,Lang::get($this->langText.'.engineering_7'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        if(!$parent)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_7')]; //負責承商
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_16')]; //件數
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_14')]; //成員姓名
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_51')]; //違規
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_60')]; //限制進出
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_52')]; //違規分類
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_53')]; //違規法規
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_55')]; //違規罰則
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_58')]; //再次在廠日期
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_63')]; //再次在廠日期
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_64')]; //再次在廠日期
        }


        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                if(!$parent)
                {
                    $name1        = $value->name; //
                    $name4        = $value->amt; ///

                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_13'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '4'=>[ 'name'=> $name4],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $name1          = $value->user; //
                    $name3          = $value->violation_record1; //
                    $name4          = $value->apply_stamp2; //
                    $name5          = $value->violation_record4; //
                    $name6          = $value->violation_record2; //
                    $name7          = $value->violation_record3; //
                    $name8          = $value->limit_edate1; //
                    $name9          = $value->aproc_name; //
                    $name10         = ($value->aproc == 'O')? HtmlLib::Color($value->limit_edate2,'red',1) : ''; //
                    $Color          = 0; //

                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_30'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '5'=>[ 'name'=> $name5],
                        '6'=>[ 'name'=> $name6],
                        '7'=>[ 'name'=> $name7],
                        '8'=>[ 'name'=> $name8,'label'=>$Color],
                        '9'=>[ 'name'=> $name9],
                        '11'=>[ 'name'=> $name10],
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
            $A5         = $getData->e_violation_id; //
            $A6         = $getData->violation_record1; //
            $A7         = $getData->violation_record2; //
            $A8         = $getData->violation_record3; //
            $A9         = $getData->violation_record4; //
            $A10        = ($getData->isControl == 'Y')? '是' : '否';
            $A11        = $getData->apply_stamp1; //
            $A12        = ($getData->isControl == 'Y')? $getData->limit_sdate.' ～ '.$getData->limit_edate : ''; //
            $A13        = $getData->apply_stamp2; //
            $A14        = $getData->apply_user; //
            $A15        = $getData->apply_memo; //
            $A16        = $getData->aproc_name; //
            $pid        = $getData->b_supply_id; //

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost1,$id),'POST',1,TRUE);
        //工程案件
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'));
        //負責承攬商
        $html = $A2.'-'.$A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_59'));
        //違規時間
        $html = $A13;
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

        $form->addHr();
        //申請人
        $html = $A14;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_65'));
        //申請時間
        $html = $A11;
        $form->add('nameT98',$html,Lang::get($this->langText.'.engineering_66'));
        //申訴進度
        $html = $A16;
        $form->add('nameT98',$html,Lang::get($this->langText.'.engineering_63'));
        //申訴事由
        $html = $A15;
        $form->add('nameT98',$html,Lang::get($this->langText.'.engineering_62'));
        $form->addHr();
        //再次入場時間
        $html = $form->date('limit_edate2');
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_64'));
        //審查備註
        $html = $form->textarea('memo');
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_67'),1);

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack.'?pid='.$pid, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('vid',$getData->e_violation_contractor_id);
        $submitDiv.= $form->hidden('limit_sdate',$getData->limit_sdate);
        $submitDiv.= $form->hidden('limit_edate',$getData->limit_edate);
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
            $("#limit_edate2").datepicker({
                format: "yyyy-mm-dd",
                language: "zh-TW",
                startDate: new Date(),
                endDate: new Date("'.$getData->limit_edate.'"),
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
        if( (!$request->has('agreeY') && !$request->has('agreeN')) || !$request->id || !$request->vid || !$request->limit_sdate || !$request->memo )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif($request->has('agreeY') && !CheckLib::isDate($request->limit_edate2))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1023'))
                ->withInput();
        }
        elseif($request->has('agreeY') && strtotime($request->limit_edate2) > strtotime($request->limit_edate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1021'))
                ->withInput();
        }
        elseif($request->has('agreeY') && strtotime($request->limit_edate2) < strtotime($request->limit_sdate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1022'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id    = SHCSLib::decode($request->id);
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
        $upAry['vid']           = $request->vid;
        $upAry['aproc']         = $request->has('agreeY')? 'O' : 'C';
        $upAry['limit_edate2']  = $request->limit_edate2;
        $upAry['memo']          = $request->memo;

        //新增
        if($isNew)
        {
            $ret = 0;//$this->createViolationComplain($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setViolationComplain($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'e_violation_complain',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10124'));
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
    public function create(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents = '';
        $pid        = Session::get('user.supply_id',0);
        $projectAry = e_project::getSelect('P',$pid);
        $parent     = ($request->e_project_id)? $request->e_project_id : 0;
        $vid        = ($request->vid)? $request->vid : 0;
        $projectName= (isset($projectAry[$parent]))? $projectAry[$parent] : '';
        if($parent)
        {
            $listAry    = e_violation_contractor::getSelect($parent,$pid);
        }
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header
        $hrefPost   = ($parent && $vid)? $this->routerPost1 : $this->routerPost2;
        $btnSubmit  = ($parent && $vid)? Lang::get('sys_btn.btn_43') : Lang::get('sys_btn.btn_37');

        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($hrefPost,-1),'POST',1,TRUE);

        //先選擇 工程案件
        if(!$parent)
        {
            //名稱
            $html = $form->select('e_project_id',$projectAry);
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
        } elseif(!$vid)
        {
            $html  = $projectName;
            $html .= $form->hidden('e_project_id',$parent);
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
            //名稱
            $html = $form->select('vid',$listAry);
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
        } else {
            //工程案件
            $html  = $projectName;
            $html .= $form->hidden('e_project_id',$parent);
            $html .= $form->hidden('vid',$vid);
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
            //違規人員
            $html  = e_violation_contractor::getName($vid);
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_51'),1);
            //申訴理由
            $html = $form->textarea('memo');
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_62'));
        }

        //Submit
        $submitDiv  = $form->submit($btnSubmit,'1','agreeY').'&nbsp;';
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

    /**
     * 下載Excel
     * @return excel
     */
    protected function downExcel()
    {
        $pid   = Session::get($this->hrefMain.'.search.pid',0);
        $aid   = Session::get($this->hrefMain.'.search.aid',0);
        $bid   = Session::get($this->hrefMain.'.search.bid',0);
        $sdate = Session::get($this->hrefMain.'.search.sdate','');
        $edate = Session::get($this->hrefMain.'.search.edate','');
        if(!$pid)
        {
            \Redirect::back()->withErrors(Lang::get('sys_base.base_10122'));
        }

        $listAry = $this->getApiViolationContractorList([$bid,$aid,$sdate,$edate,$pid,0,0,'']);
        //dd($listAry);
        if(count($listAry))
        {
            $excelAry = $header = [];
            $header[] = Lang::get($this->langText.'.engineering_1');
            $header[] = Lang::get($this->langText.'.engineering_7');
            $header[] = Lang::get($this->langText.'.engineering_14');
            $header[] = Lang::get($this->langText.'.engineering_51');
            $header[] = Lang::get($this->langText.'.engineering_60');
            $header[] = Lang::get($this->langText.'.engineering_52');
            $header[] = Lang::get($this->langText.'.engineering_53');
            $header[] = Lang::get($this->langText.'.engineering_55');
            $header[] = Lang::get($this->langText.'.engineering_56');
            $header[] = Lang::get($this->langText.'.engineering_58');
            $excelAry[] = $header;
            foreach ($listAry as $value)
            {
                $excelAry[] = [$value->project,$value->supply,$value->user,$value->violation_record1,
                               $value->apply_stamp,$value->violation_record4,$value->violation_record2,
                               $value->violation_record3,$value->isControl,$value->limit_edate];
            }
            Excel::create(Lang::get($this->langText.'.excel15'),function($excel) use ($excelAry){
                $excel->sheet('REPORT', function($sheet) use ($excelAry){
                    $sheet->rows($excelAry);
                });
            })->export('xls');
        }
        \Redirect::back()->withErrors(Lang::get('sys_base.base_10122'));
    }

}
