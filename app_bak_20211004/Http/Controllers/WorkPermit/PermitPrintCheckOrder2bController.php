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
use App\Model\WorkPermit\wp_work_check_record2;
use Lang;
use Illuminate\Http\Request;
use Config;
use Html;
use DB;
use Storage;
use DNS2D;
use PDF;

class PermitPrintCheckOrder2bController extends Controller
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

        // 承攬商(或施工部門)
        $this->showAry['ans']['supply'] = SHCSLib::genReportAnsHtml(b_supply::getName($this->workData->b_supply_id), 24, SHCSLib::ALIGN_LEFT);

        $records = wp_work_check_record2::where('wp_work_id', $this->work_id)->where('isClose', 'N')->select('*')->get()->toArray();
        $page_size = 21;
        $this->showAry['ans']['total_page'] = empty($records) ? 1 : ceil(count($records) / $page_size);
        $total_row_count = empty($records) ? 21 : ceil(count($records) / $page_size) * $page_size;
        $this->showAry['ans']['data'] = array();
        for ($i = 0; $i < $total_row_count; $i++) {
            if (isset($records[$i])) {
                $this->showAry['ans']['data'][ceil(($i + 1)/$page_size)][] = array(
                    1 => '', // 設備名稱
                    2 => User::getName($records[$i]['b_cust_id']), // 姓    名
                    3 => $records[$i]['door_stamp1'], // 進入時間
                    4 => $records[$i]['door_stamp2'], // 出來時間
                    5 => '', // 備    註
                );
            } else {
                $this->showAry['ans']['data'][ceil(($i + 1)/$page_size)][] = array(
                    1 => '', // 設備名稱
                    2 => '', // 姓    名
                    3 => '', // 進入時間
                    4 => '', // 出來時間
                    5 => '', // 備    註
                );
            }
        }

        return view('permit.permit_check2b_v1',$this->showAry);
    }


    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl,$imgAt,$maxWidth=80,$maxHeight=25)
    {
        return '<img src="'.$imgUrl.'" class="sign_img" width="'.$maxWidth.'"   height="'.$maxHeight.'"><span class="time_at">'.substr($imgAt,11,5).'</span>';
    }
}
