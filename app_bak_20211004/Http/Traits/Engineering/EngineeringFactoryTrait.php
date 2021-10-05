<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_f;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_license_type;
use App\Model\Engineering\e_project_s;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_d;
use App\Model\User;

/**
 * 工程案件_廠區
 *
 */
trait EngineeringFactoryTrait
{
    public function createEngineeringFactoryGroup($data,$mod_user = 1)
    {
        $ret = false;
        $suc = $err = 0;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->store) && count($data->store)) return $ret;

        foreach ($data->store as $fid)
        {
            $UPD = [];
            $UPD['e_project_id']    = $data->e_project_id;
            $UPD['b_factory_id']    = b_factory_a::getStoreId($fid);
            $UPD['b_factory_a_id']  = $fid;
            if($this->createEngineeringFactory($UPD,$mod_user))
            {
                $suc++;
            } else {
                $err++;
            }
        }
        return $suc;
    }
    /**
     * 新增 工程案件_廠區
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineeringFactory($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->e_project_id) || !isset($data->b_factory_a_id)) return $ret;

        $INS = new e_project_f();
        $INS->e_project_id      = $data->e_project_id;
        $INS->b_factory_id      = $data->b_factory_id;
        $INS->b_factory_a_id    = $data->b_factory_a_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工程案件_廠區
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setEngineeringFactory($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = e_project_f::find($id);
        if(!isset($UPD->b_factory_a_id)) return $ret;
        //廠區
        if(isset($data->b_factory_a_id) && is_numeric($data->b_factory_a_id) && $data->b_factory_a_id !== $UPD->b_factory_a_id)
        {
            $isUp++;
            $UPD->b_factory_id   = b_factory_a::getStoreId($data->b_factory_id);
            $UPD->b_factory_a_id = $data->b_factory_a_id;
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
     * 取得 工程案件_廠區
     *
     * @return array
     */
    public function getApiEngineeringFactoryList($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        //dd([$passAry,$WLAry]);
        //取第一層
        $data = e_project_f::
                join('e_project as el','e_project_f.e_project_id','=','el.id')->
                join('b_factory as s','e_project_f.b_factory_id','=','s.id')->
                join('b_factory_a as sa','e_project_f.b_factory_a_id','=','sa.id')->
                select('e_project_f.*','el.name as project','s.name as store','sa.name as local')->
                where('e_project_f.e_project_id',$pid)->where('e_project_f.isClose','N');

        $data =$data->orderby('id','desc')->get() ;
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

    /**
     * 取得 工程案件_廠區(For APP)
     *
     * @return array
     */
    public function getApiEngineeringFactory($pid ,$isDetial = 'N')
    {
        $ret = array();
        if(!$pid) return $ret;
        //取第一層
        $data = e_project_f::
                join('b_factory as s','e_project_f.b_factory_id','=','s.id')->
                join('b_factory_a as sa','e_project_f.b_factory_a_id','=','sa.id')->
                select('e_project_f.b_factory_id','e_project_f.b_factory_a_id as id','s.name as store','sa.name as local')->
                where('e_project_f.e_project_id',$pid)->where('e_project_f.isClose','N');

        $data =$data->orderby('id','desc')->get() ;
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id']          = $v->id;
                $tmp['store']       = $v->store;
                $tmp['name']        = $v->local;
                //施工地點
                if($isDetial == 'Y')
                {
                    $tmp['works_area'] = $this->getApiFactoryDeviceReply($v->id);
                    $tmp['door']       = b_factory_d::getApiSelect($v->b_factory_id,0);
                }

                $ret[$v->id] = $tmp;
            }
            sort($ret);
        }
        //dd([2,$ret]);
        return $ret;
    }

}
