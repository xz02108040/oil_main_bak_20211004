<?php

namespace App\Http\Controllers\Emp;


use App\Exports\EmpMemberExport;
use App\Exports\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\SessTraits;
use App\Imports\ExcelImport;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\b_menu_group;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_factory;
use App\Model\User;
use App\Model\View\view_user;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Excel;

class ImportToEmpController extends Controller
{
    use BcustTrait,BcustATrait,EmpTrait, SessTraits;

    /*
    |--------------------------------------------------------------------------
    | ImportToEmpController
    |--------------------------------------------------------------------------
    |
    | excel 匯入 職員檔
    |
    |
    */

    /**
     * 環境參數
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     *
     * @return void
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
        $this->hrefMain         = 'exceltoemp';
        $this->hrefEmp          = 'emp';
        $this->langText         = 'sys_emp';

        $this->hrefMainDetail   = 'exceltoemp/';
        $this->hrefMainNew      = 'new_exceltoemp';
        $this->routerPost       = 'ImportToEmp';
        $this->routerErr        = 'exceltoemp_err';

        $this->pageTitleMain    = Lang::get($this->langText.'.title5');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list5');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new5');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit5');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pagedownBtn      = Lang::get('sys_btn.btn_29');//[按鈕]下載
        $this->pageErrBtn      = Lang::get('sys_btn.btn_74');//[按鈕]錯誤報告
    }

    /**
     * @return mixed 匯出錯誤報表
     */
    public function download()
    {
        if(Session::has('download.exceltoexport'))
        {
            return Excel::download(new ExcelExport(), Lang::get('sys_base.base_10167').date('Ymdhis').'.ods');
        }
    }

    /**
     * 首頁內容
     *
     * @return void
     */
    public function index()
    {
        set_time_limit(0);
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        if($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        }
        //參數
        $out = $js ='';
        $deptAry = be_dept::getSelect(0,0,0,'Y',0,2);
        $menuGroupAry   = b_menu_group::getSelect([2]);
        //view元件參數
        $tbTitle    = $this->pageTitleList;//列表標題
        $hrefMain   = $this->hrefMain;
        $hrefExcel  = 'excel/example3.xlsx';
        $btnExcel   = $this->pagedownBtn;
        $hrefBack   = $this->hrefEmp;
        $btnBack    = $this->pageBackBtn;
        $hrefDown   = $this->routerErr;
        $btnDown    = $this->pageErrBtn;

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(1, array($this->routerPost), 'POST', 1, TRUE);
        $form->addLinkBtn($hrefExcel, $btnExcel,2); //下載
        $form->addLinkBtn($hrefBack, $btnBack,1); //下載
        if(Session::has('download.exceltoexport'))
        {
            $form->addLinkBtn($hrefDown, $btnDown,5); //下載
        }
        $form->addHr();
        //部門
        $html = $form->select('be_dept_id',$deptAry,0,2);
        $form->add('nameT', $html, Lang::get($this->langText.'.emp_3'), 1);
        //權限群組
        $html = $form->select('b_menu_group_id',$menuGroupAry,1,2);
        $form->add('nameT', $html, Lang::get('sys_base.base_10710'), 1);
        //Excel
        $html = $form->file('excel',Lang::get($this->langText.'.emp_22'));
        $form->add('nameT', $html, Lang::get($this->langText.'.emp_20'), 1);

        //匯入excel
        $submitDiv = $form->submit(Lang::get('sys_btn.btn_17'), '1', 'agreeY') . '&nbsp;';
        $form->boxFoot($submitDiv);
        //輸出
        $out .= $form->output(1);

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTitle, $out));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> jsavascript
        //-------------------------------------------//
        $js = '$(document).ready(function() {


                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index', $retArray);
    }

    /**
     * 處理Excel資料，並匯入職員檔
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //資料不齊全
        if(!$request->be_dept_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10166'))
                ->withInput();
        }
        elseif (!$request->b_menu_group_id) {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10168'))
                ->withInput();
        }
        elseif (!$request->hasFile('excel')) {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10121'))
                ->withInput();
        } else {
            $this->getBcustParam();
            $did = $request->be_dept_id;
            $gid = $request->b_menu_group_id;
            $ip  = $request->ip();
            $menu = $this->pageTitleMain;
            $isSuc = $isErr = 0;
            $errStrCodeAry  = [1=>'emp_1002',2=>'emp_1003'];
            //Excel 匯入
            $excelAry = Excel::toArray(new ExcelImport(), $request->excel);
            if(!empty($excelAry) || !count($excelAry[0]))
            {
                $f = 1;
                $isOk = 1;
                $errCode = 0;
                $cnt = count($excelAry[0]) - 1;
                $msgAry = [];
                foreach ($excelAry[0] as $row) {
                    if ($f == 1 && ($row[0] != '姓名' || $row[1] != '職員編號'  || $row[2] != 'EMAIL')) {
                        //格式錯誤，退出
                        $isOk = 0;
                        Session::flash('message',Lang::get('sys_base.base_10169'));
                    }
                    if ($f > 1 && $isOk) {
                        $isIns = 1;
                        $emp_no     = trim($row[1]);
                        $name       = trim($row[0]);
                        $email      = trim($row[2]);

                        if(!$emp_no && !$name)
                        {
                            $isIns      = 0;
                            $errCode    = 0;
                        }
                        if(!$emp_no || !$name)
                        {
                            $isIns      = 0;
                            $errCode    = 1;
                        }
                        //職員編號重複
                        if($isIns && b_cust_e::isEmpNoExist($emp_no))
                        {
                            $isIns = 0;
                            $errCode    = 2;
                        }

                        if($isIns)
                        {
                            $tmp = [];
                            $tmp['name']        = $name;
                            $tmp['emp_no']      = $emp_no;

                            $tmp['bc_type']         = 2;
                            $tmp['bc_type_app']     = 1;
                            $tmp['b_menu_group_id'] = $gid;
                            $tmp['b_factory_id']    = 1;
                            $tmp['be_dept_id']      = $did;
                            $tmp['be_title_id']     = 1;
                            $tmp['account']         = $emp_no;
                            $tmp['password']        = $emp_no;
                            $tmp['isIN']            = 'Y';
                            $tmp['isAutoAccount']   = 'Y';
                            $tmp['head_img']        = '';
                            $tmp['sex']             = 'F';
                            $tmp['bloodRh']         = '';
                            $tmp['email1']          = $email;
                            $tmp['kin_kind']        = 1;
                            $tmp['boss_id']         = 0;
                            $tmp['attorney_id']     = 0;

                            $ret = $this->createBcust($tmp,$this->b_cust_id);
                            $id  = $ret;
                            //2-1. 更新成功
                            if($ret)
                            {
                                $isSuc++;
                                //動作紀錄
                                LogLib::putLogAction($this->b_cust_id,$menu,$ip,1,'b_cust',$id);
                                LogLib::putLogAction($this->b_cust_id,$menu,$ip,1,'b_cust_a',$id);
                                LogLib::putLogAction($this->b_cust_id,$menu,$ip,1,'b_cust_e',$id);
                            } else {
                                $isErr++;
                            }
                        } else {
                            if($errCode)
                            {
                                $isErr++;
                                $errStrCode = isset($errStrCodeAry[$errCode])? $errStrCodeAry[$errCode] : $errStrCodeAry[1];
                                $msgAry[] = Lang::get($this->langText.$errStrCode,['no'=>$emp_no]);
                            }
                        }
                    }
                    $f++;
                }

                if(count($msgAry)) Session::put('download.exceltoexport',$msgAry);
                //匯入結果
                $sessAlert = ($isErr > 0)? 'danger' : 'message';
                Session::flash($sessAlert,Lang::get($this->langText.'.emp_21',['amt'=>$cnt,'suc'=>$isSuc,'err'=>$isErr]));
            } else {
                //匯入格式不正確
                Session::flash('message',Lang::get('sys_base.base_10169'));
            }
        }


        return \Redirect::to($this->hrefMain);
    }
}
