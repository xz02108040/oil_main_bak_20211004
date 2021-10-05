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
use App\Model\WorkPermit\wp_work_check_topic_a;
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

class PermitPrintCheckOrder2aController extends Controller
{
    use BcustTrait,SessTraits,WorkPermitTopicOptionTrait,WorkPermitWorkerTrait;
    /**
     * 建構子
     */
    public function __construct()
    {
        $equalImg = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAA7klEQVR42mNkgABeIK4D4mggloCKMQLxfzQ2shguNS+AeCkQNwHxZ0ao4YeBWA+HBlItgLEvArEtiNENxCUMtAE9IAueEQgWcn0ADi4Y4z+NLMDQQHWAywdvgXgKEP8g0hwOIM4BYmFcPkC3YDoQZ5Po2KlAnElsEIFcn0uiBZOhviAqiNYAcRiJFqwC4hBigwgEngLxHyINZwFiGQY8qWj4JVOqBxE6WM1AXiSHEusDUJomJ5lmo5kzcBntDQN5RYUIsUGEi012MqWpBaAKR5LE8CYWPIdVmcU08kEvXSp9BgbUZgulwfWcAanZAgCnBI76sx2a+AAAAABJRU5ErkJggg==" width="10pt" width="10pt">';

        $this->checkAry     = ['Y'=>'■','N'=>'□',''=>'□','='=>$equalImg];
        // $this->ampmAry     = ['AM'=>'上午','PM'=>'下午'];
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

        // 進入
        $this->showAry['ans'][52] = isset($this->showAry['ans'][52]) ? $this->checkAry[$this->showAry['ans'][52]] : $this->checkAry['N'];
        // 動火
        $this->showAry['ans'][53] = isset($this->showAry['ans'][53]) ? $this->checkAry[$this->showAry['ans'][53]] : $this->checkAry['N'];

        // 本證編號
        $this->showAry['ans']['permit_no'] = $this->workData->permit_no;

        // 簽發時間
        $charge_date = new DateTime($this->workData->charge_stamp);
        $this->showAry['ans']["charge_date"] = $charge_date->format(' Y 年 m 月 d 日 H 點 i 分');

        // 開始作業時間
        $this->showAry['ans']["work_stime"] = "上午   時   分至上午   時   分止";

        list($work_stime)    = wp_work_topic_a::getTopicAns($this->work_id, 120);
        if (!empty($work_stime)) {
            $work_stime = new DateTime($work_stime);
            $hour = $work_stime->format('H');
            $minute = $work_stime->format('i');
            $this->showAry['ans']["work_stime"] = ($work_stime->format('A') == 'AM') ? "上午 $hour 時 $minute 分至上午 12  時 00 分止" : "下午 13 時 00 分    至下午 $hour 時 $minute 分止";
        }

        // 結束作業時間
        $this->showAry['ans']["work_etime"] = "下午   時   分    至下午   時   分止";
        list($work_etime)    = wp_work_topic_a::getTopicAns($this->work_id,121);
        if (!empty($work_etime)) {
            $work_etime = new DateTime($work_etime);
            $hour = $work_etime->format('H');
            $minute = $work_etime->format('i');
            $this->showAry['ans']["work_etime"] = ($work_etime->format('A') == 'AM') ? "上午 $hour 時 $minute 分至上午 12  時 00 分止" : "下午 13 時 00 分    至下午 $hour 時 $minute 分止";
        }

        // 承攬商(或施工部門)
        $this->showAry['ans']['supply'] = SHCSLib::genReportAnsHtml(b_supply::getName($this->workData->b_supply_id),24, SHCSLib::ALIGN_LEFT);

        // 監造部門
        $this->showAry['ans']['charge_dept'] = SHCSLib::genReportAnsHtml(be_dept::getName($this->workData->be_dept_id2),12, SHCSLib::ALIGN_LEFT);

        // 監造人員
        $this->showAry['ans']['charge_name'] = SHCSLib::genReportAnsHtml(User::getName(e_project::getChargeUser($this->workData->e_project_id)[0]),12, SHCSLib::ALIGN_LEFT);

        // 一、作業場所
        // 轄區部門
        $this->showAry['ans']['dept'] = SHCSLib::genReportAnsHtml(be_dept::getName($this->workData->be_dept_id1),20, SHCSLib::ALIGN_LEFT);

        // 施工地點
        $this->showAry['ans']['work_place'] = SHCSLib::genReportAnsHtml(b_factory_b::getName($this->workData->b_factory_b_id),20, SHCSLib::ALIGN_LEFT);

        // 二、作業種類
        // 動火作業
        $this->showAry['ans'][54] = isset($this->showAry['ans'][54]) ? $this->checkAry[$this->showAry['ans'][54]] : $this->checkAry['N'];
        // 噴砂、除銹作業
        $this->showAry['ans'][55] = isset($this->showAry['ans'][55]) ? $this->checkAry[$this->showAry['ans'][55]] : $this->checkAry['N'];
        // 油漆作業
        $this->showAry['ans'][56] = isset($this->showAry['ans'][56]) ? $this->checkAry[$this->showAry['ans'][56]] : $this->checkAry['N'];
        // 搭架作業
        $this->showAry['ans'][57] = isset($this->showAry['ans'][57]) ? $this->checkAry[$this->showAry['ans'][57]] : $this->checkAry['N'];
        // 煉、儲設備清洗作業
        $this->showAry['ans'][58] = isset($this->showAry['ans'][58]) ? $this->checkAry[$this->showAry['ans'][58]] : $this->checkAry['N'];
        // 其他
        $this->showAry['ans']['59_val'] = !empty($this->showAry['ans'][59]) ? $this->showAry['ans'][59] : '';
        $this->showAry['ans'][59] = !empty($this->showAry['ans'][59]) ? $this->checkAry['Y'] : $this->checkAry['N'];

        // 動火作業應檢點項目：
        // 1.電焊機已接地妥並設有自動電擊防止裝置(交流)，電線無破損且絕緣良好。
        $this->showAry['ans'][61] = isset($this->showAry['ans'][61]) ? $this->checkAry[$this->showAry['ans'][61]] : $this->checkAry['N'];
        // 2.接用電源時設有漏電斷路器。
        $this->showAry['ans'][62] = isset($this->showAry['ans'][62]) ? $this->checkAry[$this->showAry['ans'][62]] : $this->checkAry['N'];
        // 3.乙炔瓶須豎立妥且加上不燃性護帽，軟管完好且確實以管夾夾緊。
        $this->showAry['ans'][63] = isset($this->showAry['ans'][63]) ? $this->checkAry[$this->showAry['ans'][63]] : $this->checkAry['N'];
        // 4.每一施工現場十公尺內，應備有20型以上手提滅火器。
        $this->showAry['ans'][64] = isset($this->showAry['ans'][64]) ? $this->checkAry[$this->showAry['ans'][64]] : $this->checkAry['N'];
        // 5.已備有□氧氣□可燃性氣體□毒性氣體(      )偵測器，型號            ，並連續監測及定時記錄備查。
        $this->showAry['ans'][65] = !empty($this->showAry['ans'][70]) ? $this->checkAry['Y'] : $this->checkAry['N'];
        $this->showAry['ans'][66] = isset($this->showAry['ans'][66]) ? $this->checkAry[$this->showAry['ans'][66]] : $this->checkAry['N'];
        $this->showAry['ans'][67] = isset($this->showAry['ans'][67]) ? $this->checkAry[$this->showAry['ans'][67]] : $this->checkAry['N'];
        $this->showAry['ans'][68] = isset($this->showAry['ans'][68]) ? $this->checkAry[$this->showAry['ans'][68]] : $this->checkAry['N'];
        $this->showAry['ans'][69] = isset($this->showAry['ans'][69]) ? SHCSLib::genReportAnsHtml($this->showAry['ans'][69], 6) : SHCSLib::genReportAnsHtml('', 6);
        $this->showAry['ans'][70] = isset($this->showAry['ans'][70]) ? SHCSLib::genReportAnsHtml($this->showAry['ans'][70], 12) : SHCSLib::genReportAnsHtml('', 12);
        // 6.看火者：
        $this->showAry['ans']['71_val'] = isset($this->showAry['ans'][71]) ? User::getName($this->showAry['ans'][71]) : '';
        $this->showAry['ans'][71] = !empty($this->showAry['ans'][71]) ? $this->checkAry['Y'] : $this->checkAry['N'];

        // 三、作業環境測定 (抓工作許可證 承攬商測氣的值)
        list($chk_supply_topic99, $chk_supply_topic100)    = wp_work_topic_a::getTopicAns($this->work_id, 24, $this->imgResize);
        // 測定時間 [40]
        $this->showAry['ans'][40] = isset($chk_supply_topic100) ? $chk_supply_topic100 : '';
        // 可燃性氣體LEL% [41]
        $this->showAry['ans'][41] = isset($chk_supply_topic99[3]) ?    $chk_supply_topic99[3] : '';
        // 氧氣% [42]
        $this->showAry['ans'][42] = isset($chk_supply_topic99[4]) ?    $chk_supply_topic99[4] : '';
        // 有害氣體ppm() [46]
        $this->showAry['ans'][46] = isset($chk_supply_topic99[15]) ?   $chk_supply_topic99[15] : '';
        // 測定人員 [48]
        list($chk_supply_topic108)  = wp_work_topic_a::getTopicAns($this->work_id,40);
        $this->showAry['ans'][48] = !empty($chk_supply_topic108)? $this->genImgHtml($chk_supply_topic108,'') : '';

        // 四、作業場所可能之危害
        // 缺氧、窒息 [72]
        $this->showAry['ans'][72] = isset($this->showAry['ans'][72]) ? $this->checkAry[$this->showAry['ans'][72]] : $this->checkAry['N'];
        // 火災、爆炸 [73]
        $this->showAry['ans'][73] = isset($this->showAry['ans'][73]) ? $this->checkAry[$this->showAry['ans'][73]] : $this->checkAry['N'];
        // 感電 [74]
        $this->showAry['ans'][74] = isset($this->showAry['ans'][74]) ? $this->checkAry[$this->showAry['ans'][74]] : $this->checkAry['N'];
        // 墜落、滑落 [75]
        $this->showAry['ans'][75] = isset($this->showAry['ans'][75]) ? $this->checkAry[$this->showAry['ans'][75]] : $this->checkAry['N'];
        // 有機溶劑 [76]
        $this->showAry['ans'][76] = isset($this->showAry['ans'][76]) ? $this->checkAry[$this->showAry['ans'][76]] : $this->checkAry['N'];
        // 特定化學物質 [77]
        $this->showAry['ans'][77] = isset($this->showAry['ans'][77]) ? $this->checkAry[$this->showAry['ans'][77]] : $this->checkAry['N'];
        // 其它：[78]
        $this->showAry['ans']['78_val'] = !empty($this->showAry['ans'][78]) ? $this->showAry['ans'][78] : '';
        $this->showAry['ans'][78] = !empty($this->showAry['ans'][78]) ? $this->checkAry['Y'] : $this->checkAry['N'];

        // 五、作業場所之能源隔離措施（會同轄區人員確認）
        // 危害物質及惰性氣體進出口管線 [79]
        $this->showAry['ans'][79] = isset($this->showAry['ans'][79]) ? $this->checkAry[$this->showAry['ans'][79]] : $this->checkAry['N'];
        // 閥或旋塞關閉，加鎖或鉛封，掛「不得開啟」之標示； [80]
        $this->showAry['ans'][80] = isset($this->showAry['ans'][80]) ? $this->checkAry[$this->showAry['ans'][80]] : $this->checkAry['N'];
        // 已加盲。 [81]
        $this->showAry['ans'][81] = isset($this->showAry['ans'][81]) ? $this->checkAry[$this->showAry['ans'][81]] : $this->checkAry['N'];
        // 機械停止運轉。並上鎖及設置標示等措施。 [82]
        $this->showAry['ans'][82] = isset($this->showAry['ans'][82]) ? $this->checkAry[$this->showAry['ans'][82]] : $this->checkAry['N'];
        // 已將該設備或管線適當冷卻。 [83]
        $this->showAry['ans'][83] = isset($this->showAry['ans'][83]) ? $this->checkAry[$this->showAry['ans'][83]] : $this->checkAry['N'];
        // 手提式照明燈，其使用電壓不得超過二十四伏特，且導線須為耐磨損及有良好絕緣，並不得有接頭。 [84]
        $this->showAry['ans'][84] = isset($this->showAry['ans'][84]) ? $this->checkAry[$this->showAry['ans'][84]] : $this->checkAry['N'];
        // 其他： [85]
        $this->showAry['ans']['85_val'] = !empty($this->showAry['ans'][85]) ? $this->showAry['ans'][85] : '';
        $this->showAry['ans'][85] = !empty($this->showAry['ans'][85]) ? $this->checkAry['Y'] : $this->checkAry['N'];

        // 六、換氣裝置
        // 通風換氣裝置運轉正常 [86]
        $this->showAry['ans'][86] = isset($this->showAry['ans'][86]) ? $this->checkAry[$this->showAry['ans'][86]] : $this->checkAry['N'];
        // 型式 Blower [87]
        $this->showAry['ans'][87] = isset($this->showAry['ans'][87]) ? $this->checkAry[$this->showAry['ans'][87]] : $this->checkAry['N'];
        // 型式 Air Jet [88]
        $this->showAry['ans'][88] = isset($this->showAry['ans'][88]) ? $this->checkAry[$this->showAry['ans'][88]] : $this->checkAry['N'];

        // 七、作業人員與外部連繫之設備及方法
        // 緊急救命呼叫器 [89]
        $this->showAry['ans'][89] = isset($this->showAry['ans'][89]) ? $this->checkAry[$this->showAry['ans'][89]] : $this->checkAry['N'];
        // 對講機 [90]
        $this->showAry['ans'][90] = isset($this->showAry['ans'][90]) ? $this->checkAry[$this->showAry['ans'][90]] : $this->checkAry['N'];
        // 其他 [91]
        $this->showAry['ans']['91_val'] = !empty($this->showAry['ans'][91]) ? $this->showAry['ans'][91] : '';
        $this->showAry['ans'][91] = !empty($this->showAry['ans'][91]) ? $this->checkAry['Y'] : $this->checkAry['N'];

        // 八、防護設備、救援設備及使用方法(使用方法如背面)
        // 物體飛落、有害物中毒、或缺氧危害之虞，已備 [92]
        $this->showAry['ans'][92] = isset($this->showAry['ans'][92]) ? $this->checkAry[$this->showAry['ans'][92]] : $this->checkAry['N'];
        // 安全帽 [93]
        $this->showAry['ans'][93] = isset($this->showAry['ans'][93]) ? $this->checkAry[$this->showAry['ans'][93]] : $this->checkAry['N'];
        // 空氣呼吸器 [94]
        $this->showAry['ans'][94] = isset($this->showAry['ans'][94]) ? $this->checkAry[$this->showAry['ans'][94]] : $this->checkAry['N'];
        // 正壓自供式 [95]
        $this->showAry['ans'][95] = isset($this->showAry['ans'][95]) ? $this->checkAry[$this->showAry['ans'][95]] : $this->checkAry['N'];
        // 輸氣管式 [96]
        $this->showAry['ans'][96] = isset($this->showAry['ans'][96]) ? $this->checkAry[$this->showAry['ans'][96]] : $this->checkAry['N'];
        // 氧氣呼吸器 [97]
        $this->showAry['ans'][97] = isset($this->showAry['ans'][97]) ? $this->checkAry[$this->showAry['ans'][97]] : $this->checkAry['N'];
        // 防毒面具(口罩) [98]
        $this->showAry['ans'][98] = isset($this->showAry['ans'][98]) ? $this->checkAry[$this->showAry['ans'][98]] : $this->checkAry['N'];
        // 防塵面具(口罩)等防護器材。 [99]
        $this->showAry['ans'][99] = isset($this->showAry['ans'][99]) ? $this->checkAry[$this->showAry['ans'][99]] : $this->checkAry['N'];
        // 有暴露於 [100]
        $this->showAry['ans'][100] = isset($this->showAry['ans'][100]) ? $this->checkAry[$this->showAry['ans'][100]] : $this->checkAry['N'];
        // 高溫 [101]
        $this->showAry['ans'][101] = isset($this->showAry['ans'][101]) ? $this->checkAry[$this->showAry['ans'][101]] : $this->checkAry['N'];
        // 低溫 [102]
        $this->showAry['ans'][102] = isset($this->showAry['ans'][102]) ? $this->checkAry[$this->showAry['ans'][102]] : $this->checkAry['N'];
        // 非游離輻射線 [103]
        $this->showAry['ans'][103] = isset($this->showAry['ans'][103]) ? $this->checkAry[$this->showAry['ans'][103]] : $this->checkAry['N'];
        // 生物病原體 [104]
        $this->showAry['ans'][104] = isset($this->showAry['ans'][104]) ? $this->checkAry[$this->showAry['ans'][104]] : $this->checkAry['N'];
        // 有害氣體 [105]
        $this->showAry['ans'][105] = isset($this->showAry['ans'][105]) ? $this->checkAry[$this->showAry['ans'][105]] : $this->checkAry['N'];
        // 蒸氣 [106]
        $this->showAry['ans'][106] = isset($this->showAry['ans'][106]) ? $this->checkAry[$this->showAry['ans'][106]] : $this->checkAry['N'];
        // 粉塵或 [107]
        $this->showAry['ans'][107] = isset($this->showAry['ans'][107]) ? $this->checkAry[$this->showAry['ans'][107]] : $this->checkAry['N'];
        // 其他有害物之虞者，已置備 [108]
        $this->showAry['ans'][108] = isset($this->showAry['ans'][108]) ? $this->checkAry[$this->showAry['ans'][108]] : $this->checkAry['N'];
        // 安全面罩 [110]
        $this->showAry['ans'][110] = isset($this->showAry['ans'][110]) ? $this->checkAry[$this->showAry['ans'][110]] : $this->checkAry['N'];
        // 防塵口罩 [111]
        $this->showAry['ans'][111] = isset($this->showAry['ans'][111]) ? $this->checkAry[$this->showAry['ans'][111]] : $this->checkAry['N'];
        // 防毒面具 [112]
        $this->showAry['ans'][112] = isset($this->showAry['ans'][112]) ? $this->checkAry[$this->showAry['ans'][112]] : $this->checkAry['N'];
        // 防護眼鏡 [113]
        $this->showAry['ans'][113] = isset($this->showAry['ans'][113]) ? $this->checkAry[$this->showAry['ans'][113]] : $this->checkAry['N'];
        // 防護衣等適當之防護具。 [114]
        $this->showAry['ans'][114] = isset($this->showAry['ans'][114]) ? $this->checkAry[$this->showAry['ans'][114]] : $this->checkAry['N'];
        // 有墜落之虞之作業，已置備適當之 [115]
        $this->showAry['ans'][115] = isset($this->showAry['ans'][115]) ? $this->checkAry[$this->showAry['ans'][115]] : $this->checkAry['N'];
        // 梯子 [116]
        $this->showAry['ans'][116] = isset($this->showAry['ans'][116]) ? $this->checkAry[$this->showAry['ans'][116]] : $this->checkAry['N'];
        // 安全帶 [117]
        $this->showAry['ans'][117] = isset($this->showAry['ans'][117]) ? $this->checkAry[$this->showAry['ans'][117]] : $this->checkAry['N'];
        // 安全帶或救生索及 [118]
        $this->showAry['ans'][118] = isset($this->showAry['ans'][118]) ? $this->checkAry[$this->showAry['ans'][118]] : $this->checkAry['N'];
        // 其他必要之防護具： [119]
        $this->showAry['ans'][119] = !empty($this->showAry['ans'][119]) ? $this->showAry['ans'][119] : '';
        // 電焊、氣焊從事熔接、熔斷等作業，已置備 [120]
        $this->showAry['ans'][120] = isset($this->showAry['ans'][120]) ? $this->checkAry[$this->showAry['ans'][120]] : $this->checkAry['N'];
        // 安全面罩 [121]
        $this->showAry['ans'][121] = isset($this->showAry['ans'][121]) ? $this->checkAry[$this->showAry['ans'][121]] : $this->checkAry['N'];
        // 防護眼鏡及 [122]
        $this->showAry['ans'][122] = isset($this->showAry['ans'][122]) ? $this->checkAry[$this->showAry['ans'][122]] : $this->checkAry['N'];
        // 防護手套等。 [123]
        $this->showAry['ans'][123] = isset($this->showAry['ans'][123]) ? $this->checkAry[$this->showAry['ans'][123]] : $this->checkAry['N'];
        // 已置備 [124]
        $this->showAry['ans'][124] = isset($this->showAry['ans'][124]) ? $this->checkAry[$this->showAry['ans'][124]] : $this->checkAry['N'];
        // 空氣呼吸器 [125]
        $this->showAry['ans'][125] = isset($this->showAry['ans'][125]) ? $this->checkAry[$this->showAry['ans'][125]] : $this->checkAry['N'];
        // 梯子 [126]
        $this->showAry['ans'][126] = isset($this->showAry['ans'][126]) ? $this->checkAry[$this->showAry['ans'][126]] : $this->checkAry['N'];
        // 安全帶 [127]
        $this->showAry['ans'][127] = isset($this->showAry['ans'][127]) ? $this->checkAry[$this->showAry['ans'][127]] : $this->checkAry['N'];
        // 胸式或全身式救生索等設備，供勞工避難或救援人員使用。 [128]
        $this->showAry['ans'][128] = isset($this->showAry['ans'][128]) ? $this->checkAry[$this->showAry['ans'][128]] : $this->checkAry['N'];

        // 九、其他維護作業人員之安全措施
        // 說明 [129]
        $this->showAry['ans'][129] = isset($this->showAry['ans'][129]) ? $this->showAry['ans'][129] : '';

        // 十、許可進入之人員名冊：
        $this->showAry['ans']['supply_workers']  = $this->getApiWorkPermitWorkerForPrint($this->work_id,27);

        // 十一、現場監視人員簽名： [130]
        $this->showAry['ans'][130] = !empty($this->showAry['ans'][130]) ? $this->genImgHtml($this->showAry['ans'][130], '') : '';

        // 缺氧作業主管
        $this->showAry['ans']['hypoxic_work_supervisor'] = $this->getApiWorkPermitWorkerForPrint($this->work_id, 28);
        // 有機溶劑作業主管
        $this->showAry['ans']['organic_solvents_work_supervisor'] = SHCSLib::genReportAnsHtml($this->getApiWorkPermitWorkerForPrint($this->work_id,32),20);
        // 特定化學物質作業主管
        $specified_chemical_substance_supervisor = wp_work_worker::where('wp_work_id', $this->work_id)->where('engineering_identity_id', 24)->select('user_id')->first();
        $this->showAry['ans']['specified_chemical_substance_supervisor'] = isset($specified_chemical_substance_supervisor) ? User::getName($specified_chemical_substance_supervisor->user_id) : '';

        // 檢點者 (承攬商_工安)
        list($supply_sign_url)  = wp_work_topic_a::getTopicAns($this->work_id, 40);
        $this->showAry['ans']['supply_sign_url'] = !empty($supply_sign_url) ? $this->genImgHtml($supply_sign_url, '') . SHCSLib::genReportAnsHtml('', 2) : SHCSLib::genReportAnsHtml('', 12);

        // 原核簽者 (轄區主管=課級主管(工場長)) id=67
        // 新核簽者 (承攬商_工負) by 2021-07-16
        list($dept_admin_sign_url) = wp_work_topic_a::getTopicAns($this->work_id,74);
        $this->showAry['ans']['dept_admin_sign_url'] = !empty($dept_admin_sign_url)? $this->genImgHtml($dept_admin_sign_url, '') : '';

        return view('permit.permit_check2a_v1',$this->showAry);
    }


    /**
     * 圖檔
     */
    public function genImgHtml($imgUrl,$imgAt,$maxWidth=80,$maxHeight=25)
    {
        return '<img src="'.$imgUrl.'" class="sign_img" width="'.$maxWidth.'"   height="'.$maxHeight.'"><span class="time_at">'.substr($imgAt,11,5).'</span>';
    }
}
