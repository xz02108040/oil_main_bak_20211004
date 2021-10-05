<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\Factory\b_car;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_rp_chg_card extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_chg_card';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

}
