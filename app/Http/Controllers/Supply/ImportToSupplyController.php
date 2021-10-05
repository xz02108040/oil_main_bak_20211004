<?php

namespace App\Http\Controllers\Supply;


use App\Exports\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyMemberTrait;
use App\Imports\ExcelImport;
use App\Imports\SupplyMemberImport;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_supply_user;
use App\Model\View\view_user;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Excel;

class ImportToSupplyController extends Controller
{
    use BcustTrait,BcustATrait,SupplyMemberTrait, SessTraits;

    /*
    |--------------------------------------------------------------------------
    | ImportToSupplyController
    |--------------------------------------------------------------------------
    |
    | excel 匯入 承攬商職員檔
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
    public function __construct()
    {
        //身分驗證
        $this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'exceltocontractor';
        $this->hrefSupply       = 'contractor';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'exceltocontractor/';
        $this->hrefMainNew      = 'new_exceltocontractor';
        $this->routerPost       = 'ImportToContractor';
        $this->routerErr        = 'exceltocontractor_err';

        $this->pageTitleMain    = Lang::get($this->langText.'.title6');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list6');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new6');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit6');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pagedownBtn      = Lang::get('sys_btn.btn_29');//[按鈕]下載
        $this->pageErrBtn       = Lang::get('sys_btn.btn_74');//[按鈕]錯誤報告
    }

    /**
     * @return mixed 匯出錯誤報表
     */
    public function download()
    {
        if(Session::has('download.exceltoexport'))
        {
            return Excel::download(new ExcelExport(), Lang::get('sys_base.base_10167').date('Ymdhis').'.xlsx');
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
        //參數
        $out = $js ='';
        $supplyAry = b_supply::getSelect();
        //view元件參數
        $tbTitle    = $this->pageTitleList;//列表標題
        $hrefMain   = $this->hrefMain;
        $hrefExcel  = 'excel/example1.xlsx';
        $btnExcel   = $this->pagedownBtn;
        $hrefBack   = $this->hrefSupply;
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
        //承攬商
        $html = $form->select('b_supply_id',$supplyAry,0,2);
        $form->add('nameT', $html, Lang::get($this->langText.'.supply_12'), 1);
        //Excel
        $html = $form->file('excel',Lang::get($this->langText.'.supply_63'));
        $form->add('nameT', $html, Lang::get($this->langText.'.supply_61'), 1);

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
        if(!$request->b_supply_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10125'))
                ->withInput();
        }
        elseif (!$request->hasFile('excel')) {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10121'))
                ->withInput();
        } else {
            $this->getBcustParam();
            $isTestshow     = $request->has('testshow')? 1 : 0;
            $fid            = $request->b_supply_id;
            $supply         = b_supply::getName($fid);
            $supplyno       = b_supply::getTaxNum($fid);
            $ip             = $request->ip();
            $errStrCodeAry  = [1=>'supply_77',2=>'supply_1042',3=>'supply_1040',4=>'supply_82'];
            if(!$supplyno || !is_numeric($supplyno) || strlen($supplyno) != 8)
            {
                //該廠商「:name 」統編「:no 」異常，請確認是否符合八碼數字
                Session::flash('danger',Lang::get($this->langText.'.supply_1043',['name'=>$supply,'no'=>$supplyno]));
            } else {
                //Excel 匯入
                $excelAry = Excel::toArray(new ExcelImport, $request->excel);
//                 dd($excelAry);
                if (!empty($excelAry) || !count($excelAry[0])) {
                    $f = 1;
                    $isSuc = $isErr = 0;
                    $isOk = 1;
                    $errCode = 0;
                    $isNotSelfSupply = 0;
                    $rowRealTotal = 0;
                    $cnt = count($excelAry[0]) - 1;
                    $msgAry = [];
//                    dd([$isTestshow,$excelAry[0]]);
                    foreach ($excelAry[0] as $row) {
                        if ($f == 1 && ($row[0] != '統編' || $row[1] != '姓名' || $row[2] != '身分證')) {
                            //格式錯誤，退出
                            $isOk = 0;
                            $errCode = 5; //錯誤格式
                            break;
                        }
                        if ($f > 1 && ($row[0] && $row[1] && $row[2] && $row[3] && $row[4])) {
                            $isIns = 1;
                            $rowRealTotal++;
                            $supplyno2 = sprintf("%08d", trim($row[0]));
                            $name = trim($row[1]);
                            $bcid = trim($row[2]);
                            $birth = trim($row[3]);
                            $blood = trim($row[4]);
                            $tel = trim($row[5]);
                            $mobile = trim(str_replace('-', '', trim($row[6])));
                            $addr1 = trim($row[7]);
                            $kin_user = trim($row[8]);
                            $kin_tel = trim($row[9]);
                            $kin_addr = trim($row[10]);

                            if (!$supplyno2 && !$name && !$bcid && !$birth) {
                                $isIns = 0;
                                $errCode = 0;
                            }

                            //非該廠商之統編，請先確認匯入內容!
                            if (($supplyno != $supplyno2 || !$supplyno2) && ($name && $bcid && $birth)) {
                                $isIns = 0;
                                $isNotSelfSupply = 1;
                                break;
                            }
                            //如果 主要資料不齊全
                            if ($isIns && (!$name || !$birth || !$mobile || !$bcid)) {
                                $isIns = 0;
                                $errCode = 2;
                            }

                            $rp_bcust_id = CheckLib::checkBCIDExist($bcid);
                            $rp_bcust_name = ($rp_bcust_id) ? User::getName($rp_bcust_id) . '，' . view_supply_user::getSupplyName($rp_bcust_id) : '';
                            //行動電話重複
                            if ($isIns && $mobile && $rp_bcust_id) {
                                $isIns = 0;
                                $errCode = 3;
                            }

                            //新增一筆
                            if ($isIns) {
                                if (is_numeric($birth)) $birth = SHCSLib::tranExcelDate($birth);
                                $tmp = [];
                                //b_cust
                                $tmp['name']        = $name;
                                $tmp['bc_id']       = $bcid;
                                $tmp['blood']       = $blood;
                                $tmp['tel1']        = $tel;
                                $tmp['addr1']       = $addr1;
                                $tmp['kin_user']    = $kin_user;
                                $tmp['kin_tel']     = $kin_tel;
                                $tmp['kin_addr']    = $kin_addr;

                                $tmp['mobile1'] = trim(str_replace('-', '', $mobile));
                                $tmp['birth'] = ($birth) ? date('Y-m-d', strtotime($birth)) : '';

                                //b_cust_a
                                $tmp['bc_type'] = 3;
                                $tmp['bc_type_app'] = 0;
                                $tmp['b_menu_group_id'] = 1;
                                $tmp['b_supply_id'] = $fid;
                                $tmp['account'] = $bcid;
                                $tmp['password'] = substr($bcid, -4);
                                $tmp['isIN'] = 'N';
                                $tmp['isAutoAccount'] = 'N';
                                $tmp['head_img'] = '';
                                $tmp['sex'] = (strlen($bcid) && substr($bcid, 1, 1) == 2) ? 'F' : 'M';
                                $tmp['bloodRh'] = '';
                                $tmp['email1'] = '';
                                $tmp['kin_kind'] = 1;

                                $ret = $this->createBcust($tmp, $this->b_cust_id);
                                $id = $ret;
                                //2-1. 更新成功
                                if ($ret) {
                                    $isSuc++;
                                    //動作紀錄
                                    LogLib::putLogAction($this->b_cust_id, $this->pageTitleMain, $ip, 1, 'b_cust', $id);
                                    LogLib::putLogAction($this->b_cust_id, $this->pageTitleMain, $ip, 1, 'b_cust_a', $id);
                                    LogLib::putLogAction($this->b_cust_id, $this->pageTitleMain, $ip, 1, 'b_supply_member', $id);
                                } else {
                                    $isErr++;
                                    $msgAry[] = Lang::get($this->langText . '.supply_66', ['bid' => $tmp['name']]);
                                }
                            } else {

                                if ($errCode) {
                                    $isErr++;
                                    $errStrCode = isset($errStrCodeAry[$errCode]) ? $errStrCodeAry[$errCode] : $errStrCodeAry[1];
                                    $msgAry[][1] = Lang::get($this->langText . '.' . $errStrCode, ['bid' => $name, 'account' => $bcid, 'name' => $rp_bcust_name]);
                                } else {
                                    $rowRealTotal--;
                                    $cnt--;
                                }
                            }
                        }
                        $f++;
                    }

                    if ($isNotSelfSupply) {
                        //匯入內容非選擇廠商之統編，請先確認匯入承攬商與內容正確性!
                        Session::flash('danger', Lang::get($this->langText . '.supply_1039'));
                    } elseif ($errCode == 5) {
                        Session::flash('danger', Lang::get('sys_base.base_10169'));
                    } else {
                        if (count($msgAry)) {
                            Session::put('download.exceltoexport', $msgAry);
                            $errors = Lang::get($this->langText . '.supply_62', ['amt' => $rowRealTotal, 'suc' => $isSuc, 'err' => $isErr, 'msg' => '']);
                            Session::flash('danger', $errors);
                        } else {
                            Session::flash('message', Lang::get($this->langText . '.supply_62', ['amt' => $rowRealTotal, 'suc' => $isSuc, 'err' => $isErr, 'msg' => '']));
                        }
                    }
                } else {
                    Session::flash('message',Lang::get('sys_base.base_10169'));
                }
            }
        }
        return \Redirect::to($this->hrefMain);
    }
}
