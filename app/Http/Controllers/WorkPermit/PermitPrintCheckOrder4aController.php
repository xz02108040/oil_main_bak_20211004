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
use App\Model\Supply\b_supply_engineering_identity;
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

class PermitPrintCheckOrder4aController extends Controller
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

        // 日期
        $sdateTime = new DateTime($this->workData->sdate);
        $this->showAry['ans']['work_date'] = SHCSLib::genReportAnsHtml($sdateTime->format('Y年n月j日'), 16, SHCSLib::ALIGN_LEFT);

        // 一、作業人員
        // 1.起重機操作人員：            ，已接受訓練或技能檢定取得資格。 [148] 新:['worker'][4]
        $this->showAry['worker']['4_chk'] = isset($workerNames[4]) && !empty($workerNames[4]) ?  $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['worker'][4] = isset($workerNames[4]) && !empty($workerNames[4]) ? SHCSLib::genReportAnsHtml($workerNames[4], 16, SHCSLib::ALIGN_LEFT) : '';
        // 2.已規定一定之運轉指揮信號，並指派專人負責辦理。 [149]
        $this->showAry['ans'][149] = isset($this->showAry['ans'][149]) ? $this->checkAry[$this->showAry['ans'][149]] : $this->checkAry['N'];
        // 3.已設置使用起重機具從事吊掛作業人員：            。 [150]。 新:['worker'][5]
        $this->showAry['worker']['5_chk'] = isset($workerNames[5]) && !empty($workerNames[5]) ?  $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['worker'][5] = isset($workerNames[5]) && !empty($workerNames[5]) ? SHCSLib::genReportAnsHtml($workerNames[5], 16, SHCSLib::ALIGN_LEFT) : '';
       // 二、起重機具
        // 1.起重機具，有標示最高負荷，本次使用未超過此項限制。 [151]
        // 2.起重機具之吊鉤或吊具，有防止吊舉中所吊物體脫落之裝置。 [152]
        // 3.起重機具之吊鉤或吊具，有過捲預防裝置。 [153]
        $this->showAry['ans'][151] = isset($this->showAry['ans'][151]) ? $this->checkAry[$this->showAry['ans'][151]] : $this->checkAry['N'];
        $this->showAry['ans'][152] = isset($this->showAry['ans'][152]) ? $this->checkAry[$this->showAry['ans'][152]] : $this->checkAry['N'];
        $this->showAry['ans'][153] = isset($this->showAry['ans'][153]) ? $this->checkAry[$this->showAry['ans'][153]] : $this->checkAry['N'];

        // 三、吊掛用具
        // 1.使用吊鏈作為起重機具之吊掛用具： [154]
        $this->showAry['ans'][154] = ((isset($this->showAry['ans'][155]) && $this->showAry['ans'][155] == 'Y') ||
            (isset($this->showAry['ans'][156]) && $this->showAry['ans'][156] == 'Y') ||
            (isset($this->showAry['ans'][157]) && $this->showAry['ans'][157] == 'Y')) ?
            $this->checkAry['Y'] : $this->checkAry['N'];

        //  1.延伸長度未超過百分之五以上者。 [155]
        //  2.斷面直徑未減少百分之十以上者。 [156]
        //  3.未有龜裂者。 [157]
        $this->showAry['ans'][155] = isset($this->showAry['ans'][155]) ? $this->checkAry[$this->showAry['ans'][155]] : $this->checkAry['N'];
        $this->showAry['ans'][156] = isset($this->showAry['ans'][156]) ? $this->checkAry[$this->showAry['ans'][156]] : $this->checkAry['N'];
        $this->showAry['ans'][157] = isset($this->showAry['ans'][157]) ? $this->checkAry[$this->showAry['ans'][157]] : $this->checkAry['N'];

        // 2.使用吊掛之鋼索作為起重機具之吊掛用具： [158]
        $this->showAry['ans'][158] = ((isset($this->showAry['ans'][159]) && $this->showAry['ans'][159] == 'Y') ||
        (isset($this->showAry['ans'][160]) && $this->showAry['ans'][160] == 'Y') ||
        (isset($this->showAry['ans'][161]) && $this->showAry['ans'][161] == 'Y') ||
        (isset($this->showAry['ans'][162]) && $this->showAry['ans'][162] == 'Y')) ?
        $this->checkAry['Y'] : $this->checkAry['N'];

        //  1.鋼索一撚間未有百分之十以上素線截斷者。 [159]
        //  2.直徑減少未達公稱直徑百分之七以上者。 [160]
        //  3.未有顯著變形或腐蝕者。 [161]
        //  4.未扭結者。 [162]
        $this->showAry['ans'][159] = isset($this->showAry['ans'][159]) ? $this->checkAry[$this->showAry['ans'][159]] : $this->checkAry['N'];
        $this->showAry['ans'][160] = isset($this->showAry['ans'][160]) ? $this->checkAry[$this->showAry['ans'][160]] : $this->checkAry['N'];
        $this->showAry['ans'][161] = isset($this->showAry['ans'][161]) ? $this->checkAry[$this->showAry['ans'][161]] : $this->checkAry['N'];
        $this->showAry['ans'][162] = isset($this->showAry['ans'][162]) ? $this->checkAry[$this->showAry['ans'][162]] : $this->checkAry['N'];

        // 3.作為起重機具吊掛用具之吊鉤、鉤環、鏈環，無變形或龜裂。 [163]
        $this->showAry['ans'][163] = isset($this->showAry['ans'][163]) ? $this->checkAry[$this->showAry['ans'][163]] : $this->checkAry['N'];

        // 4.使用纖維索、帶，作為起重機具之吊掛用具： [164]
        $this->showAry['ans'][164] = ((isset($this->showAry['ans'][165]) && $this->showAry['ans'][165] == 'Y') ||
        (isset($this->showAry['ans'][166]) && $this->showAry['ans'][166] == 'Y')) ?
        $this->checkAry['Y'] : $this->checkAry['N'];

        //  1.未斷一股子索者。 [165]
        //  2.未有顯著之損傷或腐蝕者。 [166]
        $this->showAry['ans'][165] = isset($this->showAry['ans'][165]) ? $this->checkAry[$this->showAry['ans'][165]] : $this->checkAry['N'];
        $this->showAry['ans'][166] = isset($this->showAry['ans'][166]) ? $this->checkAry[$this->showAry['ans'][166]] : $this->checkAry['N'];

        // 四、其它
        // 1.起重機具運轉時，已採取防止吊掛物通過人員上方及人員進入吊掛物下方之設備或措施：[167]
        $this->showAry['ans']['167_chk'] = isset($this->showAry['ans'][167]) && !empty($this->showAry['ans'][167]) ? $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['ans'][167] = isset($this->showAry['ans'][167]) ? SHCSLib::genReportAnsHtml($this->showAry['ans'][167], 40, SHCSLib::ALIGN_LEFT) : '';

        // 承攬商（施工部門）檢點者
        list($supply_sign_url)  = wp_work_topic_a::getTopicAns($this->work_id, 40);
        $this->showAry['ans']['supply_sign_url'] = !empty($supply_sign_url) ? $this->genImgHtml($supply_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        $new_user = wp_work_check_topic_a::where('wp_work_id', $this->work_id)->where('wp_check_id', 8)->select('new_user')->first();
        $create_user_name = isset($new_user) ? User::getName($new_user->new_user) : '';
        $this->showAry['ans']['create_user_name'] = SHCSLib::genReportAnsHtml($create_user_name, 12, SHCSLib::ALIGN_LEFT);

        return view('permit.permit_check4a_v1', $this->showAry);
    }


    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl,$imgAt,$maxWidth=80,$maxHeight=25)
    {
        return '<img src="'.$imgUrl.'" class="sign_img" width="'.$maxWidth.'"   height="'.$maxHeight.'"><span class="time_at">'.substr($imgAt,11,5).'</span>';
    }
}
