<?php

namespace App\Model\View;

use App\Model\Emp\be_dept;
use App\Model\Factory\b_car;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Supply\b_supply;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class view_project_factory extends Model
{
    /**
     * 使用者Table: 列出工程案件之可進出的廠區
     */
    protected $table = 'view_project_factory';
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
     *  承攬商 是否在該廠區是否有工程案件
     * @param $id
     * @return int
     */
    protected function getProjectExist($sid,$fid)
    {
        $ret = [0,''];
        if(!$sid && !$fid) return ;
        $data  = view_project_factory::where('b_supply_id',$sid)->where('b_factory_id',$fid)->first();
        $ret = isset($data->e_project_id)? [$data->e_project_id,$data->name] : $ret;

        return $ret;
    }

    /**
     *  工程案件 之廠區
     * @param $uid
     * @return array|int
     */
    protected function getProjectLocal($eid , $islocal = 1, $isDept = 1)
    {
        if(!$eid) return 0;
        $ret  = [];
        $data = view_project_factory::where('e_project_id',$eid)->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $tmp = [];
                $tmp['id']      = $val->b_factory_id;
                $tmp['name']    = b_factory::getName($val->b_factory_id);
                if($islocal)
                {
                    $tmp['detail']  = b_factory_a::getApiSelect($val->b_factory_id);
                }
                //轄區部門
                if($isDept)
                {
                    $tmp['dept']    = be_dept::getApiSelect($val->b_factory_id,5,'Y');
                }

                $ret[] = $tmp;
            }
        }

        return $ret;
    }

    /**
     *  工程案件 之廠區
     * @param $uid
     * @return array|int
     */
    protected function getSupplyLocal($sid , $islocal = 1)
    {
        if(!$sid) return 0;
        $ret  = [];
        $data = view_project_factory::select('b_factory_id')->where('b_supply_id',$sid)->select('b_factory_id')->groupby('b_factory_id')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $tmp = [];
                $tmp['id']      = $val->b_factory_id;
                $tmp['name']    = b_factory::getName($val->b_factory_id);
                if($islocal)
                {
                    $tmp['detail']  = b_factory_a::getApiSelect($val->b_factory_id);
                }

                $ret[] = $tmp;
            }
        }

        return $ret;
    }
}
