<?php

namespace App\Http\Controllers\Supply;


use App\Exports\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\TraningMemberTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyMemberLicenseTrait;
use App\Http\Traits\Supply\SupplyMemberTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Http\Request;
use App\Imports\SupplyMemberImport;
use Session;
use Lang;
use Auth;
use Excel;

class ImportToTraningController extends Controller
{
    use BcustTrait,BcustATrait,SupplyMemberTrait,SupplyMemberIdentityTrait,SupplyMemberLicenseTrait,TraningMemberTrait, SessTraits;

    /*
    |--------------------------------------------------------------------------
    | ImportToSupplyController
    |--------------------------------------------------------------------------
    |
    | excel 匯入 職員報名教育訓練功能
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
        $this->hrefMain         = 'exceltotraning';
        $this->hrefSupply       = 'etraning';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'exceltotraning/';
        $this->hrefMainNew      = 'new_exceltotraning';
        $this->routerPost       = 'ImportToTraning';
        $this->routerErr        = 'exceltotraning_err';

        $this->pageTitleMain    = Lang::get($this->langText.'.title6');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list6');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new6');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit6');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pagedownBtn      = Lang::get('sys_btn.btn_29');//[按鈕]下載
        $this->pageErrBtn       = Lang::get('sys_btn.btn_71');//[按鈕]下載
    }

    public function download()
    {
        if(Session::has('download.exceltoexport'))
        {
            return Excel::download(new ExcelExport(), '匯入失敗原因'.date('Ymdhis').'.xlsx');
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
        $hrefExcel  = 'excel/example5.xlsx';
        $btnExcel   = $this->pagedownBtn;
        $hrefBack   = $this->hrefSupply;
        $btnBack    = $this->pageBackBtn;
        $hrefDown   = $this->routerErr;
        $btnDown    = $this->pageErrBtn;

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(1, array($this->routerPost), 'POST', 1, TRUE);
        $form->addLinkBtn($hrefExcel, $btnExcel,2); //下載DEMO
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回

        if(Session::has('download.supplymember'))
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
            $errStrCodeAry  = [1=>'supply_77',2=>'supply_76',3=>'supply_75',4=>'supply_82'];
            if(!$supplyno || !is_numeric($supplyno) || strlen($supplyno) != 8)
            {
                //該廠商「:name 」統編「:no 」異常，請確認是否符合八碼數字
                Session::flash('danger',Lang::get($this->langText.'.supply_79',['name'=>$supply,'no'=>$supplyno]));
            } else {
                //Excel 匯入
                $excelAry = Excel::toArray(new SupplyMemberImport, $request->excel);
//                 dd($excelAry);
                if(!empty($excelAry) || !count($excelAry[0]))
                {
                    $f = 1;
                    $isSuc = $isErr = 0;
                    $isOk = 1;
                    $errCode = 0;
                    $isNotSelfSupply = 0;
                    $rowRealTotal = 0;
                    $cnt = count($excelAry[0]) - 1;
                    $today = date('Y-m-d');
                    $msgAry = [];
//                    dd([$isTestshow,$excelAry[0]]);
                    foreach ($excelAry[0] as $row)
                    {
                        if($f == 1 && ( $row[0] != '統編' || $row[1] != '申請人姓名' || $row[2] != '行動電話'  ))
                        {
                            //格式錯誤，退出
                            $isOk       = 0;
                            $errCode    = 5; //錯誤格式
                            break;
                        }
                        if($f > 1 && ($row[0] && $row[1] && $row[2] && $row[3] && $row[4]))
                        {
                            $isIns = 1;
                            $rowRealTotal ++;
                            $new_id     = SHCSLib::genUserID(3,$supplyno);
                            $supplyno2  = sprintf("%08d", trim($row[0]));
                            $name       = trim($row[1]);
                            $mobile     = trim(str_replace('-','',trim($row[2])));
                            $birth      = trim($row[3]);
                            $nation     = trim($row[4]);
                            $code1      = trim($row[5]);
                            $edate1     = trim($row[6]);
                            $code2      = trim($row[7]);
                            $edate2     = trim($row[8]);
                            if(!in_array($nation,[1,2])) $nation = 1;

                            if(!$supplyno2 && !$name && !$mobile && !$birth)
                            {
                                $isIns          = 0;
                                $errCode        = 0;
                            }

                            //非該廠商之統編，請先確認匯入內容!
                            if( ($supplyno != $supplyno2  || !$supplyno2 ) && ($name && $mobile && $birth) )
                            {
                                $isIns          = 0;
                                $isNotSelfSupply= 1;
                                break;
                            }
                            //統編異常
                            if(!$new_id)
                            {
                                $isIns      = 0;
                                $errCode    = 4;
                            }
                            //如果 本國籍 ＆勞保沒有填寫
                            if($isIns && $nation == 1 && !$code2)
                            {
                                $isIns      = 0;
                                $errCode    = 1;
                            }
                            //如果 主要資料不齊全
                            if($isIns && (!$name || !$birth || !$mobile || !$code1 || !$edate1))
                            {
                                $isIns      = 0;
                                $errCode    = 2;
                            }

                            $rp_bcust_id    = CheckLib::checkMobileExist($mobile);
                            $rp_bcust_name  = ($rp_bcust_id)? User::getName($rp_bcust_id).'，'.b_supply_member::getSupplyName($rp_bcust_id) : '';
                            //行動電話重複
                            if($isIns && $mobile && $rp_bcust_id)
                            {
                                $isIns      = 0;
                                $errCode    = 3;
                            }

                            //新增一筆
                            if($isIns)
                            {
                                if(is_numeric($birth))  $birth  = SHCSLib::tranExcelDate($birth);
                                if(is_numeric($edate1)) $edate1 = SHCSLib::tranExcelDate($edate1);
                                if(is_numeric($edate2)) $edate2 = SHCSLib::tranExcelDate($edate2);
                                $tmp = [];
                                //b_cust
                                $tmp['name']        = $name;
                                $tmp['nation']      = $nation;

                                $tmp['mobile1']     = trim(str_replace('-','',$mobile));
                                $tmp['birth']       = ($birth)? date('Y-m-d',strtotime($birth)) : '';

                                //b_cust_a
                                $tmp['id']              = $new_id;
                                $tmp['bc_type']         = 3;
                                $tmp['bc_type_app']     = 0;
                                $tmp['b_menu_group_id'] = 1;
                                $tmp['b_supply_id']     = $fid;
                                $tmp['account']         = $mobile;
                                $tmp['password']        = substr($mobile,-4);
                                $tmp['isIN']            = 'N';
                                $tmp['isAutoAccount']   = 'N';
                                $tmp['head_img']        = '';
                                $tmp['sex']             = 'F';
                                $tmp['bloodRh']         = '';
                                $tmp['email1']          = '';
                                $tmp['kin_kind']        = 1;

                                //b_supply_member_ei
                                //b_supply_member_el
                                $tmp['type_id']         = sys_param::getParam('SUPPLY_RP_BCUST_IDENTITY_ID',3);
                                $tmp['aproc']           = 'O';
                                $tmp['license'][1]['edate']         = date('Y-m-d');
                                $tmp['license'][1]['license_code']  = ($nation == 1)? $code2 : 'NA';
//                                $tmp['license'][2]['edate']         = date('Y-m-d');
//                                $tmp['license'][2]['license_code']  = 'NA';
                                $tmp['license'][3]['edate']         = ($edate1)? date('Y-m-d',strtotime($edate1)) : '';
                                $tmp['license'][3]['license_code']  = $code1;

                                //et_traning_m
                                $tmp['et_course_id']    = 1;
                                $tmp['et_traning_id']   = 1;
                                $tmp['apply_date']      = ($edate2)? date('Y-m-d',strtotime($edate2)) : '';
                                //dd([$row,$supplyno,$tmp]);

                                $ret = $this->createBcust($tmp,$this->b_cust_id);
                                $id  = $ret;
                                //2-1. 更新成功
                                if($ret)
                                {
                                    $isSuc++;
                                    //動作紀錄
                                    LogLib::putLogAction($this->b_cust_id,$this->pageTitleMain,$ip,1,'b_cust',$id);
                                    LogLib::putLogAction($this->b_cust_id,$this->pageTitleMain,$ip,1,'b_cust_a',$id);
                                    LogLib::putLogAction($this->b_cust_id,$this->pageTitleMain,$ip,1,'b_supply_member',$id);
                                } else {
                                    $isErr++;
                                    $msgAry[] = Lang::get($this->langText.'.supply_66',['bid'=>$tmp['name']]);
                                }
                            } else {

                                if($errCode)
                                {
                                    $isErr++;
                                    $errStrCode = isset($errStrCodeAry[$errCode])? $errStrCodeAry[$errCode] : $errStrCodeAry[1];
                                    $msgAry[][1] = Lang::get($this->langText.'.'.$errStrCode,['bid'=>$name,'account'=>$mobile,'name'=>$rp_bcust_name]);
                                } else {
                                    $rowRealTotal--;
                                    $cnt--;
                                }
                            }
                        }
                        $f++;
                    }

                    if($isNotSelfSupply)
                    {
                        Session::flash('danger',Lang::get($this->langText.'.supply_80'));

                    } elseif ($errCode == 5) {
                        Session::flash('danger',Lang::get($this->langText.'.supply_1025'));
                    }else {
                        if(count($msgAry))
                        {
                            Session::put('download.supplymember',$msgAry);
                            $errors = Lang::get($this->langText.'.supply_62',['amt'=>$rowRealTotal,'suc'=>$isSuc,'err'=>$isErr,'msg'=>'']);
                            Session::flash('danger',$errors);
                        } else {
                            Session::flash('message',Lang::get($this->langText.'.supply_62',['amt'=>$rowRealTotal,'suc'=>$isSuc,'err'=>$isErr,'msg'=>'']));
                        }
                    }
                } else {
                    Session::flash('danger',Lang::get($this->langText.'.supply_1025'));
                }
                //dd($excelAry);
                /*
                Excel::load($request->excel, function ($reader) use ($fid,$ip,$supplyno) {
                    $this->getBcustParam();
                    //參數
                    $msgAry  = [];
                    $isSuc = $isErr = 0;
                    $menu  = $this->pageTitleMain;
                    $errStrCodeAry = [1=>'supply_77',2=>'supply_76',3=>'supply_75',4=>'supply_82'];
                    //Excel資料
                    $data = $reader->toArray();
//                dd($data);
                    if ($cnt = count($data)) {
                        $isNotSelfSupply = 0;
                        foreach ($data as $key => $val) {
                            $isIns = 1;
                            $errCode = 0;
                            if(isset($val['統編']) && isset($val['申請人姓名']) && isset($val['行動電話']) && isset($val['申請人出生日期']))
                            {
                                $tmp = [];
                                $supplyno2  = trim($val['統編']);
                                $birth      = trim($val['申請人出生日期']);
                                $edate1     = trim($val['商業保險到期日']);
                                $edate2     = trim($val['康寧危害告知訓練日期']);

                                //b_cust
                                $tmp['name']        = trim($val['申請人姓名']);
                                $tmp['nation']      = trim($val['國籍1本國籍2外國籍']);
                                if(!in_array($tmp['nation'],[1,2])) $tmp['nation'] = 1;
                                $tmp['mobile1']     = trim(str_replace('-','',$val['行動電話']));
                                $tmp['birth']       = ($birth)? date('Y-m-d',strtotime($birth)) : '';

                                //b_cust_a
                                $tmp['id']              = SHCSLib::genUserID(3,$supplyno);
                                $tmp['bc_type']         = 3;
                                $tmp['bc_type_app']     = 0;
                                $tmp['b_menu_group_id'] = 1;
                                $tmp['b_supply_id']     = $fid;
                                $tmp['account']         = $tmp['mobile1'];
                                $tmp['password']        = substr($tmp['mobile1'],-4);
                                $tmp['isIN']            = 'N';
                                $tmp['isAutoAccount']   = 'N';
                                $tmp['head_img']        = '';
                                $tmp['sex']             = 'F';
                                $tmp['bloodRh']         = '';
                                $tmp['email1']          = '';
                                $tmp['kin_kind']        = 1;

                                //b_supply_member_ei
                                //b_supply_member_el
                                $tmp['type_id']         = sys_param::getParam('SUPPLY_RP_BCUST_IDENTITY_ID',3);
                                $tmp['aproc']           = 'O';
                                $tmp['license'][1]['edate']         = date('Y-m-d');
                                $tmp['license'][1]['license_code']  = ($tmp['nation'] == 1)? trim($val['勞工保險證號本國籍需填勞保']) : 'NA';
                                $tmp['license'][2]['edate']         = date('Y-m-d');
                                $tmp['license'][2]['license_code']  = 'NA';
                                $tmp['license'][3]['edate']         = ($edate1)? date('Y-m-d',strtotime($edate1)) : '';
                                $tmp['license'][3]['license_code']  = trim($val['商業保險保單編號']);

                                //et_traning_m
                                $tmp['et_course_id']    = 1;
                                $tmp['et_traning_id']   = 1;
                                $tmp['apply_date']      = ($edate2)? date('Y-m-d',strtotime($edate2)) : '';
                                //dd([$supplyno,$tmp]);



                                //dd($tmp,$isIns,$errCode);
                                if($isIns)
                                {
                                    $ret = $this->createBcust($tmp,$this->b_cust_id);
                                    $id  = $ret;
                                    //2-1. 更新成功
                                    if($ret)
                                    {
                                        $isSuc++;
                                        //動作紀錄
                                        LogLib::putLogAction($this->b_cust_id,$menu,$ip,1,'b_cust',$id);
                                        LogLib::putLogAction($this->b_cust_id,$menu,$ip,1,'b_cust_a',$id);
                                        LogLib::putLogAction($this->b_cust_id,$menu,$ip,1,'b_supply_member',$id);
                                    } else {
                                        $isErr++;
                                        $msgAry[] = Lang::get($this->langText.'.supply_66',['bid'=>$tmp['name']]);
                                    }
                                } else {
                                    $isErr++;
                                    $errStrCode = isset($errStrCodeAry[$errCode])? $errStrCodeAry[$errCode] : $errStrCodeAry[1];
                                    $msgAry[] = Lang::get($this->langText.'.'.$errStrCode,['bid'=>$tmp['name'],'account'=>$tmp['account'],'name'=>$rp_bcust_name]);
                                }
                            } else {
                                $isErr++;
                                $msgAry[] = Lang::get($this->langText.'.supply_67');
                                break;
                            }
                        }

                        if($isNotSelfSupply)
                        {
                            Session::flash('message',Lang::get($this->langText.'.supply_80'));
                        } else {
                            $msg = implode('，',$msgAry);
                            Session::put('download',$msgAry);
                            Session::flash('message',Lang::get($this->langText.'.supply_62',['amt'=>$cnt,'suc'=>$isSuc,'err'=>$isErr,'msg'=>$msg]));
                        }
                    }
                });
                */
            }
        }


        return \Redirect::to($this->hrefMain);
    }
}
