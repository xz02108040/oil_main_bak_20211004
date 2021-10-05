<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\EngineeringMemberIdentityTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyEngineeringIdentityTrait;
use App\Http\Traits\Supply\SupplyMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyMemberLicenseTrait;
use App\Http\Traits\Supply\SupplyMemberTrait;
use App\Http\Traits\Supply\SupplyRPMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyRPMemberLicenseTrait;
use App\Http\Traits\Supply\SupplyRPMemberTrait;
use App\Http\Traits\Supply\SupplyRPProjectLicenseTrait;
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
use App\Model\View\view_user;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;

class SupplyRPMemberController extends Controller
{
    use SupplyRPMemberTrait,SupplyRPMemberIdentityTrait,SupplyRPMemberLicenseTrait,SessTraits;
    use BcustTrait,BcustATrait,SupplyMemberIdentityTrait,SupplyMemberLicenseTrait;
    use SupplyMemberTrait,EngineeringMemberTrait,SupplyEngineeringIdentityTrait;
    use SupplyRPProjectLicenseTrait,EngineeringMemberIdentityTrait;
    use PushTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPMemberController
    |--------------------------------------------------------------------------
    |
    | 審查＿承攬商成員申請單
    | > 申請成員
    | > 加入工程案件
    | > 申請工程身份
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
        $this->hrefMain         = 'rp_contractormember';
        $this->hrefMember       = 'contractormember';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'rp_contractormember/';
        $this->hrefMainDetail2  = 'rp_contractormember2/';
        $this->hrefMainDetail3  = 'contractorrpmemberapply1/';
        $this->hrefMainNew      = 'new_rp_contractormember';
        $this->routerPost       = 'postContractorrpmember';
        $this->routerPost2      = 'contractorrpmemberapplyList';

        $this->pageTitleMain    = Lang::get($this->langText.'.title7');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list7');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new7');//新增
        $this->pageNewTitle2    = Lang::get($this->langText.'.identity7');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit7');//編輯

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
        $aprocAry       = SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC',1,0,['P','R']);
        //進度
        $aproc = ($request->aproc)? $request->aproc : '';
        if($aproc)
        {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        } else {
            $aproc = Session::get($this->hrefMain.'.search.aproc','A');
        }
        //承攬商
        $pid = $request->pid;
        if($pid)
        {
            $supply= b_supply::getName($pid);
            Session::put($this->hrefMain.'.search.pid',$pid);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$pid)
        {
            $listAry = $this->getApiSupplyRPMemberMainList($aproc,$allowProjectAry);

        } else {
            $listAry = $this->getApiSupplyRPMemberList($pid,$aproc,$allowProjectAry);
            Session::put($this->hrefMain.'.Record',$listAry);
        }
        //dd($listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
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
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_21')]; //身分證
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_22')]; //行動電話
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_23')]; //血型
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_24')]; //緊急聯絡人
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_25')]; //聯絡方式
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
                    $btn          = HtmlLib::btn(SHCSLib::url($hrefMain,'','pid='.$id),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->id;
                    $name1        = $value->b_supply; //
                    $name2        = $value->apply_name; //
                    $name3        = $value->apply_stamp; //
                    $name4        = $value->aproc_name; //

                    $name10       = HtmlLib::Color($value->name,'red',1); //
                    $name11       = $value->nation_name.' / '.$value->bc_id; //
                    $name12       = $value->mobile1; //
                    if($value->tel1)
                    {
                        $name12 .= (strlen($name12))? '<br/>' : '';
                        $name12 .= $value->tel1;
                    }
                    $name13       = $value->blood.($value->bloodRH ? '('.$value->bloodRH.')' : ''); //
                    $name14       = $value->kin_user.($value->kin_kind_name ? '('.$value->kin_kind_name.')' : ''); //
                    $name15       = $value->kin_tel; //
                    $aprocColor   = isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 1; //

                    $btnRoute     = ($aproc == 'A')? $this->hrefMainDetail : $this->hrefMainDetail2;
                    $btnName      = ($aproc == 'A')? Lang::get('sys_btn.btn_21') : Lang::get('sys_btn.btn_60');
                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($btnRoute,$id),$btnName,4); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4,'label'=>$aprocColor],
                        '10'=>[ 'name'=> $name10],
                        '11'=>[ 'name'=> $name11],
                        '12'=>[ 'name'=> $name12],
                        '13'=>[ 'name'=> $name13],
                        '14'=>[ 'name'=> $name14],
                        '15'=>[ 'name'=> $name15],
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
        //返回參數，單筆工程身份功能
        Session::put($this->hrefMain.'.backPage',$this->hrefMainDetail.$urlid);
        Session::put($this->hrefMain.'.isEdit','N');
        $sexAry         = SHCSLib::getCode('SEX',1);
        $bloodAry       = SHCSLib::getCode('BLOOD',1);
        $bloodrhAry     = SHCSLib::getCode('BLOODRH');
        $kindAry        = SHCSLib::getCode('PERSON_KIND',1);
        $bctypeAry      = SHCSLib::getCode('BC_TYPE');
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
            $eiid       = $getData->b_supply_rp_member_ei_id; //
            $piid       = $getData->b_supply_rp_project_license_id; //
            $lid        = $getData->b_supply_rp_member_l_id; //
            $pid        = $getData->b_supply_id;

            $A1         = $getData->apply_name;     //申請人
            $A2         = $getData->apply_stamp;    //申請時間
            $A3         = $getData->aproc_name;     //進度名稱
            $A4         = $getData->head_img ? url('img/RPMember/'.$urlid)  : ''; //頭像ＵＲＬ

            $A11        = $getData->name;       //對象目標
            $A13        = $getData->sex;        //性別
            $A14        = $getData->bc_id;      //身分證
            $A15        = $getData->birth;      //生日
            $A16        = $getData->blood;      //血型
            $A17        = $getData->bloodRh;    //ＲＨ
            $A18        = $getData->tel1;       //家中電話
            $A19        = $getData->mobile1;    //行動電話
            $A20        = $getData->email1;     //E-mail
            $A21        = $getData->addr1;      //地址
            $A22        = $getData->kin_user;   //緊急連絡人
            $A23        = $getData->kin_kind;   //聯絡人關係
            $A24        = $getData->kin_tel;    //聯絡人電話
            $A25        = $getData->head_img;   //頭像
            $A26        = $getData->nation;   //頭像
            $A27        = $getData->nation_name;    //頭像

            $A34        = $getData->e_project_id; //指定參加的工程案件

//            //SESSION：申請工程身份陣列
            //取得工程身份＆證照申請
            $identityApplyAry = $this->getApiSupplyRPMemberIdentityAllList($pid,$id,$A34);
//                dd($identityApplyAry);
            //放入Session
            Session::put($this->hrefMain.'.identityApplyAry',$identityApplyAry);
            Session::put($this->hrefMain.'.auth_id',$id);

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $projectAry = e_project::getSelect('P',$pid);
            $projectIdentityAry = e_project_l::getSelect($A34);
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

        //table
        $table = new TableLib();

        //姓名＋圖片
        $fhtml1  = $A11;
        $fhtml2  = ($A4)? Html::image($A4,'',['class'=>'img-responsive','width'=>'200']) : '';
//        $fhtml2 .= $form->file('headImg');
//        $fhtml2 .= '<span id="blah_div" style="display: none;"><img id="blah" src="#" alt="" width="200" /></span>';
        $tBody[] = [
            '0'=>['name'=>Lang::get('sys_base.base_10707'),'b'=>1,'style'=>'width:15%;'],//$no
            '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
            '3'=>['name'=> $fhtml2,'style'=>'width:35%;','row'=>3,'col'=>2],
        ];
        unset($fhtml1,$fhtml2);

        //承攬商
        $fhtml1 = b_supply::getName($pid);
        //客戶名稱 / 會員編號
        $tBody[] = ['0'=>['name'=>Lang::get($this->langText.'.supply_12'),'b'=>1,'style'=>'width:15%;'],//$no
            '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
        ];

        //身分
        $fhtml1 = $bctypeAry[3];
        //客戶名稱 / 會員編號
        $tBody[] = ['0'=>['name'=>Lang::get('sys_base.base_10718'),'b'=>1,'style'=>'width:15%;'],//$no
            '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
        ];

        $table->addBody($tBody);
        $form->addHtml( $table->output() );

        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 個人資訊 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10902'),4);
        $form->addHtml( $html );
        //工程案件
        $html = isset($projectAry[$A34])? $projectAry[$A34] : '';
        $form->add('nameT6', $html,Lang::get('sys_base.base_10735'),1);
        //性別
        $html = isset($sexAry[$A13])? $sexAry[$A13] : '';
        $form->add('nameT6', $html,Lang::get('sys_base.base_10905'),1);
        //國籍
        $html = $A27;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10904'),1);
        //身分證
        $html = $A14;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10906'),1);
        //生日
        $html = $A15;
        $form->add('nameT3', $html,Lang::get('sys_base.base_10907'));
        //血型
        $html = isset($bloodAry[$A16])? $bloodAry[$A16] : '';
        $form->add('nameT5', $html,Lang::get('sys_base.base_10908'),1);
        //血型ＲＨ
        $html = isset($bloodrhAry[$A17])? $bloodrhAry[$A17] : '';
        $form->add('nameT6', $html,Lang::get('sys_base.base_10909'));
        //電話
        $html = $A18;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10910'));
        //行動電話
        $html = $A19;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10911'),1);
        //Email
        $html = $A20;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10912'));
        //地址
        $html = $A21;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10913'),1);
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 緊急聯絡人 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10903'),5);
        $form->addHtml( $html );
        //緊急聯絡人
        $html = $A22;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10914'),1);
        //關係
        $html = isset($kindAry[$A23])? $kindAry[$A23] : '';
        $form->add('nameT5', $html,Lang::get('sys_base.base_10915'),1);
        //聯絡電話
        $html = $A24;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10916'),1);
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

        //審查
        $form->addHr();
        //審查事由
        $html = $form->textarea('charge_memo');
        $html.= HtmlLib::Color(Lang::get($this->langText.'.supply_1021'),'red',1);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_50'));

        //Submit
        //$submitDiv  = $form->submit(Lang::get('sys_btn.btn_38'),'4','editY').'&nbsp;';
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY','','chgSubmit("agreeY")').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN','','chgSubmit("agreeN")').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('bc_type',3);
        $submitDiv.= $form->hidden('b_supply_id',$pid);
        $submitDiv.= $form->hidden('pid',$pid);
        $submitDiv.= $form->hidden('eiid',$eiid);
        $submitDiv.= $form->hidden('piid',$piid);
        $submitDiv.= $form->hidden('lid',$lid);
        $submitDiv.= $form->hidden('name',$A11);
        $submitDiv.= $form->hidden('sex',$A13);
        $submitDiv.= $form->hidden('nation',$A26);
        $submitDiv.= $form->hidden('bc_id',$A14);
        $submitDiv.= $form->hidden('birth',$A15);
        $submitDiv.= $form->hidden('blood',$A16);
        $submitDiv.= $form->hidden('bloodRh',$A17);
        $submitDiv.= $form->hidden('tel1',$A18);
        $submitDiv.= $form->hidden('mobile1',$A19);
        $submitDiv.= $form->hidden('email1',$A20);
        $submitDiv.= $form->hidden('addr1',$A21);
        $submitDiv.= $form->hidden('kind_user',$A22);
        $submitDiv.= $form->hidden('kind_type',$A23);
        $submitDiv.= $form->hidden('kind_tel',$A24);
        $submitDiv.= $form->hidden('e_project_id',$A34);
        $submitDiv.= $form->hidden('head_img_path',$A25);
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
        /**
         * 第一階段：規則檢核
         */
        //1-1. 取得「事件」按鈕
        $submitBtn = $request->submitBtn;
        //1-2-1. 規則檢核：資料異常，請重新作業，謝謝
        if(!$request->id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10019'))
                ->withInput();
        }
        /**
         * 第二階段：參數
         */
        $this->getBcustParam();
        $menu = $this->pageTitleMain;
        $id   = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $eiid = $request->eiid;
        $piid = $request->piid;
        $lid  = $request->lid;
        $pid  = $request->pid;
        //2-2. 如果 承攬商不存在
        if(!$pid && is_numeric($pid) && $pid > 0)
        {
            $msg = Lang::get($this->langText.'.supply_1000');
            return \Redirect::back()->withErrors($msg);
        }

        $isAgree = 0;
        $isNew   = ($id > 0)? 0 : 1;
        $action  = ($isNew)? 1 : 2;
        $headImg = $headImgN = '';
        $identityApplyAry = [];

        //1-3. 如果是 同意
        if($submitBtn == 'agreeY')
        {
            //1-3-1. 資料異常，請重新作業，謝謝
            if( !$request->name || !$request->bc_id || !$request->sex || !$request->blood || !$request->mobile1 || !$request->kind_user || !$request->kind_type || !$request->kind_tel || !$request->e_project_id)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10019'))
                    ->withInput();
            }
            //1-3-2. 身分證格式重複
            elseif(CheckLib::checkBCIDExist($request->bc_id, $id))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10925'))
                    ->withInput();
            }else{
                //2-3.
                $identityApplyAry = Session::get($this->hrefMain.'.identityApplyAry',[]);
                if(!count($identityApplyAry))
                {
                    //查無工程身份申請
                    return \Redirect::back()
                        ->withErrors(Lang::get($this->langText.'.supply_1022'))
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

                            } else {
                                //申請工程身份，不完整
                                return \Redirect::back()
                                    ->withErrors(Lang::get($this->langText.'.supply_1019',['name'=>$val['name']]))
                                    ->withInput();
                            }
                        } else {
                            //去除沒有申請的
                            unset($identityApplyAry[$key]);
                        }
                    }
                    if(!$isApplyNum)
                    {
                        //查無要申請的工程身份，請至少填寫「施工人員」所需資料
                        return \Redirect::back()
                            ->withErrors(Lang::get($this->langText.'.supply_1018'))
                            ->withInput();
                    }
//                dd($identityApplyAry);
                }
            }
        }
        //1-4. 如果是不同意
        elseif($submitBtn == 'agreeN') {
            //1-4-1. 不同意，需要填寫事由！
            if (!$request->charge_memo)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1021'))
                    ->withInput();
            }
        } else {
            //1-5. 如果找不到相對應的按鈕：異常
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10020'))
                ->withInput();
        }



        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']              = $request->name;
        $upAry['head_img']          = $headImg;
        $upAry['head_imgN']         = $headImgN;
        $upAry['sex']               = $request->sex;
        $upAry['nation']            = $request->nation;
        $upAry['bc_id']             = $request->bc_id;
        $upAry['birth']             = $request->birth;
        $upAry['blood']             = $request->blood;
        $upAry['bloodRh']           = $request->bloodRh;
        $upAry['tel1']              = $request->tel1;
        $upAry['mobile1']           = $request->mobile1;
        $upAry['email1']            = $request->email1;
        $upAry['addr1']             = $request->addr1;
        $upAry['kin_user']          = $request->kind_user;
        $upAry['kin_kind']          = $request->kind_type;
        $upAry['kin_tel']           = $request->kind_tel;
        $upAry['e_project_id']      = $request->e_project_id;
        $upAry['charge_memo']       = $request->charge_memo;

        $upAry['b_cust_id']         = 0;
        $upAry['b_supply_id']       = $pid;

        //工程身份＋執照
        $upAry['identity']          = $identityApplyAry;

        if($submitBtn == 'agreeY')
        {
            $isAgree        = 1;
            $upAry['aproc'] = 'O';

            $upAry['bc_type']                   = 3;
            $upAry['bc_type_app']               = 0;
            $upAry['b_menu_group_id']           = 1;
            $upAry['c_menu_group_id']           = 2;
            $upAry['account']                   = $request->bc_id;
            $upAry['password']                  = substr($request->bc_id,-4);
            $upAry['isIN']                      = 'Y';
            $upAry['isAutoAccount']             = 'N';
            $upAry['b_supply_rp_member_id']     = $id;
            $upAry['b_supply_rp_member_ei_id']  = $eiid;
            $upAry['b_supply_rp_project_license_id']  = $piid;
            $upAry['b_supply_rp_member_l_id']   = $lid;
            $upAry['head_img_path']             = $request->head_img_path;
            $upAry['isMember']                  = 1;
            $upAry['isUT']                      = ($request->isUT == 'Y')? 'Y' : 'C';
            $upAry['job_kind']                  = 1;
        }
        if($submitBtn == 'agreeN')
        {
            $upAry['aproc'] = 'C';
        }
//        dd($upAry);
        //新增
        if($isNew)
        {
            //$ret = $this->createSupplyRPMember($upAry,$this->b_cust_id);
            $ret  = 0;
            $id  = $ret;
        } else {
            //修改
            $ret1 = $this->setSupplyRPMember($id,$upAry,$this->b_cust_id);
            $ret2 = 0;//$this->setSupplyRPMemberLicenseGroup($upAry['identity'],$this->b_cust_id);
            $ret3 = 0;//$this->setSupplyRPMemberIdentity($id,$upAry,$this->b_cust_id);
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
                //動作紀錄
                if($isAgree)
                {
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust',$id);
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust_a',$id);
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member',$id);
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_ei',$id);
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_l',$id);
                }
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_rp_member_ei',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_rp_member_l',$id);
                //推播：申請審查結果->通知申請人
                $isOk = ($upAry['aproc'] == 'O')? 'Y' : 'N';
                $this->pushToRPMemberApplyResult($id,$isOk);
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
        $sexAry         = SHCSLib::getCode('SEX');
        $kindAry        = SHCSLib::getCode('PERSON_KIND');
        $bctypeAry      = SHCSLib::getCode('BC_TYPE');
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
            $A4         = $getData->head_img ? url('img/RPMember/'.$urlid)  : ''; //

            $A9         = $getData->charge_memo; //

            $A11        = $getData->name; //
            $A13        = $getData->sex; //
            $A14        = $getData->bc_id; //
            $A15        = $getData->birth; //
            $A16        = $getData->blood; //
            $A17        = $getData->bloodRh; //
            $A18        = $getData->tel1; //
            $A19        = $getData->mobile1; //
            $A20        = $getData->email1; //
            $A21        = $getData->addr1; //
            $A22        = $getData->kin_user; //
            $A23        = $getData->kin_kind; //
            $A24        = $getData->kin_tel; //
            $A34        = $getData->e_project_id; //
            $A35        = $getData->b_cust_id; //
            $A36        = e_project_s::getUTName($A34,$A35); //

            //SESSION：申請工程身份陣列
            //取得工程身份＆證照申請
            $identityApplyAry = $this->getApiSupplyRPMemberIdentityAllList($pid,$id,$A34);
            foreach ($identityApplyAry as $key => $val)
            {
                if(!isset($val['isOk']) || $val['isOk'] != 'Y') unset($identityApplyAry[$key]);
            }
            //dd($identityApplyAry);
            //放入Session
            Session::put($this->hrefMain.'.identityApplyAry',$identityApplyAry);

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $projectAry = e_project::getSelect('P',$pid);
            $projectIdentityAry = e_project_l::getSelect($A34);
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

        //table
        $table = new TableLib();

        //姓名＋圖片
        $fhtml1  = $A11;
        $fhtml2  = ($A4)? Html::image($A4,'',['class'=>'img-responsive','width'=>'200']) : '';
        $fhtml2 .= '<span id="blah_div" style="display: none;"><img id="blah" src="#" alt="" width="200" /></span>';
        $tBody[] = [
            '0'=>['name'=>Lang::get('sys_base.base_10707'),'b'=>1,'style'=>'width:15%;'],//$no
            '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
            '3'=>['name'=> $fhtml2,'style'=>'width:35%;','row'=>3,'col'=>2],
        ];
        unset($fhtml1,$fhtml2);

        //承攬商
        $fhtml1 = b_supply::getName($pid);
        //客戶名稱 / 會員編號
        $tBody[] = ['0'=>['name'=>Lang::get($this->langText.'.supply_12'),'b'=>1,'style'=>'width:15%;'],//$no
                    '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
        ];

        //身分
        $fhtml1 = $bctypeAry[3];
        //客戶名稱 / 會員編號
        $tBody[] = ['0'=>['name'=>Lang::get('sys_base.base_10718'),'b'=>1,'style'=>'width:15%;'],//$no
                    '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
        ];
        $table->addBody($tBody);
        $form->addHtml( $table->output() );

        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 個人資訊 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10902'),4);
        $form->addHtml( $html );
        //工程案件
        $html = isset($projectAry[$A34])? $projectAry[$A34] : '';
        $form->add('nameT6', $html,Lang::get('sys_base.base_10735'),1);
        //性別
        $html = isset($sexAry[$A13])? $sexAry[$A13] : '';
        $form->add('nameT6', $html,Lang::get('sys_base.base_10905'),1);
        //身分證
        $html = $A14;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10906'),1);
        //生日
        $html = $A15;
        $form->add('nameT3', $html,Lang::get('sys_base.base_10907'));
        //血型
        $html = $A16;
        $form->add('nameT5', $html,Lang::get('sys_base.base_10908'),1);
        //血型ＲＨ
        $html = $A17;
        $form->add('nameT6', $html,Lang::get('sys_base.base_10909'));
        //電話
        $html = $A18;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10910'));
        //行動電話
        $html = $A19;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10911'),1);
        //Email
        $html = $A20;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10912'));
        //地址
        $html = $A21;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10913'));
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 緊急聯絡人 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10903'),5);
        $form->addHtml( $html );
        //緊急聯絡人
        $html = $A22;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10914'),1);
        //關係
        $html = isset($kindAry[$A23])? $kindAry[$A23] : '';
        $form->add('nameT5', $html,Lang::get('sys_base.base_10915'),1);
        //聯絡電話
        $html = $A24;
        $form->add('nameT2', $html,Lang::get('sys_base.base_10916'),1);
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

                //按鈕
                if($value['isApply'] == 'R')
                {
                    //修改
                    $btn      = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail3,$id,'page=N'),Lang::get('sys_btn.btn_60'),1); //按鈕
                } else {
                    //申請
                    $btn      = ''; //按鈕
                }

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                    '1'=>[ 'name'=> $name1],
                    '4'=>[ 'name'=> $name4],
                    '2'=>[ 'name'=> $name2],
                    '3'=>[ 'name'=> $name3],
                    '99'=>[ 'name'=> $btn ]
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
            $form->add('nameT3', $html,$show_name2,1);
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
