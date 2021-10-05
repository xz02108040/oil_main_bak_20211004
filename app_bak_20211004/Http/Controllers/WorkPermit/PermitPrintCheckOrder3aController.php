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
use App\Model\WorkPermit\wp_work_check_topic_a;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;
use App\Model\WorkPermit\wp_work_workitem;
use DateTime;
use Illuminate\Http\Request;
use Config;
use Html;
use DB;
use Storage;
use DNS2D;
use PDF;

class PermitPrintCheckOrder3aController extends Controller
{
    use BcustTrait, SessTraits, WorkPermitTopicOptionTrait, WorkPermitWorkerTrait;
    /**
     * 建構子
     */
    public function __construct()
    {
        $equalImg = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAA7klEQVR42mNkgABeIK4D4mggloCKMQLxfzQ2shguNS+AeCkQNwHxZ0ao4YeBWA+HBlItgLEvArEtiNENxCUMtAE9IAueEQgWcn0ADi4Y4z+NLMDQQHWAywdvgXgKEP8g0hwOIM4BYmFcPkC3YDoQZ5Po2KlAnElsEIFcn0uiBZOhviAqiNYAcRiJFqwC4hBigwgEngLxHyINZwFiGQY8qWj4JVOqBxE6WM1AXiSHEusDUJomJ5lmo5kzcBntDQN5RYUIsUGEi012MqWpBaAKR5LE8CYWPIdVmcU08kEvXSp9BgbUZgulwfWcAanZAgCnBI76sx2a+AAAAABJRU5ErkJggg==" width="10pt" width="10pt">';

        $this->checkAry     = ['Y' => '■', 'N' => '□', '' => '□', '=' => $equalImg];
        $this->imgResize    = 500;
        $this->work_id      = 0;
        $this->permit_danger = 'C';
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
        $this->work_id = SHCSLib::decode($request->id);
        $this->workData = wp_work::getData($this->work_id);

        $this->showAry['ans'] = array();
        $topics = wp_work_check_topic::where('wp_work_id', $this->work_id)->select('*')->where('isClose', 'N')->get()->toArray();

        foreach ($topics as $value) {
            $ansArr = wp_work_check_topic_a::getData($value['id']);
            foreach ($ansArr as $key => $value2) {
                $this->showAry['ans'][$key] = $value2;
            }
        }

        // 檢點設備(地點)
        $this->showAry['ans']['work_place'] = SHCSLib::genReportAnsHtml(b_factory_b::getName($this->workData->b_factory_b_id), 28, SHCSLib::ALIGN_LEFT);

        // 日期
        $sdateTime = new DateTime($this->workData->sdate);
        $this->showAry['ans']['work_date'] = SHCSLib::genReportAnsHtml($sdateTime->format('Y年n月j日'), 16, SHCSLib::ALIGN_LEFT);

        // 1. 關斷動力:
        $this->showAry['ans']['section1_chk'] = $this->checkAry['N'];
        if (
            isset($this->showAry['ans'][131]) && $this->showAry['ans'][131] == 'Y' &&
            ((isset($this->showAry['ans'][133]) && $this->showAry['ans'][133] == 'Y') || (isset($this->showAry['ans'][134]) && $this->showAry['ans'][134] == 'Y')) &&
            isset($this->showAry['ans'][135]) && $this->showAry['ans'][135] == 'Y' &&
            isset($this->showAry['ans'][136]) && $this->showAry['ans'][136] == 'Y' &&
            isset($this->showAry['ans'][137]) && $this->showAry['ans'][137] == 'Y'
        ) {
            $this->showAry['ans']['section1_chk'] = $this->checkAry['Y'];
        }

        // MCC配電盤電源開關已關斷、上鎖及加掛簽妥姓名與日期時間之「停電作業中，禁止送電」掛籤。 [131]
        $this->showAry['ans'][131] = isset($this->showAry['ans'][131]) ? $this->checkAry[$this->showAry['ans'][131]] : $this->checkAry['N'];
        // MCC配電盤電源開關無法上鎖，但已加掛簽妥姓名與日期時間之「停電作業中，禁止送電」掛籤，並已在現場之：[132]
        $this->showAry['ans'][132] = ((isset($this->showAry['ans'][133]) && $this->showAry['ans'][133] == 'Y') || (isset($this->showAry['ans'][134]) && $this->showAry['ans'][134] == 'Y')) ? $this->checkAry['Y'] : $this->checkAry['N'];
        // 按鈕開關插梢處加鎖或 [133]
        $this->showAry['ans'][133] = isset($this->showAry['ans'][133]) ? $this->checkAry[$this->showAry['ans'][133]] : $this->checkAry['N'];
        // 按鈕外蓋上加鎖。 [134]
        $this->showAry['ans'][134] = isset($this->showAry['ans'][134]) ? $this->checkAry[$this->showAry['ans'][134]] : $this->checkAry['N'];
        // 高壓馬達開關盤已予關斷隔離，並加掛簽妥姓名與日期時間之「停電作業中，禁止送電」掛籤。 [135]
        $this->showAry['ans'][135] = isset($this->showAry['ans'][135]) ? $this->checkAry[$this->showAry['ans'][135]] : $this->checkAry['N'];
        // 透平蒸汽進口閥已關斷，並加掛簽妥姓名與日期時間之「檢修中，禁止操作」掛籤。 [136]
        $this->showAry['ans'][136] = isset($this->showAry['ans'][136]) ? $this->checkAry[$this->showAry['ans'][136]] : $this->checkAry['N'];
        // 壓縮空氣進口閥已關斷，並加掛簽妥姓名與日期時間之「檢修中，禁止操作」掛籤。 [137]
        $this->showAry['ans'][137] = isset($this->showAry['ans'][137]) ? $this->checkAry[$this->showAry['ans'][137]] : $this->checkAry['N'];

        // 2. 連通設備之管線:
        $this->showAry['ans']['section2_chk'] = $this->checkAry['N'];
        if (
            isset($this->showAry['ans'][138]) && $this->showAry['ans'][138] == 'Y' &&
            isset($this->showAry['ans'][139]) && $this->showAry['ans'][139] == 'Y' &&
            isset($this->showAry['ans'][140]) && $this->showAry['ans'][140] == 'Y' &&
            isset($this->showAry['ans'][141]) && $this->showAry['ans'][141] == 'Y' &&
            isset($this->showAry['ans'][142]) && $this->showAry['ans'][142] == 'Y'
        ) {
            $this->showAry['ans']['section2_chk'] = $this->checkAry['Y'];
        }
        // 連通進、出口閥已關斷。 [138]
        $this->showAry['ans'][138] = isset($this->showAry['ans'][138]) ? $this->checkAry[$this->showAry['ans'][138]] : $this->checkAry['N'];
        // 連通進、出口閥已關斷，加裝盲板並掛牌。 [139]
        $this->showAry['ans'][139] = isset($this->showAry['ans'][139]) ? $this->checkAry[$this->showAry['ans'][139]] : $this->checkAry['N'];
        // 設備內之物質已排淨（放空）。 [140]
        $this->showAry['ans'][140] = isset($this->showAry['ans'][140]) ? $this->checkAry[$this->showAry['ans'][140]] : $this->checkAry['N'];
        // 已釋壓，內部已無壓力（壓力與大氣壓力一致）。 [141]
        $this->showAry['ans'][141] = isset($this->showAry['ans'][141]) ? $this->checkAry[$this->showAry['ans'][141]] : $this->checkAry['N'];
        // 已降溫，溫度已降至40℃以下。 [142]
        $this->showAry['ans'][142] = isset($this->showAry['ans'][142]) ? $this->checkAry[$this->showAry['ans'][142]] : $this->checkAry['N'];

        // 3. 拆裝盲板作業：
        $this->showAry['ans']['section3_chk'] = $this->checkAry['N'];
        if (
            isset($this->showAry['ans'][143]) && $this->showAry['ans'][143] == 'Y' &&
            isset($this->showAry['ans'][144]) && $this->showAry['ans'][144] == 'Y' &&
            isset($this->showAry['ans'][145]) && $this->showAry['ans'][145] == 'Y' &&
            isset($this->showAry['ans'][146]) && $this->showAry['ans'][146] == 'Y' &&
            !empty($this->showAry['ans'][147])
        ) {
            $this->showAry['ans']['section3_chk'] = $this->checkAry['Y'];
        }
        // 已確認連通之系統已無壓力。 [143]
        $this->showAry['ans'][143] = isset($this->showAry['ans'][143]) ? $this->checkAry[$this->showAry['ans'][143]] : $this->checkAry['N'];
        // 已關閉進出口管閥。 [144]
        $this->showAry['ans'][144] = isset($this->showAry['ans'][144]) ? $this->checkAry[$this->showAry['ans'][144]] : $this->checkAry['N'];
        // 已排放、釋壓、吹除(purge)內容物。 [145]
        $this->showAry['ans'][145] = isset($this->showAry['ans'][145]) ? $this->checkAry[$this->showAry['ans'][145]] : $this->checkAry['N'];
        // 已量測Drain或Vent出口端物質濃度，量測紀錄已填寫工作許可證上。 [146]
        $this->showAry['ans'][146] = isset($this->showAry['ans'][146]) ? $this->checkAry[$this->showAry['ans'][146]] : $this->checkAry['N'];
        // 工作人員有配戴適當之呼吸防護具 [147]
        $this->showAry['ans']['147_chk'] = isset($this->showAry['ans'][147]) && !empty($this->showAry['ans'][147]) ? $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['ans'][147] = isset($this->showAry['ans'][147]) ? SHCSLib::genReportAnsHtml($this->showAry['ans'][147], 16, SHCSLib::ALIGN_LEFT) : '';

        // 轄區人員 (轄區：檢點者)
        list($dept_sign_url)   = wp_work_topic_a::getTopicAns($this->work_id, 57);
        $this->showAry['ans']['dept_sign_url'] = !empty($dept_sign_url) ? $this->genImgHtml($dept_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        // 監造負責人 (承攬商：職安衛人員)
        list($supply_sign_url)  = wp_work_topic_a::getTopicAns($this->work_id, 40);
        $this->showAry['ans']['supply_sign_url'] = !empty($supply_sign_url) ? $this->genImgHtml($supply_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        // 承攬商
        $this->showAry['ans']['supply'] = SHCSLib::genReportAnsHtml(b_supply::getName($this->workData->b_supply_id), 12, SHCSLib::ALIGN_LEFT);

        return view('permit.permit_check3a_v1', $this->showAry);
    }


    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl, $imgAt, $maxWidth = 80, $maxHeight = 25)
    {
        return '<img src="' . $imgUrl . '" class="sign_img" width="' . $maxWidth . '"   height="' . $maxHeight . '"><span class="time_at">' . substr($imgAt, 11, 5) . '</span>';
    }
}
