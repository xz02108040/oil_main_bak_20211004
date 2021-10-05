<?php

namespace App\Model\Tmp;

use App\Model\b_menu;
use Illuminate\Database\Eloquent\Model;
use Lang;

class t_project_member extends Model
{
    /**
     * Table: 中油大林廠介接資料庫之工程案件的承攬商成員
     */
    protected $table = 't_project_member';
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
    protected  function isExist($chk_date,$e_project_id,$bc_id)
    {
        if(!$e_project_id || !$bc_id) return 0;
        $data = t_project_member::where('e_project_id',$e_project_id);
        $data = $data->where('chk_date',$chk_date);
        $data = $data->where(function ($query) use ($chk_date) {
            $query->where('edate', '=', '')
                ->orWhere('edate', '>=', $chk_date);
        });
        $data = $data->where('bc_id',$bc_id)->first();
        return (isset($data->id))? $data->id : 0;
    }



}
