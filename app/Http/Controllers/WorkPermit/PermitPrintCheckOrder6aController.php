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

class PermitPrintCheckOrder6aController extends Controller
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
        $created_at = wp_work_check_topic_a::where('wp_work_id', $this->work_id)->where('wp_check_id', 10)->select('created_at')->first();
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
        list($work_etime)    = wp_work_topic_a::getTopicAns($this->work_id,121);
        if (!empty($work_etime)) {
            $work_etime = new DateTime($work_etime);
            $this->showAry['ans']["work_etime"] = $work_etime->format('H時i分');
        }

        // 目視： [179]
        // 儀器測定： [180]
        // 對於施工中可能之飛散、落物體是否設置防護措施： [181]
        // 作業人員之安全帽及安全帶應監督確實使用： [182]
        // 作業人員應穿著安全鞋或必要時穿防滑性佳之膠鞋： [183]
        // 工作台、走道、階梯等不可有堆積物料阻礙通行及作業： [184]
        // 不可於施工架上使用梯子、合梯或踏凳等從事作業： [185]
        // 施工架應設安全母索並使確實戴用雙掛鉤背負式安全帶： [186]
        // 上下二架間高度≧1.5M應設置供勞工安全上下之階梯： [187]
        // 吊升或卸放材料、器具、工具等應使用吊索、吊帶等： [188]
        // 有鄰近或跨越工作走道部份應設置斜籬或安全網： [189]
        // 應設置警示區嚴禁無關人員進入組、拆作業區域： [190]
        // 檢查架材、主柱、橫檔踏腳桁、斜撐材之按裝、鬆弛狀況： [191]
        // 檢查基腳之下沈、滑動及斜撐材、索條、橫擋等補強材之狀況： [192]
        // 不可因外牆施工有不當切除繫壁杆之情形： [193]
        // 檢查固定材料與固定金屬配件之損傷及腐蝕狀況： [194]
        $this->showAry['ans'][179] = isset($this->showAry['ans'][179]) ? $this->checkAry[$this->showAry['ans'][179]] : $this->checkAry['N'];
        $this->showAry['ans'][180] = isset($this->showAry['ans'][180]) ? $this->checkAry[$this->showAry['ans'][180]] : $this->checkAry['N'];
        $this->showAry['ans'][181] = isset($this->showAry['ans'][181]) ? $this->checkAry2[$this->showAry['ans'][181]] : $this->checkAry2['N'];
        $this->showAry['ans'][182] = isset($this->showAry['ans'][182]) ? $this->checkAry2[$this->showAry['ans'][182]] : $this->checkAry2['N'];
        $this->showAry['ans'][183] = isset($this->showAry['ans'][183]) ? $this->checkAry2[$this->showAry['ans'][183]] : $this->checkAry2['N'];
        $this->showAry['ans'][184] = isset($this->showAry['ans'][184]) ? $this->checkAry2[$this->showAry['ans'][184]] : $this->checkAry2['N'];
        $this->showAry['ans'][185] = isset($this->showAry['ans'][185]) ? $this->checkAry2[$this->showAry['ans'][185]] : $this->checkAry2['N'];
        $this->showAry['ans'][186] = isset($this->showAry['ans'][186]) ? $this->checkAry2[$this->showAry['ans'][186]] : $this->checkAry2['N'];
        $this->showAry['ans'][187] = isset($this->showAry['ans'][187]) ? $this->checkAry2[$this->showAry['ans'][187]] : $this->checkAry2['N'];
        $this->showAry['ans'][188] = isset($this->showAry['ans'][188]) ? $this->checkAry2[$this->showAry['ans'][188]] : $this->checkAry2['N'];
        $this->showAry['ans'][189] = isset($this->showAry['ans'][189]) ? $this->checkAry2[$this->showAry['ans'][189]] : $this->checkAry2['N'];
        $this->showAry['ans'][190] = isset($this->showAry['ans'][190]) ? $this->checkAry2[$this->showAry['ans'][190]] : $this->checkAry2['N'];
        $this->showAry['ans'][191] = isset($this->showAry['ans'][191]) ? $this->checkAry2[$this->showAry['ans'][191]] : $this->checkAry2['N'];
        $this->showAry['ans'][192] = isset($this->showAry['ans'][192]) ? $this->checkAry2[$this->showAry['ans'][192]] : $this->checkAry2['N'];
        $this->showAry['ans'][193] = isset($this->showAry['ans'][193]) ? $this->checkAry2[$this->showAry['ans'][193]] : $this->checkAry2['N'];
        $this->showAry['ans'][194] = isset($this->showAry['ans'][194]) ? $this->checkAry2[$this->showAry['ans'][194]] : $this->checkAry2['N'];

        // 設備名稱（編號）
        $device_number = isset($this->showAry['ans'][253]) ? $this->showAry['ans'][253] : '';
        $this->showAry['ans'][253] = SHCSLib::genReportAnsHtml($device_number, 16, SHCSLib::ALIGN_LEFT);

        // 轄區人員簽章
        list($dept_sign_url)   = wp_work_topic_a::getTopicAns($this->work_id, 57);
        $this->showAry['ans']['dept_sign_url'] = !empty($dept_sign_url) ? $this->genImgHtml($dept_sign_url, '') . SHCSLib::genReportAnsHtml('', 6) : SHCSLib::genReportAnsHtml('', 16);
        // 施工檢點者簽章
        list($supply_sign_url)  = wp_work_topic_a::getTopicAns($this->work_id, 40);
        $this->showAry['ans']['supply_sign_url'] = !empty($supply_sign_url) ? $this->genImgHtml($supply_sign_url, '') . SHCSLib::genReportAnsHtml('', 6) : SHCSLib::genReportAnsHtml('', 16);
        

        return view('permit.permit_check6a_v1', $this->showAry);
    }


    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl,$imgAt,$maxWidth=80,$maxHeight=25)
    {
        return '<img src="'.$imgUrl.'" class="sign_img" width="'.$maxWidth.'"   height="'.$maxHeight.'"><span class="time_at">'.substr($imgAt,11,5).'</span>';
    }
}
