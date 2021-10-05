<?php

namespace App\Http\Controllers;

use App\Lib\AESLib;
use App\Lib\SHCSLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Engineering\et_course;
use App\Model\Engineering\et_traning;
use App\Model\Factory\b_car;
use App\Model\Supply\b_supply_member_l;
use App\Model\Supply\b_supply_rp_car;
use App\Model\Supply\b_supply_rp_member_l;
use App\Model\WorkPermit\wp_check_kind_f;
use Illuminate\Http\Request;
use File;
use Lang;
use Image;
use Storage;
use Response;

class FileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FileController
    |--------------------------------------------------------------------------
    |
    | 下載檔案
    |
    |
    */


    /**
     * Router.
     *
     * @var string
     */
    protected $redirectTo = '/file';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //身分驗證
        $this->middleware('auth');
        $this->AES = new AESLib(5);
    }

    /**
     * 下載 [檔案]
     * path = file/course/
     * @param $filename 參數
     * @return string
     */
    public function downFile(Request $request,$urlparam)
    {
        $fileParam   = $filename = '';
        $decode     = SHCSLib::decode($urlparam);
        $param      = $request->sid;
        $showType   = $request->show;

        //2. 檢查查看檔案參數/類型
        list($filecode,$fileid) = $this->getFileParam($decode);


        //3. 找到該檔案
        switch ($param)
        {
            case 'Course':
                $fileParam = et_course::getFile($fileid,$filecode);
                break;
            case 'ContractorLicense':
                $fileParam = b_supply_member_l::getFile($fileid,$filecode);
                break;
            case 'Car':
                $fileParam = b_car::getFile($fileid,$filecode);
                break;
            case 'RPContractorLicense':
                $fileParam = b_supply_rp_member_l::getFile($fileid,$filecode);
                break;
            case 'RPContractorCar':
                $fileParam = b_supply_rp_car::getFile($fileid,$filecode);
                break;
            case 'PermitCheckFile':
                $fileParam = wp_check_kind_f::getFile($fileid,$filecode);
                break;
        }

        //尋找檔案
        if($fileParam)
        {
            //轉換檔案網址
            $filepath = $this->tranUrl($fileParam);
            if($filepath)
            {
                $explodeAry = explode('/',$filepath);
                $filename   = (count($explodeAry))? $explodeAry[count($explodeAry)-1] : '';
            }

            if($showType == 'pdf')
            {
                return Response::make(file_get_contents($filepath), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$filename.'"'
                ]);
            } else {
                return response()->download($filepath, $filename);
            }
        } else {
            return abort(403, Lang::get('sys_base.base_10121'));
        }


    }

    //==================================================================//

    /**
     * 拆解 網址參數
     * @param $filename
     * @return array
     */
    public function getFileParam($filename)
    {
        $filecode = substr($filename,0,1);
        $fileid   = substr($filename,1);

        return [$filecode,$fileid];
    }

    /**
     * 轉換成實際路徑
     * @param $filepath
     * @return string
     */
    public function tranUrl($filePath)
    {
        $defFile = '';

        $filepath = ($filePath)? storage_path('app'.$filePath) : $defFile;

        return ($filepath && File::exists($filepath))? $filepath : '';
    }
}
