<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkCheckKindFileTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkCheckKindTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_worker;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkPrintController extends Controller
{
    use SessTraits,WorkPermitWorkOrderTrait,WorkPermitWorkCheckKindTrait,WorkPermitWorkCheckKindFileTrait;
    use WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    /*
    |--------------------------------------------------------------------------
    | WorkPrintController
    |--------------------------------------------------------------------------
    |
    | 報表列印_工單_康寧_2020v6
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
        $this->hrefMain         = 'printpermitlist';
        $this->hrefPrint        = 'printpermit';
        $this->hrefBack         = 'workpermitprocessshow';
        $this->hrefBack2        = 'wpworkorder';
        $this->langText         = 'sys_workpermit';
        $this->pageTitleMain    = Lang::get($this->langText.'.title31');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list31');//大標題

        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

    }

    /**
     * 報表列印_列表
     *
     * @return void
     */
    public function index(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $urlParam1   = $request->has('no')? $request->no : '';
        $urlParam2   = $request->has('lid')? $request->no : '';
        $wp_id       = $urlParam1 ? SHCSLib::decode($urlParam1) : '';
        $wp_aproc    = wp_work::isExist($wp_id);
        //CHECK: 工單存在
        if(!$wp_id || !$wp_aproc) return \Redirect::back()->withErrors(Lang::get('sys_base.base_10177'));

        //資料
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $workAry  = $this->getApiWorkPermitWorkCheckKindList($wp_id);

        //畫面內容
        $hrefBack = ($urlParam2)? ($this->hrefBack.'?wid='.$urlParam1.'&lid='.$urlParam2) : ($this->hrefBack2);
        $btnBack  = $this->pageBackBtn;
        $html = FormLib::linkbtn( $hrefBack ,$btnBack,'2','BackBtn'); //新增;

        $html.= '<table class="table">';
        $html.= '<thead>
                          <tr>
                            <th width="25%">'.Lang::get($this->langText.'.permit_210').'</th>
                            <th>'.Lang::get($this->langText.'.permit_216').'</th>
                          </tr>
                        </thead>';
        foreach ($workAry as $val)
        {
            $check_kind_name = $val->name;
            $check_kind_id   = $val->check_kind_id;
            $fileNum         = isset($val->file)? count($val->file) : 1;

            $html .= '<tr>';
            $html .= '<td>'.HtmlLib::Color($check_kind_name,'red',1).'</td>';
            $html .= '<td>';
            //
            if($wp_aproc == 'F' && $check_kind_id == 1)
            {
                $url   = $this->hrefPrint.'?no='.SHCSLib::encode($wp_id.'_MAIN');
                $name  = isset($wp_file_nameAry['MAIN'])? $wp_file_nameAry['MAIN'] : '';
                $html .= FormLib::linkbtn( $url ,$name,'9','checkKindfiledown').' ';
                $url   = $this->hrefPrint.'?no='.SHCSLib::encode($wp_id.'_MEN');
                $name  = isset($wp_file_nameAry['MAIN'])? $wp_file_nameAry['MEN'] : '';
                $html .= FormLib::linkbtn( $url ,$name,'9','checkKindfiledown').' ';
                $url   = $this->hrefPrint.'?no='.SHCSLib::encode($wp_id.'_TOOLBOX');
                $name  = isset($wp_file_nameAry['MAIN'])? $wp_file_nameAry['TOOLBOX'] : '';
                $html .= FormLib::linkbtn( $url ,$name,'9','checkKindfiledown').' ';
                $url   = $this->hrefPrint.'?no='.SHCSLib::encode($wp_id.'_CHARGELIST');
                $name  = isset($wp_file_nameAry['MAIN'])? $wp_file_nameAry['CHARGELIST'] : '';
                $html .= FormLib::linkbtn( $url ,$name,'9','checkKindfiledown').' ';
                $html .= '<br/>';
            }
            if($wp_aproc == 'F' && $check_kind_id >= 3 && $check_kind_id <= 11)
            {
                $url   = $this->hrefPrint.'?no='.SHCSLib::encode($wp_id.'_KIND'.$check_kind_id);
                $name  = isset($wp_file_nameAry['MAIN'])? $wp_file_nameAry['KIND'.$check_kind_id] : '';
                $html .= FormLib::linkbtn( $url ,$name,'9','checkKindfiledown').' ';
            }
            if($fileNum)
            {
                foreach ($val->file as $kfval)
                {
                    $html .= FormLib::linkbtn( $kfval['url'] ,$kfval['name'],'6','checkKindfiledown').' ';
                }
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        if($wp_aproc != 'F')
        {
            $html .= '<hr>'.HtmlLib::Color(Lang::get($this->langText.'.permit_217'),'red',1);
        }
        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($this->pageTitleList,$html));
        $contents = $content->output();

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>''];
        return view('index',$retArray);
    }
    /**
     * 報表列印
     *
     * @return void
     */
    public function print(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $contents = $js = '';
        $urlParam    = $request->has('no')? $request->no : '';
        $urlDeCode   = $urlParam ? SHCSLib::decode($urlParam) : '';
        $urlParamAry = explode('_',$urlDeCode);
        $wp_id       = isset($urlParamAry[0])? $urlParamAry[0] : '';
        $wp_type     = isset($urlParamAry[1])? $urlParamAry[1] : '';
        $isRep       = $request->has('isRep')? $request->isRep : ''; //重新產生檔案
        $wp_aproc    = wp_work::isExist($wp_id);
        $words_path  = config('mycfg.permit_word_path');
        $wp_dir_path = storage_path('app'.$words_path.$wp_id.'/');
        //CHECK: 工單存在
        if($wp_id && $wp_aproc != 'F') return \Redirect::back()->withErrors(Lang::get('sys_base.base_10177'));
        if(!file_exists($wp_dir_path)) mkdir($wp_dir_path, 0777, true);
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        $wp_no            = wp_work::getNo($wp_id);
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $workWordRealPath = $wp_dir_path.$wp_fileName;
        $workPdfRealPath  = $wp_dir_path.$wp_fileName2;
        //如果檔案已經存在，不在重新產生
        if($isRep != 'Y' && file_exists($workWordRealPath))
        {
//            dd($workPdfRealPath,$workWordRealPath);
//            $content = file_get_contents($workPdfRealPath);
//            return response()->make($content);
            return response()->download($workWordRealPath);
        }
        //工單內容
        $dataAry = $this->getWorkPermitWorkOrder($wp_id);
//        dd($dataAry,$wp_type,$workWordRealPath);
        //-------------------------------------------//
        //  WORD
        //-------------------------------------------//
        Switch($wp_type)
        {
            //主表_工單申請表
            case 'MAIN':
                $this->word1($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //施工人員名單
            case 'MEN':
                $this->word2($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //簽核流程
            case 'CHARGELIST':
                $this->word3($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //工具箱會議
            case 'TOOLBOX':
                $this->word4($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //動火
            case 'KIND3':
                $this->kind3($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //消防
            case 'KIND4':
                $this->kind4($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //緊急
            case 'KIND5':
                $this->kind5($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //電氣
            case 'KIND6':
                $this->kind6($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //高架
            case 'KIND7':
                $this->kind7($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //特殊物料
            case 'KIND8':
                $this->kind8($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //特殊物料
            case 'KIND9':
                $this->kind9($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //侷限
            case 'KIND10':
                $this->kind10($wp_id,$wp_no,$wp_type,$dataAry);
                break;
            //管線
            case 'KIND11':
                $this->kind11($wp_id,$wp_no,$wp_type,$dataAry);
                break;
        }
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        if(file_exists($workWordRealPath))
        {
            return response()->download($workWordRealPath);
        } else {
            //產生工單列印失敗
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10178'));
        }
    }

    /**
     * 工作許可證_主表 MAIN
     */
    public function word1($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);
//        dd($dataAry,$wp_type,$templateWordPath,$workPdfRealPath);
        //pharam
        $a1     = $dataAry->permit_no;
        $a2     = $dataAry->apply_date;
        $a3     = $dataAry->apply_user_name;
        $a4     = $dataAry->apply_user_tel;
        $a5     = $dataAry->be_dept_id2_name;
        $a6a    = $dataAry->shift_work_check1;
        $a6b    = $dataAry->shift_work_check2;
        $a7     = $dataAry->shift_work_start;
        $a8     = $dataAry->shift_work_end;
        $a9     = $dataAry->work_day;

        $a10    = $dataAry->b_factory_memo;
        $a11    = $dataAry->wp_permit_workitem_memo;
        $a12a   = $dataAry->permit_kind_check1;
        $a12b   = $dataAry->permit_kind_check2;
        $a13    = $dataAry->supply;
        $a14    = $dataAry->addMemberCnt;
        $a15    = $dataAry->be_dept_id1_name;
        $a16    = $dataAry->be_dept_id4_name;
        $a17    = $dataAry->worker_user;
        $a18    = $dataAry->safer_user;

        $a20a   = $dataAry->check_kind_checkbox[3];
        $a20b   = $dataAry->check_kind_checkbox[4];
        $a20c   = $dataAry->check_kind_checkbox[5];
        $a20d   = $dataAry->check_kind_checkbox[6];
        $a20e   = $dataAry->check_kind_checkbox[7];
        $a20f   = $dataAry->check_kind_checkbox[8];
        $a20g   = $dataAry->check_kind_checkbox[9];
        $a20h   = $dataAry->check_kind_checkbox[10];
        $a20i   = $dataAry->check_kind_checkbox[11];

        $a30   = $dataAry->charge_process2['charger'].$dataAry->charge_process2['stamp'];
        $a31   = $dataAry->charge_process3['charger'].$dataAry->charge_process3['stamp'];
        $a32   = implode(',',$dataAry->charge_process5);
        $a33   = $dataAry->charge_process6['charger'].$dataAry->charge_process6['stamp'];


        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->setValue('wpA4', $a4);
        $templateProcessor->setValue('wpA5', $a5);
        $templateProcessor->setValue('wpA6a', $a6a);
        $templateProcessor->setValue('wpA6b', $a6b);
        $templateProcessor->setValue('wpA7', $a7);
        $templateProcessor->setValue('wpA8', $a8);
        $templateProcessor->setValue('wpA9', $a9);
        $templateProcessor->setValue('wpA10', $a10);
        $templateProcessor->setValue('wpA11', $a11);
        $templateProcessor->setValue('wpA12a', $a12a);
        $templateProcessor->setValue('wpA12b', $a12b);
        $templateProcessor->setValue('wpA13', $a13);
        $templateProcessor->setValue('wpA14', $a14);
        $templateProcessor->setValue('wpA15', $a15);
        $templateProcessor->setValue('wpA16', $a16);
        $templateProcessor->setValue('wpA17', $a17);
        $templateProcessor->setValue('wpA18', $a18);
        $templateProcessor->setValue('wpA20a', $a20a);
        $templateProcessor->setValue('wpA20b', $a20b);
        $templateProcessor->setValue('wpA20c', $a20c);
        $templateProcessor->setValue('wpA20d', $a20d);
        $templateProcessor->setValue('wpA20e', $a20e);
        $templateProcessor->setValue('wpA20f', $a20f);
        $templateProcessor->setValue('wpA20g', $a20g);
        $templateProcessor->setValue('wpA20h', $a20h);
        $templateProcessor->setValue('wpA20i', $a20i);
        $templateProcessor->setValue('wpA30', $a30);
        $templateProcessor->setValue('wpA31', $a31);
        $templateProcessor->setValue('wpA32', $a32);
        $templateProcessor->setValue('wpA33', $a33);
        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_人員名單 MEN
     */
    public function word2($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $page_max_rows    = 13;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);
//        dd($dataAry,$wp_type,$templateWordPath,$workPdfRealPath);

        $a1     = $dataAry->supply;
        $a2     = $dataAry->permit_no;
        $a3     = date('Y-m-d');
        //施工人員
        list($menCnt,$menAry) = wp_work_worker::getListAry($wp_id);
        $page_rows = $page_max_rows - ($menCnt%$page_max_rows);

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->cloneRow('wpA10', $menCnt+$page_rows);
        if($menCnt > 0)
        {
            foreach ($menAry as $no => $val)
            {
                $row_id = $no+1;
                $templateProcessor->setValue('wpA10#'.$row_id, $row_id);
                $templateProcessor->setValue('wpA11#'.$row_id, $val['name']);
                $templateProcessor->setValue('wpA13#'.$row_id, $val['birth']);
                $templateProcessor->setValue('wpA14#'.$row_id, $val['id']);
            }
        }
        if($page_rows)
        {
            $i = 0;
            do{
                $i++;
                $row_id++;
                $templateProcessor->setValue('wpA10#'.$row_id, $row_id);
                $templateProcessor->setValue('wpA11#'.$row_id, '');
                $templateProcessor->setValue('wpA13#'.$row_id, '');
                $templateProcessor->setValue('wpA14#'.$row_id, '');
            }while($i <= $page_rows);
        }


        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_簽核流程 CHARGELIST
     */
    public function word3($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);
//        dd($dataAry,$wp_type,$templateWordPath,$workPdfRealPath);

        $a1     = $dataAry->permit_no;
        //施工人員
        $chargeAry  = $this->getApiWorkPermitChargeList($wp_id);
        $totalCnt   = count($chargeAry);
        $rejectAry  = ['R'=>Lang::get('sys_btn.btn_2'),'N'=>Lang::get('sys_btn.btn_1'),'Y'=>Lang::get('sys_btn.btn_73')];
        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->cloneRow('wpA10', $totalCnt);
        if($totalCnt > 0)
        {
            foreach ($chargeAry as $no => $val)
            {
                $row_id = $no+1;
                $isReject = isset($rejectAry[$val['isReject']])?$rejectAry[$val['isReject']]:'';
                $templateProcessor->setValue('wpA10#'.$row_id, $isReject);
                $templateProcessor->setValue('wpA11#'.$row_id, $val['charge_dept']);
                $templateProcessor->setValue('wpA12#'.$row_id, $val['charge_user']);
                $templateProcessor->setValue('wpA13#'.$row_id, $val['charge_stamp']);
                $templateProcessor->setValue('wpA14#'.$row_id, $val['charge_memo']);
            }
        }


        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_工具箱會議 TOOLBOX
     */
    public function word4($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);
//        dd($dataAry,$wp_type,$templateWordPath,$workPdfRealPath);

        $a1     = $dataAry->permit_no;

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);

        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_動火 KIND3
     */
    public function kind3($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);


        $checkTopic = $this->getApiWorkPermitCheckTopicRecord($wp_id,3)[0]['option'];
        //dd($dataAry,$checkTopic,$wp_type,$templateWordPath,$workPdfRealPath);
        $a1     = $dataAry->permit_no;
        $a2     = $dataAry->apply_date;
        $a3     = $dataAry->apply_user_name;
        $a4     = $dataAry->apply_user_tel;
        $a5     = $dataAry->be_dept_id2_name;
        $a7     = $dataAry->shift_work_start;
        $a8     = $dataAry->shift_work_end;

        $a10    = $dataAry->b_factory_memo;
        $a11    = $a20 = $a21 = $a22 = '';
        if(count($checkTopic))
        {
            foreach ($checkTopic as $val)
            {
                $topic_a_id = isset($val['topic_a_id'])? $val['topic_a_id'] : 0;
                $ans_value  = isset($val['ans_value'])? $val['ans_value'] : '';
                if($topic_a_id == 10)
                {
                    $a11 = $ans_value;
                }
                if($topic_a_id == 11)
                {
                    $a20 = $ans_value;
                }
                if($topic_a_id == 12)
                {
                    $a21 = $ans_value;
                }
                if($topic_a_id == 87)
                {
                    $a22 = $ans_value;
                }
            }
        }

        $a32   = implode(',',$dataAry->charge_process5);
        $a33   = $dataAry->charge_process6['charger'].$dataAry->charge_process6['stamp'];

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->setValue('wpA4', $a4);
        $templateProcessor->setValue('wpA5', $a5);
        $templateProcessor->setValue('wpA7', $a7);
        $templateProcessor->setValue('wpA8', $a8);
        $templateProcessor->setValue('wpA10', $a10);
        $templateProcessor->setValue('wpA11', $a11);
        $templateProcessor->setValue('wpA20', $a20);
        $templateProcessor->setValue('wpA21', $a21);
        $templateProcessor->setValue('wpA22', $a22);
        $templateProcessor->setValue('wpA32', $a32);
        $templateProcessor->setValue('wpA33', $a33);

        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_消防 KIND4
     */
    public function kind4($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);


        $checkTopic = $this->getApiWorkPermitCheckTopicRecord($wp_id,4)[0]['option'];
        //dd($dataAry,$checkTopic,$wp_type,$templateWordPath,$workPdfRealPath);
        $a1     = $dataAry->permit_no;
        $a2     = $dataAry->apply_date;
        $a3     = $dataAry->apply_user_name;
        $a4     = $dataAry->apply_user_tel;
        $a5     = $dataAry->be_dept_id2_name;
        $a7     = $dataAry->shift_work_start;
        $a8     = $dataAry->shift_work_end;

        $a10    = $dataAry->b_factory_memo;
        $a20    = $a21 = $a22 = $a23 = $a23 = $a24 = $a25 = $a26 = $a27 = $a28 = $a29 = '';
        if(count($checkTopic))
        {
            foreach ($checkTopic as $val)
            {
                $topic_a_id = isset($val['topic_a_id'])? $val['topic_a_id'] : 0;
                $ans_value  = isset($val['ans_value'])? $val['ans_value'] : '';
                if($topic_a_id == 15)
                {
                    $a20 = $ans_value;
                }
                if($topic_a_id == 14)
                {
                    $a21 = $ans_value;
                }
                if($topic_a_id == 13)
                {
                    $a22 = $ans_value;
                }
                if($topic_a_id == 80)
                {
                    $a23 = $ans_value;
                }
                if($topic_a_id == 16)
                {
                    $a24 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 17)
                {
                    $a25 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 18)
                {
                    $a26 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 19)
                {
                    $a27 = $ans_value;
                }
                if($topic_a_id == 20)
                {
                    $a28 = $ans_value;
                }
                if($topic_a_id == 81)
                {
                    $a29 = $ans_value;
                }
            }
        }

        $a30   = $dataAry->charge_process2['charger'].$dataAry->charge_process2['stamp'];
        $a31   = $dataAry->charge_process3['charger'].$dataAry->charge_process3['stamp'];
        $a32   = implode(',',$dataAry->charge_process5);
        $a33   = $dataAry->charge_process6['charger'].$dataAry->charge_process6['stamp'];
        $a34   = $dataAry->charge_process4['charger'].$dataAry->charge_process4['stamp'];

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->setValue('wpA4', $a4);
        $templateProcessor->setValue('wpA5', $a5);
        $templateProcessor->setValue('wpA7', $a7);
        $templateProcessor->setValue('wpA8', $a8);
        $templateProcessor->setValue('wpA10', $a10);
        $templateProcessor->setValue('wpA20', $a20);
        $templateProcessor->setValue('wpA21', $a21);
        $templateProcessor->setValue('wpA22', $a22);
        $templateProcessor->setValue('wpA23', $a23);
        $templateProcessor->setValue('wpA24', $a24);
        $templateProcessor->setValue('wpA25', $a25);
        $templateProcessor->setValue('wpA26', $a26);
        $templateProcessor->setValue('wpA27', $a27);
        $templateProcessor->setValue('wpA28', $a28);
        $templateProcessor->setValue('wpA29', $a29);
        $templateProcessor->setValue('wpA30', $a30);
        $templateProcessor->setValue('wpA31', $a31);
        $templateProcessor->setValue('wpA32', $a32);
        $templateProcessor->setValue('wpA33', $a33);
        $templateProcessor->setValue('wpA34', $a34);

        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_緊急 KIND5
     */
    public function kind5($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);


        $checkTopic = $this->getApiWorkPermitCheckTopicRecord($wp_id,3)[0]['option'];
        //dd($dataAry,$checkTopic,$wp_type,$templateWordPath,$workPdfRealPath);
        $a1     = $dataAry->permit_no;
        $a2     = $dataAry->apply_date;
        $a3     = $dataAry->apply_user_name;
        $a4     = $dataAry->apply_user_tel;
        $a5     = $dataAry->be_dept_id2_name;
        $a7     = $dataAry->shift_work_start;
        $a8     = $dataAry->shift_work_end;

        $a10    = $dataAry->b_factory_memo;
        $a11    = $a20 = $a21 = '';
        if(count($checkTopic))
        {
            foreach ($checkTopic as $val)
            {
                $topic_a_id = isset($val['topic_a_id'])? $val['topic_a_id'] : 0;
                $ans_value  = isset($val['ans_value'])? $val['ans_value'] : '';
                if($topic_a_id == 40)
                {
                    $a20 = $ans_value;
                }
            }
        }

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->setValue('wpA4', $a4);
        $templateProcessor->setValue('wpA5', $a5);
        $templateProcessor->setValue('wpA7', $a7);
        $templateProcessor->setValue('wpA8', $a8);
        $templateProcessor->setValue('wpA10', $a10);
        $templateProcessor->setValue('wpA11', $a11);
        $templateProcessor->setValue('wpA20', $a20);

        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_電氣 KIND6
     */
    public function kind6($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);


        $checkTopic = $this->getApiWorkPermitCheckTopicRecord($wp_id,6)[0]['option'];
        //dd($dataAry,$checkTopic,$wp_type,$templateWordPath,$workPdfRealPath);
        $a1     = $dataAry->permit_no;
        $a2     = $dataAry->apply_date;
        $a3     = $dataAry->apply_user_name;
        $a4     = $dataAry->apply_user_tel;
        $a5     = $dataAry->be_dept_id2_name;
        $a7     = $dataAry->shift_work_start;
        $a8     = $dataAry->shift_work_end;

        $a10    = $dataAry->b_factory_memo;
        $a20    = $a21 = $a22 = $a23 = $a23 = $a24 = $a25 = $a26 = $a27 = $a28 = $a29 = '';
        if(count($checkTopic))
        {
            foreach ($checkTopic as $val)
            {
                $topic_a_id = isset($val['topic_a_id'])? $val['topic_a_id'] : 0;
                $ans_value  = isset($val['ans_value'])? $val['ans_value'] : '';
                if($topic_a_id == 57)
                {
                    $a20 = $ans_value;
                }
                if($topic_a_id == 58)
                {
                    $a21 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 59)
                {
                    $a22 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 60)
                {
                    $a23 = $ans_value ? $ans_value : '☐';
                }
            }
        }

        $a30   = $dataAry->charge_process2['charger'].$dataAry->charge_process2['stamp'];
        $a31   = $dataAry->charge_process3['charger'].$dataAry->charge_process3['stamp'];
        $a32   = implode(',',$dataAry->charge_process5);
        $a33   = $dataAry->charge_process6['charger'].$dataAry->charge_process6['stamp'];
        $a34   = $dataAry->charge_process4['charger'].$dataAry->charge_process4['stamp'];

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->setValue('wpA4', $a4);
        $templateProcessor->setValue('wpA5', $a5);
        $templateProcessor->setValue('wpA7', $a7);
        $templateProcessor->setValue('wpA8', $a8);
        $templateProcessor->setValue('wpA10', $a10);
        $templateProcessor->setValue('wpA20', $a20);
        $templateProcessor->setValue('wpA21', $a21);
        $templateProcessor->setValue('wpA22', $a22);
        $templateProcessor->setValue('wpA23', $a23);
        $templateProcessor->setValue('wpA24', $a24);
        $templateProcessor->setValue('wpA25', $a25);
        $templateProcessor->setValue('wpA26', $a26);
        $templateProcessor->setValue('wpA27', $a27);
        $templateProcessor->setValue('wpA28', $a28);
        $templateProcessor->setValue('wpA29', $a29);
        $templateProcessor->setValue('wpA30', $a30);
        $templateProcessor->setValue('wpA31', $a31);
        $templateProcessor->setValue('wpA32', $a32);
        $templateProcessor->setValue('wpA33', $a33);
        $templateProcessor->setValue('wpA34', $a34);

        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_電氣 KIND7
     */
    public function kind7($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);


        $checkTopic = $this->getApiWorkPermitCheckTopicRecord($wp_id,7)[0]['option'];
        //dd($dataAry,$checkTopic,$wp_type,$templateWordPath,$workPdfRealPath);
        $a1     = $dataAry->permit_no;
        $a2     = $dataAry->apply_date;
        $a3     = $dataAry->apply_user_name;
        $a4     = $dataAry->apply_user_tel;
        $a5     = $dataAry->be_dept_id2_name;
        $a7     = $dataAry->shift_work_start;
        $a8     = $dataAry->shift_work_end;

        $a10    = $dataAry->b_factory_memo;
        $a20    = $a21 = $a22 = $a23 = $a23 = $a24 = $a25 = $a26 = $a27 = $a28 = $a29 = '';
        if(count($checkTopic))
        {
            foreach ($checkTopic as $val)
            {
                $topic_a_id = isset($val['topic_a_id'])? $val['topic_a_id'] : 0;
                $ans_value  = isset($val['ans_value'])? $val['ans_value'] : '';
                if($topic_a_id == 61)
                {
                    $a20 = $ans_value;
                }
                if($topic_a_id == 62)
                {
                    $a21 = $ans_value;
                }
                if($topic_a_id == 63)
                {
                    $a22 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 64)
                {
                    $a23 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 65)
                {
                    $a24 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 66)
                {
                    $a25 = $ans_value ? $ans_value : '☐';
                }
            }
        }

        $a30   = $dataAry->charge_process2['charger'].$dataAry->charge_process2['stamp'];
        $a31   = $dataAry->charge_process3['charger'].$dataAry->charge_process3['stamp'];
        $a32   = implode(',',$dataAry->charge_process5);
        $a33   = $dataAry->charge_process6['charger'].$dataAry->charge_process6['stamp'];
        $a34   = $dataAry->charge_process4['charger'].$dataAry->charge_process4['stamp'];

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->setValue('wpA4', $a4);
        $templateProcessor->setValue('wpA5', $a5);
        $templateProcessor->setValue('wpA7', $a7);
        $templateProcessor->setValue('wpA8', $a8);
        $templateProcessor->setValue('wpA10', $a10);
        $templateProcessor->setValue('wpA20', $a20);
        $templateProcessor->setValue('wpA21', $a21);
        $templateProcessor->setValue('wpA22', $a22);
        $templateProcessor->setValue('wpA23', $a23);
        $templateProcessor->setValue('wpA24', $a24);
        $templateProcessor->setValue('wpA25', $a25);
        $templateProcessor->setValue('wpA26', $a26);
        $templateProcessor->setValue('wpA27', $a27);
        $templateProcessor->setValue('wpA28', $a28);
        $templateProcessor->setValue('wpA29', $a29);
        $templateProcessor->setValue('wpA30', $a30);
        $templateProcessor->setValue('wpA31', $a31);
        $templateProcessor->setValue('wpA32', $a32);
        $templateProcessor->setValue('wpA33', $a33);
        $templateProcessor->setValue('wpA34', $a34);

        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_特殊物料 KIND8
     */
    public function kind8($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);


        $checkTopic = $this->getApiWorkPermitCheckTopicRecord($wp_id,8)[0]['option'];
        //dd($dataAry,$checkTopic,$wp_type,$templateWordPath,$workPdfRealPath);
        $a1     = $dataAry->permit_no;
        $a2     = $dataAry->apply_date;
        $a3     = $dataAry->apply_user_name;
        $a4     = $dataAry->apply_user_tel;
        $a5     = $dataAry->be_dept_id2_name;
        $a7     = $dataAry->shift_work_start;
        $a8     = $dataAry->shift_work_end;

        $a10    = $dataAry->b_factory_memo;
        $a20    = $a21 = $a22 = $a23 = $a23 = $a24 = $a25 = $a26 = $a27 = $a28 = $a29 = '';
        if(count($checkTopic))
        {
            foreach ($checkTopic as $val)
            {
                $topic_a_id = isset($val['topic_a_id'])? $val['topic_a_id'] : 0;
                $ans_value  = isset($val['ans_value'])? $val['ans_value'] : '';
                if($topic_a_id == 67)
                {
                    $a20 = $ans_value;
                }
                if($topic_a_id == 83)
                {
                    $a21 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 84)
                {
                    $a22 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 85)
                {
                    $a23 = $ans_value ? $ans_value : '☐';
                }
                if($topic_a_id == 86)
                {
                    $a24 = $ans_value ? $ans_value : '☐';
                }
            }
        }

        $a30   = $dataAry->charge_process2['charger'].$dataAry->charge_process2['stamp'];
        $a31   = $dataAry->charge_process3['charger'].$dataAry->charge_process3['stamp'];
        $a32   = implode(',',$dataAry->charge_process5);
        $a33   = $dataAry->charge_process6['charger'].$dataAry->charge_process6['stamp'];
        $a34   = $dataAry->charge_process4['charger'].$dataAry->charge_process4['stamp'];

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->setValue('wpA4', $a4);
        $templateProcessor->setValue('wpA5', $a5);
        $templateProcessor->setValue('wpA7', $a7);
        $templateProcessor->setValue('wpA8', $a8);
        $templateProcessor->setValue('wpA10', $a10);
        $templateProcessor->setValue('wpA20', $a20);
        $templateProcessor->setValue('wpA21', $a21);
        $templateProcessor->setValue('wpA22', $a22);
        $templateProcessor->setValue('wpA23', $a23);
        $templateProcessor->setValue('wpA24', $a24);
        $templateProcessor->setValue('wpA25', $a25);
        $templateProcessor->setValue('wpA26', $a26);
        $templateProcessor->setValue('wpA27', $a27);
        $templateProcessor->setValue('wpA28', $a28);
        $templateProcessor->setValue('wpA29', $a29);
        $templateProcessor->setValue('wpA30', $a30);
        $templateProcessor->setValue('wpA31', $a31);
        $templateProcessor->setValue('wpA32', $a32);
        $templateProcessor->setValue('wpA33', $a33);
        $templateProcessor->setValue('wpA34', $a34);

        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_物料暫存 KIND9
     */
    public function kind9($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);


        $checkTopic = $this->getApiWorkPermitCheckTopicRecord($wp_id,9)[0]['option'];
//        dd($dataAry,$checkTopic,$wp_type,$templateWordPath,$workPdfRealPath);
        $a1     = $dataAry->permit_no;
        $a2     = $dataAry->apply_date;
        $a3     = $dataAry->apply_user_name;
        $a4     = $dataAry->apply_user_tel;
        $a5     = $dataAry->be_dept_id2_name;
        $a7     = $dataAry->sdate;
        $a8     = $dataAry->edate;

        $a10    = $dataAry->b_factory_memo;
        $a20    = $a21 = $a22 = $a23 = $a23 = $a24 = $a25 = $a26 = $a27 = $a28 = $a29 = '';
        if(count($checkTopic))
        {
            foreach ($checkTopic as $val)
            {
                $topic_a_id = isset($val['topic_a_id'])? $val['topic_a_id'] : 0;
                $ans_value  = isset($val['ans_value'])? $val['ans_value'] : '';
                if($topic_a_id == 72)
                {
                    $a20 = $ans_value;
                }
                if($topic_a_id == 68)
                {
                    $a21 = $ans_value;
                }
                if($topic_a_id == 70)
                {
                    $a22 = $ans_value;
                }
                if($topic_a_id == 71)
                {
                    $a23 = $ans_value;
                }
            }
        }

        $a30   = $dataAry->charge_process2['charger'].$dataAry->charge_process2['stamp'];
        $a31   = $dataAry->charge_process3['charger'].$dataAry->charge_process3['stamp'];
        $a32   = implode(',',$dataAry->charge_process5);
        $a33   = $dataAry->charge_process6['charger'].$dataAry->charge_process6['stamp'];
        $a34   = $dataAry->charge_process4['charger'].$dataAry->charge_process4['stamp'];

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->setValue('wpA4', $a4);
        $templateProcessor->setValue('wpA5', $a5);
        $templateProcessor->setValue('wpA7', $a7);
        $templateProcessor->setValue('wpA8', $a8);
        $templateProcessor->setValue('wpA10', $a10);
        $templateProcessor->setValue('wpA20', $a20);
        $templateProcessor->setValue('wpA21', $a21);
        $templateProcessor->setValue('wpA22', $a22);
        $templateProcessor->setValue('wpA23', $a23);
        $templateProcessor->setValue('wpA24', $a24);
        $templateProcessor->setValue('wpA25', $a25);
        $templateProcessor->setValue('wpA26', $a26);
        $templateProcessor->setValue('wpA27', $a27);
        $templateProcessor->setValue('wpA28', $a28);
        $templateProcessor->setValue('wpA29', $a29);
        $templateProcessor->setValue('wpA30', $a30);
        $templateProcessor->setValue('wpA31', $a31);
        $templateProcessor->setValue('wpA32', $a32);
        $templateProcessor->setValue('wpA33', $a33);
        $templateProcessor->setValue('wpA34', $a34);

        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_局限 KIND10
     */
    public function kind10($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);


        $checkTopic = $this->getApiWorkPermitCheckTopicRecord($wp_id,10)[0]['option'];
        //dd($dataAry,$checkTopic,$wp_type,$templateWordPath,$workPdfRealPath);
        $a1     = $dataAry->permit_no;
        $a2     = $dataAry->apply_date;
        $a3     = $dataAry->apply_user_name;
        $a4     = $dataAry->apply_user_tel;
        $a5     = $dataAry->be_dept_id2_name;
        $a7     = $dataAry->shift_work_start;
        $a8     = $dataAry->shift_work_end;

        $a10    = $dataAry->b_factory_memo;
        $a20    = $a21 = $a22 = $a23 = $a23 = $a24 = $a25 = $a26 = $a27 = $a28 = $a29 = '';
        if(count($checkTopic))
        {
            foreach ($checkTopic as $val)
            {
                $topic_a_id = isset($val['topic_a_id'])? $val['topic_a_id'] : 0;
                $ans_value  = isset($val['ans_value'])? $val['ans_value'] : '';
                if($topic_a_id == 73)
                {
                    $a20 = $ans_value;
                }
                if($topic_a_id == 74)
                {
                    $a21 = $ans_value;
                }
                if($topic_a_id == 75)
                {
                    $a22 = $ans_value;
                }
                if($topic_a_id == 76)
                {
                    $a23 = $ans_value;
                }
            }
        }

        $a30   = $dataAry->charge_process2['charger'].$dataAry->charge_process2['stamp'];
        $a31   = $dataAry->charge_process3['charger'].$dataAry->charge_process3['stamp'];
        $a32   = implode(',',$dataAry->charge_process5);
        $a33   = $dataAry->charge_process6['charger'].$dataAry->charge_process6['stamp'];
        $a34   = $dataAry->charge_process4['charger'].$dataAry->charge_process4['stamp'];

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->setValue('wpA4', $a4);
        $templateProcessor->setValue('wpA5', $a5);
        $templateProcessor->setValue('wpA7', $a7);
        $templateProcessor->setValue('wpA8', $a8);
        $templateProcessor->setValue('wpA10', $a10);
        $templateProcessor->setValue('wpA20', $a20);
        $templateProcessor->setValue('wpA21', $a21);
        $templateProcessor->setValue('wpA22', $a22);
        $templateProcessor->setValue('wpA23', $a23);
        $templateProcessor->setValue('wpA24', $a24);
        $templateProcessor->setValue('wpA25', $a25);
        $templateProcessor->setValue('wpA26', $a26);
        $templateProcessor->setValue('wpA27', $a27);
        $templateProcessor->setValue('wpA28', $a28);
        $templateProcessor->setValue('wpA29', $a29);
        $templateProcessor->setValue('wpA30', $a30);
        $templateProcessor->setValue('wpA31', $a31);
        $templateProcessor->setValue('wpA32', $a32);
        $templateProcessor->setValue('wpA33', $a33);
        $templateProcessor->setValue('wpA34', $a34);

        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }

    /**
     * 工作許可證_管線 KIND11
     */
    public function kind11($wp_id,$wp_no,$wp_type,$dataAry)
    {
        if(!$wp_id) return false;
        $wp_file_nameAry  = SHCSLib::getCode('WP_FILE_NAME',0);
        $fileName         = isset($wp_file_nameAry[$wp_type])? $wp_file_nameAry[$wp_type] : '';
        $wp_fileName      = $wp_no.'_'.$fileName.'.docx';
        $wp_fileName2     = $wp_no.'_'.$fileName.'.html';
        $templat_fileName = 'WP_'.$wp_type.'.docx';
        $templateWordPath = storage_path('app/WORD/'.$templat_fileName);
        $workWordRealPath = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName);
        $workPdfRealPath  = storage_path('app'.config('mycfg.permit_word_path').$wp_id.'/'.$wp_fileName2);


        $checkTopic = $this->getApiWorkPermitCheckTopicRecord($wp_id,11)[0]['option'];
        //dd($dataAry,$checkTopic,$wp_type,$templateWordPath,$workPdfRealPath);
        $a1     = $dataAry->permit_no;
        $a2     = $dataAry->apply_date;
        $a3     = $dataAry->apply_user_name;
        $a4     = $dataAry->apply_user_tel;
        $a5     = $dataAry->be_dept_id2_name;
        $a7     = $dataAry->shift_work_start;
        $a8     = $dataAry->shift_work_end;

        $a10    = $dataAry->b_factory_memo;
        $a20    = $a21 = $a22 = $a23 = $a23 = $a24 = $a25 = $a26 = $a27 = $a28 = $a29 = '';
        if(count($checkTopic))
        {
            foreach ($checkTopic as $val)
            {
                $topic_a_id = isset($val['topic_a_id'])? $val['topic_a_id'] : 0;
                $ans_value  = isset($val['ans_value'])? $val['ans_value'] : '';
                if($topic_a_id == 77)
                {
                    $a20 = $ans_value;
                }
                if($topic_a_id == 78)
                {
                    $a21 = $ans_value;
                }
                if($topic_a_id == 79)
                {
                    $a22 = $ans_value;
                }
            }
        }

        $a30   = $dataAry->charge_process2['charger'].$dataAry->charge_process2['stamp'];
        $a31   = $dataAry->charge_process3['charger'].$dataAry->charge_process3['stamp'];
        $a32   = implode(',',$dataAry->charge_process5);
        $a33   = $dataAry->charge_process6['charger'].$dataAry->charge_process6['stamp'];
        $a34   = $dataAry->charge_process4['charger'].$dataAry->charge_process4['stamp'];

        //列印內容
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateWordPath);
        $templateProcessor->setValue('wpA1', $a1);
        $templateProcessor->setValue('wpA2', $a2);
        $templateProcessor->setValue('wpA3', $a3);
        $templateProcessor->setValue('wpA4', $a4);
        $templateProcessor->setValue('wpA5', $a5);
        $templateProcessor->setValue('wpA7', $a7);
        $templateProcessor->setValue('wpA8', $a8);
        $templateProcessor->setValue('wpA10', $a10);
        $templateProcessor->setValue('wpA20', $a20);
        $templateProcessor->setValue('wpA21', $a21);
        $templateProcessor->setValue('wpA22', $a22);
        $templateProcessor->setValue('wpA23', $a23);
        $templateProcessor->setValue('wpA24', $a24);
        $templateProcessor->setValue('wpA25', $a25);
        $templateProcessor->setValue('wpA26', $a26);
        $templateProcessor->setValue('wpA27', $a27);
        $templateProcessor->setValue('wpA28', $a28);
        $templateProcessor->setValue('wpA29', $a29);
        $templateProcessor->setValue('wpA30', $a30);
        $templateProcessor->setValue('wpA31', $a31);
        $templateProcessor->setValue('wpA32', $a32);
        $templateProcessor->setValue('wpA33', $a33);
        $templateProcessor->setValue('wpA34', $a34);

        $templateProcessor->saveAs($workWordRealPath);
        //WORD
//        $domPdfPath = base_path( 'vendor/dompdf/dompdf');
//        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        //加載docx文檔
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($workWordRealPath);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "HTML");
        $xmlWriter->save($workPdfRealPath);

        return false;
    }
}
