<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitIdentityTrait;
use App\Http\Traits\WorkPermit\WorkPermitKindTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicTrait;
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
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_identity;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_topic;
use App\Model\WorkPermit\wp_topic_type;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkPermitIdentityController extends Controller
{
    use WorkPermitIdentityTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitIdentityController
    |--------------------------------------------------------------------------
    |
    | 工作許可證 需要工程身份人員
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
        $this->hrefMain         = 'workpermitidentity';
        $this->hrefPermit       = 'workpermit';
        $this->langText         = 'sys_workpermit';

        $this->hrefMainDetail   = 'workpermitidentity/';
        $this->hrefMainNew      = 'new_workpermitidentity/';
        $this->routerPost       = 'postWorkpermitidentity';

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
        //參數
        $out = $js ='';
        $no  = 0;
        $closeAry = SHCSLib::getCode('CLOSE');
        $checkAry = SHCSLib::getCode('CHECK');
        //工作許可證ＩＤ
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
        $permitName = wp_permit::getName($kid);

        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$permitName;//列表標題
        $hrefMain = $this->hrefMain.'?kid='.$urlid;
        $hrefNew  = $this->hrefMainNew.$urlid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefPermit;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiWorkPermitIdentityList($kid);
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
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_41')]; //流程
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_42')]; //階段
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_7')];  //狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->name; //
                $name2        = $value->least_amt; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
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
            $A2         = $getData->wp_permit; //
            $A3         = $getData->engineering_identity_id; //
            $A4         = $getData->least_amt; //
            $A6         = $getData->wp_permit_id; //
            $kid        = SHCSLib::encode($A6); //


            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;

            $identityAry= b_supply_engineering_identity::getSelect();
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //
        $html = $A2;
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_1'),1);
        //工程身份
        $html = $form->select('engineering_identity_id',$identityAry,$A3);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_41'),1);
        //至少人數
        $html = $form->text('least_amt',$A4);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_42'));
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
        $submitDiv.= $form->hidden('permit_id',$A6);
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
        if( !$request->has('agreeY') || !$request->id || !$request->engineering_identity_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif (wp_permit_identity::isExist($request->permit_id,$request->engineering_identity_id))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.permit_10019'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id   = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $kid  = $request->kid;
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
        $upAry['engineering_identity_id']   = $request->engineering_identity_id;
        $upAry['least_amt']                 = $request->least_amt;
        $upAry['isClose']                   = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createWorkPermitIdentity($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setWorkPermitIdentity($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_permit_identity',$id);
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
        $kid        = SHCSLib::decode($urlid);
        $kind       = wp_permit::getName($kid);
        $identityAry= b_supply_engineering_identity::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain.'?kid='.$urlid;;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        $html = $kind.$form->hidden('permit_id',$kid);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_1'),1);
        //工程身份
        $html = $form->select('engineering_identity_id',$identityAry);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_41'),1);
        //至少人數
        $html = $form->text('least_amt',0);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_42'));

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
