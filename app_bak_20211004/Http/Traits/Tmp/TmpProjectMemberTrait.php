<?php

namespace App\Http\Traits\Tmp;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\Tmp\t_project_member;
use App\Model\User;
use App\Model\View\view_door_supply_member;
use DB;
use App\Model\View\view_user;

/**
 * 中油大林廠介接資料庫之工程案件的承攬商成員
 *
 */
trait TmpProjectMemberTrait
{
    /**
     * 新增 承攬商
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createTmpProjectMember($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new t_project_member();
        $INS->e_project_id  = $data->e_project_id;
        $INS->b_supply_id   = $data->b_supply_id;
        $INS->b_cust_id     = $data->b_cust_id;
        $INS->project_no    = $data->project_no;
        $INS->name          = $data->name;
        $INS->bc_id         = $data->bc_id;
        $INS->chk_date      = date('Y-m-d');
        $INS->sdate         = $data->sdate;
        $INS->edate         = $data->edate;
        $INS->sdate_str     = $data->sdate_str;
        $INS->edate_str     = $data->edate_str;
        $INS->tax_num       = $data->tax_num ? $data->tax_num : '';


        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    public function createTmpProjectMemberLoop($data,$mod_user = 1)
    {
        $isRep = $isSuc = $isErr = 0;
        $today = date('Y-m-d');

        //1. 因為人員資料有問題，每次更新只好作廢
        $UPD = [];
        $UPD['isClose'] = 'Y';
        $UPD['close_user']  = $mod_user;
        $UPD['close_stamp'] = date('Y-m-d H:i:s');
        DB::table('t_project_member')
            ->where('chk_date', $today)
            ->update($UPD);

        foreach ($data as $val)
        {
            $tmp = $val;
            list($tmp->e_project_id,$tmp->b_supply_id) = e_project::getId($val->project_no);
            $tmp->b_cust_id    = view_user::isBCIDExist($val->bc_id);

            if($this->createTmpProjectMember($tmp,$mod_user))
            {
                $isSuc++;
            } else {
                $isErr++;
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
    public function setTmpProjectMember($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = t_project_member::find($id);
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
    public function getApiTmpProjectMemberList($project_id)
    {
        $ret = $memberAry = array();
        //1. 中介系統
        $data2 = t_project_member::where('e_project_id',$project_id)->where('isClose','N')->orderby('id','desc');
//
        if($data2->count())
        {
            $data2 = $data2->get();
            foreach ($data2 as $k => $v)
            {
                $memberAry[$v->bc_id] = $v;
                $memberAry[$v->bc_id]['source'] = '大林';
            }
        }
        $data1 = view_door_supply_member::where('e_project_id',$project_id);
        if($data1->count())
        {
            $data1 = $data1->get();
            foreach ($data1 as $k => $v)
            {
                if(!isset($memberAry[$v->bc_id]))
                {
                    $tmp = [];
                    $tmp['project_no']  = $v->project_no;
                    $tmp['supply']      = $v->supply;
                    $tmp['b_supply_id'] = $v->b_supply_id;
                    $tmp['b_cust_id']   = $v->b_cust_id;
                    $tmp['name1']       = $v->name;
                    $tmp['bc_id1']      = $v->bc_id;
                    $tmp['bc_id']       = '';
                    $tmp['name']        = '';
                    $tmp['sdate_str']   = '';
                    $tmp['edate_str']   = '';
                    $tmp['source']      = '正式';

                    $memberAry[$v->bc_id] = $tmp;
                } else {
                    $memberAry[$v->bc_id]['source'] = '大林＆正式';
                    $memberAry[$v->bc_id]['name1']  = $v->name;
                    $memberAry[$v->bc_id]['bc_id1'] = $v->bc_id;
                }

            }
        }
        if(count($memberAry))
        {
            foreach ($memberAry as $v)
            {
                $ret[] = (object)$v;
            }
        }



        return  $ret;
    }
}
