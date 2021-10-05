<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\et_course;
use App\Model\Engineering\et_course_type;
use Storage;
use App\Model\User;

/**
 * 課程
 *
 */
trait CourseTrait
{
    /**
     * 新增 課程
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createCourse($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new et_course();
        $INS->name          = $data->name;
        $INS->course_code   = $data->course_code ? $data->course_code : '';
        $INS->course_type   = $data->course_type ? $data->course_type : 1;
        //$INS->isDoorRule    = (isset($data->isDoorRule) && $data->isDoorRule == 'Y') ? 'Y' : 'N';
        $INS->valid_day     = $data->valid_day ? $data->valid_day : 999;
        $INS->memo          = $data->memo ? $data->memo : '';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret)
        {
            $isUp       = 0;
            $id         = $ret;
            $filepath   = config('mycfg.course_path').date('Y/').$id.'/';

            if(isset($data->file1) && isset($data->file1N) && $data->file1)
            {
                $filename = $id.'_A.'.$data->file1N;
                $file1    = $filepath.$filename;
                if(Storage::put($file1,$data->file1))
                {
                    $isUp++;
                    $INS->tran_file1   = $file1;
                }
            }
            if(isset($data->file2) && isset($data->file2N) && $data->file2)
            {
                $filename = $id.'_B.'.$data->file2N;
                $file2    = $filepath.$filename;
                if(Storage::put($file2,$data->file2))
                {
                    $isUp++;
                    $INS->tran_file2   = $file2;
                }
            }
            if(isset($data->file3) && isset($data->file3N) && $data->file3)
            {
                $filename = $id.'_C.'.$data->file3N;
                $file3    = $filepath.$filename;
                if(Storage::put($file3,$data->file3))
                {
                    $isUp++;
                    $INS->tran_file3   = $file3;
                }
            }
            if($isUp) $INS->save();
        }
        return $ret;
    }

    /**
     * 修改 課程
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setCourse($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;
        $filepath   = config('mycfg.course_path').date('Y/').$id.'/';

        $UPD = et_course::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //分類
        if(isset($data->course_type) && is_numeric($data->course_type) && $data->course_type !== $UPD->course_type)
        {
            $isUp++;
            $UPD->course_type = $data->course_type;
        }
        //代碼
        if(isset($data->course_code) && strlen($data->course_code) && $data->course_code !== $UPD->course_code)
        {
            $isUp++;
            $UPD->course_code = $data->course_code;
        }
        //有效天數
        if(isset($data->valid_day) && $data->valid_day > 0 && $data->valid_day !== $UPD->valid_day)
        {
            $isUp++;
            $UPD->valid_day = $data->valid_day;
        }
        //說明
        if(isset($data->memo) && strlen($data->memo) && $data->memo !== $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
        }
        //檔案1
        if(isset($data->file1) && $data->file1)
        {
            $filename = $id.'_A.'.$data->file1N;
            $file     = $filepath.$filename;
            if(Storage::put($file,$data->file1))
            {
                $isUp++;
                $UPD->tran_file1   = $file;
            }
        }
        //檔案2
        if(isset($data->file2) && $data->file2)
        {
            $filename = $id.'_B.'.$data->file2N;
            $file     = $filepath.$filename;
            if(Storage::put($file,$data->file2))
            {
                $isUp++;
                $UPD->tran_file2   = $file;
            }
        }
        //檔案3
        if(isset($data->file3) && $data->file3)
        {
            $filename = $id.'_C.'.$data->file3N;
            $file     = $filepath.$filename;
            if(Storage::put($file,$data->file3))
            {
                $isUp++;
                $UPD->tran_file3   = $file;
            }
        }
        //是否為門禁規則必要條件
        if(isset($data->isDoorRule) && in_array($data->isDoorRule,['Y','N']) && $data->isDoorRule !== $UPD->isDoorRule)
        {
            $isUp++;
            $UPD->isDoorRule = $data->isDoorRule;
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
                $UPD->close_stamp   = $now;
            } else {
                $UPD->isClose = 'N';
            }
        }
        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 課程
     *
     * @return array
     */
    public function getApiCourseList()
    {
        $ret = array();
        //取第一層
        $data = et_course::join('et_course_type as t','t.id','=','et_course.course_type')->
            select('et_course.*','t.name as course_type_name')->orderby('et_course.isClose')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $id        = $v->id;
                $filePath1 = strlen($v->tran_file1)? storage_path('app'.$v->tran_file1) : '';
                $filePath2 = strlen($v->tran_file2)? storage_path('app'.$v->tran_file2) : '';
                $filePath3 = strlen($v->tran_file3)? storage_path('app'.$v->tran_file3) : '';
                $data[$k]['filePath1'] = ($filePath1 && file_exists($filePath1))? SHCSLib::url('file/','A'.$id,'sid=Course') : '';
                $data[$k]['filePath2'] = ($filePath2 && file_exists($filePath2))? SHCSLib::url('file/','B'.$id,'sid=Course') : '';
                $data[$k]['filePath3'] = ($filePath3 && file_exists($filePath3))? SHCSLib::url('file/','C'.$id,'sid=Course') : '';

                $data[$k]['close_user']         = User::getName($v->close_user);
                $data[$k]['new_user']           = User::getName($v->new_user);
                $data[$k]['mod_user']           = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
