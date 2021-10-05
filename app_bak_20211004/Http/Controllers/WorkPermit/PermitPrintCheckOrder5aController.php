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
use Lang;
use Illuminate\Http\Request;
use Config;
use DateTime;
use Html;
use DB;
use Storage;
use DNS2D;
use PDF;

class PermitPrintCheckOrder5aController extends Controller
{
    use BcustTrait,SessTraits,WorkPermitTopicOptionTrait,WorkPermitWorkerTrait;
    /**
     * 建構子
     */
    public function __construct()
    {
        $equalImg = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAA7klEQVR42mNkgABeIK4D4mggloCKMQLxfzQ2shguNS+AeCkQNwHxZ0ao4YeBWA+HBlItgLEvArEtiNENxCUMtAE9IAueEQgWcn0ADi4Y4z+NLMDQQHWAywdvgXgKEP8g0hwOIM4BYmFcPkC3YDoQZ5Po2KlAnElsEIFcn0uiBZOhviAqiNYAcRiJFqwC4hBigwgEngLxHyINZwFiGQY8qWj4JVOqBxE6WM1AXiSHEusDUJomJ5lmo5kzcBntDQN5RYUIsUGEi012MqWpBaAKR5LE8CYWPIdVmcU08kEvXSp9BgbUZgulwfWcAanZAgCnBI76sx2a+AAAAABJRU5ErkJggg==" width="10pt" width="10pt">';

        $this->checkAry     = ['Y'=>'■','N'=>'□',''=>'□','='=>$equalImg];
        $this->checkAry2     = ['Y'=>'✓','N'=>'=',''=>'='];
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

        // 部門
        $this->showAry['ans']['charge_dept'] = SHCSLib::genReportAnsHtml(be_dept::getName($this->workData->be_dept_id2), 12, SHCSLib::ALIGN_LEFT);

        // 檢點時間
        $created_at = wp_work_check_topic_a::where('wp_work_id', $this->work_id)->where('wp_check_id', 9)->select('created_at')->first();
        $create_time = isset($created_at) ? $created_at->created_at->format('Y年m月d日') : '';
        $this->showAry['ans']['create_time'] = SHCSLib::genReportAnsHtml($create_time, 16, SHCSLib::ALIGN_LEFT);

        // 有效時間
        $this->showAry['ans']["work_stime"] = "&nbsp;&nbsp;時&nbsp;&nbsp;分";
        list($work_stime)    = wp_work_topic_a::getTopicAns($this->work_id, 120);
        if (!empty($work_stime)) {
            $work_stime = new DateTime($work_stime);
            $this->showAry['ans']["work_stime"] = $work_stime->format('H時i分');
        }

        // 結束作業時間
        $this->showAry['ans']["work_etime"] = "&nbsp;&nbsp;時&nbsp;&nbsp;分";
        list($work_etime)    = wp_work_topic_a::getTopicAns($this->work_id, 121);
        if (!empty($work_etime)) {
            $work_etime = new DateTime($work_etime);
            $this->showAry['ans']["work_etime"] = $work_etime->format('H時i分');
        }

        // 作業勞工戴用絕緣用防護具，並於有接觸或接近該電路部分設置絕緣用防護裝備： [168]
        // 使作業勞工使用活線作業用裝置，並不得使勞工之身體或其使用中之金屬工具、材料等導電體接觸或接近於有使勞工感電之虞之電路或帶電體： [169]
        // 操作人員的臉部不可位於開關設備的下方：[170]
        // 勞工於作業中或通行時，有因接觸或接近致發生感電之虞者，應設防止感電之護圍或絕緣被覆： [171]
        // 不可以濕手或濕操作棒操作開關設備： [172]
        // 操作時， 應有監督人員在旁負責指揮督導： [173]
        // 使用昇降車輛操作時，操作人員的頭部距離開關設備至少要有1.5公尺的距離： [174]
        // 嚴禁兩人同時進行不同相別的活線作業 ： [175]
        // 作業勞工使用活線作業用器具： [176]
        // 目視： [177]
        // 儀器測定： [178]
        $this->showAry['ans'][168] = isset($this->showAry['ans'][168]) ? $this->checkAry2[$this->showAry['ans'][168]] : $this->checkAry2['N'];
        $this->showAry['ans'][169] = isset($this->showAry['ans'][169]) ? $this->checkAry2[$this->showAry['ans'][169]] : $this->checkAry2['N'];
        $this->showAry['ans'][170] = isset($this->showAry['ans'][170]) ? $this->checkAry2[$this->showAry['ans'][170]] : $this->checkAry2['N'];
        $this->showAry['ans'][171] = isset($this->showAry['ans'][171]) ? $this->checkAry2[$this->showAry['ans'][171]] : $this->checkAry2['N'];
        $this->showAry['ans'][172] = isset($this->showAry['ans'][172]) ? $this->checkAry2[$this->showAry['ans'][172]] : $this->checkAry2['N'];
        $this->showAry['ans'][173] = isset($this->showAry['ans'][173]) ? $this->checkAry2[$this->showAry['ans'][173]] : $this->checkAry2['N'];
        $this->showAry['ans'][174] = isset($this->showAry['ans'][174]) ? $this->checkAry2[$this->showAry['ans'][174]] : $this->checkAry2['N'];
        $this->showAry['ans'][175] = isset($this->showAry['ans'][175]) ? $this->checkAry2[$this->showAry['ans'][175]] : $this->checkAry2['N'];
        $this->showAry['ans'][176] = isset($this->showAry['ans'][176]) ? $this->checkAry2[$this->showAry['ans'][176]] : $this->checkAry2['N'];
        $this->showAry['ans'][177] = isset($this->showAry['ans'][177]) ? $this->checkAry[$this->showAry['ans'][177]] : $this->checkAry['N'];
        $this->showAry['ans'][178] = isset($this->showAry['ans'][178]) ? $this->checkAry[$this->showAry['ans'][178]] : $this->checkAry['N'];

        // 設備名稱（編號）
        $device_number = isset($this->showAry['ans'][254]) ? $this->showAry['ans'][254] : '';
        $this->showAry['ans'][254] = SHCSLib::genReportAnsHtml($device_number, 16, SHCSLib::ALIGN_LEFT);

        // 轄區人員簽章
        list($dept_sign_url)   = wp_work_topic_a::getTopicAns($this->work_id, 57);
        $this->showAry['ans']['dept_sign_url'] = !empty($dept_sign_url) ? $this->genImgHtml($dept_sign_url, '') . SHCSLib::genReportAnsHtml('', 6) : SHCSLib::genReportAnsHtml('', 16);
        // 施工檢點者簽章
        list($supply_sign_url)  = wp_work_topic_a::getTopicAns($this->work_id, 40);
        $this->showAry['ans']['supply_sign_url'] = !empty($supply_sign_url) ? $this->genImgHtml($supply_sign_url, '') . SHCSLib::genReportAnsHtml('', 6) : SHCSLib::genReportAnsHtml('', 16);

        return view('permit.permit_check5a_v1',$this->showAry);
    }


    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl,$imgAt,$maxWidth=80,$maxHeight=25)
    {
        return '<img src="'.$imgUrl.'" class="sign_img" width="'.$maxWidth.'"   height="'.$maxHeight.'"><span class="time_at">'.substr($imgAt,11,5).'</span>';
    }
}
