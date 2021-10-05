<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_param;
use App\Model\WorkPermit\wp_check_topic;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkCheckTopicOptionController extends Controller
{
    use WorkCheckTopicOptionTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkCheckTopicOptionController
    |--------------------------------------------------------------------------
    |
    | 檢點表 檢核選項維護
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
        $this->hrefHome         = '/';
        $this->hrefMain         = 'workchecktopicoption';
        $this->hrefTopic        = 'workchecktopic';
        $this->langText         = 'sys_workpermit';

        $this->hrefMainDetail   = 'workchecktopicoption/';
        $this->hrefMainNew      = 'new_workchecktopicoption/';
        $this->routerPost       = 'postWorkchecktopicoption';

        $this->pageTitleMain    = Lang::get($this->langText.'.title12');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list12');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new12');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit12');//編輯
        
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
        $closeAry = SHCSLib::getCode('CLOSE');
        //ＩＤ
        $urlid    = $request->did ? $request->did : '';
        $did      = SHCSLib::decode($urlid);
        if($did)
        {
            Session::put($this->langText.'.select.did',$did);
        } else {
            $did = Session::get($this->langText.'.select.did');
        }
        $kid = Session::get($this->langText.'.select.urlkid');
        if(!$did) return \Redirect::back()->withErrors(Lang::get($this->langText.'.permit_10002'));
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $topicName = wp_check_topic::getFullName($did,$Icon);
        $tbTitle  = $this->pageTitleList.$Icon.$topicName;//列表標題
        $hrefMain = $this->hrefMain.'?did='.$urlid;
        $hrefNew  = $this->hrefMainNew.$urlid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefTopic.'?kid='.$kid;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiWorkCheckTopicOptionList($did);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_2')];  //種類
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_22')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_56')]; //數值型別
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_37')]; //預設值
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_57')]; //系統代碼
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_36')]; //安全值說明
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_38')]; //上限
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_39')]; //下限
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_40')]; //上下限關係
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_34')]; //單位
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_41')]; //工程身分
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_4')];  //排序
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_7')];  //狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->type; //
                $name2        = $value->name; //
                $name3        = $value->show_order; //
                $name4        = $value->unit; //
                $name5        = $value->defult_val; //
                $name6        = $value->safe_val; //
                $name7        = $value->safe_limit1; //
                $name8        = $value->safe_limit2; //
                $name9        = $value->safe_action; //
                $name10       = $value->ans_type; //
                $name11       = $value->sys_code; //
                $name12       = $value->identtity; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '10'=>[ 'name'=> $name10],
                            '5'=>[ 'name'=> $name5],
                            '11'=>[ 'name'=> $name11],
                            '6'=>[ 'name'=> $name6],
                            '7'=>[ 'name'=> $name7],
                            '8'=>[ 'name'=> $name8],
                            '9'=>[ 'name'=> $name9],
                            '4'=>[ 'name'=> $name4],
                            '22'=>[ 'name'=> $name12],
                            '3'=>[ 'name'=> $name3],
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
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $extStr = sys_param::getParam('WORKOPTION_TYPE_EXCLUDE_B','');
        $extAry = explode(',',$extStr);
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
        } else {
            //資料明細
            $A1         = $getData->name; //
            $A2         = $getData->wp_check; //
            $A3         = $getData->wp_check_topic; //
            $A4         = $getData->wp_check_id; //
            $A5         = $getData->wp_check_topic_id; //
            $A6         = $getData->wp_option_type; //
            $A7         = $getData->show_order; //
            $A8         = $getData->unit; //
            $A10        = $getData->memo; //
            $A11        = $getData->safe_val; //
            $A12        = $getData->ans_type; //
            $A13        = $getData->safe_limit1; //
            $A14        = $getData->safe_limit2; //
            $A15        = $getData->safe_action; //
            $A16        = $getData->sys_code; //
            $A17        = $getData->defult_val; //

            $did        = SHCSLib::encode($A5); //


            $typeAry    = SHCSLib::array_key_exclude(SHCSLib::getCode('WP_OPTION_TYPE'),$extAry);
            $ansTypeAry = SHCSLib::getCode('TOPIC_ANS_TYPE',1);
            $identityAry= b_supply_engineering_identity::getSelect();

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;

            $divShow2   = ($A6 == 2)? '' : 'display:none;';
            $divShow3   = ($A6 == 11)? '' : 'display:none;';
            $divShow4   = ($A6 == 10)? '' : 'display:none;';
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//

        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_22'),1);
        //附加檢點表
        $html = $A2;
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_1'),1);
        //檢核項目
        $html = $A3.$form->hidden('topic_id',$A5);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_21'),1);
        //種類
        $html = $form->select('wp_option_type',$typeAry,$A6);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_2'),1);
        //種類
        $html = $form->select('ans_type',$ansTypeAry,$A12);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_56'),1);
        //系統代碼
        $html = $form->text('sys_code',$A16);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_57'));
        //預設值
        $html = $form->text('defult_val',$A17);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_37'));
        //單位
        $form->addHtml('<div id="div2" style="'.$divShow2.'">');
        //安全值說明
        $html = $form->text('safe_val',$A11);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_36'));
        //上限
        $html = $form->text('safe_limit1',$A13);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_38'));
        //下限
        $html = $form->text('safe_limit2',$A14);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_39'));
        //上下限關係
        $html = $form->text('safe_val',$A15);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_40'));
        //單位
        $html = $form->text('unit',$A8).HtmlLib::color(Lang::get($this->langText.'.permit_35'),'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_34'));
        $form->addHtml('</div>');
        //工程身分
        $form->addHtml('<div id="div4" style="display:'.$divShow4.';">');
        $html = $form->select('engineering_identity_id',$identityAry);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_41'));
        $form->addHtml('</div>');

        //備註
        $form->addHtml('<div id="div3" style="'.$divShow3.'">');
        $html = $form->textarea('memo','','',$A10);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_14'),1);
        $form->addHtml('</div>');


        //排序
        $html = $form->text('show_order',$A7);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_4'));
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.permit_8'));
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack.'?did='.$did, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('did',$did);
        $submitDiv.= $form->hidden('check_id',$A4);
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
            $( "#wp_option_type" ).change(function() {
                $("#div2,#div3,#div4").hide();
                if($( this ).val() == 2 )
                {
                    $("#div2").show();
                }
                if($( this ).val() == 10 )
                {
                    $("#div4").show();
                }
                if($( this ).val() == 11 )
                {
                    $("#div3").show();
                }
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
        //參數
        $this->getBcustParam();
        $id   = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $did  = $request->did;
        $menu = $this->pageTitleMain;
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->name  || !$request->topic_id || !$request->wp_option_type)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif(wp_check_topic_a::isNameExist($request->topic_id, $request->name , $id))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10110'))
                ->withInput();
        }
        elseif($request->wp_option_type == 11 &&  !$request->memo)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.permit_10012'))
                ->withInput();
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']                      = $request->name;
        $upAry['wp_check_id']               = $request->check_id;
        $upAry['wp_check_topic_id']         = $request->topic_id;
        $upAry['wp_option_type']            = $request->wp_option_type;
        $upAry['ans_type']                  = $request->ans_type;
        $upAry['defult_val']                = isset($request->defult_val)? $request->defult_val : '';
        $upAry['safe_val']                  = isset($request->safe_val)? $request->safe_val : '';
        $upAry['sys_code']                  = isset($request->sys_code)? $request->sys_code : '';
        $upAry['safe_limit1']               = isset($request->safe_limit1)? $request->safe_limit1 : 0;
        $upAry['safe_limit2']               = isset($request->safe_limit2)? $request->safe_limit2 : 0;
        $upAry['safe_action']               = isset($request->safe_action)? $request->safe_action : '';
        $upAry['memo']                      = is_null($request->memo)? '' : $request->memo;
        $upAry['unit']                      = is_null($request->unit)? '' : $request->unit;
        $upAry['engineering_identity_id']   = is_null($request->engineering_identity_id)? '' : $request->engineering_identity_id;
        $upAry['show_order']                = $request->show_order ? $request->show_order : 0;
        $upAry['isClose']                   = ($request->isClose == 'Y')? 'Y' : 'N';
        if($request->showtest) dd($upAry);
        //新增
        if($isNew)
        {
            $ret = $this->createWorkCheckTopicOption($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setWorkCheckTopicOption($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_check_topic_a',$id);
                //更新工作許可證版本
                wp_permit::updateAt();

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?did='.$did);
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
        //參數
        $js = $contents = '';
        $Icon       = HtmlLib::genIcon('caret-square-o-right');
        $did        = SHCSLib::decode($urlid);
        $topicName  = wp_check_topic::getFullName($did,$Icon);
        $extStr     = sys_param::getParam('WORKOPTION_TYPE_EXCLUDE_B','');
        $extAry     = explode(',',$extStr);
        $typeAry    = SHCSLib::array_key_exclude(SHCSLib::getCode('WP_OPTION_TYPE'),$extAry);
        $ansTypeAry = SHCSLib::getCode('TOPIC_ANS_TYPE',1);
        $identityAry= b_supply_engineering_identity::getSelect();
        $kid        = Session::get($this->langText.'.select.kid');
        $showtest   = $request->showtest;
        //view元件參數
        $hrefBack   = $this->hrefMain.'?did='.$urlid;;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html = $form->text('name');
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_22'),1);
        //檢核項目
        $html = $topicName.$form->hidden('topic_id',$did);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_21'),1);
        //種類
        $html = $form->select('wp_option_type',$typeAry);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_2'),1);
        //種類
        $html = $form->select('ans_type',$ansTypeAry,1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_26'),1);
        //單位
        $form->addHtml('<div id="div2" style="display:none;">');
        $html = $form->text('unit').HtmlLib::color(Lang::get($this->langText.'.permit_35'),'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_34'));
        $html = $form->text('safe_val');
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_36'));
        $form->addHtml('</div>');
        //工程身分
        $form->addHtml('<div id="div4" style="display:none;">');
        $html = $form->select('engineering_identity_id',$identityAry);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_41'));
        $form->addHtml('</div>');

        //備註
        $form->addHtml('<div id="div3" style="display:none;">');
        $html = $form->textarea('memo');
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_14'),1);
        $form->addHtml('</div>');

        //排序
        $html = $form->text('show_order',999);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_4'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('did',$urlid);
        $submitDiv.= $form->hidden('check_id',$kid);
        $submitDiv.= $form->hidden('showtest',$showtest);
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
            $( "#wp_option_type" ).change(function() {
                $("#div2,#div3,#div4").hide();
                if($( this ).val() == 2 )
                {
                    $("#div2").show();
                }
                if($( this ).val() == 10 )
                {
                    $("#div4").show();
                }
                if($( this ).val() == 11 )
                {
                    $("#div3").show();
                }
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
