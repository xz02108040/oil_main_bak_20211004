<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Http\Traits\Factory\FactoryTrait;
use App\Http\Traits\Factory\GuestTrait;
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
use App\Model\Factory\b_factory_b;
use App\Model\Supply\b_supply;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class GuestController extends Controller
{
    use GuestTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | GuestController
    |--------------------------------------------------------------------------
    |
    | 訪客簽到記錄功能
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
        //$this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'guest';
        $this->langText         = 'sys_base';

        $this->hrefMainDetail   = 'guest/';
        $this->hrefMainNew      = 'new_guest';
        $this->routerPost       = 'postGuest';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_30300');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_30301');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.base_30302');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.base_30303');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

//        dd('test');
    }
    /**
     * 首頁內容
     *
     * @return void
     */
    public function index(Request $request)
    {
        //讀取 Session 參數
        //$this->getBcustParam();
        //$this->getMenuParam();
        //參數
        $out = $js ='';
        $no  = 0;
        $sdate     = $request->sdate;
        $aid       = $request->aid; //廠區
        $b_factory_a_id = b_factory_b::isIDCodeExist($request->local);
        $store_id  = b_factory_a::getStoreId($b_factory_a_id); //廠區
        $store     = b_factory::getName($store_id); //廠區
        $today     = date('Y-m-d');
        $closeAry  = SHCSLib::getCode('CLOSE');
        //view元件參數
        $Icon      = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle   = $this->pageTitleList.$Icon.$store;//列表標題
        $hrefMain  = $this->hrefMain;
        $hrefNew   = $this->hrefMainNew.'?local='.$request->local;
        $btnNew    = $this->pageNewBtn;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;

        if($request->has('clear'))
        {
            $sdate = $aid = '';
            Session::forget($this->hrefMain.'.search');
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate',$today);
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid','N');
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiGuestList($store_id,$sdate,$aid);
        Session::put($this->hrefMain.'.Record',$listAry);
        Session::put($this->hrefMain.'.local',$request->local);
        Session::put($this->hrefMain.'.store_id',$store_id);
        Session::put($this->hrefMain.'.store',$store);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($store_id)
        {
            $form->addLinkBtn($hrefNew, $btnNew,2); //新增
            $form->addHr();
        }
        $html = '';
        $html.= $form->date('sdate',$sdate,3,Lang::get($this->langText.'.base_30318'));
        //$html.= $form->select('aid',$closeAry,$aid,2,Lang::get($this->langText.'.base_10807'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','','');
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30318')]; //拜訪日期
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30323')]; //訪客證
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30311')]; //來訪公司
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30312')]; //來訪人員
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30313')]; //聯絡電話
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30315')]; //拜訪單位
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30316')]; //拜訪對象
        $heads[] = ['title'=>Lang::get($this->langText.'.base_30317')]; //拜訪原因
        //$heads[] = ['title'=>Lang::get($this->langText.'.base_10807')]; //狀態

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->guest_comp; //
                $name2        = $value->guest_name; //
                $name3        = $value->guest_tel; //
                $name4        = $value->visit_dept; //
                $name5        = $value->visit_emp; //
                $name6        = $value->visit_purpose; //
                $name11       = $value->visit_sdate; //
                $name7        = $value->guest_no; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name11],
                            '11'=>[ 'name'=> $name7],
                            '2'=>[ 'name'=> $name1],
                            '3'=>[ 'name'=> $name2],
                            '4'=>[ 'name'=> $name3],
                            '5'=>[ 'name'=> $name4],
                            '6'=>[ 'name'=> $name5],
                            '7'=>[ 'name'=> $name6],
                            //'90'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
//                            '99'=>[ 'name'=> $btn ]
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
                    $("#sdate,#edate").datepicker({
                        format: "yyyy-mm-dd",
                        startDate: "today",
                        language: "zh-TW"
                    });
                    
                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'js'=>$js];
        return view('report',$retArray);
    }

    /**
     * 單筆資料 編輯
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
//        $this->getBcustParam();
//        $this->getMenuParam();
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $empAry     = b_cust_e::getSelect($id);
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
            $A1         = $getData->guest_comp; //
            $A2         = $getData->guest_name; //
            $A3         = $getData->guest_tel; //
            $A4         = $getData->visit_dept; //
            $A5         = $getData->visit_emp; //
            $A6         = $getData->visit_purpose; //
            $A7         = $getData->visit_sdate; //
            $A8         = $getData->guest_no; //


            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //來訪時間
        $html = $A7;
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30318'),1);
        //訪客證號
        $html = $A8;
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30323'),1);
        //來訪公司
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30311'),1);
        //來訪人員
        $html = $A2;
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30312'),1);
        //聯絡電話
        $html = $A3;
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30313'),1);
        //拜訪單位
        $html = $A4;
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30315'),1);
        //拜訪對象
        $html = $A5;
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30316'),1);
        //拜訪原因
        $html = $A6;
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30317'),1);
        //停用
        //$html = $form->checkbox('isClose','Y',$A99);
        //$form->add('isCloseT',$html,Lang::get($this->langText.'.base_10312'));
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        //$submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv  = $form->linkbtn($hrefBack, $btnBack,2);

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
                language: "zh-TW"
            });
        });';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'js'=>$js];
        return view('report',$retArray);
    }

    /**
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //資料不齊全
        if( !$request->has('agreeY'))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        else {
            //$this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
            $this->b_cust_id = 1;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        if($isNew)
        {
            if( !$request->guest_comp || !$request->guest_name || !$request->guest_tel|| !$request->guest_no)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_30320'))
                    ->withInput();
            }
            if( !$request->visit_dept || !$request->visit_emp || !$request->visit_purpose)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_30321'))
                    ->withInput();
            }
            if( !$request->visit_purpose)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_30322'))
                    ->withInput();
            }
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
            $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';
        } else {
            $upAry['b_factory_id']      = $request->store_id;
            $upAry['guest_no']          = $request->guest_no;
            $upAry['guest_comp']        = $request->guest_comp;
            $upAry['guest_name']        = $request->guest_name ? $request->guest_name : '';
            $upAry['guest_tel']         = $request->guest_tel ? $request->guest_tel : '';
            $upAry['gruest_id']         = $request->gruest_id ? $request->gruest_id : '';
            $upAry['visit_dept']        = $request->visit_dept ? $request->visit_dept : '';
            $upAry['visit_emp']         = $request->visit_emp ? $request->visit_emp : '';
            $upAry['visit_purpose']     = $request->visit_purpose ? $request->visit_purpose : '';
        }

        //新增
        if($isNew)
        {
            $ret = $this->createGuest($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setGuest($id,$upAry,$this->b_cust_id);
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
                $local      = Session::get($this->hrefMain.'.local');
                //動作紀錄
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_guest',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10163'));
                return \Redirect::to($this->hrefMain.'?local='.$local);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.base_10163');
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
//        $this->getBcustParam();
//        $this->getMenuParam();
        //參數
        $js = $contents = '';
        $local      = Session::get($this->hrefMain.'.local');
        $store_id   = Session::get($this->hrefMain.'.store_id');
        $store      = Session::get($this->hrefMain.'.store');
        //view元件參數
        $hrefBack   = $this->hrefMain.'?local='.$local;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header

        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //廠區
        $html = $store;
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_40106'),1);
        //訪客正
        $html = $form->text('guest_no');
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30323'),1);
        //來訪公司
        $html = $form->text('guest_comp');
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30311'),1);
        //來訪人員
        $html = $form->text('guest_name');
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30312'),1);
        //聯絡電話
        $html = $form->text('guest_tel');
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30313'),1);
        //拜訪單位
        $html = $form->text('visit_dept');
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30315'),1);
        //拜訪對象
        $html = $form->text('visit_emp');
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30316'),1);
        //拜訪原因
        $html = $form->text('visit_purpose');
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_30317'),1);

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_9'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('store_id',$store_id);
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
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'js'=>$js];
        return view('report',$retArray);
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
