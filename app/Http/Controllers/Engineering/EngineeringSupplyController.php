<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Engineering\EngineeringSupplyTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_a;
use App\Model\Engineering\e_project_c;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_project_s;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\Supply\b_supply_member_ei;
use App\Model\View\view_door_supply_member;
use App\Model\User;
use App\Model\WorkPermit\wp_work_worker;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class EngineeringSupplyController extends Controller
{
    use EngineeringSupplyTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | EngineeringSupplyController
    |--------------------------------------------------------------------------
    |
    | 承攬項目之協力廠商 維護
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
        $this->hrefHome         = 'engineering';
        $this->hrefMain         = 'engineeringsupply';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'engineeringsupply/';
        $this->hrefMainNew      = 'new_engineeringsupply/';
        $this->routerPost       = 'postEngineeringsupply';

        $this->pageTitleMain    = Lang::get($this->langText.'.title26');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list26');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new26');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit26');//編輯

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
        $pid      = SHCSLib::decode($request->pid);
        if(!$pid && !(is_numeric($pid) && $pid > 0))
        {
            $msg = Lang::get($this->langText.'.engineering_1010');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = 'pid='.$request->pid;
        }

        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
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
        $listAry = $this->getApiEngineeringSupplyList($pid);
        Session::put($this->hrefMain.'.project_id',$request->pid);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($isActive)
        {
            $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        }
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回

        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        //$heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_107')]; //承攬商

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->b_supply; //

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['1'=>[ 'name'=> $name1],
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
        //參數
        $js = $contents ='';
        $pid      = SHCSLib::decode($request->pid);
        if(!$pid && !(is_numeric($pid) && $pid > 0))
        {
            $msg = Lang::get($this->langText.'.engineering_1010');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = '?pid='.$request->pid;
        }
        $id = SHCSLib::decode($urlid);
        $jobkindAry = SHCSLib::getCode('JOB_KIND');
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
            $A1         = e_project::getName($pid);//
            $A2         = $getData->b_supply; //
            $A3         = $getData->e_project_id; //
            $A4         = $getData->b_supply_id; //
            $hasmember  = e_project_s::hasMember($A3,$A4);


            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //承攬項目
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
        //承攬商
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_107'),1);

        //停用
        if($hasmember)
        {
            $html = HtmlLib::Color(Lang::get($this->langText.'.engineering_1044'),'red',1);
            $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_34'));
        } else {
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
        $submitDiv   = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv  .= $form->linkbtn($hrefBack, $btnBack,2);

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
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->pid )
        {
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
            if(!$request->b_supply_id){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1041'))
                    ->withInput();
            }
        } else {
            if($request->isClose == 'Y')
            {
                //如果有加入的承攬商成員
                $hasmember = e_project_s::hasMember($project_id,$request->b_supply_id);
                if($hasmember)
                {
                    $msg = Lang::get($this->langText.'.engineering_1044');
                    return \Redirect::back()->withErrors($msg);
                }
            }
        }

        $upAry = array();
        $upAry['e_project_id']      = is_numeric($project_id) ? $project_id : 0;
        if(!$isNew)
        {
            $upAry['id']            = $id;
            $upAry['isClose']       = ($request->isClose == 'Y')? 'Y' : 'N';
        } else {
            $upAry['b_supply_id']       = $request->b_supply_id;
        }

        //新增
        if($isNew)
        {
            $ret = $this->createEngineeringSupply($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setEngineeringSupply($id,$upAry,$this->b_cust_id);
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
        //參數
        $js = $contents = '';
        $pid = SHCSLib::decode($urlid);
        //承攬項目＆承攬商
        $proejct        = e_project::getName($pid);
        $sid            = e_project::getSupply($pid);
        $supply         = b_supply::getName($sid);
        $supplyAry2     = e_project_a::getIDAry($pid);
        $supplyAry2[]   = $sid;
        $supplyAry      = b_supply::getSelect($supplyAry2);
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
        //承攬商
        $html = $supply;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_93'),1);
        //協力廠商
        $html = $form->select('b_supply_id',$supplyAry);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_107'),1);

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

}
