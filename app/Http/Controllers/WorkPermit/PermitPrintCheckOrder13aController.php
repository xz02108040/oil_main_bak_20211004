<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Lib\SHCSLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\b_cust_e;
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
use App\Model\Supply\b_supply_member_l;
use Lang;
use Illuminate\Http\Request;
use Config;
use Html;
use DB;
use Storage;
use DNS2D;
use PDF;

class PermitPrintCheckOrder13aController extends Controller
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

        //取得工作許可證_工作人員證書字號
        $workerLicenseCodes = $this->getApiWorkPermitWorkerLicenseCodes($this->work_id, 57);
        
        // 一、承攬商確認
        // (一)堆高機操作人員確認
        // 1.有堆高機操作人員操作訓練合格證書。證書字號： 新:['worker'][22] 
        $this->showAry['worker']['22_chk'] = isset($workerLicenseCodes[22]) && !empty($workerLicenseCodes[22]) ?  $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['worker'][22] = isset($workerLicenseCodes[22]) && !empty($workerLicenseCodes[22]) ? SHCSLib::genReportAnsHtml($workerLicenseCodes[22], 16, SHCSLib::ALIGN_LEFT) : '';

        // 2.精神狀況良好。 [235]
        // 3.無飲用酒類或酒精性飲料： [236]
        $this->showAry['ans'][235] = isset($this->showAry['ans'][235]) ? $this->checkAry[$this->showAry['ans'][235]] : $this->checkAry['N'];
        $this->showAry['ans'][236] = isset($this->showAry['ans'][236]) ? $this->checkAry[$this->showAry['ans'][236]] : $this->checkAry['N'];

        // (二)堆高機確認
        // 1.堆高機制動裝置（手剎車、腳剎車）正常： [238]
        // 2.堆高機警報裝置正常： [239]
        // 3.堆高機前照燈及後照燈正常（夜間照明良好）： [240]
        // 4.堆高機有裝設滅焰器： [241]
        // 5.堆高機設有頂篷： [242]
        $this->showAry['ans'][238] = isset($this->showAry['ans'][238]) ? $this->checkAry[$this->showAry['ans'][238]] : $this->checkAry['N'];
        $this->showAry['ans'][239] = isset($this->showAry['ans'][239]) ? $this->checkAry[$this->showAry['ans'][239]] : $this->checkAry['N'];
        $this->showAry['ans'][240] = isset($this->showAry['ans'][240]) ? $this->checkAry[$this->showAry['ans'][240]] : $this->checkAry['N'];
        $this->showAry['ans'][241] = isset($this->showAry['ans'][241]) ? $this->checkAry[$this->showAry['ans'][241]] : $this->checkAry['N'];
        $this->showAry['ans'][242] = isset($this->showAry['ans'][242]) ? $this->checkAry[$this->showAry['ans'][242]] : $this->checkAry['N'];

        // 二、轄區部門確認
        // (一)工作場所確認
        // 1.轄區規劃進出通道及動線，動線上無危險設施： [244]
        // 2.進出通道明顯且無障礙： [245]
        // 3.動線暢通、明顯及有明顯之警戒措施： [246]
        // 4.動線通道寬敞。(超過車寬一公尺以上)： [247]
        // 5.現場連繫人員： [248]
        $this->showAry['ans'][244] = isset($this->showAry['ans'][244]) ? $this->checkAry[$this->showAry['ans'][244]] : $this->checkAry['N'];
        $this->showAry['ans'][245] = isset($this->showAry['ans'][245]) ? $this->checkAry[$this->showAry['ans'][245]] : $this->checkAry['N'];
        $this->showAry['ans'][246] = isset($this->showAry['ans'][246]) ? $this->checkAry[$this->showAry['ans'][246]] : $this->checkAry['N'];
        $this->showAry['ans'][247] = isset($this->showAry['ans'][247]) ? $this->checkAry[$this->showAry['ans'][247]] : $this->checkAry['N'];
        $this->showAry['ans']['248_chk'] = isset($this->showAry['ans'][248]) && !empty($this->showAry['ans'][248]) ? $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['ans'][248] = isset($this->showAry['ans'][248]) && !empty($this->showAry['ans'][248]) ? SHCSLib::genReportAnsHtml(User::getName($this->showAry['ans'][248]), 12, SHCSLib::ALIGN_LEFT) : '';

        // 承攬商確認者
        list($supply_sign_url)  = wp_work_topic_a::getTopicAns($this->work_id, 40);
        $this->showAry['ans']['supply_sign_url'] = !empty($supply_sign_url) ? $this->genImgHtml($supply_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        // 連繫者
        list($contact_sign_url)  = wp_work_topic_a::getTopicAns($this->work_id,62);
        $this->showAry['ans']['contact_sign_url'] = !empty($contact_sign_url) ? $this->genImgHtml($contact_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        // 轄區確認者
        list($dept_sign_url)   = wp_work_topic_a::getTopicAns($this->work_id, 57);
        $this->showAry['ans']['dept_sign_url'] = !empty($dept_sign_url) ? $this->genImgHtml($dept_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        // 轄區主管
        // 核簽者 (轄區主管=課級主管(工場長))
        list($dept_admin_sign_url) = wp_work_topic_a::getTopicAns($this->work_id, 67);
        $this->showAry['ans']['dept_admin_sign_url'] = !empty($dept_admin_sign_url) ? $this->genImgHtml($dept_admin_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        return view('permit.permit_check13a_v1', $this->showAry);
    }


    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl,$imgAt,$maxWidth=80,$maxHeight=25)
    {
        return '<img src="'.$imgUrl.'" class="sign_img" width="'.$maxWidth.'"   height="'.$maxHeight.'"><span class="time_at">'.substr($imgAt,11,5).'</span>';
    }
}
