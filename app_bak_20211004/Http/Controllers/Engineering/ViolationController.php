<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\ViolationLawTrait;
use App\Http\Traits\Engineering\ViolationTrait;
use App\Http\Traits\Engineering\ViolationTypeTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_violation_law;
use App\Model\Engineering\e_violation_punish;
use App\Model\Engineering\e_violation_type;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class ViolationController extends Controller
{
    use ViolationTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ViolationController
    |--------------------------------------------------------------------------
    |
    | 違規事項 維護
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
        $this->hrefMain         = 'eviolation';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'eviolation/';
        $this->hrefMainNew      = 'new_eviolation';
        $this->routerPost       = 'postEViolation';

        $this->pageTitleMain    = Lang::get($this->langText.'.title10');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list10');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new10');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit10');//編輯

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
        $listAry = $this->getApiViolationList();
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_54')]; //代碼
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_31')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_52')]; //分類
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_53')]; //法規
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_55')]; //罰則
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_56')]; //限制進出
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_57')]; //限制天數
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_33')]; //狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->violation_code; //
                $name2        = $value->name; //
                $name3        = $value->violation_type; //
                $name4        = $value->violation_law; //
                $name5        = $value->violation_punish; //
                $name6        = $value->isControl == 'Y' ? 'N' : 'Y' ; //
                $name7        = $value->limit_day > 0 ? $value->limit_day : ''; //
                $isLimitColor = $name7 ? 1 : 0 ; //停用顏色
                $isControl      = isset($closeAry[$name6])? $closeAry[$name6] : '' ; //停用
                $isControlColor = $name6 == 'Y' ? 2 : 5 ; //停用顏色
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '11'=>[ 'name'=> $isControl,'label'=>$isControlColor],
                            '12'=>[ 'name'=> $name7,'label'=>$isLimitColor],
                            '21'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
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
        $typeAry    = e_violation_type::getSelect();
        $lawAry     = e_violation_law::getSelect();
        $punishAry  = e_violation_punish::getSelect();
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
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //資料明細
            $A1         = $getData->name; //
            $A2         = $getData->violation_code; //
            $A3         = $getData->e_violation_type_id; //
            $A4         = $getData->e_violation_law_id; //
            $A5         = $getData->e_violation_punish_id; //
            $A6         = ($getData->isControl == 'Y')? true : false;
            $A7         = $getData->limit; //


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
        $html = $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_31'),1);
        //代碼
        $html = $form->text('violation_code',$A2);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_54'),1);
        //分類
        $html = $form->select('type_id',$typeAry,$A3);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_52'),1);
        //法規
        $html = $form->select('law_id',$lawAry,$A4);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_53'),1);
        //罰條
        $html = $form->select('punish_id',$punishAry,$A5);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_55'),1);
        //是否管制進出
        $html = $form->checkbox('isControl','Y',$A6);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_56'));
        //限制天數
        $html = $form->text('limit_day',$A7);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_57'));
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
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
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
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
        if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->violation_code )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif($request->isClose == 'N')
        {
            if(!$request->type_id || !$request->law_id || !$request->punish_id )
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1011'))
                    ->withInput();
            }
            elseif( $request->isControl == 'Y' && ($request->limit_day <= 0 || !is_numeric($request->limit_day) ) )
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1012'))
                    ->withInput();
            }
        }
        $this->getBcustParam();
        $id = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']                  = $request->name;
        $upAry['violation_code']        = $request->violation_code;
        $upAry['e_violation_type_id']   = is_numeric($request->type_id) ? $request->type_id : 0;
        $upAry['e_violation_law_id']    = is_numeric($request->law_id) ? $request->law_id : 0;
        $upAry['e_violation_punish_id'] = is_numeric($request->punish_id) ? $request->punish_id : 0;
        $upAry['limit_day']             = is_numeric($request->limit_day) ? $request->limit_day : 999;
        $upAry['isControl']             = ($request->isControl == 'Y')? 'Y' : 'N';
        $upAry['isClose']               = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createViolation($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setViolation($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'e_violation',$id);

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
        $typeAry    = e_violation_type::getSelect();
        $lawAry     = e_violation_law::getSelect();
        $punishAry  = e_violation_punish::getSelect();
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
        $html = $form->text('name');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_31'),1);
        //代碼
        $html = $form->text('violation_code');
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_54'),1);
        //分類
        $html = $form->select('type_id',$typeAry,1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_52'),1);
        //法規
        $html = $form->select('law_id',$lawAry,1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_53'),1);
        //罰條
        $html = $form->select('punish_id',$punishAry,1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_55'),1);
        //是否管制進出
        $html = $form->checkbox('isControl','Y',false);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_56'));
        //限制天數
        $html = $form->text('limit_day',0);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_57'));

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
