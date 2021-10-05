<?php

namespace App\Model\Factory;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_guest extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_guest';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];


}
