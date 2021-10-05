<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\Emp\EmpTitleTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\be_title;
use App\Model\Supply\b_supply;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class SupplyController extends Controller
{
    use SupplyTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyController
    |--------------------------------------------------------------------------
    |
    | 承攬商公司
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
        $this->hrefMain         = 'contractor';
        $this->hrefExcel        = 'exceltocontractor';
        $this->hrefUser         = 'userc';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'contractor/';
        $this->hrefMainDetail2  = 'contractormember';
        $this->hrefMainNew      = 'new_contractor';
        $this->routerPost       = 'postContractor';

        $this->pageTitleMain    = Lang::get($this->langText.'.title1');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list1');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new1');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit1');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageExcelBtn     = Lang::get('sys_btn.btn_17');//[按鈕]匯入

    }
    /**
     * 首頁內容
     *
     * @return void
     */
    public function index()
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $no  = 0;
        $out = $js ='';
        $closeAry = SHCSLib::getCode('CLOSE');
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefExcel= $this->hrefExcel;
        $btnExcel = $this->pageExcelBtn;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiSupplyList();
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefExcel, $btnExcel,1); //匯入
        //$form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_1')]; //公司名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_4')]; //統編
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_3')]; //負責人
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_9')]; //電話1
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_38')]; //帳號管理
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_19')]; //成員管理
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_7')]; //狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->name; //
                $name2        = $value->tax_num; //
                $name3        = $value->boss_name; //
                $name4        = ($value->tel1)? $value->tel1.($value->tel2 ? ','.$value->tel2 : '') : $value->fax2; //
                $name5        = ($value->fax1)? $value->fax1.($value->fax2 ? ','.$value->fax2 : '') : $value->fax2; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $MemberBtn    = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),3); //按鈕
                $UserBtn      = HtmlLib::btn(SHCSLib::url($this->hrefUser,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕
                $btn          = (($this->isWirte == 'Y'))? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $UserBtn],
                            '6'=>[ 'name'=> $MemberBtn],
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
        $levelAry = SHCSLib::getCode('BE_TITLE_LEVEL');
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
            $A2         = $getData->tax_num; //
            $A3         = $getData->boss_name; //
            $A4         = $getData->tel1; //
            $A5         = $getData->sub_name; //
            $A6         = $getData->fax1; //
            $A7         = $getData->fax2; //
            $A8         = $getData->email; //
            $A9         = $getData->address; //

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //公司名稱
        $html = $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_1'),1);
        //公司暱稱
        $html = $form->text('sub_name',$A5).HtmlLib::Color(Lang::get($this->langText.'.supply_1023'),'red');
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_73'),1);
        //統編
        $html = $form->text('tax_num',$A2);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_4'),1);
        //負責人
        $html = $form->text('boss_name',$A3);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_3'));
        //電話
        $html  = $form->text('tel1',$A4);
        //$html .= $form->text('tel2',$A5);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_9'));
        //傳真
        $html  = $form->text('fax1',$A6);
        //$html .= $form->text('fax2',$A7);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_14'));
        //email
        $html = $form->text('email',$A8);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_8'));
        //地址
        $html = $form->text('address',$A9);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_13'));
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.supply_18'));
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
        elseif(b_supply::isNameExist($request->name,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1014'))
                ->withInput();
        }
        elseif(mb_strlen(trim($request->sub_name)) > 4 || mb_strlen(trim($request->sub_name)) == 0)
        {
            //公司暱稱請少於四個字
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1023'))
                ->withInput();
        }
        elseif($request->tax_num && (!(strlen($request->tax_num) > 7) || !is_numeric($request->tax_num)))
        {
            //公司統編八碼數字
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1024'))
                ->withInput();
        }
        elseif(b_supply::isSubNameExist($request->sub_name,SHCSLib::decode($request->id)))
        {
            //簡稱已經存在
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1026'))
                ->withInput();
        }
        elseif(b_supply::isTaxExist($request->tax_num,SHCSLib::decode($request->id)))
        {
            //公司統編已經存在
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1025'))
                ->withInput();
        }
        elseif($request->email && !CheckLib::isMail($request->email))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1015'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id   = SHCSLib::decode($request->id);
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
        $upAry['name']              = trim($request->name);
        $upAry['sub_name']          = trim($request->sub_name);
        $upAry['tax_num']           = $request->tax_num;
        $upAry['boss_name']         = $request->boss_name;
        $upAry['tel1']              = $request->tel1;
        $upAry['tel2']              = $request->tel2;
        $upAry['fax1']              = $request->fax1;
        $upAry['fax2']              = isset($request->fax2)? $request->fax2 : '';
        $upAry['email']             = $request->email;
        $upAry['address']           = $request->address;
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createSupply($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSupply($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply',$id);

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
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //公司名稱
        $html = $form->text('name');
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_1'),1);
        //公司名稱
        $html = $form->text('sub_name').HtmlLib::Color(Lang::get($this->langText.'.supply_1023'),'red');
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_73'));
        //統編
        $html  = $form->text('tax_num');
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_4'));
        //負責人
        $html  = $form->text('boss_name');
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_3'));
        //電話
        $html  = $form->text('tel1');
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_9'));
        //電話
        //$html  = $form->text('tel2');
        //$form->add('nameT2', $html,Lang::get($this->langText.'.supply_10'));
        //傳真
        $html  = $form->text('fax1');
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_14'));
        //傳真
        //$html  = $form->text('fax2');
        //$form->add('nameT2', $html,Lang::get($this->langText.'.supply_15'));
        //email
        $html = $form->text('email');
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_8'));
        //地址
        $html = $form->text('address');
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_13'));

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
