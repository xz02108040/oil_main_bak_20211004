<?php

namespace App\Model\Factory;

use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Model\Emp\be_dept;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_factory_e extends Model
{
    use FactoryDeviceTrait;
    /**
     * 使用者Table:
     */
    protected $table = 'b_factory_e';
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
    protected function isExist($factory_a_id, $dept_id, $extid = 0)
    {
        if(!$factory_a_id || !$dept_id) return 0;
        $data  = b_factory_e::where('b_factory_a_id',$factory_a_id)->where('be_dept_id',$dept_id)->where('isClose','N');
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    //取得 下拉選擇全部
    protected  function getStoreAry($dept_id = 0)
    {
        $ret = [];
        if(!$dept_id) return $ret;
        if($dept_id == 1)
        {
            $data = b_factory_e::where('isClose','N')->select('b_factory_id')->groupBy('b_factory_id');
        } else {
            $data = b_factory_e::where('be_dept_id',$dept_id)->where('isClose','N')->select('b_factory_id');
        }
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $ret[] = $val->b_factory_id;
            }
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($sid = 0,$aid = 0,$isFirst = 1)
    {
        $ret  = [];
        $data = b_factory_e::join('be_dept as e','e.id','=','b_factory_e.be_dept_id')->
                select('b_factory_e.id','b_factory_e.be_dept_id','e.name as name')->where('b_factory_e.isClose','N');
        if($sid)
        {
            $data = $data->where('b_factory_e.b_factory_id',$sid);
        }
        if($aid)
        {
            $data = $data->where('b_factory_e.b_factory_a_id',$aid);
        }
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->be_dept_id] = $val->name;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getDeptSelect($dept_id, $isFirst = 1)
    {
        $ret  = [];
        $data = b_factory_e::
                join('b_factory_a as a','a.id','=','b_factory_e.b_factory_a_id')->
                select('a.id','a.name')->where('b_factory_e.isClose','N');
        if($dept_id)
        {
            $data = $data->where('b_factory_e.be_dept_id',$dept_id);
        }
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getApiSelect($b_factory_a_id = 0,$isFirst = 1)
    {
        $ret  = [];
        $data = b_factory_e::join('be_dept as e','e.id','=','b_factory_e.be_dept_id')->
        select('e.id','b_factory_e.b_factory_a_id','e.name')->where('b_factory_e.isClose','N');
        if($b_factory_a_id)
        {
            $data = $data->where('b_factory_e.b_factory_a_id',$b_factory_a_id);
        }

        $data = $data->get();

        if($isFirst) $ret[0] = ['id'=>0,'name'=>Lang::get('sys_base.base_10015'),'b_factory_a_id'=>0];
        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id']              = $val->id;
            $tmp['name']            = $val->name;
            $tmp['b_factory_a_id']  = $val->b_factory_a_id;
            $ret[] = $tmp;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getLocalApiSelect($dept_id = 0,$isFirst = 1,$isDetial = 'N')
    {
        $ret  = [];
        $data   = b_factory_e::join('b_factory_a as fa','fa.id','=','b_factory_e.b_factory_a_id')->
        where('fa.isClose','N')->where('b_factory_e.isClose','N');
        $data = $data->where('b_factory_e.be_dept_id',$dept_id);

        $data = $data->select('fa.id','fa.name','b_factory_e.b_factory_id')->get();

        if($isFirst) $ret[0] = ['id'=>0,'name'=>Lang::get('sys_base.base_10015'),'b_factory_a_id'=>0];
        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id']              = $val->id;
            $tmp['name']            = $val->name;

            //施工地點
            if($isDetial == 'Y')
            {
                $tmp['works_area'] = $this->getApiFactoryDeviceReply($val->id);
                $tmp['door']       = b_factory_d::getApiSelect($val->b_factory_id,0);
            }
            $ret[] = $tmp;
        }

        return $ret;
    }
}
