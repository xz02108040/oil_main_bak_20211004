<?php

namespace App\Model\Engineering;

use App\Lib\HtmlLib;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_param;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_project_c extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_project_c';
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
    protected function isExist($project_id,$course_id,$extid = 0)
    {
        if(!$project_id || !$course_id) return 0;
        $data  = e_project_c::where('e_project_c.e_project_id',$project_id)->
                where('e_project_c.et_course_id',$course_id)->where('e_project_c.isClose','N');
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    //取得 下拉選擇全部
    protected  function getAllName($project_id)
    {
        $ret  = [];
        $retStr = '';
        $data = e_project_c::where('e_project_id',$project_id)->
        select('id','et_course_id')->where('isClose','N');

        $data = $data->orderby('et_course_id')->get();

        foreach ($data as $key => $val)
        {
            $ret[$val->et_course_id] = $val->et_course_id;
        }
        if(count($ret))
        {
            foreach ($ret as $key => $val)
            {
                if($retStr) $retStr .= '<br/>';
                $name    = et_course::getName($key);
                $retStr .= $name;
            }
        }

        return $retStr;
    }

    //取得 下拉選擇全部
    protected  function getSelect($project_id,$isDoorRule = 0)
    {
        $ret  = [];
        $data = e_project_c::where('e_project_c.e_project_id',$project_id)->where('e_project_c.isClose','N');
        if($isDoorRule)
        {
            $data = $data->join('et_course as c','c.id','=','e_project_c.et_course_id')->where('c.isDoorRule','Y')->
                    select('e_project_c.*');
        }

        $data = $data->get();

        if(count($data))
        {
            foreach ($data as $key => $val)
            {
                $ret[$val->et_course_id] = $val->et_course_id;
            }
        }

        return $ret;
    }



}
