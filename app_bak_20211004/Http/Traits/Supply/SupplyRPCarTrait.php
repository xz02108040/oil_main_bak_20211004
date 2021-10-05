<?php

namespace App\Http\Traits\Supply;

use Lang;
use Storage;
use App\Model\User;
use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Model\Factory\b_car;
use App\Model\Factory\b_car_type;
use App\Http\Traits\Factory\CarTrait;
use App\Model\Supply\b_supply_rp_car;
use App\Model\Engineering\e_project_car;
use App\Http\Traits\Engineering\EngineeringCarTrait;

/**
 * 承攬商_車輛申請
 *
 */
trait SupplyRPCarTrait
{
    use CarTrait, EngineeringCarTrait;
    
    /**
     * 修改 車輛申請
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPCar($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $aprocAry = array_keys(SHCSLib::getCode('RP_SUPPLY_CAR_APROC'));
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_supply_rp_car::find($id);
        $b_car_id = b_car::getID($UPD->car_no);
        if(!isset($UPD->car_no)) return $ret;
        $filepath   = config('mycfg.supply_car_apth').'RP/'.date('Y/').$id.'/';

        //申請類型
        if(isset($data->rp_type) && ($data->rp_type) && $data->rp_type !== $UPD->rp_type)
        {
            $isUp++;
            $UPD->rp_type = $data->rp_type;
        }
        //車牌
        if(isset($data->car_no) && ($data->car_no) && $data->car_no !== $UPD->car_no)
        {
            $isUp++;
            $UPD->car_no = $data->car_no;
        }
        //通行證號
        if(isset($data->car_memo) && ($data->car_memo) && $data->car_memo !== $UPD->car_memo)
        {
            $isUp++;
            $UPD->car_memo = $data->car_memo;
        }

        //車分類
        if(isset($data->car_type) && ($data->car_type) && $data->car_type !== $UPD->car_type)
        {
            $isUp++;
            $UPD->car_type = $data->car_type;
        }
        //有效日期
        if(isset($data->sdate) && CheckLib::isDate($data->sdate) && $data->sdate !== $UPD->sdate)
        {
            $isUp++;
            $UPD->sdate = $data->sdate;
        }
        //有效日期
        if(isset($data->last_car_inspection_date) && CheckLib::isDate($data->last_car_inspection_date) && $data->last_car_inspection_date !== $UPD->last_car_inspection_date)
        {
            $isUp++;
            $UPD->last_car_inspection_date = $data->last_car_inspection_date;
        }
        //有效日期
        if(isset($data->last_exhaust_inspection_date) && CheckLib::isDate($data->last_exhaust_inspection_date) && $data->last_exhaust_inspection_date !== $UPD->last_exhaust_inspection_date)
        {
            $isUp++;
            $UPD->last_exhaust_inspection_date = $data->last_exhaust_inspection_date;
        }
        //有效日期
        if(isset($data->last_exhaust_inspection_date2) && CheckLib::isDate($data->last_exhaust_inspection_date2) && $data->last_exhaust_inspection_date2 !== $UPD->last_exhaust_inspection_date2)
        {
            $isUp++;
            $UPD->last_exhaust_inspection_date2 = $data->last_exhaust_inspection_date2;
        }
        //證照檔案
        if(isset($data->file1) && ($data->file1) && $data->file1N)
        {
            $filename = $id.'_A.'.$data->file1N;
            $file    = $filepath.$filename;
            if(Storage::put($file,$data->file1))
            {
                $isUp++;
                $UPD->file1 = $file;
            }
        }
        //證照檔案
        if(isset($data->file2) && ($data->file2) && $data->file2N)
        {
            $filename = $id.'_B.'.$data->file2N;
            $file    = $filepath.$filename;
            if(Storage::put($file,$data->file2))
            {
                $isUp++;
                $UPD->file2 = $file;
            }
        }
        //證照檔案
        if(isset($data->file3) && ($data->file3) && $data->file3N)
        {
            $filename = $id.'_C.'.$data->file3N;
            $file    = $filepath.$filename;
            if(Storage::put($file,$data->file3))
            {
                $isUp++;
                $UPD->file3 = $file;
            }
        }

        //審查結果
        if(isset($data->aproc) && in_array($data->aproc,$aprocAry) && $data->aproc !== $UPD->aproc)
        {
            $isOK = 0;
            //審查通過
            if($data->aproc == 'O')
            {
                $data->car_no       = $UPD->car_no;
                $data->car_memo     = $UPD->car_memo;
                $data->car_type     = $UPD->car_type;
                $data->img_path     = $UPD->img_path;
                $data->filepath1    = $UPD->file1;
                $data->filepath2    = $UPD->file2;
                $data->filepath3    = $UPD->file3;
                $data->e_project_id = $UPD->e_project_id;
                
                //申請類型
                switch ($data->rp_type) {
                    // 1.加公司加案件
                    case '1':
                        if ($this->createCar($data, $mod_user)) {
                            $isOK = 1;
                        }
                        break;
                    // 2.只加案件
                    case '2':
                        $data->b_car_id = $b_car_id;
                        if ($this->createEngineeringCar($data, $mod_user)) {
                            // 車輛加入案件後，同步更新通行證號等車輛基本資料
                            $this->setCar($b_car_id, $data, $mod_user);
                        
                            $isOK = 1;
                        }
                        break;
                    // 3.轉換公司，目前無轉換公司，直接回傳結果失敗
                    case '3':
                        $isOK = 0;
                        break;
                    // 4.只退案件
                    case '4':
                        $e_project_car_id = e_project_car::getId($b_car_id);
                        $upAry = array();
                        $upAry['isClose'] = 'Y';
                        if ($this->setEngineeringCar($e_project_car_id, $upAry, $mod_user)) {
                            $isOK = 1;
                        }
                        break;
                    // 5.退公司退案件
                    case '5':
                        //退車時，也一併退案
                        $upAry = array();
                        $upAry['isClose']  = 'Y';
                        $upAry['car_memo'] = ' ';
                        if ($this->setCar($b_car_id, $upAry, $mod_user)) {
                            $isOK = 1;
                        }
                        break;
                    //預設值
                    default:
                        $isOK = 0;
                        break;
                }

            } else {
                $isOK = 1;
            }
            if($isOK)
            {
                $isUp++;
                //監造審查完畢
                if($UPD->aproc == 'P')
                {
                    $UPD->charge_user2   = $mod_user;
                    $UPD->charge_stamp2  = $now;
                    $UPD->charge_memo2   = $data->charge_memo;
                } else {
                    $UPD->charge_user1   = $mod_user;
                    $UPD->charge_stamp1  = $now;
                    $UPD->charge_memo1   = $data->charge_memo;
                }
                $UPD->aproc         = $data->aproc;
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
    public function getApiSupplyRPCarMainList($aproc = 'A',$allowProject = [])
    {
        $data = b_supply_rp_car::join('b_supply as s','s.id','=','b_supply_rp_car.b_supply_id')->
        selectRaw('MAX(s.id) as b_supply_id,MAX(s.name) as b_supply,MAX(s.tel1) as tel1,count(s.id) as amt')->
        groupby('b_supply_id');

        if($aproc)
        {
            $data = $data->where('aproc',$aproc);
        }
        if(count($allowProject))
        {
            $data = $data->whereIn('e_project_id',$allowProject);
        }
        $data = $data->get();
        if(is_object($data)) {
            $ret = (object)$data;
        }
        return $ret;
    }

    /**
     * 取得 車輛申請
     *
     * @return array
     */
    public function getApiSupplyRPCarList($sid,$aproc = 'A',$allowProject = [])
    {
        $ret = array();
        $typeAry  = b_car_type::getSelect();
        $aprocAry = SHCSLib::getCode('RP_SUPPLY_CAR_APROC');
        $rp_type_Ary = SHCSLib::getCode('RP_SUPPLY_CAR_TYPE');
        //取第一層
        $data = b_supply_rp_car::join('e_project as p','p.id','=','b_supply_rp_car.e_project_id')->
                join('b_supply as s','s.id','=','b_supply_rp_car.b_supply_id')->
                select('b_supply_rp_car.*','p.name as project','p.project_no','p.edate as project_edate','s.name as supply')->
                where('b_supply_rp_car.b_supply_id',$sid)->where('b_supply_rp_car.aproc',$aproc);
        if(count($allowProject))
        {
            $data = $data->whereIn('p.id',$allowProject);
        }
        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $id        = $v->id;
                $filePath1 = strlen($v->file1)? storage_path('app'.$v->file1) : '';
                $filePath2 = strlen($v->file2)? storage_path('app'.$v->file2) : '';
                $filePath3 = strlen($v->file3)? storage_path('app'.$v->file3) : '';
                $imgPath   = strlen($v->img_path)? storage_path('app'.$v->img_path) : '';

                $data[$k]['img_path']  = ($imgPath && file_exists($imgPath))? SHCSLib::url('img/RpCar/',$id,'sid=D') : '';
                $data[$k]['filePath1'] = ($filePath1 && file_exists($filePath1))? SHCSLib::url('img/RpCar/',$id,'sid=A') : '';
                $data[$k]['filePath2'] = ($filePath2 && file_exists($filePath2))? SHCSLib::url('img/RpCar/',$id,'sid=B') : '';
                $data[$k]['filePath3'] = ($filePath3 && file_exists($filePath3))? SHCSLib::url('img/RpCar/',$id,'sid=C') : '';

                $data[$k]['car_type_name']  = isset($typeAry[$v->car_type])? $typeAry[$v->car_type] : '';
                $data[$k]['aproc_name']     = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['rp_type']        = isset($v->rp_type)? $v->rp_type : '';
                $data[$k]['rp_type_name']   = isset($rp_type_Ary[$v->rp_type])? $rp_type_Ary[$v->rp_type] : '';
                $data[$k]['apply_name']     = User::getName($v->apply_user);
                $data[$k]['apply_stamp']    = substr($v->apply_stamp,0,16);
                $data[$k]['charge_name1']   = User::getName($v->charge_user1);
                $data[$k]['charge_stamp1']  = substr($v->charge_stamp1,0,16);
                $data[$k]['charge_name2']   = User::getName($v->charge_user2);
                $data[$k]['charge_stamp2']  = substr($v->charge_stamp2,0,16);
                $data[$k]['chg_user']       = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    
    /**
     * 取得 車輛申請類型的陣列
     * @param int $rp_type
     * @return array
     */
    protected function getCarRpTypeAry($rp_type = 0)
    {
        //申請類型 1.加公司加案件 2.只加案件 3.轉換公司 4.只退案件 5.退公司退案件
        switch ($rp_type) {
                //加入
            case '1':
                $extAry = [2, 3, 4, 5];
                break;
            case '2':
                $extAry = [1, 3, 4, 5];
                break;
            //轉換
            case '3':
                $extAry = [1, 2, 4, 5];
                break;
            //退出
            case '4':
            case '5':
                $extAry = [1, 2, 3];
                break;
            //全部
            default:
                $extAry = [];
                break;
        }
        $rp_type_Ary = SHCSLib::getCode('RP_SUPPLY_CAR_TYPE', 0, 0, $extAry);
        return $rp_type_Ary;
    }

}
