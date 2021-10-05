<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Lib\SHCSLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_check;
use App\Model\WorkPermit\wp_work_check_topic;
use App\Model\WorkPermit\wp_work_line;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;
use App\Model\WorkPermit\wp_work_workitem;
use App\Model\WorkPermit\wp_work_rp_extension;
use Lang;
use Illuminate\Http\Request;
use Config;
use Html;
use DB;
use Storage;
use DNS2D;
use PDF;

class PermitPrintController extends Controller
{
    use BcustTrait,SessTraits,WorkPermitTopicOptionTrait,WorkPermitWorkerTrait;
    /**
     * 建構子
     */
    public function __construct()
    {
        $equalImg = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAA7klEQVR42mNkgABeIK4D4mggloCKMQLxfzQ2shguNS+AeCkQNwHxZ0ao4YeBWA+HBlItgLEvArEtiNENxCUMtAE9IAueEQgWcn0ADi4Y4z+NLMDQQHWAywdvgXgKEP8g0hwOIM4BYmFcPkC3YDoQZ5Po2KlAnElsEIFcn0uiBZOhviAqiNYAcRiJFqwC4hBigwgEngLxHyINZwFiGQY8qWj4JVOqBxE6WM1AXiSHEusDUJomJ5lmo5kzcBntDQN5RYUIsUGEi012MqWpBaAKR5LE8CYWPIdVmcU08kEvXSp9BgbUZgulwfWcAanZAgCnBI76sx2a+AAAAABJRU5ErkJggg==" width="10pt" width="10pt">';

        $this->checkAry     = ['Y'=>'■','N'=>'□',''=>'□','='=>$equalImg];
        $this->imgResize    = 500;
        $this->work_id      = 0;
        $this->permit_danger= 'C';
        $this->sdate        = '';
        $this->workData     = (object)[];
        $this->showAry      = $this->chk_photo_topic2 = [];
    }

    /**
     * 顯示測試內容
     * @param Request $request
     */
    public function index(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();

        //工作許可證(ID & 資料)
        $this->work_id      = SHCSLib::decode($request->id);
        if(!is_numeric($this->work_id) && $this->work_id > 0) exit;
        $this->showAry['work_id']   = $this->work_id;
        $this->workData             = wp_work::getData($this->work_id);
        $this->permit_danger        = $this->workData->wp_permit_danger;

        //1.工單_申請內容
        $this->print_main1();
        //2.施工安全檢點
        $this->print_main2();
        //3.環境安全檢點(轄區)
        $this->print_main3();
        //3.環境安全檢點(轄區)
        $this->print_main4();
        //3.環境安全檢點(轄區)
        $this->print_main5();
        //3.環境安全檢點(轄區)
        $this->print_main6();

        if($request->has('testprint'))
        {
            dd($this->work_id,$this->showAry);
        }

        if($request->has('pdf'))
        {
//            $pdf = PDF::loadView('permit.permit_main2019v2', $showAry);
//            return $pdf->download($permit_no.'.pdf');
        }
        return view('permit.permit_main2021v1',$this->showAry);
    }

    /**
     * 工單_申請內容
     */
    public function print_main1()
    {
        //工單編號
        $permit_no          = $this->workData->permit_no;
        //最後更新
        $last_updated_at    = wp_work_list::getLastAt($this->work_id);
        $last_updated_format= date('Y年m月d日 H點i分',$last_updated_at);
        //申請時間
        $apply_stamp        = $this->workData->apply_stamp;
        $apply_format       = date('Y年m月d日 H點i分',strtotime($apply_stamp));
        //預計工作時間
        $this->sdate        = $this->workData->sdate;
        $work_date_Y        = substr($this->sdate,0,4);
        $work_date_M        = substr($this->sdate,5,2);
        $work_date_D        = substr($this->sdate,8,2);
        //工作時段
        $shift_id           = $this->workData->wp_permit_shift_id;
        if($shift_id == 2)
        {
            $work_date_T1       = '18:00';
            $work_date_T2       = '24:00';
            $work_date_T3       = '01:00';
            $work_date_T4       = '08:00';
        } else {
            $work_date_T1       = '08:00';
            $work_date_T2       = '12:00';
            $work_date_T3       = '13:00';
            $work_date_T4       = '17:00';
        }

        //工程作業分級
        $permit_danger      = $this->workData->wp_permit_danger;
        $permit_dangerA     = ($permit_danger == 'A')? $this->checkAry['Y'] : $this->checkAry['N'];
        $permit_dangerB     = ($permit_danger == 'B')? $this->checkAry['Y'] : $this->checkAry['N'];
        $permit_dangerC     = ($permit_danger == 'C')? $this->checkAry['Y'] : $this->checkAry['N'];

        //轄區/監造/監工/會簽/上層部門/廠工
        $be_dept_id1        = be_dept::getName($this->workData->be_dept_id1);
        $be_dept_id2        = be_dept::getName($this->workData->be_dept_id2);
        $be_dept_id3        = be_dept::getName($this->workData->be_dept_id3);
        $be_dept_id4        = be_dept::getName($this->workData->be_dept_id4);
        $be_dept_id5        = '';
        $be_dept_id6        = ''; //廠工部門
        //承攬商
        $b_supply           = b_supply::getName($this->workData->b_supply_id);
        //施工人員數目
        $supply_worker_amt  = wp_work_worker::getAmt($this->work_id,'Y');
        //車牌
        $car_no             = ''; // rept_doorinout_car_t::getTodayCar($this->workData->b_factory_id,$this->workData->b_supply_id,2);
        //
        $work_place         = b_factory_b::getName($this->workData->b_factory_b_id).'('.$this->workData->b_factory_memo.')';
        //
        $project_no         = e_project::getNo($this->workData->e_project_id);

        $work_memo          = $this->workData->wp_permit_workitem_memo;

        $isOvertime         = isset($this->checkAry[$this->workData->isOvertime])? $this->checkAry[$this->workData->isOvertime] : '';
        //工作項目
        $workitemAry        = wp_work_workitem::getSelect($this->work_id);
        //非動火
        $workitem_1         = isset($workitemAry[2])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_2         = isset($workitemAry[3])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_3         = isset($workitemAry[4])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_4         = isset($workitemAry[5])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_5         = isset($workitemAry[6])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_6         = isset($workitemAry[7])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_7         = isset($workitemAry[8])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_8         = isset($workitemAry[9])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_11        = isset($workitemAry[20])? $workitemAry[20] : '';
        $workitem_12        = isset($workitemAry[21])? $workitemAry[21] : '';
        $workitem_9         = (isset($workitemAry[1]) || $workitem_11 || $workitem_12)? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_10        = $workitem_11? $workitem_11 : '';
        $workitem_10       .= $workitem_12? (strlen($workitem_10)? '，' : '').$workitem_12 : '';
        $workitem_10       .= isset($workitemAry[1])? (strlen($workitem_10)? '，' : '').$workitemAry[1] : '';
        //動火
        $workitem_20        = isset($workitemAry[11])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_21        = isset($workitemAry[12])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_22        = isset($workitemAry[13])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_23        = isset($workitemAry[14])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_24        = isset($workitemAry[15])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_25        = isset($workitemAry[16])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_26        = isset($workitemAry[10])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_27        = isset($workitemAry[10])? $workitemAry[10] : '';
        //局限空間
        $workitem_30        = isset($workitemAry[18])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_31        = isset($workitemAry[19])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_32        = isset($workitemAry[17])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workitem_33        = isset($workitemAry[17])? $workitemAry[17] : '';
        //附加檢點表
        $workCheckAry       = wp_work_check::getSelect($this->work_id,0);
        $workCheckStr       = implode('，',$workCheckAry);
        //管線內容物
        $worklineAry        = wp_work_line::getSelect($this->work_id);
        $workline_1         = isset($worklineAry[1])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workline_11        = isset($worklineAry[1])? $worklineAry[1] : '';
        $workline_2         = isset($worklineAry[2])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workline_3         = isset($worklineAry[3])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workline_4         = isset($worklineAry[4])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workline_5         = isset($worklineAry[5])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workline_6         = isset($worklineAry[6])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workline_7         = isset($worklineAry[7])? $this->checkAry['Y'] : $this->checkAry['N'];
        $workline_8         = isset($worklineAry[8])? $this->checkAry['Y'] : $this->checkAry['N'];

        $showAry = $this->showAry;
        $showAry['permit_no']       = SHCSLib::genReportAnsHtml($permit_no,16);
        $showAry['qrcode']          = 'data:image/png;base64,'.DNS2D::getBarcodePNG($permit_no, "QRCODE");

        $showAry['last_updated_at'] = SHCSLib::genReportAnsHtml($last_updated_format,16);

        $showAry['apply_date']      = SHCSLib::genReportAnsHtml($apply_format,46);

        $showAry['work_date_Y']     = SHCSLib::genReportAnsHtml($work_date_Y,4);
        $showAry['work_date_M']     = SHCSLib::genReportAnsHtml($work_date_M,3);
        $showAry['work_date_D']     = SHCSLib::genReportAnsHtml($work_date_D,3);

        $showAry['work_date_T1']    = $work_date_T1;
        $showAry['work_date_T2']    = $work_date_T2;
        $showAry['work_date_T3']    = $work_date_T3;
        $showAry['work_date_T4']    = $work_date_T4;

        $showAry['permit_danger_a'] = $permit_dangerA;
        $showAry['permit_danger_b'] = $permit_dangerB;
        $showAry['permit_danger_c'] = $permit_dangerC;

        $showAry['dept_name1']      = SHCSLib::genReportAnsHtml($be_dept_id1,19);
        $showAry['dept_name2']      = SHCSLib::genReportAnsHtml($be_dept_id2,16);
        $showAry['dept_name3']      = SHCSLib::genReportAnsHtml($be_dept_id3,16);
        $showAry['dept_name4']      = SHCSLib::genReportAnsHtml($be_dept_id4,8);
        $showAry['dept_name5']      = SHCSLib::genReportAnsHtml($be_dept_id5,8);
        $showAry['dept_name6']      = SHCSLib::genReportAnsHtml($be_dept_id6,19);

        $showAry['supply']          = SHCSLib::genReportAnsHtml($b_supply,16);

        $showAry['supply_men']      = SHCSLib::genReportAnsHtml($supply_worker_amt,16);

        $showAry['supply_car']      = SHCSLib::genReportAnsHtml($car_no,16);

        $showAry['work_place']      = SHCSLib::genReportAnsHtml($work_place,48);

        $showAry['project_no']      = SHCSLib::genReportAnsHtml($project_no,16);

        $showAry['work_memo']       = SHCSLib::genReportAnsHtml($work_memo,60);

        $showAry['chk_isOvertime']  = $isOvertime;

        $showAry['permit_check']    = SHCSLib::genReportAnsHtml($workCheckStr,54);

        $showAry['chk_workitem'][1] = $workitem_1;
        $showAry['chk_workitem'][2] = $workitem_2;
        $showAry['chk_workitem'][3] = $workitem_3;
        $showAry['chk_workitem'][4] = $workitem_4;
        $showAry['chk_workitem'][5] = $workitem_5;
        $showAry['chk_workitem'][6] = $workitem_6;
        $showAry['chk_workitem'][7] = $workitem_7;
        $showAry['chk_workitem'][8] = $workitem_8;
        $showAry['chk_workitem'][9] = $workitem_9;
        $showAry['chk_workitem'][10] = SHCSLib::genReportAnsHtml($workitem_10,16);

        $showAry['chk_workitem'][20] = $workitem_20;
        $showAry['chk_workitem'][21] = $workitem_21;
        $showAry['chk_workitem'][22] = $workitem_22;
        $showAry['chk_workitem'][23] = $workitem_23;
        $showAry['chk_workitem'][24] = $workitem_24;
        $showAry['chk_workitem'][25] = $workitem_25;
        $showAry['chk_workitem'][26] = $workitem_26;
        $showAry['chk_workitem'][27] = SHCSLib::genReportAnsHtml($workitem_27,16);

        $showAry['chk_workitem'][30] = $workitem_30;
        $showAry['chk_workitem'][31] = $workitem_31;
        $showAry['chk_workitem'][32] = $workitem_32;
        $showAry['chk_workitem'][33] = SHCSLib::genReportAnsHtml($workitem_33,16);

        $showAry['chk_workline'][1] = $workline_1;
        $showAry['chk_workline'][11]= SHCSLib::genReportAnsHtml($workline_11,50);
        $showAry['chk_workline'][2] = $workline_2;
        $showAry['chk_workline'][3] = $workline_3;
        $showAry['chk_workline'][4] = $workline_4;
        $showAry['chk_workline'][5] = $workline_5;
        $showAry['chk_workline'][6] = $workline_6;
        $showAry['chk_workline'][7] = $workline_7;
        $showAry['chk_workline'][8] = $workline_8;

        $this->showAry = $showAry;
        unset($showAry,$worklineAry,$workCheckAry,$workitemAry);
    }
    
    /**
     * 施工安全檢點
     */
    public function print_main2(){
        //監造簽名檔
        list($sign_url1,$sign_url1_at) = wp_work_topic_a::getTopicAns($this->work_id,4);
        //監造人員ID
        $signtCharger       = wp_work_process::getChargeUser($this->work_id,1);
        //代簽(如果不等於原本的監造ID)
        $sign_agent1        = ($this->workData->proejct_charge != $signtCharger)? '（代）' : '';
        
        //每一施工現場十公尺內應備有20型以上手提滅火器
        list($chk_supply_topic1)  = wp_work_topic_a::getTopicAns($this->work_id,76);
        $chk_supply_topic1a = isset($this->checkAry[$chk_supply_topic1])? $this->checkAry[$chk_supply_topic1] : $this->checkAry['N'];
        //已備有校正合格四用氣體偵測器，進行監測並每小時記錄備查
        list($chk_supply_topic6)  = wp_work_topic_a::getTopicAns($this->work_id,77);
        $chk_supply_topic6a = isset($this->checkAry[$chk_supply_topic6])? $this->checkAry[$chk_supply_topic6] : $this->checkAry['N'];

        //標準施工架
        list($chk_supply_topic2)  = wp_work_topic_a::getTopicAns($this->work_id,6);
        $chk_supply_topic2a = isset($this->checkAry[$chk_supply_topic2])? $this->checkAry[$chk_supply_topic2] : $this->checkAry['N'];
        //安全網
        list($chk_supply_topic3)  = wp_work_topic_a::getTopicAns($this->work_id,7);
        $chk_supply_topic3a = isset($this->checkAry[$chk_supply_topic3])? $this->checkAry[$chk_supply_topic3] : $this->checkAry['N'];
        //安全帶
        list($chk_supply_topic4)  = wp_work_topic_a::getTopicAns($this->work_id,8);
        $chk_supply_topic4a = isset($this->checkAry[$chk_supply_topic4])? $this->checkAry[$chk_supply_topic4] : $this->checkAry['N'];
        //其他
        list($chk_supply_topic5)  = wp_work_topic_a::getTopicAns($this->work_id,9);
        $chk_supply_topic5a = $chk_supply_topic5 ? $this->checkAry['Y'] : $this->checkAry['='];
        //二公尺以上無標準平台之高架作業已備妥
        if($chk_supply_topic2 == 'Y' || $chk_supply_topic3 == 'Y' || $chk_supply_topic4 == 'Y' || $chk_supply_topic5)
        {
            $chk_supply_topic7 = $this->checkAry['Y'];
        }elseif($chk_supply_topic2 == '=' || $chk_supply_topic3 == '=' || $chk_supply_topic4 == '=')
        {
            $chk_supply_topic7 = $this->checkAry['='];
        }else {
            $chk_supply_topic7 = $this->checkAry['N'];
        }

        //防爆電氣設備
        list($chk_supply_topic8)  = wp_work_topic_a::getTopicAns($this->work_id,10);
        $chk_supply_topic8a = isset($this->checkAry[$chk_supply_topic8])? $this->checkAry[$chk_supply_topic8] : $this->checkAry['N'];
        //安全工具
        list($chk_supply_topic9)  = wp_work_topic_a::getTopicAns($this->work_id,11);
        $chk_supply_topic9a = isset($this->checkAry[$chk_supply_topic9])? $this->checkAry[$chk_supply_topic9] : $this->checkAry['N'];
        //危險場所機具使用
        if($chk_supply_topic8 == 'Y' || $chk_supply_topic9 == 'Y')
        {
            $chk_supply_topic10 = $this->checkAry['Y'];
        }elseif($chk_supply_topic8 == '=' || $chk_supply_topic9 == '=')
        {
            $chk_supply_topic10 = $this->checkAry['='];
        } else {
            $chk_supply_topic10 = $this->checkAry['N'];
        }

        //防塵口罩
        list($chk_supply_topic11)  = wp_work_topic_a::getTopicAns($this->work_id,12);
        $chk_supply_topic11a = isset($this->checkAry[$chk_supply_topic11])? $this->checkAry[$chk_supply_topic11] : $this->checkAry['N'];
        //防毒口罩
        list($chk_supply_topic12)  = wp_work_topic_a::getTopicAns($this->work_id,13);
        $chk_supply_topic12a = isset($this->checkAry[$chk_supply_topic12])? $this->checkAry[$chk_supply_topic12] : $this->checkAry['N'];
        //防毒面具
        list($chk_supply_topic13)  = wp_work_topic_a::getTopicAns($this->work_id,14);
        $chk_supply_topic13a = isset($this->checkAry[$chk_supply_topic13])? $this->checkAry[$chk_supply_topic13] : $this->checkAry['N'];
        //自給式空氣呼吸器
        list($chk_supply_topic14)  = wp_work_topic_a::getTopicAns($this->work_id,15);
        $chk_supply_topic14a = isset($this->checkAry[$chk_supply_topic14])? $this->checkAry[$chk_supply_topic14] : $this->checkAry['N'];
        //輸氣管式空氣呼吸器
        list($chk_supply_topic15)  = wp_work_topic_a::getTopicAns($this->work_id,16);
        $chk_supply_topic15a = isset($this->checkAry[$chk_supply_topic15])? $this->checkAry[$chk_supply_topic15] : $this->checkAry['N'];
        //氧氣救生器
        list($chk_supply_topic16)  = wp_work_topic_a::getTopicAns($this->work_id,17);
        $chk_supply_topic16a = isset($this->checkAry[$chk_supply_topic16])? $this->checkAry[$chk_supply_topic16] : $this->checkAry['N'];
        //防護衣褲
        list($chk_supply_topic17)  = wp_work_topic_a::getTopicAns($this->work_id,18);
        $chk_supply_topic17a = isset($this->checkAry[$chk_supply_topic17])? $this->checkAry[$chk_supply_topic17] : $this->checkAry['N'];
        //防酸鹼手套
        list($chk_supply_topic18)  = wp_work_topic_a::getTopicAns($this->work_id,19);
        $chk_supply_topic18a = isset($this->checkAry[$chk_supply_topic18])? $this->checkAry[$chk_supply_topic18] : $this->checkAry['N'];
        //絕緣手套
        list($chk_supply_topic19)  = wp_work_topic_a::getTopicAns($this->work_id,20);
        $chk_supply_topic19a = isset($this->checkAry[$chk_supply_topic19])? $this->checkAry[$chk_supply_topic19] : $this->checkAry['N'];
        //防護眼罩
        list($chk_supply_topic20)  = wp_work_topic_a::getTopicAns($this->work_id,21);
        $chk_supply_topic20a = isset($this->checkAry[$chk_supply_topic20])? $this->checkAry[$chk_supply_topic20] : $this->checkAry['N'];
        //救生索
        list($chk_supply_topic21)  = wp_work_topic_a::getTopicAns($this->work_id,22);
        $chk_supply_topic21a = isset($this->checkAry[$chk_supply_topic21])? $this->checkAry[$chk_supply_topic21] : $this->checkAry['N'];
        //SOS自動警報器(局限空間作業時)
        list($chk_supply_topic22)  = wp_work_topic_a::getTopicAns($this->work_id,23);
        $chk_supply_topic22a = isset($this->checkAry[$chk_supply_topic22])? $this->checkAry[$chk_supply_topic22] : $this->checkAry['N'];

        //已備有個人防護具：
        if($chk_supply_topic11 == 'Y' || $chk_supply_topic12 == 'Y' || $chk_supply_topic13 == 'Y' || $chk_supply_topic14 == 'Y'
            || $chk_supply_topic15 == 'Y' || $chk_supply_topic16 == 'Y' || $chk_supply_topic17 == 'Y' || $chk_supply_topic18 == 'Y'
            || $chk_supply_topic19 == 'Y' || $chk_supply_topic20 == 'Y' || $chk_supply_topic21 == 'Y' || $chk_supply_topic22 == 'Y')
        {
            $chk_supply_topic23 = $this->checkAry['Y'];
        } elseif($chk_supply_topic11 == '=' || $chk_supply_topic12 == '=' || $chk_supply_topic13 == '=' || $chk_supply_topic14 == '='
            || $chk_supply_topic15 == '=' || $chk_supply_topic16 == '=' || $chk_supply_topic17 == '=' || $chk_supply_topic18 == '='
            || $chk_supply_topic19 == '=' || $chk_supply_topic20 == '=' || $chk_supply_topic21 == '=' || $chk_supply_topic22 == '=')
        {
            $chk_supply_topic23 = $this->checkAry['='];
        } else {
            $chk_supply_topic23 = $this->checkAry['N'];
        }

        //附加檢點
        $wp_checkAry = wp_work_check::getSelect($this->work_id,0);
        $chk_supply_topic60 = isset($wp_checkAry[4])? $this->checkAry['Y'] : $this->checkAry['='];
        $chk_supply_topic61 = isset($wp_checkAry[2])? $this->checkAry['Y'] : $this->checkAry['='];
        $chk_supply_topic62 = isset($wp_checkAry[6])? $this->checkAry['Y'] : $this->checkAry['='];
        $chk_supply_topic63 = isset($wp_checkAry[3])? $this->checkAry['Y'] : $this->checkAry['='];
        $chk_supply_topic64 = isset($wp_checkAry[7])? $this->checkAry['Y'] : $this->checkAry['='];
        $chk_supply_topic65 = isset($wp_checkAry[10])? $this->checkAry['Y'] : $this->checkAry['='];
        $chk_supply_topic66 = isset($wp_checkAry[99])? $this->checkAry['Y'] : $this->checkAry['='];
        $chk_supply_topic67 = [];
        if(isset($wp_checkAry[5]))  $chk_supply_topic67[] = $wp_checkAry[5];
        if(isset($wp_checkAry[8]))  $chk_supply_topic67[] = $wp_checkAry[8];
        if(isset($wp_checkAry[9]))  $chk_supply_topic67[] = $wp_checkAry[9];
        if(isset($wp_checkAry[11])) $chk_supply_topic67[] = $wp_checkAry[11];
        if(isset($wp_checkAry[12])) $chk_supply_topic67[] = $wp_checkAry[12];
        if(isset($wp_checkAry[13])) $chk_supply_topic67[] = $wp_checkAry[13];
        $chk_supply_topic68 = implode('，',$chk_supply_topic67);
        $chk_supply_topic67 = $chk_supply_topic68? $this->checkAry['Y'] : $this->checkAry['='];
        $chk_supply_topic70 = count($wp_checkAry)? $this->checkAry['Y'] : $this->checkAry['='];

        //看火者
        list($chk_supply_topic30)  = wp_work_topic_a::getTopicAns($this->work_id,25);
        if($chk_supply_topic30) $chk_supply_topic30 = User::getName($chk_supply_topic30);
        $chk_supply_topic32a  = ($chk_supply_topic30)? $this->checkAry['Y'] : $this->checkAry['='];
        //人孔監視者
        list($chk_supply_topic31)  = wp_work_topic_a::getTopicAns($this->work_id,26);
        if($chk_supply_topic31) $chk_supply_topic31 = User::getName($chk_supply_topic31);
        $chk_supply_topic32b  = ($chk_supply_topic31)? $this->checkAry['Y'] : $this->checkAry['='];

        //施工人員
        $chk_supply_topic34  = $this->getApiWorkPermitWorkerForPrint($this->work_id,27);
        $chk_supply_topic34_len = SHCSLib::mb_strlen($chk_supply_topic34);
        if($chk_supply_topic34_len > 20)
        {
            $chk_supply_topic34a = mb_substr($chk_supply_topic34,0,19);
            $chk_supply_topic34b = mb_substr($chk_supply_topic34,19);
        }  else {
            $chk_supply_topic34a = $chk_supply_topic34;
            $chk_supply_topic34b = '';
        }
        $chk_supply_topic35  = ($chk_supply_topic34)? $this->checkAry['Y'] : $this->checkAry['='];
//        dd($chk_supply_topic34);

        //缺氧作業主管
        $chk_supply_topic36  = $this->getApiWorkPermitWorkerForPrint($this->work_id,28);
        $chk_supply_topic37  = ($chk_supply_topic36)? $this->checkAry['Y'] : $this->checkAry['='];
        //施工架組配作業主管
        $chk_supply_topic38  = $this->getApiWorkPermitWorkerForPrint($this->work_id,29);
        $chk_supply_topic39  = ($chk_supply_topic38)? $this->checkAry['Y'] : $this->checkAry['='];
        //起重操作人員
        $chk_supply_topic40  = $this->getApiWorkPermitWorkerForPrint($this->work_id,30);
        $chk_supply_topic41  = ($chk_supply_topic40)? $this->checkAry['Y'] : $this->checkAry['='];
        //起重操作人員
        $chk_supply_topic42  = $this->getApiWorkPermitWorkerForPrint($this->work_id,31);
        $chk_supply_topic43  = ($chk_supply_topic42)? $this->checkAry['Y'] : $this->checkAry['='];
        //有機溶劑作業主管
        $chk_supply_topic44  = $this->getApiWorkPermitWorkerForPrint($this->work_id,32);
        $chk_supply_topic45  = ($chk_supply_topic44)? $this->checkAry['Y'] : $this->checkAry['='];
        //其他作業主管
        $chk_supply_topic46  = $this->getApiWorkPermitWorkerForPrint($this->work_id,34);
        $chk_supply_topic47  = ($chk_supply_topic46)? $this->checkAry['Y'] : $this->checkAry['='];

        //廠工帶班者簽名
        $sign_url2           = '';
        $chk_supply_topic50  = ($sign_url2)? $this->checkAry['Y'] : $this->checkAry['='];
        //職安衛人員
        list($sign_url3,$sign_url3_at,$sign_user3)  = wp_work_topic_a::getTopicAns($this->work_id,40);
        $chk_supply_topic51a = b_cust_a::getMobile($sign_user3);

        //工地負責人
        list($sign_url4,$sign_url4_at,$sign_user4)  = wp_work_topic_a::getTopicAns($this->work_id,74);
        $chk_supply_topic52a = b_cust_a::getMobile($sign_user4);

        $chk_supply_topic51 = ($sign_url3 || $sign_url4)? $this->checkAry['Y'] : $this->checkAry['N'];


        //
        $showAry = $this->showAry;
        //監造簽名
        $showAry['sign_url1']        = ($sign_url1)? $this->genImgHtml($sign_url1,$sign_url1_at).$sign_agent1 : ''; //監造人員

        $showAry['chk_supply_topic'][1] = $chk_supply_topic1a;
        $showAry['chk_supply_topic'][24] = $chk_supply_topic6a;

        $showAry['chk_supply_topic'][7] = $chk_supply_topic7;
        $showAry['chk_supply_topic'][2] = $chk_supply_topic2a;
        $showAry['chk_supply_topic'][3] = $chk_supply_topic3a;
        $showAry['chk_supply_topic'][4] = $chk_supply_topic4a;
        $showAry['chk_supply_topic'][5] = $chk_supply_topic5a;
        $showAry['chk_supply_topic'][6] = SHCSLib::genReportAnsHtml($chk_supply_topic5,16);;

        $showAry['chk_supply_topic'][8] = $chk_supply_topic8a;
        $showAry['chk_supply_topic'][9] = $chk_supply_topic9a;
        $showAry['chk_supply_topic'][10] = $chk_supply_topic10;

        $showAry['chk_supply_topic'][11] = $chk_supply_topic11a;
        $showAry['chk_supply_topic'][12] = $chk_supply_topic12a;
        $showAry['chk_supply_topic'][13] = $chk_supply_topic13a;
        $showAry['chk_supply_topic'][14] = $chk_supply_topic14a;
        $showAry['chk_supply_topic'][15] = $chk_supply_topic15a;
        $showAry['chk_supply_topic'][16] = $chk_supply_topic16a;
        $showAry['chk_supply_topic'][17] = $chk_supply_topic17a;
        $showAry['chk_supply_topic'][18] = $chk_supply_topic18a;
        $showAry['chk_supply_topic'][19] = $chk_supply_topic19a;
        $showAry['chk_supply_topic'][20] = $chk_supply_topic20a;
        $showAry['chk_supply_topic'][21] = $chk_supply_topic21a;
        $showAry['chk_supply_topic'][22] = $chk_supply_topic22a;
        $showAry['chk_supply_topic'][23] = $chk_supply_topic23;

        $showAry['chk_supply_topic'][30] = SHCSLib::genReportAnsHtml($chk_supply_topic30,16);
        $showAry['chk_supply_topic'][31] = SHCSLib::genReportAnsHtml($chk_supply_topic31,16);
        $showAry['chk_supply_topic']['32a'] = $chk_supply_topic32a;
        $showAry['chk_supply_topic']['32b'] = $chk_supply_topic32b;

        $showAry['chk_supply_topic']['34a'] = SHCSLib::genReportAnsHtml($chk_supply_topic34a,32);
        $showAry['chk_supply_topic']['34b'] = SHCSLib::genReportAnsHtml($chk_supply_topic34b,120);
        $showAry['chk_supply_topic'][35] = $chk_supply_topic35;

        $showAry['chk_supply_topic'][36] = SHCSLib::genReportAnsHtml($chk_supply_topic36,16);
        $showAry['chk_supply_topic'][37] = $chk_supply_topic37;

        $showAry['chk_supply_topic'][38] = SHCSLib::genReportAnsHtml($chk_supply_topic38,16);
        $showAry['chk_supply_topic'][39] = $chk_supply_topic39;

        $showAry['chk_supply_topic'][40] = SHCSLib::genReportAnsHtml($chk_supply_topic40,16);
        $showAry['chk_supply_topic'][41] = $chk_supply_topic41;

        $showAry['chk_supply_topic'][42] = SHCSLib::genReportAnsHtml($chk_supply_topic42,16);
        $showAry['chk_supply_topic'][43] = $chk_supply_topic43;

        $showAry['chk_supply_topic'][44] = SHCSLib::genReportAnsHtml($chk_supply_topic44,16);
        $showAry['chk_supply_topic'][45] = $chk_supply_topic45;

        $showAry['chk_supply_topic'][46] = SHCSLib::genReportAnsHtml($chk_supply_topic46,16);
        $showAry['chk_supply_topic'][47] = $chk_supply_topic47;

        $showAry['chk_supply_topic'][50] = $chk_supply_topic50;
        $showAry['sign_url2']            = ''; //監造人員

        $showAry['sign_url3']        = ($sign_url3)? $this->genImgHtml($sign_url3,$sign_url3_at) : ''; //職安衛人員
        $showAry['sign_url4']        = ($sign_url4)? $this->genImgHtml($sign_url4,$sign_url4_at) : ''; //工地負責人

        $showAry['chk_supply_topic'][51] = $chk_supply_topic51;
        $showAry['chk_supply_topic'][52] = SHCSLib::genReportAnsHtml($chk_supply_topic51a,15);
        $showAry['chk_supply_topic'][53] = SHCSLib::genReportAnsHtml($chk_supply_topic52a,15);

        $showAry['chk_supply_topic'][60] = $chk_supply_topic60;
        $showAry['chk_supply_topic'][61] = $chk_supply_topic61;
        $showAry['chk_supply_topic'][62] = $chk_supply_topic62;
        $showAry['chk_supply_topic'][63] = $chk_supply_topic63;
        $showAry['chk_supply_topic'][64] = $chk_supply_topic64;
        $showAry['chk_supply_topic'][65] = $chk_supply_topic65;
        $showAry['chk_supply_topic'][66] = $chk_supply_topic66;
        $showAry['chk_supply_topic'][67] = $chk_supply_topic67;
        $showAry['chk_supply_topic'][68] = SHCSLib::genReportAnsHtml($chk_supply_topic68,50);
        $showAry['chk_supply_topic'][70] = $chk_supply_topic70;

        $this->showAry = $showAry;
    }

    /**
     * 環境安全檢點(轄區)
     */
    public function print_main3(){

        //設備或管線原存物質
        list($chk_emp_topic2)  = wp_work_topic_a::getTopicAns($this->work_id,42);
        $chk_emp_topic1  = ($chk_emp_topic2)? $this->checkAry['Y'] : $this->checkAry['N'];

        //設備、管線已釋壓並吹驅乾淨或清洗
        list($chk_emp_topic3)  = wp_work_topic_a::getTopicAns($this->work_id,78);
        $chk_emp_topic3a = isset($this->checkAry[$chk_emp_topic3])? $this->checkAry[$chk_emp_topic3] : $this->checkAry['N'];

        //關斷
        list($chk_emp_topic5)  = wp_work_topic_a::getTopicAns($this->work_id,43);
        $chk_emp_topic5a = isset($this->checkAry[$chk_emp_topic5])? $this->checkAry[$chk_emp_topic5] : $this->checkAry['N'];
        //加盲
        list($chk_emp_topic6)  = wp_work_topic_a::getTopicAns($this->work_id,44);
        $chk_emp_topic6a = isset($this->checkAry[$chk_emp_topic6])? $this->checkAry[$chk_emp_topic6] : $this->checkAry['N'];
        //掛牌
        list($chk_emp_topic7)  = wp_work_topic_a::getTopicAns($this->work_id,148);
        $chk_emp_topic7a = isset($this->checkAry[$chk_emp_topic7])? $this->checkAry[$chk_emp_topic7] : $this->checkAry['N'];
        //盲板圖標示已掛於現場
        list($chk_emp_topic8)  = wp_work_topic_a::getTopicAns($this->work_id,45);
        $chk_emp_topic8a = isset($this->checkAry[$chk_emp_topic8])? $this->checkAry[$chk_emp_topic8] : $this->checkAry['N'];
        //確認進出口已
        if($chk_emp_topic5 == 'Y' || $chk_emp_topic6 == 'Y' || $chk_emp_topic7 == 'Y' || $chk_emp_topic8 == 'Y')
        {
            $chk_emp_topic4 = $this->checkAry['Y'];
        }elseif($chk_emp_topic5 == '=' || $chk_emp_topic6 == '=' || $chk_emp_topic7 == '=' || $chk_emp_topic8 == '=')
        {
            $chk_emp_topic4 = $this->checkAry['='];
        } else {
            $chk_emp_topic4 = $this->checkAry['N'];
        }

        //已備妥通風設備
        list($chk_emp_topic9)  = wp_work_topic_a::getTopicAns($this->work_id,80);
        $chk_emp_topic9a = isset($this->checkAry[$chk_emp_topic9])? $this->checkAry[$chk_emp_topic9] : $this->checkAry['N'];
        //電源已隔離、加鎖及掛牌標示
        list($chk_emp_topic10) = wp_work_topic_a::getTopicAns($this->work_id,81);
        $chk_emp_topic10a= isset($this->checkAry[$chk_emp_topic10])? $this->checkAry[$chk_emp_topic10] : $this->checkAry['N'];
        //施工現場十公尺內或下方之暗溝口、方井、電纜溝口已堵塞並密封
        list($chk_emp_topic11)  = wp_work_topic_a::getTopicAns($this->work_id,82);
        $chk_emp_topic11a = isset($this->checkAry[$chk_emp_topic11])? $this->checkAry[$chk_emp_topic11] : $this->checkAry['N'];
        //地面已無遺浮油、雜物及可燃物,確已做好安全處理
        list($chk_emp_topic12)  = wp_work_topic_a::getTopicAns($this->work_id,83);
        $chk_emp_topic12a = isset($this->checkAry[$chk_emp_topic12])? $this->checkAry[$chk_emp_topic12] : $this->checkAry['N'];

        //手提減火器
        list($chk_emp_topic13)  = wp_work_topic_a::getTopicAns($this->work_id,46);
        $chk_emp_topic13a = isset($this->checkAry[$chk_emp_topic13])? $this->checkAry[$chk_emp_topic13] : $this->checkAry['N'];
        //輪架式滅火車
        list($chk_emp_topic14)  = wp_work_topic_a::getTopicAns($this->work_id,47);
        $chk_emp_topic14a = isset($this->checkAry[$chk_emp_topic14])? $this->checkAry[$chk_emp_topic14] : $this->checkAry['N'];
        //高壓噴槍
        list($chk_emp_topic15)  = wp_work_topic_a::getTopicAns($this->work_id,48);
        $chk_emp_topic15a = isset($this->checkAry[$chk_emp_topic15])? $this->checkAry[$chk_emp_topic15] : $this->checkAry['N'];
        //消防水帶接妥消防栓
        list($chk_emp_topic16)  = wp_work_topic_a::getTopicAns($this->work_id,49);
        $chk_emp_topic16a = isset($this->checkAry[$chk_emp_topic16])? $this->checkAry[$chk_emp_topic16] : $this->checkAry['N'];
        //其它
        list($chk_emp_topic18)  = wp_work_topic_a::getTopicAns($this->work_id,50);
        $chk_emp_topic17  = $chk_emp_topic18 ? $this->checkAry['Y'] : $this->checkAry['N'];
        //施工現場十公尺內已備妥
        if($chk_emp_topic13 == 'Y' || $chk_emp_topic14 == 'Y' || $chk_emp_topic15 == 'Y' || $chk_emp_topic16 == 'Y' || $chk_emp_topic18)
        {
            $chk_emp_topic19 = $this->checkAry['Y'];
        }elseif($chk_emp_topic13 == '=' || $chk_emp_topic14 == '=' || $chk_emp_topic15 == '=' || $chk_emp_topic16 == '=')
        {
            $chk_emp_topic19 = $this->checkAry['='];
        }else {
            $chk_emp_topic19 = $this->checkAry['N'];
        }

        //地點:
        list($chk_emp_topic20)  = wp_work_topic_a::getTopicAns($this->work_id,51);
        //聯絡人:
        list($chk_emp_topic21)  = wp_work_topic_a::getTopicAns($this->work_id,52);
        //電話:
        list($chk_emp_topic22)  = wp_work_topic_a::getTopicAns($this->work_id,53);
        //緊急事故時,承攬商疏散到指定
        if($chk_emp_topic20 || $chk_emp_topic21 || $chk_emp_topic22 )
        {
            $chk_emp_topic23 = $this->checkAry['Y'];
        } else {
            $chk_emp_topic23 = $this->checkAry['N'];
        }

        //承攬商 檢點表
        list($chk_supply_topic99,$chk_supply_topic100)    = wp_work_topic_a::getTopicAns($this->work_id,24,$this->imgResize);
        $chk_supply_topic101   = isset($chk_supply_topic99[3])?    $chk_supply_topic99[3] : '';
        $chk_supply_topic102   = isset($chk_supply_topic99[4])?    $chk_supply_topic99[4] : '';
        $chk_supply_topic103   = isset($chk_supply_topic99[16])?   $chk_supply_topic99[16] : '';
        $chk_supply_topic104   = isset($chk_supply_topic99[17])?   $chk_supply_topic99[17] : '';
        $chk_supply_topic105   = isset($chk_supply_topic99[5])?    $chk_supply_topic99[5] : '';
        $chk_supply_topic106   = isset($chk_supply_topic99[15])?   $chk_supply_topic99[15] : '';
        $chk_supply_topic107   = isset($chk_supply_topic99[8])?    $chk_supply_topic99[8] : '';//氣體偵測拍照
        //承攬商簽名
        list($chk_supply_topic108)  = wp_work_topic_a::getTopicAns($this->work_id,40);

        //職員 檢點表
        list($chk_emp_topic99,$chk_emp_topic100)    = wp_work_topic_a::getTopicAns($this->work_id,54);
        $chk_emp_topic101   = isset($chk_emp_topic99[41])?    $chk_emp_topic99[41]   : '';
        $chk_emp_topic102   = isset($chk_emp_topic99[42])?    $chk_emp_topic99[42]   : '';
        $chk_emp_topic103   = isset($chk_emp_topic99[43])?    $chk_emp_topic99[43]  : '';
        $chk_emp_topic104   = isset($chk_emp_topic99[44])?    $chk_emp_topic99[44]  : '';
        $chk_emp_topic105   = isset($chk_emp_topic99[45])?    $chk_emp_topic99[45]   : '';
        $chk_emp_topic106   = isset($chk_emp_topic99[46])?    $chk_emp_topic99[46]  : '';
        $chk_emp_topic107   = isset($chk_emp_topic99[47])?     $chk_emp_topic99[47]   : '';//氣體偵測拍照
        //檢點者
        list($sign_url5,$sign_url5_at)   = wp_work_topic_a::getTopicAns($this->work_id,57);
        //連繫者
        $chk_emp_topic30 = ($this->permit_danger == 'A')? $this->checkAry['Y'] : $this->checkAry['N'];
        $chk_emp_topic31 = ($this->permit_danger != 'A')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_emp_topic32)  = wp_work_topic_a::getTopicAns($this->work_id,85);
        list($sign_url6,$sign_url6_at)  = wp_work_topic_a::getTopicAns($this->work_id,62);

        //延時工作時間
        $chk_emp_delay_Ary = wp_work_rp_extension::isExist($this->work_id);
        $chk_emp_delay = wp_work_rp_extension::getExtensionData($this->work_id);
        $chk_emp_delay_Topic     = ($chk_emp_delay_Ary != 0)? $this->checkAry['Y'] : $this->checkAry['N'];
        $eta_etime1_a = substr($chk_emp_delay['eta_etime1'], 11, 2) ?? '';
        $eta_etime1_b = substr($chk_emp_delay['eta_etime1'], 14, 2) ?? '';
        $eta_etime2_a = substr($chk_emp_delay['eta_etime2'], 11, 2) ?? '';
        $eta_etime2_b = substr($chk_emp_delay['eta_etime2'], 14, 2) ?? '';
        $chk_delay_charge1_topic     = ($chk_emp_delay['charge_user1'] != '0') ? $this->checkAry['Y'] : $this->checkAry['N'];
        $chk_delay_charge2_topic     = ($chk_emp_delay['charge_user2'] != '0') ? $this->checkAry['Y'] : $this->checkAry['N'];
        $chk_delay_charge1 = ($chk_emp_delay['charge_user1'] != '0') ? $this->genImgHtml($chk_emp_delay['charge_user1'], '') : '';
        $chk_delay_charge2 = ($chk_emp_delay['charge_user2'] != '0') ? $this->genImgHtml($chk_emp_delay['charge_user1'], '') : '';

        //工安叮嚀與重要提醒事項
        list($chk_emp_topic33a) = wp_work_topic_a::getTopicAns($this->work_id,122);
        list($chk_emp_topic33b) = wp_work_topic_a::getTopicAns($this->work_id,123);
        list($chk_emp_topic33c) = wp_work_topic_a::getTopicAns($this->work_id,124);
        list($chk_emp_topic33d) = wp_work_topic_a::getTopicAns($this->work_id,164);
        list($chk_emp_topic33d2) = wp_work_topic_a::getTopicAns($this->work_id,163);
        list($chk_emp_topic33e) = wp_work_topic_a::getTopicAns($this->work_id,165); //2019-11-20 監造
        list($chk_emp_topic33j) = wp_work_topic_a::getTopicAns($this->work_id,179); //2019-12-10 監造
        list($chk_emp_topic33f) = wp_work_topic_a::getTopicAns($this->work_id,167); //2019-11-20 轄區負責人
        list($chk_emp_topic33g) = wp_work_topic_a::getTopicAns($this->work_id,168); //2019-11-20 轄區負責人
        $chk_emp_topic33h = ($chk_emp_topic33f)? $chk_emp_topic33f :'';
        $chk_emp_topic33h.= ($chk_emp_topic33g)? (strlen($chk_emp_topic33h)? '，' : '').$chk_emp_topic33g :'';
        $chk_emp_topic33i = ($chk_emp_topic33e)? $chk_emp_topic33e :'';
        $chk_emp_topic33i.= ($chk_emp_topic33j)? (strlen($chk_emp_topic33j)? '，' : '').$chk_emp_topic33j :'';

        //工安叮嚀與重要提醒事項
        $chk_emp_topic33 = '';
        if($chk_emp_topic33a) $chk_emp_topic33 .= '轄區：'.$chk_emp_topic33a;
        if($chk_emp_topic33a && $chk_emp_topic33b) $chk_emp_topic33 .= '，';
        if($chk_emp_topic33b) $chk_emp_topic33 .= '連繫者：'.$chk_emp_topic33b;
        if(($chk_emp_topic33a || $chk_emp_topic33b) && $chk_emp_topic33c)  $chk_emp_topic33 .= '，';
        if($chk_emp_topic33c) $chk_emp_topic33 .= '複檢者：'.$chk_emp_topic33c;
        if($chk_emp_topic33a || $chk_emp_topic33b || $chk_emp_topic33c) $chk_emp_topic33 .= '<br/>';
        if($chk_emp_topic33i) $chk_emp_topic33 .= '監造：'.$chk_emp_topic33i;
        if($chk_emp_topic33i && $chk_emp_topic33h) $chk_emp_topic33 .= '，';
        if($chk_emp_topic33h) $chk_emp_topic33 .= '轄區負責人：'.$chk_emp_topic33h;
        if (($chk_emp_topic33d == 'Y') || ($chk_emp_topic33d == '=')) $chk_emp_topic33d = $chk_emp_topic33d2;
        if($chk_emp_topic33d) $chk_emp_topic33 .= '<br/>主簽者：'.$chk_emp_topic33d;

        //A級
        list($chk_emp_topic34)  = wp_work_topic_a::getTopicAns($this->work_id,125);
        $chk_emp_topic34a = isset($this->checkAry[$chk_emp_topic34])? $this->checkAry[$chk_emp_topic34] : $this->checkAry['N'];

        if($this->permit_danger == 'A')
        {
            list($chk_emp_topic37a) = wp_work_topic_a::getTopicAns($this->work_id,127);
            list($chk_emp_topic37b) = wp_work_topic_a::getTopicAns($this->work_id,133);
            list($chk_emp_topic37c) = wp_work_topic_a::getTopicAns($this->work_id,134);
            list($chk_emp_topic37d) = wp_work_topic_a::getTopicAns($this->work_id,163);
            list($chk_emp_topic37f) = wp_work_topic_a::getTopicAns($this->work_id,169); //2019-11-20 轄區負責人
            list($chk_emp_topic37g) = wp_work_topic_a::getTopicAns($this->work_id,170); //2019-11-20 轄區負責人
            $chk_emp_topic37h = '<b>A級會勘：</b><br/>';
            $chk_emp_topic37h.= ($chk_emp_topic37f)? $chk_emp_topic37f :'';
            $chk_emp_topic37h.= ($chk_emp_topic37g)? (strlen($chk_emp_topic37h)? '，' : '').$chk_emp_topic37g :'';
            $chk_emp_topic37 = '監工：'.$chk_emp_topic37a.'<br>承商：'.$chk_emp_topic37b.'<br/>轄區：'.$chk_emp_topic37c;
            if($chk_emp_topic37h) $chk_emp_topic37 .= '<br>轄區負責人：'.$chk_emp_topic37h;
            if($chk_emp_topic37d) $chk_emp_topic37 .= '<br>主簽者：'.$chk_emp_topic37d;
            if($chk_emp_topic37)  $chk_emp_topic33 .= $chk_emp_topic37;
        }


        //監工簽核
        list($sign_url8,$sign_url8_at)  = wp_work_topic_a::getTopicAns($this->work_id,130);
        $chk_emp_topic38 = ($sign_url8)? $this->checkAry['Y'] : $this->checkAry['N'];
        //施工簽名
        list($sign_url9,$sign_url9_at)  = wp_work_topic_a::getTopicAns($this->work_id,128);
        $chk_emp_topic39 = ($sign_url9)? $this->checkAry['Y'] : $this->checkAry['N'];
        //轄區簽名
        list($sign_url10,$sign_url10_at)= wp_work_topic_a::getTopicAns($this->work_id,129);
        $chk_emp_topic40 = ($sign_url10)? $this->checkAry['Y'] : $this->checkAry['N'];

        //會簽主簽人簽章
        list($sign_url14,$sign_url14_at)         = wp_work_topic_a::getTopicAns($this->work_id,65); //會簽主簽人簽章
        //
        list($sign_url15,$sign_url15_at)         = wp_work_topic_a::getTopicAns($this->work_id,58); //轄區負責人/職安人員簽名
        list($sign_url16,$sign_url16_at)         = wp_work_topic_a::getTopicAns($this->work_id,67); //轄區主簽人簽章
        list($sign_url17,$sign_url17_at)         = wp_work_topic_a::getTopicAns($this->work_id,131);//經理簽章

        list($sign_url18,$sign_url18_at)         = wp_work_topic_a::getTopicAns($this->work_id,90); //承攬商收工申請
        list($sign_url19,$sign_url19_at)         = wp_work_topic_a::getTopicAns($this->work_id,92); //轄區同意收工
//        dd($sign_url14,$sign_url15,$sign_url16,$sign_url17,$sign_url18,$sign_url19);

        list($chk_emp_topic50)    = wp_work_topic_a::getTopicAns($this->work_id,120);
        $chk_emp_topic50    = ($chk_emp_topic50)? date('H:i',strtotime($chk_emp_topic50)) : '';
        //開始時間
        if($chk_emp_topic50)
        {
            $topic50Ary         = explode(':',$chk_emp_topic50);
            if($topic50Ary[0] > 12)  $chk_emp_topic50 = '下午 13:00 至 '.$chk_emp_topic50;
            if($topic50Ary[0] <= 12) $chk_emp_topic50 = '上午 '.$chk_emp_topic50.' 至 12:00';
            $chk_emp_topic50    = $chk_emp_topic50;
        }

        //結束時間
        list($chk_emp_topic51)    = wp_work_topic_a::getTopicAns($this->work_id,121);
        $chk_emp_topic51    = ($chk_emp_topic51)? date('H:i',strtotime($chk_emp_topic51)) : '';
        if($chk_emp_topic51)
        {
            $topic51Ary     = explode(':',$chk_emp_topic51);
            if($topic51Ary[0] > 12)  $chk_emp_topic51 = '下午 13:00 至 '.$chk_emp_topic51;
            if($topic51Ary[0] <= 12) $chk_emp_topic51 = '上午 '.$chk_emp_topic51.' 至 12:00';
            $chk_emp_topic51    = $chk_emp_topic51;
        }
        //是否收工
        $chk_emp_topic52    = ($sign_url18)? $this->checkAry['Y'] : $this->checkAry['N'];
        
        $showAry = $this->showAry;
        $showAry['chk_emp_topic'][1] = $chk_emp_topic1;
        $showAry['chk_emp_topic'][2] = $chk_emp_topic2;
        $showAry['chk_emp_topic'][3] = $chk_emp_topic3a;
        $showAry['chk_emp_topic'][4] = $chk_emp_topic4;
        $showAry['chk_emp_topic'][5] = $chk_emp_topic5a;
        $showAry['chk_emp_topic'][6] = $chk_emp_topic6a;
        $showAry['chk_emp_topic'][7] = $chk_emp_topic7a;
        $showAry['chk_emp_topic'][8] = $chk_emp_topic8a;
        $showAry['chk_emp_topic'][9] = $chk_emp_topic9a;
        $showAry['chk_emp_topic'][10] = $chk_emp_topic10a;
        $showAry['chk_emp_topic'][11] = $chk_emp_topic11a;
        $showAry['chk_emp_topic'][12] = $chk_emp_topic12a;
        $showAry['chk_emp_topic'][13] = $chk_emp_topic13a;
        $showAry['chk_emp_topic'][14] = $chk_emp_topic14a;
        $showAry['chk_emp_topic'][15] = $chk_emp_topic15a;
        $showAry['chk_emp_topic'][16] = $chk_emp_topic16a;
        $showAry['chk_emp_topic'][17] = $chk_emp_topic17;
        $showAry['chk_emp_topic'][18] = SHCSLib::genReportAnsHtml($chk_emp_topic18,16);
        $showAry['chk_emp_topic'][19] = $chk_emp_topic19;
        $showAry['chk_emp_topic'][20] = SHCSLib::genReportAnsHtml($chk_emp_topic20,16);
        $showAry['chk_emp_topic'][21] = SHCSLib::genReportAnsHtml($chk_emp_topic21,16);
        $showAry['chk_emp_topic'][22] = SHCSLib::genReportAnsHtml($chk_emp_topic22,16);
        $showAry['chk_emp_topic'][23] = $chk_emp_topic23;

        //承攬商氣體偵測
        $showAry['chk_supply_topic'][100] = $chk_supply_topic100;
        $showAry['chk_supply_topic'][101] = $chk_supply_topic101;
        $showAry['chk_supply_topic'][102] = $chk_supply_topic102;
        $showAry['chk_supply_topic'][103] = $chk_supply_topic103;
        $showAry['chk_supply_topic'][104] = $chk_supply_topic104;
        $showAry['chk_supply_topic'][105] = $chk_supply_topic105;
        $showAry['chk_supply_topic'][106] = $chk_supply_topic106;
        $showAry['chk_supply_topic'][107] = $chk_supply_topic107;
        $showAry['chk_supply_topic'][108] = ($chk_supply_topic108)? $this->genImgHtml($chk_supply_topic108,'') : '';
        //轄區氣體偵測
        $showAry['chk_emp_topic'][100] = $chk_emp_topic100;
        $showAry['chk_emp_topic'][101] = $chk_emp_topic101;
        $showAry['chk_emp_topic'][102] = $chk_emp_topic102;
        $showAry['chk_emp_topic'][103] = $chk_emp_topic103;
        $showAry['chk_emp_topic'][104] = $chk_emp_topic104;
        $showAry['chk_emp_topic'][105] = $chk_emp_topic105;
        $showAry['chk_emp_topic'][106] = $chk_emp_topic106;
        $showAry['chk_emp_topic'][107] = $chk_emp_topic107;
        $showAry['chk_emp_topic'][108] = ($sign_url5)? $this->genImgHtml($sign_url5,'') : '';

        $showAry['sign_url5']        = ($sign_url5)? $this->genImgHtml($sign_url5,$sign_url5_at) : '';
        $showAry['sign_url6']        = ($sign_url6)? $this->genImgHtml($sign_url6,$sign_url6_at) : '';
        $showAry['sign_url7']        = ''; //聯繫者

        $showAry['chk_emp_delay_Topic'] = $chk_emp_delay_Topic; //是否為延長工時
        $showAry['eta_etime1_a'] = $eta_etime1_a; //延長工時(預計收工時間)-時
        $showAry['eta_etime1_b'] = $eta_etime1_b; //延長工時(預計收工時間)-分
        $showAry['eta_etime2_a'] = $eta_etime2_a; //延長工時(延長收工時間)-時
        $showAry['eta_etime2_b'] = $eta_etime2_b; //延長工時(延長收工時間)-分
        $showAry['chk_delay_charge1_topic'] = $chk_delay_charge1_topic; //監造
        $showAry['chk_delay_charge1'] = $chk_delay_charge1; //監造
        $showAry['chk_delay_charge2_topic'] = $chk_delay_charge2_topic; //轄區
        $showAry['chk_delay_charge2'] = $chk_delay_charge2; //轄區

        $showAry['chk_emp_topic'][30] = $chk_emp_topic30;
        $showAry['chk_emp_topic'][31] = $chk_emp_topic31;
        $showAry['chk_emp_topic'][32] = $chk_emp_topic32;

        $showAry['chk_emp_topic'][33] = $chk_emp_topic33;
        $showAry['chk_emp_topic'][34] = $chk_emp_topic34a;

        $showAry['sign_url8']         = ($sign_url8)? $this->genImgHtml($sign_url8,$sign_url8_at) : ''; //
        $showAry['sign_url9']         = ($sign_url9)? $this->genImgHtml($sign_url9,$sign_url9_at) : ''; //
        $showAry['sign_url10']        = ($sign_url10)? $this->genImgHtml($sign_url10,$sign_url10_at) : ''; //
        $showAry['chk_emp_topic'][38] = $chk_emp_topic38;
        $showAry['chk_emp_topic'][39] = $chk_emp_topic39;
        $showAry['chk_emp_topic'][40] = $chk_emp_topic40;

        $showAry['sign_url14']        = ($sign_url14)? $this->genImgHtml($sign_url14,$sign_url14_at) : ''; //
        $showAry['sign_url15']        = ($sign_url15)? $this->genImgHtml($sign_url15,$sign_url15_at) : ''; //
        $showAry['sign_url16']        = ($sign_url16)? $this->genImgHtml($sign_url16,$sign_url16_at) : ''; //
        $showAry['sign_url17']        = ($sign_url17)? $this->genImgHtml($sign_url17,$sign_url17_at) : ''; //
        $showAry['sign_url18']        = ($sign_url18)? $this->genImgHtml($sign_url18,$sign_url18_at) : ''; //
        $showAry['sign_url19']        = ($sign_url19)? $this->genImgHtml($sign_url19,$sign_url19_at) : ''; //

//        $showAry['chk_emp_topic'][35] = $chk_emp_topic35a;
//        $showAry['chk_emp_topic'][36] = $chk_emp_topic36a;
//        $showAry['chk_emp_topic'][37] = $chk_emp_topic37;
//
        $showAry['chk_emp_topic'][50] = $chk_emp_topic50;
        $showAry['chk_emp_topic'][51] = $chk_emp_topic51;
        $showAry['chk_emp_topic'][52] = $chk_emp_topic52;
//
//        $showAry['sign_url7']        = ($sign_url7)? '<img src="'.$sign_url7.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url7_at,11,5).'</span>' : ''; //監造人員
//        $showAry['sign_url11']       = '';
//        $showAry['sign_url12']       = '';
//        $showAry['sign_url13']       = '';
        $this->showAry = $showAry;
    }

    /**
     * 氣體偵測&巡邏
     */
    public function  print_main4(){
        //危害告知
        list($chk_danger_topic1)  = wp_work_topic_a::getTopicAns($this->work_id,97);
        $chk_danger_topic1  = ($chk_danger_topic1 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic2)  = wp_work_topic_a::getTopicAns($this->work_id,98);
        $chk_danger_topic2  = ($chk_danger_topic2 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic3)  = wp_work_topic_a::getTopicAns($this->work_id,99);
        $chk_danger_topic3  = ($chk_danger_topic3 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic4)  = wp_work_topic_a::getTopicAns($this->work_id,100);
        $chk_danger_topic4  = ($chk_danger_topic4 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic5)  = wp_work_topic_a::getTopicAns($this->work_id,101);
        $chk_danger_topic5  = ($chk_danger_topic5 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic6)  = wp_work_topic_a::getTopicAns($this->work_id,102);
        $chk_danger_topic6  = ($chk_danger_topic6 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic7)  = wp_work_topic_a::getTopicAns($this->work_id,103);
        $chk_danger_topic7  = ($chk_danger_topic7 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic8)  = wp_work_topic_a::getTopicAns($this->work_id,104);
        $chk_danger_topic8  = ($chk_danger_topic8 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic9)  = wp_work_topic_a::getTopicAns($this->work_id,105);
        $chk_danger_topic9  = ($chk_danger_topic9 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic10) = wp_work_topic_a::getTopicAns($this->work_id,106);
        $chk_danger_topic10 = ($chk_danger_topic10 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic11) = wp_work_topic_a::getTopicAns($this->work_id,107);
        $chk_danger_topic11 = ($chk_danger_topic11 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic12) = wp_work_topic_a::getTopicAns($this->work_id,108);
        $chk_danger_topic12 = ($chk_danger_topic12 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic13) = wp_work_topic_a::getTopicAns($this->work_id,109);
        $chk_danger_topic13 = ($chk_danger_topic13 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic14) = wp_work_topic_a::getTopicAns($this->work_id,110);
        $chk_danger_topic14 = ($chk_danger_topic14 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic15) = wp_work_topic_a::getTopicAns($this->work_id,111);
        $chk_danger_topic15 = ($chk_danger_topic15 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic16) = wp_work_topic_a::getTopicAns($this->work_id,112);
        $chk_danger_topic16 = ($chk_danger_topic16 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic17) = wp_work_topic_a::getTopicAns($this->work_id,113);
        $chk_danger_topic17 = ($chk_danger_topic17 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic18) = wp_work_topic_a::getTopicAns($this->work_id,114);
        $chk_danger_topic18 = ($chk_danger_topic18 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic19) = wp_work_topic_a::getTopicAns($this->work_id,115);
        $chk_danger_topic19 = ($chk_danger_topic19 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic20) = wp_work_topic_a::getTopicAns($this->work_id,116);
        $chk_danger_topic20 = ($chk_danger_topic20 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic21) = wp_work_topic_a::getTopicAns($this->work_id,117);
        $chk_danger_topic21 = ($chk_danger_topic21 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic22) = wp_work_topic_a::getTopicAns($this->work_id,118);
        $chk_danger_topic22 = ($chk_danger_topic22 == 'Y')? $this->checkAry['Y'] : $this->checkAry['N'];
        list($chk_danger_topic23) = wp_work_topic_a::getTopicAns($this->work_id,5); //職安衛簽名
        $chk_danger_topic23a= ($chk_danger_topic23)? '<img src="'.$chk_danger_topic23.'" class="sign_img" width="80"  height="25">' : '';

        $chk_danger_topic24 = wp_work_topic_a::getTopicAnsAt($this->work_id,5); //職安衛簽名時間
        $chk_danger_topic24a= date('Y 年 m 月 d 日  H 點 i 分',strtotime($chk_danger_topic24));

        $showAry = $this->showAry;
        $showAry['chk_danger_topic'][1] = $chk_danger_topic1;
        $showAry['chk_danger_topic'][2] = $chk_danger_topic2;
        $showAry['chk_danger_topic'][3] = $chk_danger_topic3;
        $showAry['chk_danger_topic'][4] = $chk_danger_topic4;
        $showAry['chk_danger_topic'][5] = $chk_danger_topic5;
        $showAry['chk_danger_topic'][6] = $chk_danger_topic6;
        $showAry['chk_danger_topic'][7] = $chk_danger_topic7;
        $showAry['chk_danger_topic'][8] = $chk_danger_topic8;
        $showAry['chk_danger_topic'][9] = $chk_danger_topic9;
        $showAry['chk_danger_topic'][10] = $chk_danger_topic10;
        $showAry['chk_danger_topic'][11] = $chk_danger_topic11;
        $showAry['chk_danger_topic'][12] = $chk_danger_topic12;
        $showAry['chk_danger_topic'][13] = $chk_danger_topic13;
        $showAry['chk_danger_topic'][14] = $chk_danger_topic14;
        $showAry['chk_danger_topic'][15] = $chk_danger_topic15;
        $showAry['chk_danger_topic'][16] = $chk_danger_topic16;
        $showAry['chk_danger_topic'][17] = $chk_danger_topic17;
        $showAry['chk_danger_topic'][18] = $chk_danger_topic18;
        $showAry['chk_danger_topic'][19] = $chk_danger_topic19;
        $showAry['chk_danger_topic'][20] = $chk_danger_topic20;
        $showAry['chk_danger_topic'][21] = $chk_danger_topic21;
        $showAry['chk_danger_topic'][22] = $chk_danger_topic22;
        $showAry['chk_danger_topic'][23] = $chk_danger_topic23a;
        $showAry['chk_danger_topic'][24] = $chk_danger_topic24a;
        
        $this->showAry = $showAry;
    }

    /**
     * 照片
     */
    public function  print_main5(){
        $chk_danger_topic24 = wp_work_topic_a::getTopicAnsAt($this->work_id,5); //職安衛簽名時間
//        $chk_danger_topic24a= date('Y 年 m 月 d 日  H 點 i 分',strtotime($chk_danger_topic24));
        $tmpPhoto = [];
        //圖片記錄
        //環境檢點表
        $total_max_row  = 9;
        $chk_tip_topic  = [];
        $chk_tip_topic1 = wp_work_check_topic::getCheckTopicAns($this->work_id,3,$this->imgResize); //承攬商/廠方施工部門作業環境紀錄表
        $chk_tip_topic2 = wp_work_check_topic::getCheckTopicAns($this->work_id,4,$this->imgResize); //轄區施工部門作業環境紀錄表
        $chk_tip_topic3 = wp_work_check_topic::getCheckTopicAns($this->work_id,2,$this->imgResize); //巡邏

        $chk_tip_topic1_amt = count($chk_tip_topic1);
        $chk_tip_topic2_amt = count($chk_tip_topic2);
        $chk_tip_topic3_amt = count($chk_tip_topic3);
        $chk_tip_topic_total_amt = ($chk_tip_topic1_amt > $chk_tip_topic2_amt) ? $chk_tip_topic1_amt : $chk_tip_topic2_amt;
        $chk_tip_topic_total_amt = ($chk_tip_topic3_amt > $chk_tip_topic_total_amt) ? $chk_tip_topic3_amt : $chk_tip_topic_total_amt;
        $chk_tip_topic_count_amt = intval(ceil($chk_tip_topic_total_amt / $total_max_row));
        if(!$chk_tip_topic_count_amt) $chk_tip_topic_count_amt = 1;
        //承攬商/廠方施工部門作業環境紀錄表
        if(count($chk_tip_topic1))
        {
            $j1 = 1;
            foreach ( $chk_tip_topic1 as $val )
            {
                $i1 = 1;
                if(!isset($val['ans'])) continue;
                $ans   = $val['ans'];
                $sign  = $val['record_sign'];
                $recordStamp = isset($ans[18])? $ans[18] : $val['record_stamp'];
                $stamp = substr($recordStamp,0,19);
                $tmp = [];
                $tmp[1] = $stamp;
                $tmp[2] = isset($ans[19])? $ans[19] : '';
                $tmp[3] = isset($ans[20])? $ans[20] : '';
                $tmp[4] = isset($ans[21])? $ans[21] : '';
                $tmp[5] = isset($ans[22])? $ans[22] : '';
                $tmp[6] = $sign ? '<img src="'.$sign.'" class="sign_img" width="80" height="25">' : '';
                $chk_tip_topic[$j1][1][] =$tmp;
                $i1++;
                if($i1 >= 9) $j1++;
                //作業環境紀錄表圖片
                $chk_img_tmp         = isset($ans[25])? $ans[25] : '';
                $tmpPhoto['title']   = '承攬商作業環境紀錄表'.$stamp;
                $tmpPhoto['img']     = ($chk_img_tmp)? '<img src="'.$chk_img_tmp.'" class="chk_img" >' : '';
                $this->chk_photo_topic2[]   = $tmpPhoto;
            }
        }
        //轄區施工部門作業環境紀錄表
        if(count($chk_tip_topic2))
        {
            $j2 = 1;
            foreach ( $chk_tip_topic2 as $val )
            {
                $i2 = 1;
                if(!isset($val['ans'])) continue;
                $ans   = $val['ans'];
                $sign  = $val['record_sign'];
                $recordStamp = isset($ans[29])? $ans[29] : $val['record_stamp'];
                $stamp = substr($recordStamp,0,19);
                $tmp = [];
                $tmp[1] = $stamp;
                $tmp[2] = isset($ans[30])? $ans[30] : '';
                $tmp[3] = isset($ans[31])? $ans[31] : '';
                //$tmp[4] = isset($ans[34])? (($ans[34])? ($ans[34] . (($ans[35])? ('-'. $ans[35]) : '' )) : '') : '';
                $tmp[4] = isset($ans[32])? $ans[32] : '';
                $tmp[5] = isset($ans[33])? $ans[33] : '';
                $tmp[6] = $sign ? '<img src="'.$sign.'" class="sign_img" width="80" height="25">' : '';
                $chk_tip_topic[$j2][2][] =$tmp;
                $i2++;
                if($i2 >= 9) $j2++;
                //作業環境紀錄表圖片
                $chk_img_tmp          = isset($ans[36])? $ans[36] : '';
                $tmpPhoto['title']    = '轄區作業環境紀錄表'.$stamp;
                $tmpPhoto['img']      = ($chk_img_tmp)? '<img src="'.$chk_img_tmp.'" class="chk_img" >' : '';
                $this->chk_photo_topic2[]   = $tmpPhoto;
            }
        }
        //巡邏
        if(count($chk_tip_topic3))
        {
            $j3 = 1;
            foreach ( $chk_tip_topic3 as $val )
            {
                $i3 = 1;
                if(!isset($val['ans'])) continue;
                $dept = $val['record_dept'];
                $ans  = $val['ans'];
                $sign = $val['record_sign'];
                $recordStamp = isset($ans[9])? $ans[9] : $val['record_stamp'];
                $stamp = substr($recordStamp,0,19);
                $tmp = [];
                $tmp[1] = $dept;
                $tmp[2] = $stamp;
                $tmp[3] = isset($sign)? '<img src="'.$sign.'" class="sign_img" width="80" height="25">' : '';
                $tmp[4] = isset($ans[12])? $ans[12] : '';
                $chk_tip_topic[$j3][3][] =$tmp;
                $i3++;
                if($i3 >= 9) $j3++;
                //作業環境紀錄表圖片
                $chk_img_tmp         = isset($ans[10])? $ans[10] : '';
                $tmpPhoto['title']   = '巡查紀錄表'.$stamp;
                $tmpPhoto['img']     = ($chk_img_tmp)? '<img src="'.$chk_img_tmp.'" class="chk_img" >' : '';
                $this->chk_photo_topic2[]   = $tmpPhoto;
            }
        }
        //補足 九筆
        for ($i = 1; $i <= $chk_tip_topic_count_amt;$i++)
        {
            if(!isset($chk_tip_topic[$i])) $chk_tip_topic[$i] = [];
            if(!isset($chk_tip_topic[$i][1])) $chk_tip_topic[$i][1] = [];
            if(!isset($chk_tip_topic[$i][2])) $chk_tip_topic[$i][2] = [];
            if(!isset($chk_tip_topic[$i][3])) $chk_tip_topic[$i][3] = [];
            $tipCont1 = count($chk_tip_topic[$i][1]);
            if($tipCont1 < $total_max_row)
            {
                for($j = 1; $j <= intval($total_max_row - $tipCont1); $j++)
                {
                    $chk_tip_topic[$i][1][] = [];
                }
            }


            $tipCont2 = count($chk_tip_topic[$i][2]);
            if($tipCont2 < $total_max_row)
            {
                for($j = 1; $j <= intval($total_max_row - $tipCont2); $j++)
                {
                    $chk_tip_topic[$i][2][] = [];
                }
            }
            $tipCont3 = count($chk_tip_topic[$i][3]);
            if($tipCont3 < $total_max_row)
            {
                for($j = 1; $j <= intval($total_max_row - $tipCont3); $j++)
                {
                    $chk_tip_topic[$i][3][] = [];
                }
            }
        }
//        dd($chk_tip_topic_total_amt,$chk_tip_topic_count_amt,$chk_tip_topic1,$chk_tip_topic2,$chk_tip_topic3,$chk_tip_topic);

        $showAry = $this->showAry;
        $showAry['chk_tip_topic'][1] = $chk_tip_topic_count_amt;
        $showAry['chk_tip_topic'][2] = $chk_tip_topic;
        $this->showAry = $showAry;
        unset($showAry,$tmpPhoto,$chk_tip_topic,$chk_tip_topic_count_amt);
    }

    public function  print_main6(){

        $showAry = $this->showAry;
        $chk_photo_topic = $tmpPhoto = [];
        //承攬商檢點照片
        list($chk_photo_topic_tmp) = wp_work_topic_a::getTopicAns($this->work_id,37,$this->imgResize); //施工人員合照
        $tmpPhoto['title']   = '施工人員合照';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        list($chk_photo_topic_tmp) = wp_work_topic_a::getTopicAns($this->work_id,38,$this->imgResize); //施工前環境拍照
        $tmpPhoto['title']   = '施工前環境拍照';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        list($chk_photo_topic_tmp) = wp_work_topic_a::getTopicAns($this->work_id,150,$this->imgResize); //施工前環境拍照
        $tmpPhoto['title']   = '施工前工安勤前教育紀錄';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        $tmpPhoto['title']   = '承攬商氣體偵測';
        $tmpPhoto['img']     = ($showAry['chk_supply_topic'][107])? '<img src="'.$showAry['chk_supply_topic'][107].'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;


        //轄區拍照
        list($chk_photo_topic_tmp)  = wp_work_topic_a::getTopicAns($this->work_id,55,$this->imgResize); //現場環境拍照（一）
        $tmpPhoto['title']   = '現場環境拍照（一）';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;
        list($chk_photo_topic_tmp)  = wp_work_topic_a::getTopicAns($this->work_id,84,$this->imgResize); //現場環境拍照（二）
        $tmpPhoto['title']   = '現場環境拍照（二）';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;
        $tmpPhoto['title']   = '轄區氣體偵測';
        $tmpPhoto['img']     = ($showAry['chk_emp_topic'][107])? '<img src="'.$showAry['chk_emp_topic'][107].'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        //施工中
        if(count($this->chk_photo_topic2))
        {
            foreach ($this->chk_photo_topic2 as $value)
            {
                $chk_photo_topic[] = $value;
            }
        }


        //收工拍照
        list($chk_photo_topic_tmp)  = wp_work_topic_a::getTopicAns($this->work_id,88,$this->imgResize); //收工環境紀錄（一）
        $tmpPhoto['title']   = '收工環境紀錄（一）';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;
        list($chk_photo_topic_tmp)  = wp_work_topic_a::getTopicAns($this->work_id,89,$this->imgResize); //收工環境紀錄（二）
        $tmpPhoto['title']   = '收工環境紀錄（二）';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        $show_max_photo         = 6;
        $chk_photo_topic_ary    = [];
        $chk_photo_topic_ary[1] = [];
        $chk_photo_cont         = intval(ceil(count($chk_photo_topic)/$show_max_photo));
        if(!$chk_photo_cont) $chk_photo_cont = 1;
        $i = 1;
        $j = 0;
        foreach ($chk_photo_topic as $val)
        {
            $j++;
            $chk_photo_topic_ary[$i][$j] = $val;

            if($j == $show_max_photo)
            {
                $j = 0;
                $i++;
            }
        }
        //補足
        for($i = 1;$i <= $chk_photo_cont; $i++)
        {
            if(!isset($chk_photo_topic_ary[$i])) $chk_photo_topic_ary[$i] = [];
            $photoCont = count($chk_photo_topic_ary[$i]);
            if($photoCont < $show_max_photo)
            {
                for($j = 1; $j <= intval($show_max_photo - $photoCont); $j++)
                {
                    $chk_photo_topic_ary[$i][] = ['img'=>'','title'=>''];
                }
            }
        }

        $showAry['chk_photo_count'] = $chk_photo_cont;
        $showAry['chk_photo']       = $chk_photo_topic_ary;
        $showAry['chk_no']          = [1=>'(一)',2=>'(二)',3=>'(三)',4=>'(四)',5=>'(五)',6=>'(六)',7=>'(七)',8=>'(八)',9=>'(九)',10=>'(十)',];
        $this->showAry = $showAry;
    }
    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl,$imgAt,$maxWidth=80,$maxHeight=25)
    {
        return '<img src="'.$imgUrl.'" class="sign_img" width="'.$maxWidth.'"   height="'.$maxHeight.'"><span class="time_at">'.substr($imgAt,11,5).'</span>';
    }
}
