<?php

namespace App\Model\Engineering;

use Illuminate\Database\Eloquent\Model;
use Lang;

class et_course extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'et_course';
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
        $data  = et_course::where('id',$id);
        return $data->count();
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return 0;
        $data  = et_course::where('id',$id)->select('name')->first();
        return isset($data->name)? $data->name : '';
    }

    /**
     *  取得有效天數
     * @param $id
     * @return int
     */
    protected function getValidday($id)
    {
        if(!$id) return 0;
        $data  = et_course::where('id',$id)->select('valid_day')->first();
        return isset($data->valid_day)? $data->valid_day : 0;
    }
    /**
     *  取得上課檔案路徑
     * @param $id
     * @return int
     */
    protected function getFile($id, $code = '')
    {
        if(!$id) return '';
        $data  = et_course::where('id',$id)->select('tran_file1','tran_file2','tran_file3')->first();

        if($code == 'C')
        {
            $filekey = 'tran_file3';
        }elseif($code == 'B')
        {
            $filekey = 'tran_file2';
        }else{
            $filekey = 'tran_file1';
        }

        return isset($data->$filekey)? $data->$filekey : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret  = [];
        $data = et_course::select('id','name')->where('isClose','N');
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }


    //取得 下拉選擇全部
    protected  function getApiSelect()
    {
        $ret  = [];
        $ret[]= ['id'=>0,'name'=>Lang::get('sys_base.base_10015')];
        $data = et_course::select('id','name')->where('isClose','N');
        $data = $data->get();

        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id']      = $val->id;
            $tmp['name']    = $val->name;
            $ret[]          = $tmp;
        }

        return $ret;
    }


}
