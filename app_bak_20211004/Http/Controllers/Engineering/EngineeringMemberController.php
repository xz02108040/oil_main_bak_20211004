<?php

namespace App\Http\Controllers\Engineering;

use Auth;
use Lang;
use Session;
use App\Lib\LogLib;
use App\Model\User;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Lib\TableLib;
use App\Lib\ContentLib;
use App\Model\sys_param;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use App\Model\Bcust\b_cust_a;
use App\Http\Traits\SessTraits;
use App\Model\View\view_used_rfid;
use App\Http\Controllers\Controller;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_c;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\et_traning_m;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\Supply\b_supply_member_l;
use App\Model\Supply\b_supply_member_ei;
use App\Model\View\view_supply_etraning;
use App\Model\WorkPermit\wp_work_worker;
use App\Model\View\view_door_supply_member;
use App\Model\Engineering\e_project_license;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;

class EngineeringMemberController extends Controller
{
    use EngineeringMemberTrait,EngineeringTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | EngineeringMemberController
    |--------------------------------------------------------------------------
    |
    | 工程案件之承攬商成員 維護
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
        $this->hrefHome         = 'engineering';
        $this->hrefMain         = 'engineeringmember';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'engineeringmember/';
        $this->hrefMainNew      = 'new_engineeringmember/';
        $this->routerPost       = 'postEngineeringmember';

        $this->pageTitleMain    = Lang::get($this->langText.'.title22');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list22');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new22');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit22');//編輯

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
        $no  = 0;
        $passAry  = SHCSLib::getCode('PASS');
        $overAry  = SHCSLib::getCode('DATE_OVER');
        $pairAry  = SHCSLib::getCode('RFID_CARD_PAIRD');
        $identityAry  = SHCSLib::getCode('IDENTITY_OVER');
        $pid      = SHCSLib::decode($request->pid);
        if(!$pid && !(is_numeric($pid) && $pid > 0))
        {
            $msg = Lang::get($this->langText.'.engineering_1010');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = 'pid='.$request->pid;
        }
        //view元件參數
        $projectN = e_project::getName($pid,2);
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $projectN = $projectN ? $Icon.'<b>'.$projectN.'</b>' : '';
        $tbTitle  = $this->pageTitleList.$projectN;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew.$request->pid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefHome;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $isActive = e_project::isExist($pid);
        $listAry = $this->getApiEngineeringMemberList($pid);
        Session::put($this->hrefMain.'.project_id',$request->pid);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($isActive && $this->isWirte == 'Y')
        {
            $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        }
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回

        $contbox = new ContentLib();
        //工程案件所需工程身份
        $title1  = Lang::get($this->langText.'.engineering_1050');
        $cont1   = e_project_l::getAllName($pid,1);
        //工程案件所需教育身份
        //$title2  = Lang::get($this->langText.'.engineering_1051');
        //$cont2   = e_project_c::getAllName($pid);
        //說明
        $title3  = Lang::get('sys_base.base_10017');
        $cont3   = Lang::get($this->langText.'.engineering_1030');
        $form->addHtml($contbox->solid_box_3_row(1,4,$title1,$cont1,3,1,4,'','',4,3,4,$title3,$cont3,0)); //說明

        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        //$heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_93')]; //承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_14')];//承攬商成員
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_20')]; //專案角色
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_136')]; //尿檢
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_27')]; //工作身份
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_169')]; //工程身份資格
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_41')]; //教育訓練
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_28')]; //工程案件資格
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_26')]; //教育訓練資格
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_29')]; //配卡資格
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_30')]; //違規
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_25')]; //通行資格

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->b_supply; //
                $mobile       = ($value->mobile)? HtmlLib::Color('('.$value->mobile.')','',1) : '';
                $name2        = $value->b_cust_id.'<br/>'.$value->user.$mobile;
                $name3        = $value->job_kind_name; //
                $name11       = $value->ut_name.'<br/>'.$value->sdate; //
                $name6        = $value->identitylist; //

                //工程身份是否正常
                $isApplyYN      = e_project_license::getUserlicense($pid, $value->b_cust_id);
                $name12       = isset($identityAry[$isApplyYN]) ? $identityAry[$isApplyYN] : ''; //
                $name12C      = $isApplyYN == 'Y' ? 2 : 5; //

                $name10       = $value->courselist; //

                $name4        = isset($passAry[$value->isPass])? $passAry[$value->isPass] : ''; //
                $name4C       = $value->isPass == 'Y'? 2 : 5; //
                //工程案件是否過期
                $name7        = isset($overAry[$value->isOver])? $overAry[$value->isOver] : ''; //
                $name7C       = $value->isOver == 'Y'? 2 : 5; //
                //是否配卡
                $name8        = isset($pairAry[$value->isPair])? $pairAry[$value->isPair] : ''; //
                $name8C       = $value->isPair == 'Y'? 2 : 5; //
                //違規
                $name9        = $value->isViolaction; //
                $name9C       = $value->isViolaction ? 5 : 1; //
                //白名單
                $isWhiteList  = $value->isViolaction ? 'N' : $value->isWhiteList;
                $name5        = isset($passAry[$isWhiteList])? $passAry[$isWhiteList] : ''; //
                $name5C       = $isWhiteList == 'Y'? 2 : 5; //

                //按鈕
                $btn          = ($isActive && $this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '6'=>[ 'name'=> $name11],
                            '4'=>[ 'name'=> $name6],
                            '7'=>[ 'name'=> $name12,'label'=>$name12C],
                            '5'=>[ 'name'=> $name10],
                            '13'=>[ 'name'=> $name7,'label'=>$name7C],
                            '11'=>[ 'name'=> $name4,'label'=>$name4C],
                            '14'=>[ 'name'=> $name8,'label'=>$name8C],
                            '15'=>[ 'name'=> $name9,'label'=>$name9C],
                            '12'=>[ 'name'=> $name5,'label'=>$name5C],
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
        $pid      = SHCSLib::decode($request->pid);
        if(!$pid && !(is_numeric($pid) && $pid > 0))
        {
            $msg = Lang::get($this->langText.'.engineering_1010');
            return \Redirect::back()->withErrors($msg);
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            $param = '?pid='.$request->pid;
        }
        $id = SHCSLib::decode($urlid);
        //view元件參數
        $hrefBack       = $this->hrefMain.$param;
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
            $A2         = $getData->b_supply; //
            $A3         = $getData->user.'('.$getData->cpc_tag.')'; //
            $A4         = $getData->job_kind; //
            $A5         = $getData->b_cust_id; //
            $A6         = $getData->ut_name; //
            $A7         = $getData->ut_sdate; //
            $A8         = $getData->ut_edate; //
            $A9         = $getData->isUT; //
            $A10        = $getData->cpc_tag; //
            $A11        = $getData->isPair; //
            $A12        = ($getData->isUT == 'Y')? true : false;  //
            $myIdentity = e_project_license::isWhoIdenttity($getData->e_project_id,$getData->b_cust_id);
            $extidAry   = ($myIdentity == 1)? [3,5] : (($myIdentity == 2)? [2,5] : [2,3,5]);


            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $jobkindAry = SHCSLib::getCode('JOB_KIND',1,0,$extidAry);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //工程案件
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
        //承攬商
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_93'),1);
        //承攬商成員
        $html = $A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_14'),1);
        //專案角色
        if($this->isRootDept || $this->isRoot)
        {
            $html = $form->select('job_kind',$jobkindAry,$A4);
        } else {
            $html = isset($jobkindAry[$A4])? $jobkindAry[$A4] : '';
        }
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_20'),1);
        
        //尿檢
        if($A11 == 'Y' && $this->isRoot != 'Y')
        {
            $html = $A6;
        } else {
            $html = $form->checkbox('isUT1','Y',$A12);
        }
        
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_136'),1);
        if($A9 != 'C')
        {
            //尿檢日
            $html = $A7;
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_137'),1);
            //下次尿檢日
            $html = $A8;
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_138'),1);
        }
        //專業證照 對應 工程身分
        //TODO
        //$html = $form->select('job_kind',$jobkindAry,$A4);
        //$form->add('nameT3', $html,Lang::get($this->langText.'.engineering_20'),1);
        //停用
        $html = ($getData->isPair == 'Y')? HtmlLib::Color(Lang::get('sys_base.base_10947'),'red',1) : $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_34'));
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv   = ($this->isRootDept || $this->isRoot)? $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;' : '';
        $submitDiv  .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('pid',$request->pid);
        $submitDiv.= $form->hidden('uid',$A5);
        $submitDiv.= $form->hidden('isUT',$A9);
        $submitDiv.= $form->hidden('cpc_tag',$A10);
        $submitDiv.= $form->hidden('isPair',$A11);
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
        if( !$request->has('agreeY') || !$request->id || !$request->pid || !$request->job_kind )
        {
            //必填內容不完整
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $project_id = SHCSLib::decode($request->pid);
            $id         = SHCSLib::decode($request->id);
            $ip         = $request->ip();
            $menu       = $this->pageTitleMain;
        }
        if(!$project_id)
        {
            $msg = Lang::get($this->langText.'.engineering_1010');
            return \Redirect::back()->withErrors($msg);
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        //如果沒有選擇成員
        if($isNew)
        {
            if(!count($request->member)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1017'))
                    ->withInput();
            }
            //2019-12-12 如果有
            foreach ($request->member as $uid)
            {
                list($hasWork,$hasWorkType) = wp_work_worker::hasInWork($project_id,$uid);
                if($hasWork)
                {
                    $errCode = ($hasWorkType == 2)? 'engineering_1040' : 'engineering_1039';
                    $msg = Lang::get($this->langText.'.'.$errCode);
                    return \Redirect::back()->withErrors($msg);
                } elseif ($UserInOutResult == 'Y') {
                    //該人員尚未離場！
                    return \Redirect::back()
                    ->withErrors(Lang::get($this->langText . '.engineering_1082'))
                    ->withInput();
                }
            }
        } else {
            if($request->isClose == 'Y')
            {
                list($hasWork,$hasWorkType) = wp_work_worker::hasInWork($project_id,$request->uid);
                list($UserInOutResult,$door_stamp) = rept_doorinout_t::getUserInOutResult($request->uid);

                //該成員正參與工作許可證，請先作廢該工作許可證/通知該成員離廠後再變更
                if ($hasWork) {
                    $errCode = ($hasWorkType == 2) ? 'engineering_1040' : 'engineering_1039';
                    $msg = Lang::get($this->langText . '.' . $errCode);
                    return \Redirect::back()->withErrors($msg);
                } elseif ($UserInOutResult == 'Y') {
                    //該人員尚未離場！
                    return \Redirect::back()
                    ->withErrors(Lang::get($this->langText . '.engineering_1082'))
                    ->withInput();
                }
            }
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
            $isUT     = isset($request->isUT)? $request->isUT : 'C';
            $isUT1    = (isset($request->isUT1))? ($request->isUT1? 'Y' : 'C') : 'C';
            $job_kind = isset($request->job_kind)? $request->job_kind : 1;
            $cpc_tag  = isset($request->cpc_tag)? $request->cpc_tag : 'C';
            if($job_kind == 2)
            {
                $cpc_tag = 'A';
            } elseif ($job_kind == 3)
            {
                $cpc_tag = 'B';
            } else {
                $cpc_tag  = ($job_kind == 4)? 'E' : (($isUT == 'C')? 'C' : 'D');
            }
            $upAry['job_kind']          = $job_kind;
            $upAry['cpc_tag']           = $cpc_tag;
//            dd($request->all(),$upAry);
            if(in_array($isUT1,['Y','C']) && $isUT1 != $isUT)
            {
                $upAry['isUT']          = $isUT1;
            }
        } else {
            $upAry['e_project_id']      = is_numeric($project_id) ? $project_id : 0;
            $upAry['b_supply_id']       = $request->sid;
            $upAry['member']            = $request->member;
        }

        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';
        //新增
        if($isNew)
        {
            $ret = $this->createEngineeringMemberGroup($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setEngineeringMember($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'e_project_s',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?pid='.$request->pid);
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
    public function create($urlid)
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
        $pid = SHCSLib::decode($urlid);
        //工程案件＆承攬商
        $proejct    = e_project::getName($pid);
        $sid        = e_project::getSupply($pid);
        $supply     = b_supply::getName($sid);
        if(!$proejct)
        {
            $msg = Lang::get($this->langText.'.engineering_1010');
            return \Redirect::back()->withErrors($msg);
        }
        //view元件參數
        $hrefBack   = $this->hrefMain.'?pid='.$urlid;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html = $proejct;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
        //承商
        $html = $supply;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_93'),1);
        //說明
        $html = HtmlLib::Color(Lang::get($this->langText.'.engineering_1032'),'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_101'),1);

        /**
         * 人員選擇器
         */
        //承攬商成員
        $mebmer1    = b_supply_member::getSelect($sid,1,'',0);
        //目前已經參與 工程案件的成員
        $mebmer2    = view_door_supply_member::getSupplyMemberSelect($sid);
        foreach ($mebmer2 as $b_cust_id => $value)
        {
            if(isset($mebmer1[$b_cust_id]))
            {
                unset($mebmer1[$b_cust_id]);
            }
        }
        //報名
        $table = new TableLib();
        //標題
        $heads[] = ['title'=>Lang::get('sys_supply.supply_43')]; //
        $heads[] = ['title'=>Lang::get('sys_supply.supply_19')]; //成員

        $table->addHead($heads,0);
        if(count($mebmer1))
        {
            foreach($mebmer1 as $uid => $value)
            {
                $name1        = $form->checkbox('member[]',$uid); //
                $name2        = $value; //

                $tBody[] = ['0'=>[ 'name'=> $name1],
                            '1'=>[ 'name'=> $name2],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $form->add('nameT1', $table->output(),Lang::get($this->langText.'.engineering_94'));
        unset($table,$heads,$tBody);


        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('pid',$urlid);
        $submitDiv.= $form->hidden('sid',$sid);
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

    protected function report(Request $request,$urlid)
    {
        $showAry    = [];
        $SEXARY     = SHCSLib::getCode('SEX');
        $LICENSE_1  = sys_param::getParam('LICENSE_',1);
        $LICENSE_2  = sys_param::getParam('LICENSE_',14);

        $project_id = SHCSLib::decode($urlid);
        $projectData= $this->getEngineeringData($project_id);
        $jobKindAry     = SHCSLib::getCode('JOB_KIND', 0);

        //title
        $showAry['project_name']    = $projectData->name;
        $showAry['project_no']      = $projectData->project_no;
        $showAry['supply']          = $projectData->supply;
        $sdateAry                   = explode('-',SHCSLib::chgTaiwanDate($projectData->sdate));
        $showAry['sdate']           = $sdateAry;
        $edateAry                   = explode('-',SHCSLib::chgTaiwanDate($projectData->edate));
        $showAry['edate']           = $edateAry;

        // 工安課印章
        $StampImg = $this->genImgHtml(SHCSLib::tranImgToBase64Img('images/WorkSafetyClass/Stamp.jpg'),'62','85');

        $memberAry = [];
        $listAry = $this->getApiEngineeringMemberList($project_id);
        $page = 1;
        $max_show_mun = 4;
        $i = $no = 0;
        $totalAmt = count($listAry);
        $modNum   = $totalAmt % $max_show_mun;
        if(!$totalAmt) $modNum = $max_show_mun;
        if($totalAmt && $modNum && $modNum != $max_show_mun) $modNum = $max_show_mun - $modNum;

        $page2 = 1;
        $max_show_mun2 = 8;
        $i2 = 0;
        $modNum2   = $totalAmt % $max_show_mun2;
        if(!$totalAmt) $modNum2 = $max_show_mun2;
        if($totalAmt && $modNum2 && $modNum2 != $max_show_mun2) $modNum2 = $max_show_mun2 - $modNum2;

        if($totalAmt)
        {
            foreach ($listAry as $val)
            {
                $i++;
                $i2++;
                $no++;
                $tmp = [];
                $tmp['no']          = $no;
                $tmp['id']          = $val->b_cust_id;
                $tmp['name']        = $val->user;
                $tmp['bcid']        = $val->bc_id;
                $tmp['kin']         = $val->kin_tel;
                $tmp['sex']         = isset($SEXARY[$val->sex])? $SEXARY[$val->sex] : '' ;
                $tmp['birth']       = ['','',''];
                if(CheckLib::isDate($val->birth) && $val->birth != '1970-01-01') {
                    $birthAry           = explode('-',SHCSLib::chgTaiwanDate($val->birth));
                    $tmp['birth']       = $birthAry;
                }
                $tmp['birthday']    = !empty($tmp['birth'][0]) && !empty($tmp['birth'][1]) && !empty($tmp['birth'][2]) ? $tmp['birth'][0] . '-' . $tmp['birth'][1] . '-' . $tmp['birth'][2] : '';

                $tmp['address']     = b_cust_a::getAddress($val->b_cust_id);
                //證照
                $identity1          = b_supply_member_l::getLicense($val->b_cust_id,$LICENSE_1);
                $identity1_h        = substr($identity1,0,2);
                $tmp['identity1'][0]= ($identity1_h == '01')? $identity1 : '';
                $tmp['identity1'][1]= ($identity1_h == '05')? $identity1 : '';
                $tmp['identity1'][2]= ($identity1_h == '02')? $identity1 : '';
                $tmp['identity2']   = b_supply_member_l::getLicense($val->b_cust_id,$LICENSE_2);
                //教育訓練
                $tmp['cdate']       = et_traning_m::getPassDate(1,$val->b_cust_id);
                //安全衛生訓練到期日
                $valid_date = view_supply_etraning::where('b_cust_id', $val->b_cust_id)->select('valid_date')->first();
                $tmp['edate_img']   = isset($valid_date) ? '有效期限' . $valid_date->valid_date . ' ' . $StampImg : '';
                $tmp['edate']       = isset($valid_date) ? $valid_date->valid_date : '';
                //卡片
                list($rfid_id,$rfid_name,$rfidcode) = view_used_rfid::isUserExist($val->b_cust_id);
                $tmp['rfid']        = $rfid_id ? $rfidcode : '';
                // 身分
                $tmp['cpc_tag_report'] = !empty($val->cpc_tag_report) ? $val->cpc_tag_report : '';

                // 尿檢
                $tmp['isUTY'] =  ($val->isUT == 'Y' && isset($val->isUT)) ? '&#8718;' : '';
                $tmp['isUTN'] =  ($val->isUT == 'C' && isset($val->isUT)) ? '&#8718;' : '';

                $memberAry[$page][$i] = $tmp;
                if($max_show_mun == $i)
                {
                    $page++;
                    $i = 0;
                }

                $memberAry2[$page2][$i2] = $tmp;
                if($max_show_mun2 == $i2)
                {
                    $page2++;
                    $i2 = 0;
                }
            }
        }
        for($j = 1; $j <= $modNum; $j++)
        {
            $no++;
            $tmp = [];
            $tmp['no']          = $no;
            $tmp['id']          = '';
            $tmp['name']        = '';
            $tmp['bcid']        = '';
            $tmp['sex']         = '';
            $tmp['kin']         = '';
            $tmp['birth']       = ['','',''];
            $tmp['address']     = '';
            $tmp['identity1']   = ['','',''];
            $tmp['identity2']   = '';
            $tmp['cdate']       = '';
            $tmp['edate_img']   = '';
            $tmp['edate']       = '';
            $tmp['rfid']        = '';
            $tmp['birthday']    = '';
            $tmp['cpc_tag_report']    = '';
            $tmp['isUTY']       = '';
            $tmp['isUTN']       = '';

            $memberAry[$page][] = $tmp;
        }

        for($j = 1; $j <= $modNum2; $j++)
        {
            $no++;
            $tmp = [];
            $tmp['no']          = $no;
            $tmp['id']          = '';
            $tmp['name']        = '';
            $tmp['bcid']        = '';
            $tmp['sex']         = '';
            $tmp['kin']         = '';
            $tmp['birth']       = ['','',''];
            $tmp['address']     = '';
            $tmp['identity1']   = ['','',''];
            $tmp['identity2']   = '';
            $tmp['cdate']       = '';
            $tmp['edate_img']   = '';
            $tmp['edate']       = '';
            $tmp['rfid']        = '';
            $tmp['birthday']    = '';
            $tmp['cpc_tag_report']    = '';
            $tmp['isUTY']       = '';
            $tmp['isUTN']       = '';
            $memberAry2[$page2][] = $tmp;
        }

        $showAry['totalPage'] = count($memberAry);
        $showAry['memberAry'] = $memberAry;
        $showAry['totalPage2'] = count($memberAry2);
        $showAry['memberAry2'] = $memberAry2;
        if($request->showtest) dd($totalAmt,$modNum,$showAry);
        return view('report.cpc_roster',$showAry);
    }

    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl,$maxWidth=80,$maxHeight=25)
    {
        return '<img src="'.$imgUrl.'" class="sign_img" width="'.$maxWidth.'"   height="'.$maxHeight.'"><span class="time_at">'.'</span>';
    }
}
