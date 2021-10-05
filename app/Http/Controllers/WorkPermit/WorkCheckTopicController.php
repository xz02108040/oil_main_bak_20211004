<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\sys_param;
use App\Model\WorkPermit\wp_check;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_topic_type;
use App\Model\WorkPermit\wp_check_topic;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkCheckTopicController extends Controller
{
    use WorkCheckTopicTrait,WorkCheckTopicOptionTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkCheckTopicController
    |--------------------------------------------------------------------------
    |
    | 附加檢點表-檢核項目維護
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
        $this->hrefMain         = 'workchecktopic';
        $this->hrefPermit       = 'workcheck';
        $this->hrefOption       = 'workchecktopicoption';
        $this->langText         = 'sys_workpermit';

        $this->hrefMainDetail   = 'workchecktopic/';
        $this->hrefMainNew      = 'new_workchecktopic/';
        $this->routerPost       = 'postWorkchecktopic';

        $this->pageTitleMain    = Lang::get($this->langText.'.title11');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list11');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new11');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit11');//編輯
        
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
        $checkAry = SHCSLib::getCode('CHECK');
        //檢點單ＩＤ
        $urlid    = $request->kid ? $request->kid : '';
        $kid      = SHCSLib::decode($urlid);
        if($kid)
        {
            Session::put($this->langText.'.select.kid',$kid);
            Session::put($this->langText.'.select.urlkid',$urlid);
        } else {
            $kid = Session::get($this->langText.'.select.kid');
        }
        if(!$kid) return \Redirect::back()->withErrors(Lang::get($this->langText.'.permit_10000'));
        $headtName = wp_check::getName($kid);

        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$headtName;//列表標題
        $hrefMain = $this->hrefMain.'?kid='.$urlid;
        $hrefNew  = $this->hrefMainNew.$urlid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefPermit;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiWorkCheckTopicList($kid);
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
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_21')]; //項目
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_32')]; //紀錄週期
        //$heads[] = ['title'=>Lang::get($this->langText.'.permit_11')]; //顯示為勾選
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_4')]; //排序
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_7')]; //狀態
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_22')]; //檢核選項

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
                //$name4        = isset($checkAry[$value->isCheck])? $checkAry[$value->isCheck] : '' ; //
                $name5        = $value->isOption; //
                $name6        = ($value->circle > 0)? $value->circle : ''; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色
                //$isCheckColor = $value->isCheck == 'Y' ? 2 : 5 ; //停用顏色

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //按鈕
                $btn2         = HtmlLib::btn(SHCSLib::url($this->hrefOption,'','did='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '11'=>[ 'name'=> $name6],
                            '3'=>[ 'name'=> $name3],
                            '21'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
                            '90'=>[ 'name'=> ($name5 == 'Y')? $btn2 : ''],
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
            $A3         = $getData->show_order; //
            $A4         = $getData->wp_topic_type; //
            $A5         = $getData->wp_check_id; //
            $A6         = $getData->circle; //
            $kid        = SHCSLib::encode($A5); //
            $A10        = ($getData->isCheck == 'Y')? true : false;

            $typeAry  = wp_topic_type::getSelect(1,1,[],'','Y');
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $isShowCircle      = ($A4 == 6)? '' : 'none';
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_21'),1);
        //
        $html = $A2;
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_31'),1);
        //類型
        $html = $form->select('type_id',$typeAry,$A4);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_2'),1);
        //紀錄表專用：紀錄週期
        $html = '<div id="circleDiv" style="display:'.$isShowCircle.'">';
        $form->addHtml($html);
        $html = $form->text('circle',$A6).HtmlLib::Color(Lang::get($this->langText.'.permit_33'),'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_32'));
        $html = '</div>';
        $form->addHtml($html);
        //排序
        $html = $form->text('show_order',$A3);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_4'));
        //是否顯示為勾選
        //$html = $form->checkbox('isCheck','Y',$A10);
        //$form->add('isCloseT',$html,Lang::get($this->langText.'.permit_11'));
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.permit_8'));
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack.'?kid='.$kid, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('kid',$kid);
        $submitDiv.= $form->hidden('check_id',$A5);
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
            $( "#type_id" ).change(function() {
                  var type = $( this ).val();
                  alert(type);
                  if(type == 6)
                  {
                      $("#circleDiv").show();
                  } else {
                      $("#circleDiv").hide();
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
        if( !$request->has('agreeY') || !$request->id || !$request->name  || !$request->type_id || !$request->check_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
//        elseif(wp_check_topic::isNameExist($request->permit_id, $request->name , SHCSLib::decode($request->id)))
//        {
//            return \Redirect::back()
//                ->withErrors(Lang::get('sys_base.base_10110'))
//                ->withInput();
//        }
        elseif($request->type_id == 6 && !($request->circle >= 0))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.permit_10013'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id     = SHCSLib::decode($request->id);
            $ip     = $request->ip();
            $kid    = $request->kid;
            $copy_id= $request->copy_id? $request->copy_id : 0;
            $menu   = $this->pageTitleMain;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']              = $request->name;
        $upAry['circle']            = $request->circle;
        $upAry['wp_check_id']       = $request->check_id;
        $upAry['wp_topic_type']     = $request->type_id;
        $upAry['show_order']        = $request->show_order ? $request->show_order : 999;
        $upAry['isCheck']           = ($request->isCheck == 'Y')? 'Y' : 'N';
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createWorkCheckTopic($upAry,$copy_id,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setWorkCheckTopic($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_check_topic',$id);
                //更新工作許可證版本
                wp_permit::updateAt();

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?kid='.$kid);
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
        $kid      = SHCSLib::decode($urlid);
        $kind     = wp_check::getName($kid);
        $typeAry  = wp_topic_type::getSelect(1,1,[],'','Y');
        //view元件參數
        $hrefBack   = $this->hrefMain.'?kid='.$urlid;;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //複製特定檢核項目
        //$html = $form->select('copy_id',$topicAry);
        //$form->add('nameT1', $html,Lang::get($this->langText.'.permit_21'),1);
        //名稱
        $html = $form->text('name');
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_21'),1);
        //種類
        $html = $kind.$form->hidden('check_id',$kid);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_31'),1);
        //類型
        $html = $form->select('type_id',$typeAry,1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_2'),1);
        //紀錄表專用：紀錄週期
        $html = '<div id="circleDiv" style="display:none">';
        $form->addHtml($html);
        $html = $form->text('circle',0).HtmlLib::Color(Lang::get($this->langText.'.permit_33'),'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_32'));
        $html = '</div>';
        $form->addHtml($html);
        //排序
        $html  = $form->text('show_order',999);
        $form->add('nameT2', $html,Lang::get($this->langText.'.permit_4'));
        //是否顯示為勾選
        //$html = $form->checkbox('isCheck','Y',true);
        //$form->add('isCloseT',$html,Lang::get($this->langText.'.permit_11'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('kid',$urlid);
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
            $("#sdate").datepicker({
                format: "yyyy-mm-dd",
                language: "zh-TW"
            });
            $( "#type_id" ).change(function() {
                  var type = $( this ).val();
                  alert(type);
                  if(type == 6)
                  {
                      $("#circleDiv").show();
                  } else {
                      $("#circleDiv").hide();
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
