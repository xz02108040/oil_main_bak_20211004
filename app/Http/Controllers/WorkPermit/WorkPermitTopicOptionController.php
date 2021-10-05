<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitKindTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_param;
use App\Model\User;
use App\Model\WorkPermit\wp_check;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_topic;
use App\Model\WorkPermit\wp_permit_topic_a;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkPermitTopicOptionController extends Controller
{
    use WorkPermitTopicOptionTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitTopicOptionController
    |--------------------------------------------------------------------------
    |
    | 工作許可證 檢核選項維護
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
        $this->hrefMain         = 'workpermittopicoption';
        $this->hrefTopic        = 'workpermittopic';
        $this->langText         = 'sys_workpermit';

        $this->hrefMainDetail   = 'workpermittopicoption/';
        $this->hrefMainNew      = 'new_workpermittopicoption/';
        $this->routerPost       = 'postWorkpermittopicoption';

        $this->pageTitleMain    = Lang::get($this->langText.'.title6');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list6');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new6');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit6');//編輯
        
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
        $topicName = wp_permit_topic::getFullName($did,$Icon);
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
        $listAry = $this->getApiWorkPermitTopicOptionList($did);
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
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_2')]; //種類
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_22')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_23')]; //工程身份
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_31')]; //檢點單
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_4')]; //排序
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_7')]; //狀態

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
                $name10       = $value->engineering_identity; //
                $name11       = $value->wp_check; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '11'=>[ 'name'=> $name10],
                            '12'=>[ 'name'=> $name11],
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
        $extStr = sys_param::getParam('WORKOPTION_TYPE_EXCLUDE_A','');
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
            $A2         = $getData->wp_permit; //
            $A3         = $getData->wp_permit_topic; //
            $A4         = $getData->wp_permit_id; //
            $A5         = $getData->wp_permit_topic_id; //
            $A6         = $getData->wp_option_type; //
            $A7         = $getData->show_order; //
            $A8         = $getData->memo; //
            $A10        = $getData->engineering_identity_id; //
            $A11        = $getData->wp_check_id; //

            $did        = SHCSLib::encode($A5); //

            $typeAry        = SHCSLib::array_key_exclude(SHCSLib::getCode('WP_OPTION_TYPE'),$extAry);
            $identityAry    = b_supply_engineering_identity::getSelect();
            $checkAry       = wp_check::getSelect();

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;

            $divShow1   = ($A6 == 9)? '' : 'display:none;';
            $divShow2   = ($A6 == 10)? '' : 'display:none;';
            $divShow3   = ($A6 == 11)? '' : 'display:none;';
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_22'),1);
        //工作許可證
        $html = $A2;
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_1'),1);
        //檢核項目
        $html = $A3.$form->hidden('topic_id',$A5);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_21'),1);
        //種類
        $html = $form->select('wp_option_type',$typeAry,$A6);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_2'),1);


        //檢點單
        $form->addHtml('<div id="div1" style="'.$divShow1.'">');
        $html = $form->select('check_id',$checkAry,$A11);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_31'),1);
        $form->addHtml('</div>');

        //工程身份
        $form->addHtml('<div id="div2" style="'.$divShow2.'">');
        $html = $form->select('identity_id',$identityAry,$A10);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_23'),1);
        $form->addHtml('</div>');

        //說明
        $form->addHtml('<div id="div3" style="'.$divShow3.'">');
        $html = $form->textarea('memo','','',$A8);
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
        $submitDiv.= $form->hidden('permit_id',$A4);
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
                $("#div1,#div2,#div3").hide();
                if($( this ).val() == 9 )
                {
                    $("#div1").show();
                }
                if($( this ).val() == 10 )
                {
                    $("#div2").show();
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
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->name  || !$request->topic_id || !$request->wp_option_type)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif(wp_permit_topic_a::isNameExist($request->topic_id, $request->name , SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10110'))
                ->withInput();
        }
        elseif($request->wp_option_type == 9 &&  !$request->check_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.permit_10010'))
                ->withInput();
        }
        elseif($request->wp_option_type == 10 &&  !$request->identity_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.permit_10011'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id   = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $did  = $request->did;
            $menu = $this->pageTitleMain;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']                      = $request->name;
        $upAry['memo']                      = $request->memo;
        $upAry['wp_permit_id']              = $request->permit_id;
        $upAry['wp_permit_topic_id']        = $request->topic_id;
        $upAry['wp_option_type']            = $request->wp_option_type;
        $upAry['engineering_identity_id']   = $request->identity_id;
        $upAry['wp_check_id']               = $request->check_id;
        $upAry['show_order']                = $request->show_order ? $request->show_order : 0;
        $upAry['isClose']                   = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createWorkPermitTopicOption($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setWorkPermitTopicOption($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_permit_topic_a',$id);
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
        $dangerName = wp_permit_topic::getFullName($did,$Icon);
        $extStr     = sys_param::getParam('WORKOPTION_TYPE_EXCLUDE_A','');
        $extAry     = explode(',',$extStr);
        $typeAry    = SHCSLib::array_key_exclude(SHCSLib::getCode('WP_OPTION_TYPE'),$extAry);
        $kid        = Session::get($this->langText.'.select.kid');
        $identityAry= b_supply_engineering_identity::getSelect();
        $checkAry   = wp_check::getSelect();
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
        $html = $dangerName.$form->hidden('topic_id',$did);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_21'),1);
        //種類
        $html = $form->select('wp_option_type',$typeAry);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_2'),1);

        //檢點單
        $form->addHtml('<div id="div1" style="display:none;">');
        $html = $form->select('check_id',$checkAry);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_31'),1);
        $form->addHtml('</div>');

        //工程身份
        $form->addHtml('<div id="div2" style="display:none;">');
        $html = $form->select('identity_id',$identityAry);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_23'),1);
        $form->addHtml('</div>');

        //說明
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
        $submitDiv.= $form->hidden('permit_id',$kid);
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
                $("#div1,#div2,#div3").hide();
                if($( this ).val() == 9 )
                {
                    $("#div1").show();
                }
                if($( this ).val() == 10 )
                {
                    $("#div2").show();
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
