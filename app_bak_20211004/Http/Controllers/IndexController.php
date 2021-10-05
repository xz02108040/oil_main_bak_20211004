<?php

namespace App\Http\Controllers;


use App\Http\Traits\BcustTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyRPCarTrait;
use App\Http\Traits\Supply\SupplyRPMemberTrait;
use App\Http\Traits\Supply\SupplyRPProjectLicenseTrait;
use App\Http\Traits\Supply\SupplyRPProjectTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\TokenLib;
use App\Model\Engineering\e_project;
use App\Model\sys_param;
use App\Model\WorkPermit\wp_work;
use Session;
use Lang;
use Auth;

class IndexController extends Controller
{
    use SessTraits,BcustTrait;
    use SupplyRPMemberTrait,SupplyRPProjectLicenseTrait,SupplyRPProjectTrait;
    use SupplyRPCarTrait,WorkPermitWorkOrderTrait;
    /*
    |--------------------------------------------------------------------------
    | Index Controller
    |--------------------------------------------------------------------------
    |
    | 首頁
    |
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
        $this->hrefHome      = '/';

        $this->pageTitleN    = Lang::get('sys_base.base_10200');//標題
        //個人佈告欄
        $this->personLoginSuc    = Lang::get('sys_base.base_10211');//標題
        $this->personLoginErr    = Lang::get('sys_base.base_10212');//標題
        $this->personSignErr     = Lang::get('sys_base.base_10213');//標題
        $this->personSignErr2    = Lang::get('sys_base.base_10214');//標題

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
        //參數
        $personAry = []; //個人資訊
        $StoreName = (isset($this->user_title))? '-'.$this->user_title : '';
        $today = date('Y-m-d');

        //-------------------------------------------//
        //View
        //-------------------------------------------//

        $out = new ContentLib();
        //上次登入成功時間
        $personAry[] = $out->info_box($this->personLoginSuc,$this->login_suc_at,'','user',2,'myinfo');
        //上次登入失敗時間
        if($this->login_err_at)
        {
            $personAry[] = $out->info_box($this->personLoginErr,$this->login_err_at,'','user',5);
        }
        //2019-08-06 如果電子簽名沒有設定，出現提示
        if(!$this->sign_url)
        {
            $personAry[] = $out->info_box($this->personSignErr,$this->personSignErr2,'','user',5,url('/myinfo'));
        }
        //個人儀表板
        $out->rowTo($personAry);
        if($this->bc_type == 2) {
            $personAry2 = [];
            //成員申請
            if(in_array('rp_contractormember', array_keys($this->menu_auth)))
            {
                $count1  = 0;
                $aproc              = ($this->isRootDept)? 'P' : 'A';
                $allowProjectAry    = ($this->isRootDept)? [] : $this->allowProjectAry;
                $dataAry = $this->getApiSupplyRPMemberMainList($aproc,$allowProjectAry);
                if(count($dataAry))
                {
                    foreach ($dataAry as $val)
                    {
                        $count1 += $val['amt'];
                    }
                }
                if ($count1) {
                    $title     = Lang::get('sys_supply.title7');
                    $personAry2[] = $out->small_box( $title, $count1, 'paper-airplane', 4, 'rp_contractormember');
                }
            }
            //審查車輛申請
            if(in_array('rp_contractorcar', array_keys($this->menu_auth)))
            {
                $total_amt          = 0;
                $aproc              = ($this->isRootDept)? 'P' : 'A';
                $allowProjectAry    = ($this->isRootDept)? [] : $this->allowProjectAry;
                $count1 = $this->getApiSupplyRPCarMainList($aproc, $allowProjectAry, 'Y');
                foreach ($count1 as $val)
                {
                    $total_amt += $val->amt;
                }
                if ($total_amt) {
                    $title     = Lang::get('sys_supply.title11');
                    $personAry2[] = $out->small_box( $title, $total_amt, 'paper-airplane', 4, 'rp_contractorcar');
                }
            }
            //審查工程案件之工程身分審查
            if(in_array('rp_contractorprojectidentity', array_keys($this->menu_auth)))
            {
                $aproc              = ($this->isRootDept)? 'P' : 'A';
                $allowProjectAry    = ($this->isRootDept)? [] : $this->allowProjectAry;
                $count1 = $this->getApiSupplyRPProjectMemberLicenseMainList($aproc, $allowProjectAry, 'Y');
                if ($count1) {
                    $title     = Lang::get('sys_supply.title16');
                    $personAry2[] = $out->small_box( $title, $count1, 'paper-airplane', 4, 'rp_contractorprojectidentity');
                }
            }
            //審查加入工程案件
            if(in_array('rp_contractorproject', array_keys($this->menu_auth)))
            {
                $total_amt          = 0;
                $aproc              = ($this->isRootDept)? 'P' : 'A';
                $allowProjectAry    = ($this->isRootDept)? [] : $this->allowProjectAry;
                $count1 = $this->getApiSupplyRPProjectMainList($aproc, $allowProjectAry);
                foreach ($count1 as $val)
                {
                    $total_amt += $val->amt;
                }
                if ($total_amt) {
                    $title     = Lang::get('sys_supply.title15');
                    $personAry2[] = $out->small_box( $title, $total_amt, 'paper-airplane', 4, 'rp_contractorproject');
                }
            }
            //工作許可證-申請
            if(in_array('exa_wpworkorder', array_keys($this->menu_auth)))
            {
                $count1 = $this->getApiWorkPermitWorkOrderByProject($this->be_dept_id,'Y');
                if ($count1) {
                    $title     = Lang::get('sys_workpermit.title21');
                    $personAry2[] = $out->small_box( $title, $count1, 'paper-airplane', 4, 'exa_wpworkorder');
                }
            }
            if(in_array('exa_wpworkorder2', array_keys($this->menu_auth)))
            {
                //工作許可證－啟動
                $total_amt = $this->getApiWorkPermitWorkOrderByProcess(2,$this->be_dept_id);
                if ($total_amt) {
                    $title     = Lang::get('sys_workpermit.edit24h');
                    $personAry2[] = $out->small_box( $title, $total_amt, 'paper-airplane', 5, 'exa_wpworkorder2?aproc=P');
                }

                //工作許可證－轄區簽核
                $total_amt = $this->getApiWorkPermitWorkOrderByProcess(1,$this->be_dept_id);
                if ($total_amt) {
                    $title     = Lang::get('sys_workpermit.edit24b');
                    $personAry2[] = $out->small_box( $title, $total_amt, 'paper-airplane', 5, 'exa_wpworkorder2?aproc=P');
                }

                //工作許可證－監工簽核
                $total_amt = $this->getApiWorkPermitWorkOrderByProcess(3,$this->be_dept_id);
                //dd($total_amt);
                if ($total_amt) {
                    $title     = Lang::get('sys_workpermit.edit24d');
                    $personAry2[] = $out->small_box( $title, $total_amt, 'paper-airplane', 5, 'exa_wpworkorder2?aproc=P');
                }

                //工作許可證－會簽簽核
                $total_amt = $this->getApiWorkPermitWorkOrderByProcess(4,$this->be_dept_id);
                //dd($total_amt);
                if ($total_amt) {
                    $title     = Lang::get('sys_workpermit.edit24e');
                    $personAry2[] = $out->small_box( $title, $total_amt, 'paper-airplane', 5, 'exa_wpworkorder2?aproc=P');
                }

                //工作許可證－轄區主簽者
                $total_amt = $this->getApiWorkPermitWorkOrderByProcess(5,$this->be_dept_id,$this->be_title_id,$this->store_id);
                //dd($total_amt);
                if ($total_amt) {
                    $title     = Lang::get('sys_workpermit.edit24f');
                    $personAry2[] = $out->small_box( $title, $total_amt, 'paper-airplane', 5, 'exa_wpworkorder2?aproc=P');
                }

                //工作許可證－廠區主簽者
                $total_amt = $this->getApiWorkPermitWorkOrderByProcess(6,$this->be_dept_id,$this->be_title_id,$this->store_id);
                //dd($total_amt);
                if ($total_amt) {
                    $title     = Lang::get('sys_workpermit.edit24g');
                    $personAry2[] = $out->small_box( $title, $total_amt, 'paper-airplane', 5, 'exa_wpworkorder2?aproc=P');
                }

                //工作許可證－收工回簽簽核
                $aprocSearch= ['O'];
                $wpSearch   = [0,0,'','',0];
                $storeSearch= [0,0,0];
                $depSearch  = [0,$this->be_dept_id,0,0,0,0];
                $dateSearch = [$today,'','Y'];
                $count = $this->getApiWorkPermitWorkOrderList(0,$aprocSearch,$wpSearch,$storeSearch,$depSearch,$dateSearch,['N',0],'Y');
                $total_amt = 0;
                foreach ($count as $val)
                {
                    $amt = isset($val['amt'])? $val['amt'] : 0;
                    $total_amt += $amt;
                }
                if ($total_amt) {
                    $title     = Lang::get('sys_workpermit.edit24a');
                    $personAry2[] = $out->small_box( $title, $total_amt, 'paper-airplane', 5, 'exa_wpworkorder2?aproc=O');
                }
            }
            //工作許可證－補人
            if(in_array('exa_wpworkorder3', array_keys($this->menu_auth)))
            {
                $total_amt = wp_work::hasApplyAddmember($this->be_dept_id);
                //dd($total_amt);
                if ($total_amt) {
                    $title     = Lang::get('sys_workpermit.title27');
                    $personAry2[] = $out->small_box( $title, $total_amt, 'paper-airplane', 5, 'exa_wpworkorder3');
                }
            }

            //個人儀表板
            $out->rowTo($personAry2);
        }
        $content = $out->output();

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleN.$StoreName,'content'=>$content,'menu'=>$this->sys_menu];

        return view('index',$retArray);
    }
}
