<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyRPDoor1DetailTrait;
use App\Http\Traits\Supply\SupplyRPDoor1Trait;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Lib\CheckLib;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_rp_door1;
use App\Model\sys_param;
use App\Model\View\view_door_car;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_door_supply_whitelist_pass;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;
use Storage;
use function Matrix\diagonal;

class SupplyRPDoor1Controller extends Controller
{
    use SupplyRPDoor1Trait,SupplyRPDoor1DetailTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPDoor1Controller
    |--------------------------------------------------------------------------
    |
    | 承攬商[臨時入場/過夜] 申請單
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
        $this->hrefHome         = 'contractor';
        $this->hrefMain         = 'contractorrpdoor1';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'contractorrpdoor1/';
        $this->hrefMainDetail2  = 'contractorrpdoor1a/';
        $this->hrefMainNew      = 'new_contractorrpdoor1';
        $this->routerPost       = 'postContractorrpdoor1';
        $this->routerPost2      = 'contractorrpdoor1Create';

        $this->pageTitleMain    = Lang::get($this->langText.'.title19');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list19');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new19');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit19');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_11');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

        $this->fileSizeLimit1   = config('mycfg.file_upload_limit','102400');
        $this->fileSizeLimit2   = config('mycfg.file_upload_limit_name','10MB');
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
        //允許管理的工程案件
        $allowProjectAry = ($this->isRootDept)? [] : $this->allowProjectAry;
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        //參數
        $no = 0;
        $out = $js ='';
        $today     = date('m/d/Y');
        $aprocColorAry  = ['A'=>1,'P'=>4,'R'=>4,'O'=>2,'C'=>5];
        $kindColorAry   = [1=>1,2=>2,3=>4];
        $aprocAry       = SHCSLib::getCode('RP_DOOR_APROC1',1);
        $typeAry        = SHCSLib::getCode('RP_DOOR_KIND1');
        $yesAry         = SHCSLib::getCode('YES');
        $overAry        = SHCSLib::getCode('DATE_OVER');
        //進度
        $aproc    = $request->aproc;
        $type     = $request->type;
        $isNow    = $request->isNow;
        $sdate     = $request->sdate;
        $edate     = $request->edate;

        if($request->has('clear'))
        {
            $aproc = $type = $isNow = $sdate = $edate = '';
            Session::forget($this->hrefMain.'.search');
        }
        if($aproc)
        {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        } else {
            $aproc = Session::get($this->hrefMain.'.search.aproc',($this->isRootDept? 'R' : 'P'));
        }
        if($type)
        {
            Session::put($this->hrefMain.'.search.type',$type);
        } else {
            $type = Session::get($this->hrefMain.'.search.type',1);
        }
        if($isNow)
        {
            Session::put($this->hrefMain.'.search.isNow',$isNow);
        } else {
            $isNow = Session::get($this->hrefMain.'.search.isNow','Y');
        }
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate',$today);
        } else {
//            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate',$today);
        } else {
            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate; //如果結束日期 小於開始日期
            Session::put($this->hrefMain.'.search.edate',$edate);
        }

        if($type == 1) { unset($aprocAry['R']); } else { unset($aprocAry['P']);}
        $kindName = isset($typeAry[$type])? $Icon.$typeAry[$type] : '';
        //view元件參數
        $tbTitle  = $this->pageTitleList.$kindName;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew.$request->pid;
        $btnNew   = Lang::get('sys_btn.btn_7');
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料 //aproc:RP_DOOR_APROC1
        $isCharge= ($this->isRootDept && $aproc == 'R')? 1 : ((!$this->isRootDept && $aproc == 'P')? 1 : 0);
        $isRootDept = $this->isRootDept;

        $listAry = $this->getApiRPDoor1List($allowProjectAry,$type,$aproc,$isNow, $sdate, $edate);
        Session::put($this->hrefMain.'.Record',$listAry);
        Session::put($this->hrefMain.'.project',$allowProjectAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');

        if($isRootDept){
            $form->addLinkBtn($hrefNew, $btnNew,1); //新增
        }
//        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //搜尋
        $html= $form->date('sdate',$sdate,2,'開始日期');
        $html.= $form->date('edate',$edate,2,'結束日期');
        $form->addRowCnt($html);

        $html = $form->select('type',$typeAry,$type,2,Lang::get($this->langText.'.supply_2'));
        $html.= $form->select('aproc',$aprocAry,$aproc,2,Lang::get($this->langText.'.supply_52'));
        $html.= $form->select('isNow',$yesAry,$isNow,1,Lang::get($this->langText.'.supply_110'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        //說明
        $html = HtmlLib::Color(Lang::get('sys_supply.supply_1100'),'red',1);
        $form->addRow($html,11,1);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'No'];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_2')]; //類型
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_29')]; //申請人
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_28')]; //申請時間
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_49')]; //工程案件
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_109')]; //申請廠區
        if($type == 3)  $heads[] = ['title'=>Lang::get($this->langText.'.supply_121')]; //施工地點
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_105')]; //申請區間
        if(in_array($type, [2,3]) && $aproc == 'O')  $heads[] = ['title'=>Lang::get($this->langText.'.supply_120')]; //核准區間
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_106')]; //人數/車輛數
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_52')]; //進度
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_107')]; //審查部門
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_46')]; //審查人
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_84')]; //審查時間

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->apply_door_kind_name; //
                $kindColor    = isset($kindColorAry[$value->apply_door_kind]) ? $kindColorAry[$value->apply_door_kind] : 1; //
                $name2        = ($value->apply_user == $value->charge_user) ? $value->charge_user : $value->supply.' / '.$value->apply_user; //
                $name3        = substr($value->apply_stamp,0,19); //
                $name4        = $value->project_no.' '.$value->project.'/'.HtmlLib::Color($value->supply,'',1); //

                $name_wp      = $value->work_place;
                $name6        = ($value->sdate == $value->edate)? $value->sdate : ($value->sdate.' - '.$value->edate); //
                $name_allow   = ($value->sdate_allow == $value->edate_allow)? $value->sdate_allow : ($value->sdate_allow.' - '.$value->edate_allow); //

                $name7        = $value->store; //
                $name8        = $value->apply_amt; //

                $name10       = ($value->isActive == 'N')? (isset($overAry[$value->isActive])? HtmlLib::Color($overAry[$value->isActive],'',1) : '') : $value->aproc_name; //
                $aprocColor   = ($value->isActive == 'N')? 5 : (isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 1); //

                $name11       = $value->charge_dept_name; //
                $name12       = $value->charge_user; //
                $name13       = substr($value->charge_stamp,0,19); //

                $isCharge     = ($value->isActive == 'Y')? $isCharge : 0;
                //按鈕
                $btnRoute     = ($isCharge)? $this->hrefMainDetail : $this->hrefMainDetail2;
                $btnName      = ($isCharge)? Lang::get('sys_btn.btn_21') : Lang::get('sys_btn.btn_30');
                $btn          = HtmlLib::btn(SHCSLib::url($btnRoute,$id),$btnName,1); //按鈕


                if($type == 1){
                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1,'label'=>$kindColor],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '7'=>[ 'name'=> $name7],
                        '6'=>[ 'name'=> $name6],
                        '8'=>[ 'name'=> $name8],
                        '10'=>[ 'name'=> $name10,'label'=>$aprocColor],
                        '11'=>[ 'name'=> $name11],
                        '12'=>[ 'name'=> $name12],
                        '13'=>[ 'name'=> $name13],
                        '99'=>[ 'name'=> $btn ]
                    ];
                }else if($type == 2){
                    if($aproc == 'O'){
                        $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1,'label'=>$kindColor],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '7'=>[ 'name'=> $name7],
                            '6'=>[ 'name'=> $name6],
                            '14'=>['name'=> $name_allow],
                            '8'=>[ 'name'=> $name8],
                            '10'=>[ 'name'=> $name10,'label'=>$aprocColor],
                            '11'=>[ 'name'=> $name11],
                            '12'=>[ 'name'=> $name12],
                            '13'=>[ 'name'=> $name13],
                            '99'=>[ 'name'=> $btn ]
                        ];
                    }else{
                        $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1,'label'=>$kindColor],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '7'=>[ 'name'=> $name7],
                            '6'=>[ 'name'=> $name6],
                            '8'=>[ 'name'=> $name8],
                            '10'=>[ 'name'=> $name10,'label'=>$aprocColor],
                            '11'=>[ 'name'=> $name11],
                            '12'=>[ 'name'=> $name12],
                            '13'=>[ 'name'=> $name13],
                            '99'=>[ 'name'=> $btn ]
                        ];
                    }
                }else{
                    if($aproc == 'O'){
                        $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1,'label'=>$kindColor],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '7'=>[ 'name'=> $name7],
                            '9'=>[ 'name'=> $name_wp],
                            '6'=>[ 'name'=> $name6],
                            '14'=>['name'=> $name_allow],
                            '8'=>[ 'name'=> $name8],
                            '10'=>[ 'name'=> $name10,'label'=>$aprocColor],
                            '11'=>[ 'name'=> $name11],
                            '12'=>[ 'name'=> $name12],
                            '13'=>[ 'name'=> $name13],
                            '99'=>[ 'name'=> $btn ]
                        ];
                    }else{
                        $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1,'label'=>$kindColor],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '7'=>[ 'name'=> $name7],
                            '9'=>[ 'name'=> $name_wp],
                            '6'=>[ 'name'=> $name6],
                            '8'=>[ 'name'=> $name8],
                            '10'=>[ 'name'=> $name10,'label'=>$aprocColor],
                            '11'=>[ 'name'=> $name11],
                            '12'=>[ 'name'=> $name12],
                            '13'=>[ 'name'=> $name13],
                            '99'=>[ 'name'=> $btn ]
                        ];
                    }
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
                    $( "#type" ).change(function() {
                        var kid = $("#type").val();
                        chgAproc(kid)
                    });
                } );
                function chgAproc(kid)
                {
                    $.ajax({
                          type:"GET",
                          url: "'.url('/findSupplyRpAproc').'",  
                          data: { type: 1, kid : kid },
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#aproc option").remove();
                             $.each(result, function(key, val) {
                                $("#aproc").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             }); 
                          },
                          error: function(result){
                          }
                    });
                }
                ';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * 單筆資料 新增
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js   = $contents = '';
        $id   = SHCSLib::decode($urlid);
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageEditTitle; //table header

        //資料內容
        $getData    = $this->getData($id);

        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $A1         = $getData->apply_user; //
            $A2         = $getData->apply_stamp; //
            $A3         = $getData->aproc_name; //
            $A4         = $getData->apply_door_kind_name; //
            $A5         = $getData->apply_memo; //
            $A6         = $getData->e_project_id; //
            $A7         = $getData->supply; //
            $A8         = e_project::getStatus($A6); //

            //到期日
            $A10        = $getData->project_no.' '.$getData->project.'(<b>'.$A8.'</b>)'; //
            $A11        = $getData->apply_door_kind; //
            $A14        = $getData->sdate; //
            $A15        = $getData->edate; //
            $A17        = $getData->charge_dept_name; //
            $A18        = $getData->store; //
            $A19        = $getData->b_supply_id; //
            $A20        = $getData->b_factory_id; //
            $A22        = substr($getData->stime,0,5); //
            $A23        = substr($getData->etime,0,5); //
            $wp         = $getData->work_place;


            $memberAry = $this->getApiSupplyRPDoor1DetailList($getData->id);

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //類型
        $html = $A4;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_2'),1);
        //申請人
        $html = HtmlLib::Color($A7,'',1) .' / ' .$A1;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A2;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = HtmlLib::Color($A3,'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));
        //審查事由
        $html = HtmlLib::Color($A17,'blue',1);
        $form->add('nameT2', $html,Lang::get('sys_supply.supply_107'),1);

        //工程案件
        $html = $A10;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_49'));
        //申請廠區
        $html = $A18;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_109'));

        //施工地點
        if($A11 == 3){
            $html = $wp;
            $form->add('nameT2', $html,Lang::get($this->langText.'.supply_121'));
        }
        //開始日期
        $html = $A14;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_16'));
        if($A11 != 1)
        {
            //開始時間
            $html = $A22;
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_112'));
            //結束日期
            $html = $A15;
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_17'));
            //結束時間
            $html = $A23;
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_113'));
        }
        //申請事由
        $html = HtmlLib::Color($A5,'blue',1);
        $form->add('nameT2', $html,Lang::get('sys_supply.supply_108'));

        //工程案件之成員
        $table = new TableLib();
        $heads = $tBody = [];
        //標題
        $heads[] = ['title'=>Lang::get('sys_supply.supply_19')]; //成員
        $heads[] = ['title'=>Lang::get('sys_supply.supply_37')]; //說明

        $table->addHead($heads,0);
        if(count($memberAry))
        {
            foreach($memberAry as $key => $value)
            {
                $name2        = $value['user']; //
                $name3        = $value['job_kind']; //
                $user_id      = ($value['b_cust_id'])? $value['b_cust_id'] : $value['b_car_id'];
                $name4        = b_supply_rp_door1::isAliveExist($A19,$A11,$A20,$user_id); //
                $name4        = $name4 ? ('，'.HtmlLib::Color($name4,'red',1)) : '';
                $tBody[] = [
                            '1'=>[ 'name'=> $name2],
                            '2'=>[ 'name'=> HtmlLib::Color($name3,'red').$name4],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $form->add('nameT1', $table->output(),Lang::get($this->langText.'.supply_30'));
        unset($table);

        if($A11 != 1)
        {

            $html = $form->date('sdate',$A14);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_116'),1);
            $html = $form->time('stime',$A22);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_117'),1);
            //結束日期
            $html = $form->date('edate',$A15);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_118'),1);
            //結束時間
            $html = $form->time('etime',$A23);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_119'),1);
        }


        $form->addHr();
        $html = $form->textarea('memo', '');
        $form->add('memo', $html, Lang::get('sys_supply.supply_50'));

        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY').'&nbsp;&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN').'&nbsp;&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,1));
        $contents = $content->output();
        //-------------------------------------------//
        //  View -> JavaScript
        //-------------------------------------------//
        $js = '$(function () {
            // $("form").submit(function() {
            //           $(this).find("input[type=\'submit\']").prop("disabled",true);
            //         });
        
            $("#sdate,#edate").datepicker({
                format: "yyyy-mm-dd",
                changeYear: true, 
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
     * 單筆資料 新增
     */
    public function show2(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js   = $contents = '';
        $id   = SHCSLib::decode($urlid);
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageEditTitle; //table header

        //資料內容
        $getData    = $this->getData($id);

        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $A1         = $getData->apply_user; //
            $A2         = substr($getData->apply_stamp,0,19); //
            $A3         = $getData->aproc_name; //
            $A4         = $getData->apply_door_kind_name; //
            $A5         = $getData->apply_memo; //
            $A6         = $getData->e_project_id; //
            $A7         = $getData->supply; //
            $A8         = $getData->charge_user; //
            $A9         = substr($getData->charge_stamp,0,19); //


            $A10        = $getData->project_no.' '.$getData->project; //
            $A11        = $getData->apply_door_kind; //
            $A14        = $getData->sdate; //
            $A15        = $getData->edate; //
            $A16        = $getData->charge_memo; //
            $A17        = $getData->charge_dept_name; //
            $A18        = $getData->store; //
            $overAry    = SHCSLib::getCode('DATE_OVER');
            $A20        = isset($overAry[$getData->isActive])? HtmlLib::Color($overAry[$getData->isActive],'red',1) : ''; //
            $A21        = $getData->isActive == 'N' ? '【'.$A20.'】' : '';
            $A22        = substr($getData->stime,0,5); //
            $A23        = substr($getData->etime,0,5); //
            $wp         = $getData->work_place;
            $sdate_a    = $getData->sdate_allow;
            $stime_a    = substr($getData->stime_allow,0,5);
            $edate_a    = $getData->edate_allow;
            $etime_a    = substr($getData->etime_allow,0,5);
            $aproc      = $getData->aproc;


            $aprocAry   = SHCSLib::getCode('ENGINEERING_APROC');
            $aproc_name = isset($aprocAry[$getData->project_aproc])? $aprocAry[$getData->project_aproc] : '';
            $A25        = \Lang::get('sys_engineering.engineering_165',['name'=>$aproc_name,'edate'=>$getData->project_edate]);
            $A10       .= ' (<b>'.$A25.'</b>)';
            $memberAry = $this->getApiSupplyRPDoor1DetailList($getData->id);

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //類型
        $html = $A4;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_2'),1);
        //申請人
        $html = HtmlLib::Color($A7,'',1) .' / ' .$A1;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A2;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = HtmlLib::Color($A3,'red',1).$A21;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));
        //審查人
        $html = HtmlLib::Color($A17,'',1) .' / ' .$A8;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_46'));
        //審查時間
        $html = $A9;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_47'));
        //審查事由
        $html = HtmlLib::Color($A16,'blue',1);
        $form->add('nameT2', $html,Lang::get('sys_supply.supply_50'));

        //工程案件
        $html = $A10;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_49'));
        //申請廠區
        $html = $A18;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_109'));
        //施工地點
        if($A11 == 3){
            $html = $wp;
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_121'),1);
        }
        //開始日期
        $html = $A14;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_16'));
        if($A11 != 1)
        {
            //開始時間
            $html = $A22;
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_112'));
            //結束日期
            $html = $A15;
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_17'));
            //結束時間
            $html = $A23;
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_113'));
            //核准通過
            if($aproc == 'O'){
                $html = $sdate_a;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_116'),1);
                $html = $stime_a;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_117'),1);
                $html = $edate_a;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_118'),1);
                $html = $etime_a;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_11'),1);
            }
        }
        //申請事由
        $html = HtmlLib::Color($A5,'blue',1);
        $form->add('nameT2', $html,Lang::get('sys_supply.supply_108'),1);

        //工程案件之成員
        $table = new TableLib();
        $heads = $tBody = [];
        //標題
        $heads[] = ['title'=>Lang::get('sys_supply.supply_19')]; //成員
        $heads[] = ['title'=>Lang::get('sys_supply.supply_37')]; //說明

        $table->addHead($heads,0);
        if(count($memberAry))
        {
            foreach($memberAry as $key => $value)
            {
                $name2        = $value['user']; //
                $name3        = $value['job_kind']; //

                $tBody[] = [
                    '1'=>[ 'name'=> $name2],
                    '2'=>[ 'name'=> HtmlLib::Color($name3,'red',1)],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $form->add('nameT1', $table->output(),Lang::get($this->langText.'.supply_30'));
        unset($table);

        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $form->boxFoot($submitDiv);

        $out = $form->output();
        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,1));
        $contents = $content->output();
        //-------------------------------------------//
        //  View -> JavaScript
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
        $submitBtn  = $request->submitBtn;
        $sdate      = $request->sdate;
        $stime      = $request->stime;
        $edate      = $request->edate;
        $etime      = $request->etime;

        $rid   = SHCSLib::decode($request->id);
        $getData    = $this->getData($rid);

        $start_time = strtotime($sdate.$stime);
        $end_time = strtotime($edate.$etime);

        $today          = date('Y-m-d');
        $todayStamp     = strtotime($today);
        $tomorrow       = SHCSLib::addDay(1);
        $now            = time();
        $apply_max_time = strtotime($today.' 16:00:00');
        $work_place = $request->work_place;

        //資料不齊全
        if( !$request->id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        
        $this->getBcustParam();
        $id   = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;

        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        if($request->has('agreeN') && !$request->memo){
            return \Redirect::back()->withErrors(Lang::get('sys_supply.supply_2001'))->withInput();
        }

        $upAry['aproc']             = ($request->has('agreeN'))? 'C' : 'O';
        $upAry['charge_memo']       = $request->memo;

        //核准時間
        $upAry['sdate_allow'] = $sdate;
        $upAry['stime_allow'] = $stime;
        $upAry['edate_allow'] = $edate;
        $upAry['etime_allow'] = $etime;

//        dd($upAry);
        //新增
        if($isNew)
        {
            //資料不齊全
            if( !$request->id)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10103'))
                    ->withInput();
            }
            //請選擇【工程案件】
            if(!$request->e_project_id)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1061'))
                    ->withInput();
            }
            //請選擇【申請廠區】
            if(!$request->b_factory_id)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1062',['name1'=>Lang::get('sys_supply.supply_109')]))
                    ->withInput();
            }

            //申請加班 請選擇施工地點
            if($request->type_id == 3){
                if(!$work_place){
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_supply.supply_1063'))
                        ->withInput();
                }
            }

            //請選擇【申請事由】
            if(!$request->memo)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1065'))
                    ->withInput();
            }
            elseif($request->sdate && !CheckLib::isDate($request->sdate))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1066',['name1'=>Lang::get('sys_supply.supply_16')]))
                    ->withInput();
            }
            elseif($request->sdate && strtotime($request->sdate) < $todayStamp)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1067',['name1'=>Lang::get('sys_supply.supply_16')]))
                    ->withInput();
            }
            elseif($request->edate && !CheckLib::isDate($request->edate))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1066',['name1'=>Lang::get('sys_supply.supply_17')]))
                    ->withInput();
            }
            elseif($request->edate && strtotime($request->edate) < $todayStamp)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1067',['name1'=>Lang::get('sys_supply.supply_17')]))
                    ->withInput();
            }
            elseif($request->edate && strtotime($request->edate) < strtotime($request->sdate))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1068'))
                    ->withInput();
            }
            elseif(!isset($request->member) || !count($request->member))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1064'))
                    ->withInput();
            }

            list($project_aproc,$project_edate) = e_project::getProjectList1($request->e_project_id);
            $charge_dept = ($request->type_id == 1)? e_project::getChargeDept($request->e_project_id) : sys_param::getParam('ROOT_CHARGE_DEPT');

            $upAry['b_supply_id']       = $request->b_supply_id;
            $upAry['e_project_id']      = $request->e_project_id;
            $upAry['project_aproc']     = $project_aproc;
            $upAry['project_edate']     = $project_edate;
            $upAry['b_factory_id']      = $request->b_factory_id;
            $upAry['apply_door_kind']   = $request->type_id;
            $upAry['sdate']             = $request->sdate;
            $edate = ($request->type_id == 1)? $request->sdate : $request->edate;
            $upAry['edate']             = $edate;
            $upAry['stime']             = $request->stime;
            $upAry['etime']             = $request->etime;
            $upAry['charge_dept']       = $charge_dept;
            $upAry['apply_memo']        = $request->memo;
            $upAry['member']            = $request->member;
            $upAry['car']               = $request->car;
            $upAry['work_place']        = $request->work_place;

            $ret = $this->createSupplyRPDoor1($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSupplyRPDoor1($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_rp_door1',$id);

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
    public function create(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();

        $isRootDept = $this->isRootDept;
        if(!$isRootDept) {
            return \Redirect::to($this->hrefMain)
                ->withErrors(Lang::get('sys_base.base_auth'))
                ->withInput();
        }


        //參數
        $js = $contents = '';
        $supply_id  = -1;

        $projectAry     = e_project::getActiveProjectSelect();

        $typeAry        = SHCSLib::getCode('RP_DOOR_KIND1');
        unset($typeAry[1]);

        $tomorrow       = SHCSLib::addDay(1);
        $memberAry      = [];
        $type_id        = $request->type_id;
        $e_project_id   = $request->e_project_id;
        $b_factory_id   = $request->b_factory_id;
        $sdate          = $request->sdate ? $request->sdate : $tomorrow;
        $edate          = $request->edate ? $request->edate : $tomorrow;
        $stime          = $request->stime ? $request->stime : '08:00';
        $etime          = $request->etime ? $request->etime : '18:00';
        $memo           = $request->memo ? $request->memo : '';

        $storeAry       = b_factory::getSelect();
        $supply_name    = '';

        if($type_id && $e_project_id && $b_factory_id)
        {
            $supply_id = e_project::getSupply($e_project_id);
            $supply_name = b_supply::getName($supply_id);
        }

        if(in_array($type_id,[1]))
        {
            $memberAry = view_door_supply_whitelist_pass::getSelect($e_project_id,2,0);
        }
        elseif(in_array($type_id,[2]))
        {
            $memberAry = view_door_car::getSelectByProject($supply_id,1,0,$e_project_id);
        }elseif(in_array($type_id, [3]))
        {
            $memberAry = view_door_supply_whitelist_pass::getSelect($e_project_id,2,0);
            $carAry = view_door_car::getSelectByProject($supply_id,1,0,$e_project_id);
        }

        //routerPost2   contractorrpdoor1Create
        //routerPost    postContractorrpdoor1
        $router = ($type_id && $e_project_id && $b_factory_id)? $this->routerPost : $this->routerPost2;
        $btn    = ($type_id && $e_project_id && $b_factory_id)? 'btn_7' : 'btn_37';

        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($router,-1),'POST',1,TRUE);
        //--- 帳號區 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10949'),3);
        $form->addHtml( $html );

        //未選擇 類型 工程案 申請廠區
        if(!$type_id || !$e_project_id || !$b_factory_id)
        {
            //類型
            $html = $form->select('type_id',$typeAry,1);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_2'),1);
            //工程案件
            $html = $form->select('e_project_id',$projectAry);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_49'),1);
            //廠區
            $html = $form->select('b_factory_id',$storeAry);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_109'),1);
            //說明
            $html = HtmlLib::Color(Lang::get('sys_supply.supply_1100'),'red',1);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_37'),1);
        } else {
            //類型
            $html = isset($typeAry[$type_id])? $typeAry[$type_id] : '';
            $html .= $form->hidden('type_id',$type_id);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_2'),1);

            //申請廠商
            $html = $supply_name;
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_123'),1);

            //工程案件
            $html = isset($projectAry[$e_project_id])? $projectAry[$e_project_id] : '';
            $html .= $form->hidden('e_project_id',$e_project_id);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_49'),1);
            //廠區
            $html  = isset($storeAry[$b_factory_id])? $storeAry[$b_factory_id] : '';
            $html .= $form->hidden('b_factory_id',$b_factory_id);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_109'),1);
            //說明
            $html = HtmlLib::Color(Lang::get('sys_supply.supply_1056'),'red',1);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_37'),1);

            if($type_id == 3){
                $html = $form->text('work_place','',6);
                $form->add('nameT2', $html,Lang::get('sys_supply.supply_121'),1);
            }
            //開始日期
            $html = $form->date('sdate',$sdate);
            //臨時入場申請單
            if($type_id == 1)
            {
                $html .= $form->hidden('edate','');
                $html .= $form->hidden('stime','00:00:00');
                $html .= $form->hidden('etime','00:00:00');
            }
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_16'),1);

            //車輛過夜申請單
            //加班申請
            if(in_array($type_id,[2,3]))
            {
                //開始時間
                $html = $form->time('stime',$stime);
                $form->add('nameT2', $html,Lang::get('sys_supply.supply_112'),1);
                //結束日期
                $html = $form->date('edate',$edate);
                $form->add('nameT2', $html,Lang::get('sys_supply.supply_17'),1);
                //結束時間
                $html = $form->time('etime',$etime);
                $form->add('nameT2', $html,Lang::get('sys_supply.supply_113'),1);
            }
            //工程案件之成員
            $table = new TableLib();
            $heads = $tBody = [];
            //標題
            $type_title = ($type_id == 3) ? 'supply_19' : 'supply_41';
            $heads[] = ['title'=>Lang::get('sys_supply.supply_122')]; //功能
            $heads[] = ['title'=>Lang::get('sys_supply.'.$type_title)]; //成員
            $heads[] = ['title'=>Lang::get('sys_supply.supply_37')]; //說明

            $table->addHead($heads,0);
            if(count($memberAry))
            {
                foreach($memberAry as $key => $value)
                {
                    $name1        = $form->checkbox('member[]',$key,'','store_box'); //
                    $name2        = $value; //
                    $name3        = b_supply_rp_door1::isAliveExist($supply_id,$type_id,$b_factory_id,$key); //

                    $tBody[] = ['0'=>[ 'name'=> $name1],
                        '1'=>[ 'name'=> $name2],
                        '2'=>[ 'name'=> HtmlLib::Color($name3,'red',1)],
                    ];
                }
                $table->addBody($tBody);
            }
            //輸出
            $checkAllBtn = HtmlLib::btn('#',Lang::get('sys_btn.btn_77'),2,'checkAllBtn','','checkAll()');
            $form->add('nameT1', $checkAllBtn.$table->output(),Lang::get($this->langText.'.supply_30'));
            unset($table);

            //加班申請單 新增車輛選擇
            if(in_array($type_id, [3])){
                $table = new TableLib('', 'table2');
                $heads = $tBody = [];
                //標題
                $heads[] = ['title'=>Lang::get('sys_supply.supply_122')]; //功能
                $heads[] = ['title'=>Lang::get('sys_supply.supply_41')]; //車牌
                $heads[] = ['title'=>Lang::get('sys_supply.supply_37')]; //說明

                $table->addHead($heads,0);
                if(count($carAry))
                {
                    foreach($carAry as $key => $value)
                    {
                        $name1        = $form->checkbox('car[]',$key,'','store_box_car');
                        $name2        = $value; //
                        $name3        = b_supply_rp_door1::isAliveExist($supply_id,2,$b_factory_id,$key); //kind2 車輛

                        $tBody[] = ['0'=>[ 'name'=> $name1],
                            '1'=>[ 'name'=> $name2],
                            '2'=>[ 'name'=> HtmlLib::Color($name3,'red',1)],
                        ];
                    }
                    $table->addBody($tBody);
                }

                $checkAllBtn = HtmlLib::btn('#',Lang::get('sys_btn.btn_77'),2,'checkAllBtnCar','','checkAllCar()');
                $form->add('nameT1', $checkAllBtn.$table->output(),Lang::get($this->langText.'.supply_30'));
                unset($table);
            }
            //申請事由
            $html = $form->textarea('memo','','',$memo);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_108'),1);

        }

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.'.$btn),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);
        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('b_supply_id',$supply_id);

        $form->boxFoot($submitDiv);
        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,1));
        $contents = $content->output();
        //-------------------------------------------//
        //  View -> JavaScript
        //-------------------------------------------//
        $js = '$(function () {
            $("form").submit(function(e) {
                $(this).find("input[type=\'submit\']").prop("disabled",true);
            });
        
            $("#sdate,#edate").datepicker({
                format: "yyyy-mm-dd",
                changeYear: true, 
                language: "zh-TW"
            });
            
            $("#stime,#etime").timepicker({
                showMeridian: false,
                defaultTime: false,
                timeFormat: "HH:mm"
            })
        });
        
        var clicked = false;
        function checkAll()
        {
            $(".store_box").prop("checked", !clicked);
            clicked = !clicked;
            btn = clicked ? "'.Lang::get('sys_btn.btn_78').'" : "'.Lang::get('sys_btn.btn_77').'";
            $("#checkAllBtn").html(btn);
            return false;
        }
        
        //加班申請單 車輛table全選
        var clicked_car = false;
        function checkAllCar()
        {
            $(".store_box_car").prop("checked", !clicked_car);
            clicked_car = !clicked_car;
            btn = clicked_car ? "'.Lang::get('sys_btn.btn_78').'" : "'.Lang::get('sys_btn.btn_77').'";
            $("#checkAllBtnCar").html(btn);
            return false;
        }
        ';
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
