<?php

namespace App\Http\Traits\Tmp;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\Tmp\t_project_member;
use App\Model\User;
use App\Model\Tmp\t_project;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_user;

/**
 * 中油大林廠介接資料庫之工程案件
 *
 */
trait TmpProjectTrait
{
    /**
     * 新增 承攬商
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createTmpProject($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new t_project();
        $INS->e_project_id  = $data->e_project_id;
        $INS->b_supply_id   = $data->b_supply_id;
        $INS->b_cust_id1    = $data->b_cust_id1;
        $INS->b_cust_id2    = $data->b_cust_id2;
        $INS->b_cust_id3    = $data->b_cust_id3;
        $INS->b_cust_id4    = $data->b_cust_id4;
        $INS->project_no    = $data->project_no;
        $INS->name          = $data->name;
        $INS->chk_date      = date('Y-m-d');
        $INS->sdate         = $data->sdate;
        $INS->edate         = $data->edate;
        $INS->sdate_str     = $data->sdate_str;
        $INS->edate_str     = $data->edate_str;
        $INS->tax_num       = $data->tax_num ? $data->tax_num : '';
        $INS->eng_pic_id    = $data->eng_pic_id ? $data->eng_pic_id : '';
        $INS->eng_pic       = $data->eng_pic ? $data->eng_pic : '';
        $INS->eng_pic_id1   = $data->eng_pic_id1 ? $data->eng_pic_id1 : '';
        $INS->eng_pic1      = $data->eng_pic1 ? $data->eng_pic1 : '';
        $INS->guard_id      = $data->guard_id ? $data->guard_id : '';
        $INS->guard         = $data->guard ? $data->guard : '';
        $INS->guard_id1     = $data->guard_id1 ? $data->guard_id1 : '';
        $INS->guard1        = $data->guard1 ? $data->guard1 : '';


        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    public function createTmpProjectLoop($data,$mod_user = 1)
    {
        $isRep = $isSuc = $isErr = 0;
        $today = date('Y-m-d');
        foreach ($data as $val)
        {
            $tmp = $val;
            list($tmp->e_project_id,$tmp->b_supply_id) = e_project::getId($val->project_no);
            $tmp->b_cust_id1   = view_user::isBCIDExist($val->eng_pic_id);
            $tmp->b_cust_id2   = view_user::isBCIDExist($val->eng_pic_id1);
            $tmp->b_cust_id3   = view_user::isBCIDExist($val->guard_id);
            $tmp->b_cust_id4   = view_user::isBCIDExist($val->guard_id1);

            if(t_project::isExist($today,$tmp->e_project_id))
            {
                $isRep++;
            } else {
                if($this->createTmpProject($tmp,$mod_user))
                {
                    $isSuc++;
                } else {
                    $isErr++;
                }
            }

        }
        return [$isSuc,$isErr,$isRep];
    }

    /**
     * 修改 承攬商
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setTmpProject($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = t_project::find($id);
        if(!isset($UPD->name)) return $ret;
        //公司名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //公司名稱
        if(isset($data->sub_name) && $data->sub_name && $data->sub_name !== $UPD->sub_name)
        {
            $isUp++;
            $UPD->sub_name = $data->sub_name;
        }
        //統編
        if(isset($data->tax_num) && is_numeric($data->tax_num) && $data->tax_num !== $UPD->tax_num)
        {
            $isUp++;
            $UPD->tax_num = $data->tax_num;
        }
        //負責人
        if(isset($data->boss_name) && strlen($data->boss_name) && $data->boss_name !== $UPD->boss_name)
        {
            $isUp++;
            $UPD->boss_name = $data->boss_name;
        }
        //電話1
        if(isset($data->tel1) && is_numeric($data->tel1) && $data->tel1 !== $UPD->tel1)
        {
            $isUp++;
            $UPD->tel1 = $data->tel1;
        }
        //電話2
        if(isset($data->tel2) && is_numeric($data->tel2) && $data->tel2 !== $UPD->tel2)
        {
            $isUp++;
            $UPD->tel2 = $data->tel2;
        }
        //傳真1
        if(isset($data->fax1) && is_numeric($data->fax1) && $data->fax1 !== $UPD->fax1)
        {
            $isUp++;
            $UPD->fax1 = $data->fax1;
        }
        //傳真2
        if(isset($data->fax2) && is_numeric($data->fax2) && $data->fax2 !== $UPD->fax2)
        {
            $isUp++;
            $UPD->fax2 = $data->fax2;
        }
        //信箱
        if(isset($data->email) && ($data->email) && $data->email !== $UPD->email)
        {
            $isUp++;
            $UPD->email = $data->email;
        }
        //地址
        if(isset($data->address) && ($data->address) && $data->address !== $UPD->address)
        {
            $isUp++;
            $UPD->address = $data->address;
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

                //需要停用所有該 承攬商成員
                b_supply_member::setClose($id,$mod_user);
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
     * 取得 承攬商
     *
     * @return array
     */
    public function getApiTmpProjectList()
    {
        $ret = array();
        $identity_A = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $identity_B = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
        //取第一層
        $data = t_project::where('chk_date',date('Y-m-d'))->where('isClose','N')->orderby('id','desc')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {

                $worker1    = view_door_supply_member::getProjectMemberSelect($v->e_project_id,[1,2],$identity_A,0,0);
                $worker2    = view_door_supply_member::getProjectMemberSelect($v->e_project_id,[1,3],$identity_B,0,0);

                $data[$k]['supply']  = b_supply::getName($v->b_supply_id);
                $data[$k]['worker1']  = implode('<br>',$worker1);
                $data[$k]['worker2']  = implode('<br>',$worker2);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 承攬商
     *
     * @return array
     */
    public function getApiTmpProjectDefList()
    {
        $ret = $projectAry = array();
        $identity_A = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $identity_B = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
        //取第一層
        $data1 = t_project::select('e_project_id')->where('chk_date',date('Y-m-d'))->where('isClose','N')->orderby('id','desc');
        if($data1->count())
        {
            $data1 = $data1->get();
            foreach ($data1 as $val)
            {
                $projectAry[] = $val->e_project_id;
            }
        }


        $data2 = e_project::where('isClose','N')->whereNotIn('id',$projectAry);
        $data2 = $data2->where('aproc','P')->where('edate','>=',date('Y-m-d'));

        if($data2->count())
        {
            $data2 = $data2->get();
            foreach ($data2 as $k => $v)
            {
                $worker1    = view_door_supply_member::getProjectMemberSelect($v->id,[1,2],$identity_A,0,0);
                $worker2    = view_door_supply_member::getProjectMemberSelect($v->id,[1,3],$identity_B,0,0);

                $data2[$k]['supply']   = b_supply::getName($v->b_supply_id);
                $data2[$k]['worker1']  = implode('<br>',$worker1);
                $data2[$k]['worker2']  = implode('<br>',$worker2);
            }
            $ret = (object)$data2;
//            dd($ret);
        }

        return $ret;
    }
}
