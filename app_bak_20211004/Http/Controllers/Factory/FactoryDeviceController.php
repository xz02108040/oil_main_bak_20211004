<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Http\Traits\Factory\FactoryDeviceTrait;
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
use App\Model\Factory\b_factory_a;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class FactoryDeviceController extends Controller
{
    use FactoryDeviceTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | FactoryDeviceController
    |--------------------------------------------------------------------------
    |
    | 場地->施工地點
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
        $this->hrefHome         = 'factorylocal';
        $this->hrefMain         = 'factorydevice';
        $this->langText         = 'sys_factory';

        $this->hrefMainDetail   = 'factorydevice/';
        $this->hrefMainNew      = 'new_factorydevice';
        $this->routerPost       = 'postFactoryDevice';

        $this->pageTitleMain    = Lang::get($this->langText.'.title3');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list3');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new3');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit3');//編輯

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
        $lid      = $request->lid ? $request->lid : '';
        Session::put($this->hrefMain.'.search',$lid);
        $lid     = SHCSLib::decode($lid);
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        //view元件參數
        $tbTitle  = $this->pageTitleList.$Icon.b_factory_a::getName($lid);//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefHome;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        if($lid)
        {
            //抓取資料
            $listAry = $this->getApiFactoryDeviceList($lid);
            Session::put($this->hrefMain.'.Record',$listAry);
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y') $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_1')]; //廠區
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_31')]; //場地名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_11')]; //施工地點
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_18')]; //緯度
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_19')]; //經度
        $heads[] = ['title'=>Lang::get($this->langText.'.factory_7')]; //狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name2        = $value->factory; //
                $name1        = $value->name; //
                $name3        = $value->local; //
                $name6        = $value->GPSY; //
                $name7        = $value->GPSX; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name2],
                            '2'=>[ 'name'=> $name3],
                            '3'=>[ 'name'=> $name1],
                            '6'=>[ 'name'=> $name6],
                            '7'=>[ 'name'=> $name7],
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
        $lid= Session::get($this->hrefMain.'.search',0);

        $kindAry  = SHCSLib::getCode('FACTORY_B_DEVICE_KIND',1);
        $typeAry  = SHCSLib::getCode('FACTORY_B_READER_TYPE',1);
        //view元件參數
        $hrefBack       = $this->hrefMain.'?lid='.$lid;
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
        }  else {
            //資料明細
            $A1         = $getData->name; //
            $A2         = $getData->factory; //
            $A3         = $getData->b_factory_a_id; //
            $A4         = $getData->GPSX; //
            $A5         = $getData->GPSY; //
            $A9         = $getData->memo; //


            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;

            $localAry = b_factory_a::getSelect($getData->b_factory_id);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.factory_12'),1);
        //廠區
        $html = $A2;
        $form->add('nameT2', $html,Lang::get($this->langText.'.factory_1'));
        //場地
        $html = $form->select('b_factory_a_id',$localAry,$A3);
        $form->add('nameT2', $html,Lang::get($this->langText.'.factory_31'),1);
        //GPSX
        $html = $form->text('GPSX',$A4);
        $form->add('nameT3', $html,Lang::get($this->langText.'.factory_18'));
        //GPSY
        $html = $form->text('GPSY',$A5);
        $form->add('nameT3', $html,Lang::get($this->langText.'.factory_19'));
        //ＭＥＭＯ
        $html = $form->text('memo',$A9);
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
        if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->b_factory_a_id )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
            $lid  = Session::get($this->hrefMain.'.search',0);

        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']              = $request->name;
        $upAry['b_factory_id']      = b_factory_a::getStoreId($request->b_factory_a_id);
        $upAry['b_factory_a_id']    = $request->b_factory_a_id;
        $upAry['GPSX']              = $request->GPSX;
        $upAry['GPSY']              = $request->GPSY;
        $upAry['memo']              = $request->memo;
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createFactoryDevice($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setFactoryDevice($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_factory_b',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?lid='.$lid);
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
        $js  = $contents = '';
        $lid = Session::get($this->hrefMain.'.search',0);
        $lid = SHCSLib::decode($lid);
        $local    = b_factory_a::getName($lid);
        //view元件參數
        $hrefBack   = $this->hrefMain.'?lid='.$lid;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html = $form->text('name');
        $form->add('nameT1', $html,Lang::get($this->langText.'.factory_11'),1);
        //場地
        $html  = $local;
        $form->add('nameT2', $html,Lang::get($this->langText.'.factory_31'));
        //GPSX
        $html = $form->text('GPSX');
        $form->add('nameT3', $html,Lang::get($this->langText.'.factory_18'));
        //GPSY
        $html = $form->text('GPSY');
        $form->add('nameT3', $html,Lang::get($this->langText.'.factory_19'));
        //說明
        $html = $form->text('memo');
        $form->add('nameT3', $html,Lang::get($this->langText.'.factory_9'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('b_factory_a_id',$lid);
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
