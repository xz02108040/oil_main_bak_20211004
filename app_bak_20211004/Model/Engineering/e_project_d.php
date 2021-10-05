<?php

namespace App\Model\Engineering;

use App\Lib\HtmlLib;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_project_d extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_project_d';
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
    protected function isExist($project_id,$be_dept_id = 0,$b_cust_id = 0,$extid = 0)
    {
        if(!$project_id) return 0;
        $data  = e_project_d::where('e_project_id',$project_id)->where('isClose','N');
        if($be_dept_id)
        {
            $data = $data->where('be_dept_id',$be_dept_id);
        }
        if($b_cust_id)
        {
            $data = $data->where('b_cust_id',$b_cust_id);
        }
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }
    /**
     *  部門名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return '';
        $data  = e_project_d::join('be_dept as f','f.id','=','e_project_d.be_dept_id')->
                where('e_project_d.id',$id)->where('e_project_d.isClose','N')->select('f.name')->first();
        return isset($data->name)? $data->name : '';
    }

    /**
     *  取得工程案件之所有負責部門名稱
     * @param $project_id
     * @return int
     */
    protected function getNameAry($project_id)
    {
        if(!$project_id) return '';
        $storeAry = [];

        $data  = e_project_d::
                join('be_dept as f','f.id','=','e_project_d.be_dept_id')->
                where('e_project_d.e_project_id',$project_id)->where('e_project_d.isClose','N')->select('f.name')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $storeAry[] = $val->name;
            }
        }

        return implode('，',$storeAry);
    }
    /**
     *  取得工程案件之負責廠區名稱
     * @param $id
     * @return int
     */
    protected function genBtn($project_id)
    {
        if(!$project_id) return '';
        $storeAry = [];

        $data  = e_project_d::
        join('be_dept as f','f.id','=','e_project_d.be_dept_id')->
        join('view_user as u','u.b_cust_id','=','e_project_d.b_cust_id')->
        where('e_project_d.e_project_id',$project_id)->where('e_project_d.isClose','N')->
        select('f.name as dept','u.name')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $storeAry[] = HtmlLib::btn('#',$val->name.'('.$val->dept.')',8);
            }
        }

        return implode(' ',$storeAry);
    }

    //取得 下拉選擇全部
    protected  function getDeptList($id,$color = 1)
    {
        $retAry  = [];
        if(!$id) return '';
        $color = $color ? $color : 6;

        $data = e_project_d::where('e_project_id',$id)->select('be_dept_id')->where('isClose','N');

        $data = $data->get();

        foreach ($data as $key => $val)
        {
            $retAry[$val->b_cust_id] = HtmlLib::btn('#',be_dept::getName($val->be_dept_id),$color);
        }

        return implode('，',$retAry);
    }

    //取得 下拉選擇全部
    protected  function getSelect($project_id,$dept_id = 0, $isAll = 1,$isFirst = 1,$isApp = 0)
    {
        $ret  = [];
        if($isFirst)
        {
            if($isApp)
            {
                $ret[] = ['id'=>0,'name'=>Lang::get('sys_base.base_10015')];
            } else {
                $ret[0] = Lang::get('sys_base.base_10015');
            }
        }
        if($isAll)
        {
            $chargeUserAry       = e_project::getChargeUser($project_id);

            foreach ($chargeUserAry as $uid)
            {
                if($isApp)
                {
                    $ret[] = ['id'=>$uid,'name'=>User::getName($uid)];
                } else {
                    $ret[$uid] = User::getName($uid);
                }
            }
        }

        $data = e_project_d::
            //join('be_dept as e','e.id','=','e_project_d.be_dept_id')->
            join('view_user as u','u.b_cust_id','=','e_project_d.b_cust_id')->
            where('e_project_id',$project_id)->select('u.b_cust_id as id','u.name')->where('e_project_d.isClose','N');
        if($dept_id)
        {
            $data = $data->where('e_project_d.be_dept_id',$dept_id);
        }
        $data = $data->get();


        foreach ($data as $key => $val)
        {
            if($isApp)
            {
                $ret[] = ['id'=>$val->id,'name'=>$val->name];
            } else {
                $ret[$val->id] = ($val->name);
            }

        }

        return $ret;
    }



}
