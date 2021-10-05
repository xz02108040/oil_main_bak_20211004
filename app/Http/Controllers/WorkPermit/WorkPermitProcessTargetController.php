<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTargetTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\bc_type_app;
use App\Model\sys_param;
use App\Model\WorkPermit\wp_check_topic;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkPermitProcessTargetController extends Controller
{
    use WorkPermitProcessTargetTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitProcessTargetController
    |--------------------------------------------------------------------------
    |
    | 工作許可證 流程＿對象維護
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
        $this->hrefMain         = 'workpermitprocesstarget';
        $this->hrefTopic        = 'workpermitprocess';
        $this->langText         = 'sys_workpermit';

        $this->hrefMainDetail   = 'workpermitprocesstarget/';
        $this->hrefMainNew      = 'new_workpermitprocesstarget/';
        $this->routerPost       = 'postWorkpermitprocesstarget';

        $this->pageTitleMain    = Lang::get($this->langText.'.title8');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list8');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new8');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit8');//編輯
        
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
        $listAry = $this->getApiWorkPermitProcessTargetList($did);
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
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_18')];  //身份
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_4')];   //排序
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_7')];   //狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name2        = $value->bc_type_app; //
                $name3        = $value->show_order; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '2'=>[ 'name'=> $name2],
                            '80'=>[ 'name'=> $name3],
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
            $A1         = $getData->bc_type_app_id; //
            $A2         = $getData->bc_type; //
            $A3         = $getData->show_order; //

            $A20        = $getData->wp_permit_id; //
            $A21        = $getData->wp_permit_process_id; //

            $did        = SHCSLib::encode($A21); //

            $bctypeAry  = SHCSLib::getCode('BC_TYPE',1);
            unset($bctypeAry[1]);//刪除「自建」

            $typeAry    = bc_type_app::getSelect($A2);

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //APP身份
        $html = $form->select('bc_type_app',$typeAry,$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_18'),1);
        //排序
        $html = $form->text('show_order',$A3);
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
        $submitDiv.= $form->hidden('permit_id',$A20);
        $submitDiv.= $form->hidden('process_id',$A21);
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
            $( "#bc_type" ).change(function() {
                        var tid = $("#bc_type").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findBcType').'",  
                          data: { type: 2, tid : tid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             var count = Object.keys(result).length;
                             $("#bc_type_app option").remove();
                             if(count > 1)
                             {
                                $.each(result, function(key, val) {
                                    $("#bc_type_app").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                });
                             }
                             
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
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->process_id || !$request->bc_type_app)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif(wp_permit_process_target::isExist($request->process_id, $request->bc_type_app , SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.permit_10015'))
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
        $upAry['wp_permit_id']              = $request->permit_id;
        $upAry['wp_permit_process_id']      = $request->process_id;
        $upAry['bc_type_app_id']            = $request->bc_type_app;
        $upAry['show_order']                = $request->show_order;
        $upAry['isClose']                   = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createWorkPermitProcessTarget($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setWorkPermitProcessTarget($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_permit_process_target',$id);
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
        $did        = SHCSLib::decode($urlid);
        $kid        = Session::get($this->langText.'.select.kid');
        $bctype     = wp_permit_process::getBcType($did);
        $typeAry    = bc_type_app::getSelect($bctype);
        //view元件參數
        $hrefBack   = $this->hrefMain.'?did='.$urlid;;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //APP身份
        $html = $form->select('bc_type_app',$typeAry);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_18'),1);
        //排序
        $html = $form->text('show_order',999);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_4'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('did',$urlid);
        $submitDiv.= $form->hidden('permit_id',$kid);
        $submitDiv.= $form->hidden('process_id',$did);
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
            $( "#bc_type" ).change(function() {
                        var tid = $("#bc_type").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findBcType').'",  
                          data: { type: 2, tid : tid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             var count = Object.keys(result).length;
                             $("#bc_type_app option").remove();
                             if(count > 1)
                             {
                                $.each(result, function(key, val) {
                                    $("#bc_type_app").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                });
                             }
                             
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
