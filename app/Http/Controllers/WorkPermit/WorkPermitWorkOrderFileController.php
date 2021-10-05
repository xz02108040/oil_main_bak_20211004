<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\LicenseTrait;
use App\Http\Traits\Engineering\EngineeringTypeTrait;
use App\Http\Traits\Factory\FactoryTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkCheckKindTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_license_type;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_f;
use App\Model\Engineering\e_project_s;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_e;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_door_supply_whitelist_pass;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_check_kind;
use App\Model\WorkPermit\wp_check_kind_f;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_identity;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_work_list;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkPermitWorkOrderFileController extends Controller
{
    use WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,SessTraits;
    use WorkPermitWorkOrderListTrait,WorkPermitWorkOrderItemTrait;
    use WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderDangerTrait;
    use WorkCheckKindTrait;
    use PushTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitWorkOrderFileController
    |--------------------------------------------------------------------------
    |
    | 工單 [危險作業之檔案下載區] 維護
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
        $this->hrefMain1        = 'wpworkorder_file';
        $this->hrefMain2        = 'wpworkorder';
        $this->langText         = 'sys_workpermit';

        $this->routerPost1      = 'wpworkorderfileList';

        $this->pageTitleMain    = Lang::get($this->langText.'.title30');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list30');//標題列表

        $this->pageNewBtn       = Lang::get('sys_btn.btn_11');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_30');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageNextBtn      = Lang::get('sys_btn.btn_37');//[按鈕]下一步

    }

    /**
     * 危險作業檔案下載區 專用
     */
    public function index(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents = '';
        $router     = $this->routerPost1;
        $out        = "";
        //view元件參數
        $tbTitle    = $this->pageTitleList; //table header
        $hrefBack = $this->hrefMain2;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        //  Data
        //-------------------------------------------//
        $data = $this->getApiWorkCheckKindList();
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($router,''),'POST',1,TRUE);
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        $out .= $form->output(1);
        //基本參數
        $tBody = $heads = [];
        //table
        $table = new TableLib();
        //標題
        $heads[] = ['title'=>'NO']; //NO
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_215')]; //危險作業
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_211')]; //下載檔案
        $table->addHead($heads,0);

        if(count($data))
        {
            $NO = 1;
            foreach ($data as $val)
            {
                $NO++;
                $name1        = HtmlLib::Color($val->name,'',1);
                $name2        = wp_check_kind_f::getDownloadBtn($val->id);
                $tBody[] = [
                    '0'=>[ 'name'=> $NO,'b'=>1,'style'=>'width:5%;'],
                    '11'=>[ 'name'=> $name1,'style'=>'width:25%;'],
                    '12'=>[ 'name'=> $name2],
                ];
            }

        }
        $table->addBody($tBody);

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
                    
                    
                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }
}
