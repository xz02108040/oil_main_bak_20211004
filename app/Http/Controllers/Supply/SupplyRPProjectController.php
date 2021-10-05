<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\EngineeringMemberIdentityTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyEngineeringIdentityTrait;
use App\Http\Traits\Supply\SupplyMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyMemberLicenseTrait;
use App\Http\Traits\Supply\SupplyMemberTrait;
use App\Http\Traits\Supply\SupplyRPMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyRPMemberLicenseTrait;
use App\Http\Traits\Supply\SupplyRPMemberTrait;
use App\Http\Traits\Supply\SupplyRPProjectLicenseTrait;
use App\Http\Traits\Supply\SupplyRPProjectTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_project_s;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_ei;
use App\Model\View\view_user;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;

class SupplyRPProjectController extends Controller
{
    use SupplyRPProjectTrait,SupplyRPMemberIdentityTrait,SupplyRPMemberLicenseTrait,SessTraits;
    use SupplyMemberIdentityTrait,SupplyMemberLicenseTrait,DoorTrait;
    use EngineeringMemberTrait,SupplyEngineeringIdentityTrait;
    use SupplyRPProjectLicenseTrait,EngineeringMemberIdentityTrait;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPProjectController
    |--------------------------------------------------------------------------
    |
    | 承攬商成員加入工程案件之申請單
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
        $this->hrefMain         = 'rp_contractorproject';
        $this->hrefMember       = 'contractormember';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'rp_contractorproject/';
        $this->hrefMainDetail2  = 'rp_contractorproject2/';
        $this->hrefMainDetail3  = 'contractorrpprojectapply1/';
        $this->hrefMainNew      = 'new_rp_contractorproject';
        $this->routerPost       = 'postContractorrpproject';
        $this->routerPost2      = 'contractorrpprojectapplyList';

        $this->pageTitleMain    = Lang::get($this->langText.'.title15');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list15');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new15');//新增
        $this->pageNewTitle2    = Lang::get($this->langText.'.identity7');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit15');//編輯

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
        //Session Forget
        Session::forget($this->hrefMain.'.identityApplyAry');
        Session::forget($this->hrefMain.'.backPage');
        Session::forget($this->hrefMain.'.isEdit');
        //允許管理的工程案件
        $allowProjectAry= $this->allowProjectAry;
        //參數
        $no = 0;
        $out = $js = $supply = '';
        $aprocColorAry  = ['A'=>1,'P'=>4,'R'=>4,'O'=>2,'C'=>5];
        $aprocAry       = SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC');
        //進度
        $aproc    = ($request->aproc)? $request->aproc : '';
        if($aproc)
        {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        } else {
            $aproc = Session::get($this->hrefMain.'.search.aproc','A');
        }
        //承攬商
        $pid      = SHCSLib::decode($request->pid);
        if($pid)
        {
            $supply= b_supply::getName($pid);
            Session::put($this->hrefMain.'.search.pid',$pid);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew.$request->pid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$pid)
        {
            $listAry = $this->getApiSupplyRPProjectMainList($aproc,$allowProjectAry);

        } else {
            $listAry = $this->getApiSupplyRPProjectList($pid,$aproc,$allowProjectAry);
            Session::put($this->hrefMain.'.Record',$listAry);
        }
        //dd($listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($pid)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        }
        $form->addHr();
        //搜尋
        $html = $form->select('aproc',$aprocAry,$aproc,2,Lang::get($this->langText.'.supply_52'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'No'];
        if(!$pid)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_12')]; //承攬商
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_39')]; //件數
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_29')]; //申請人
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_28')]; //申請時間
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_52')]; //進度
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_30')]; //成員
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_49')]; //工程案件
        }


        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                if(!$pid)
                {
                    $id           = $value->b_supply_id;
                    $name1        = $value->b_supply; //
                    $name2        = $value->amt; //
                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($hrefMain,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->id;
                    $name1        = $value->b_supply; //
                    $name2        = $value->apply_name; //
                    $name3        = substr($value->apply_stamp,0,19); //
                    $name4        = $value->aproc_name; //

                    $name10       = HtmlLib::Color($value->user,'red',1); //
                    $name11       = $value->project; //
                    $aprocColor   = isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 1; //

                    $isCharge     = ((!$this->isRootDept && $aproc == 'A')? 1 : 0);

                    $btnRoute     = ($isCharge)? $this->hrefMainDetail : $this->hrefMainDetail2;
                    $btnName      = ($isCharge)? Lang::get('sys_btn.btn_21') : Lang::get('sys_btn.btn_60');
                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($btnRoute,$id),$btnName,4); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4,'label'=>$aprocColor],
                        '10'=>[ 'name'=> $name10],
                        '11'=>[ 'name'=> $name11],
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

    /**
     * 單筆資料 - 審查
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $pid  = 0;
        $js   = $contents = '';
        $id   = SHCSLib::decode($urlid);
        Session::forget($this->hrefMain.'.identityApplyAry');
        Session::forget($this->hrefMain.'.auth_id');
        Session::put($this->hrefMain.'.backPage',$this->hrefMainDetail.$urlid);
        Session::put($this->hrefMain.'.isEdit','N');
        $applyAry       = SHCSLib::getCode('APPLY');
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
            $pid        = $getData->b_supply_id;

            $A1         = $getData->apply_name; //
            $A2         = substr($getData->apply_stamp,0,19); //
            $A3         = $getData->aproc_name; //
            $A5         = $getData->b_cust_id; //
            $A6         = $getData->e_project_id; //
            $A7         = $getData->user; //
            $A8         = $getData->project; //

            //SESSION：申請工程身份陣列
            //取得工程身份＆證照申請
            $identityApplyAry = $this->getApiSupplyRPProjectIdentityAllList($pid,$id,$A5,$A6);
            //dd($identityApplyAry);
            //放入Session
            Session::put($this->hrefMain.'.identityApplyAry',$identityApplyAry);
            Session::put($this->hrefMain.'.auth_id',$id);

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $projectIdentityAry = e_project_l::getSelect($A6);
            $isExist    = e_project_s::isExist($A6, $A5 );
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //申請人
        $html = $A1;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A2;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = $A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //--- 帳號區 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10901'),3);
        $form->addHtml( $html );
        //成員
        $html = $A7;
        $html.= $form->hidden('b_cust_id',$A5);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_19'),1);
        //工程案件
        $html = $A8;
        $html.= $form->hidden('e_project_id',$A6);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_49'),1);
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 尿檢 ---//
        $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.supply_101'));
        $form->addHtml( $html );
        //是否需要尿檢
        $html = $form->checkbox('isUT','Y');
        $form->add('nameT2', $html,Lang::get('sys_base.base_10945'));
        //是否需要尿檢
        $html = HtmlLib::Color(Lang::get('sys_base.base_10946'),'red',1);
        $form->add('nameT2', $html,Lang::get('sys_base.base_10018'));
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 工程身分 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10932'),2);
        $form->addHtml( $html );

        //table
        $table = new TableLib($this->hrefMain);
        //標題
        $heads   = [];
        $heads[] = ['title'=>'No'];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_51')]; //工程身份
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_1020')]; //工程身份
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_58')]; //是否已申請
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_60')]; //所需執照

        $table->addHead($heads,0);
        if(count($identityApplyAry))
        {
            $no = 0;
            $tBody = [];
            foreach($identityApplyAry as $value)
            {
                $no++;
                $id           = $value['id'];
                $name1        = HtmlLib::Color($value['name'],'',1); //
                $name2        = isset($applyAry[$value['isApply']])? $applyAry[$value['isApply']] : ''; //
                $name3        = $value['licenseAllName']; //

                $isTag        = isset($projectIdentityAry[$value['id']])? Lang::get($this->langText.'.supply_59') : '';
                $name4        = '<span id="tag'.$id.'" class="showTag" style="color: blue;font-weight:bold;">'.$isTag.'</span>'; //


                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                    '1'=>[ 'name'=> $name1],
                    '4'=>[ 'name'=> $name4],
                    '2'=>[ 'name'=> $name2],
                    '3'=>[ 'name'=> $name3],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $form->addHtml($table->output());
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        $form->addHr();
        //審查事由
        $html = $form->textarea('charge_memo');
        $html.= HtmlLib::Color(Lang::get($this->langText.'.supply_1021'),'red',1);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_50'));
        //Submit
        $submitDiv = '';
        if(!$isExist)
        {
            //$submitDiv .= $form->submit(Lang::get('sys_btn.btn_38'),'4','editY').'&nbsp;';
            $submitDiv .= $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY','','chgSubmit("agreeY")').'&nbsp;';
        }
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN','','chgSubmit("agreeN")').'&nbsp;';
        //$submitDiv .= $form->submit(Lang::get('sys_btn.btn_40'),'4','Clear','','clearSession()').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('bc_type',3);
        $submitDiv.= $form->hidden('b_supply_id',$pid);
        $submitDiv.= $form->hidden('pid',$pid);
        $submitDiv.= $form->hidden('submitBtn','');
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
            
            $("form").submit(function() {
                $(this).find("input[type=\'submit\']").prop("disabled",true);
              });
            $("#birth,#edate,.datetype").datepicker({
                format: "yyyy-mm-dd",
                changeYear: true, 
                language: "zh-TW"
            });
            $("input[name=\'headImg\']").change(function() {
              $("#blah_div").hide();
              readURL(this);
            });
            function readURL(input) {
              if (input.files && input.files[0]) {
                var reader = new FileReader();
            
                reader.onload = function(e) {
                  $("#blah").attr("src", e.target.result);
                  $("#blah_div").show();
                }
            
                reader.readAsDataURL(input.files[0]);
              }
            }
        });
        function clearSession()
        {
            $("#submitBtn").val("Clear");
        }
        function chgSubmit(btnTitle)
        {
            $("#submitBtn").val(btnTitle);
        }
        ';
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
        //清除工程身份
        if($request->has('submitBtn') && $request->submitBtn == 'Clear')
        {
            Session::forget($this->hrefMain.'.identityApplyAry');
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10142'));
        }
        //資料不齊全
        if( !$request->id || !$request->b_cust_id || !$request->e_project_id )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        //已經存在該工程案件 重複
        elseif(($request->submitBtn == 'agreeY') && e_project_s::isExist($request->e_project_id, $request->b_cust_id ))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10933'))
                ->withInput();
        }
        elseif (($request->submitBtn == 'agreeN') && !$request->charge_memo)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1021'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id   = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $pid  = $request->pid;
            $menu = $this->pageTitleMain;

            if(!$pid && is_numeric($pid) && $pid > 0)
            {
                $msg = Lang::get($this->langText.'.supply_1000');
                return \Redirect::back()->withErrors($msg);
            }
            //
            $identityApplyAry = Session::get($this->hrefMain.'.identityApplyAry',[]);
            if(!count($identityApplyAry))
            {
                //查無工程身份
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.supply_1017'))
                    ->withInput();
            } else {
                $isApplyNum = 0;
                foreach ($identityApplyAry as $key => $val)
                {
                    if(isset($val['isOk']))
                    {
                        if($val['isOk'] == 'Y')
                        {
                            $isApplyNum++;
                            //調整成新增陣列
                            $licenseAry = [];
                            foreach ($val['license'] as $val2)
                            {
                                $lid = isset($val2['b_supply_rp_member_l_id'])? $val2['b_supply_rp_member_l_id'] : 0;
                                $licenseAry[$lid] = $val2;
                            }
                            $identityApplyAry[$key]['license'] = $licenseAry;
                        } else {
                            //申請工程身份，不完整
                            return \Redirect::back()
                                ->withErrors(Lang::get($this->langText.'.supply_1019',['name'=>$val['name']]))
                                ->withInput();
                        }
                    } else {
                        unset($identityApplyAry[$key]);
                    }
                }
                if(($request->submitBtn == 'agreeY') && !$isApplyNum)
                {
//                    //查無要申請的工程身份，請至少填寫「施工人員」所需資料
//                    return \Redirect::back()
//                        ->withErrors(Lang::get($this->langText.'.supply_1018'))
//                        ->withInput();
                }
//                dd($identityApplyAry);
            }
        }
        $isAgree = 0;
        $isNew   = ($id > 0)? 0 : 1;
        $action  = ($isNew)? 1 : 2;
        $isOK    = 0;


        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['b_cust_id']         = $request->b_cust_id;
        $upAry['e_project_id']      = $request->e_project_id;
        $upAry['charge_memo']       = $request->charge_memo;

        $upAry['b_supply_id']       = $pid;

        //工程身份＋執照
        $upAry['identity']          = $identityApplyAry;

        if(($request->submitBtn == 'agreeY'))
        {
            $isAgree = $isOK = 1;
            $upAry['aproc']                 = 'O';
            $upAry['isUT']                  = ($request->isUT == 'Y')? 'Y' : 'C';
            $upAry['job_kind']              = 1;
            $upAry['b_supply_rp_project_id']= $id;
        }
        if(($request->submitBtn == 'agreeN'))
        {
            $upAry['aproc'] = 'C';
        }
        //新增
        if($isNew)
        {
            //$ret = $this->createSupplyRPMember($upAry,$this->b_cust_id);
            $ret  = 0;
            $id  = $ret;
        } else {
            //修改
            //1.承攬商_申請_加入工程案件
            $ret1 = $this->setSupplyRPProject($id,$upAry,$this->b_cust_id); 
            // 2.承攬商_成員_擁有的工程身份
            $ret2 = 0;//$this->setSupplyRPMemberLicenseGroup($upAry['identity'],$this->b_cust_id);
            // 3.承攬商_申請_加入工程案件之工程身分
            $ret3 = $this->updateSupplyRPProjectMemberLicense($upAry,$this->b_cust_id);
            if($ret1 || $ret2 || $ret3)
            {
                if( $ret1 <= 0 && $ret2 <= 0 && $ret3 <= 0)
                {
                    $ret = -1;
                } else {
                    $ret = 1;
                }
            }
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
                if($isOK)
                {
                    //新增 教育訓練資格 通過名單紀錄
                    //$this->createCoursePass();
                }
                //動作紀錄
                if($isAgree)
                {
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'e_project_s',$id);
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_ei',$id);
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_l',$id);
                }
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_rp_project',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_rp_member_ei',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_rp_member_l',$id);

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
     * 單筆資料 - 查看
     */
    public function show2(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        Session::put($this->hrefMain.'.backPage',$this->hrefMainDetail2.$urlid);
        Session::put($this->hrefMain.'.isEdit','N');
        //參數
        $pid  = 0;
        $js   = $contents = '';
        $id   = SHCSLib::decode($urlid);
        $applyAry       = SHCSLib::getCode('APPLY');
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
            $pid        = $getData->b_supply_id;

            $A1         = $getData->apply_name; //
            $A2         = $getData->apply_stamp; //
            $A3         = $getData->aproc_name; //
            $A5         = $getData->b_cust_id; //
            $A6         = $getData->e_project_id; //
            $A7         = $getData->user; //
            $A8         = $getData->project; //
            $A9         = $getData->charge_memo; //
            $A36        = e_project_s::getUTName($A6,$A5); //

            //SESSION：申請工程身份陣列
            //取得工程身份＆證照申請
            $identityApplyAry = $this->getApiSupplyRPProjectIdentityAllList($pid,$id,0,$A6);
            foreach ($identityApplyAry as $key => $val)
            {
                if(!isset($val['isOk']) || $val['isOk'] != 'Y') unset($identityApplyAry[$key]);
            }
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $projectAry = e_project::getSelect('P',$pid);
            $projectIdentityAry = e_project_l::getSelect($A6);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //申請人
        $html = $A1;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A2;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = $A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));

        //尿檢
        $html = HtmlLib::Color($A36,'red',1);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_101'));
        //審查事由
        $html = HtmlLib::Color($A9,'red',1);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_50'));
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));


        //--- 帳號區 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10901'),3);
        $form->addHtml( $html );
        //成員
        $html = $A7;
        $html.= $form->hidden('b_cust_id',$A5);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_19'),1);
        //工程案件
        $html = $A8;
        $html.= $form->hidden('e_project_id',$A6);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_49'),1);
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 工程身分 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10932'),2);
        $form->addHtml( $html );

        //table
        $table = new TableLib($this->hrefMain);
        //標題
        $heads   = [];
        $heads[] = ['title'=>'No'];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_51')]; //工程身份
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_1020')]; //工程身份
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_58')]; //是否已申請
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_60')]; //所需執照

        $table->addHead($heads,0);
        if(count($identityApplyAry))
        {
            $no = 0;
            $tBody = [];
            foreach($identityApplyAry as $value)
            {
                $no++;
                $id           = $value['id'];
                $name1        = HtmlLib::Color($value['name'],'',1); //
                $name2        = isset($applyAry[$value['isApply']])? $applyAry[$value['isApply']] : ''; //
                $name3        = $value['licenseAllName']; //

                $isTag        = isset($projectIdentityAry[$value['id']])? Lang::get($this->langText.'.supply_59') : '';
                $name4        = '<span id="tag'.$id.'" class="showTag" style="color: blue;font-weight:bold;">'.$isTag.'</span>'; //


                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                    '1'=>[ 'name'=> $name1],
                    '4'=>[ 'name'=> $name4],
                    '2'=>[ 'name'=> $name2],
                    '3'=>[ 'name'=> $name3],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $form->addHtml($table->output());
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);


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
     *  申請工程身份
     */
    protected function setIdentityLicense(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents = $name = $jsName = '';
        $identity_id = SHCSLib::decode($urlid);
        //取得：指定的工程案件之工程身份申請資料
        $listDataAry = Session::get($this->hrefMain.'.identityApplyAry.'.$identity_id,[]);
        //dd($listDataAry);

        //------ 檢查 「該筆工程身份」是否存在 ------
        if(!count($listDataAry) || !isset($listDataAry['id']))
        {
            $msg = Lang::get($this->langText.'.supply_1017');
            return \Redirect::back()->withErrors($msg);
        } else {
            $name       = isset($listDataAry['name'])?      $listDataAry['name'] : '';
            $licenseAry = isset($listDataAry['license'])?   $listDataAry['license'] : [];
        }

        //------ ＰＯＳＴ ------
        if($request->has('license_id'))
        {
            foreach ($request->license_id as $key => $val)
            {
                if(isset($licenseAry[$key]))
                {
                    $edate          = isset($val['edate'])? $val['edate'] : '';
                    $edate_type     = isset($val['edate_type'])? $val['edate_type'] : 'edate';
                    $license_code   = isset($val['license_code'])? $val['license_code'] : '';
                    $file1          = isset($val['file1'])? $val['file1'] : '';

                    $licenseAry[$key]['edate']      = $edate;
                    $licenseAry[$key]['license_code']      = $license_code;

                    if(!$edate)
                    {
                        //日期 ＆ 檔案 沒有上傳
                        return \Redirect::back()
                            ->withErrors(Lang::get($this->langText . '.supply_1016'))
                            ->withInput();
                    }
                    elseif(!CheckLib::isDate($edate))
                    {
                        //日期格式不正確
                        return \Redirect::back()
                            ->withErrors(Lang::get($this->langText.'.supply_1004'))
                            ->withInput();
                    }
                    elseif($edate_type == 'edate' && strtotime($edate) < strtotime(date('Y-m-d')))
                    {
                        //日期不可小於今日
                        return \Redirect::back()
                            ->withErrors(Lang::get($this->langText.'.supply_1006'))
                            ->withInput();
                    }
                    //檔案
                    if($file1)
                    {
                        $File       = $file1;
                        $extension  = $File->extension();
                        $filesize   = $File->getSize();
                        //dd([$File,$extension]);
                        //[錯誤]格式錯誤
                        if(!in_array(strtoupper($extension),['JPG','PNG','GIF','PDF','JPEG'])){
                            return \Redirect::back()
                                ->withErrors(Lang::get('sys_base.base_10119'))
                                ->withInput();
                        }elseif($filesize > $this->fileSizeLimit1){
                            return \Redirect::back()
                                ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                                ->withInput();
                        } else {
                            $fileN      = $extension;
                            $fileData   = file_get_contents($File);
                            $imgData    = 'data:image/' . $fileN . ';base64,' . base64_encode($fileData);

                            $licenseAry[$key]['file1']      = $fileData;
                            $licenseAry[$key]['file1N']     = $fileN;
                            $licenseAry[$key]['fileImg1']   = '<img src="'.$imgData.'" class="img-responsive" height="30%">';
                        }
                    }
                    //dd($licenseAry);
                }
            }
            //檢查 是否已經填寫完整
            $nameStr = '';
            $isApply = 'N';
            $max_cnt = count($licenseAry);
            $cal_cnt = 0;
            foreach ($licenseAry as $key => $val)
            {
                $edate = isset($val['edate'])? $val['edate'] : '';
                $file1 = isset($val['file1'])? $val['file1'] : '';
                if($edate || $file1)
                {
                    $cal_cnt++;
                    $isApply = 'R';
                }
                if($edate && $file1)
                {
                    $licenseAry[$key]['isOk'] = 'Y';
                    $color = 'blue';
                    $tag   = 'Ｏ';
                } else {
                    $color = 'red';
                    $tag   = 'Ｘ';
                }
                if(strlen($nameStr)) $nameStr .= '/';
                $nameStr .= HtmlLib::Color($tag.$val['license_name'],$color);
            }
            $isOk = ($max_cnt == $cal_cnt)? 'Y' : 'N';
            //回寫回ＳＥＳＳＩＯＮ
            Session::put($this->hrefMain.'.identityApplyAry.'.$identity_id.'.isApply',$isApply);
            Session::put($this->hrefMain.'.identityApplyAry.'.$identity_id.'.isOk',$isOk);
            Session::put($this->hrefMain.'.identityApplyAry.'.$identity_id.'.licenseAllName',$nameStr);
            Session::put($this->hrefMain.'.identityApplyAry.'.$identity_id.'.license',$licenseAry);

            //2019-08-31 根據寶月＆ken需求調整
            Session::flash('message',Lang::get('sys_base.base_10145'));
            return \Redirect::to(Session::get($this->hrefMain.'.backPage',$this->hrefMain));
        }
        //view元件參數
        $isEdit     = Session::get($this->hrefMain.'.isEdit','N');
        $hrefBack   = Session::get($this->hrefMain.'.backPage',$this->hrefMain);
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle2; //table header

        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost2,$urlid),'POST',1,TRUE);
        //工程身份
        $html = $name;
        $html.= $form->hidden('type_id',$identity_id);
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_51'),1);
        foreach ($licenseAry as $lid => $val)
        {
            list($show_name1,$show_name2,$show_name3,$show_name4,$show_name5,$edate_type) = e_license::getShowList($val['license_id']);
            $form->addHr();
            //證件
            $lname = $val['license_name'];
            $html  = HtmlLib::Color($lname,'blue',1);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_32'),1);
            //有效期限
            if($isEdit == 'Y')
            {
                $html  = $form->text('license_id['.$lid.'][license_code]',$val['license_code']);
                $html .= $form->hidden('license_id['.$lid.'][edate_type]',$edate_type);
            } else {
                $html  = $val['license_code'];
            }
            $form->add('nameT3', $html,$show_name1,1);
            //有效期限
            if($isEdit == 'Y')
            {
                $html  = $form->date('license_id['.$lid.'][edate]',$val['edate'],4,'','datetype');
                $html .= Lang::get($this->langText.'.supply_69');
            } else {
                $html  = $val['edate'];
            }
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_33'),1);
            //證明1－檔案1
            if($val['fileImg1'])
            {
                $html = ($val['fileImg1'])? $val['fileImg1'] : '';
            } else {
                $html = ($val['filePath1'])? Html::image($val['filePath1'],'',['class'=>'img-responsive','height'=>'30%']) : '';
            }
            if($isEdit == 'Y')
            {
                $html .= $form->file('license_id['.$lid.'][file1]');
                $html .= '<span id="blah_div'.$lid.'" style="display: none;"><img id="blah'.$lid.'" src="#" alt="" width="240" /></span>';
            }
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_34'),1);

            $jsName .= '$("input[name=\'license_id['.$lid.'][file1]\']").change(function() {
                          readURL(this,"#blah'.$lid.'","#blah_div'.$lid.'");
                          $("#blah_div1").hide();
                        });
                        ';
        }
        //Submit
        $submitDiv = '';
        if($isEdit == 'Y')
        {
            $submitDiv .= $form->submit(Lang::get('sys_btn.btn_11'),'1','agreeY').'&nbsp;';
        }
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);
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
            $("form").submit(function() {
                $(this).find("input[type=\'submit\']").prop("disabled",true);
            });
                    
            $(".datetype").datepicker({
                format: "yyyy-mm-dd",
                changeYear: true, 
                language: "zh-TW"
            });
            '.$jsName.'
            function readURL(input,divname,divshow) {
              if (input.files && input.files[0]) {
                var reader = new FileReader();
            
                reader.onload = function(e) {
                  $(divname).attr("src", e.target.result);
                  $(divshow).show();
                }
            
                reader.readAsDataURL(input.files[0]);
              }
            }
        });
        ';
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }
}
