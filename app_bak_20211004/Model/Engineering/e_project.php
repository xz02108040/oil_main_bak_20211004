<?php

namespace App\Model\Engineering;

use App\Http\Traits\Engineering\EngineeringCarTrait;
use App\Http\Traits\Engineering\EngineeringDeptTrait;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Lib\SHCSLib;
use App\Model\App\app_menu_a;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member_ei;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_door_supply_whitelist_pass;
use App\Model\View\view_project_factory;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_project extends Model
{
    use EngineeringDeptTrait,EngineeringFactoryTrait,FactoryDeviceTrait,EngineeringCarTrait;
    /**
     * 使用者Table:
     */
    protected $table = 'e_project';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    protected $guarded = ['id'];

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isExist($id , $sid = 0, $isActive = 'Y')
    {
        if(!$id) return 0;
        $data  = e_project::where('id',$id)->where('isClose','N');
        if($sid)
        {
            $data = $data->where('b_supply_id',$sid);
        }
        if($isActive == 'Y')
        {
            $data = $data->whereIn('aproc',['B','P','R'])->where('edate','>=',date('Y-m-d') );
        }
        return $data->count();
    }
    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isNoExist($project_no , $exitid = 0)
    {
        if(!$project_no) return 0;
        $data  = e_project::where('project_no',$project_no)->where('isClose','N');
        if($exitid)
        {
            $data = $data->where('id','!=',$exitid);
        }
        return $data->count();
    }

    /**
     *  利用工程編號尋找ＩＤ
     * @param $id
     * @return int
     */
    protected function getId($no)
    {
        if(!$no) return [0,0];
        $data  = e_project::where('project_no',$no)->first();
        return isset($data->id)? [$data->id,$data->b_supply_id] : [0,0];
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getName($id,$type = 1)
    {
        $ret = '';
        if(!$id) return $ret;
        $data  = e_project::where('id',$id)->select('name','project_no')->first();
        if($type == 2) $ret .= isset($data->project_no)? $data->project_no.' ' : '';
        $ret .= isset($data->name)? $data->name : '';
        return $ret;
    }

    /**
     *  案號
     * @param $id
     * @return int
     */
    protected function getNo($id)
    {
        if(!$id) return '';
        $data  = e_project::where('id',$id)->select('project_no')->first();
        return isset($data->project_no)? $data->project_no : '';
    }
    /**
     *  案號
     * @param $id
     * @return int
     */
    protected function getNameList($id)
    {
        if(!$id) return ['',''];
        $data  = e_project::where('id',$id)->select('name','project_no')->first();
        return isset($data->project_no)? [$data->name,$data->project_no] : ['',''];
    }

    /**
     *  到期日
     * @param $id
     * @return int
     */
    protected function getStatus($id)
    {
        $ret = '';
        if(!$id) return $ret;
        $data  = e_project::where('id',$id)->select('aproc','edate')->first();
        if(isset($data->edate))
        {
            $aprocAry   = SHCSLib::getCode('ENGINEERING_APROC');
            $aproc_name = isset($aprocAry[$data->aproc])? $aprocAry[$data->aproc] : '';
            $ret = \Lang::get('sys_engineering.engineering_165',['name'=>$aproc_name,'edate'=>$data->edate]);
        }
        return $ret;
    }
    /**
     *  監造部門
     * @param $id
     * @return int
     */
    protected function getChargeDept($id)
    {
        if(!$id) return 0;
        $data  = e_project::where('id',$id)->select('charge_dept')->first();
        return isset($data->charge_dept)? $data->charge_dept : 0;
    }
    /**
     *  監造
     * @param $id
     * @return int
     */
    protected function getChargeUser($id)
    {
        if(!$id) return [0,0];
        $data  = e_project::where('id',$id)->select('charge_user','charge_user2')->first();
        return isset($data->charge_user)? [$data->charge_user,$data->charge_user2] : [0,0];
    }
    /**
     *  查詢工程案件之狀態
     * @param $id
     * @return int
     */
    protected function getProjectList1($id)
    {
        if(!$id) return ['',''];
        $data  = e_project::where('id',$id)->select('aproc','edate')->first();
        return isset($data->aproc)? [$data->aproc,$data->edate] : ['',''];
    }

    /**
     *  監造部門
     * @param $id
     * @return int
     */
    protected function getChargeDeptUser($id)
    {
        $ret = [];
        if(!$id) return '';
        $data  = e_project::where('id',$id)
            ->select('charge_dept','charge_dept2','charge_user','charge_user2')->first();

        if(isset($data->charge_dept))
        {
            if($data->charge_dept)
            {
                $tmp = [];
                $tmp['dept_id'] = $data->charge_dept;
                $tmp['dept']    = be_dept::getName($data->charge_dept);
                list($user,$tel) = User::getMobileInfo($data->charge_user);
                $tmp['id']      = $data->charge_user;
                $tmp['name']    = $user;
                $tmp['tel']     = $tel;
                $ret[] = $tmp;
            }
            if($data->charge_dept2)
            {
                $tmp = [];
                $tmp['dept_id'] = $data->charge_dept2;
                $tmp['dept']    = be_dept::getName($data->charge_dept2);
                list($user,$tel)= User::getMobileInfo($data->charge_user2);
                $tmp['id']      = $data->charge_user2;
                $tmp['name']    = $user;
                $tmp['tel']     = $tel;
                $ret[] = $tmp;
            }
        }
        return $ret;
    }

    protected function getEmpProject($store,$dept = 0,$isFirst = 1, $isApi = 0)
    {
        if(!$store) return [];
        if(!is_array($store)) $store = [$store];
        $this->identity_A       = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $this->identity_B       = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
        $ret  = [];
        $data = e_project::join('e_project_f as f','f.e_project_id','=','e_project.id')->
                where('e_project.isClose','N')->where('e_project.edate','>=',date('Y-m-d'))->
                where('f.isClose','N');

        if($store && !$dept)
        {
            $data = $data->whereIn('f.b_factory_id',$store);
        }
        if(!$store && $dept)
        {
            $data = $data->where('e_project.charge_dept',$dept);
        }
        if($store && $dept)
        {
            $data = $data->whereIn('f.b_factory_id',$store);
            $data = $data->where('e_project.charge_dept',$dept);
        }

        $data = $data->select('e_project.id','e_project.project_no','e_project.name','e_project.b_supply_id')->groupby('e_project.id')->
                groupby('e_project.b_supply_id')->groupby('e_project.name')->groupby('e_project.project_no')->get();
        if($isFirst) {
            if($isApi)
            {
                $ret[0] = ['id'=>0,'name'=>Lang::get('sys_base.base_10015')];
            } else {
                $ret[0] = Lang::get('sys_base.base_10015');
            }
        }
        if(count($data))
        {
            foreach ($data as $val)
            {
                if($isApi)
                {
                    $tmp = [];
                    $tmp['id']      = $val->id;
                    $tmp['name']    = $val->project_no.' '.$val->name;
                    $ret[] = $tmp;
                } else {
                    $ret[$val->id] = $val->project_no.' '.$val->name;
                }

            }
        }
        return $ret;
    }

    /**
     * 承攬商
     * @param $id
     * @return int
     */
    protected function getSupply($id)
    {
        if(!$id) return 0;
        $data  = e_project::find($id);
        return isset($data->b_supply_id)? $data->b_supply_id : 0;
    }

    //取得 門禁規則 與 工地負責人＆安衛人員
    protected  function getDoorRule($id)
    {
        if(!$id) return [0,[],[],[]];
        $data = e_project::find($id);
        return isset($data->id)? [$data->door_check_rule,e_project_s::getJobUser($id,'A'),e_project_s::getJobUser($id,'B'),e_project_s::getJobUser($id,'E')] : [0,[],[],[]];
    }

    //取得 門禁規則
    protected  function getDoorCheckRule($id)
    {
        if(!$id) return 0;
        $data = e_project::find($id);
        return isset($data->id)? $data->door_check_rule : 0;
    }

    //取得 負責的工程案件
    protected  function getChargeAry($b_cust_id,$deptAry = [0])
    {
        $ret = [0];
        if(!$b_cust_id) return $ret;
        //1.取得代理人
        $AttorneyAry = b_cust_e::getAttorneyAry($b_cust_id,1);
        //2. 負責的工程案件
        $data = e_project::where(function ($query) use ($AttorneyAry,$deptAry) {
            $query->whereIn('charge_user', $AttorneyAry)
                ->orWhereIn('charge_user2', $AttorneyAry)
                ->orWhereIn('charge_dept', $deptAry);
        })->whereIn('aproc',['B','R','P'])->where('edate','>=',date('Y-m-d'))->
        where('isClose','N')->select('id');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $ret[] = $val->id;
            }
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($aproc = '' , $supply_id = 0, $isFirst = 1)
    {
        $ret  = [];
        $data = e_project::select('id','name','project_no')->where('isClose','N');
        if($supply_id)
        {
            $data = $data->where('b_supply_id',$supply_id);
        }
        if($aproc)
        {
            if($aproc == 'P')
            {
                $data = $data->whereIn('aproc',['B','P','R']);
                $data = $data->where('edate','>=',date('Y-m-d'));
            } else {
                $data = $data->where('aproc',$aproc);
            }
        }
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->project_no.' '.$val->name;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getApiSelect($supply = 0,$chargeDept = 0)
    {
        $ret  = [];
        $today = date('Y-m-d');
        $data = e_project::select('id','name','project_no')->where('isClose','N')->
            whereIn('aproc',['B','R','P'])->where('edate','>=',$today);
        if($chargeDept)
        {
            $data = $data->where('charge_dept', $chargeDept);
        }
        if($supply)
        {
            $data = $data->where('b_supply_id',$supply);
        }
        $data = $data->get();

        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id']      = $val->id;
            $tmp['no']      = $val->project_no;
            $tmp['name']    = $val->project_no.$val->name;
            $tmp['dept']    = '';
            $tmp['worker']  = '';
            $tmp['safer']   = '';
            $tmp['identity']  = '';

            if($supply)
            {
                //車輛
                $tmp['car']         = $this->getApiEngineeringCar($val->id);
                //監造部門
                $tmp['dept']        = $this->getApiEngineeringDept($val->id,'Y');
                //工負
                $tmp['worker']      = view_door_supply_whitelist_pass::getProjectMemberWhitelistSelect($val->id,['A'],0,1,1);;
                //工安
                $tmp['safer']       = view_door_supply_whitelist_pass::getProjectMemberWhitelistSelect($val->id,['B'],0,1,1);;
                //施工人員
                $tmp['identity']    = view_door_supply_whitelist_pass::getProjectMemberIdentitySelect($val->id,[],[1,2]);

            }
            $ret[]          = $tmp;
        }

        return $ret;
    }


    //取得 下拉選擇全部
    protected  function getActiveProjectSelect($show_type = 1, $supply_id = 0, $ext_project_id = 0, $isFirst = 1)
    {
        $ret  = [];
        $data = e_project::select('id','name','project_no')->where('isClose','N');
        $data = $data->whereIn('aproc',['B','R','P'])->where('edate','>=',date('Y-m-d'));
        if($supply_id)
        {
            $data = $data->where('b_supply_id',$supply_id);
        }
        if($ext_project_id)
        {
            $data = $data->where('id','!=',$ext_project_id);
        }
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = ($show_type == 2) ? $val->project_no : $val->project_no.' '.$val->name;
        }

        return $ret;
    }

}
