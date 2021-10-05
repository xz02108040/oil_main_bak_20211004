<?php

namespace App\Model\Engineering;

use App\Model\Factory\b_car;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_violation_contractor_history extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_violation_contractor_history';
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

}
