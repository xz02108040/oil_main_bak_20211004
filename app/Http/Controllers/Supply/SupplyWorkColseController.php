<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\Emp\EmpTitleTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyTrait;
//use App\Http\Traits\Factory\DoorTrait;
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

class SupplyWorkColseController extends Controller
{
    use SupplyTrait,SessTraits/*,DoorTrait*/;
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
    public function __construct()
    {
        //身分驗證
        $this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'SupplyWorkColse';
        $this->hrefExcel        = 'exceltocontractor';
        $this->hrefUser         = 'userc';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'SupplyWorkColse/';
        $this->hrefMainDetail2  = 'contractormember';
        $this->hrefMainNew      = 'new_contractor';
        $this->routerPost       = 'postsupplyworkcolse';

        $this->pageTitleMain    = Lang::get($this->langText.'.title17');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list17');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new1');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit1');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_81');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageExcelBtn     = Lang::get('sys_btn.btn_82');//[按鈕]匯入

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
        $no  = 0;
        $out = $js ='';
		$upAry = '';
        $closeAry = SHCSLib::getCode('CLOSE');
		$wc_btn1 = $request->Workopen;
		$wc_btn2 = $request->Workcolse;
		//echo "測試抓取1:".$wc_btn1." 測試抓取2".$wc_btn2;
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefExcel= $this->hrefExcel;
        $btnExcel = $this->pageExcelBtn;
        
		if(($wc_btn1!="")||($wc_btn2!=""))
		{
			
			if($wc_btn1!="")
			{
				$upAry	= 'Y';

			}
			if($wc_btn2!="")
			{
				$upAry	= 'N';
			}
			$ret = $this->setWorkState($upAry);
			//print_r($ret);
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
					//LogLib::putLogAction($this->b_cust_id,$this->pageTitleMain,$request->ip(),'','b_supply');

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
		
		//$hrefBack = $this->hrefHome;
		//$btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiSupplyList();
        Session::put($this->hrefMain.'.Record',$listAry);
		//print_r($listAry);
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html= $form->submit(Lang::get('sys_btn.btn_85'),'5','Workopen');
        $html.= $form->submit(Lang::get('sys_btn.btn_86'),'2','Workcolse');
        $form->addRowCnt($html);
		//$form->addLinkBtn($hrefMain, $btnNew,2); //新增
        //$form->addLinkBtn($hrefExcel, $btnExcel,1); //匯入
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
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
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_7')]; //狀態
		$heads[] = ['title'=>Lang::get($this->langText.'.supply_84')]; //狀態
		
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
				$isWorkClose  = isset($closeAry[$value->isWorkClose])? $closeAry[$value->isWorkClose] : '' ; //停用
                $isWorkCloseColor = $value->isWorkClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $MemberBtn    = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),3); //按鈕
                $UserBtn      = HtmlLib::btn(SHCSLib::url($this->hrefUser,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '11'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
							'21'=>[ 'name'=> $isWorkClose,'label'=>$isWorkCloseColor],
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
        $levelAry = SHCSLib::getCode('BE_TITLE_LEVEL');
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $this->getData($id);
		//$test = $this->createDoorInoutRecord('123456780013','1','1','1','1','2020-09-01');
		//print_r($test);
		//print_r($getData);
		
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
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
			$A101        = ($getData->work_close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->work_close_user,'time'=>$getData->work_close_stamp]) : ''; //
			$A100        = ($getData->isWorkClose == 'Y')? true : false;
			
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
		//門禁綁工單停用
        $html = $form->checkbox('isWorkClose','Y',$A100);
        $form->add('isWorkCloseT',$html,Lang::get($this->langText.'.supply_83'));
		if($A100)
        {
            $html = $A101;
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
        elseif(!$request->tax_num || !is_numeric($request->tax_num) || strlen($request->tax_num) != 8)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_81',['no'=>$request->tax_num]))
                ->withInput();
        }
        elseif(b_supply::isNameExist($request->name,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1014'))
                ->withInput();
        }
        elseif(b_supply::isTaxNumExist($request->tax_num,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1024'))
                ->withInput();
        }
        elseif(mb_strlen(trim($request->sub_name)) > 10 || mb_strlen(trim($request->sub_name)) == 0)
        {
            //公司暱稱請少於10個字(2020-04-24)
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1023'))
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
        $upAry['tax_num']           = isset($request->tax_num)? $request->tax_num : '';
        $upAry['boss_name']         = isset($request->boss_name)? $request->boss_name : '';
        $upAry['tel1']              = isset($request->tel1)? $request->tel1 : '';
        $upAry['tel2']              = isset($request->tel2)? $request->tel2 : '';
        $upAry['fax1']              = isset($request->fax1)? $request->fax1 : '';
        $upAry['fax2']              = isset($request->fax2)? $request->fax2 : '';
        $upAry['email']             = isset($request->email)? $request->email : '';
        $upAry['address']           = isset($request->address)? $request->address : '';
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';
		$upAry['isWorkClose']       = ($request->isWorkClose == 'Y')? 'Y' : 'N';
        //新增
        if($isNew)
        {
            $ret = $this->createSupply($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
			//echo "HIHI";
			//exit;
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
