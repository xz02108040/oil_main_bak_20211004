<?php

namespace App\Http\Traits\Factory;

use DB;
use Storage;
use App\Model\User;
use App\Lib\HTTCLib;
use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Model\sys_param;
use App\Model\Emp\b_cust_e;
use App\Model\Factory\b_car;
use App\Model\Supply\b_supply;
use App\Model\Factory\b_car_type;
use App\Model\View\view_dept_member;
use App\Model\Engineering\e_project_car;

/**
 * 車輛
 *
 */
trait CarTrait
{
    public function toCreateEmpCar($data,$mod_user = 1000000001)
    {
        $ret = false;
        if(!count($data)) return [$ret,0,0];
        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');
        $suc = $err = $pass = 0;

        //1. 先作廢
//        $tmp = [];
//        $tmp['isClose']         = 'Y';
//        $tmp['close_user']      = $mod_user;
//        $tmp['close_stamp']     = $now;
//        DB::table('b_car')->where('car_kind',1)->where('isClose','N')->update($tmp);

        foreach ($data as $val)
        {
            $carno      = isset($val->car_no)? $val->car_no : '';
            $empno      = isset($val->emp_no)? $val->emp_no : '';
            $car_memo   = isset($val->car_memo)? $val->car_memo : '';
            if($carno)
            {
                list($b_cust_id,$be_dept_id) = b_cust_e::getloginInfo($empno);

                $carOj = b_car::where('car_no',$carno)->select('id','be_dept_id','b_cust_id','isClose','car_kind')->first();
                if(isset($carOj->id))
                {
                    //
                    $pass++;
                    if($carOj->car_kind == 1)
                    {
                        if($carOj->be_dept_id != $be_dept_id || $carOj->b_cust_id != $b_cust_id || $carOj->isClose == 'Y')
                        {
                            if($empno) $carOj->be_dept_id = $be_dept_id;
                            if($empno) $carOj->b_cust_id  = $b_cust_id;
                            if($car_memo) $carOj->car_memo  = $car_memo;
                            $carOj->isClose    = 'N';
                            $carOj->mod_user   = $mod_user;
                            $pass--;
                            if($carOj->save())
                            {
                                $suc++;
                            } else {
                                $err++;
                            }
                        }
                    }
                } else {
                    $INS = new b_car();
                    $INS->be_dept_id            = $be_dept_id ? $be_dept_id : 1;
                    $INS->b_cust_id             = $b_cust_id;
                    $INS->car_no                = $carno;
                    $INS->car_memo              = $car_memo;
                    $INS->car_kind              = 1;
                    $INS->car_type              = 1;
                    $INS->sdate                 = $today;
                    $INS->edate                 = '9999-12-31';
                    $INS->new_user              = $mod_user;
                    $INS->mod_user              = $mod_user;
                    if($INS->save())
                    {
                        $suc++;
                    } else {
                        $err++;
                    }
                }


            }
        }
        if($suc) $ret = true;
        return [$ret,$suc,$err,$pass];
    }


    /**
     * 新增 車輛[承攬商,職員]
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createCar($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->car_no)) return $ret;
        $last_car_inspection_date2      = HTTCLib::genNextInspectionDate($data->sdate);

        $INS = new b_car();
        $INS->be_dept_id            = isset($data->be_dept_id)? $data->be_dept_id : 0;
        $INS->b_supply_id           = isset($data->b_supply_id)? $data->b_supply_id : 0;
        $INS->car_no                = $data->car_no;
        $INS->car_memo              = $data->car_memo;
        $INS->car_kind              = $data->car_kind;
        $INS->car_type              = $data->car_type;
        $INS->sdate                 = $data->sdate;
        $INS->last_car_inspection_date          = isset($data->last_car_inspection_date)?   $data->last_car_inspection_date : date('Y-m-d');
        $INS->last_car_inspection_date2         = $last_car_inspection_date2;
        $INS->last_car_inspection_date1         = SHCSLib::addYear(-1,$last_car_inspection_date2);
        $INS->isInspectionCar                   = HTTCLib::isInspection($INS->last_car_inspection_date,$INS->last_car_inspection_date1,$data->sdate);
        $INS->last_exhaust_inspection_date      = isset($data->last_exhaust_inspection_date)?   $data->last_exhaust_inspection_date : date('Y-m-d');
        $INS->last_exhaust_inspection_date1     = isset($data->last_exhaust_inspection_date)?   $data->last_exhaust_inspection_date : date('Y-m-d');
        $INS->last_exhaust_inspection_date2     = isset($data->last_exhaust_inspection_date2)?   $data->last_exhaust_inspection_date2 : date('Y-m-d');
        $INS->isInspectionExhaust               = HTTCLib::isInspection($INS->last_exhaust_inspection_date,$INS->last_exhaust_inspection_date1,$data->sdate);
        $INS->edate                 = '9999-12-31';//$data->edate;
        $INS->img_path              = isset($data->img_path)? $data->img_path : '';
        $INS->img_at                = isset($data->img_path) ? time() : 0;
        $INS->b_supply_rp_car_id    = isset($data->b_supply_rp_car_id)? $data->b_supply_rp_car_id : 0;
        $INS->file1                 = isset($data->filepath1)? $data->filepath1 : '';
        $INS->file2                 = isset($data->filepath2)? $data->filepath2 : '';
        $INS->file3                 = isset($data->filepath3)? $data->filepath3 : '';
//        dd($INS);
        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;

        $ret = ($INS->save())? $INS->id : 0;
        if($ret)
        {
            $isUp       = 0;
            $id         = $ret;
            $filekind   = ($data->car_kind == 1)? config('mycfg.car_member_path') : config('mycfg.car_supply_path');
            $filepath   = $filekind.date('Y/').$id.'/';

            if(isset($data->file1) && isset($data->file1N) && $data->file1)
            {
                $filename = $id.'_A.'.$data->file1N;
                $file1    = $filepath.$filename;
                if(Storage::put($file1,$data->file1))
                {
                    $isUp++;
                    $INS->file1   = $file1;
                }
            }
            if(isset($data->file2) && isset($data->file2N) && $data->file2)
            {
                $filename = $id.'_B.'.$data->file2N;
                $file2    = $filepath.$filename;
                if(Storage::put($file2,$data->file2))
                {
                    $isUp++;
                    $INS->file2   = $file2;
                }
            }
            if(isset($data->file3) && isset($data->file3N) && $data->file3)
            {
                $filename = $id.'_C.'.$data->file3N;
                $file3    = $filepath.$filename;
                if(Storage::put($file3,$data->file3))
                {
                    $isUp++;
                    $INS->file3   = $file3;
                }
            }
            if($isUp) $INS->save();

            //2020-11-16
            if(isset($data->e_project_id) && $data->e_project_id)
            {
                //新的車體
                $data->b_car_id = $id;
                $ret = $this->createEngineeringCar($data,$mod_user);
            }
        }
        return $ret;
    }

    /**
     * 修改 車輛[承攬商,職員]
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setCar($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_car::find($id);
        if(!isset($UPD->car_no)) return $ret;
        $filekind   = ($UPD->car_kind == 1)? config('mycfg.car_member_path') : config('mycfg.car_supply_path');
        $filepath   = $filekind.date('Y/').$id.'/';
        //名稱
        if(isset($data->b_supply_id) && $data->b_supply_id && $data->b_supply_id !==  $UPD->b_supply_id)
        {
            $isUp++;
            $UPD->b_supply_id = $data->b_supply_id;
        }
        //名稱
        if(isset($data->car_no) && $data->car_no && $data->car_no !==  $UPD->car_no)
        {
            $isUp++;
            $UPD->car_no = $data->car_no;
        }
        //名稱
        if(isset($data->car_memo) && $data->car_memo && $data->car_memo !==  $UPD->car_memo)
        {
            $isUp++;
            $UPD->car_memo = $data->car_memo;
        }
        //名稱
        if(isset($data->car_type) && $data->car_type && $data->car_type !==  $UPD->car_type)
        {
            $isUp++;
            $UPD->car_type = $data->car_type;
        }
        //發證日
        if(isset($data->sdate) && $data->sdate && $data->sdate !==  $UPD->sdate)
        {
            $isUp++;
            $UPD->sdate = $data->sdate;
            //驗車
            $UPD->last_car_inspection_date2 = HTTCLib::genNextInspectionDate($data->sdate);
            $UPD->last_car_inspection_date1 = SHCSLib::addYear(-1,$UPD->last_car_inspection_date2);
            $UPD->isInspectionCar           = HTTCLib::isInspection($UPD->last_car_inspection_date,$UPD->last_car_inspection_date1,$UPD->sdate);
            //驗排氣
            $UPD->last_exhaust_inspection_date2 = HTTCLib::genNextInspectionDate($data->sdate);
            $UPD->last_exhaust_inspection_date1 = SHCSLib::addYear(-1,$UPD->last_exhaust_inspection_date2);
            $UPD->isInspectionExhaust           = HTTCLib::isInspection($UPD->last_exhaust_inspection_date,$UPD->last_exhaust_inspection_date1,$UPD->sdate);
        }
        //上次驗車日
        if(isset($data->last_car_inspection_date) && $data->last_car_inspection_date && $data->last_car_inspection_date !==  $UPD->last_car_inspection_date)
        {
            $isUp++;
            $UPD->last_car_inspection_date  = $data->last_car_inspection_date;
            $UPD->isInspectionCar           = HTTCLib::isInspection($UPD->last_car_inspection_date,$UPD->last_car_inspection_date1,$UPD->sdate);
            //dd($UPD->isInspectionCar,$UPD->last_car_inspection_date,$UPD->last_car_inspection_date1,$UPD->last_car_inspection_date2);
        }
        //上次驗排氣日
        if(isset($data->last_exhaust_inspection_date) && $data->last_exhaust_inspection_date && $data->last_exhaust_inspection_date !==  $UPD->last_exhaust_inspection_date)
        {
            $isUp++;
            $UPD->last_exhaust_inspection_date  = $data->last_exhaust_inspection_date;
            $UPD->isInspectionExhaust           = HTTCLib::isInspection($UPD->last_exhaust_inspection_date,$UPD->last_exhaust_inspection_date1,$UPD->sdate);
            //dd($UPD->isInspectionExhaust,$UPD->last_exhaust_inspection_date,$UPD->last_exhaust_inspection_date1,$UPD->last_exhaust_inspection_date2);
        }
        //圖片
        if(isset($data->img_path) && $data->img_path)
        {
            $isUp++;
            $UPD->img_path = $data->img_path;
            $UPD->img_at   = time();
        }
        //檔案1
        if(isset($data->file1) && $data->file1)
        {
            $filename = $id.'_A.'.$data->file1N;
            $file     = $filepath.$filename;
            if(Storage::put($file,$data->file1))
            {
                $isUp++;
                $UPD->file1   = $file;
            }
        }
        //只更新檔案路徑至b_car車輛基本檔
        elseif (isset($data->filepath1) && $data->filepath1 !==  $UPD->filepath1) {
            $isUp++;
            $UPD->file1   = $data->filepath1;
        }

        //檔案2
        if(isset($data->file2) && $data->file2)
        {
            $filename = $id.'_B.'.$data->file2N;
            $file     = $filepath.$filename;
            if(Storage::put($file,$data->file2))
            {
                $isUp++;
                $UPD->file2   = $file;
            }
        } 
        //只更新檔案路徑至b_car車輛基本檔
        elseif (isset($data->filepath2) && $data->filepath2 !==  $UPD->filepath2) {
            $isUp++;
            $UPD->file2   = $data->filepath2;
        }

        //檔案3
        if(isset($data->file3) && $data->file3)
        {
            $filename = $id.'_C.'.$data->file3N;
            $file     = $filepath.$filename;
            if(Storage::put($file,$data->file3))
            {
                $isUp++;
                $UPD->file3   = $file;
            }
        }
        //只更新檔案路徑至b_car車輛基本檔
        elseif (isset($data->filepath3) && $data->filepath3 !==  $UPD->filepath3) {
            $isUp++;
            $UPD->file3   = $data->filepath3;
        }

        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !==  $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
                $UPD->close_stamp   = $now;

                // 車輛退公司時，同步清除通行證號
                $UPD->car_memo   = ' ';

                //退車時，也一併退案
                $e_project_car_id = e_project_car::getId($id);
                $upAry = array();
                $upAry['isClose'] = 'Y';
                $ret2 = $this->setEngineeringCar($e_project_car_id, $upAry, $mod_user);
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
     * 取得 車輛申請 by 承攬商
     *
     * @return array
     */
    public function getApiSupplyCarMainList($searchList = [0,'N','','',''])
    {
        list($type,$isClose,$car_no,$sdate1,$edate1) = $searchList;
//        dd($type,$isClose,$car_no,$sdate1,$edate1);
        $data = b_car::join('b_supply as s','s.id','=','b_car.b_supply_id')->
        selectRaw('MAX(s.id) as b_supply_id,MAX(s.name) as b_supply,MAX(s.tel1) as tel1,count(s.id) as amt')->
        where('b_car.car_kind',2);
        if($car_no)
        {
            $data = $data->where('b_car.car_no','like','%'.$car_no.'%');
        }
        if(CheckLib::isDate($sdate1))
        {
            $data = $data->where('b_car.sdate','>=',$sdate1);
        }
        if(CheckLib::isDate($edate1))
        {
            $data = $data->where('b_car.edate','<=',$edate1);
        }
        if($type)
        {
            $data = $data->where('b_car.car_type',$type);
        }
        if($isClose)
        {
            $data = $data->where('b_car.isClose',$isClose);
        }
        $data = $data->groupby('b_supply_id');

        $data = $data->get();
        if(is_object($data)) {
            $ret = (object)$data;
        }
        return $ret;
    }

    /**
     * 取得 車輛[承攬商,職員]
     *
     * @return array
     */
    public function getApiCarList($kind, $b_supply_id = 0, $searchList = [0,'N','','',''])
    {
        list($type,$isClose,$car_no,$sdate1,$edate1) = $searchList;
        $ret = array();
        $inspectionAry1 = SHCSLib::getCode('CAR_YEAR_LNSPECTION',0);
        $inspectionAry2 = SHCSLib::getCode('CAR_YEAR_LNSPECTION2',0);
        $typeAry = b_car_type::getSelect();
        //取第一層
        $data = b_car::where('car_kind',$kind);

        if($isClose)
        {
            $data = $data->where('isClose',$isClose);
        }
        if($b_supply_id)
        {
            $data = $data->where('b_supply_id',$b_supply_id);
        }
        if($type)
        {
            $data = $data->where('car_type',$type);
        }
        if($car_no)
        {
            $data = $data->where('b_car.car_no','like','%'.$car_no.'%');
        }
        if(CheckLib::isDate($sdate1))
        {
            $data = $data->where('b_car.sdate','>=',$sdate1);
        }
        if(CheckLib::isDate($edate1))
        {
            $data = $data->where('b_car.edate','<=',$edate1);
        }
        $data = $data->orderby('isClose')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $id        = $v->id;
                $sdate     = $v->sdate;
                $last_car_inspection_date = $v->last_car_inspection_date;
                $imgPath   = strlen($v->img_path)? storage_path('app'.$v->img_path) : '';
                $filePath1 = strlen($v->file1)? storage_path('app'.$v->file1) : '';
                $filePath2 = strlen($v->file2)? storage_path('app'.$v->file2) : '';
                $filePath3 = strlen($v->file3)? storage_path('app'.$v->file3) : '';

                $data[$k]['img_path']  = ($imgPath && file_exists($imgPath))? SHCSLib::url('img/Car/',$id,'sid=D') : '';
                $data[$k]['filePath1'] = ($filePath1 && file_exists($filePath1))? SHCSLib::url('img/Car/',$id,'sid=A') : '';
                $data[$k]['filePath2'] = ($filePath2 && file_exists($filePath2))? SHCSLib::url('img/Car/',$id,'sid=B') : '';
                $data[$k]['filePath3'] = ($filePath3 && file_exists($filePath3))? SHCSLib::url('img/Car/',$id,'sid=C') : '';
                
                $data[$k]['supply']     = b_supply::getName($v->b_supply_id);
                $data[$k]['project']     = e_project_car::getProjectAry($id);
                $data[$k]['car_type_name']     = isset($typeAry[$v->car_type])? $typeAry[$v->car_type] : '';
                $data[$k]['oil_kind']          = b_car_type::getOilKind($v->car_type);
                $data[$k]['inspection_name1']  = isset($inspectionAry1[$v->isInspectionCar])? $inspectionAry1[$v->isInspectionCar] : '';
                $data[$k]['inspection_name2']  = isset($inspectionAry2[$v->isInspectionExhaust])? $inspectionAry2[$v->isInspectionExhaust] : '';
                if($sdate == '1900-01-01') $data[$k]['sdate'] = '';
                if($last_car_inspection_date == '1900-01-01') $data[$k]['last_car_inspection_date'] = '';
                
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
