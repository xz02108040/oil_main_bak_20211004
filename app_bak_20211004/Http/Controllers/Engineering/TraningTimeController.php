<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\EngineeringLicenseTrait;
use App\Http\Traits\Engineering\TraningTimeTrait;
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
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class TraningTimeController extends Controller
{
    use TraningTimeTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | TraningTimeController
    |--------------------------------------------------------------------------
    |
    | 課程時段 維護
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
        $this->hrefHome         = 'etraning';
        $this->hrefMain         = 'etraningtime';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'etraningtime/';
        $this->hrefMainNew      = 'new_etraningtime/';
        $this->routerPost       = 'postETraningtime';

        $this->pageTitleMain    = Lang::get($this->langText.'.title14');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list14');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new14');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit14');//編輯

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
        if(!$pid && is_numeric($pid) && $pid > 0)
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
        $listAry = $this->getApiTraningTimeList($pid);
        Session::put($this->hrefMain.'.traning_id',$request->pid);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_50')]; //週
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_35')]; //開始時間
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_36')]; //結束時間
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_13')]; //說明
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_33')]; //停用

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->week_name; //
                $name2        = $value->stime; //
                $name3        = $value->etime; //
                $name4        = $value->memo; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

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
        if(!$pid || !is_numeric($pid))
        {
            $msg = Lang::get($this->langText.'.engineering_1018');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = '?pid='.$request->pid;
        }
        $id = SHCSLib::decode($urlid);
        $weekAry = SHCSLib::getCode('WEEK',1);
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
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //資料明細
            $A1         = $getData->week; //
            $A2         = $getData->stime; //
            $A3         = $getData->etime; //
            $A4         = $getData->memo; //

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //週
        $html = $form->select('week',$weekAry,$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_50'),1);
        //開始時間
        $html = $form->time('stime',$A2);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_35'),1);
        //結束時間
        $html = $form->time('etime',$A3);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_36'),1);
        //說明
        $html = $form->textarea('memo',$A4);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));
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
            $("#stime,#etime").timepicker({
                showMeridian: false,
                defaultTime: false,
                timeFormat: "HH:mm"
            })
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
        if( !$request->has('agreeY') || !$request->id || !$request->pid || !$request->stime || !$request->etime )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $traning_id = SHCSLib::decode($request->pid);
            $id         = SHCSLib::decode($request->id);
            $ip         = $request->ip();
            $menu       = $this->pageTitleMain;
        }
        if(!$traning_id)
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
        $upAry['et_traning_id']     = is_numeric($traning_id) ? $traning_id : 0;
        $upAry['week']              = $request->week;
        $upAry['stime']             = CheckLib::isTime($request->stime) ? $request->stime : '';
        $upAry['etime']             = CheckLib::isTime($request->etime) ? $request->etime : '';
        $upAry['memo']              = $request->memo;
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createTraningTime($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setTraningTime($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'et_traning_time',$id);

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
        $pid = SHCSLib::decode($urlid);
        $weekAry = SHCSLib::getCode('WEEK',1);
        if(!$pid)
        {
            $msg = Lang::get($this->langText.'.engineering_1013');
            return \Redirect::back()->withErrors($msg);
        }
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //週
        $html = $form->select('week',$weekAry);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_50'),1);
        //開始時間
        $html = $form->time('stime','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_35'),1);
        //結束時間
        $html = $form->time('etime','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_36'),1);
        //說明
        $html = $form->textarea('memo','');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));

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
           $("#stime,#etime").timepicker({
                showMeridian: false,
                defaultTime: false
            })
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
