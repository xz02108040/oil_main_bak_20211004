<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class log_action extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.v';
    /**
     * 使用者Table:
     */
    protected $table = 'log_action';
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
    protected function isExist($id)
    {
        if(!$id) return 0;
        $data  = log_action::where('id',$id)->where('isClose','N');
        return $data->count();
    }

}
