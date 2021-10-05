<?php

namespace App\Http\Traits\Engineering;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project_history;
use App\Model\User;

/**
 * 工程案件異動階段歷程
 *
 */
trait EngineeringHistoryTrait
{
    /**
     * 新增 工程案件異動階段歷程
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineeringHistory($project_id,$data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!$project_id || !isset($data->old_aproc)) return $ret;

        $INS = new e_project_history();
        $INS->e_project_id  = $project_id;
        $INS->old_aproc     = $data->old_aproc;
        $INS->new_aproc     = $data->new_aproc;
        $INS->old_edate     = isset($data->old_edate)? $data->old_edate : '';
        $INS->chg_edate     = isset($data->chg_edate)? $data->chg_edate : '';
        $INS->chg_memo      = isset($data->chg_memo)? $data->chg_memo : '';
        $INS->aproc_user    = $mod_user;
        $INS->aproc_memo    = isset($data->aproc_memo)? $data->aproc_memo : '';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 取得 工程案件異動階段歷程
     *
     * @return array
     */
    public function getApiEngineeringHistoryList($project_id)
    {
        $ret = array();
        //取第一層
        $data = e_project_history::where('e_project_id',$project_id);
//        dd($project_id,$data->count(),$data->get());
        if($data->count())
        {
            $aprocAry  = SHCSLib::getCode('ENGINEERING_APROC');
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['old_aproc']   = isset($aprocAry[$v->old_aproc])? $aprocAry[$v->old_aproc] : '';
                $tmp['new_aproc']   = isset($aprocAry[$v->new_aproc])? $aprocAry[$v->new_aproc] : '';
                $tmp['old_edate']   = ($v->old_edate);
                $tmp['chg_edate']   = ($v->chg_edate);
                $tmp['chg_memo']    = ($v->chg_memo);
                $tmp['aproc_memo']  = ($v->aproc_memo);
                $tmp['aproc_user']  = User::getName($v->new_user);
                $tmp['aproc_stamp'] = substr($v->updated_at,0,19);
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

}
