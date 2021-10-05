<?php

namespace App\Http\Controllers;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Lib\SHCSLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory_a;
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

class TestPermitController extends Controller
{
    use BcustTrait,SessTraits,WorkPermitTopicOptionTrait;
    /**
     * 建構子
     */
    public function __construct()
    {

    }

    /**
     * 顯示測試內容
     * @param Request $request
     */
    public function index(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $equalImg = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAA7klEQVR42mNkgABeIK4D4mggloCKMQLxfzQ2shguNS+AeCkQNwHxZ0ao4YeBWA+HBlItgLEvArEtiNENxCUMtAE9IAueEQgWcn0ADi4Y4z+NLMDQQHWAywdvgXgKEP8g0hwOIM4BYmFcPkC3YDoQZ5Po2KlAnElsEIFcn0uiBZOhviAqiNYAcRiJFqwC4hBigwgEngLxHyINZwFiGQY8qWj4JVOqBxE6WM1AXiSHEusDUJomJ5lmo5kzcBntDQN5RYUIsUGEi012MqWpBaAKR5LE8CYWPIdVmcU08kEvXSp9BgbUZgulwfWcAanZAgCnBI76sx2a+AAAAABJRU5ErkJggg==" width="15pt" >';
        //$checkAry = ['Y'=>'■','N'=>'□',''=>'='];
        $checkAry = ['Y'=>'■','N'=>'□',''=>'□','='=>$equalImg];
        $imgResize = 500;

        //工作許可證
        $work_id  = SHCSLib::decode($request->id);
        if(!is_numeric($work_id)) exit;
        $workData           = wp_work::getData($work_id);
        $permit_no          = $workData->permit_no;
        $apply_stamp        = $workData->apply_stamp;
        $apply_format       = date('Y年m月d日 H點i分',strtotime($apply_stamp));
        $last_updated_at    = wp_work_list::getLastAt($work_id);
        $last_updated_format= date('Y年m月d日 H點i分',$last_updated_at);
        $permit_danger      = $workData->wp_permit_danger;
        $permit_dangerA     = ($permit_danger == 'A')? $checkAry['Y'] : $checkAry['N'];
        $permit_dangerB     = ($permit_danger == 'B')? $checkAry['Y'] : $checkAry['N'];
        $permit_dangerC     = ($permit_danger == 'C')? $checkAry['Y'] : $checkAry['N'];
        $be_dept_id1        = be_dept::getName($workData->be_dept_id1);
        $be_dept_id2        = be_dept::getName($workData->be_dept_id2);
        $be_dept_id3        = be_dept::getName($workData->be_dept_id3);
        $be_dept_id4        = be_dept::getName($workData->be_dept_id4);
        $be_dept_id5        = '';
        $sdate              = date('Y年m月d日',strtotime($workData->sdate));
        $b_supply           = b_supply::getName($workData->b_supply_id);
        $b_supply_worker_amt= wp_work_worker::getAmt($work_id);
        $car_no             = rept_doorinout_car_t::getTodayCar($workData->b_factory_id,$workData->b_supply_id,2);
        $work_place         = b_factory_a::getName($workData->b_factory_a_id).'('.$workData->b_factory_memo.')';
        $project_no         = e_project::getNo($workData->e_project_id);
        $work_memo          = $workData->wp_permit_workitem_memo;
        $isOvertime         = isset($checkAry[$workData->isOvertime])? $checkAry[$workData->isOvertime] : '';
        //工作項目
        $workitemAry        = wp_work_workitem::getSelect($work_id);
        $workitem_1         = isset($workitemAry[2])? $checkAry['Y'] : $checkAry['N'];
        $workitem_2         = isset($workitemAry[3])? $checkAry['Y'] : $checkAry['N'];
        $workitem_3         = isset($workitemAry[4])? $checkAry['Y'] : $checkAry['N'];
        $workitem_4         = isset($workitemAry[5])? $checkAry['Y'] : $checkAry['N'];
        $workitem_5         = isset($workitemAry[6])? $checkAry['Y'] : $checkAry['N'];
        $workitem_6         = isset($workitemAry[7])? $checkAry['Y'] : $checkAry['N'];
        $workitem_7         = isset($workitemAry[8])? $checkAry['Y'] : $checkAry['N'];
        $workitem_8         = isset($workitemAry[9])? $checkAry['Y'] : $checkAry['N'];
        $workitem_9         = isset($workitemAry[1])? $checkAry['Y'] : $checkAry['N'];
        $workitem_10        = isset($workitemAry[1])? $workitemAry[1] : '';
        $workitem_20        = isset($workitemAry[11])? $checkAry['Y'] : $checkAry['N'];
        $workitem_21        = isset($workitemAry[12])? $checkAry['Y'] : $checkAry['N'];
        $workitem_22        = isset($workitemAry[13])? $checkAry['Y'] : $checkAry['N'];
        $workitem_23        = isset($workitemAry[14])? $checkAry['Y'] : $checkAry['N'];
        $workitem_24        = isset($workitemAry[15])? $checkAry['Y'] : $checkAry['N'];
        $workitem_25        = isset($workitemAry[16])? $checkAry['Y'] : $checkAry['N'];
        $workitem_26        = isset($workitemAry[10])? $checkAry['Y'] : $checkAry['N'];
        $workitem_27        = isset($workitemAry[10])? $workitemAry[10] : '';
        $workitem_30        = isset($workitemAry[18])? $checkAry['Y'] : $checkAry['N'];
        $workitem_31        = isset($workitemAry[19])? $checkAry['Y'] : $checkAry['N'];
        $workitem_32        = isset($workitemAry[17])? $checkAry['Y'] : $checkAry['N'];
        $workitem_33        = isset($workitemAry[17])? $workitemAry[17] : '';
        //附加檢點表
        $workCheckAry       = wp_work_check::getSelect($work_id,0);
        $workCheckStr       = implode('，',$workCheckAry);
        //管線內容物
        $worklineAry        = wp_work_line::getSelect($work_id);
        $workline_1         = isset($worklineAry[1])? $checkAry['Y'] : $checkAry['N'];
        $workline_11        = isset($worklineAry[1])? $worklineAry[1] : '';
        $workline_2         = isset($worklineAry[2])? $checkAry['Y'] : $checkAry['N'];
        $workline_3         = isset($worklineAry[3])? $checkAry['Y'] : $checkAry['N'];
        $workline_4         = isset($worklineAry[4])? $checkAry['Y'] : $checkAry['N'];
        $workline_5         = isset($worklineAry[5])? $checkAry['Y'] : $checkAry['N'];
        $workline_6         = isset($worklineAry[6])? $checkAry['Y'] : $checkAry['N'];
        $workline_7         = isset($worklineAry[7])? $checkAry['Y'] : $checkAry['N'];
        $workline_8         = isset($worklineAry[8])? $checkAry['Y'] : $checkAry['N'];
        /*
         * ============================================
         */
        //監造人員簽名
        $projectCharge      = wp_work::getProjectCharge($work_id);
        $signtCharge1       = wp_work_process::getChargeUser($work_id,1);
        $sign_agent1        = ($projectCharge != $signtCharge1)? '（代）' : '';
        $sign_url1          = wp_work_topic_a::getTopicAns($work_id,4);
        $sign_url1_at       = wp_work_topic_a::getTopicAnsAt($work_id,4);
        $chk_supply_topic1  = wp_work_topic_a::getTopicAns($work_id,76);
        $chk_supply_topic1a = isset($checkAry[$chk_supply_topic1])? $checkAry[$chk_supply_topic1] : $checkAry['N'];

        $chk_supply_topic2  = wp_work_topic_a::getTopicAns($work_id,6);
        $chk_supply_topic2a = isset($checkAry[$chk_supply_topic2])? $checkAry[$chk_supply_topic2] : $checkAry['N'];
        $chk_supply_topic3  = wp_work_topic_a::getTopicAns($work_id,7);
        $chk_supply_topic3a = isset($checkAry[$chk_supply_topic3])? $checkAry[$chk_supply_topic3] : $checkAry['N'];
        $chk_supply_topic4  = wp_work_topic_a::getTopicAns($work_id,8);
        $chk_supply_topic4a = isset($checkAry[$chk_supply_topic4])? $checkAry[$chk_supply_topic4] : $checkAry['N'];
        $chk_supply_topic5  = wp_work_topic_a::getTopicAns($work_id,9);
        $chk_supply_topic5a = $chk_supply_topic5 ? $checkAry['Y'] : $checkAry['N'];
        $chk_supply_topic6  = wp_work_topic_a::getTopicAns($work_id,77);
        $chk_supply_topic6a = isset($checkAry[$chk_supply_topic6])? $checkAry[$chk_supply_topic6] : $checkAry['N'];
        if($chk_supply_topic2 == 'Y' || $chk_supply_topic3 == 'Y' || $chk_supply_topic4 == 'Y' || $chk_supply_topic5)
        {
            $chk_supply_topic7 = $checkAry['Y'];
        } else {
            $chk_supply_topic7 = $checkAry['N'];
        }

        $chk_supply_topic8  = wp_work_topic_a::getTopicAns($work_id,10);
        $chk_supply_topic8a = isset($checkAry[$chk_supply_topic8])? $checkAry[$chk_supply_topic8] : $checkAry['N'];
        $chk_supply_topic9  = wp_work_topic_a::getTopicAns($work_id,11);
        $chk_supply_topic9a = isset($checkAry[$chk_supply_topic9])? $checkAry[$chk_supply_topic9] : $checkAry['N'];
        if($chk_supply_topic8 == 'Y' || $chk_supply_topic9 == 'Y')
        {
            $chk_supply_topic10 = $checkAry['Y'];
        } else {
            $chk_supply_topic10 = $checkAry['N'];
        }

        $chk_supply_topic11  = wp_work_topic_a::getTopicAns($work_id,12);
        $chk_supply_topic11a = isset($checkAry[$chk_supply_topic11])? $checkAry[$chk_supply_topic11] : $checkAry['N'];
        $chk_supply_topic12  = wp_work_topic_a::getTopicAns($work_id,13);
        $chk_supply_topic12a = isset($checkAry[$chk_supply_topic12])? $checkAry[$chk_supply_topic12] : $checkAry['N'];
        $chk_supply_topic13  = wp_work_topic_a::getTopicAns($work_id,14);
        $chk_supply_topic13a = isset($checkAry[$chk_supply_topic13])? $checkAry[$chk_supply_topic13] : $checkAry['N'];
        $chk_supply_topic14  = wp_work_topic_a::getTopicAns($work_id,15);
        $chk_supply_topic14a = isset($checkAry[$chk_supply_topic14])? $checkAry[$chk_supply_topic14] : $checkAry['N'];
        $chk_supply_topic15  = wp_work_topic_a::getTopicAns($work_id,16);
        $chk_supply_topic15a = isset($checkAry[$chk_supply_topic15])? $checkAry[$chk_supply_topic15] : $checkAry['N'];
        $chk_supply_topic16  = wp_work_topic_a::getTopicAns($work_id,17);
        $chk_supply_topic16a = isset($checkAry[$chk_supply_topic16])? $checkAry[$chk_supply_topic16] : $checkAry['N'];
        $chk_supply_topic17  = wp_work_topic_a::getTopicAns($work_id,18);
        $chk_supply_topic17a = isset($checkAry[$chk_supply_topic17])? $checkAry[$chk_supply_topic17] : $checkAry['N'];
        $chk_supply_topic18  = wp_work_topic_a::getTopicAns($work_id,19);
        $chk_supply_topic18a = isset($checkAry[$chk_supply_topic18])? $checkAry[$chk_supply_topic18] : $checkAry['N'];
        $chk_supply_topic19  = wp_work_topic_a::getTopicAns($work_id,20);
        $chk_supply_topic19a = isset($checkAry[$chk_supply_topic19])? $checkAry[$chk_supply_topic19] : $checkAry['N'];
        $chk_supply_topic20  = wp_work_topic_a::getTopicAns($work_id,21);
        $chk_supply_topic20a = isset($checkAry[$chk_supply_topic20])? $checkAry[$chk_supply_topic20] : $checkAry['N'];
        $chk_supply_topic21  = wp_work_topic_a::getTopicAns($work_id,22);
        $chk_supply_topic21a = isset($checkAry[$chk_supply_topic21])? $checkAry[$chk_supply_topic21] : $checkAry['N'];
        $chk_supply_topic22  = wp_work_topic_a::getTopicAns($work_id,23);
        $chk_supply_topic22a = isset($checkAry[$chk_supply_topic22])? $checkAry[$chk_supply_topic22] : $checkAry['N'];
        if($chk_supply_topic11 == 'Y' || $chk_supply_topic12 == 'Y' || $chk_supply_topic13 == 'Y' || $chk_supply_topic14 == 'Y'
            || $chk_supply_topic15 == 'Y' || $chk_supply_topic16 == 'Y' || $chk_supply_topic17 == 'Y' || $chk_supply_topic18 == 'Y'
            || $chk_supply_topic19 == 'Y' || $chk_supply_topic20 == 'Y' || $chk_supply_topic21 == 'Y' || $chk_supply_topic22 == 'Y')
        {
            $chk_supply_topic23 = $checkAry['Y'];
        } else {
            $chk_supply_topic23 = $checkAry['N'];
        }
        //看火者＆
        $chk_supply_topic30  = wp_work_topic_a::getTopicAns($work_id,25);
        if($chk_supply_topic30) $chk_supply_topic30 = User::getName($chk_supply_topic30);
        $chk_supply_topic31  = wp_work_topic_a::getTopicAns($work_id,26);
        if($chk_supply_topic31) $chk_supply_topic31 = User::getName($chk_supply_topic31);
        $chk_supply_topic32a  = ($chk_supply_topic30)? $checkAry['Y'] : $checkAry['N'];
        $chk_supply_topic32b  = ($chk_supply_topic31)? $checkAry['Y'] : $checkAry['N'];
        //施工人員
        $chk_supply_topic34  = $this->getApiWorkPermitTopicOptionIdentity($work_id,27,1);
//        dd($chk_supply_topic34);
        if(is_array($chk_supply_topic34)) $chk_supply_topic34 = '';
        $chk_supply_topic35  = ($chk_supply_topic34)? $checkAry['Y'] : $checkAry['N'];
        //缺氧作業主管
        $chk_supply_topic36  = $this->getApiWorkPermitTopicOptionIdentity($work_id,28,1);
        if(is_array($chk_supply_topic36)) $chk_supply_topic36 = '';
        $chk_supply_topic37  = ($chk_supply_topic36)? $checkAry['Y'] : $checkAry['N'];
        //施工架組配作業主管
        $chk_supply_topic38  = $this->getApiWorkPermitTopicOptionIdentity($work_id,29,1);
        if(is_array($chk_supply_topic38)) $chk_supply_topic38 = '';
        $chk_supply_topic39  = ($chk_supply_topic38)? $checkAry['Y'] : $checkAry['N'];
        //起重操作人員
        $chk_supply_topic40  = $this->getApiWorkPermitTopicOptionIdentity($work_id,30,1);
        if(is_array($chk_supply_topic40)) $chk_supply_topic40 = '';
        $chk_supply_topic41  = ($chk_supply_topic40)? $checkAry['Y'] : $checkAry['N'];
        //起重操作人員
        $chk_supply_topic42  = $this->getApiWorkPermitTopicOptionIdentity($work_id,31,1);
        if(is_array($chk_supply_topic42)) $chk_supply_topic42 = '';
        $chk_supply_topic43  = ($chk_supply_topic42)? $checkAry['Y'] : $checkAry['N'];
        //有機溶劑作業主管
        $chk_supply_topic44  = $this->getApiWorkPermitTopicOptionIdentity($work_id,32,1);
        if(is_array($chk_supply_topic44)) $chk_supply_topic44 = '';
        $chk_supply_topic45  = ($chk_supply_topic44)? $checkAry['Y'] : $checkAry['N'];

        //監造人員簽名
        $sign_url2          = '';
        $chk_supply_topic50 = ($sign_url2)? $checkAry['Y'] : $checkAry['N'];

        //監造人員簽名
        $sign_url3          = wp_work_topic_a::getTopicAns($work_id,40);
        $sign_url3_at       = wp_work_topic_a::getTopicAnsAt($work_id,40);
        $chk_supply_topic51 = ($sign_url3)? $checkAry['Y'] : $checkAry['N'];
        $chk_supply_topic51a = b_cust_a::getMobile($workData->supply_safer);

        //監造人員簽名
        $sign_url4          = wp_work_topic_a::getTopicAns($work_id,74);
        $sign_url4_at       = wp_work_topic_a::getTopicAnsAt($work_id,74);
        $chk_supply_topic52a= b_cust_a::getMobile($workData->supply_worker);

        //其他主管
        $chk_supply_topic55  = $this->getApiWorkPermitOtherIdentity($work_id);
        if(is_array($chk_supply_topic55)) $chk_supply_topic55 = '';
        $chk_supply_topic54  = ($chk_supply_topic55)? $checkAry['Y'] : $checkAry['N'];

        $chk_emp_topic2  = wp_work_topic_a::getTopicAns($work_id,42);
        $chk_emp_topic1  = ($chk_emp_topic2)? $checkAry['Y'] : $checkAry['N'];

        $chk_emp_topic3  = wp_work_topic_a::getTopicAns($work_id,78);
        $chk_emp_topic3a = isset($checkAry[$chk_emp_topic3])? $checkAry[$chk_emp_topic3] : $checkAry['N'];

        $chk_emp_topic5  = wp_work_topic_a::getTopicAns($work_id,43);
        $chk_emp_topic5a = isset($checkAry[$chk_emp_topic5])? $checkAry[$chk_emp_topic5] : $checkAry['N'];
        $chk_emp_topic6  = wp_work_topic_a::getTopicAns($work_id,44);
        $chk_emp_topic6a = isset($checkAry[$chk_emp_topic6])? $checkAry[$chk_emp_topic6] : $checkAry['N'];
        $chk_emp_topic7  = wp_work_topic_a::getTopicAns($work_id,148);
        $chk_emp_topic7a = isset($checkAry[$chk_emp_topic7])? $checkAry[$chk_emp_topic7] : $checkAry['N'];
        $chk_emp_topic8  = wp_work_topic_a::getTopicAns($work_id,45);
        $chk_emp_topic8a = isset($checkAry[$chk_emp_topic8])? $checkAry[$chk_emp_topic8] : $checkAry['N'];
        if($chk_emp_topic5 == 'Y' || $chk_emp_topic6 == 'Y' || $chk_emp_topic7 == 'Y' || $chk_emp_topic8 == 'Y')
        {
            $chk_emp_topic4 = $checkAry['Y'];
        } else {
            $chk_emp_topic4 = $checkAry['N'];
        }


        $chk_emp_topic9  = wp_work_topic_a::getTopicAns($work_id,80);
        $chk_emp_topic9a = isset($checkAry[$chk_emp_topic9])? $checkAry[$chk_emp_topic9] : $checkAry['N'];
        $chk_emp_topic10 = wp_work_topic_a::getTopicAns($work_id,81);
        $chk_emp_topic10a= isset($checkAry[$chk_emp_topic10])? $checkAry[$chk_emp_topic10] : $checkAry['N'];

        $chk_emp_topic11  = wp_work_topic_a::getTopicAns($work_id,82);
        $chk_emp_topic11a = isset($checkAry[$chk_emp_topic11])? $checkAry[$chk_emp_topic11] : $checkAry['N'];
        $chk_emp_topic12  = wp_work_topic_a::getTopicAns($work_id,83);
        $chk_emp_topic12a = isset($checkAry[$chk_emp_topic12])? $checkAry[$chk_emp_topic12] : $checkAry['N'];

        $chk_emp_topic13  = wp_work_topic_a::getTopicAns($work_id,46);
        $chk_emp_topic13a = isset($checkAry[$chk_emp_topic13])? $checkAry[$chk_emp_topic13] : $checkAry['N'];
        $chk_emp_topic14  = wp_work_topic_a::getTopicAns($work_id,47);
        $chk_emp_topic14a = isset($checkAry[$chk_emp_topic14])? $checkAry[$chk_emp_topic14] : $checkAry['N'];
        $chk_emp_topic15  = wp_work_topic_a::getTopicAns($work_id,48);
        $chk_emp_topic15a = isset($checkAry[$chk_emp_topic15])? $checkAry[$chk_emp_topic15] : $checkAry['N'];
        $chk_emp_topic16  = wp_work_topic_a::getTopicAns($work_id,49);
        $chk_emp_topic16a = isset($checkAry[$chk_emp_topic16])? $checkAry[$chk_emp_topic16] : $checkAry['N'];
        $chk_emp_topic18  = wp_work_topic_a::getTopicAns($work_id,50);
        $chk_emp_topic17  = $chk_emp_topic18 ? $checkAry['Y'] : $checkAry['N'];
        if($chk_emp_topic13 == 'Y' || $chk_emp_topic14 == 'Y' || $chk_emp_topic15 == 'Y' || $chk_emp_topic16 == 'Y' || $chk_emp_topic18)
        {
            $chk_emp_topic19 = $checkAry['Y'];
        } else {
            $chk_emp_topic19 = $checkAry['N'];
        }

        $chk_emp_topic20  = wp_work_topic_a::getTopicAns($work_id,51);
        $chk_emp_topic21  = wp_work_topic_a::getTopicAns($work_id,52);
        $chk_emp_topic22  = wp_work_topic_a::getTopicAns($work_id,53);
        if($chk_emp_topic20 || $chk_emp_topic21 || $chk_emp_topic22 )
        {
            $chk_emp_topic23 = $checkAry['Y'];
        } else {
            $chk_emp_topic23 = $checkAry['N'];
        }
        $chk_emp_topic24 = $checkAry['Y'];
        $sign_url5          = wp_work_topic_a::getTopicAns($work_id,57);
        $sign_url5_at       = wp_work_topic_a::getTopicAnsAt($work_id,57);
        $sign_url6          = wp_work_topic_a::getTopicAns($work_id,58);
        $sign_url6_at       = wp_work_topic_a::getTopicAnsAt($work_id,58);
        $sign_url7          = wp_work_topic_a::getTopicAns($work_id,62);
        $sign_url7_at       = wp_work_topic_a::getTopicAnsAt($work_id,62);

        $chk_emp_topic30 = ($permit_danger == 'A')? $checkAry['Y'] : $checkAry['N'];
        $chk_emp_topic31 = ($permit_danger != 'A')? $checkAry['Y'] : $checkAry['N'];
        $chk_emp_topic32 = wp_work_topic_a::getTopicAns($work_id,85);
        $chk_emp_topic33a = wp_work_topic_a::getTopicAns($work_id,122);
        $chk_emp_topic33b = wp_work_topic_a::getTopicAns($work_id,123);
        $chk_emp_topic33c = wp_work_topic_a::getTopicAns($work_id,124);
        $chk_emp_topic33d = wp_work_topic_a::getTopicAns($work_id,164);
        $chk_emp_topic33e = wp_work_topic_a::getTopicAns($work_id,165); //2019-11-20 監造
        $chk_emp_topic33j = wp_work_topic_a::getTopicAns($work_id,179); //2019-12-10 監造
        $chk_emp_topic33f = wp_work_topic_a::getTopicAns($work_id,167); //2019-11-20 轄區負責人
        $chk_emp_topic33g = wp_work_topic_a::getTopicAns($work_id,168); //2019-11-20 轄區負責人
        $chk_emp_topic33h = ($chk_emp_topic33f)? $chk_emp_topic33f :'';
        $chk_emp_topic33h.= ($chk_emp_topic33g)? (strlen($chk_emp_topic33h)? '，' : '').$chk_emp_topic33g :'';
        $chk_emp_topic33i = ($chk_emp_topic33e)? $chk_emp_topic33e :'';
        $chk_emp_topic33i.= ($chk_emp_topic33j)? (strlen($chk_emp_topic33j)? '，' : '').$chk_emp_topic33j :'';

        $chk_emp_topic33 = '轄區：'.$chk_emp_topic33a.'<br>連繫者：'.$chk_emp_topic33b.'<br/> 複檢者：'.$chk_emp_topic33c;
        if($chk_emp_topic33i) $chk_emp_topic33 .= '<br>監造：'.$chk_emp_topic33i;
        if($chk_emp_topic33h) $chk_emp_topic33 .= '<br>轄區負責人：'.$chk_emp_topic33h;
        if($chk_emp_topic33d) $chk_emp_topic33 .= '<br>主簽者：'.$chk_emp_topic33d;

        $chk_emp_topic34 = wp_work_topic_a::getTopicAns($work_id,125);
        $chk_emp_topic34a = isset($checkAry[$chk_emp_topic34])? $checkAry[$chk_emp_topic34] : $checkAry['N'];
        $chk_emp_topic35 = wp_work_topic_a::getTopicAns($work_id,149);
        $chk_emp_topic35a = isset($checkAry[$chk_emp_topic35])? $checkAry[$chk_emp_topic35] : $checkAry['N'];
        $chk_emp_topic36 = wp_work_topic_a::getTopicAns($work_id,126);
        $chk_emp_topic36a = isset($checkAry[$chk_emp_topic36])? $checkAry[$chk_emp_topic36] : $checkAry['N'];
        $chk_emp_topic37a = wp_work_topic_a::getTopicAns($work_id,127);
        $chk_emp_topic37b = wp_work_topic_a::getTopicAns($work_id,133);
        $chk_emp_topic37c = wp_work_topic_a::getTopicAns($work_id,134);
        $chk_emp_topic37d = wp_work_topic_a::getTopicAns($work_id,163);
        $chk_emp_topic37f = wp_work_topic_a::getTopicAns($work_id,169); //2019-11-20 轄區負責人
        $chk_emp_topic37g = wp_work_topic_a::getTopicAns($work_id,170); //2019-11-20 轄區負責人
        $chk_emp_topic37h = ($chk_emp_topic37f)? $chk_emp_topic37f :'';
        $chk_emp_topic37h.= ($chk_emp_topic37g)? (strlen($chk_emp_topic37h)? '，' : '').$chk_emp_topic37g :'';
        $chk_emp_topic37 = '監工：'.$chk_emp_topic37a.'<br>承商：'.$chk_emp_topic37b.'<br/>轄區：'.$chk_emp_topic37c;
        if($chk_emp_topic37h) $chk_emp_topic37 .= '<br>轄區負責人：'.$chk_emp_topic37h;
        if($chk_emp_topic37d) $chk_emp_topic37 .= '<br>主簽者：'.$chk_emp_topic37d;


        $sign_url8          = wp_work_topic_a::getTopicAns($work_id,130);
        $sign_url8_at       = wp_work_topic_a::getTopicAnsAt($work_id,130);
        $chk_emp_topic38 = ($sign_url8)? $checkAry['Y'] : $checkAry['N'];
        $sign_url9          = wp_work_topic_a::getTopicAns($work_id,128);
        $sign_url9_at       = wp_work_topic_a::getTopicAnsAt($work_id,128);
        $chk_emp_topic39 = ($sign_url9)? $checkAry['Y'] : $checkAry['N'];
        $sign_url10         = wp_work_topic_a::getTopicAns($work_id,129);
        $sign_url10_at      = wp_work_topic_a::getTopicAnsAt($work_id,129);
        $chk_emp_topic40 = ($sign_url10)? $checkAry['Y'] : $checkAry['N'];

        $sign_url14         = wp_work_topic_a::getTopicAns($work_id,65); //會簽主簽人簽章
        $sign_url14_at      = wp_work_topic_a::getTopicAnsAt($work_id,65); //
        $sign_url15         = wp_work_topic_a::getTopicAns($work_id,166); //轄區負責人簽章
        $sign_url15_at      = wp_work_topic_a::getTopicAnsAt($work_id,166); //
        $sign_url16         = wp_work_topic_a::getTopicAns($work_id,67); //轄區主簽人簽章
        $sign_url16_at      = wp_work_topic_a::getTopicAnsAt($work_id,67); //
        $sign_url17         = wp_work_topic_a::getTopicAns($work_id,131);//經理簽章
        $sign_url17_at      = wp_work_topic_a::getTopicAnsAt($work_id,131);//
        $sign_url18         = wp_work_topic_a::getTopicAns($work_id,90); //承攬商收工申請
        $sign_url18_at      = wp_work_topic_a::getTopicAnsAt($work_id,90); //
        $sign_url19         = wp_work_topic_a::getTopicAns($work_id,92); //轄區同意收工
        $sign_url19_at      = wp_work_topic_a::getTopicAnsAt($work_id,92);

        $chk_emp_topic50    = wp_work_topic_a::getTopicAns($work_id,120);
        $chk_emp_topic50    = ($chk_emp_topic50)? date('H:i',strtotime($chk_emp_topic50)) : '';

        //開始時間
        if($chk_emp_topic50)
        {
            $topic50Ary         = explode(':',$chk_emp_topic50);
            if($topic50Ary[0] > 12)  $chk_emp_topic50 = '下午 13:00 至 '.$chk_emp_topic50;
            if($topic50Ary[0] <= 12) $chk_emp_topic50 = '上午 '.$chk_emp_topic50.'至 12:00';
            $chk_emp_topic50    = $sdate.$chk_emp_topic50;
        }

        //結束時間
        $chk_emp_topic51    = wp_work_topic_a::getTopicAns($work_id,121);
        $chk_emp_topic51    = ($chk_emp_topic51)? date('H:i',strtotime($chk_emp_topic51)) : '';
        if($chk_emp_topic51)
        {
            $topic51Ary     = explode(':',$chk_emp_topic51);
            if($topic51Ary[0] > 12)  $chk_emp_topic51 = '下午 13:00 至 '.$chk_emp_topic51;
            if($topic51Ary[0] <= 12) $chk_emp_topic51 = '上午 '.$chk_emp_topic51.'至 12:00';
            $chk_emp_topic51    = $sdate.$chk_emp_topic51;
        }
        $chk_emp_topic52    = ($sign_url19)? $checkAry['Y'] : $checkAry['N'];


        //承攬商 檢點表
        $chk_supply_topic99    = wp_work_topic_a::getTopicAns($work_id,24,$imgResize);
        $chk_supply_topic100   = wp_work_topic_a::getTopicAnsAt($work_id,24);
        $chk_supply_topic101   = isset($chk_supply_topic99[3])?    $chk_supply_topic99[3] : '';
        $chk_supply_topic102   = isset($chk_supply_topic99[4])?    $chk_supply_topic99[4] : '';
        $chk_supply_topic103   = isset($chk_supply_topic99[16])?   $chk_supply_topic99[16] : '';
        $chk_supply_topic104   = isset($chk_supply_topic99[17])?   $chk_supply_topic99[17] : '';
        $chk_supply_topic105   = isset($chk_supply_topic99[5])?    $chk_supply_topic99[5] : '';
        $chk_supply_topic106   = isset($chk_supply_topic99[15])?   $chk_supply_topic99[15] : '';
        $chk_supply_topic107   = isset($chk_supply_topic99[8])?    $chk_supply_topic99[8] : '';//氣體偵測拍照
        $chk_supply_topic108a  = wp_work_topic_a::getTopicAns($work_id,40);
        $chk_supply_topic108   = $chk_supply_topic108a ? '<img src="'.$chk_supply_topic108a.'" class="sign_img" width="80"   height="25">' : '';
        //dd($chk_supply_topic99);

        //職員 檢點表
        $chk_emp_topic99    = wp_work_topic_a::getTopicAns($work_id,54);
        $chk_emp_topic100   = wp_work_topic_a::getTopicAnsAt($work_id,54);
        $chk_emp_topic101   = isset($chk_emp_topic99[41])?    $chk_emp_topic99[41]   : '';
        $chk_emp_topic102   = isset($chk_emp_topic99[42])?    $chk_emp_topic99[42]   : '';
        $chk_emp_topic103   = isset($chk_emp_topic99[43])?    $chk_emp_topic99[43]  : '';
        $chk_emp_topic104   = isset($chk_emp_topic99[44])?    $chk_emp_topic99[44]  : '';
        $chk_emp_topic105   = isset($chk_emp_topic99[45])?    $chk_emp_topic99[45]   : '';
        $chk_emp_topic106   = isset($chk_emp_topic99[46])?    $chk_emp_topic99[46]  : '';
        $chk_emp_topic107   = isset($chk_emp_topic99[47])?     $chk_emp_topic99[47]   : '';//氣體偵測拍照
        $chk_emp_topic108a  = wp_work_topic_a::getTopicAns($work_id,57);
        $chk_emp_topic108   = $chk_emp_topic108a ? '<img src="'.$chk_emp_topic108a.'" class="sign_img" width="80"   height="25">' : '';


        //危害告知
        $chk_danger_topic1  = wp_work_topic_a::getTopicAns($work_id,97);
        $chk_danger_topic1  = ($chk_danger_topic1 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic2  = wp_work_topic_a::getTopicAns($work_id,98);
        $chk_danger_topic2  = ($chk_danger_topic2 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic3  = wp_work_topic_a::getTopicAns($work_id,99);
        $chk_danger_topic3  = ($chk_danger_topic3 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic4  = wp_work_topic_a::getTopicAns($work_id,100);
        $chk_danger_topic4  = ($chk_danger_topic4 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic5  = wp_work_topic_a::getTopicAns($work_id,101);
        $chk_danger_topic5  = ($chk_danger_topic5 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic6  = wp_work_topic_a::getTopicAns($work_id,102);
        $chk_danger_topic6  = ($chk_danger_topic6 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic7  = wp_work_topic_a::getTopicAns($work_id,103);
        $chk_danger_topic7  = ($chk_danger_topic7 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic8  = wp_work_topic_a::getTopicAns($work_id,104);
        $chk_danger_topic8  = ($chk_danger_topic8 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic9  = wp_work_topic_a::getTopicAns($work_id,105);
        $chk_danger_topic9  = ($chk_danger_topic9 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic10 = wp_work_topic_a::getTopicAns($work_id,106);
        $chk_danger_topic10 = ($chk_danger_topic10 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic11 = wp_work_topic_a::getTopicAns($work_id,107);
        $chk_danger_topic11 = ($chk_danger_topic11 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic12 = wp_work_topic_a::getTopicAns($work_id,108);
        $chk_danger_topic12 = ($chk_danger_topic12 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic13 = wp_work_topic_a::getTopicAns($work_id,109);
        $chk_danger_topic13 = ($chk_danger_topic13 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic14 = wp_work_topic_a::getTopicAns($work_id,110);
        $chk_danger_topic14 = ($chk_danger_topic14 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic15 = wp_work_topic_a::getTopicAns($work_id,111);
        $chk_danger_topic15 = ($chk_danger_topic15 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic16 = wp_work_topic_a::getTopicAns($work_id,112);
        $chk_danger_topic16 = ($chk_danger_topic16 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic17 = wp_work_topic_a::getTopicAns($work_id,113);
        $chk_danger_topic17 = ($chk_danger_topic17 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic18 = wp_work_topic_a::getTopicAns($work_id,114);
        $chk_danger_topic18 = ($chk_danger_topic18 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic19 = wp_work_topic_a::getTopicAns($work_id,115);
        $chk_danger_topic19 = ($chk_danger_topic19 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic20 = wp_work_topic_a::getTopicAns($work_id,116);
        $chk_danger_topic20 = ($chk_danger_topic20 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic21 = wp_work_topic_a::getTopicAns($work_id,117);
        $chk_danger_topic21 = ($chk_danger_topic21 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic22 = wp_work_topic_a::getTopicAns($work_id,118);
        $chk_danger_topic22 = ($chk_danger_topic22 == 'Y')? $checkAry['Y'] : $checkAry['N'];
        $chk_danger_topic23 = wp_work_topic_a::getTopicAns($work_id,5); //職安衛簽名
        $chk_danger_topic23a= ($chk_danger_topic23)? '<img src="'.$chk_danger_topic23.'" class="sign_img" width="80"  height="25">' : '';

        $chk_danger_topic24 = wp_work_topic_a::getTopicAnsAt($work_id,5); //職安衛簽名時間
        $chk_danger_topic24a= date('Y 年 m 月 d 日  H 點 i 分',strtotime($chk_danger_topic24));

        //圖片記錄
        $chk_photo_topic = $chk_photo_topic2 = $tmpPhoto = [];
        //環境檢點表
        $total_max_row  = 9;
        $chk_tip_topic  = [];
        $chk_tip_topic1 = wp_work_check_topic::getCheckTopicAns($work_id,3,$imgResize); //承攬商/廠方施工部門作業環境紀錄表
        $chk_tip_topic2 = wp_work_check_topic::getCheckTopicAns($work_id,4,$imgResize); //轄區施工部門作業環境紀錄表
        $chk_tip_topic3 = wp_work_check_topic::getCheckTopicAns($work_id,2,$imgResize); //巡邏

        $chk_tip_topic1_amt = count($chk_tip_topic1);
        $chk_tip_topic2_amt = count($chk_tip_topic2);
        $chk_tip_topic3_amt = count($chk_tip_topic3);
        $chk_tip_topic_total_amt = ($chk_tip_topic1_amt > $chk_tip_topic2_amt) ? $chk_tip_topic1_amt : $chk_tip_topic2_amt;
        $chk_tip_topic_total_amt = ($chk_tip_topic3_amt > $chk_tip_topic_total_amt) ? $chk_tip_topic3_amt : $chk_tip_topic_total_amt;
        $chk_tip_topic_count_amt = intval(ceil($chk_tip_topic_total_amt / $total_max_row));
        if(!$chk_tip_topic_count_amt) $chk_tip_topic_count_amt = 1;
        //承攬商/廠方施工部門作業環境紀錄表
        if(count($chk_tip_topic1))
        {
            $j1 = 1;
            foreach ( $chk_tip_topic1 as $val )
            {
                $i1 = 1;
                if(!isset($val['ans'])) continue;
                $ans   = $val['ans'];
                $sign  = $val['record_sign'];
                $stamp = $val['record_stamp'];
                $tmp = [];
                $tmp[1] = $stamp;
                $tmp[2] = isset($ans[19])? $ans[19] : '';
                $tmp[3] = isset($ans[20])? $ans[20] : '';
                $tmp[4] = isset($ans[21])? $ans[21] : '';
                $tmp[5] = isset($ans[22])? $ans[22] : '';
                $tmp[6] = $sign ? '<img src="'.$sign.'" class="sign_img" width="80" height="25">' : '';
                $chk_tip_topic[$j1][1][] =$tmp;
                $i1++;
                if($i1 >= 9) $j1++;
                //作業環境紀錄表圖片
                $chk_img_tmp         = isset($ans[25])? $ans[25] : '';
                $tmpPhoto['title']   = '承攬商作業環境紀錄表'.$stamp;
                $tmpPhoto['img']     = ($chk_img_tmp)? '<img src="'.$chk_img_tmp.'" class="chk_img" >' : '';
                $chk_photo_topic2[]   = $tmpPhoto;
            }
        }
        //轄區施工部門作業環境紀錄表
        if(count($chk_tip_topic2))
        {
            $j2 = 1;
            foreach ( $chk_tip_topic2 as $val )
            {
                $i2 = 1;
                if(!isset($val['ans'])) continue;
                $ans   = $val['ans'];
                $sign  = $val['record_sign'];
                $stamp = $val['record_stamp'];
                $tmp = [];
                $tmp[1] = $stamp;
                $tmp[2] = isset($ans[30])? $ans[30] : '';
                $tmp[3] = isset($ans[31])? $ans[31] : '';
                //$tmp[4] = isset($ans[34])? (($ans[34])? ($ans[34] . (($ans[35])? ('-'. $ans[35]) : '' )) : '') : '';
                $tmp[4] = isset($ans[32])? $ans[32] : '';
                $tmp[5] = isset($ans[33])? $ans[33] : '';
                $tmp[6] = $sign ? '<img src="'.$sign.'" class="sign_img" width="80" height="25">' : '';
                $chk_tip_topic[$j2][2][] =$tmp;
                $i2++;
                if($i2 >= 9) $j2++;
                //作業環境紀錄表圖片
                $chk_img_tmp          = isset($ans[36])? $ans[36] : '';
                $tmpPhoto['title']    = '轄區作業環境紀錄表'.$stamp;
                $tmpPhoto['img']      = ($chk_img_tmp)? '<img src="'.$chk_img_tmp.'" class="chk_img" >' : '';
                $chk_photo_topic2[]   = $tmpPhoto;
            }
        }
        //巡邏
        if(count($chk_tip_topic3))
        {
            $j3 = 1;
            foreach ( $chk_tip_topic3 as $val )
            {
                $i3 = 1;
                if(!isset($val['ans'])) continue;
                $dept = $val['record_dept'];
                $ans  = $val['ans'];
                $sign = $val['record_sign'];
                $stamp= $val['record_stamp'];
                $tmp = [];
                $tmp[1] = $dept;
                $tmp[2] = $stamp;
                $tmp[3] = isset($sign)? '<img src="'.$sign.'" class="sign_img" width="80" height="25">' : '';
                $tmp[4] = isset($ans[12])? $ans[12] : '';
                $chk_tip_topic[$j3][3][] =$tmp;
                $i3++;
                if($i3 >= 9) $j3++;
                //作業環境紀錄表圖片
                $chk_img_tmp         = isset($ans[10])? $ans[10] : '';
                $tmpPhoto['title']   = '巡查紀錄表'.$stamp;
                $tmpPhoto['img']     = ($chk_img_tmp)? '<img src="'.$chk_img_tmp.'" class="chk_img" >' : '';
                $chk_photo_topic2[]   = $tmpPhoto;
            }
        }
        //補足 九筆
        for ($i = 1; $i <= $chk_tip_topic_count_amt;$i++)
        {
            if(!isset($chk_tip_topic[$i])) $chk_tip_topic[$i] = [];
            if(!isset($chk_tip_topic[$i][1])) $chk_tip_topic[$i][1] = [];
            if(!isset($chk_tip_topic[$i][2])) $chk_tip_topic[$i][2] = [];
            if(!isset($chk_tip_topic[$i][3])) $chk_tip_topic[$i][3] = [];
            $tipCont1 = count($chk_tip_topic[$i][1]);
            if($tipCont1 < $total_max_row)
            {
                for($j = 1; $j <= intval($total_max_row - $tipCont1); $j++)
                {
                    $chk_tip_topic[$i][1][] = [];
                }
            }


            $tipCont2 = count($chk_tip_topic[$i][2]);
            if($tipCont2 < $total_max_row)
            {
                for($j = 1; $j <= intval($total_max_row - $tipCont2); $j++)
                {
                    $chk_tip_topic[$i][2][] = [];
                }
            }
            $tipCont3 = count($chk_tip_topic[$i][3]);
            if($tipCont3 < $total_max_row)
            {
                for($j = 1; $j <= intval($total_max_row - $tipCont3); $j++)
                {
                    $chk_tip_topic[$i][3][] = [];
                }
            }
        }
//        dd($chk_tip_topic_total_amt,$chk_tip_topic_count_amt,$chk_tip_topic1,$chk_tip_topic2,$chk_tip_topic3,$chk_tip_topic);

        //承攬商檢點照片
        $chk_photo_topic_tmp = wp_work_topic_a::getTopicAns($work_id,37,$imgResize); //施工人員合照
        $tmpPhoto['title']   = '施工人員合照';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        $chk_photo_topic_tmp = wp_work_topic_a::getTopicAns($work_id,38,$imgResize); //施工前環境拍照
        $tmpPhoto['title']   = '施工前環境拍照';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        $chk_photo_topic_tmp = wp_work_topic_a::getTopicAns($work_id,150,$imgResize); //施工前環境拍照
        $tmpPhoto['title']   = '施工前工安勤前教育紀錄';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        $tmpPhoto['title']   = '承攬商氣體偵測';
        $tmpPhoto['img']     = ($chk_supply_topic107)? '<img src="'.$chk_supply_topic107.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        //承攬商 附加檢點表
        //局限空間
        if(isset($workCheckAry[2]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,132,$imgResize); //大林煉油廠局限空間作業檢點表
            $tmpPhoto['title']   = '大林煉油廠局限空間作業檢點表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;

            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,136,$imgResize); //大林煉油廠局限空間人員進出時間簽名表
            $tmpPhoto['title']   = '大林煉油廠局限空間人員進出時間簽名表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }
        //閉鎖及標識
        if(isset($workCheckAry[3]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,137,$imgResize); //大林煉油廠閉鎖、標識 或拆裝盲板作業檢點表
            $tmpPhoto['title']   = '大林煉油廠閉鎖、標識 或拆裝盲板作業檢點表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }
        //起重吊掛
        if(isset($workCheckAry[4]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,138,$imgResize); //大林煉油廠起重吊掛作業檢點表
            $tmpPhoto['title']   = '大林煉油廠起重吊掛作業檢點表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }
        //電器活線
        if(isset($workCheckAry[5]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,139,$imgResize); //區間管線施工前吹驅隔離內容物確認檢點表
            $tmpPhoto['title']   = '區間管線施工前吹驅隔離內容物確認檢點表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }
        //高架作業
        if(isset($workCheckAry[6]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,140,$imgResize); //高架作業檢點表
            $tmpPhoto['title']   = '高架作業檢點表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }
        //開挖作業
        if(isset($workCheckAry[7]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,141,$imgResize); //大林煉油廠開挖作業檢點表
            $tmpPhoto['title']   = '大林煉油廠開挖作業檢點表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }
        //施工架組裝
        if(isset($workCheckAry[8]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,142,$imgResize); //大林煉油廠施工架組配及使用作業檢點表
            $tmpPhoto['title']   = '大林煉油廠施工架組配及使用作業檢點表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }
        //模板支撐，擋土支撐，鋼構組配作業
        if(isset($workCheckAry[9]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,143,$imgResize); //大林煉油廠閉鎖、標識 或拆裝盲板作業檢點表
            $tmpPhoto['title']   = '大林煉油廠閉鎖、標識 或拆裝盲板作業檢點表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }
        //游離輻射
        if(isset($workCheckAry[10]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,144,$imgResize); //大林煉油廠游離輻射作業檢點表
            $tmpPhoto['title']   = '大林煉油廠游離輻射作業檢點表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }
        //高空工作車作業
        if(isset($workCheckAry[11]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,145,$imgResize); //高空工作車作業環境危害因素及安全衛生告知單
            $tmpPhoto['title']   = '高空工作車作業環境危害因素及安全衛生告知單';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }
        //吊籠作業
        if(isset($workCheckAry[12]))
        {
            $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,146,$imgResize); //吊籠作業檢點表
            $tmpPhoto['title']   = '吊籠作業檢點表';
            $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
            $chk_photo_topic[]   = $tmpPhoto;
        }

        //轄區拍照
        $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,55,$imgResize); //現場環境拍照（一）
        $tmpPhoto['title']   = '現場環境拍照（一）';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;
        $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,84,$imgResize); //現場環境拍照（二）
        $tmpPhoto['title']   = '現場環境拍照（二）';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;
        $tmpPhoto['title']   = '轄區氣體偵測';
        $tmpPhoto['img']     = ($chk_emp_topic107)? '<img src="'.$chk_emp_topic107.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        //施工中
        if(count($chk_photo_topic2))
        {
            foreach ($chk_photo_topic2 as $value)
            {
                $chk_photo_topic[] = $value;
            }
        }


        //收工拍照
        $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,88,$imgResize); //收工環境紀錄（一）
        $tmpPhoto['title']   = '收工環境紀錄（一）';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;
        $chk_photo_topic_tmp  = wp_work_topic_a::getTopicAns($work_id,89,$imgResize); //收工環境紀錄（二）
        $tmpPhoto['title']   = '收工環境紀錄（二）';
        $tmpPhoto['img']     = ($chk_photo_topic_tmp)? '<img src="'.$chk_photo_topic_tmp.'" class="chk_img" >' : '';
        $chk_photo_topic[]   = $tmpPhoto;

        $show_max_photo         = 6;
        $chk_photo_topic_ary    = [];
        $chk_photo_topic_ary[1] = [];
        $chk_photo_cont         = intval(ceil(count($chk_photo_topic)/$show_max_photo));
        if(!$chk_photo_cont) $chk_photo_cont = 1;
        $i = 1;
        $j = 0;
        foreach ($chk_photo_topic as $val)
        {
            $j++;
            $chk_photo_topic_ary[$i][$j] = $val;

            if($j == $show_max_photo)
            {
                $j = 0;
                $i++;
            }
        }
        //補足
        for($i = 1;$i <= $chk_photo_cont; $i++)
        {
            if(!isset($chk_photo_topic_ary[$i])) $chk_photo_topic_ary[$i] = [];
            $photoCont = count($chk_photo_topic_ary[$i]);
            if($photoCont < $show_max_photo)
            {
                for($j = 1; $j <= intval($show_max_photo - $photoCont); $j++)
                {
                    $chk_photo_topic_ary[$i][] = ['img'=>'','title'=>''];
                }
            }
        }

//        dd($chk_photo_topic_ary);

        /*
         * ============================================
         */

        if(1)
        {
            $showAry = [];
            $showAry['work_id']         = $work_id;
            $showAry['last_updated_at'] = $last_updated_format;
            $showAry['qrcode']          = 'data:image/png;base64,'.DNS2D::getBarcodePNG($permit_no, "QRCODE");
            $showAry['apply_date']      = $apply_format;
            $showAry['permit_no']       = $permit_no;
            $showAry['work_time']       = $sdate.' 上午 08時00分 至上午12時00分 下午13時00分 至下午17時 00分';
            $showAry['permit_danger_a'] = $permit_dangerA;
            $showAry['permit_danger_b'] = $permit_dangerB;
            $showAry['permit_danger_c'] = $permit_dangerC;
            $showAry['dept_name1']      = SHCSLib::genReportAnsHtml($be_dept_id1,8);
            $showAry['dept_name2']      = SHCSLib::genReportAnsHtml($be_dept_id2,8);
            $showAry['dept_name3']      = SHCSLib::genReportAnsHtml($be_dept_id3,8);
            $showAry['dept_name4']      = SHCSLib::genReportAnsHtml($be_dept_id4,8);
            $showAry['dept_name5']      = SHCSLib::genReportAnsHtml($be_dept_id5,8);
            $showAry['supply']          = $b_supply;
            $showAry['supply_men']      = SHCSLib::genReportAnsHtml($b_supply_worker_amt,8);
            $showAry['supply_car']      = SHCSLib::genReportAnsHtml($car_no,8);
            $showAry['work_place']      = SHCSLib::genReportAnsHtml($work_place,30);
            $showAry['project_no']      = $project_no;
            $showAry['work_memo']       = SHCSLib::genReportAnsHtml($work_memo,40);
            $showAry['chk_isOvertime']  = $isOvertime;
            $showAry['permit_check']    = SHCSLib::genReportAnsHtml($workCheckStr,60);
            $showAry['chk_workitem'][1] = $workitem_1;
            $showAry['chk_workitem'][2] = $workitem_2;
            $showAry['chk_workitem'][3] = $workitem_3;
            $showAry['chk_workitem'][4] = $workitem_4;
            $showAry['chk_workitem'][5] = $workitem_5;
            $showAry['chk_workitem'][6] = $workitem_6;
            $showAry['chk_workitem'][7] = $workitem_7;
            $showAry['chk_workitem'][8] = $workitem_8;
            $showAry['chk_workitem'][9] = $workitem_9;
            $showAry['chk_workitem'][10] = SHCSLib::genReportAnsHtml($workitem_10,5);
            $showAry['chk_workitem'][20] = $workitem_20;
            $showAry['chk_workitem'][21] = $workitem_21;
            $showAry['chk_workitem'][22] = $workitem_22;
            $showAry['chk_workitem'][23] = $workitem_23;
            $showAry['chk_workitem'][24] = $workitem_24;
            $showAry['chk_workitem'][25] = $workitem_25;
            $showAry['chk_workitem'][26] = $workitem_26;
            $showAry['chk_workitem'][27] = SHCSLib::genReportAnsHtml($workitem_27,5);
            $showAry['chk_workitem'][30] = $workitem_30;
            $showAry['chk_workitem'][31] = $workitem_31;
            $showAry['chk_workitem'][32] = $workitem_32;
            $showAry['chk_workitem'][33] = SHCSLib::genReportAnsHtml($workitem_33,5);

            $showAry['chk_workline'][1] = $workline_1;
            $showAry['chk_workline'][11]= SHCSLib::genReportAnsHtml($workline_11,8);
            $showAry['chk_workline'][2] = $workline_2;
            $showAry['chk_workline'][3] = $workline_3;
            $showAry['chk_workline'][4] = $workline_4;
            $showAry['chk_workline'][5] = $workline_5;
            $showAry['chk_workline'][6] = $workline_6;
            $showAry['chk_workline'][7] = $workline_7;
            $showAry['chk_workline'][8] = $workline_8;
        }

        /*
         * ============================================
         */

        if(2)
        {
            $showAry['sign_url1']        = ($sign_url1)? '<img src="'.$sign_url1.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url1_at,11,5).'</span>' : ''; //監造人員
            $showAry['sign_url1']       .= $sign_agent1;//監造人員

            $showAry['chk_supply_topic'][1] = $chk_supply_topic1a;
            $showAry['chk_supply_topic'][2] = $chk_supply_topic2a;
            $showAry['chk_supply_topic'][3] = $chk_supply_topic3a;
            $showAry['chk_supply_topic'][4] = $chk_supply_topic4a;
            $showAry['chk_supply_topic'][5] = $chk_supply_topic5a;
            $showAry['chk_supply_topic'][6] = $chk_supply_topic5;
            $showAry['chk_supply_topic'][7] = $chk_supply_topic7;
            $showAry['chk_supply_topic'][8] = $chk_supply_topic8a;
            $showAry['chk_supply_topic'][9] = $chk_supply_topic9a;
            $showAry['chk_supply_topic'][10] = $chk_supply_topic10;

            $showAry['chk_supply_topic'][11] = $chk_supply_topic11a;
            $showAry['chk_supply_topic'][12] = $chk_supply_topic12a;
            $showAry['chk_supply_topic'][13] = $chk_supply_topic13a;
            $showAry['chk_supply_topic'][14] = $chk_supply_topic14a;
            $showAry['chk_supply_topic'][15] = $chk_supply_topic15a;
            $showAry['chk_supply_topic'][16] = $chk_supply_topic16a;
            $showAry['chk_supply_topic'][17] = $chk_supply_topic17a;
            $showAry['chk_supply_topic'][18] = $chk_supply_topic18a;
            $showAry['chk_supply_topic'][19] = $chk_supply_topic19a;
            $showAry['chk_supply_topic'][20] = $chk_supply_topic20a;
            $showAry['chk_supply_topic'][21] = $chk_supply_topic21a;
            $showAry['chk_supply_topic'][22] = $chk_supply_topic22a;
            $showAry['chk_supply_topic'][23] = $chk_supply_topic23;
            $showAry['chk_supply_topic'][24] = $chk_supply_topic6a;

            $showAry['chk_supply_topic'][30] = SHCSLib::genReportAnsHtml($chk_supply_topic30,15);
            $showAry['chk_supply_topic'][31] = SHCSLib::genReportAnsHtml($chk_supply_topic31,15);
            $showAry['chk_supply_topic']['32a'] = $chk_supply_topic32a;
            $showAry['chk_supply_topic']['32b'] = $chk_supply_topic32b;

            $showAry['chk_supply_topic'][34] = SHCSLib::genReportAnsHtml($chk_supply_topic34,30);
            $showAry['chk_supply_topic'][35] = $chk_supply_topic35;

            $showAry['chk_supply_topic'][36] = SHCSLib::genReportAnsHtml($chk_supply_topic36,15);
            $showAry['chk_supply_topic'][37] = $chk_supply_topic37;

            $showAry['chk_supply_topic'][38] = SHCSLib::genReportAnsHtml($chk_supply_topic38,15);
            $showAry['chk_supply_topic'][39] = $chk_supply_topic39;

            $showAry['chk_supply_topic'][40] = SHCSLib::genReportAnsHtml($chk_supply_topic40,8);
            $showAry['chk_supply_topic'][41] = $chk_supply_topic41;

            $showAry['chk_supply_topic'][42] = SHCSLib::genReportAnsHtml($chk_supply_topic42,8);
            $showAry['chk_supply_topic'][43] = $chk_supply_topic43;

            $showAry['chk_supply_topic'][44] = SHCSLib::genReportAnsHtml($chk_supply_topic44,8);
            $showAry['chk_supply_topic'][45] = $chk_supply_topic45;

            $showAry['chk_supply_topic'][50] = $chk_supply_topic50;
            $showAry['chk_supply_topic'][51] = $chk_supply_topic51;
            $showAry['chk_supply_topic'][52] = $chk_supply_topic51a;
            $showAry['chk_supply_topic'][53] = $chk_supply_topic52a;
            $showAry['chk_supply_topic'][54] = $chk_supply_topic54;
            $showAry['chk_supply_topic'][55] = SHCSLib::genReportAnsHtml($chk_supply_topic55,8);


            $showAry['chk_supply_topic'][100] = $chk_supply_topic100;
            $showAry['chk_supply_topic'][101] = $chk_supply_topic101;
            $showAry['chk_supply_topic'][102] = $chk_supply_topic102;
            $showAry['chk_supply_topic'][103] = $chk_supply_topic103;
            $showAry['chk_supply_topic'][104] = $chk_supply_topic104;
            $showAry['chk_supply_topic'][105] = $chk_supply_topic105;
            $showAry['chk_supply_topic'][106] = $chk_supply_topic106;
            $showAry['chk_supply_topic'][107] = $chk_supply_topic107;
            $showAry['chk_supply_topic'][108] = $chk_supply_topic108;

            $showAry['sign_url2']        = ''; //監造人員
            $showAry['sign_url3']        = ($sign_url3)? '<img src="'.$sign_url3.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url3_at,11,5).'</span>' : ''; //監造人員
            $showAry['sign_url4']        = ($sign_url4)? '<img src="'.$sign_url4.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url4_at,11,5).'</span>' : ''; //監造人員

        }

        /*
         * ============================================
         */
        if(3)
        {
            $showAry['chk_emp_topic'][1] = $chk_emp_topic1;
            $showAry['chk_emp_topic'][2] = $chk_emp_topic2;
            $showAry['chk_emp_topic'][3] = $chk_emp_topic3a;
            $showAry['chk_emp_topic'][4] = $chk_emp_topic4;
            $showAry['chk_emp_topic'][5] = $chk_emp_topic5a;
            $showAry['chk_emp_topic'][6] = $chk_emp_topic6a;
            $showAry['chk_emp_topic'][7] = $chk_emp_topic7a;
            $showAry['chk_emp_topic'][8] = $chk_emp_topic8a;
            $showAry['chk_emp_topic'][9] = $chk_emp_topic9a;
            $showAry['chk_emp_topic'][10] = $chk_emp_topic10a;
            $showAry['chk_emp_topic'][11] = $chk_emp_topic11a;
            $showAry['chk_emp_topic'][12] = $chk_emp_topic12a;
            $showAry['chk_emp_topic'][13] = $chk_emp_topic13a;
            $showAry['chk_emp_topic'][14] = $chk_emp_topic14a;
            $showAry['chk_emp_topic'][15] = $chk_emp_topic15a;
            $showAry['chk_emp_topic'][16] = $chk_emp_topic16a;
            $showAry['chk_emp_topic'][17] = $chk_emp_topic17;
            $showAry['chk_emp_topic'][18] = $chk_emp_topic18;
            $showAry['chk_emp_topic'][19] = $chk_emp_topic19;
            $showAry['chk_emp_topic'][20] = SHCSLib::genReportAnsHtml($chk_emp_topic20,8);
            $showAry['chk_emp_topic'][21] = SHCSLib::genReportAnsHtml($chk_emp_topic21,8);
            $showAry['chk_emp_topic'][22] = SHCSLib::genReportAnsHtml($chk_emp_topic22,8);
            $showAry['chk_emp_topic'][23] = $chk_emp_topic23;
            $showAry['chk_emp_topic'][24] = $chk_emp_topic24;

            $showAry['chk_emp_topic'][30] = $chk_emp_topic30;
            $showAry['chk_emp_topic'][31] = $chk_emp_topic31;
            $showAry['chk_emp_topic'][32] = $chk_emp_topic32;
            $showAry['chk_emp_topic'][33] = $chk_emp_topic33;
            $showAry['chk_emp_topic'][34] = $chk_emp_topic34a;
            $showAry['chk_emp_topic'][35] = $chk_emp_topic35a;
            $showAry['chk_emp_topic'][36] = $chk_emp_topic36a;
            $showAry['chk_emp_topic'][37] = $chk_emp_topic37;
            $showAry['chk_emp_topic'][38] = $chk_emp_topic38;
            $showAry['chk_emp_topic'][39] = $chk_emp_topic39;
            $showAry['chk_emp_topic'][40] = $chk_emp_topic40;

            $showAry['chk_emp_topic'][50] = $chk_emp_topic50;
            $showAry['chk_emp_topic'][51] = $chk_emp_topic51;
            $showAry['chk_emp_topic'][52] = $chk_emp_topic52;

            $showAry['sign_url5']        = ($sign_url5)? '<img src="'.$sign_url5.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url5_at,11,5).'</span>' : ''; //監造人員
            $showAry['sign_url6']        = ($sign_url6)? '<img src="'.$sign_url6.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url6_at,11,5).'</span>' : ''; //監造人員
            $showAry['sign_url7']        = ($sign_url7)? '<img src="'.$sign_url7.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url7_at,11,5).'</span>' : ''; //監造人員
            $showAry['sign_url8']        = ($sign_url8)? '<img src="'.$sign_url8.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url8_at,11,5).'</span>' : ''; //監造人員
            $showAry['sign_url9']        = ($sign_url9)? '<img src="'.$sign_url9.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url9_at,11,5).'</span>' : ''; //監造人員
            $showAry['sign_url10']       = ($sign_url10)? '<img src="'.$sign_url10.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url10_at,11,5).'</span>' : ''; //監造人員
            $showAry['sign_url11']       = '';
            $showAry['sign_url12']       = '';
            $showAry['sign_url13']       = '';
            $showAry['sign_url14']       = ($sign_url14)? '<img src="'.$sign_url14.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url14_at,11,5).'</span>' : '';
            $showAry['sign_url15']       = ($sign_url15)? '<img src="'.$sign_url15.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url15_at,11,5).'</span>' : '';
            $showAry['sign_url16']       = ($sign_url16)? '<img src="'.$sign_url16.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url16_at,11,5).'</span>' : '';
            $showAry['sign_url17']       = ($sign_url17)? '/ <img src="'.$sign_url17.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url17_at,11,5).'</span>' : '';
            $showAry['sign_url18']       = ($sign_url18)? '<img src="'.$sign_url18.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url18_at,11,5).'</span>' : '';
            $showAry['sign_url19']       = ($sign_url19)? '<img src="'.$sign_url19.'" class="sign_img" width="80"   height="25"><span class="time_at">'.substr($sign_url19_at,11,5).'</span><br/>' : '';


            $showAry['chk_emp_topic'][100] = $chk_emp_topic100;
            $showAry['chk_emp_topic'][101] = $chk_emp_topic101;
            $showAry['chk_emp_topic'][102] = $chk_emp_topic102;
            $showAry['chk_emp_topic'][103] = $chk_emp_topic103;
            $showAry['chk_emp_topic'][104] = $chk_emp_topic104;
            $showAry['chk_emp_topic'][105] = $chk_emp_topic105;
            $showAry['chk_emp_topic'][106] = $chk_emp_topic106;
            $showAry['chk_emp_topic'][107] = $chk_emp_topic107;
            $showAry['chk_emp_topic'][108] = $chk_emp_topic108;
        }

        /*
         * ============================================
         */
        if(4)
        {
            $showAry['chk_danger_topic'][1] = $chk_danger_topic1;
            $showAry['chk_danger_topic'][2] = $chk_danger_topic2;
            $showAry['chk_danger_topic'][3] = $chk_danger_topic3;
            $showAry['chk_danger_topic'][4] = $chk_danger_topic4;
            $showAry['chk_danger_topic'][5] = $chk_danger_topic5;
            $showAry['chk_danger_topic'][6] = $chk_danger_topic6;
            $showAry['chk_danger_topic'][7] = $chk_danger_topic7;
            $showAry['chk_danger_topic'][8] = $chk_danger_topic8;
            $showAry['chk_danger_topic'][9] = $chk_danger_topic9;
            $showAry['chk_danger_topic'][10] = $chk_danger_topic10;
            $showAry['chk_danger_topic'][11] = $chk_danger_topic11;
            $showAry['chk_danger_topic'][12] = $chk_danger_topic12;
            $showAry['chk_danger_topic'][13] = $chk_danger_topic13;
            $showAry['chk_danger_topic'][14] = $chk_danger_topic14;
            $showAry['chk_danger_topic'][15] = $chk_danger_topic15;
            $showAry['chk_danger_topic'][16] = $chk_danger_topic16;
            $showAry['chk_danger_topic'][17] = $chk_danger_topic17;
            $showAry['chk_danger_topic'][18] = $chk_danger_topic18;
            $showAry['chk_danger_topic'][19] = $chk_danger_topic19;
            $showAry['chk_danger_topic'][20] = $chk_danger_topic20;
            $showAry['chk_danger_topic'][21] = $chk_danger_topic21;
            $showAry['chk_danger_topic'][22] = $chk_danger_topic22;
            $showAry['chk_danger_topic'][23] = $chk_danger_topic23a;
            $showAry['chk_danger_topic'][24] = $chk_danger_topic24a;

            $showAry['chk_tip_topic'][1] = $chk_tip_topic_count_amt;
            $showAry['chk_tip_topic'][2] = $chk_tip_topic;
//            dd($showAry['chk_tip_topic']);
        }

        /*
         * ========================= 檢點照片 =========================
         */
        if(5)
        {
            $showAry['chk_photo_count'] = $chk_photo_cont;
            $showAry['chk_photo']       = $chk_photo_topic_ary;
            $showAry['chk_no']          = [1=>'(一)',2=>'(二)',3=>'(三)',4=>'(四)',5=>'(五)',6=>'(六)',7=>'(七)',8=>'(八)',9=>'(九)',10=>'(十)',];
        }

        if($request->has('testprint'))
        {
            dd($work_id,$showAry);
        }

        if($request->has('pdf'))
        {
            $pdf = PDF::loadView('permit.permit_main2019v2', $showAry);
            return $pdf->download($permit_no.'.pdf');
        }
        return view('permit.permit_main2019v2',$showAry);
    }
}
