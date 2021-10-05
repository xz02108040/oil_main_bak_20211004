<?php

namespace App\Model\Tmp;

use Illuminate\Database\Eloquent\Model;
use Lang;

class t_project extends Model
{
    /**
     * Table: 中油大林廠介接資料庫之工程案件
     */
    protected $table = 't_project';
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

    //選單是否存在
    protected  function isExist($chk_date,$e_project_id)
    {
        if(!$e_project_id ) return 0;
        $data = t_project::where('e_project_id',$e_project_id);
        $data = $data->where('chk_date',$chk_date)->first();
        return (isset($data->id))? $data->id : 0;
    }
    //選單是否存在
    protected  function getUpAt($chk_date = '')
    {
        if(!$chk_date) $chk_date = date('Y-m-d');
        $data = t_project::where('chk_date',$chk_date)->first();
        return (isset($data->id))? $data->created_at : '';
    }



}
