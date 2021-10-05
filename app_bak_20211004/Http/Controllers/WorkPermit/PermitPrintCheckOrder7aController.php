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
use Html;
use DB;
use Storage;
use DNS2D;
use PDF;

class PermitPrintCheckOrder7aController extends Controller
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
        $created_at = wp_work_check_topic_a::where('wp_work_id', $this->work_id)->where('wp_check_id', 11)->select('created_at')->first();
        $create_time = isset($created_at) ? $created_at->created_at->format('Y年m月d日') : '';
        $this->showAry['ans']['create_time'] = SHCSLib::genReportAnsHtml($create_time, 16, SHCSLib::ALIGN_LEFT);

        // 一、施工前
        // 1.確認查無地下埋設物方准使用機械進行開挖作業： [195]
        // 2.查明有地下埋設物須以人工開挖為原則： [196]
        // 3.開挖施工地點無堆置器材及廢料： [197]
        // 4.開挖時須豎立警告標示： [198]
        // 5.已完成煉製事業部大林煉油廠挖掘、佔用道路申請書： [199]
        $this->showAry['ans'][195] = isset($this->showAry['ans'][195]) ? $this->checkAry[$this->showAry['ans'][195]] : $this->checkAry['N'];
        $this->showAry['ans'][196] = isset($this->showAry['ans'][196]) ? $this->checkAry[$this->showAry['ans'][196]] : $this->checkAry['N'];
        $this->showAry['ans'][197] = isset($this->showAry['ans'][197]) ? $this->checkAry[$this->showAry['ans'][197]] : $this->checkAry['N'];
        $this->showAry['ans'][198] = isset($this->showAry['ans'][198]) ? $this->checkAry[$this->showAry['ans'][198]] : $this->checkAry['N'];
        $this->showAry['ans'][199] = isset($this->showAry['ans'][199]) ? $this->checkAry[$this->showAry['ans'][199]] : $this->checkAry['N'];

        // 二、施工中
        // 1.開挖超過一天者，須裝設夜間警示燈： [200]
        // 2.挖土機械作業前，須沿開挖範圍橫斷面，先以人工試探性挖土至規定深度，了解地下物，確認安全： [201]
        // 3.為防止坑內落磐、落水或側壁之崩塌等，須採設置支撐，清除浮石等必要措施： [202]
        // 4.工作場所之開口部份，須設有適度強度之圍欄或擋板或設鉸鏈、蓋板等或採取其他安全措施： [203]
        // 5.露天開挖場所開挖深度在1.5公尺以上之工程，應設置擋土支撐預防坍塌及設置明顯且有效之標示圍籬等防止人員墜落之措施，並應設置擋土支撐作業主管： [204]。 新:['worker'][14] 
        // 6.深度1.5公尺以上應設上下設施： [252]
        $this->showAry['ans'][200] = isset($this->showAry['ans'][200]) ? $this->checkAry[$this->showAry['ans'][200]] : $this->checkAry['N'];
        $this->showAry['ans'][201] = isset($this->showAry['ans'][201]) ? $this->checkAry[$this->showAry['ans'][201]] : $this->checkAry['N'];
        $this->showAry['ans'][202] = isset($this->showAry['ans'][202]) ? $this->checkAry[$this->showAry['ans'][202]] : $this->checkAry['N'];
        $this->showAry['ans'][203] = isset($this->showAry['ans'][203]) ? $this->checkAry[$this->showAry['ans'][203]] : $this->checkAry['N'];
        $this->showAry['worker']['14_chk'] = isset($workerNames[14]) && !empty($workerNames[14]) ?  $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['worker'][14] = isset($workerNames[14]) && !empty($workerNames[14]) ? SHCSLib::genReportAnsHtml($workerNames[14], 16, SHCSLib::ALIGN_LEFT) : '';
        $this->showAry['ans'][252] = isset($this->showAry['ans'][252]) ? $this->checkAry[$this->showAry['ans'][252]] : $this->checkAry['N'];

        // 轄區人員 (轄區：檢點者)
        list($dept_sign_url)   = wp_work_topic_a::getTopicAns($this->work_id, 57);
        $this->showAry['ans']['dept_sign_url'] = !empty($dept_sign_url) ? $this->genImgHtml($dept_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        // 監造負責人 (承攬商：職安衛人員)
        list($supply_sign_url)  = wp_work_topic_a::getTopicAns($this->work_id, 40);
        $this->showAry['ans']['supply_sign_url'] = !empty($supply_sign_url) ? $this->genImgHtml($supply_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        // 承攬商
        $this->showAry['ans']['supply'] = SHCSLib::genReportAnsHtml(b_supply::getName($this->workData->b_supply_id), 12, SHCSLib::ALIGN_LEFT);

        return view('permit.permit_check7a_v1',$this->showAry);
    }


    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl,$imgAt,$maxWidth=80,$maxHeight=25)
    {
        return '<img src="'.$imgUrl.'" class="sign_img" width="'.$maxWidth.'"   height="'.$maxHeight.'"><span class="time_at">'.substr($imgAt,11,5).'</span>';
    }
}
