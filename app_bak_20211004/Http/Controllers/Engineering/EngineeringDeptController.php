<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\EngineeringDeptTrait;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\Engineering\EngineeringLicenseTrait;
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
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_d;
use App\Model\Engineering\e_project_l;
use App\Model\Factory\b_factory_e;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class EngineeringDeptController extends Controller
{
    use EngineeringDeptTrait,EngineeringFactoryTrait,SessTraits;
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
        $this->hrefMain         = 'engineeringdept';
        $this->hrefLocal        = 'engineeringfactory';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'engineeringdept/';
        $this->hrefMainNew      = 'new_engineeringdept/';
        $this->routerPost       = 'postEngineeringdept';
        $this->routerPost2      = 'engineeringdeptCreate';

        $this->pageTitleMain    = Lang::get($this->langText.'.title26');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list26');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new26');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit26');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageLocalBtn     = Lang::get($this->langText.'.title23');//[按鈕]場地維護

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
        $hrefLocal= $this->hrefLocal.'?'.$param;
        $btnLocal = $this->pageLocalBtn;
        $hrefBack = $this->hrefHome;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $isActive = e_project::isExist($pid);
        $listAry = $this->getApiEngineeringDeptList($pid);
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
        $form->addLinkBtn($hrefLocal, $btnLocal,3); //返回
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHtml(HtmlLib::Color(Lang::get($this->langText.'.engineering_1047'),'red',1)); //說明
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_1')]; //專案
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_2')];//廠區
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_151')];//轄區部門
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_127')];//轄區監造
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_33')];//狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->project; //
                $name2        = HtmlLib::Color($value->store,'blue',1); //
                $name3        = HtmlLib::Color($value->name,'',1);; //
                $name4        = HtmlLib::Color($value->user,'',1);; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($isActive && $this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '90'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
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
            $A2         = $getData->store; //
            $A3         = $getData->be_dept_id; //


            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;

            $deptAry    = be_dept::getSelect(0,0,0,'Y');
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
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_2'),1);
        //部門
        $html = $form->select('be_dept_id',$deptAry,$A3);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_127'),1);
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_34'));
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
        $this->getBcustParam();
        $project_id = SHCSLib::decode($request->pid);
        $id         = SHCSLib::decode($request->id);
        $ip         = $request->ip();
        $menu       = $this->pageTitleMain;
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->pid || !$request->be_dept_id )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif(!$project_id)
        {
            $msg = Lang::get($this->langText.'.engineering_1010');
            return \Redirect::back()->withErrors($msg);
        }

        elseif($isNew) {
            if(!isset($request->emp) || !count($request->emp))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1069'))
                    ->withInput();
            }
            foreach ($request->emp as $uid => $val)
            {
                if(e_project_d::isExist($project_id,$request->be_dept_id,$uid,$id))
                {
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10113'))
                        ->withInput();
                }
            }
        }


        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['e_project_id']      = is_numeric($project_id) ? $project_id : 0;
        $upAry['be_dept_id']        = is_numeric($request->be_dept_id) ? $request->be_dept_id : 0;
        $upAry['emp']               = is_array($request->emp) ? $request->emp : [];
        $upAry['local']             = (isset($request->local) && is_array($request->local)) ? $request->local : [];
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';
        //dd($request->all(),$upAry);
        //新增
        if($isNew)
        {
            $ret = $this->createEngineeringDept($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setEngineeringDept($id,$upAry,$this->b_cust_id);
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
    public function create(Request $request,$urlid)
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
        $pid            = SHCSLib::decode($urlid);
        $deptAry        = be_dept::getSelect(0,0,0,'Y');
        $proejct        = e_project::getName($pid);
        $seTag          = HtmlLib::Color(Lang::get($this->langText.'.engineering_1072'),'red',1);
        $dept_id        = $request->be_dept_id;
        if(!$proejct)
        {
            $msg = Lang::get($this->langText.'.engineering_1010');
            return \Redirect::back()->withErrors($msg);
        }
        //view元件參數
        $hrefBack   = $this->hrefMain.'?pid='.$urlid;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header
        $postHref   = ($dept_id)? $this->routerPost : $this->routerPost2;
        $postid     = ($dept_id)? -1 : $urlid;
        $btnName    = ($dept_id)? 'btn_7' : 'btn_37';

        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($postHref,$postid),'POST',1,TRUE);
        //名稱
        $html = $proejct;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);

        if(!$dept_id)
        {
            //轄區監造
            $html = $form->select('be_dept_id',$deptAry,$dept_id);
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_151'),1);
        } else {
            //是否為全廠部門
            $isFullField = be_dept::isFullField($dept_id);
            //已加入的轄區監造
            $existEmptAry   = e_project_d::getSelect($pid,0,1,0);
            $existEmptIDAry = array_keys($existEmptAry);
            //轄區監造
            $html = isset($deptAry[$dept_id])? $deptAry[$dept_id] : '';
            if($isFullField == 'Y') $html.= HtmlLib::Color(Lang::get($this->langText.'.engineering_1071'),'',1);
            $html.= $form->hidden('be_dept_id',$dept_id);
            $html.= $form->hidden('isFullField',$isFullField);
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_151').$seTag,1);

            //轄區監造
            $html   = '';
            $i      = 0;
            $empAry = b_cust_e::getSelect(0,$dept_id,0,0,'Y',0);
            //dd($existEmptAry,$existEmptIDAry,$pid,$empAry);
            if(count($empAry))
            {
                foreach ($empAry as $uid => $name)
                {
                    if(!in_array($uid,$existEmptIDAry))
                    {
                        $i++;
                        $html .= $form->checkbox('emp['.$uid.']',$uid).$name.' ';
                        if($i % 3 == 0) $html .= '<br/>';
                    }
                }
            }
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_127'),1);
            //場地
            $html   = HtmlLib::Color(Lang::get($this->langText.'.engineering_1070'),'red',1);
            $i      = 0;
            $localAry = b_factory_e::getDeptSelect($dept_id,0);
            if(count($localAry))
            {
                $html = '';
                foreach ($localAry as $fid => $name)
                {
                    $i++;
                    $html .= $form->checkbox('local['.$fid.']',$fid).$name.' ';
                    if($i % 3 == 0) $html .= '<br/>';
                }
            }

            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_109'));
        }


        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.'.$btnName),'1','agreeY').'&nbsp;';
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
