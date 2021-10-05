<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_f;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\View\view_door_supply_member;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class EngineeringFactoryController extends Controller
{
    use EngineeringFactoryTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | EngineeringFactoryController
    |--------------------------------------------------------------------------
    |
    | 工程案件之負責廠區 維護
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
        $this->hrefHome         = 'engineeringdept';
        $this->hrefMain         = 'engineeringfactory';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'engineeringfactory/';
        $this->hrefMainNew      = 'new_engineeringfactory/';
        $this->routerPost       = 'postEngineeringfactory';
        $this->routerPost2      = 'engineeringfactoryCreate';

        $this->pageTitleMain    = Lang::get($this->langText.'.title23');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list23');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new23');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit23');//編輯

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
        $hrefBack = $this->hrefHome.'?'.$param;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $isActive = ($this->isWirte == 'Y')? e_project::isExist($pid) : 0;
        $listAry = $this->getApiEngineeringFactoryList($pid);
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
        $form->addHr();
        $form->addRow(HtmlLib::Color(Lang::get($this->langText.'.engineering_1073'),'red',1),8,1);
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_2')];  //廠區
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_109')];  //場地

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->store; //
                $name2        = $value->local; //

                //按鈕
                $btn          = ($isActive && $this->isWirte == 'Y')?HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
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
            $A2         = $getData->store; ///
            $A3         = $getData->local; ///


            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //工程案件
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
        //廠區
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_2'),1);
        //廠區
        $html = $A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_109'),1);
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
        if($isNew && !count($request->store)){
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1027'))
                ->withInput();
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['e_project_id']      = is_numeric($project_id) ? $project_id : 0;
        $upAry['store']             = $request->store;
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createEngineeringFactoryGroup($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setEngineeringFactory($id,$upAry,$this->b_cust_id);
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
        $pid = SHCSLib::decode($urlid);
        $storeAry = b_factory::getSelect(1,'Y'); //廠區陣列
        $b_factory_id = $request->b_factory_id;
        //工程案件＆承攬商
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
        $postHref   = ($b_factory_id)? $this->routerPost : $this->routerPost2;
        $postid     = ($b_factory_id)? -1 : $urlid;
        $btnName    = ($b_factory_id)? 'btn_7' : 'btn_37';
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($postHref,$postid),'POST',1,TRUE);
        //名稱
        $html = $proejct;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);

        //廠區
        $html = ($b_factory_id)? (b_factory::getName($b_factory_id).$form->hidden('b_factory_id',$b_factory_id)) : $form->select('b_factory_id',$storeAry);
        if(!$b_factory_id) $html .= HtmlLib::Color(Lang::get($this->langText.'.engineering_1053'),'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_2'),1);

        //選擇場地
        if($b_factory_id)
        {
            //承攬商成員
            $store1    = b_factory_a::getSelect($b_factory_id);
            //目前已經參與 工程案件的廠區
            $store2    = e_project_f::getSelect($pid,$b_factory_id);
            foreach ($store2 as $id => $value)
            {
                if(isset($store1[$id]))
                {
                    unset($store1[$id]);
                }
            }
            //報名
            $table = new TableLib();
            //標題
            $heads[] = ['title'=>Lang::get('sys_supply.supply_45'),'style'=>'width:10%']; //
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_109')]; //場地

            $table->addHead($heads,0);
            if(count($store1))
            {
                foreach($store1 as $id => $value)
                {
                    $name1        = $form->checkbox('store[]',$id,'','store_box'); //
                    $name2        = $value; //

                    $tBody[] = ['0'=>[ 'name'=> $name1],
                                '1'=>[ 'name'=> $name2],
                    ];
                }
                $table->addBody($tBody);
            }
            //輸出
            $checkAllBtn = HtmlLib::btn('#',Lang::get('sys_btn.btn_77'),2,'checkAllBtn','','checkAll()');
            $memo        = HtmlLib::Color(Lang::get($this->langText.'.engineering_1074'),'red',1);
            $form->add('nameT1', $checkAllBtn.$memo.$table->output(),Lang::get($this->langText.'.engineering_94'));
            unset($table,$heads,$tBody);
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
        });
        var clicked = false;
        function checkAll()
        {
            $(".store_box").prop("checked", !clicked);
            clicked = !clicked;
            btn = clicked ? "'.Lang::get('sys_btn.btn_78').'" : "'.Lang::get('sys_btn.btn_77').'";
            $("#checkAllBtn").html(btn);
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
