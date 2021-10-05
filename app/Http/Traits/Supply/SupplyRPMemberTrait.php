<?php

namespace App\Http\Traits\Supply;

use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Model\Supply\b_supply_member;
use App\Model\Supply\b_supply_rp_member;
use App\Model\User;
use Storage;

/**
 * 承攬商_成員申請單
 *
 */
trait SupplyRPMemberTrait
{
    /**
     * 新增 承攬商_成員申請單
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyRPMember($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_id)) return $ret;
        $sexAry         = array_keys(SHCSLib::getCode('SEX'));
        $bloodAry       = array_keys(SHCSLib::getCode('BLOOD'));
        $bloodrhAry     = array_keys(SHCSLib::getCode('BLOODRH'));
        $kindAry        = array_keys(SHCSLib::getCode('PERSON_KIND'));

        $INS = new b_supply_rp_member();
        $INS->b_supply_id   = $data->b_supply_id ? $data->b_supply_id : 0;
        $INS->apply_user    = $mod_user;
        $INS->apply_stamp   = date('Y-m-d H:i:s');
        $INS->apply_user    = $mod_user;
        $INS->name          = $data->name;
        $INS->head_img      = strlen($data->head_img)? $data->head_img : '';
        $INS->sex           = in_array($data->sex,$sexAry)? $data->sex : 'N';
        $INS->blood         = in_array($data->blood,$bloodAry)? $data->blood : '';
        $INS->bloodRh       = in_array($data->bloodRh,$bloodrhAry)? $data->bloodRh : '';
        $INS->bc_id         = strtoupper($data->bc_id);
        $INS->birth         = $data->birth;
        $INS->tel1          = $data->tel1;
        $INS->mobile1       = $data->mobile1;
        $INS->email1        = $data->email1;
        $INS->addr1         = $data->addr1;
        $INS->kin_kind      = in_array($data->kin_kind,$kindAry)? $data->kin_kind : 0;
        $INS->kin_tel       = $data->kin_tel;
        $INS->kin_user      = $data->kin_user;
        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret && $data->type_id )
        {
            if($data->head_img)
            {
                $isUp     = 0;
                $filepath = config('mycfg.user_head_path').'RP/'.date('Y/').$ret.'/';
                $filename = $ret.'_head.'.$data->head_imgN;
                $file1    = $filepath.$filename;
                if(Storage::put($file1,$data->head_img))
                {
                    $isUp++;
                    $INS->head_img   = $file1;
                }
                if($isUp) $INS->save();
            }
            $data->b_supply_rp_member_id = $ret;
            $this->createSupplyRPMemberIdentity($data,$mod_user);
        }

        return $ret;
    }

    /**
     * 修改 承攬商_成員
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPMember($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = $isAgree = 0;
        $sexAry         = array_keys(SHCSLib::getCode('SEX'));
        $bloodAry       = array_keys(SHCSLib::getCode('BLOOD'));
        $bloodrhAry     = array_keys(SHCSLib::getCode('BLOODRH'));
        $kindAry        = array_keys(SHCSLib::getCode('PERSON_KIND'));
        $aprocAry       = array_keys(SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC'));

        $UPD = b_supply_rp_member::find($id);
        if(!isset($UPD->id)) return $ret;
//        dd($data);

        if(isset($data->aproc) && in_array($data->aproc,$aprocAry) && $data->aproc !== $UPD->aproc)
        {
            $isUp++;
            $UPD->aproc         = $data->aproc;
            $UPD->charge_memo   = $data->charge_memo;
            $UPD->charge_user   = $mod_user;
            $UPD->charge_stamp  = $now;

            //審查通過
            if($data->aproc == 'O')
            {
                //建立會員帳號＆明細
                $b_cust_id = $this->createBcust($data,$mod_user);
                if($b_cust_id)
                {
                    $UPD->b_cust_id   = $b_cust_id;
                    $data->b_cust_id  = $b_cust_id;

                    //如果有工程案件，則配對給該工程案件
                    if($data->e_project_id)
                    {
                        $this->createEngineeringMember($data,$mod_user);
                    }

                    //如果有工程身份申請，則配對工程身份
                    if(is_array($data->identity) && count($data->identity))
                    {
                        $this->createEngineeringMemberIdentityGroup($data,$mod_user);
                    }
                } else {
                    $isUp--;
                }
            }
            //審查不通過
            if($data->aproc == 'C')
            {
                $tmp = [];
                $tmp['aproc']           = 'C';
                $tmp['charge_user1']    = $mod_user;
                $tmp['charge_stamp1']   = $now;
                $tmp['charge_memo1']    = isset($data->charge_memo)? $data->charge_memo : '';
                $tmp['mod_user']        = $mod_user;
                $tmp['updated_at']      = $now;
                \DB::table('b_supply_rp_project_license')->where('b_supply_rp_member_id',$id)->update($tmp);
            }
        }

        if(isset($data->name) && strlen($data->name) && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }

        if(isset($data->head_img) && strlen($data->head_img))
        {
            $isUp     = 0;
            $filepath = config('mycfg.user_head_path').'RP/'.date('Y/').$ret.'/';
            $filename = $ret.'_head.'.$data->head_imgN;
            $file1    = $filepath.$filename;
            if(Storage::put($file1,$data->head_img))
            {
                $isUp++;
                $UPD->head_img   = $file1;
            }
        }
        //
        if(isset($data->sex) && in_array($data->sex,$sexAry) && $data->sex !== $UPD->sex)
        {
            $isUp++;
            $UPD->sex = $data->sex;
        }
        if(isset($data->blood) && in_array($data->blood,$bloodAry) && $data->blood !== $UPD->blood)
        {
            $isUp++;
            $UPD->blood = $data->blood;
        }
        if(isset($data->bloodRh) && in_array($data->bloodRh,$bloodrhAry) && $data->bloodRh !== $UPD->bloodRh)
        {
            $isUp++;
            $UPD->bloodRh = $data->bloodRh;
        }
        if(isset($data->bc_id) && $data->bc_id && $data->bc_id !== $UPD->bc_id)
        {
            $isUp++;
            $UPD->bc_id = strtoupper($data->bc_id);
        }
        if(isset($data->birth) && CheckLib::isDate($data->birth) && $data->birth !== $UPD->birth)
        {
            $isUp++;
            $UPD->birth = $data->birth;
        }
        if(isset($data->tel1) && $data->tel1 && $data->tel1 !== $UPD->tel1)
        {
            $isUp++;
            $UPD->tel1 = $data->tel1;
        }
        if(isset($data->mobile1) && $data->mobile1 && $data->mobile1 !== $UPD->mobile1)
        {
            $isUp++;
            $UPD->mobile1 = $data->mobile1;
        }
        if(isset($data->email1) && $data->email1 && $data->email1 !== $UPD->email1)
        {
            $isUp++;
            $UPD->email1 = $data->email1;
        }
        if(isset($data->addr1) && $data->addr1 && $data->addr1 !== $UPD->addr1)
        {
            $isUp++;
            $UPD->addr1 = $data->addr1;
        }
        if(isset($data->kin_kind) && in_array($data->kin_kind,$kindAry) && $data->kin_kind !== $UPD->kin_kind)
        {
            $isUp++;
            $UPD->kin_kind = $data->kin_kind;
        }
        if(isset($data->kin_user) && $data->kin_user && $data->kin_user !== $UPD->kin_user)
        {
            $isUp++;
            $UPD->kin_user = $data->kin_user;
        }
        if(isset($data->kin_tel) && $data->kin_tel && $data->kin_tel !== $UPD->kin_tel)
        {
            $isUp++;
            $UPD->kin_tel = $data->kin_tel;
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
    public function getApiSupplyRPMemberMainList($aproc = 'A',$allowProjectAry = [])
    {
        $data = b_supply_rp_member::join('b_supply as s','s.id','=','b_supply_rp_member.b_supply_id')->
                join('e_project as p','p.id','=','b_supply_rp_member.e_project_id')->
                where('b_supply_rp_member.isClose','N')->where('s.isClose','N')->where('p.isClose','N')->
                selectRaw('MAX(s.id) as b_supply_id,MAX(s.name) as b_supply,count(s.id) as amt')->
                groupby('b_supply_rp_member.b_supply_id');

        if($aproc)
        {
            $data = $data->where('b_supply_rp_member.aproc',$aproc);
        }
        if(is_array($allowProjectAry) && count($allowProjectAry))
        {
            $data = $data->whereIn('b_supply_rp_member.e_project_id',$allowProjectAry);
        }
        $data = $data->get();
        if(is_object($data)) {
            $ret = (object)$data;
        }
        return $ret;
    }

    /**
     * 取得 承攬商_成員
     *
     * @return array
     */
    public function getApiSupplyRPMemberList($sid,$aproc = 'A',$allowProjectAry = [])
    {
        $ret = array();
        $bctypeAry      = SHCSLib::getCode('BC_TYPE');
        $nationAry      = SHCSLib::getCode('NATION_TYPE');
        $kindAry        = SHCSLib::getCode('PERSON_KIND');
        $aprocAry       = SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC');
        //取第一層
        $data = b_supply_rp_member::join('b_supply as s','s.id','=','b_supply_rp_member.b_supply_id')->
            join('e_project as p','p.id','=','b_supply_rp_member.e_project_id')->
            where('b_supply_rp_member.b_supply_id',$sid)->
            where('b_supply_rp_member.isClose','N')->where('s.isClose','N')->where('p.isClose','N');
        //進度
        if($aproc)
        {
            $data = $data->where('b_supply_rp_member.aproc',$aproc);
        }
        if(is_array($allowProjectAry) && count($allowProjectAry))
        {
            $data = $data->whereIn('b_supply_rp_member.e_project_id',$allowProjectAry);
        }
        $data = $data->select('b_supply_rp_member.*','s.name as supply')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $id = $v->id;
                $data[$k]['aproc_name']     = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['nation_name']    = isset($nationAry[$v->nation])? $nationAry[$v->nation] : '';
                $data[$k]['bc_type_name']   = isset($bctypeAry[$v->bc_type])? $bctypeAry[$v->bc_type] : '';
                $data[$k]['kin_kind_name']  = isset($kindAry[$v->kin_kind])? $kindAry[$v->kin_kind] : '';
                $data[$k]['apply_name']     = User::getName($v->apply_user);
                $data[$k]['apply_stamp']    = substr($v->apply_stamp,0,19);
                $data[$k]['charge_name']    = User::getName($v->charge_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);

                //取得工程身份＆證照申請
                $rpAry = $this->getApiSupplyRPProjectMemberLicenseIDFroRpMember($sid,$id);
                //dd(['getApiSupplyRPMemberIdentityList',$rpAry]);
                if(is_object($rpAry))
                {
                    foreach ($rpAry as $v2)
                    {
                        $data[$k]['b_supply_rp_member_ei_id']       = 0;
                        $data[$k]['b_supply_rp_project_license_id'] = $v2->id;
                        $data[$k]['type']                           = $v2->engineering_identity_name;
                        $data[$k]['type_id']                        = $v2->engineering_identity_id;
                    }
                }
            }

            $ret = (object)$data;
        }

        return $ret;
    }

}
