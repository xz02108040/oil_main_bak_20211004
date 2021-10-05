<?php

namespace App\Http\Controllers;

use App\Lib\AESLib;
use App\Lib\SHCSLib;
use App\Lib\TokenLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Factory\b_car;
use App\Model\Supply\b_supply_member_l;
use App\Model\Supply\b_supply_rp_car;
use App\Model\Supply\b_supply_rp_member;
use App\Model\Supply\b_supply_rp_member_l;
use App\Model\User;
use App\Model\WorkPermit\wp_work_img;
use App\Model\WorkPermit\wp_work_topic_a;
use Illuminate\Http\Request;
use DB;
use File;
use Image;
use Storage;
use Response;
use Auth;

class ImgController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Imgae Show Controller
    |--------------------------------------------------------------------------
    |
    | 顯示特定圖片
    |
    | @time 2017/08/06
    |
    */


    /**
     * Router.
     *
     * @var string
     */
    protected $redirectTo = '/img';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //身分驗證
        //$this->middleware('auth');
        $this->AES = new AESLib(5);
        $this->defaultImgPath1 = public_path('images/photo_null.png');
        $this->defaultImgPath2 = public_path('images/stop-icon.png');
    }

    /**
     * 顯示 會員頭像
     * path = img/custhead
     * @param $filename 參數
     * @return string
     */
    public function showUserHeadImg(Request $request,$filename)
    {
        $filepath = $this->defaultImgPath1;
        $size     = $request->size;
        //2. 檢查查看圖片參數/類型
        if($isAuth = $this->isAuth($request->key))
        {
            $fileid = SHCSLib::decode($filename);
            //尋找圖片
            if($imgPath = b_cust_a::getHeadImg($fileid))
            {
                //顯示圖片
                $filepath = $this->showImg($imgPath);
            }
        }

        if($size > 0)
        {
            return Image::make($filepath)->resize($size,null,function ($constraint) {
                $constraint->aspectRatio();
            })->response('jpg','75');
        } else {
            return Image::make($filepath)->response('jpg','75');
        }
    }

    /**
     * 顯示 會員電子簽
     * path = img/custhead
     * @param $filename 參數
     * @return string
     */
    public function showUserSignImg(Request $request,$filename)
    {
        $filepath = $this->defaultImgPath1;
        //2. 檢查查看圖片參數/類型
        if($isAuth = $this->isAuth($request->key))
        {
            $fileid = SHCSLib::decode($filename);
            //尋找圖片
            if($imgPath = User::getSignImg($fileid))
            {
                //顯示圖片
                $filepath = $this->showImg($imgPath);
            }
        }

        return Image::make($filepath)->response('jpg','75');
    }

    /**
     * 顯示 承攬商申請成員的頭像
     * path = img/custhead
     * @param $filename 參數
     * @return string
     */
    public function showUserRPHeadImg(Request $request,$filename)
    {
        $filepath = $this->defaultImgPath1;

        //2. 檢查查看圖片參數/類型
        if($isAuth = $this->isAuth($request->key))
        {
            $fileid = SHCSLib::decode($filename);
            //尋找圖片
            if($imgPath = b_supply_rp_member::getHeadImg($fileid))
            {
                //顯示圖片
                $filepath = $this->showImg($imgPath);
            }
        }

        return Image::make($filepath)->response('jpg','75');
    }

    /**
     * 顯示 門禁刷卡拍攝照片
     * path = img/custhead
     * @param $filename 參數
     * @return string
     */
    public function showDoorImg(Request $request,$filename)
    {
        $filepath = $this->defaultImgPath1;
        $size     = $request->size;
        $type     = $request->has('type')? $request->type : 'M';
        $db       = $type == 'C' ? 'log_door_car_inout' : 'log_door_inout';
        //2. 檢查查看圖片參數/類型
        if($isAuth = $this->isAuth($request->key))
        {
            $fileid = SHCSLib::decode($filename);
            //尋找圖片
            $data = DB::table($db)->where('id',$fileid)->select('img_path')->first();
            if($imgPath = (isset($data->img_path)? $data->img_path : ''))
            {
                //顯示圖片
                $filepath = $this->showImg($imgPath);
            }
        }

        if($size > 0)
        {
            return Image::make($filepath)->resize($size,null,function ($constraint) {
                $constraint->aspectRatio();
            })->response('jpg','75');
        } else {
            return Image::make($filepath)->response('jpg','75');
        }
    }

    /**
     * 顯示 車輛照片
     * path = img/custhead
     * @param $filename 參數
     * @return string
     */
    public function showCarImg(Request $request,$filename)
    {
        $filepath = $this->defaultImgPath1;
        $size     = $request->size;
        //2. 檢查查看圖片參數/類型
        if($isAuth = $this->isAuth($request->key))
        {
            $fileid  = SHCSLib::decode($filename);
            $paramid = in_array($request->sid,['A','B','C','D'])? $request->sid : 'X';
            //尋找圖片
            $imgPath = b_car::getImg($fileid,$paramid);
            if($imgPath)
            {
                //顯示圖片
                $filepath = $this->showImg($imgPath);
                $fileAry  = explode('.',$filepath);
                $ext      = $fileAry[count($fileAry)-1];
                $isPDF    = (!in_array(strtoupper($ext),['JPG','JPEG','PNG','GIF']))? 1 : 0;
            }
        }

        if($isPDF)
        {
            return response()->file($filepath);
        } else {
            if($size > 0)
            {
                return Image::make($filepath)->resize($size,null,function ($constraint) {
                    $constraint->aspectRatio();
                })->response('jpg','75');
            } else {
                return Image::make($filepath)->response('jpg','75');
            }
        }
    }
    /**
     * 顯示 申請車輛照片
     * path = img/custhead
     * @param $filename 參數
     * @return string
     */
    public function showRpCarImg(Request $request,$filename)
    {
        $filepath = $this->defaultImgPath1;
        $size     = $request->size;
        //2. 檢查查看圖片參數/類型
        if($isAuth = $this->isAuth($request->key))
        {
            $fileid  = SHCSLib::decode($filename);
            $paramid = in_array($request->sid,['A','B','C','D'])? $request->sid : 'X';
            //尋找圖片
            $imgPath = b_supply_rp_car::getImg($fileid,$paramid);
            if($imgPath)
            {
                //顯示圖片
                $filepath = $this->showImg($imgPath);
                $fileAry  = explode('.',$filepath);
                $ext      = $fileAry[count($fileAry)-1];
                $isPDF    = (!in_array(strtoupper($ext),['JPG','JPEG','PNG','GIF']))? 1 : 0;
            }
        }

        if($isPDF)
        {
            return response()->file($filepath);
        } else {
            if($size > 0)
            {
                return Image::make($filepath)->resize($size,null,function ($constraint) {
                    $constraint->aspectRatio();
                })->response('jpg','75');
            } else {
                return Image::make($filepath)->response('jpg','75');
            }
        }
    }

    /**
     * 顯示 證照照片
     * path = img/custhead
     * @param $filename 參數
     * @return string
     */
    public function showLicenseImg(Request $request,$filename)
    {
        $isPDF    = 0;
        $filepath = $this->defaultImgPath1;
        //2. 檢查查看圖片參數/類型
        if($isAuth = $this->isAuth($request->key))
        {
            $fileid  = SHCSLib::decode($filename);
            $paramid = in_array($request->sid,['A','B','C'])? $request->sid : 'X';
            //尋找圖片
            $imgPath = b_supply_member_l::getFile($fileid,$paramid);
            if($imgPath)
            {
                //顯示圖片
                $filepath = $this->showImg($imgPath);
                $fileAry  = explode('.',$filepath);
                $ext      = $fileAry[count($fileAry)-1];
                $isPDF    = (!in_array(strtoupper($ext),['JPG','JPEG','PNG','GIF']))? 1 : 0;
            }
        }

        return $isPDF? response()->file($filepath) : Image::make($filepath)->response('jpg','75');
    }

    /**
     * 顯示 證照照片_申請
     * path = img/custhead
     * @param $filename 參數
     * @return string
     */
    public function showRpLicenseImg(Request $request,$filename)
    {
        $isPDF    = 0;
        $filepath = $this->defaultImgPath1;
        //2. 檢查查看圖片參數/類型
        if($isAuth = $this->isAuth($request->key))
        {
            $fileid  = SHCSLib::decode($filename);
            $paramid = in_array($request->sid,['A','B','C'])? $request->sid : 'X';
            //尋找圖片
            $imgPath = b_supply_rp_member_l::getFile($fileid,$paramid);
            if($imgPath)
            {
                //顯示圖片
                $filepath = $this->showImg($imgPath);
                $fileAry  = explode('.',$filepath);
                $ext      = $fileAry[count($fileAry)-1];
                $isPDF    = (!in_array(strtoupper($ext),['JPG','JPEG','PNG','GIF']))? 1 : 0;
            }
//            dd($isAuth,$imgPath,$fileid);
        }

        return $isPDF? response()->file($filepath) : Image::make($filepath)->response('jpg','75');
    }

    /**
     * 顯示 工作許可證
     * path = img/custhead
     * @param $filename 參數
     * @return string
     */
    public function showPermitImg(Request $request,$filename)
    {
        $filepath = $this->defaultImgPath1;
        $size = $request->size ? $request->size : 500;
        //2. 檢查查看圖片參數/類型
        if($isAuth = $this->isAuth($request->key))
        {
            $fileid = SHCSLib::decode($filename);
            //尋找圖片
            $imgPath = wp_work_img::getImg($fileid);
            if($imgPath)
            {
                //顯示圖片
                $filepath = $this->showImg($imgPath);
            }
        }
        if($size > 0)
        {
            return Image::make($filepath)->resize($size,null,function ($constraint) {
                $constraint->aspectRatio();
            })->response('jpg','75');
        } else {
            return Image::make($filepath)->response('jpg','75');
        }
    }


    /**
     * 顯示 工作許可證_題目作答
     * path = img/custhead
     * @param $filename 參數
     * @return string
     */
    public function showPermitImg2(Request $request,$filename)
    {
        $filepath = $this->defaultImgPath1;
        //2. 檢查查看圖片參數/類型
        if($isAuth = $this->isAuth($request->key))
        {
            $fileid = SHCSLib::decode($filename);
            //尋找圖片
            $imgPath = wp_work_topic_a::getImg($fileid);
            if($imgPath)
            {
                //顯示圖片
                $filepath = $this->showImg($imgPath);
            }
        }
        if($request->size > 0)
        {
            return (string)Image::make($filepath)->resize($request->size,null,function ($constraint) {
                $constraint->aspectRatio();
            })->encode('data-url');
        } else {
            return (string)Image::make($filepath)->encode('data-url');
        }
    }

    //==================================================================//

    /**
     * 拆解 網址參數
     * @param $filename
     * @return array
     */
    public function getImgParam($filename)
    {
        $filename = base64_decode($filename);
        $filename = $this->AES->decode($filename);
        $filecode = substr($filename,0,1);
        $fileid   = substr($filename,1);

        return [$filecode,$fileid];
    }

    /**
     * 顯示圖片
     * @param $filepath
     * @return string
     */
    public function showImg($imgPath,$isImg = 1)
    {
        $defFile = ($isImg)? $this->defaultImgPath1 : '';

        $filepath = ($imgPath)? storage_path('app'.$imgPath) : $defFile;
        return ($filepath && File::exists($filepath))? $filepath : '';
    }

    /**
     * 認證確認
     * @param $key
     * @return int
     */
    public function isAuth($key)
    {
        $isAuth = 0;
        return 1;
        if (Auth::check()) {
            $isAuth = 1;
        } elseif($key) {
            $isAuth = TokenLib::isApiKey('app',$key);
        }
        return $isAuth;
    }
}
