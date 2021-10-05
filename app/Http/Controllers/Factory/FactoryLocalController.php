<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Http\Traits\Factory\FactoryLocalTrait;
use App\Http\Traits\Factory\FactoryTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\Factory\b_factory;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class FactoryLocalController extends Controller
{
    use FactoryLocalTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | FactoryLocalController
    |--------------------------------------------------------------------------
    |
    | 廠區->場地維護
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
        $this->hrefMain         = 'factorylocal';
        $this->hrefMain2        = 'factorydevice';
        $this->hrefMain3        = 'factorydept';
        $this->langText         = 'sys_factory';

        $this->hrefMainDetail   = 'factorylocal/';
        $this->hrefMainNew      = 'new_factorylocal';
        $this->routerPost       = 'postFactoryLocal';

        $this->pageTitleMain    = Lang::get($this->langText.'.title2');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list2');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new2');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit2');//編輯

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
        $listAry = [];
        $closeAry = SHCSLib::getCode('CLOSE');
        $storeAry = b_factory::getSelect();
        $sid      = $request->sid ? $request->sid : 0;
        $tid      = $request->tid ? $request->tid : 0;
        if(!$sid) $sid = Session::get($this->langText.'.search.sid',0);
        if(!$tid) $tid = Session::get($this->langText.'.search.tid',0);
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        if($sid)
        {
            Session::put($this->langText.'.search.sid',$sid);
            Session::put($this->langText.'.search.tid',$tid);
            //抓取資料
            $listAry = $this->getApiFactoryLocalList($sid,$tid);
            Session::put($this->hrefMain.'.Record',$listAry);
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y') $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        $html = $form->select('sid',$storeAry,$sid,2,Lang::get($this->langText.'.factory_1'));
        //$html.= $form->select('tid',$kindAry,$tid,2,Lang::get($this->langText.'.factory_31'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_1')]; //廠區
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_31')]; //場地名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_9')]; //MEMO
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_20')]; //GPS
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_7')]; //狀態
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_11')]; //施工地點
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_41')]; //轄區部門

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->name; //
                $name2        = $value->factory; //
                $name3        = $value->memo; //
                $name4        = $value->voice_box_ip; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕
                $btn2         = HtmlLib::btn(SHCSLib::url($this->hrefMain2,'','lid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕
                $btn3         = HtmlLib::btn(SHCSLib::url($this->hrefMain3,'','lid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name2],
                            '2'=>[ 'name'=> $name1],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '21'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
                            '91'=>[ 'name'=> $btn2 ],
                            '92'=>[ 'name'=> $btn3 ],
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
        $storeAry = b_factory::getSelect();
        $kindAry  = SHCSLib::getCode('FACORY_LOCL_TYPE',1);
        $doorAry  = SHCSLib::getCode('DOOR_CONTROL',1);
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
            $A2         = $getData->b_factory_id; //
            $A3         = $getData->kind; //
            $A4         = $getData->door_type; //
            $A5         = $getData->memo; //
            $GPSX       = $getData->GPSX; //
            $GPSY       = $getData->GPSY; //
            $A10        = $getData->voice_box_ip; //


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
        $form->add('nameT1', $html,Lang::get($this->langText.'.factory_31'),1);
        //廠區
        $html = $form->select('b_factory_id',$storeAry,$A2);
        $form->add('nameT2', $html,Lang::get($this->langText.'.factory_1'));
        //類別
//        $html = $form->select('kind',$kindAry,$A3);
//        $form->add('nameT2', $html,Lang::get($this->langText.'.factory_32'));
        //門禁規則
//        $form->addHtml('<div id="door_type_div" style="display: none">');
//        $html  = $form->select('door_type',$doorAry,$A4);
//        $form->add('nameT2', $html,Lang::get($this->langText.'.factory_33'));
//        $form->addHtml('</div>');
        //GPS
//        $html  = $form->text('GPSX',$GPSX,2,Lang::get($this->langText.'.factory_18'));
//        $html .= $form->text('GPSY',$GPSY,2,Lang::get($this->langText.'.factory_19'));
//        $form->add('nameT3', $html,'GPS');
//        if(is_numeric($GPSX) && is_numeric($GPSY))
//        {
//            $html  =  HtmlLib::genMapIframe($GPSX,$GPSY).'<br/>';;
//            $form->add('nameT3', $html,Lang::get($this->langText.'.factory_21'));
//        }
        //語音盒IP
        $html = $form->text('voice_box_ip',$A10);
        $form->add('nameT3', $html,Lang::get($this->langText.'.factory_20'));
        //ＭＥＭＯ
        $html = $form->text('memo',$A5);
        $form->add('nameT3', $html,Lang::get($this->langText.'.factory_9'));
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.factory_8'));
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
            if($("#kind").val() == 3)
            {
                $("#door_type_div").show();
            } else {
                $("#door_type_div").hide();
            }
            $("#kind").change(function() {
                var kind = $(this).val();
                if(kind == 3)
                {
                    $("#door_type_div").show();
                } else {
                    $("#door_type_div").hide();
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
        if( !$request->has('agreeY') || !$request->id || !$request->name )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
//        elseif($request->kind == 3 && !$request->door_type)
//        {
//            return \Redirect::back()
//                ->withErrors(Lang::get($this->langText.'.factory_36'))
//                ->withInput();
//        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;

        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']              = $request->name;
        $upAry['b_factory_id']      = $request->b_factory_id ? $request->b_factory_id : 1;
        $upAry['kind']              = 1;
        $upAry['door_type']         = 0;
        $upAry['voice_box_ip']      = $request->voice_box_ip;
        $upAry['memo']              = $request->memo;
        $upAry['GPSX']              = '';
        $upAry['GPSY']              = '';
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createFactoryLocal($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setFactoryLocal($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_factory_a',$id);

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
        $storeAry = b_factory::getSelect();
        $kindAry  = SHCSLib::getCode('FACORY_LOCL_TYPE',1);
        $doorAry  = SHCSLib::getCode('DOOR_CONTROL',1);
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
        $form->add('nameT1', $html,Lang::get($this->langText.'.factory_31'),1);
        //廠區
        $html  = $form->select('b_factory_id',$storeAry,1);
        $form->add('nameT2', $html,Lang::get($this->langText.'.factory_1'));
        //類型
//        $html  = $form->select('kind',$kindAry,1);
//        $form->add('nameT2', $html,Lang::get($this->langText.'.factory_32'));
        //門禁類別
//        $html  = $form->select('door_type',$doorAry);
//        $form->add('nameT2', $html,Lang::get($this->langText.'.factory_33'));
        //語音盒IP
        $html = $form->text('voice_box_ip','');
        $form->add('nameT3', $html,Lang::get($this->langText.'.factory_20'));
        //說明
        $html = $form->text('memo');
        $form->add('nameT3', $html,Lang::get($this->langText.'.factory_9'));

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
            if($("#kind").val() == 3)
            {
                $("#door_type").attr("disabled", false);
            } else {
                $("#door_type").attr("disabled", true);
            }
            $("#kind").change(function() {
                var kind = $(this).val();
                if(kind == 3)
                {
                    $("#door_type").attr("disabled", false);
                } else {
                    $("#door_type").attr("disabled", true);
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
