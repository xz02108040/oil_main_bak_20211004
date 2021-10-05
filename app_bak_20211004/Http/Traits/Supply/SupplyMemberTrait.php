<?php

namespace App\Http\Traits\Supply;

use App\Lib\LogLib;
use App\Model\User;
use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Bcust\b_cust_a;
use App\Model\Supply\b_supply;
use App\Model\View\view_used_rfid;
use App\Model\Engineering\e_project;
use App\Model\Supply\b_supply_member;
use App\Model\Engineering\e_project_s;
use App\Model\Supply\b_supply_member_l;
use App\Model\Supply\b_supply_rp_project;
use App\Model\View\view_door_supply_member;
use App\Model\Engineering\e_project_license;
use App\Model\Engineering\e_violation_contractor;
use App\Model\Supply\b_supply_rp_project_license;

/**
 * 承攬商_成員
 *
 */
trait SupplyMemberTrait
{
    /**
     * 新增 承攬商_成員(帳號＋個資明細)
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyMember($data,$mod_user = 1)
    {
        $ret = false;
        if(!isset($data->b_cust_id)) return $ret;

        $INS = new b_supply_member();
        $INS->b_cust_id     = $data->b_cust_id;
        $INS->b_supply_id   = $data->b_supply_id ? $data->b_supply_id : 0;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->b_cust_id : 0;

        return $ret;
    }

    /**
     * 修改 承攬商_成員
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyMember($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $isUp = 0;

        $UPD = b_supply_member::find($id);
        if(!isset($UPD->b_cust_id)) return $ret;

        // 承攬商
        if(isset($data->b_supply_id) && $data->b_supply_id !== $UPD->b_supply_id)
        {
            $UPD->b_supply_id   = $data->b_supply_id;
            $isUp++;
        }

        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();

            //轉入新公司後，由系統自動停用
            //1.停用工程身分  
            $closeProjectIdentity = e_project_license::closeUserIdentity($UPD->b_cust_id , 0, $mod_user);
            //2.停用勞保.健保.團保,健康檢查
            $closeMemberLicense = b_supply_member_l::closeSupplyMemberLicense($UPD->b_cust_id ,$mod_user);
            //3.清除緊急聯絡人
            $closeKin = b_cust_a::closeKin($UPD->b_cust_id ,$mod_user);
            //4.停用登入帳號權限
            $closeKin = User::closeIsLogin($UPD->b_cust_id ,$mod_user);
            //5.取消加入/轉移/作廢工程身分申請單
            $closeProjectIdentity = b_supply_rp_project_license::cancelSuppyRPProjectIdentity($UPD->b_cust_id, $mod_user);
            //6.取消加入案件申請單
            $closeProject = b_supply_rp_project::cancelSuppyRPProject($UPD->b_cust_id, $mod_user);
            
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 承攬商_成員
     *
     * @return array
     */
    public function getApiSupplyMemberList($sid,$isInProject = '')
    {
        $ret = array();
        $bctypeAry      = SHCSLib::getCode('BC_TYPE');
        $nationAry      = SHCSLib::getCode('NATION_TYPE');
        $kindAry        = SHCSLib::getCode('PERSON_KIND');
        $passAry        = LogLib::getCoursePassSelect($sid); //承攬商教育訓練通過名單
        $WLAry          = LogLib::getWhiteListSelect($sid); //承攬商門禁白名單
        //取第一層
        $data = b_supply_member::where('b_supply_id',$sid)->
        join('view_user as u','b_supply_member.b_cust_id','=','u.b_cust_id')->
        select('u.*','b_supply_member.b_supply_id');

        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $isOk = 1;
                $project_id = view_door_supply_member::getProjectID($v->b_cust_id);
                $isOver     = (e_project::isExist($project_id,0,'Y'))? 'Y' : 'N';

                if($isInProject == 'Y' && !$project_id)
                {
                    $isOk = 0;
                }
                if($isInProject == 'N' && $project_id)
                {
                    $isOk = 0;
                }

                if($isOk)
                {
                    $data[$k]['bc_type_name']   = isset($bctypeAry[$v->bc_type])? $bctypeAry[$v->bc_type] : '';
                    $data[$k]['nation_name']    = isset($nationAry[$v->nation])? $nationAry[$v->nation] : '';
                    $data[$k]['kin_kind_name']  = isset($kindAry[$v->kin_kind])? $kindAry[$v->kin_kind] : '';
                    $data[$k]['project_id']     = $project_id;
                    $data[$k]['project']        = e_project::getName($project_id);
                    $data[$k]['chg_user']       = User::getName($v->close_user);
                    $data[$k]['new_user']       = User::getName($v->new_user);
                    $data[$k]['mod_user']       = User::getName($v->mod_user);

                    //尿檢
                    $data[$k]['ut_name']        = e_project_s::getUTName($project_id,$v->b_cust_id,2);
                    //工程身分
                    $data[$k]['identitylist']   = e_project_license::getUserIdentityAllName($project_id,$v->b_cust_id);;

                    //工程案件資格
                    $data[$k]['isOver']         = $isOver;

                    //教育訓練白名單
                    $data[$k]['isPass']         = in_array($v->b_cust_id,$passAry)? 'Y' : (User::isExist($v->b_cust_id)? 'N' :'C');

                    //是否有無配卡
                    list($rfid,$rfidname,$rfidcode) = view_used_rfid::isUserExist($v->b_cust_id);
                    $data[$k]['isPair']             = $rfid ? 'Y' : 'N';
                    $data[$k]['rfid']               = $rfid;

                    //判斷是否違規
                    $isViolaction = e_violation_contractor::isMemberExist($v->b_cust_id,2);
                    $data[$k]['isViolaction']   = $isViolaction;

                    //通行資格
                    $data[$k]['isWhiteList']    = in_array($v->b_cust_id,$WLAry)? 'Y' : 'N';
                } else {
                    unset($data[$k]);
                }

            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 承攬商_成員 For App
     *
     * @return array
     */
    public function getApiSupplyMember($sid, $isDetail = 'N')
    {
        $ret = array();
        $kindAry        = SHCSLib::getCode('PERSON_KIND');

        //取第一層
        $data = b_supply_member::where('b_supply_id',$sid)->
                join('view_user as v','b_supply_member.b_cust_id','=','v.b_cust_id')->
                select('v.*','b_supply_member.b_supply_id');

        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $urlid           = SHCSLib::encode($v->b_cust_id);
                $kin_kind_name   = isset($kindAry[$v->kin_kind])? $kindAry[$v->kin_kind] : '';
                $tel1            = ($v->tel_area1? $v->tel_area1 : '').$v->tel1;
                $tel2            = ($v->tel_area2? $v->tel_area2 : '').$v->tel2;
                $tmp = [];
                $tmp['id']              = $v->b_cust_id;
                $tmp['name']            = $v->name;
                $tmp['mobile']          = $v->mobile1.($tel1 ? ' / '.$tel1 : '').($tel2 ? ' / '.$tel2 : '');
                $tmp['blood']           = $v->blood;
                $tmp['bc_id']           = SHCSLib::genBCID($v->bc_id);
                $tmp['kin_user']        = $v->kin_user.'('.$kin_kind_name.')';
                $tmp['kin_tel']         = $v->kin_tel;
                $tmp['age']             = SHCSLib::toAge($v->birth);
                $tmp['head_img']        = $v->head_img ? url('img/User/'.$urlid) : '';
                if($isDetail == 'Y')
                {
                    //專業證照
                    $tmp['license']    = $this->getApiSupplyMemberLicense($sid,$v->b_cust_id);
                    //教育訓練
                    $tmp['course']     = $this->getApiTraningMemberSelf($v->b_cust_id);

                }
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

}
