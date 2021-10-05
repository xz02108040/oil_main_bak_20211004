<?php

namespace App\Model\Engineering;

use Lang;
use App\Lib\HtmlLib;
use App\Model\Supply\b_supply;
use Illuminate\Support\Facades\DB;
use App\Model\View\view_supply_user;
use Illuminate\Database\Eloquent\Model;

class et_traning_m extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'et_traning_m';
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
    protected function isExist($cid = 0,$tid = 0,$sid = 0,$uid = 0,$aproc = '',$project = [])
    {
        if(!$cid && !$tid) return 0;
        $data  = et_traning_m::where('et_traning_m.isClose','N');
        if($cid)
        {
            $data = $data->where('et_traning_m.et_course_id',$cid);
        }
        if($tid)
        {
            $data = $data->where('et_traning_m.et_traning_id',$tid);
        }
        if($sid)
        {
            $data = $data->where('et_traning_m.b_supply_id',$sid);
        }
        if($uid)
        {
            $data = $data->where('et_traning_m.b_cust_id',$uid);
        }
        if(is_array($project) && count($project))
        {
            $data = $data->whereIn('et_traning_m.e_project_id',$project);
        }
        if(is_array($aproc) && count($aproc))
        {
            $data = $data->whereIn('et_traning_m.aproc',$aproc);
        }elseif(is_string($aproc))
        {
            $data = $data->where('et_traning_m.aproc',$aproc);
        }
        return $data->count();
    }

    //取得 通過日期
    protected  function getPassDate($et_course_id,$b_cust_id)
    {
        if(!$et_course_id || !$b_cust_id) return '';
        $today = date('Y-m-d');
        $data = et_traning_m::where('et_course_id',$et_course_id)->where('b_cust_id',$b_cust_id)->where('isClose','N');
        $data = $data->where('valid_date','>=',$today)->where('aproc','O');
        $data = $data->select('pass_date')->first();

        return isset($data->pass_date)? $data->pass_date : '';
    }

    //取得 下拉選擇全部
    protected  function getSupplySelect($et_traning_id,$isFirst = 1)
    {
        $ret  = [];
        $data = et_traning_m::join('view_user as v','v.b_cust_id','=','et_traning_m.b_cust_id')->
                where('et_traning_m.et_traning_id',$et_traning_id)->where('et_traning_m.isClose','N');
        $data = $data->select('et_traning_m.id','et_traning_m.b_supply_id')->groupby('et_traning_m.b_supply_id')->
        groupby('et_traning_m.id')->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = b_supply::getName($val->b_supply_id);
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($tid = 0,$sid = 0,$aproc = [],$isFirst = 1)
    {
        if(!$tid || !$sid) return 0;
        $ret  = [];
        $data = et_traning_m::join('view_user as a','et_traning_m.b_cust_id','=','a.b_cust_id')->
                where('et_traning_m.isClose','N')->select('a.b_cust_id as id','a.name','a.bc_id');
        if($tid)
        {
            $data = $data->where('et_traning_m.et_traning_id',$tid);
        }
        if($sid)
        {
            $data = $data->where('et_traning_m.b_supply_id',$sid);
        }
        if(is_array($aproc) && count($aproc))
        {
            $data = $data->whereIn('et_traning_m.aproc',$aproc);
        }
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }

    protected function getSelfPassCourse($uid, $inAry = [], $isApi = 0)
    {
        $ret = ($isApi)? [] : '';
        $vailDate  = '';
        $courseAry = $coursePassAry = [];
        $tmpY = $tmpN = [];
        if(!$uid) return $ret;

        $data = et_traning_m::join('et_course as c','c.id','=','et_traning_m.et_course_id')->
        where('et_traning_m.b_cust_id',$uid)->where('et_traning_m.aproc','O')->
        select(\DB::raw("MAX(et_traning_m.valid_date) AS valid_date,et_traning_m.et_course_id,c.name as course"))->
        groupby('et_traning_m.et_course_id','c.name')->
        orderby('c.name','desc');
        
        if(count($inAry))
        {
            $data = $data->whereIn('et_course_id',$inAry);
        }
        if($data->count())
        {

            foreach ($data->get() as $key => $val)
            {
                $courseAry[] = $val->et_course_id;

                // 取得該筆教育訓練資料
                $data2 = et_traning_m::where('b_cust_id', $uid)->
                where('valid_date', $val->valid_date)->
                where('et_course_id', $val->et_course_id)->
                orderBy('isClose')->
                first();

                if($data2->isClose == 'Y')
                {
                    //過期
                    if(!isset($tmpN[$val->et_course_id]))
                    {
                        if($isApi)
                        {
                            $tmpN[$val->et_course_id]['name'] = $val->course.'('.$val->valid_date.')';
                            $tmpN[$val->et_course_id]['date'] = $val->valid_date;
                        } else {
                            $tmpN[$val->et_course_id] = HtmlLib::Color($val->course,'#CF836E',1).'(<span style="color:red">過期</span>'.$val->valid_date.')';
                        }
                    }
                } else {
                    if(!isset($tmpY[$val->et_course_id]))
                    {
                        // if(!$vailDate) $vailDate = $val->valid_date;
                        //有效
                        if(strtotime($val->valid_date) >= strtotime($vailDate))
                        {
                            if($isApi)
                            {
                                $tmpY[$val->et_course_id]['name'] = $val->course.'('.$val->valid_date.')';
                                $tmpY[$val->et_course_id]['date'] = $val->valid_date;
                            } else {
                                $tmpY[$val->et_course_id] = HtmlLib::Color($val->course,'#CF836E',1).'('.$val->valid_date.')';
                            }
                        }
                    }
                }
            }

            if(count($courseAry))
            {
                foreach ($courseAry as $cid)
                {
                    $coursePassAry[$cid] = (isset($tmpY[$cid]))? $tmpY[$cid] : (isset($tmpN[$cid])? $tmpN[$cid] : '' );
                }
            }
            $ret = ($isApi)? $coursePassAry : implode('<br/>',$coursePassAry);
        }
        return $ret;
    }
    protected function getAmt($et_traning_id, $supply_id = 0, $aproc = ['A','P','R'])
    {
        $data = et_traning_m::where('et_traning_id',$et_traning_id)->
        where('et_traning_m.isClose','N');

        if($supply_id)
        {
            $data = $data->where('et_traning_m.b_supply_id',$supply_id);
        }

        if($aproc)
        {
            $data = $data->whereIn('et_traning_m.aproc',$aproc);
        }

        return $data->count();
    }
    protected function getCoursePassAmt($et_course_id, $supply_id = 0)
    {
        $today = date('Y-m-d');
        $memberAry = view_supply_user::getMemeber($supply_id);

        $data = et_traning_m::where('et_course_id',$et_course_id)->
        where('et_traning_m.isClose','N')->where('aproc','O')->where('valid_date','>=',$today);
        if(count($memberAry))
        {
            $data = $data->whereIn('b_cust_id',$memberAry);
        }

        return $data->count();
    }
    //取得審核報名申請時，是否為人數限制內
    protected function getTraningIsEdit($et_traning_id)
    {
        $IsEdit = 0;
        $register_men_limit = et_traning::where('id', $et_traning_id)->select('register_men_limit')->first()['register_men_limit'];
        $traning_men_RO = $this->isExist(0,$et_traning_id,0,0,['R','O']);
        
        //工安課同意人數通過需要低於限制或限制人數等於0，才能核可
        if ($traning_men_RO < $register_men_limit || $register_men_limit == '0') {
            $IsEdit = 1;
        }
        return $IsEdit;
    }
}
