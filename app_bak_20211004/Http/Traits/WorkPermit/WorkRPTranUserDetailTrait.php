<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_d;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_pipeline;
use App\Model\WorkPermit\wp_permit_shift;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_work_rp_extension;
use App\Model\WorkPermit\wp_work_rp_tranuser;
use App\Model\WorkPermit\wp_work_rp_tranuser_a;
use App\Model\WorkPermit\wp_work_worker;
use Illuminate\Database\Eloquent\Model;

/**
 * 工作許可證 轉單工單申請單
 *
 */
trait WorkRPTranUserDetailTrait
{
    /**
     * 新增 轉單工單申請單
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkRPTranUserDetailTrait($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;

        $INS = new wp_work_rp_tranuser_a();
        $INS->wp_work_rp_tranuser_id= $data->wp_work_rp_tranuser_id;
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->wp_work_worker_id     = wp_work_worker::isLock($data->wp_work_id,$data->b_cust_id);
        $INS->b_cust_id             = $data->b_cust_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;


        return $ret;
    }

    /**
     * 修改 轉單工單申請單
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkRPTranUserDetailTrait($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_rp_tranuser_a::find($id);
        if(!isset($UPD->wp_work_id)) return $ret;
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
     * 取得 轉單工單申請單
     *
     * @return array
     */
    public function getApiWorkRPTranUserDetailTrait($id)
    {
        $ret = [];
        //取第一層
        $data = wp_work_rp_tranuser_a::join('b_cust as c','c.id','=','wp_work_rp_tranuser_a.b_cust_id')->
        where('wp_work_rp_tranuser_a.wp_work_rp_tranuser_id',$id)->where('wp_work_rp_tranuser_a.isClose','N')->
        select('wp_work_rp_tranuser_a.*','c.name');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $tmp = [];
                $tmp['b_cust_id']   = $val->b_cust_id;
                $tmp['name']        = $val->name;
                $ret[] = $tmp;
            }
        }
        return $ret;
    }

}
