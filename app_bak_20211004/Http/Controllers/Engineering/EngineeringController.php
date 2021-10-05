<?php

namespace App\Http\Controllers\Engineering;

use Auth;
use Lang;
use Session;
use App\Lib\LogLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Lib\TableLib;
use App\Lib\ContentLib;
use App\Model\sys_param;
use App\Model\Emp\be_dept;
use App\Model\Emp\b_cust_e;
use Illuminate\Http\Request;
use App\Model\Supply\b_supply;
use App\Http\Traits\SessTraits;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_e;
use App\Http\Controllers\Controller;
use App\Model\Engineering\e_project;
use App\Model\Supply\b_supply_member;
use App\Model\Engineering\e_project_d;
use App\Model\Engineering\e_project_f;
use App\Model\Engineering\e_project_s;
use App\Model\Report\rept_doorinout_t;
use App\Model\WorkPermit\wp_work_worker;
use App\Model\Engineering\e_project_type;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Engineering\EngineeringCourseTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\Engineering\EngineeringHistoryTrait;
use App\Http\Traits\Engineering\EngineeringLicenseTrait;

class EngineeringController extends Controller
{
    use EngineeringTrait,EngineeringHistoryTrait,EngineeringFactoryTrait,EngineeringMemberTrait,EngineeringCourseTrait,EngineeringLicenseTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | EngineeringController
    |--------------------------------------------------------------------------
    |
    | 工程案件 維護
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
        $this->hrefMain         = 'engineering';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'engineering/';
        $this->hrefMainDetail2  = 'engineeringlicense';
        $this->hrefMainDetail3  = 'engineeringdept';
        $this->hrefMainDetail4  = 'engineeringmember';
        $this->hrefMainDetail5  = 'engineeringfactory';
        $this->hrefMainDetail6  = 'engineeringcar';
        $this->hrefMainDetail7  = 'engineeringcourse';
        $this->hrefMainRept     = 'engineeringmemberroster/';
        $this->hrefMainNew      = 'new_engineering';
        $this->hrefMainChange   = 'change_engineering';
        $this->routerPost       = 'postEngineering';

        $this->pageTitleMain    = Lang::get($this->langText.'.title1');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list1');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new1');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit1');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

        $this->identity_A = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $this->identity_B = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
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
        $myDeptId = $this->isRootDept? 0 : $this->be_dept_id;
        //參數
        $out = $js ='';
        $no  = 0;
        $listAry  = [];
        $closeAry = SHCSLib::getCode('CLOSE',1);
        $aprocAry = SHCSLib::getCode('ENGINEERING_APROC',1);
        $isAdAry  = SHCSLib::getCode('PROJECT_IS_EXIST_ADUSER',1);
        $storeAry = b_factory::getSelect();
        $typeAry  = e_project_type::getSelect();
        $sid      = $request->sid;
        $tid      = $request->tid;
        $aid      = $request->aid;
        $bid      = $request->bid;
        $cid      = $request->cid;
        $aproc    = $request->aproc;
        if($request->has('clear'))
        {
            $sid = $tid = $aid= $cid = 0;
            $aproc = $bid = '';
            Session::forget($this->hrefMain.'.search');
        }
        if(!$sid)
        {
            $sid = Session::get($this->hrefMain.'.search.sid',0);
        } else {
            Session::put($this->hrefMain.'.search.sid',$sid);
        }
        if(!$tid)
        {
            $tid = Session::get($this->hrefMain.'.search.tid',0);
        } else {
            Session::put($this->hrefMain.'.search.tid',$tid);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid','');
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid','');
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if(!$cid)
        {
            $cid = Session::get($this->hrefMain.'.search.cid','N');
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        if(!$aproc)
        {
            $aproc = Session::get($this->hrefMain.'.search.aproc','');
        } else {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        }
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($sid || $tid || $aproc || $cid || $bid || $aid)
        {
            $search  = [0,$sid,$tid,$aproc,$bid,'','',$myDeptId];
            $listAry = $this->getApiEngineeringList($search,$cid,$aid,'N');
            if($request->testshow == 'Y')
            {
                foreach($listAry as $value)
                {
                    dd($value);
                }

            }
            Session::put($this->hrefMain.'.Record',$listAry);
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y') $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        $html = $form->select('sid',$storeAry,$sid,2,Lang::get($this->langText.'.engineering_95'));
        $html.= $form->select('tid',$typeAry,$tid,2,Lang::get($this->langText.'.engineering_3'));
        $html.= $form->select('aproc',$aprocAry,$aproc,2,Lang::get($this->langText.'.engineering_12'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $html = $form->select('cid',$closeAry,$cid,2,Lang::get($this->langText.'.engineering_33'));
        $html.= $form->select('aid',$isAdAry,$aid,2,Lang::get($this->langText.'.engineering_97'));
        $html.= $form->text('bid',$bid,2,Lang::get($this->langText.'.engineering_4'));
        $form->addRowCnt($html);
        $html = $form->memo(HtmlLib::Color(Lang::get($this->langText.'.engineering_1037'),'red',1));
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_4')];  //編號
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_1')];  //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_113')];  //監造工程師
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_116')];  //監造員
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_7')];  //負責廠商
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_9')];  //結束日期
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_12')]; //進度
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_109')];//場地
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_41')];//教育訓練
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_107')];//工程身分
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_127')];//監造部門
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_40')]; //車輛
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_20')]; //承攬商成員

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->name; //
                $name2        = $value->project_no; //
                $name4        = $value->charge_dept_name.'<br/>'.$value->charge_user_name; //
                $name5        = $value->charge_dept_name2.'<br/>'.$value->charge_user_name2; //
                $name6        = $value->supply.'<br/><b>'.$value->boss_name.'</b><br/>'.$value->tel1; //
                $name7        = isset($aprocAry[$value->aproc])? $aprocAry[$value->aproc] : ''; //
                $name8        = $value->edate; //
                $isCloseColor = $value->aproc == 'O' ? 4 : ($value->aproc == 'C' ? 5 : 2) ; //停用顏色
                if($value->isClose == 'Y')
                {
                    $name7 = Lang::get($this->langText.'.engineering_34');
                    $isCloseColor = 5;
                }

                //按鈕
                $StoreeBtn    = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail5,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕
                $LicenseBtn   = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕
                $DeptBtn      = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail3,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕
                $MemberBtn    = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail4,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕
                $CarBtn       = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail6,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕
                $CourseBtn    = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail7,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕
                //案件為結案和過期階段，按鈕不可列印
                if (in_array($value->aproc, ['O', 'C'])) {
                    $reptBtn      = HtmlLib::btn('#', $name2, 2);; //按鈕
                } else {
                    $reptBtn      = HtmlLib::btn(SHCSLib::url($this->hrefMainRept, $id, ''), $name2, 2); //按鈕
                }


                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['1'=>[ 'name'=> $reptBtn],
                            '2'=>[ 'name'=> $name1],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '6'=>[ 'name'=> $name6],
                            '8'=>[ 'name'=> $name8],
                            '7'=>[ 'name'=> $name7,'label'=>$isCloseColor],
                            '20'=>[ 'name'=> $StoreeBtn],
                            '24'=>[ 'name'=> $CourseBtn],
                            '21'=>[ 'name'=> $LicenseBtn],
                            '22'=>[ 'name'=> $DeptBtn],
                            '23'=>[ 'name'=> $CarBtn],
                            '30'=>[ 'name'=> $MemberBtn],
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
        $aprocAry2  = SHCSLib::getCode('ENGINEERING_APROC');
        $typeAry    = e_project_type::getSelect();
        $supplyAry  = b_supply::getSelect();
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        $hrefChange     = $this->hrefMainChange.'?pid='.$urlid.'&aid=';
        //資料內容
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //資料明細
            $A1         = $getData->name; //
            $A3         = $getData->project_type; //
            $A4         = $getData->project_no; //
            $A5         = $getData->charge_dept; //
            $A6         = $getData->charge_user; //
            $A7         = $getData->b_supply_id; //
            $A8         = isset($aprocAry2[$getData->aproc])? $aprocAry2[$getData->aproc] : ''; //
            $A25        = $getData->aproc; //
            $A9         = $getData->sdate; //
            $A10        = $getData->edate; //
            $A11        = substr($getData->stime,0,5); //
            $A12        = substr($getData->etime,0,5); //
            $A13        = $getData->memo; //
            $A14        = e_project_s::getIdentityMemberList($id,$this->identity_A); //
            $A15        = e_project_s::getIdentityMemberList($id,$this->identity_B); //
            $A19        = $getData->b_factory_id; //
            $A20        = $getData->charge_dept2; //
            $A21        = $getData->charge_user2; //
            $A22        = $getData->b_factory_id2; //
            $A23        = $getData->charge_user_name; //
            $A24        = $getData->charge_dept_name; //
            $A26        = $getData->charge_user_name2; //
            $A27        = $getData->charge_dept_name2; //
            $A28        = $getData->store1; //
            $A29        = $getData->store2; //
            $A30        = $getData->supply; //
            $A31        = $getData->type; //
            $A32        = '<b>'.$getData->boss_name.'</b>'.' / '.$getData->tel1; //


            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $isProjectClose = ($A99 || in_array($A25,['C','A','O','X']))? true : false;
            $isEditClose = in_array($A25,['C','A','B','R','O','X'])? true : false;

            $deptAry    = be_dept::getSelect();
            $empAry     = b_cust_e::getSelect(0,$A5);
            $empAry2    = b_cust_e::getSelect(0,$A20);
            $storeAry   = b_factory::getSelect();

//            $setMemberJobKindHref = 'engineeringmember?pid='.$urlid;
//            $setMemberJobKindBtn1 = HtmlLib::btn($setMemberJobKindHref,Lang::get($this->langText.'.engineering_1025'),5);
//            $setMemberJobKindBtn2 = HtmlLib::btn($setMemberJobKindHref,Lang::get($this->langText.'.engineering_1026'),5);
//            $setLocalHref = 'engineeringfactory?pid='.$urlid;
//            $isHasProjectFactory = e_project_f::isExist($id);
//            $setLocalString = ($isHasProjectFactory)? 'engineering_104' : 'engineering_1035';
//            $setLocalColor  = ($isHasProjectFactory)? 4 : 5;
//            $setLocalBtn1 = HtmlLib::btn($setLocalHref,Lang::get($this->langText.'.'.$setLocalString),$setLocalColor);

            //場地
            $btnLocal    = e_project_f::genBtn($id);
            $btnDept     = e_project_d::genBtn($id);

            //異動歷程
            $historyList = $this->getApiEngineeringHistoryList($id);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = ($isProjectClose)? $A1 : $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
        //廠區
        $html = $btnLocal;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_96'));
        //類別
        $html = ($isProjectClose)? $A31 : $form->select('project_type',$typeAry,$A3);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_3'),1);
        //名稱
        $html = ($isProjectClose)? $A4 : $form->text('project_no',$A4);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_4'),1);

        $form->addHr();
        //監造部門廠區
        $html = ($isProjectClose)? $A28 : $form->select('b_factory_id',$storeAry,$A19);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_95'),1);
        //監造部門
        $html = ($isProjectClose)? $A24 : $form->select('charge_dept',$deptAry,$A5);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_5'),1);
        //監造工程師
        $html = ($isProjectClose)? $A23 : $form->select('charge_user',$empAry,$A6);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_113'),1);

        $form->addHr();
        //監造員所屬廠區
        $html = ($isProjectClose)? $A29 : $form->select('b_factory_id2',$storeAry,$A22);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_114'));
        //監造員部門
        $html = ($isProjectClose)? $A27 : $form->select('charge_dept2',$deptAry,$A20);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_115'));
        //監造員
        $html = ($isProjectClose)? $A26 : $form->select('charge_user2',$empAry2,$A21);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_116'));

        //轄區監造
        $form->addHr();
        $html = $btnDept;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_127'),1);
        $form->addHr();
        //負責廠商
        $html =($isProjectClose)? $A30 :  $form->select('b_supply_id',$supplyAry,$A7);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_7'),1);
        //
        $html  = $A32;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_160'));
        //工地負責人
        $html  = ($isProjectClose)? $A14 : $A14;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_17'));
        //工地安衛人員
        $html  = ($isProjectClose)? $A15 : $A15;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_18'));
        //進度
        $html = HtmlLib::Color($A8,'blue',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_12'),1);
        //開始日期
        $html = ($isEditClose)? $A9 : $form->date('sdate',$A9);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_8'),1);
        //結束日期
        $html = ($isEditClose)? $A10 : $form->date('edate',$A10);
        $html.= ($isEditClose)? '' :  HtmlLib::Color(Lang::get($this->langText.'.engineering_1055'),'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_9'),1);
        //施工開始時間
        $html = ($isProjectClose)? $A11 : $form->time('stime',$A11);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_10'),1);
        //施工結束時間
        $html = ($isProjectClose)? $A12 : $form->time('etime',$A12);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_11'),1);
        //說明
        $html = ($isProjectClose)? $A13 : $form->textarea('memo',$A13);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));

        //
        if(count($historyList))
        {
            $form->addHr();
            //table
            $table = new TableLib();
            //標題
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_120')];  //原先階段
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_121')];  //異動階段
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_126')];  //延長工期
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_119')];  //異動事由
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_122')];  //異動人員
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_123')];  //異動時間

            $table->addHead($heads,0);
            if(count($historyList))
            {
                foreach($historyList as $value)
                {
                    $name1        = $value['old_aproc']; //
                    $name2        = $value['new_aproc']; //
                    $name3        = $value['chg_memo']; //
                    $name4        = $value['aproc_memo']; //
                    $name5        = $value['aproc_user']; //
                    $name6        = $value['aproc_stamp']; //

                    $tBody[] = ['1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '5'=>[ 'name'=> $name5],
                        '6'=>[ 'name'=> $name6],
                    ];
                }
                $table->addBody($tBody);
            }
            $form->add('nameT1', $table->output(),Lang::get($this->langText.'.engineering_124'));

        }
        $form->addHr();
        //停用
        if(!$isProjectClose)
        {
            $html = $form->checkbox('isClose','Y',$A99);
            $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_34'));
        }
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv = '';
        //[修改]
        if(!$isProjectClose)
        {
            $submitDiv .= $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        }

        //[延長工期]
        if(in_array($A25,['P','R','B']))
        {
            //延長工期(施工/過期->延長)$hrefChange
            $submitDiv .= $form->linkbtn($hrefChange.SHCSLib::encode('R'),Lang::get('sys_btn.btn_73'),'4','agreeToR').'&nbsp;';
        }
        //[復工]
        if($A25 == 'A')
        {
            //停工後復工(停工->復工)
            $submitDiv .= $form->linkbtn($hrefChange.SHCSLib::encode('B'),Lang::get('sys_btn.btn_72'),'4','agreeToB').'&nbsp;';
        }

        //[停工]
        if(in_array($A25,['P','R','B']))
        {
            //停工(施工/延長/過期/復工->停工)
            $submitDiv .= $form->linkbtn($hrefChange.SHCSLib::encode('A'),Lang::get('sys_btn.btn_71'),'4','agreeToA').'&nbsp;';
        }
        //[結案]
        if(in_array($A25,['P','C','R','A','B']))
        {
            //結案(過期/施工/停工/延長/復工->結案)
            $submitDiv .= $form->linkbtn($hrefChange.SHCSLib::encode('O'),Lang::get('sys_btn.btn_70'),'4','agreeToO').'&nbsp;';
        }

        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('old_sdate',$A9);
        $submitDiv.= $form->hidden('old_edate',$A10);
        $submitDiv.= $form->hidden('isProjectClose',$isProjectClose);
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
                language: "zh-TW"
            });
            
            
            $( "#b_factory_id" ).change(function() {
                        var sid = $("#b_factory_id").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 3, sid : sid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#charge_dept option").remove();
                             $.each(result, function(key, val) {
                                $("#charge_dept").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
            $( "#b_factory_id2" ).change(function() {
                        var sid = $("#b_factory_id2").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 3, sid : sid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#charge_dept2 option").remove();
                             $.each(result, function(key, val) {
                                $("#charge_dept2").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
            $( "#charge_dept" ).change(function() {
                        var eid = $("#charge_dept").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 2, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#charge_user option").remove();
                             $.each(result, function(key, val) {
                                $("#charge_user").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
            $( "#charge_dept2" ).change(function() {
                        var eid = $("#charge_dept2").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 2, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#charge_user2 option").remove();
                             $.each(result, function(key, val) {
                                $("#charge_user2").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
             $( "#b_supply_id2" ).change(function() {
                        var sid = $("#b_supply_id").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findContractor').'",  
                          data: { type: 1, sid : sid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#job_user1 option").remove();
                             $("#job_user2 option").remove();
                             $("#safe_user1 option").remove();
                             $("#safe_user2 option").remove();
                             $.each(result, function(key, val) {
                                $("#job_user1").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                $("#job_user2").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                $("#safe_user1").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                $("#safe_user2").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
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

        $this->getBcustParam();
        $id = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        //
        if($request->isProjectClose)
        {
            if($request->edate && strtotime($request->edate) > strtotime($request->old_edate)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1055'))//日期不可為空
                    ->withInput();
            }elseif(strtotime($request->edate) < strtotime(date('Y-m-d'))){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1003'))//日期不可小於今日
                    ->withInput();
            }elseif(strtotime($request->edate) < strtotime($request->sdate)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1041'))//日期不可小於今日
                    ->withInput();
            }
        } elseif($request->has('change_aporc')) {
            $aprocAry       = SHCSLib::getCode('ENGINEERING_APROC');
            $changeAproc    = SHCSLib::decode($request->change_aporc);
            /*
                結案的判斷條件
                1.無開立許可證
                2.人員尚未離場
            */
            list($hasWork, $hasWorkType) = wp_work_worker::hasInWork($id, 0);
            $project_member_Ary = e_project_s::getSelect($id, 0, 0);
            $UserInOutCount = 0;
            foreach ($project_member_Ary as $b_cust_id => $name) {
                list($UserInOutResult, $door_stamp) = rept_doorinout_t::getUserInOutResult($b_cust_id);
                if($UserInOutResult == 'Y') $UserInOutCount += 1;
            }
            
            if(!in_array($changeAproc,array_keys($aprocAry)))
            {
                return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
            }
            //請填寫事由
            elseif(!$request->memo ){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1019'))
                    ->withInput();
            }
            //展延工期
            elseif($changeAproc == 'R' && strtotime($request->old_edate) >= strtotime($request->edate)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1048'))
                    ->withInput();
            }
            //結案的判斷條件 1.無開立許可證
            elseif($changeAproc == 'O' && $hasWork){
                $errCode = ($hasWorkType == 2) ? 'engineering_1084' : 'engineering_1083';
                $msg = Lang::get('sys_engineering' . '.' . $errCode);
                return \Redirect::back()->withErrors($msg);
            }
            //結案的判斷條件 2.人員尚未離場！
            elseif($changeAproc == 'O' && $UserInOutCount){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1082'))
                    ->withInput();
            }
        } else {
            //資料不齊全
            if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->project_no || !$request->project_type)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10103'))
                    ->withInput();
            }
            //負責部門＆承辦不可為空
            elseif(!$request->charge_dept || !$request->charge_user){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1000'))
                    ->withInput();
            }
            //監工部門＆監工不可為空
//            elseif(!$request->charge_dept2 || !$request->charge_user2){
//                return \Redirect::back()
//                    ->withErrors(Lang::get($this->langText.'.engineering_1029'))
//                    ->withInput();
//            }
            //負責承攬商不可為空
            elseif(!$request->b_supply_id){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1001'))
                    ->withInput();
            }
            //開始日期不可大於結束日期
            elseif(strtotime($request->sdate) > strtotime($request->edate)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1002'))
                    ->withInput();
            }
            //工程案號已存在
            elseif(e_project::isNoExist($request->project_no,$id)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1075'))
                    ->withInput();
            }
        }

        //廠區不可為空
        if($isNew)
        {
            if(!$request->b_factory_id)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1004'))
                    ->withInput();
            }
            //不可小於今日
            elseif(strtotime($request->edate) < strtotime(date('Y-m-d'))){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1003'))
                    ->withInput();
            }

        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        if($request->change_aporc)
        {

            $upAry['aproc']              = $changeAproc;
            $upAry['aproc_memo']         = $request->memo;
        }
        elseif(!$request->isProjectClose)
        {
            $upAry['name']               = $request->name;
            $upAry['b_factory_id']       = is_numeric($request->b_factory_id) ? $request->b_factory_id : 0;
            $upAry['b_factory_id2']      = is_numeric($request->b_factory_id2) ? $request->b_factory_id2 : 0;
            $upAry['project_type']       = is_numeric($request->project_type) ? $request->project_type : 0;
            $upAry['charge_dept']        = is_numeric($request->charge_dept) ?  $request->charge_dept   : 0;
            $upAry['charge_user']        = is_numeric($request->charge_user) ?  $request->charge_user   : 0;
            $upAry['charge_dept2']       = is_numeric($request->charge_dept2) ? $request->charge_dept2   : 0;
            $upAry['charge_user2']       = is_numeric($request->charge_user2) ? $request->charge_user2   : 0;
            $upAry['b_supply_id']        = is_numeric($request->b_supply_id) ?  $request->b_supply_id   : 0;
            $upAry['project_no']         = $request->project_no;
            $upAry['door_check_rule']    = $request->door_check_rule;
            $upAry['sdate']              = $request->sdate;
            $upAry['stime']              = $request->stime;
            $upAry['etime']              = $request->etime;
            $upAry['memo']               = $request->memo;
            $upAry['isClose']            = ($request->isClose == 'Y')? 'Y' : 'N';
        }
        $upAry['edate']              = $request->edate;
        if($request->change_aporc)
        {
            $upAry['edate']              =  $upAry['aproc'] == 'O' ? date('Y-m-d') : $request->edate;
        }

        //新增
        if($isNew)
        {
            $ret = $this->createEngineering($upAry,$this->b_cust_id);
            $id  = $ret;
        } elseif($request->change_aporc) {
            //修改 工程案件階段
            $ret = $this->setEngineering($id,$upAry,$this->b_cust_id);
        } else {
            //修改
            $ret = $this->setEngineering($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'e_project',$id);

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
        $checkAry   = SHCSLib::getCode('DOOR_CHECK_RULE');
        $typeAry    = e_project_type::getSelect();
        $supplyAry  = b_supply::getSelect();
        $storeAry   = b_factory::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html = $form->text('name','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
        //類別
        $html = $form->select('project_type',$typeAry,1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_3'),1);
        //名稱
        $html = $form->text('project_no','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_4'),1);

        $form->addHr();
        //監造所屬廠區
        $html = $form->select('b_factory_id',$storeAry,0);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_95'),1);
        //監造工程師部門
        $html = $form->select('charge_dept',[],0);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_5'),1);
        //監造工程師
        $html = $form->select('charge_user',[],0);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_113'),1);

        $form->addHr();
        //監造員所屬廠區
        $html = $form->select('b_factory_id2',$storeAry,0);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_114'));
        //監造員部門
        $html = $form->select('charge_dept2',[],0);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_115'));
        //監造員
        $html = $form->select('charge_user2',[],0);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_116'));

        $form->addHr();
        //負責廠商
        $html = $form->select('b_supply_id',$supplyAry,0);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_7'),1);
        //進出規則
        $html = $form->select('door_check_rule',$checkAry,1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_19'),1);
        //開始日期
        $html = $form->date('sdate',date('Y-m-d'));
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_8'),1);
        //結束日期
        $html = $form->date('edate',date('Y-m-d'));
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_9'),1);
        //施工開始時間
        //$html = $form->time('stime','');
        //$form->add('nameT1', $html,Lang::get($this->langText.'.engineering_10'),1);
        //施工結束時間
        //$html = $form->time('etime','');
        //$form->add('nameT1', $html,Lang::get($this->langText.'.engineering_11'),1);
        //說明
        $html = $form->textarea('memo','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));

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
           $("#sdate,#edate").datepicker({
                format: "yyyy-mm-dd",
                language: "zh-TW"
            });
            
            $( "#b_factory_id" ).change(function() {
                        var sid = $("#b_factory_id").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 3, sid : sid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#charge_dept option").remove();
                             $.each(result, function(key, val) {
                                $("#charge_dept").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
            
            $( "#b_factory_id2" ).change(function() {
                        var sid = $("#b_factory_id2").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 3, sid : sid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#charge_dept2 option").remove();
                             $.each(result, function(key, val) {
                                $("#charge_dept2").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
            $( "#charge_dept" ).change(function() {
                        var eid = $("#charge_dept").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 2, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#charge_user option").remove();
                             $.each(result, function(key, val) {
                                $("#charge_user").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
            $( "#charge_dept2" ).change(function() {
                        var eid = $("#charge_dept2").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 2, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#charge_user2 option").remove();
                             $.each(result, function(key, val) {
                                $("#charge_user2").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
             $( "#b_supply_id2" ).change(function() {
                        var sid = $("#b_supply_id").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findContractor').'",  
                          data: { type: 1, sid : sid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#job_user1 option").remove();
                             $("#job_user2 option").remove();
                             $("#safe_user1 option").remove();
                             $("#safe_user2 option").remove();
                             $.each(result, function(key, val) {
                                $("#job_user1").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                $("#job_user2").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                $("#safe_user1").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                $("#safe_user2").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
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
    public function change(Request $request)
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
        $aprocAry       = SHCSLib::getCode('ENGINEERING_APROC');
        $aproc_encode   = $request->aid;
        $project_encode = $request->pid;
        $aproc          = SHCSLib::decode($aproc_encode);
        $project_id     = SHCSLib::decode($project_encode);
        $getData        = $this->getData($project_id);//資料內容

        //如果沒有資料
        if(!in_array($aproc,array_keys($aprocAry)) || !isset($getData->id))
        {
            //dd($aproc,array_keys($aprocAry),$project_id,$getData->id);
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        }
        $A1         = $getData->name;
        $A2         = $getData->aproc;
        $A3         = $getData->edate;
        $A4         = isset($aprocAry[$getData->aproc])? $aprocAry[$getData->aproc] : '';
        $A5         = isset($aprocAry[$aproc])? $aprocAry[$aproc] : '';

        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html =$A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
        //延長工期
        if($aproc == 'R')
        {
            //結束日期
            $html = $form->date('edate',$A3);
            $html.= HtmlLib::Color(Lang::get($this->langText.'.engineering_1048'),'red',1);
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_9'),1);
        }
        //目前階段
        $html = HtmlLib::Color($A4,'',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_117'),1);
        //欲變更階段
        $html = HtmlLib::Color($A5,'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_118'),1);
        //說明
        $html = $form->textarea('memo','');
        $html.= HtmlLib::Color(Lang::get($this->langText.'.engineering_1019'),'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_119'),1);

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$project_encode);
        $submitDiv.= $form->hidden('change_aporc',$aproc_encode);
        $submitDiv.= $form->hidden('old_edate',$A3);
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
