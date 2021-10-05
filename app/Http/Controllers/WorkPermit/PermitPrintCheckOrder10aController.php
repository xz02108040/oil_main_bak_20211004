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
use App\Model\Supply\b_supply_member_l;
use App\Model\User;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_check;
use App\Model\WorkPermit\wp_work_check_topic;
use App\Model\WorkPermit\wp_work_check_topic_a;
use App\Model\WorkPermit\wp_work_line;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;
use App\Model\WorkPermit\wp_work_workitem;
use App\Model\sys_param;
use Lang;
use Illuminate\Http\Request;
use Config;
use Html;
use DB;
use Storage;
use DNS2D;
use PDF;

class PermitPrintCheckOrder10aController extends Controller
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
        $topics = wp_work_check_topic::where('wp_work_id', $this->work_id)->whereIn('wp_check_id', [13, 15])->select('*')->where('isClose', 'N')->get()->toArray();

        foreach ($topics as $value) {
            $ansArr = wp_work_check_topic_a::getData($value['id']);
            foreach ($ansArr as $key => $value2) {
                $this->showAry['ans'][$key] = $value2;
            }
        }

        $radiation_safety    =  sys_param::getParam('RADIATION_SAFETY','32');
        $radiation_operation =  sys_param::getParam('RADIATION_OPERATION','33');
        $radiation_license   =  sys_param::getParam('RADIATION_LICENSE ','79');

        $rs_cer = '';
        $ro_cer = '';

        $rs = wp_work_worker::select('l.license_code')->join('b_supply_member_l as l','l.b_cust_id','=','wp_work_worker.user_id')->where('wp_work_worker.wp_work_id',$this->work_id)->where('wp_work_worker.engineering_identity_id',$radiation_safety)->where('l.e_license_id',$radiation_license)->first();
        if($rs) $rs_cer = $rs->license_code;
        $ro = wp_work_worker::select('l.license_code')->join('b_supply_member_l as l','l.b_cust_id','=','wp_work_worker.user_id')->where('wp_work_worker.wp_work_id',$this->work_id)->where('wp_work_worker.engineering_identity_id',$radiation_operation)->where('l.e_license_id',$radiation_license)->first();
        if($ro) $ro_cer = $ro->license_code;
        
        // 檢點設備(地點)
        $this->showAry['ans']['work_place'] = SHCSLib::genReportAnsHtml(b_factory_b::getName($this->workData->b_factory_b_id), 28, SHCSLib::ALIGN_LEFT);

        // 檢點日期
        $created_at = wp_work_check_topic_a::where('wp_work_id', $this->work_id)->where('wp_check_id', 13)->select('created_at')->first();
        $create_time = isset($created_at) ? $created_at->created_at->format('Y年m月d日') : '';
        $this->showAry['ans']['create_time'] = SHCSLib::genReportAnsHtml($create_time, 16, SHCSLib::ALIGN_LEFT);

        // 本次輻射作業射源核種稱： [224] check + text
        $this->showAry['ans']['224_chk'] = isset($this->showAry['ans'][224]) && !empty($this->showAry['ans'][224]) ? $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['ans'][224] = isset($this->showAry['ans'][224]) && !empty($this->showAry['ans'][224]) ? $this->showAry['ans'][224] : '';
        $this->showAry['ans'][224] = SHCSLib::genReportAnsHtml($this->showAry['ans'][224], 16);

        // 數量： [225] text
        $this->showAry['ans'][225] = isset($this->showAry['ans'][225]) && !empty($this->showAry['ans'][225]) ? $this->showAry['ans'][225] : '';
        $this->showAry['ans'][225] = SHCSLib::genReportAnsHtml($this->showAry['ans'][225], 16);

        // 確實劃定警戒範圍並於照相時記錄警戒範圍外之偵測μSv/hr(輻射警戒範圍係以射源為中心，其半徑範圍以外輻射劑量率不得超過 20μSv/hr。)： [226] check + text
        $this->showAry['ans']['226_chk'] = isset($this->showAry['ans'][226]) && !empty($this->showAry['ans'][226]) ? $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['ans'][226] = isset($this->showAry['ans'][226]) && !empty($this->showAry['ans'][226]) ? $this->showAry['ans'][226] : '';
        $this->showAry['ans'][226] = SHCSLib::genReportAnsHtml($this->showAry['ans'][226], 12, SHCSLib::ALIGN_LEFT);

        // 輻射警戒範圍必須豎立「輻射警告標示」或圍繩警戒： [227]
        $this->showAry['ans'][227] = isset($this->showAry['ans'][227]) ? $this->checkAry[$this->showAry['ans'][227]] : $this->checkAry['N'];

        // 工作人員必須配戴「輻射劑量配章」或「輻射劑量計」： [228]
        $this->showAry['ans'][228] = isset($this->showAry['ans'][228]) ? $this->checkAry[$this->showAry['ans'][228]] : $this->checkAry['N'];

        // 工作人員確定了解輻射作業安全知識與方法： [229]
        $this->showAry['ans'][229] = isset($this->showAry['ans'][229]) ? $this->checkAry[$this->showAry['ans'][229]] : $this->checkAry['N'];

        // 射源在正常位置。(發生射源脫落，嚴禁用手撿拾、觸摸，若脫落或遺失，請通知承攬商及本廠之輻射防護人員及轄區主管緊急處理。)： [230]
        $this->showAry['ans'][230] = isset($this->showAry['ans'][230]) ? $this->checkAry[$this->showAry['ans'][230]] : $this->checkAry['N'];

        // 現場射線檢測操作人員應負警戒任務，警戒範圍內禁止非工作人員進入： [231]
        $this->showAry['ans'][231] = isset($this->showAry['ans'][231]) ? $this->checkAry[$this->showAry['ans'][231]] : $this->checkAry['N'];

        // 輻射防護人員簽章: [232] img,  輻射防護證書: ['232_cer']
        $this->showAry['ans'][232] = !empty($this->showAry['ans'][232]) ? $this->genImgHtml($this->showAry['ans'][232], '') . SHCSLib::genReportAnsHtml('', 6) : SHCSLib::genReportAnsHtml('', 16);
        $this->showAry['ans']['232_cer'] = str_replace('~', '&nbsp;', str_pad($rs_cer,16,'~',STR_PAD_BOTH)); 
        $this->showAry['ans']['232_chk'] = isset($this->showAry['ans'][232]) && !empty($this->showAry['ans'][232]) ? $this->checkAry['Y'] : $this->checkAry['N'];

        // 輻射操作人員簽章: [233] img, 輻射防護證書: ['233_cer']
        $this->showAry['ans'][233] = !empty($this->showAry['ans'][233]) ? $this->genImgHtml($this->showAry['ans'][233], '') . SHCSLib::genReportAnsHtml('', 6) : SHCSLib::genReportAnsHtml('', 16);
        $this->showAry['ans']['233_cer'] = str_replace('~', '&nbsp;', str_pad($ro_cer,16,'~',STR_PAD_BOTH)); 

        // 每日收工後，必須清點射源數目並偵測工作場所，已確定射源確實收回存放於安全防護罐內： [249]
        $this->showAry['ans'][249] = isset($this->showAry['ans'][249]) ? $this->checkAry[$this->showAry['ans'][249]] : $this->checkAry['N'];
        
        // 源核種名稱： [250] text
        $this->showAry['ans'][250] = isset($this->showAry['ans'][250]) && !empty($this->showAry['ans'][250]) ? $this->showAry['ans'][250] : '';
        $this->showAry['ans'][250] = SHCSLib::genReportAnsHtml($this->showAry['ans'][250], 16);

        // 數量： [251] text
        $this->showAry['ans'][251] = isset($this->showAry['ans'][251]) && !empty($this->showAry['ans'][251]) ? $this->showAry['ans'][251] : '';
        $this->showAry['ans'][251] = SHCSLib::genReportAnsHtml($this->showAry['ans'][251], 16);

        // 承攬商（施工部門）檢點者
        list($supply_sign_url)  = wp_work_topic_a::getTopicAns($this->work_id, 40);
        $this->showAry['ans']['supply_sign_url'] = !empty($supply_sign_url) ? $this->genImgHtml($supply_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);
        
        return view('permit.permit_check10a_v1', $this->showAry);
    }


    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl, $imgAt, $maxWidth = 80, $maxHeight = 25)
    {
        return '<img src="' . $imgUrl . '" class="sign_img" width="' . $maxWidth . '"   height="' . $maxHeight . '"><span class="time_at">' . substr($imgAt, 11, 5) . '</span>';
    }
}
