<?php

namespace App\Http\Traits\Engineering;

use App\Lib\LogLib;
use App\Model\User;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Factory\b_rfid;
use App\Model\View\view_user;
use App\Model\View\view_used_rfid;
use Illuminate\Support\Facades\DB;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_c;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_project_s;
use App\Model\Engineering\et_traning_m;
use App\Model\Supply\b_supply_member_ei;
use App\Model\Engineering\e_license_type;
use App\Model\Engineering\e_project_license;
use App\Model\Engineering\e_violation_contractor;

/**
 * 工程案件_承攬商成員
 *
 */
trait EngineeringMemberTrait
{
    public function createEngineeringMemberGroup($data,$mod_user = 1)
    {
        $ret = false;
        $suc = $err = 0;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->member) && count($data->member)) return $ret;

        foreach ($data->member as $uid)
        {
            $UPD = [];
            $UPD['e_project_id']    = $data->e_project_id;
            $UPD['b_supply_id']     = $data->b_supply_id;
            $UPD['b_cust_id']       = $uid;
            $UPD['job_kind']        = 5;
            $UPD['cpc_tag']         = 'C';
            if($this->createEngineeringMember($UPD,$mod_user))
            {
                $suc++;
            } else {
                $err++;
            }
        }
        return $suc;
    }
    /**
     * 新增 工程案件_承攬商成員
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineeringMember($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->e_project_id) || !isset($data->b_supply_id)) return $ret;

        $INS = new e_project_s();
        $INS->e_project_id  = $data->e_project_id;
        $INS->b_supply_id   = $data->b_supply_id;
        $INS->b_cust_id     = $data->b_cust_id;
        $INS->job_kind      = isset($data->job_kind)? $data->job_kind : 5;
        $INS->cpc_tag       = isset($data->cpc_tag)? $data->cpc_tag : 'C';
        $INS->isUT          = isset($data->isUT)? $data->isUT : 'C';
        if($INS->isUT == 'Y')
        {
            $INS->cpc_tag   = 'D';
            $INS->ut_sdate  = date('Y-m-d');
            $INS->ut_edate  = '9999-12-31';
        }

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret)
        {
//            $isCreateIdentity = isset($data->isCreateIdentity)? $data->isCreateIdentity : 0;
//            //如果有工程身份申請，則配對工程身份
//            if($isCreateIdentity && is_array($data->identity) && count($data->identity))
//            {
//                $this->createEngineeringMemberIdentityGroup($data,$mod_user);
//            }
        }
        return $ret;
    }

    /**
     * 修改 工程案件_承攬商成員_中油標籤
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function chgEngineeringMemberCPCTag($project_id,$b_cust_id,$cpc_tag,$job_kind = 0,$mod_user = 1)
    {
        if(!$project_id || !$b_cust_id || !$cpc_tag) return false;

        $myIdentity = e_project_license::isWhoIdenttity($project_id,$b_cust_id);

        $data = e_project_s::where('e_project_id',$project_id)->where('b_cust_id',$b_cust_id)->where('isClose','N')->first();
        if(isset($data->id))
        {
            if(in_array($myIdentity,[1,2]) && in_array($data->cpc_tag,['A','B']))
            {
                //不動
            } else {
                if($cpc_tag == 'C' && $data->isUT == 'Y') $cpc_tag = 'D';
                $data->cpc_tag = $cpc_tag;
                if($job_kind)
                {
                    $data->job_kind = $job_kind;
                }
                $data->mod_user = $mod_user;
                return $data->save();
            }
        }
        return false;
    }

    /**
     * 修改 工程案件_承攬商成員
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setEngineeringMember($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = e_project_s::find($id);
        if(!isset($UPD->b_cust_id)) return $ret;
        //特殊腳色
        if(isset($data->job_kind) && is_numeric($data->job_kind) && $data->job_kind !== $UPD->job_kind)
        {
            $isUp++;
            $UPD->job_kind = $data->job_kind;
        }
        //中油標籤
        if(isset($data->cpc_tag) && strlen($data->cpc_tag) && $data->cpc_tag !== $UPD->cpc_tag)
        {
            $isUp++;
            $UPD->cpc_tag = $data->cpc_tag;
        }
        //中油標籤
        if(isset($data->isUT) && strlen($data->isUT) && $data->isUT !== $UPD->isUT)
        {
            $isUp++;
            $UPD->isUT = $data->isUT;
            if(in_array($UPD->cpc_tag,['C','D']))
            {
                $UPD->cpc_tag = ($UPD->isUT == 'Y')? 'D' : 'C';
            }
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
     * 取得 工程案件_承攬商成員
     *
     * @return array
     */
    public function getApiEngineeringMemberList($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        $sid        = e_project::getSupply($pid);
        $isOver     = (e_project::isExist($pid,0,'Y'))? 'Y' : 'N';
        $jobkindAry = SHCSLib::getCode('JOB_KIND');
        $utAry      = SHCSLib::getCode('UT_KIND');
        $passAry    = LogLib::getCoursePassSelect($sid); //承攬商教育訓練通過名單
        $WLAry      = LogLib::getWhiteListSelect($sid); //承攬商門禁白名單
        $kindColor  = e_project_s::getJobKindColorSet();
        list($project_aproc,$project_edate) = e_project::getProjectList1($pid);
        //dd([$passAry,$WLAry]);
        //取第一層
        //案件狀態為結案/過期時，顯示所有包含已停用的資料
        if (!in_array($project_aproc,['O','C'])) {
            $sWhere = " AND sj.isClose = 'N' ";
            $sWhere2 = " AND sut.isClose = 'N' ";
        }else{
            $sWhere = " AND 1 = 1 ";
            $sWhere2 = " AND 1 = 1 ";
        }

        $groupBy = array(
            'u.name', 'ua.sex', 'ua.bc_id', 'ua.mobile1', 'ua.birth', 'ua.kin_user',
           'ua.kin_tel', 'ua.addr1', 'el.name', 'el.isClose', 'el.edate',
           'el.aproc', 's.name', 'el.id',
           'e_project_s.b_cust_id', 'e_project_s.e_project_id'
       );
        $field = array(
             'u.name as user', 'ua.sex', 'ua.bc_id', 'ua.mobile1', 'ua.birth', 'ua.kin_user',
            'ua.kin_tel', 'ua.addr1', 'el.name as project', 'el.isClose as project_close', 'el.edate as project_edate',
            'el.aproc as project_aproc', 's.name as b_supply',
            'e_project_s.b_cust_id', 'e_project_s.e_project_id',
            DB::raw("STUFF((SELECT DISTINCT ',' + CAST(job_kind AS nvarchar(5))+ CAST(cpc_tag AS nvarchar(5)) FROM e_project_s sj WHERE sj.b_cust_id = e_project_s.b_cust_id AND sj.e_project_id = el.id {$sWhere} for xml path('')),1,1,'') AS job_kind"),
            DB::raw("STUFF((SELECT DISTINCT ',' + CAST(isUT AS nvarchar(5)) FROM e_project_s sut WHERE sut.b_cust_id = e_project_s.b_cust_id AND sut.e_project_id = el.id {$sWhere2} for xml path('')),1,1,'') AS isUT")
        );

        $data = e_project_s::
        join('e_project as el','e_project_s.e_project_id','=','el.id')->
        join('b_supply as s','e_project_s.b_supply_id','=','s.id')->
        join('b_cust as u','e_project_s.b_cust_id','=','u.id')->
        join('b_cust_a as ua','ua.b_cust_id','=','u.id')->
        // where('u.id','2000000153')->
        select($field)->
        groupby($groupBy)->
        where('e_project_s.e_project_id',$pid);

        //案件狀態為結案/過期時，顯示所有包含已停用的資料
        if (!in_array($project_aproc,['O','C'])) {
            $data = $data->where('e_project_s.isClose', 'N')->addSelect('e_project_s.id')->groupby('e_project_s.id','e_project_s.isClose');
        }

        $data =$data->orderBy('e_project_s.b_cust_id')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v) {
                //將e_project_s裡面多筆角色資料用切割後，合併為一筆資料顯示
                $job_kind_Ary = explode(',', $v->job_kind);
                $bIsUT = in_array('Y',explode(',', $v->isUT));  //是否尿檢
                $bIsUT_C = in_array('C',explode(',', $v->isUT));  //是否有不需尿檢

                foreach ($job_kind_Ary as $key => $val) {
                    $job_kind = substr($val, 0, 1);                 //取得第一個字元為專案角色
                    $cpc_tag = substr($val, 1, 1);                  //取得第二個字元為中油標籤

                    //中油承攬商成員標籤
                    switch ($cpc_tag) {
                        //工負
                        case 'A':
                            $data[$k]['cpc_tag_report']  = ($bIsUT) ? $cpc_tag . '、' . 'D' :  $cpc_tag;
                            break;
                        //工安
                        case 'B':
                            $data[$k]['cpc_tag_report']  = ($bIsUT) ? $cpc_tag . '、' . 'D' :  $cpc_tag;
                            break;
                        //特殊進出人員
                        case 'E':
                            $data[$k]['cpc_tag_report']  = ($bIsUT) ?  'D' . '、'  . $cpc_tag :  $cpc_tag;
                            break;
                        //施工人員
                        default:
                            $data[$k]['cpc_tag_report']  = ($bIsUT) ?  'D' :  'C';
                    }
                    
                    //專案角色
                    $color = (isset($kindColor[$job_kind])) ? $kindColor[$job_kind] : 6;
                    $data[$k]['job_kind'] = isset($jobkindAry[$job_kind]) ? $job_kind : '';
                    $data[$k]['job_kind_name']  = isset($jobkindAry[$job_kind]) ? HtmlLib::btn('#', $jobkindAry[$job_kind] . '(' . $data[$k]['cpc_tag_report'] . ')', $color) : '';
                    
                }
                //尿檢
                // $data[$k]['ut_name']  = ($v->isUT == 'Y' && isset($utAry[$v->isUT]))? $utAry[$v->isUT] : '';

                //尿檢
                $isUT_Ary = explode(',', $v->isUT);
                foreach ($isUT_Ary as $key2 => $val2) {
                    if (!in_array($project_aproc, ['O', 'C'])) {
                        $data[$k]['isUT']  = (isset($utAry[$val2])) ? $val2 : '';
                        $data[$k]['ut_name']  = ($val2 == 'Y' && isset($utAry[$val2])) ? $utAry[$val2] : '';
                    } else {
                        //案件狀態為結案/過期時，尿檢以 1.需要尿檢為優先 2.不需尿檢 3.尚未尿檢
                        if ($bIsUT) {
                            $data[$k]['isUT']  = 'Y';
                            $data[$k]['ut_name']  = $utAry['Y'];
                        } elseif (!$bIsUT && $bIsUT_C) {
                            $data[$k]['isUT']  = 'C';
                            $data[$k]['ut_name']  = '';
                        } else {
                            $data[$k]['isUT']  = 'N';
                            $data[$k]['ut_name']  = '';
                        }
                    }
                }

                //工程身分
                $data[$k]['identitylist']   = e_project_license::getUserIdentityAllName($pid,$v->b_cust_id);

                //上過通過的教育訓練
                $data[$k]['courselist']     = et_traning_m::getSelfPassCourse($v->b_cust_id);

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

                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }


    /**
     * 取得 工程案件_承攬商成員「ＡＰＰ」
     *
     * @return array
     */
    public function getApiEngineeringMember($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        $jobkindAry = SHCSLib::getCode('JOB_KIND');
        $utAry      = SHCSLib::getCode('UT_KIND');
        //dd([$passAry,$WLAry]);
        //取第一層
        $data = e_project_s::
                join('view_user as u','e_project_s.b_cust_id','=','u.b_cust_id')->
                select('e_project_s.id','e_project_s.b_cust_id','e_project_s.job_kind','e_project_s.cpc_tag',
            'e_project_s.isUT','u.name as user','u.mobile1 as mobile')->
                where('e_project_s.e_project_id',$pid)->where('e_project_s.isClose','N');

        $data =$data->orderby('job_kind','desc')->get() ;
        if(is_object($data))
        {
            foreach ($data as $k => $val)
            {
                $data[$k]['job_kind_name']  = isset($jobkindAry[$val->job_kind])? $jobkindAry[$val->job_kind] : '';
                $data[$k]['ut_name']        = isset($utAry[$val->isUT])? $utAry[$val->isUT] : '';
                $data[$k]['identitylist']   = e_project_license::getUserIdentityAllName($pid,$val->b_cust_id,2);
                $data[$k]['courselist']     = et_traning_m::getSelfPassCourse($val->b_cust_id,[],1);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
