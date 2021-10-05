<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\EngineeringCarTrait;
use App\Http\Traits\Engineering\EngineeringDeptTrait;
use App\Http\Traits\Engineering\EngineeringLicenseTrait;
use App\Http\Traits\Factory\CarTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_d;
use App\Model\Engineering\e_project_l;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class EngineeringCarController extends Controller
{
    use EngineeringCarTrait,CarTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | EngineeringDeptController
    |--------------------------------------------------------------------------
    |
    | 工程案件-監造部門(轄區監造) 維護
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
        $this->hrefMain         = 'engineeringcar';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'engineeringcar/';
        $this->hrefMainNew      = 'new_engineeringcar/';
        $this->routerPost       = 'postEngineeringcar';

        $this->pageTitleMain    = Lang::get($this->langText.'.list27');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list27');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.list27');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.list27');//編輯

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
        $closeAry = SHCSLib::getCode('CLOSE');
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
        $listAry = $this->getApiEngineeringCarList($pid);
        Session::put($this->hrefMain.'.project_id',$request->pid);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHtml(HtmlLib::Color(Lang::get($this->langText.'.engineering_1054'),'red',1)); //說明
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_1')]; //專案
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_93')];//承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_129')];//車牌
        $heads[] = ['title'=>Lang::get('sys_supply.supply_114')];//車牌
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_130')];//車輛分類
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_131')];//進廠有效日(起)
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_132')];//進廠有效日(迄)
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_33')];//狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->project; //
                $name2        = HtmlLib::Color($value->supply,'blue',1); //
                $name3        = HtmlLib::Color($value->car_no,'',1);; //
                $name4        = $value->car_type_name; //
                $name5        = $value->door_sdate; //
                $name6        = $value->door_edate; //
                $name7        = $value->car_memo; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($isActive && $this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '7'=>[ 'name'=> $name7],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '6'=>[ 'name'=> $name6],
                            '90'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
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
            $A2         = $getData->supply; //
            $A3         = HtmlLib::Color($getData->car_no,'blue',1); //
            $A4         = $getData->car_type_name; //
            $A5         = $getData->door_sdate; //
            $A6         = $getData->door_edate; //
            $A7         = $getData->project_edate; //
            $A8         = $getData->car_memo; //


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
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
        //廠區
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_93'),1);
        //車牌
        $html = $A3.'('.$A4.')';
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_129'),1);
        //
        $html = $A8;
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_114'),1);
        //進廠有效日(起)
        $html = $A5;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_131'),1);
        //進廠有效日(迄)
        $html = $this->isSuperUser ? $html = $form->date('door_edate',$A6,2) : $A6;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_132'),1);
        //進廠有效日(起)
        $html = $A7;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_112'),1);
        //停用
        if($this->isSuperUser)
        {
            $html = $form->checkbox('isClose','Y',$A99);
            $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_34'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('pid',$request->pid);
        $submitDiv.= $form->hidden('project_edate',$A7);
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
            $("#sdate,#door_edate").datepicker({
                format: "yyyy-mm-dd",
                startDate : "today",
                endDate : new Date($("#project_edate").val()),
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
        if( !$request->has('agreeY') || !$request->id || !$request->pid || !$request->door_edate )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif($request->isClose != 'Y')
        {
            if(!CheckLib::isDate($request->door_edate))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_99').'，'.Lang::get('sys_supply.supply_1004'))
                    ->withInput();
            }
            elseif(strtotime($request->door_edate) > strtotime($request->project_edate))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1038',['date'=>$request->project_edate]))
                    ->withInput();
            }
        }

        $this->getBcustParam();
        $project_id = SHCSLib::decode($request->pid);
        $id         = SHCSLib::decode($request->id);
        $ip         = $request->ip();
        $menu       = $this->pageTitleMain;
        if(!$project_id)
        {
            $msg = Lang::get($this->langText.'.engineering_1010');
            return \Redirect::back()->withErrors($msg);
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        if($request->isClose == 'Y')
        {
            $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';
        } else {
            $upAry['door_edate']        = ($request->door_edate) ? $request->door_edate :'';
        }

        //新增
        if($isNew)
        {
            $ret = 0;//$this->createEngineeringDept($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setEngineeringCar($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'e_project_l',$id);

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
        $pid        = SHCSLib::decode($urlid);
        $deptAry    = be_dept::getSelect(0,0,0,'Y');
        $proejct    = e_project::getName($pid);
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
        //轄區監造
        $html = $form->select('be_dept_id',$deptAry,1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_127'),1);

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('pid',$urlid);
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

}
