<?php

namespace App\Http\Traits\Supply;

use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_license;
use App\Model\Engineering\e_project_s;
use App\Model\Supply\b_supply_car_type;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_l;
use App\Model\Supply\b_supply_rp_bcust;
use App\Model\Supply\b_supply_rp_car;
use App\Model\Supply\b_supply_rp_chg_card;
use App\Model\Supply\b_supply_rp_member_l;
use App\Model\Supply\b_supply_rp_project;
use App\Model\Supply\b_supply_rp_project_license;
use App\Model\User;
use App\Model\View\view_used_rfid;
use DateTime;
use Storage;
use Lang;

/**
 * 承攬商_申請_加入工程案件之工程身分
 *
 */
trait SupplyRPProjectLicenseTrait
{
    /**
     * 新增 承攬商_申請_加入工程案件之工程身分
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyRPProjectMemberLicense($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_id)) return $ret;
        $now = date('Y-m-d H:i:s');

        $INS = new b_supply_rp_project_license();
        $INS->apply_user                = $mod_user;
        $INS->apply_stamp               = $now;
        $INS->b_supply_id               = $data->b_supply_id;
        $INS->b_cust_id                 = $data->b_cust_id;
        $INS->e_project_id              = $data->e_project_id;
        $INS->b_supply_member_l_id      = $data->b_supply_member_l_id;
        $INS->engineering_identity_id   = $data->engineering_identity_id;
        $INS->charge_kind               = b_supply_engineering_identity::getChargeKind($data->engineering_identity_id);
        $INS->e_license_id              = b_supply_member_l::getLicenseID($data->b_supply_member_l_id);

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }
    /**
     * 修改 承攬商_申請_加入工程案件之工程身分
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function updateSupplyRPProjectMemberLicense($data,$mod_user = 1)
    {
        $ret = 0;
        $identityAry = $data['identity'];

        foreach ($identityAry as $val) {
            $id = $val['id'];
            $b_supply_rp_project_license_id = $val['b_supply_rp_project_license_id'];
            $tmp = [];

            //審核狀態為不同意，停用工程身分申請單已審核通過的工程身分
            if ($data['aproc'] == 'C') {
                $ret = e_project_license::closeUserIdentity($data['b_cust_id'], $data['e_project_id'], $mod_user, $b_supply_rp_project_license_id);
            } else {
                if (in_array($id, [1, 2])) {
                    $tmp['aproc'] = 'P';
                    $tmp['charge_memo'] = '';
                } else {
                    $tmp['aproc'] = 'O';
                    $tmp['charge_memo'] = '';
                }
                if ($this->setSupplyRPProjectMemberLicense($b_supply_rp_project_license_id, $tmp, $mod_user)) $ret++;
            }
        }
        return $ret;
    }

    /**
     * 修改 承攬商_申請_加入工程案件之工程身分
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPProjectMemberLicense($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $date = date('Y-m-d');
        $aprocAry = array_keys(SHCSLib::getCode('RP_PROJECT_LICENSE_APROC'));
        $isUp = 0;

        $UPD = b_supply_rp_project_license::find($id);
        
        // 20210917 增加申請單狀態判斷，若已經為審核同意(O)或不同意(C)，該單不可再被異動
        if(in_array($UPD->aproc, ['O','C'])){
            return -3;
        }

        //判斷人員是否為重新補工程身分，isApplyYN=''為新申請身分，'N'為須補身分，'Y'為身分正常
        $isApplyYN      = e_project_license::getUserlicense($UPD->e_project_id, $UPD->b_cust_id, $UPD->engineering_identity_id, '');
        
        if(!isset($UPD->b_cust_id)) return $ret;

        $rfidAry = view_used_rfid::isUserExist($UPD->b_cust_id); // 是否有無配卡
        // 20210521 - 承商申請階段、監造審查階段取消檢查是否配卡，僅在工安課審查階段判斷如果配卡才阻擋
        // 工安課審查，若轉移/作廢的身分為工安或工負，且有配卡 (暫時先顯示需要退卡，暫不加入到退卡單)
        // 申請加入，重新補工程身分不需要退卡
        if($isApplyYN == 'N' && $UPD->order_type == '1'){
            $isBlock = false;
        }else{
            $isBlock = true;
        }
        // dd('rfidAry='.!empty($rfidAry[0]),'isApplyYN='.$isApplyYN,'isBlock='.$isBlock, 'engineering_id='.$UPD->engineering_identity_id);
        // dd(['aproc' => in_array($data->aproc, ['O']), 'engineering_identity_id' => in_array($UPD->engineering_identity_id, [1, 2]), 'rfidAry' => !empty($rfidAry[0]), 'isApplyYN' => $isApplyYN, 'order_type' => $UPD->order_type, 'isBlock' => $isBlock]]);
        if (in_array($data->aproc, ['O']) && in_array($UPD->engineering_identity_id, [1, 2]) && !empty($rfidAry[0]) && $isBlock ) {
            return -2;
        }

        if(isset($data->b_cust_id) && $data->b_cust_id !== $UPD->b_cust_id)
        {
            $UPD->b_cust_id   = $data->b_cust_id;
            $isUp++;
        }
        if(isset($data->b_supply_member_l_id) && $data->b_supply_member_l_id !== $UPD->b_supply_member_l_id)
        {
            $UPD->b_supply_member_l_id   = $data->b_supply_member_l_id;
            $isUp++;
        }
        if(isset($data->b_supply_member_l_id2) && $data->b_supply_member_l_id2 !== $UPD->b_supply_member_l_id2)
        {
            $UPD->b_supply_member_l_id2   = $data->b_supply_member_l_id2;
            $isUp++;
        }
        //審查結果
        if(isset($data->aproc) && in_array($data->aproc,$aprocAry) && $data->aproc !== $UPD->aproc)
        {
            $isOK = 0;

            //審查通過
            if($data->aproc == 'O') {
                $data->e_project_id                     = $UPD->e_project_id;
                $data->b_supply_id                      = $UPD->b_supply_id;
                $data->b_cust_id                        = $UPD->b_cust_id;
                $data->b_supply_member_l_id             = $UPD->b_supply_member_l_id;
                $data->b_supply_member_l_id2            = $UPD->b_supply_member_l_id2;
                $data->engineering_identity_id          = $UPD->engineering_identity_id;
                $data->b_supply_rp_project_license_id   = $id;

                if (in_array($UPD->order_type,  [2, 3])) { // 2 = 轉移工程身分, 3 = 作廢工程身分
                    if (in_array($data->engineering_identity_id, [1, 2]) && !empty($rfidAry[0])) { // 若轉移或作廢工安/工負 且已有配卡 則要加入到退卡單 (中油規則只有工安/ 工負印卡是特殊權限，所以轉換身分或換卡必需強迫退卡，再重印新身分的卡，由辦證室處理)
                        $INS = new b_supply_rp_chg_card();
                        $INS->b_supply_rp_chg_project_id = 0;
                        $INS->b_supply_rp_project_license_id = $id; // 記錄轉移身分申請單 ID，由資料庫預存程序處理退卡後，要參照申請單 ID 判斷要轉換工程身分 (處理邏輯同無配卡，退掉舊身分再加入新身分或) 或作廢工程身分 (處理邏輯同無配卡，退掉舊身分並改為施工人員)
                        $INS->b_supply_id = $data->b_supply_id;
                        $INS->e_project_id = $data->e_project_id;
                        $INS->b_cust_id = $data->b_cust_id;
                        $INS->b_rfid_id = $rfidAry[0];
                        $INS->b_rfid_a_id = $rfidAry[3];
                        $INS->chg_card_kind = 2;
                        $INS->chg_date = $date;
                        $INS->apply_user = $mod_user;
                        $INS->apply_stamp = $now;
                        $INS->save();
                        $isOK = 1;

                        $data->aproc = 'R'; // 辦證室退換卡
                    } else { // 若無配卡可以直接新增工程身分
                        $old_engineering_identity_id = null;
                        if ($UPD->order_type == 2) { // 轉移工程身分
                            // 轉工負 關閉舊的 工安身分，反之轉工安 則取消舊的 工負 身分
                            $old_engineering_identity_id = $data->engineering_identity_id == 1 ? 2 : 1;
                        } else if ($UPD->order_type == 3) { // 作廢工程身分
                            // 作廢時直接關閉所選擇的工程身分
                            $old_engineering_identity_id = $data->engineering_identity_id;
                        }

                        if (!empty($old_engineering_identity_id)) {
                            $res = e_project_license::where('e_project_id', $data->e_project_id)
                                ->where('b_cust_id', $data->b_cust_id)
                                ->where('engineering_identity_id', $old_engineering_identity_id)
                                ->where('isClose', 'N')->first();

                            $UPD2 = e_project_license::find($res->id);
                            $UPD2->isClose = 'Y';
                            $UPD2->close_user = $mod_user;
                            $UPD2->close_stamp = $now;
                            $UPD2->save();
                        }

                        // engineering_identity_id=1 (工負)
                        // engineering_identity_id=2 (工安)
                        // JOB_KIND 工程案件角色 1 施工人員
                        // JOB_KIND 工程案件角色 2 工負
                        // JOB_KIND 工程案件角色 3 工安
                        $old_job_kind = null;
                        $new_job_kind = null;
                        $new_cpc_tag = null;
                        if ($UPD->order_type == 2) { // 轉移工程身分
                            // 將舊的專案角色 工安轉工負 或 工負轉工安
                            $old_job_kind = $data->engineering_identity_id == 1 ? 3 : 2; // 轉移目標的 engineering_identity_id == 1 (工負)，則舊的 job_kind 為 3 (工安)，反之 job_kind = 2 (工負)
                            $new_job_kind = $data->engineering_identity_id == 1 ? 2 : 3; // 轉移目標的 engineering_identity_id == 1 (工負)，則新的 job_kind 為 2 (工負)，反之 job_kind = 3 (工安)
                            $new_cpc_tag = $data->engineering_identity_id == 1 ? 'A' : 'B'; // 轉移目標的 engineering_identity_id == 1 (工負)，則新的 cpc_tag 為 A (工負)，反之 cpc_tag = B (工安)
                        } else if ($UPD->order_type == 3 && in_array($data->engineering_identity_id, [1, 2])) { // 作廢工程身分 且作廢的身分為工安工負時
                            // 將舊的專案角色 取消工負或工安 轉為 施工人員
                            $old_job_kind = $data->engineering_identity_id == 1 ? 2 : 3; // 作廢目標的 engineering_identity_id == 1 (工負)，則舊的 job_kind 為 2 (工負)，反之 job_kind = 3 (工安)
                            $new_job_kind = 1; // 作廢舊的工安或工負專案角色，轉換為施工人員的 job_kind 為 1
                            $new_cpc_tag = 'C'; // 作廢舊的工安或工負專案角色，轉換為施工人員的 cpc_tag 為 C
                        }

                        if (!empty($old_job_kind) && !empty($new_job_kind) && !empty($new_cpc_tag)) {
                            $res = e_project_s::where('e_project_id', $data->e_project_id)
                                ->where('b_cust_id', $data->b_cust_id)
                                ->where('job_kind', $old_job_kind)
                                ->where('isClose', 'N')->first();

                            $UPD2 = e_project_s::find($res->id);
                            $UPD2->job_kind = $new_job_kind;
                            $UPD2->cpc_tag = $new_cpc_tag;
                            $UPD2->mod_user = $mod_user;
                            $UPD2->save();
                        }
                    }
                }
                if ($UPD->order_type == 3) { // 若是作廢僅需處理完取消身分即回傳完成
                    $isOK = 1;
                } else if ($this->createEngineeringMemberIdentity($data, $mod_user)) { // 若是新增和轉移則要增加身份
                    $isOK = 1;
                }
            } else {
                $isOK = 1;
            }
            if($isOK)
            {
                $isUp++;
                //監造審查完畢
                if($UPD->aproc == 'P')
                {
                    $UPD->charge_user2   = $mod_user;
                    $UPD->charge_stamp2  = $now;
                    $UPD->charge_memo2   = isset($data->charge_memo)? $data->charge_memo : '';
                } else {
                    $UPD->charge_user1   = $mod_user;
                    $UPD->charge_stamp1  = $now;
                    $UPD->charge_memo1   = isset($data->charge_memo)? $data->charge_memo : '';
                }
                $UPD->aproc         = $data->aproc;
            }
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !==  $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
                $UPD->close_stamp   = $now;
            } else {
                $UPD->isClose = 'N';
            }
        }

        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 承攬商_成員申請單 by 承攬商
     *
     * @return array
     */
    public function getApiSupplyRPProjectMemberLicenseMainList($aproc = 'A',$allowAry = [], $isCount = 'N')
    {
        $ret  = ($isCount == 'Y')? 0 : [];
        $data = b_supply_rp_project_license::
        selectRaw('b_supply_id,count(b_supply_id) as amt')->where('isClose','N')->
        where('b_cust_id','>',0)->groupby('b_supply_id');

        if(is_array($allowAry) && count($allowAry))
        {
            $data = $data->whereIn('e_project_id',$allowAry);
        }
        if($aproc)
        {
            $data = $data->where('aproc',$aproc);
            //工安課審查
            if($aproc == 'P')
            {
                $data = $data->where('charge_kind',2);
            }
            //項次346，監造審查階段，不顯示從加入案件申請產生的身分申請單
            if($aproc == 'A')
            {
                $data = $data->where('b_supply_rp_project_id','0');
            }
            
        }
        $data = $data->get();
        if(is_object($data)) {
            if($isCount == 'Y')
            {
                foreach ($data as $val)
                {
                    $ret += $val->amt;
                }
            } else {
                $ret = (object)$data;
            }

        }
        return $ret;
    }

    /**
     * 取得 承攬商_申請_加入工程案件之工程身分
     *
     * @return array
     */
    public function getApiSupplyRPProjectMemberLicenseList($sid,$aproc = 'A',$allowAry = [])
    {
        $ret = array();
        $aprocAry       = SHCSLib::getCode('RP_PROJECT_LICENSE_APROC');
        $chargekindAry  = SHCSLib::getCode('IDENTITY_CHARGE_KIND');
        $orderTypeAry   = [1 => "加入", 2 => "轉移", 3 => "作廢"];
        //取第一層
        $data = b_supply_rp_project_license::
        join('e_project as p','p.id','=','b_supply_rp_project_license.e_project_id')->
        where('b_supply_rp_project_license.b_supply_id',$sid)->
        where('b_supply_rp_project_license.aproc',$aproc)->where('b_supply_rp_project_license.isClose','N')->
        where('b_supply_rp_project_license.b_cust_id','>',0)->
        select('b_supply_rp_project_license.*','p.name as project');
        if(is_array($allowAry) && count($allowAry))
        {
            $data = $data->whereIn('b_supply_rp_project_license.e_project_id',$allowAry);
        }
        if($aproc == 'P')
        {
            $data = $data->where('b_supply_rp_project_license.charge_kind',2);
        }
        //項次346，監造審查階段，不顯示從加入案件申請產生的身分申請單
        if($aproc == 'A')
        {
            $data = $data->where('b_supply_rp_project_id','0');
        }

        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $id = $v->id;

                $data[$k]['charge_kind_name']   = isset($chargekindAry[$v->charge_kind])? $chargekindAry[$v->charge_kind] : '';
                $data[$k]['aproc_name']         = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['order_type']         = $v->order_type;
                $data[$k]['order_type_name']    = isset($orderTypeAry[$v->order_type])? $orderTypeAry[$v->order_type] : '';
                $data[$k]['engineering_identity_name']     = b_supply_engineering_identity::getName($v->engineering_identity_id);
                $data[$k]['apply_name']     = User::getName($v->apply_user);
                $data[$k]['apply_stamp']    = substr($v->apply_stamp,0,16);
                $data[$k]['charge_name1']   = User::getName($v->charge_user1);
                $data[$k]['charge_stamp1']  = substr($v->charge_stamp1,0,16);
                $data[$k]['charge_name2']   = User::getName($v->charge_user2);
                $data[$k]['charge_stamp2']  = substr($v->charge_stamp2,0,16);
                $data[$k]['user']           = User::getName($v->b_cust_id);
                $data[$k]['project']        = e_project::getName($v->e_project_id,2);
                $data[$k]['chg_user']       = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }
    /**
     * 取得 承攬商_申請_加入工程案件之工程身分
     *
     * @return array
     */
    public function getApiSupplyRPProjectMemberLicenseIDFroRpMember($sid,$b_supply_rp_member_id)
    {
        //取第一層
        $ret = [];
        $data = b_supply_rp_project_license::where('b_supply_id',$sid)->where('b_supply_rp_member_id',$b_supply_rp_member_id)->
        where('aproc','A')->where('isClose','N')->
        where('b_supply_rp_member_id',0)->select('id','engineering_identity_id');
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $tmp = [];
                $tmp['id'] = $val->id;
                $tmp['engineering_identity_id'] = $val->engineering_identity_id;
                $tmp['engineering_identity_name'] = b_supply_engineering_identity::getName($val->engineering_identity_id);
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

}
