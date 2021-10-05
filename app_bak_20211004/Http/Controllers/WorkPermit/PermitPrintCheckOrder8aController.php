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
use App\Model\WorkPermit\wp_work_line;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;
use App\Model\WorkPermit\wp_work_workitem;
use Lang;
use Illuminate\Http\Request;
use Config;
use Html;
use DB;
use Storage;
use DNS2D;
use PDF;

class PermitPrintCheckOrder8aController extends Controller
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

        // 取得工作許可證_工作人員姓名
        $workerNames = $this->getApiWorkPermitWorkerNames($this->work_id);

        // 檢點設備(地點)
        $this->showAry['ans']['work_place'] = SHCSLib::genReportAnsHtml(b_factory_b::getName($this->workData->b_factory_b_id), 28, SHCSLib::ALIGN_LEFT);

        // 檢點日期
        $created_at = wp_work_check_topic_a::where('wp_work_id', $this->work_id)->where('wp_check_id', 12)->select('created_at')->first();
        $create_time = isset($created_at) ? $created_at->created_at->format('Y年m月d日') : '';
        $this->showAry['ans']['create_time'] = SHCSLib::genReportAnsHtml($create_time, 16, SHCSLib::ALIGN_LEFT);

        // 1.高度五公尺以上施工架之構築，已由專任工程人員或指定專人事先以預期施工時之最大荷重，依結構力學原理妥為設計： [205]
        $this->showAry['ans'][205] = isset($this->showAry['ans'][205]) ? $this->checkAry[$this->showAry['ans'][205]] : $this->checkAry['N'];

        // 2.已設置施工架組配作業主管： [206] check + text。 新:['worker'][6]
        $this->showAry['worker']['6_chk'] = isset($workerNames[6]) && !empty($workerNames[6]) ?  $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['worker'][6] = isset($workerNames[6]) && !empty($workerNames[6]) ? SHCSLib::genReportAnsHtml($workerNames[6], 16, SHCSLib::ALIGN_LEFT) : '';
       
        // 3.作業勞工已接受本公司施工架三合一訓練並取得合格證書： [207]
        $this->showAry['ans'][207] = isset($this->showAry['ans'][207]) ? $this->checkAry[$this->showAry['ans'][207]] : $this->checkAry['N'];
        
        // 4.已將作業時間、範圍及順序等告知作業勞工： [208]
        $this->showAry['ans'][208] = isset($this->showAry['ans'][208]) ? $this->checkAry[$this->showAry['ans'][208]] : $this->checkAry['N'];
        
        // 5.天候對實施作業無危險之虞： [209]
        $this->showAry['ans'][209] = isset($this->showAry['ans'][209]) ? $this->checkAry[$this->showAry['ans'][209]] : $this->checkAry['N'];
        
        // 6.已做好禁止作業無關人員擅自進入組配作業區域內之措施： [210] check + text
        $this->showAry['ans']['210_chk'] = isset($this->showAry['ans'][210]) && !empty($this->showAry['ans'][210]) ? $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['ans'][210] = isset($this->showAry['ans'][210]) && !empty($this->showAry['ans'][210]) ? SHCSLib::genReportAnsHtml($this->showAry['ans'][210], 16, SHCSLib::ALIGN_LEFT) : '';

        // 7.吊升或卸放材料、器具、工具等時，使用 [211] display only
        $this->showAry['ans'][211] = (
            (isset($this->showAry['ans'][212]) && $this->showAry['ans'][212] == 'Y') ||
            (isset($this->showAry['ans'][213]) && $this->showAry['ans'][213] == 'Y') ||
            (isset($this->showAry['ans'][214]) && $this->showAry['ans'][214] == 'Y') ||
            (isset($this->showAry['ans'][215]) && !empty($this->showAry['ans'][162]))) ?
            $this->checkAry['Y'] : $this->checkAry['N'];

        //  吊車： [212]
        $this->showAry['ans'][212] = isset($this->showAry['ans'][212]) ? $this->checkAry[$this->showAry['ans'][212]] : $this->checkAry['N'];
        
        //  吊索： [213]
        $this->showAry['ans'][213] = isset($this->showAry['ans'][213]) ? $this->checkAry[$this->showAry['ans'][213]] : $this->checkAry['N'];
       
        //  吊物專用袋： [214]
        $this->showAry['ans'][214] = isset($this->showAry['ans'][214]) ? $this->checkAry[$this->showAry['ans'][214]] : $this->checkAry['N'];
        
        //  其他 [215] check + text
        $this->showAry['ans']['215_chk'] = isset($this->showAry['ans'][215]) && !empty($this->showAry['ans'][215]) ? $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['ans'][215] = isset($this->showAry['ans'][215]) && !empty($this->showAry['ans'][215]) ? SHCSLib::genReportAnsHtml($this->showAry['ans'][215], 16, SHCSLib::ALIGN_LEFT) : '';

        // 8.已實施檢點，檢查材料、工具、器具等，並汰換其不良品。： [216]
        $this->showAry['ans'][216] = isset($this->showAry['ans'][216]) ? $this->checkAry[$this->showAry['ans'][216]] : $this->checkAry['N'];
       
        // 9.已提供安全帶給勞工個人使用： [217]
        $this->showAry['ans'][217] = isset($this->showAry['ans'][217]) ? $this->checkAry[$this->showAry['ans'][217]] : $this->checkAry['N'];
       
        // 10.構築施工架之材料符合下列要求： [218] display only
        $this->showAry['ans'][218] = (
            (isset($this->showAry['ans'][219]) && $this->showAry['ans'][219] == 'Y') ||
            (isset($this->showAry['ans'][220]) && $this->showAry['ans'][220] == 'Y') ||
            (isset($this->showAry['ans'][221]) && $this->showAry['ans'][221] == 'Y') ||
            (isset($this->showAry['ans'][222]) && $this->showAry['ans'][222] == 'Y') ||
            (isset($this->showAry['ans'][223]) && $this->showAry['ans'][223] == 'Y')) ?
            $this->checkAry['Y'] : $this->checkAry['N'];

        //  1.無顯著之損壞、變形或腐蝕： [219]
        $this->showAry['ans'][219] = isset($this->showAry['ans'][219]) ? $this->checkAry[$this->showAry['ans'][219]] : $this->checkAry['N'];

        //  2.使用之孟宗竹，應以竹尾末梢外徑四公分以上之圓竹為限，且無裂隙或腐蝕者，必要時應加防腐處理： [220]
        $this->showAry['ans'][220] = isset($this->showAry['ans'][220]) ? $this->checkAry[$this->showAry['ans'][220]] : $this->checkAry['N'];

        //  3.使用之木材，無顯著損及強度之裂隙、蛀孔、木結、斜紋等，並應完全剝除樹皮，方得使用： [221]
        $this->showAry['ans'][221] = isset($this->showAry['ans'][221]) ? $this->checkAry[$this->showAry['ans'][221]] : $this->checkAry['N'];

        //  4.使用之木材，無施以油漆或其他處理以隱蔽其缺陷： [222]
        $this->showAry['ans'][222] = isset($this->showAry['ans'][222]) ? $this->checkAry[$this->showAry['ans'][222]] : $this->checkAry['N'];

        //  5.使用之鋼材等金屬材料，應符合國家標準；由國外進口者，應檢附相關材料規範，報請中央主管機關核備： [223]
        $this->showAry['ans'][223] = isset($this->showAry['ans'][223]) ? $this->checkAry[$this->showAry['ans'][223]] : $this->checkAry['N'];

        // 承攬商（施工部門）檢點者
        list($supply_sign_url)  = wp_work_topic_a::getTopicAns($this->work_id, 40);
        $this->showAry['ans']['supply_sign_url'] = !empty($supply_sign_url) ? $this->genImgHtml($supply_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        return view('permit.permit_check8a_v1', $this->showAry);
    }


    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl,$imgAt,$maxWidth=80,$maxHeight=25)
    {
        return '<img src="'.$imgUrl.'" class="sign_img" width="'.$maxWidth.'"   height="'.$maxHeight.'"><span class="time_at">'.substr($imgAt,11,5).'</span>';
    }
}
