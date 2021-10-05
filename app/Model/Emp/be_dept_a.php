<?php

namespace App\Model\Emp;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class be_dept_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'be_dept_a';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = be_dept_a::find($id);
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isReExist($dept,$title,$extid = 0)
    {
        if(!$dept || !$title) return 0;
        $data = be_dept_a::where('be_dept_id',$dept)->where('be_title_id',$title);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        return be_dept_a::find($id)->name;
    }

    //取得 下拉選擇[部門內的職稱]
    protected  function getSelect($dept_id)
    {
        $ret    = [];
        $data   = be_dept_a::join('be_title','be_dept_a.be_title_id','=','be_title.id')->
        orderby('be_title.level')->select('be_dept_a.be_title_id','be_title.name','be_title.level')
            ->where('be_dept_a.be_dept_id',$dept_id)
            ->where('be_dept_a.isClose','N')->where('be_title.isClose','N')->get();
        $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->be_title_id] = $val->name.'('.$val->level.')';
        }

        return $ret;
    }
}
