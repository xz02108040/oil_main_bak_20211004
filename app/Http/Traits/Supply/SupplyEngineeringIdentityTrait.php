<?php

namespace App\Http\Traits\Supply;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_violation_type;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_l;
use App\Model\Supply\b_supply_rp_member_ei;
use App\Model\Supply\b_supply_rp_member_l;
use App\Model\Supply\b_supply_rp_project_license;
use App\Model\sys_param;
use App\Model\User;

/**
 * 承攬商成員＿工程身分
 *
 */
trait SupplyEngineeringIdentityTrait
{
    /**
     * 新增 承攬商成員＿工程身分
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyEngineeringIdentity($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new b_supply_engineering_identity();
        $INS->name          = $data->name;
        $INS->charge_kind   = $data->charge_kind;
        $INS->show_order    = $data->show_order ? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 承攬商成員＿工程身分
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyEngineeringIdentity($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_supply_engineering_identity::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //審查分類
        if(isset($data->charge_kind) && is_numeric($data->charge_kind) && $data->charge_kind !== $UPD->charge_kind)
        {
            $isUp++;
            $UPD->charge_kind = $data->charge_kind;
        }
        //排序
        if(isset($data->show_order) && is_numeric($data->show_order) && $data->show_order !== $UPD->show_order)
        {
            $isUp++;
            $UPD->show_order = $data->show_order;
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !== $UPD->isClose)
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
     * 取得 承攬商成員＿工程身分
     *
     * @return array
     */
    public function getApiSupplyEngineeringIdentityList()
    {
        $ret = array();
        $kindAry = SHCSLib::getCode('IDENTITY_CHARGE_KIND');
        //取第一層
        $data = b_supply_engineering_identity::orderby('isClose')->orderby('show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['charge_kind_name']   = isset($kindAry[$v->charge_kind])? $kindAry[$v->charge_kind] : '';
                $data[$k]['close_user']         = User::getName($v->close_user);
                $data[$k]['new_user']           = User::getName($v->new_user);
                $data[$k]['mod_user']           = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 承攬商_成員_申請的工程身份[全部工程身份列表]
     *
     * @return array
     */
    public function getApiSupplyRPMemberIdentityAllList($b_supply_id,$b_supply_rp_member_id = 0,$e_project_id = 0)
    {
        $identityApplyAry = b_supply_engineering_identity::getApplySelect($e_project_id);

        foreach ($identityApplyAry as $iid => $val)
        {
            if($b_supply_rp_member_id)
            {
                $data = b_supply_rp_project_license::where('b_supply_id',$b_supply_id)->where('isClose','N');
                $data = $data->where('b_supply_rp_member_id',$b_supply_rp_member_id);
                $data = $data->where('e_project_id',$e_project_id);
                $data = $data->where('engineering_identity_id',$iid);
                $data = $data->where('aproc','!=','C')->first();
                if(isset($data->id))
                {
                    if(in_array($data->aproc,['A','P','R','O']))
                    {
                        $identityApplyAry[$iid]['isApply'] = 'Y';
                    } else {
                        $identityApplyAry[$iid]['isApply'] = 'R';
                    }
                    $identityApplyAry[$iid]['b_supply_rp_project_license_id'] = $data->id;
                    $licenseAry = [];//$identityApplyAry[$iid]['license'];
                    //dd($identityApplyAry);
                    //更新證照
                    $dataL = b_supply_rp_member_l::where('isClose','N');
                    $dataL = $dataL->where('id',$data->b_supply_rp_member_l_id);
                    $dataL = $dataL->where('aproc','!=','C')->first();
                    if(isset($dataL->id))
                    {
                        $licenseStr = '';
                        $licenseAry['e_license_id']                 = $dataL->e_license_id;
                        $licenseAry['license_name']                 = e_license::getName($dataL->e_license_id);
                        $licenseAry['isOk']                         = 'Y';
                        $licenseAry['b_supply_rp_member_l_id']      = $dataL->id;
                        $licenseAry['sdate']                        = $dataL->sdate;
                        $licenseAry['edate']                        = $dataL->edate;
                        $licenseAry['edate_type']                   = $dataL->edate_type;
                        $licenseAry['license_code']                 = $dataL->license_code;
                        $licenseAry['file1']                        = $dataL->file1;
                        $licenseAry['file2']                        = $dataL->file2;
                        $licenseAry['file3']                        = $dataL->file3;
                        $licenseAry['filePath1']                    = $dataL->file1 ? url('/img/RpLicense/'.SHCSLib::encode($dataL->id).'?sid=A') : '';
                        $licenseAry['filePath2']                    = $dataL->file2 ? url('/img/RpLicense/'.SHCSLib::encode($dataL->id).'?sid=B') : '';
                        $licenseAry['filePath3']                    = $dataL->file3 ? url('/img/RpLicense/'.SHCSLib::encode($dataL->id).'?sid=C') : '';
                        $licenseAry['b_supply_rp_member_l_id2'] = 0;
                        $licenseAry['license_name2']            = '';
                        $licenseAry['sdate2']                   = '';
                        $licenseAry['license_code2']            = '';
                        $licenseAry['filePath4']                = '';
                        if($dataL->e_license_id == 1)
                        {
                            $e_license_id2 = sys_param::getParam('LICENSE_2',14);
                            $licenseAry['license_name2'] = e_license::getName($e_license_id2);
                            $dataL2 = b_supply_rp_member_l::where('isClose','N');
                            $dataL2 = $dataL2->where('b_supply_rp_member_id',$b_supply_rp_member_id);
                            $dataL2 = $dataL2->where('e_license_id',$e_license_id2)->where('aproc','!=','C')->first();

                            if(isset($dataL2->id))
                            {
                                $licenseAry['b_supply_rp_member_l_id2'] = $dataL2->id;
                                $licenseAry['sdate2']                   = $dataL2->sdate;
                                $licenseAry['license_code2']            = $dataL2->license_code;
                                $licenseAry['file4']                    = $dataL->file1;
                                $licenseAry['filePath4']                = $dataL2->file1 ? url('/img/RpLicense/'.SHCSLib::encode($dataL2->id).'?sid=A') : '';
                            }
                        }

                        if(strlen($licenseStr)) $licenseStr .= '/';
                        $licenseStr .= HtmlLib::Color('Ｏ'.$licenseAry['license_name'],'blue');
                        $licenseStr .= '，'.HtmlLib::Color(\Lang::get($this->langText.'.supply_71').$dataL->license_code,'',1);
                        $licenseStr .= '，'.HtmlLib::Color(\Lang::get($this->langText.'.supply_74').$dataL->sdate,'',1);
                        if($licenseAry['filePath1']) $licenseStr .= ' '.HtmlLib::btn($licenseAry['filePath1'],'下載',1,'','','','_blank');
                        if($licenseAry['filePath2']) $licenseStr .= ' '.HtmlLib::btn($licenseAry['filePath2'],'下載',1,'','','','_blank');
                        if($licenseAry['filePath3']) $licenseStr .= ' '.HtmlLib::btn($licenseAry['filePath3'],'下載',1,'','','','_blank');
                        if($dataL->e_license_id == 1)
                        {
                            $licenseStr .= HtmlLib::Color('Ｏ'.$licenseAry['license_name2'],'blue');
                            $licenseStr .= '，'.HtmlLib::Color(\Lang::get($this->langText.'.supply_71').$licenseAry['license_code2'],'',1);
                            $licenseStr .= '，'.HtmlLib::Color(\Lang::get($this->langText.'.supply_74').$licenseAry['sdate2'],'',1);
                            if($licenseAry['filePath4']) $licenseStr .= ' '.HtmlLib::btn($licenseAry['filePath4'],'下載',1,'','','','_blank');
                        }
                        $identityApplyAry[$iid]['license']          = $licenseAry;
                        $identityApplyAry[$iid]['licenseAllName']   = $licenseStr;
                        $identityApplyAry[$iid]['isOk']             = 'Y';
                    }
                }
//                dd($identityApplyAry);
            }
        }

        return $identityApplyAry;
    }

    /**
     * 取得 承攬商_申請加入工程案件_申請的工程身份[全部工程身份列表]
     *
     * @return array
     */
    public function getApiSupplyRPProjectIdentityAllList($b_supply_id,$b_supply_rp_project_id = 0,$ext_user = 0,$project_id = 0)
    {
        $identityApplyAry = b_supply_engineering_identity::getApplySelect($project_id,[],$ext_user);
        $baseWorkerIdentityId = sys_param::getParam('SUPPLY_RP_BCUST_IDENTITY_ID',9);

        foreach ($identityApplyAry as $iid => $val)
        {
            if($b_supply_rp_project_id)
            {
                $data = b_supply_rp_project_license::where('b_supply_id',$b_supply_id)->where('isClose','N');
                $data = $data->where('b_supply_rp_project_id',$b_supply_rp_project_id);
                $data = $data->where('engineering_identity_id',$iid);
                $data = $data->where('aproc','!=','C')->first();
                if(isset($data->id))
                {
                    if(in_array($data->aproc,['P','R','O']))
                    {
                        $isApply = 'Y';
                    } else {
                        $isApply = 'R';
                    }
                    $identityApplyAry[$iid]['isApply']                  = $isApply;
                    $identityApplyAry[$iid]['b_supply_rp_project_license_id'] = $data->id;

                    $licenseAry = $identityApplyAry[$iid]['license'];
                    //更新證照
                    $dataL = b_supply_member_l::where('id',$data->b_supply_member_l_id)->where('isClose','N');
                    $dataL = $dataL->first();
                    if(isset($dataL->id))
                    {
                        $licenseStr = '';
                        $lid   = $dataL->e_license_id;

                        if(strlen($licenseStr)) $licenseStr .= '/';
                        $licenseStr .= HtmlLib::Color('Ｏ'.e_license::getName($lid),'blue');
                        $licenseAry[$lid]['isOk'] = 'Y';
                        $licenseStr .= $dataL->file1 ? HtmlLib::btn(url('img/License/'.SHCSLib::encode($dataL->id).'?sid=A'),'下載',3) : '';
                        $licenseStr .= $dataL->file2 ? HtmlLib::btn(url('img/License/'.SHCSLib::encode($dataL->id).'?sid=B'),'下載',3) : '';
                        $licenseStr .= $dataL->file3 ? HtmlLib::btn(url('img/License/'.SHCSLib::encode($dataL->id).'?sid=C'),'下載',3) : '';

                        //證照2
                        if($iid == $baseWorkerIdentityId){
                            $dataL_2 = b_supply_member_l::where('id',$data->b_supply_member_l_id2)->where('isClose','N');
                            $dataL_2 = $dataL_2->first();

                            if(isset($dataL_2)){
                                $lid2   = $dataL_2->e_license_id;
                                
                                if(strlen($licenseStr)) $licenseStr .= '/';
                                $licenseStr .= HtmlLib::Color('Ｏ'.e_license::getName($lid2),'blue');
                                $licenseAry[$lid2]['isOk'] = 'Y';
                                $licenseStr .= $dataL_2->file1 ? HtmlLib::btn(url('img/License/'.SHCSLib::encode($dataL_2->id).'?sid=A'),'下載',3) : '';
                                $licenseStr .= $dataL_2->file2 ? HtmlLib::btn(url('img/License/'.SHCSLib::encode($dataL_2->id).'?sid=B'),'下載',3) : '';
                                $licenseStr .= $dataL_2->file3 ? HtmlLib::btn(url('img/License/'.SHCSLib::encode($dataL_2->id).'?sid=C'),'下載',3) : '';
                            }
                        }

                        
                        if($isApply == 'Y') $licenseStr = HtmlLib::Color($licenseStr,'blue');
                        $identityApplyAry[$iid]['license']          = $licenseAry;
                        $identityApplyAry[$iid]['licenseAllName']   = $licenseStr;
                        $identityApplyAry[$iid]['isOk']             = 'Y';
                    }
                }
            }
        }

        return $identityApplyAry;
    }

}
