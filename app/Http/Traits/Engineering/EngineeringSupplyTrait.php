<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_project_a;
use App\Model\User;

/**
 * 承攬項目_協力承攬商
 *
 */
trait EngineeringSupplyTrait
{
    public function createEngineeringSupplyGroup($data,$mod_user = 1)
    {
        $ret = false;
        $suc = $err = 0;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->member) && count($data->member)) return $ret;

        foreach ($data->supply as $val)
        {
            $UPD = [];
            $UPD['e_project_id']    = $data->e_project_id;
            $UPD['b_supply_id']     = $val->b_supply_id;
            if($this->createEngineeringSupply($UPD,$mod_user))
            {
                $suc++;
            } else {
                $err++;
            }
        }
        return $suc;
    }
    /**
     * 新增 承攬項目_協力承攬商
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineeringSupply($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->e_project_id) || !isset($data->b_supply_id)) return $ret;

        $INS = new e_project_a();
        $INS->e_project_id  = $data->e_project_id;
        $INS->b_supply_id   = $data->b_supply_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 承攬項目_協力承攬商
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setEngineeringSupply($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = e_project_a::find($id);
        if(!isset($UPD->b_supply_id)) return $ret;
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
     * 取得 承攬項目_協力承攬商
     *
     * @return array
     */
    public function getApiEngineeringSupplyList($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        //dd([$passAry,$WLAry]);
        //取第一層
        $data = e_project_a::join('b_supply as s','e_project_a.b_supply_id','=','s.id')->
                select('e_project_a.*','s.name as b_supply')->
                where('e_project_a.e_project_id',$pid)->where('e_project_a.isClose','N');

        $data =$data->get() ;
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }


}
