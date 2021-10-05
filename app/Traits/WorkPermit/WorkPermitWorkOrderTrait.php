<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_title;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\View\view_user;
use App\Model\View\view_wp_work;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_shift;
use App\Model\WorkPermit\wp_work_check;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_rp_extension;
use App\Model\WorkPermit\wp_work_worker;
use Session;
use App\Model\WorkPermit\wp_work;
use Lang;

/**
 * 工作許可證-執行單
 *
 */
trait WorkPermitWorkOrderTrait
{
    /**
     * 新增 工作許可證-執行單
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkOrder($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_permit_id)) return $ret;
        //工負
        $supply_worker      = (isset($data->supply_worker) && count($data->supply_worker))? $data->supply_worker : [];
        //工安
        $supply_safer       = (isset($data->supply_safer) && count($data->supply_safer))? $data->supply_safer : [];
        //施工人員
        $identityMemberAry  = (isset($data->identityMember) && count($data->identityMember))? $data->identityMember : [];

        $now = date('Y-m-d');
        //工程案件
        $projectAry = e_project::find($data->e_project_id);
        if(!isset($projectAry->id))  return $ret;

        $INS = new wp_work();
        $INS->e_project_id              = $data->e_project_id;
        $INS->wp_permit_id              = $data->wp_permit_id;
        $INS->permit_no                 = wp_work::getNewPermitNo($data->sdate);
        $INS->b_factory_id              = $data->b_factory_id;
        $INS->b_factory_a_id            = $data->b_factory_a_id;
        $INS->b_factory_b_id            = $data->b_factory_b_id;
        $INS->b_factory_d_id            = $data->b_factory_d_id;
        $INS->be_dept_id2               = $data->be_dept_id2;
        $INS->proejct_charge            = $data->project_charge; //指定負責人
        $INS->b_factory_memo            = $data->b_factory_memo;
        $INS->b_supply_id               = $data->b_supply_id;
        $INS->wp_permit_shift_id        = $data->wp_permit_shift_id;
        //週日開單 A級
        if(in_array(date('w',strtotime($data->sdate)),[0,6])){
            $INS->wp_permit_danger      = 'A';
            $INS->isHoliday             = 'Y';
        }
        $INS->wp_permit_workitem_memo   = $data->wp_permit_workitem_memo;
        $INS->sdate                     = $data->sdate;
        $INS->edate                     = $data->edate;
        $INS->apply_date                = date('Y-m-d');
        $INS->apply_stamp               = date('Y-m-d H:i:s');
        $INS->apply_user                = $mod_user;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret)
        {
            //dd($data->b_supply_id,$projectAry->charge_dept,$data->e_project_id);
            //推播: 承攬商申請工作許可證，通知 工程案件之監造部門
            $this->pushToSupplyApplyPermit($ret,$data->b_supply_id,$projectAry->charge_dept,$data->e_project_id);
            //工地負責人
            foreach ($supply_worker as $val)
            {
                $uid = isset($val->uid)? $val->uid : 0;
                if(!$uid) continue;
                $tmp = [];
                $tmp['wp_work_id']  = $ret;
                $tmp['user_id']     = $uid;
                $tmp['apply_type']  = 1;
                $tmp['engineering_identity_id'] = 1;
                $tmp['isGuest'] = 'N';
                $this->createWorkPermitWorker((object)$tmp,$mod_user);
            }
            //工安人員
            foreach ($supply_safer as $val)
            {
                $uid = isset($val->uid)? $val->uid : 0;
                if(!$uid) continue;
                $tmp = [];
                $tmp['wp_work_id']      = $ret;
                $tmp['user_id']         = $uid;
                $tmp['apply_type']      = 1;
                $tmp['engineering_identity_id'] = 2;
                $tmp['isGuest'] = 'N';
                $this->createWorkPermitWorker((object)$tmp,$mod_user);
            }

            //施工人員
            foreach ($identityMemberAry as $val)
            {
                $uid = isset($val->uid)? $val->uid : 0;
                $iid = isset($val->iid)? $val->iid : 0;
                if(!$uid || !$iid) continue;
                $tmp = [];
                $tmp['wp_work_id'] = $ret;
                $tmp['user_id'] = $uid;
                $tmp['apply_type'] = 1;
                $tmp['engineering_identity_id'] = $iid;
                $tmp['isGuest'] = 'N';
                $this->createWorkPermitWorker((object)$tmp,$mod_user);
            }
        }

        return $ret;
    }


    /**
     * 停工 工作許可證-執行單
     * @param $id
     * @param int $mod_user
     */
    public function stopWorkPermitWorkOrder($work_id,$charge_memo,$charge_sign = '',$mod_user = 1)
    {
        $ret = false;
        if(!$work_id) return $ret;

        $listData = wp_work_list::getData($work_id);
        if(!isset($listData->id)) return $ret;
        $list_id            = $listData->id;
        $wp_work_process_id = $listData->wp_work_process_id;
        if( in_array($listData->id,['F','C'])) return $ret;
        $wp_work_img_id = 0;
        $imgPath        = '';

        //如果有簽名
        if($charge_sign)
        {
            $INS = [];
            $INS['wp_work_id']          = $work_id;
            $INS['wp_work_list_id']     = $list_id;
            $INS['wp_work_process_id']  = $wp_work_process_id;
            $filepath = config('mycfg.permit_path').date('Y/m/').$work_id.'/';
            $filename = $work_id.'_reject_'.time().'.jpg';
            $imgPath  = $filepath.$filename;
            $wp_work_img_id = $this->createWorkPermitWorkImg($INS,$filepath,$filename,$charge_sign,0,$mod_user);
        }


        $tmp = [];
        $tmp['aproc'] = 'C';
        $tmp['charge_memo']     = $charge_memo;
        $tmp['charge_sign']     = ($wp_work_img_id)? $imgPath : '';
        $tmp['wp_work_img_id']  = $wp_work_img_id;
        return $this->setWorkPermitWorkOrderList($list_id,$tmp,$mod_user);
    }
    /**
     * 修改 工作許可證-執行單
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkOrder($id,$data,$mod_user = 1,$hrefMain = '')
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work::find($id);
        if(!isset($UPD->wp_permit_id)) return $ret;
        //轄區部門
        if(isset($data->be_dept_id1) && $data->be_dept_id1 && $data->be_dept_id1 !== $UPD->be_dept_id1)
        {
            $isUp++;
            $UPD->be_dept_id1 = $data->be_dept_id1;
            $UPD->be_dept_id5 = be_dept::getParantDept($data->be_dept_id1);
        }
        //監工部門
        if(isset($data->be_dept_id3) && $data->be_dept_id3 && $data->be_dept_id3 !== $UPD->be_dept_id3)
        {
            $isUp++;
            $UPD->be_dept_id3 = $data->be_dept_id3;
        }
        //會簽部門
        if(isset($data->be_dept_id4) && $data->be_dept_id4 && $data->be_dept_id4 !== $UPD->be_dept_id4)
        {
            $isUp++;
            $UPD->be_dept_id4 = $data->be_dept_id4;
        }
        //廠區->場地
        if(isset($data->b_factory_a_id) && $data->b_factory_a_id && $data->b_factory_a_id !== $UPD->b_factory_a_id)
        {
            $isUp++;
            $UPD->b_factory_a_id = $data->b_factory_a_id;
        }
        //廠區->door
        if(isset($data->b_factory_d_id) && $data->b_factory_d_id && $data->b_factory_d_id !== $UPD->b_factory_d_id)
        {
            $isUp++;
            $UPD->b_factory_d_id = $data->b_factory_d_id;
        }
        //廠區->工作地點說明
        if(isset($data->b_factory_memo) && $data->b_factory_memo && $data->b_factory_memo !== $UPD->b_factory_memo)
        {
            $isUp++;
            $UPD->b_factory_memo = $data->b_factory_memo;
        }
        //廠區->工作地點說明(追加)
        if(isset($data->b_factory_memo2) && $data->b_factory_memo2 && $data->b_factory_memo2 !== $UPD->b_factory_memo)
        {
            $isUp++;
            $UPD->b_factory_memo2 = $UPD->b_factory_memo;
            $UPD->b_factory_memo  = $data->b_factory_memo2;
        }
        //危險等級
        if(isset($data->wp_permit_danger) && in_array($data->wp_permit_danger,['A','B','C']) && $data->wp_permit_danger !== $UPD->wp_permit_danger)
        {
            $isUp++;
            $UPD->wp_permit_danger = $data->wp_permit_danger;
        }
        //班別
        if(isset($data->wp_permit_shift_id) && $data->wp_permit_shift_id > 0 && $data->wp_permit_shift_id !== $UPD->wp_permit_shift_id)
        {
            $isUp++;
            $UPD->wp_permit_shift_id = $data->wp_permit_shift_id;
        }
        //工地負責人
        if(isset($data->supply_worker) && $data->supply_worker > 0 && $data->supply_worker !== $UPD->supply_worker)
        {
            $isUp++;
            $UPD->supply_worker = $data->supply_worker;
            $this->setWorkPermitWorkerMen(1,$id,$data->supply_worker,1,$mod_user);
        }
        //安全人員
        if(isset($data->supply_safer) && $data->supply_safer > 0 && $data->supply_safer !== $UPD->supply_safer)
        {
            $isUp++;
            $UPD->supply_safer = $data->supply_safer;
            $this->setWorkPermitWorkerMen(1,$id,$data->supply_safer,2,$mod_user);
        }
        //工作內容
        if(isset($data->wp_permit_workitem_memo) && $data->wp_permit_workitem_memo && $data->wp_permit_workitem_memo !== $UPD->wp_permit_workitem_memo)
        {
            $isUp++;
            $UPD->wp_permit_workitem_memo = $data->wp_permit_workitem_memo;
        }
        //工作內容(追加)
        if(isset($data->wp_permit_workitem_memo2) && $data->wp_permit_workitem_memo2 && $data->wp_permit_workitem_memo2 !== $UPD->wp_permit_workitem_memo)
        {
            $isUp++;
            $UPD->wp_permit_workitem_memo2 = $UPD->wp_permit_workitem_memo;
            $UPD->wp_permit_workitem_memo  = $data->wp_permit_workitem_memo2;
        }
        //施工日期
        if(isset($data->sdate) && $data->sdate && $data->sdate !== $UPD->sdate)
        {
            $isUp++;
            $UPD->sdate = $data->sdate;
        }
        //施工日期
        if(isset($data->edate) && $data->edate && $data->edate !== $UPD->edate)
        {
            $isUp++;
            $UPD->edate = $data->edate;
        }
        //簽核開始時間
        if(isset($data->stime1) && $data->stime1 && $data->stime1 !== $UPD->stime1)
        {
            $isUp++;
            $UPD->stime1 = $data->stime1;
        }
        //簽核結束時間
        if(isset($data->etime1) && $data->etime1 && $data->etime1 !== $UPD->etime1)
        {
            $isUp++;
            $UPD->etime1 = $data->etime1;
        }
        //
        if(isset($data->stime2) && $data->stime2 && $data->stime2 !== $UPD->stime2)
        {
            $isUp++;
            $UPD->stime2 = $data->stime2;
        }
        //
        if(isset($data->etime2) && $data->etime2 && $data->etime2 !== $UPD->etime2)
        {
            $isUp++;
            $UPD->etime2 = $data->etime2;
        }
        //預計工作結束時間
        if(isset($data->eta_time) && $data->eta_time && $data->eta_time !== $UPD->eta_time)
        {
            $isUp++;
            $UPD->eta_time = $data->eta_time;
        }

        //工作人員
        if(isset($data->identityMember) && count($data->identityMember))
        {
            //1. 取得舊的人員比對
            $oldAry  = Session::get($hrefMain.'.old_identityMemberAry',[]);
            foreach ($oldAry as $uid => $iid)
            {
                if(!isset($data->identityMember[$uid]))
                {
                    if($this->closeWorkPermitWorkerMen(2,$id,$uid,$iid,$mod_user))
                    {
                        $isUp++;
                    }
                }
            }
            //2. 針對新的名單比對
            foreach ($data->identityMember as $uid => $iid)
            {
                if($this->setWorkPermitWorkerMen(2,$id,$uid,$iid,$mod_user))
                {
                    $isUp++;
                }
            }
        }

        //許可工作項目
        if(isset($data->itemwork) && count($data->itemwork))
        {
            //1. 取得舊的資料比對
            $oldAry  = Session::get($hrefMain.'.old_itemworkAry',[]);
            if(count($oldAry))
            {
                foreach ($oldAry as $iid => $val)
                {
                    if($iid > 0 && !in_array($iid,$data->itemwork))
                    {
                        if($this->closeWorkPermitWorkOrderItem($id,$iid,$mod_user))
                        {
                            $isUp++;
                        }
                    }
                }
            }

            //2. 針對新的資料比對
            foreach ($data->itemwork as $iid => $val)
            {
                $memo = isset($val['memo'])? $val['memo'] : '';

                if($this->addWorkPermitWorkOrderItem($id,$iid,$memo,$mod_user))
                {
                    $isUp++;
                }
            }
        }
        //管線內容物
        if(isset($data->line) && count($data->line))
        {
            //1. 取得舊的資料比對
            $oldAry  = Session::get($hrefMain.'.old_lineAry',[]);
            if(count($oldAry))
            {
                foreach ($oldAry as $iid => $val)
                {
                    if($iid > 0 && !in_array($iid,$data->line))
                    {
                        if($this->closeWorkPermitWorkOrderLine($id,$iid,$mod_user))
                        {
                            $isUp++;
                        }
                    }
                }
            }

            //2. 針對新的資料比對
            foreach ($data->line as $iid => $val)
            {
                $memo = isset($val['memo'])? $val['memo'] : '';

                if($this->addWorkPermitWorkOrderLine($id,$iid,$memo,$mod_user))
                {
                    $isUp++;
                }
            }
        }

        //檢點單
        if(isset($data->check) && count($data->check))
        {
            //1. 取得舊的資料比對
            $oldAry  = Session::get($hrefMain.'.old_checkAry',[]);

            if(count($oldAry))
            {
                foreach ($oldAry as $iid)
                {
                    if($iid > 0 && !in_array($iid,$data->check))
                    {
                        if($this->closeWorkPermitWorkOrderCheck($id,$iid,$mod_user))
                        {
                            $isUp++;
                        }
                    }
                }
            }

            //2. 針對新的資料比對
            foreach ($data->check as $iid)
            {
                //dd([$data->check,$iid,$oldAry]);
                if($this->addWorkPermitWorkOrderCheck($id,$iid,$mod_user))
                {
                    $isUp++;
                }
            }
        }
        //危害告知
        if(isset($data->danger) && count($data->danger))
        {
            //1. 取得舊的資料比對
            $oldAry  = Session::get($hrefMain.'.old_dangerAry',[]);

            if(count($oldAry))
            {
                foreach ($oldAry as $iid)
                {
                    if($iid > 0 && !in_array($iid,$data->danger))
                    {
                        if($this->closeWorkPermitWorkOrderDanger($id,$iid,$mod_user))
                        {
                            $isUp++;
                        }
                    }
                }
            }

            //2. 針對新的資料比對
            foreach ($data->danger as $iid)
            {
                //dd([$data->check,$iid,$oldAry]);
                if($this->addWorkPermitWorkOrderDanger($id,$iid,$mod_user))
                {
                    $isUp++;
                }
            }
        }
        //審查
        if(isset($data->aproc) && in_array($data->aproc,['B','W','P','R','K','O','C','F']) && $data->aproc !== $UPD->aproc)
        {
            $isUp++;
            $UPD->aproc         = $data->aproc;
            if(in_array($data->aproc,['W','B']))
            {
                $UPD->charge_user   = $mod_user;
                $UPD->charge_stamp  = $now;
                $UPD->charge_memo   = isset($data->charge_memo)? $data->charge_memo : '';
            }

            //審查通過
            if($data->aproc == 'W')
            {
                if(!$UPD->proejct_charge)
                {
                    $UPD->proejct_charge   = $mod_user;
                }
                //審查通過人員
                $this->updateWorkPermitWorkerMenOk($id,$mod_user);
                //產生 流程管理單
                $tmp = [];
                $tmp['wp_work_id']              = $id;
                $tmp['pmp_kind']                = 1;
                $tmp['work_status']             = 1;
                $tmp['work_sub_status']         = 1;
                $tmp['wp_work_process_id']      = 0;
                $tmp['wp_permit_process_id']    = wp_permit_process::isStatusExist(1,1,1,1);
                $tmp['apply_user']              = $UPD->apply_user;
                $this->createWorkPermitWorkOrderList((object)$tmp,$mod_user);
            }
        }
        //延時
        if(isset($data->isOvertime) && in_array($data->isOvertime,['Y','N']) && $data->isOvertime !== $UPD->isOvertime)
        {
            $isUp++;
            $UPD->isOvertime = $data->isOvertime;
        }
        //假日
        if(isset($data->isHoliday) && in_array($data->isHoliday,['Y','N']) && $data->isHoliday !== $UPD->isHoliday)
        {
            $isUp++;
            $UPD->isHoliday = $data->isHoliday;
        }
        //延長收工申請
        if(isset($data->isApplyOvertime) && in_array($data->isApplyOvertime,['Y','N']) && $data->isApplyOvertime !== $UPD->isApplyOvertime)
        {
            $isUp++;
            $UPD->isApplyOvertime           = $data->isApplyOvertime;
            $UPD->wp_work_rp_extension_id   = $data->wp_work_rp_extension_id;
            $UPD->eta_time1                 = $UPD->eta_time2;
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
     * 取得 工作許可證-執行單 by 工程案件 尚未審查
     *
     * @return array
     */
    public function getApiWorkPermitWorkOrderByProject($dept2 = 0, $isCount = 'N')
    {
        $data = wp_work::selectRaw('MAX(e.id) as e_project_id,MAX(e.name) as project,MAX(e.project_no) as project_no,MAX(s.name) as supply,be_dept_id2,count(s.name) as amt')->
                join('e_project as e','e.id','=','wp_work.e_project_id')->
                join('b_supply as s','s.id','=','wp_work.b_supply_id')->
                whereIn('wp_work.aproc',['A'])->where('wp_work.isClose','N')->
                where('wp_work.sdate','>=',date('Y-m-d'));
        if($dept2)
        {
            $data = $data->where('wp_work.be_dept_id2',$dept2);
        }

        $ret = $data->groupby('wp_work.e_project_id')->groupby('wp_work.be_dept_id2')->get();
        if($isCount == 'Y')
        {
            $count = 0;
            foreach ($ret as $val)
            {
                $count += $val->amt;
            }
            return  $count;
        } else {
            return  $ret;
        }
    }
    /**
     * 取得 工作許可證-執行單 by 工程案件 尚未審查
     *
     * @return array
     */
    public function getApiWorkPermitWorkOrderByProcess($process_type,$dept_id,$title_id = 0)
    {
        $dept1 = $dept2 = $dept3 = $dept4 = $dept5 = 0;
        if($process_type == 5 && !in_array($title_id,[3,4])) return 0;
        if($process_type == 6 && !in_array($title_id,[4])) return 0;
        $data = view_wp_work::select('view_wp_work.id')->
                join('wp_work_list as e','e.wp_work_id','=','view_wp_work.id')->
                join('wp_work_process as p','p.id','=','e.wp_work_process_id')->where('view_wp_work.isClose','N');

        //轄區負責
        if($process_type == 1)
        {
            $dept1  = $dept_id;
            $data   = $data->whereIn('p.wp_permit_process_id',[4,5,6,17,18]);
        }
        //轄區負責
        if($process_type == 2)
        {
            $dept2  = $dept_id;
            $data   = $data->whereIn('p.wp_permit_process_id',[1]);
        }
        //監工負責
        if($process_type == 3)
        {
            $dept3  = $dept_id;
            $data   = $data->whereIn('p.wp_permit_process_id',[14]);
        }
        //會簽負責
        if($process_type == 4)
        {
            if($title_id == 3)
            {
                $dept1  = $dept_id;
            }
            $data   = $data->whereIn('p.wp_permit_process_id',[12]);
        }
        //主簽者負責
        if($process_type == 5)
        {
            $data     = $data->whereIn('p.wp_permit_process_id',[13]);
        }
        //廠區主簽者負責
        if($process_type == 6)
        {
            $data   = $data->whereIn('p.wp_permit_process_id',[15]);
        }

        if($dept1)
        {
            $data = $data->where('view_wp_work.be_dept_id1',$dept1);
        }
        if($dept2)
        {
            $data = $data->where('view_wp_work.be_dept_id2',$dept1);
        }
        if($dept3)
        {
            $data = $data->where('view_wp_work.be_dept_id3',$dept1);
        }
        if($dept4)
        {
            $data = $data->where('view_wp_work.be_dept_id4',$dept1);
        }

       return $data->count();
    }

    /**
     * 取得 工作許可證-執行單
     * @param $isSupply 查詢者身分: 承攬商身分 int (承攬商ID)
     * @return array
     */
    public function getApiWorkPermitWorkOrderList($isSupply ,$aproc = ['A'],$wpSearch = [0,0,'','',0],$storeSearch = [0,0,0],$depSearch = [0,0,0,0,0,0],$dateSearch=['','',''],$appSearch = ['N',0],$isGroupBy = 'N')
    {
        $ret = array();
        //承攬商ID，工程案件ID，工號，危險等級，班別
        list($b_supply_id,$e_project_id,$permit_no,$danger,$shift) = $wpSearch;
        //廠區，場地，施工地點
        list($b_factory_id,$b_factory_a_id,$b_factory_b_id) = $storeSearch;
        //全部部門，轄區部門，監造部門，，，
        list($be_dept_id,$be_dept_id1,$be_dept_id2,$be_dept_id3,$be_dept_id4,$be_dept_id5) = $depSearch;
        //施工日期，施工區間，現在
        list($sdate,$edate,$isNow) = $dateSearch;

        //APP show, User
        list($isApp,$user) = $appSearch;
        //dd($b_supply_id,$e_project_id,$permit_no,$danger,$shift);
        $aproc          = is_array($aproc)? $aproc : ($aproc ? [$aproc] : []);
        $aprocAry       = SHCSLib::getCode('PERMIT_APROC');
        $deptAry        = be_dept::getSelect(0,0,0,0,0,0);
        $shifAry        = wp_permit_shift::getSelect(); //班別
        $today          = date('Y-m-d');
        $isApi          = $isApp? 1 : 2;
        //取第一層
        $data = wp_work::join('b_supply as s','s.id','=','wp_work.b_supply_id')->
                         join('e_project as p','p.id','=','wp_work.e_project_id')->
                         join('b_factory as f','f.id','=','wp_work.b_factory_id')->
                         join('b_factory_a as fa','fa.id','=','wp_work.b_factory_a_id')->
                         join('b_factory_b as fb','fb.id','=','wp_work.b_factory_b_id')->
                         join('b_factory_d as fd','fd.id','=','wp_work.b_factory_d_id')->
                         where('wp_work.isClose','N');


        //承攬商ID
        if($b_supply_id)
        {
            $data = $data->where('wp_work.b_supply_id',$b_supply_id);
        }
        //工程案件ID
        if($e_project_id)
        {
            $data = $data->where('wp_work.e_project_id',$e_project_id);
        }
        //廠區
        if($b_factory_id)
        {
            if(is_array($b_factory_id))
            {
                $data = $data->whereIn('wp_work.b_factory_id',$b_factory_id);
            } else {
                $data = $data->where('wp_work.b_factory_id',$b_factory_id);
            }
        }
        //場地
        if($b_factory_a_id)
        {
            $data = $data->where('wp_work.b_factory_a_id',$b_factory_a_id);
        }
        //施工地點
        if($b_factory_b_id)
        {
            $data = $data->where('wp_work.b_factory_b_id',$b_factory_b_id);
        }
        //工號
        if($permit_no)
        {
            $data = $data->select('s.name as supply','wp_work.*');
            $data = $data->where(function ($query) use ($permit_no) {
                $query->where('wp_work.permit_no', 'like', '%'.$permit_no.'%')
                    ->orWhere('s.name', 'like', '%'.$permit_no.'%');
            });
        }
        //危險等級
        if($danger)
        {
            $data = $data->where('wp_work.wp_permit_danger',$danger);
        }
        //班別
        if($shift)
        {
            $data = $data->where('wp_work.wp_permit_shift_id',$shift);
        }
        //進度
        if(count($aproc))
        {
            $data = $data->whereIn('wp_work.aproc',$aproc);
        }
        //全部部門
        if($be_dept_id)
        {
            $data = $data->where(function ($query) use ($be_dept_id) {
                $query->where('wp_work.be_dept_id1', '=', $be_dept_id)
                    ->orWhere('wp_work.be_dept_id2', '=', $be_dept_id)
                    ->orWhere('wp_work.be_dept_id3', '=', $be_dept_id)
                    ->orWhere('wp_work.be_dept_id4', '=', $be_dept_id)
                    ->orWhere('wp_work.be_dept_id5', '=', $be_dept_id);
            });
        } else {
            if($be_dept_id1)
            {
                $data = $data->where('wp_work.be_dept_id1',$be_dept_id1);
            }
            if($be_dept_id2)
            {
                $data = $data->where('wp_work.be_dept_id2',$be_dept_id2);
            }
            if($be_dept_id3)
            {
                $data = $data->where('wp_work.be_dept_id3',$be_dept_id3);
            }
            if($be_dept_id4)
            {
                $data = $data->where('wp_work.be_dept_id4',$be_dept_id4);
            }
            if($be_dept_id5)
            {
                $data = $data->where('wp_work.be_dept_id5',$be_dept_id5);
            }
        }

        //是否僅列出 今日之後
        if($isNow == 'Y')
        {
            $data = $data->where('wp_work.sdate','>=',$today);
        } else {
            //工作日期
            if($sdate && !$edate)
            {
                if(time() > strtotime($today.' 08:00:00'))
                {
                    $data = $data->where('wp_work.sdate',$today);
                } else {
                    $yesterday = SHCSLib::addDay(-1,$sdate);
                    $data = $data->where(function ($query) use ($sdate,$yesterday) {
                        $query->where(function ($query2) use ($yesterday) {
                            $query2->where('wp_work.sdate',$yesterday)->where('wp_work.wp_permit_shift_id',2);
                        })->orWhere(function ($query2) use ($sdate) {
                            $query2->where('wp_work.sdate',$sdate);
                        });
                    });
                }

            }
            if($sdate && $edate)
            {
                $data = $data->where('wp_work.sdate','>=',$sdate);
                $data = $data->where('wp_work.sdate','<=',$edate);
            }
        }

        if($isGroupBy == 'Y')
        {
            $data = $data->selectRaw('p.name as headline,wp_work.e_project_id,p.project_no,s.sub_name as supply,wp_work.wp_permit_danger,COUNT(wp_work.e_project_id) as amt');
            $data = $data->groupby('wp_work.e_project_id')->groupby('p.project_no')->groupby('p.name')->groupby('s.sub_name')->groupby('wp_work.wp_permit_danger');
        } else {
            $data = $data->select('wp_work.*','s.name as supply','p.name as project','p.project_no as project_no',
                'f.name as store','fa.name as local','fb.name as device','fd.name as door');
            $data = $data->orderby('wp_work.permit_no');
        }
        $count= $data->count();
        //dd([$isSupply,$aproc,$wpSearch,$depSearch,$dateSearch,$count]);
        if($count)
        {
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                if($isGroupBy == 'Y')
                {
                    $data[$k]['title']              = Lang::get('sys_base.base_40223',['name'=>$v->wp_permit_danger]);
                    $data[$k]['unit']               = Lang::get('sys_base.base_40218');
                } else {
                    $data[$k]['aproc_name']         = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                    $data[$k]['shift_name']         = isset($shifAry[$v->wp_permit_shift_id])? $shifAry[$v->wp_permit_shift_id] : '';
                    //時間
                    $data[$k]['stime1']             = !is_null($v->work_stime)? substr($v->work_stime,0,16) : '';
                    $data[$k]['etime1']             = !is_null($v->work_etime)? substr($v->work_etime,0,16) : '';
                    $data[$k]['stime']              = !is_null($v->stime1)?     substr($v->stime1,0,16) : '';
                    $data[$k]['etime']              = !is_null($v->etime1)?     substr($v->etime1,0,16) : '';
                    $data[$k]['apply_stamp']        = !is_null($v->apply_stamp)?substr($v->apply_stamp,0,16) : '';
                    $data[$k]['eta_time']           = !is_null($v->eta_time)?substr($v->eta_time,0,16) : '';
                    //部門
                    $data[$k]['be_dept_id1_name']   = isset($deptAry[$v->be_dept_id1])? $deptAry[$v->be_dept_id1] : '';
                    $data[$k]['be_dept_id2_name']   = isset($deptAry[$v->be_dept_id2])? $deptAry[$v->be_dept_id2] : '';
                    $data[$k]['be_dept_id3_name']   = isset($deptAry[$v->be_dept_id3])? $deptAry[$v->be_dept_id3] : '';
                    $data[$k]['be_dept_id4_name']   = isset($deptAry[$v->be_dept_id4])? $deptAry[$v->be_dept_id4] : '';
                    $data[$k]['be_dept_id5_name']   = isset($deptAry[$v->be_dept_id5])? $deptAry[$v->be_dept_id5] : '';

                    //人員
                    list($user_name,$user_mobile)   = User::getMobileInfo($v->apply_user);
                    $data[$k]['apply_user_name']    = $user_name;
                    $data[$k]['apply_user_tel']     = $user_mobile;
                    list($user_name,$user_mobile)   = User::getMobileInfo($v->charge_user);
                    $data[$k]['charge_user_name']   = $user_name;
                    $data[$k]['charge_user_tel']    = $user_mobile;
                    list($user_name,$user_mobile)   = User::getMobileInfo($v->be_dept_charge2);
                    $data[$k]['be_dept_charge_name']= $user_name;
                    $data[$k]['be_dept_charge_tel'] = $user_mobile;

                    $data[$k]['close_user']         = User::getName($v->close_user);
                    $data[$k]['new_user']           = User::getName($v->new_user);
                    $data[$k]['mod_user']           = User::getName($v->mod_user);

                    //工安&工負
                    $supply_workerAry           = wp_work_worker::getSelect($v->id,1,0,$isApi);
                    $supply_workerName          = '';

                    if($isApi == 1)
                    {
                        foreach ($supply_workerAry as $val)
                        {
                            if(strlen($supply_workerName)) $supply_workerName .= '，';
                            $supply_workerName     .= $val['name'];
                        }
                    } else {
                        $supply_workerName = implode('，',$supply_workerAry);
                    }
                    $data[$k]['supply_worker']      = $supply_workerAry;
                    $data[$k]['supply_worker_name'] = $supply_workerName;

                    $supply_saferAry            = wp_work_worker::getSelect($v->id,2,0,$isApi);
                    $supply_saferName           = '';
                    if($isApi == 1)
                    {
                        foreach ($supply_saferAry as $val)
                        {
                            if(strlen($supply_saferName)) $supply_saferName .= '，';
                            $supply_saferName     .= $val['name'];
                        }
                    } else {
                        $supply_saferName = implode('，',$supply_saferAry);
                    }
                    $data[$k]['supply_safer']       = $supply_saferAry;
                    $data[$k]['supply_safer_name']  = $supply_saferName;

                    //APP端判斷
                    //執行階段: 按鈕判斷
                    $hasWorkerDoorIn = wp_work_worker::hasWorkerDoorIn($v->id);
                    $data[$k]['isStart']    = ($isSupply && $v->aproc == 'W' && $hasWorkerDoorIn)? 'Y' : 'N';   //是否可以啟動
                    $no_start_memo          = ($v->aproc == 'W')? ((!$hasWorkerDoorIn)? 'permit_11001' : 'permit_11002') : 'permit_11009';   //是否可以啟動原因
                    $data[$k]['start_memo'] = Lang::get('sys_workpermit.'.$no_start_memo);   //是否可以收工
                    $data[$k]['isOffWork']  = ($isSupply && $v->aproc == 'R')? 'Y' : 'N';   //是否可以收工
                    $data[$k]['isPause']    = ($isSupply && $v->aproc == 'R')? 'Y' : 'N';   //是否可以暫停
                    $data[$k]['isExtend']   = ($isSupply && $v->aproc == 'R' && $v->wp_permit_shift_id == 1 && !wp_work_rp_extension::isExist($v->id))? 'Y' : 'N';   //是否可以延長
                    $data[$k]['isRollCall'] = ($isSupply && $v->aproc == 'R' && wp_work_check::isExist($v->id,2))? 'Y' : 'N';   //是否可以延長
                    $data[$k]['isPatrol']   = (!$isSupply && $v->aproc == 'R')? 'Y' : 'N';   //是否巡邏
                    //施工進度
                    $processData                    = wp_work_list::getNowProcessStatus($v->id);
//                    dd($processData);
                    $data[$k]['list_aproc']         = isset($processData['list_aproc'])? $processData['list_aproc'] : '';
                    $data[$k]['list_aproc_id']      = isset($processData['list_aproc_id'])? $processData['list_aproc_id'] : '';
                    $data[$k]['now_process']        = isset($processData['now_process'])? $processData['now_process'] : '';
                    $data[$k]['now_process_id']     = isset($processData['now_process_id'])? $processData['now_process_id'] : '';
                    $data[$k]['last_process']       = isset($processData['last_process'])? $processData['last_process'] : '';
                    $data[$k]['process_target1']    = isset($processData['process_target1'])? $processData['process_target1'] : '';
                    $data[$k]['process_charger1']   = isset($processData['process_charger1'])? $processData['process_charger1'] : '';
                    $data[$k]['last_charger']       = isset($processData['last_charger'])? $processData['last_charger'] : '';
                    $data[$k]['process_stime1']     = isset($processData['process_stime1'])? $processData['process_stime1'] : '';
                    $data[$k]['process_etime1']     = isset($processData['process_etime1'])? $processData['process_etime1'] : '';
                    $data[$k]['process_target2']    = isset($processData['process_target2'])? $processData['process_target2'] : '';
                    $data[$k]['process_charger2']   = isset($processData['process_charger2'])? $processData['process_charger2'] : '';
                    $data[$k]['process_stime2']     = isset($processData['process_stime2'])? $processData['process_stime2'] : '';
                    $data[$k]['process_etime2']     = isset($processData['process_etime1'])? $processData['process_etime2'] : '';
                    $data[$k]['last_work_process_id']   = isset($processData['last_work_process_id'])? $processData['last_work_process_id'] : '';
                    $data[$k]['now_work_process_id']    = isset($processData['now_work_process_id'])? $processData['now_work_process_id'] : '';
                    $data[$k]['now_look_process_id']    = isset($processData['now_look_process_id'])? $processData['now_look_process_id'] : '';
                    //2021-04-02
                    //改成用process_id->rule_reject_type 控制是否能停工
                    $data[$k]['isStop']                 = isset($processData['now_process_allow_stop'])? $processData['now_process_allow_stop'] : 'N';
                    //如果是承攬商，則在施工階段，應該要停工是用收工。
                    $data[$k]['isStop']                 = ($v->aproc == 'R' && $isSupply)? 'N' : $data[$k]['isStop'];   //

                    if($isApp == 'Y')
                    {
                        //工作項目
                        $data[$k]['wp_item']    = $this->getApiWorkPermitWorkOrderItemList($v->id,1);
                        //危害告知
                        $data[$k]['wp_danger']  = $this->getApiWorkPermitWorkOrderDangerList($v->id,1);
                        //附加檢點表
                        $data[$k]['wp_check']   = $this->getApiWorkPermitWorkOrderCheckList($v->id,1);
                        //附加檢點表
                        $data[$k]['wp_line']    = $this->getApiWorkPermitWorkOrderLineList($v->id,1);
                        //施工人員
                        $checkDoorInout = ($v->sdate == $today)? 1 : 0; //是否要檢查門近結果（今日）
                        $isWorkerActive = (in_array($v->aproc,['F']))? 1 : 0;
                        $data[$k]['wp_worker']  = $this->getApiWorkPermitWorker($v->id,0,[],$checkDoorInout,$isWorkerActive);
                        //允許可簽核的階段＆題目
                        $data[$k]['wp_process']  = $this->getApiWorkPermitProcessTarget($v->id,$user,'Y');
                    }
                }
            }

            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工作許可證-執行單
     *
     * @return array
     */
    public function getApiWorkPermitOffLineList($search = [0,0,0,0,0], $apiKey = '')
    {
        $ret = array();
        $targetAry  = [1,2,3,4,5,6,7,8,9];
        //承攬商，工程案件，廠區，施工日期，轄區部門，監造部門，工程案件<工地負責，安衛人員>，本工作許可證監造負責人
        list($b_supply_id,$be_dept_id,$store,$b_cust_id,$bc_type) = $search;
        $storeAry       = b_factory::getSelect(0);
        $aprocAry       = SHCSLib::getCode('PERMIT_APROC');
        //取第一層
        $data = wp_work::where('wp_work.isClose','N');
        $data = $data->whereIn('wp_work.aproc',['K','P','R']); //進度,'O'
        $data = $data->where('wp_work.sdate',date('Y-m-d'));  //日期
        if($b_supply_id)
        {
            $data = $data->where('wp_work.b_supply_id',$b_supply_id);
        }
        if($be_dept_id)
        {
            $data = $data->where(function ($query) use ($be_dept_id) {
                $query->where('wp_work.be_dept_id1', '=', $be_dept_id)
                    ->orWhere('wp_work.be_dept_id2', '=', $be_dept_id)
                    ->orWhere('wp_work.be_dept_id3', '=', $be_dept_id)
                    ->orWhere('wp_work.be_dept_id4', '=', $be_dept_id)
                    ->orWhere('wp_work.be_dept_id5', '=', $be_dept_id);
            });
        }

        if($store)
        {
            if(is_array($store))
            {
                $data = $data->whereIn('wp_work.b_factory_id',$store);
            } else {
                $data = $data->where('wp_work.b_factory_id',$store);
            }
        }

        $data = $data->orderby('wp_work.permit_no')->get();
        //dd([$aproc,$listAproc,$b_supply_id,$e_project_id,$store,$sdate,$isNow,$data]);
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $ProjectName = e_project::getName($v->e_project_id);
                $SupplyName  = b_supply::getSubName($v->b_supply_id);
                $localName   = b_factory_a::getName($v->b_factory_a_id);
                $workAry     = [$v->supply_worker,$v->supply_safer];
                $tmp = [];
                $tmp['work_id']         = $v->id;
                $tmp['permit_no']       = $v->permit_no;
                $tmp['project']         = $SupplyName.'-'.$localName;
                $tmp['project_name']    = $ProjectName;
                $tmp['supply']          = $SupplyName;
                $tmp['local']           = $localName;
                $tmp['danger']          = $v->wp_permit_danger;
                $tmp['workitem_memo']      = $v->wp_permit_workitem_memo;
                $tmp['store_memo']         = $v->b_factory_memo;
                $tmp['aproc_name']         = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';

                //工安&工負
                $supply_workerAry          = wp_work_worker::getSelect($v->id,1,0,2);
                $supply_workerName         = implode('，',$supply_workerAry);
                $tmp['supply_worker']      = $supply_workerAry;
                $tmp['supply_worker_name'] = $supply_workerName;

                $supply_saferAry           = wp_work_worker::getSelect($v->id,2,0,2);
                $supply_saferName          = implode('，',$supply_saferAry);
                $tmp['supply_safer']       = $supply_saferAry;
                $tmp['supply_safer_name']  = $supply_saferName;

                list($name,$mobile)        = User::getMobileInfo($v->apply_user);
                $tmp['apply_user_name']    = $name;
                $tmp['apply_user_tel']     = $mobile;
                list($name,$mobile)        = User::getMobileInfo($v->charge_user);
                $tmp['charge_user_name']   = $name;
                $tmp['charge_user_tel']    = $mobile;
                list($name,$mobile)        = User::getMobileInfo($v->be_dept_charge2);
                $tmp['be_dept_charge_name']= $name;
                $tmp['be_dept_charge_tel'] = $mobile;
                $tmp['close_user']         = User::getName($v->close_user);
                $tmp['new_user']           = User::getName($v->new_user);
                $tmp['mod_user']           = User::getName($v->mod_user);
                $tmp['stime1']             = !is_null($v->stime1)? $v->stime1 : '';
                $tmp['etime1']             = !is_null($v->etime1)? $v->etime1 : '';
                $tmp['stime2']             = !is_null($v->stime2)? $v->stime2 : '';
                $tmp['etime2']             = !is_null($v->etime2)? $v->etime2 : '';
                //施工進度
                $processData                    = wp_work_list::getNowProcessStatus($v->id);
//                    dd($processData);
                $tmp['list_aproc']         = isset($processData['list_aproc'])? $processData['list_aproc'] : '';
                $tmp['list_aproc_id']      = isset($processData['list_aproc_id'])? $processData['list_aproc_id'] : '';
                $tmp['now_process']        = isset($processData['now_process'])? $processData['now_process'] : '';
                $tmp['now_process_id']     = isset($processData['now_process_id'])? $processData['now_process_id'] : '';
                $tmp['last_process']       = isset($processData['last_process'])? $processData['last_process'] : '';
                $tmp['process_target1']    = isset($processData['process_target1'])? $processData['process_target1'] : '';
                $tmp['process_charger1']   = isset($processData['process_charger1'])? $processData['process_charger1'] : '';
                $tmp['last_charger']       = isset($processData['last_charger'])? $processData['last_charger'] : '';
                $tmp['process_stime1']     = isset($processData['process_stime1'])? $processData['process_stime1'] : '';
                $tmp['process_etime1']     = isset($processData['process_etime1'])? $processData['process_etime1'] : '';
                $tmp['process_target2']    = isset($processData['process_target2'])? $processData['process_target2'] : '';
                $tmp['process_charger2']   = isset($processData['process_charger2'])? $processData['process_charger2'] : '';
                $tmp['process_stime2']     = isset($processData['process_stime2'])? $processData['process_stime2'] : '';
                $tmp['process_etime2']     = isset($processData['process_etime1'])? $processData['process_etime2'] : '';
                $tmp['last_work_process_id']   = isset($processData['last_work_process_id'])? $processData['last_work_process_id'] : '';
                $tmp['now_work_process_id']    = isset($processData['now_work_process_id'])? $processData['now_work_process_id'] : '';
                $tmp['now_look_process_id']    = isset($processData['now_look_process_id'])? $processData['now_look_process_id'] : '';

                //自己代表的權限
                $myTarget       = SHCSLib::genPermitSelfTarget($b_cust_id,$bc_type,$v->supply_worker,$v->supply_safer,$v->be_dept_id1,$v->be_dept_id2,$v->be_dept_id3,$v->be_dept_id4,$v->be_dept_id5);
                //
                $tmp['process'] = $this->getApiWorkPermitProcessAll([$v->wp_permit_id,$b_cust_id,$bc_type,$be_dept_id,$workAry,$v->supply_worker,$v->supply_safer,$v->be_dept_id1,$v->be_dept_id2,$v->be_dept_id3,$v->be_dept_id4,$v->be_dept_id5],$v->id,$v->wp_permit_danger,$v->be_dept_id4,$myTarget,$apiKey);
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

    /**
     * 當日工作許可證　資料
     * @param $id
     * @param int $myDept
     * @return object
     */
    public function getWorkPermitWorkOrder($id,$isApi = 2)
    {
        $aprocAry       = SHCSLib::getCode('PERMIT_APROC');
        $shifAry        = wp_permit_shift::getSelect(); //班別
        $today          = date('Y-m-d');

        $data = wp_work::where('wp_work.id',$id)->where('wp_work.isClose','N');
        $data = $data->join('e_project as p','p.id','=','wp_work.e_project_id');
        $data = $data->join('b_supply as s','s.id','=','wp_work.b_supply_id');
        $data = $data->join('wp_work_list as l','l.wp_work_id','=','wp_work.id');
        $data = $data->join('b_factory as f','f.id','=','wp_work.b_factory_id');
        $data = $data->join('b_factory_a as fa','fa.id','=','wp_work.b_factory_a_id');
        $data = $data->join('b_factory_b as fb','fb.id','=','wp_work.b_factory_b_id');
        $data = $data->join('b_factory_d as fd','fd.id','=','wp_work.b_factory_d_id');
        $data = $data->select('wp_work.*','l.id as list_id','l.aproc as list_aproc_val','l.wp_work_process_id',
        'p.name as project','p.project_no','s.name as supply','f.name as store','fa.name as local',
        'fb.name as device','fd.name as door');
        $data = $data->first();

        if(isset($data->id))
        {
            $list_id    = wp_work_list::isExist($data->id);

            $data['list_id']   = $list_id;
            $data['isStart']   = 'N';
            $data['isOffWork'] = 'N';
            $data['isStop']    = 'N';
            $data['isCheck']   = 'N';

            //如果正在「施工階段」
            if(wp_work_list::isOnWorkProcess($list_id))
            {
                $data['isCheck'] = 'Y'; //是否可以巡邏
                $data['isStop']  = 'Y'; //是否可以終止
            }

            $data['shift_name']         = isset($shifAry[$data->wp_permit_shift_id])? $shifAry[$data->wp_permit_shift_id] : '';

            $data['be_dept_id1_name']   = be_dept::getName($data->be_dept_id1);
            $data['be_dept_id2_name']   = be_dept::getName($data->be_dept_id2);
            $data['be_dept_id3_name']   = be_dept::getName($data->be_dept_id3);
            $data['be_dept_id4_name']   = be_dept::getName($data->be_dept_id4);

            $data['sub_title']          = '';
            $data['kind']               = $data->wp_permit_danger;
            $data['workitem_memo']      = $data->wp_permit_workitem_memo;
            $data['store_memo']         = $data->b_factory_memo;
            $data['aproc_name']         = isset($aprocAry[$data->aproc])? $aprocAry[$data->aproc] : '';

            //工安&工負
            $supply_workerAry           = wp_work_worker::getSelect($data->id,1,0,$isApi);
            $supply_workerName          = '';
            if($isApi == 1)
            {
                foreach ($supply_workerAry as $val)
                {
                    if(strlen($supply_workerName)) $supply_workerName .= '，';
                    $supply_workerName     .= $val['name'];
                }
            } else {
                $supply_workerName = implode('，',$supply_workerAry);
            }

            $data['supply_worker']      = $supply_workerAry;
            $data['supply_worker_name'] = $supply_workerName;

            $supply_saferAry            = wp_work_worker::getSelect($data->id,2,0,$isApi);
            $supply_saferName           = '';

            if($isApi == 1)
            {
                foreach ($supply_saferAry as $val)
                {
                    if(strlen($supply_saferName)) $supply_saferName .= '，';
                    $supply_saferName     .= $val['name'];
                }
            } else {
                $supply_saferName = implode('，',$supply_saferAry);
            }
            $data['supply_safer']       = $supply_saferAry;
            $data['supply_safer_name']  = $supply_saferName;
            list($name,$mobile)         = User::getMobileInfo($data->apply_user);
            $data['apply_user_name']    = $name;
            $data['apply_user_tel']     = $mobile;
            list($name,$mobile)        = User::getMobileInfo($data->charge_user);
            $data['charge_user_name']   = $name;
            $data['charge_user_tel']    = $mobile;
            list($name,$mobile)        = User::getMobileInfo($data->be_dept_charge2);
            $data['be_dept_charge_name']= $name;
            $data['be_dept_charge_tel'] = $mobile;
            $data['close_user']         = User::getName($data->close_user);
            $data['new_user']           = User::getName($data->new_user);
            $data['mod_user']           = User::getName($data->mod_user);
            $data['stime1']             = !is_null($data->stime1)? $data->stime1 : '';
            $data['etime1']             = !is_null($data->etime1)? $data->etime1 : '';
            $data['stime2']             = !is_null($data->stime2)? $data->stime2 : '';
            $data['etime2']             = !is_null($data->etime2)? $data->etime2 : '';

            //施工進度
            $processData                    = wp_work_list::getNowProcessStatus($data->id);
//                    dd($processData);
            $data['list_aproc']         = isset($processData['list_aproc'])? $processData['list_aproc'] : '';
            $data['now_process']        = isset($processData['now_process'])? $processData['now_process'] : '';
            $data['last_process']       = isset($processData['last_process'])? $processData['last_process'] : '';
            $data['process_target1']    = isset($processData['process_target1'])? $processData['process_target1'] : '';
            $data['process_charger1']   = isset($processData['process_charger1'])? $processData['process_charger1'] : '';
            $data['process_stime1']     = isset($processData['process_stime1'])? $processData['process_stime1'] : '';
            $data['process_etime1']     = isset($processData['process_etime1'])? $processData['process_etime1'] : '';
            $data['process_target2']    = isset($processData['process_target2'])? $processData['process_target2'] : '';
            $data['process_charger2']   = isset($processData['process_charger2'])? $processData['process_charger2'] : '';
            $data['process_stime2']     = isset($processData['process_stime2'])? $processData['process_stime2'] : '';
            $data['process_etime2']     = isset($processData['process_etime1'])? $processData['process_etime2'] : '';
            $data['last_work_process_id']   = isset($processData['last_work_process_id'])? $processData['last_work_process_id'] : '';
            $data['now_work_process_id']    = isset($processData['now_work_process_id'])? $processData['now_work_process_id'] : '';
            $data['now_look_process_id']    = isset($processData['now_look_process_id'])? $processData['now_look_process_id'] : '';

            if($isApi == 1)
            {
                //工作項目
                $data['wp_item']    = $this->getApiWorkPermitWorkOrderItemList($data->id,1);
                //危害告知
                $data['wp_danger']  = $this->getApiWorkPermitWorkOrderDangerList($data->id,1);
                //附加檢點表
                $data['wp_check']   = $this->getApiWorkPermitWorkOrderCheckList($data->id,1);
                //附加檢點表
                $data['wp_line']    = $this->getApiWorkPermitWorkOrderLineList($data->id,1);
                //施工人員
                $checkDoorInout = ($data->sdate == $today)? 1 : 0; //是否要檢查門近結果（今日）
                $isWorkerActive = (in_array($data->aproc,['F']))? 1 : 0;
                $data['wp_worker']  = $this->getApiWorkPermitWorker($data->id,0,[],$checkDoorInout,$isWorkerActive);

            }
            $data = (object)$data;
        }
        return $data;
    }


    /**
     * 取得 該工作許可證 需要列印的附加檢點表
     * @param $id
     */
    public function getWorkPermitWorkOrderCheckFile($work_id)
    {
        $ret = [];
        if(!$work_id) return $ret;

        $data = wp_work_check::join('wp_check_kind as k','k.id','=','wp_work_check.wp_check_kind_id')->
                join('wp_check_kind_f as f','k.id','=','f.wp_check_kind_id')->
                where('wp_work_check.wp_work_id',$work_id)->where('wp_work_check.isClose','N')->
                where('f.isClose','N')->select('f.id','k.name as kind','f.name','f.path')->
        orderby('k.id')->orderby('f.show_order');
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $tmp = [];
                $tmp['kind'] = $val->kind;
                $tmp['name'] = $val->name;
                $tmp['path'] = $val->path ? SHCSLib::url('file/','A'.$val->id,'sid=PermitCheckFile&show=pdf') : '';

                $ret[] = $tmp;
            }
        }
        return $ret;
    }

    /**
     * 取得 當日工作許可證 需要氣體偵測提醒
     */
    public function getWorkPermitWorkOrderCheckRegular()
    {
        $ret   = [];
        $today = date('Y-m-d');
        $data  = wp_work::where('sdate',$today)->whereIn('aproc',['P','R'])->where('isClose','N');
        $needRegularAmt = $data->count();
        if($needRegularAmt)
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $tmp = [];
                $tmp['id'] = $val->id;
                $tmp['supply_worker'] = $val->supply_worker;
                $tmp['supply_safer'] = $val->supply_safer;
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

    /**
     * 取得 當日工作許可證 已經收工完成，但是尚未離場
     */
    public function getWorkPermitWorkOrderCheckRegular2()
    {
        $ret   = [];
        $today = date('Y-m-d');
        $data  = wp_work::where('sdate',$today)->whereIn('aproc',['C','F'])->where('isClose','N');
        $needRegularAmt = $data->count();
        if($needRegularAmt)
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                //2020-02-03 離場一個小時才通知
                $nowTime = time();
                $endTime = is_numeric($val->etime1)? strtotime($val->etime1) : $nowTime;
                if(($nowTime - $endTime) >= 3600)
                {
                    //如果場內依舊有人
                    $inMenAry = rept_doorinout_t::getWorkInMen($val->id);
                    if(count($inMenAry))
                    {
                        $tmp = [];
                        $tmp['id']  = $val->id;
                        $tmp['men'] = implode('，',$inMenAry);
                        $tmp['amt'] = count($inMenAry);

                        $ret[] = $tmp;
                    }
                }
            }
        }

        return $ret;
    }
}
