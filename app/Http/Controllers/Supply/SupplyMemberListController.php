<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Emp\EmpTitleTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyMemberTrait;
use App\Http\Traits\Supply\SupplyTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\be_title;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\View\view_user;
use Storage;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class SupplyMemberListController extends Controller
{
    use SupplyMemberTrait,BcustTrait,BcustATrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyMemberListController
    |--------------------------------------------------------------------------
    |
    | 承攬商成員
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
        $this->hrefHome         = 'contractorlist';
        $this->hrefMain         = 'contractormemberlist';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'personinfo/';
        $this->hrefMainDetail2  = 'contractormemberidentitylist';
        $this->hrefMainDetail3  = 'etraningmember2';
        $this->hrefMainNew      = 'new_contractormember/';
        $this->routerPost       = 'postContractormember';

        $this->pageTitleMain    = Lang::get($this->langText.'.title2');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list2');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new2');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit2');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

        $this->fileSizeLimit1   = config('mycfg.file_upload_limit','102400');
        $this->fileSizeLimit2   = config('mycfg.file_upload_limit_name','10MB');
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
        $no = 0;
        $out = $js ='';
        $closeAry       = SHCSLib::getCode('CLOSE');
        $isInProjectAry = SHCSLib::getCode('IS_JOIN_PROJECT',1);
        $pid      = SHCSLib::decode($request->pid);
        if(!$pid && is_numeric($pid) && $pid > 0)
        {
            $msg = Lang::get($this->langText.'.supply_1000');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = 'pid='.$request->pid;
            $supply= b_supply::getName($pid);
        }

        //是否參與承攬項目
        $isInProject = ($request->isInProject)? $request->isInProject : '';
        if($request->has('clear'))
        {
            $isInProject = '';
            Session::forget($this->hrefMain.'.search');
        }
        if($isInProject)
        {
            Session::put($this->hrefMain.'.search.isInProject',$isInProject);
        } else {
            $isInProject = Session::get($this->hrefMain.'.search.isInProject','');
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew.$request->pid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefHome;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiSupplyMemberList($pid,$isInProject);
        Session::put($this->hrefMain.'.Record',$listAry);
        Session::put($this->langText.'.supply_id',$pid);
        Session::put($this->langText.'.pid',$request->pid);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain.'?'.$param,'POST','form-inline');
        //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //搜尋
        $html = $form->select('isInProject',$isInProjectAry,$isInProject,2,Lang::get($this->langText.'.supply_52'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_19')]; //成員
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_74')]; //國別
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_22')]; //行動電話
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_49')]; //承攬項目
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_51')]; //承攬身分
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_27')]; //教育訓練
//        $heads[] = ['title'=>Lang::get($this->langText.'.supply_7')];  //狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->b_cust_id;
                $did          = SHCSLib::encode($value->b_cust_id);
                $name1        = $value->name; //
                $name2        = $value->nation_name; //
                $name3        = $value->mobile1; //
                if($value->tel1)
                {
                    $name3 .= (strlen($name3))? '<br/>' : '';
                    $name3 .= $value->tel1;
                }
                $name4        = $value->blood.'('.$value->bloodRH.')'; //
                $name5        = $value->kin_user.'('.$value->kin_kind_name.')'; //
                $name6        = $value->kin_tel; //
                $name7        = $value->project; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseType  = $value->isClose == 'Y' ? 1 : 0;
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $IdentityBtn  = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,'','mid='.$did.'&k='),Lang::get('sys_btn.btn_30'),3); //按鈕
                $TraningBtn   = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail3,'','mid='.$did),Lang::get('sys_btn.btn_30'),4); //按鈕

                $btn          = ($isCloseType)? '' : HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_30'),1); //按鈕

                $tBody[] = ['1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '7'=>[ 'name'=> $name7],
                            '21'=>[ 'name'=> $IdentityBtn],
                            '22'=>[ 'name'=> $TraningBtn],
//                            '90'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
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
