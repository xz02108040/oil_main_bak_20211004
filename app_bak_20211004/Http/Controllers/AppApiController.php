<?php

namespace App\Http\Controllers;

use App\Lib\AESLib;
use App\Lib\CheckLib;
use App\Model\b_app_version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Webpatser\Uuid\Uuid;
use Session;

class AppApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | AppApiController
    |--------------------------------------------------------------------------
    |
    | 負責接收 App Api 傳遞資料
    |
    | @time 2019/05/10
    |
    */


    /**
     * Router.
     *
     * @var string
     */
    protected $redirectTo = '/httcapi';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //解碼專用
        $this->ASE = new AESLib();
        //參數
        $this->isTest      = 0;  //測試模式：0:否,1:是
        $this->minLenLimit = 10; //接收長度限制
        $this->errno       = 0;
        $this->clientIp    = '';
        $this->time_start  = microtime(true);

        //存放資料夾位置
        $this->receivePath        = config('mycfg.app_receive_path').date('Y/m/d/H/i/s/');//接收資料夾
        $this->replyPath          = config('mycfg.app_reply_path').date('Y/m/d/H/i/s/');//傳送資料夾
    }

    /**
     * 處理ＡＰＩ接收/回覆
     * @param Request $request
     */
    public function index(Request $request)
    {
        $isPost  = true;
        $context = '';
        if(!$request->isMethod('post')) $isPost = false;
        //來源ＩＰ
        $this->clientIp  = $request->ip();
//        dd($request->all());

        if($isPost)
        {
            //POST 接收
            $postInput = file_get_contents("php://input");
            //如果非空值,且長度大於 Ｘ
            if(strlen($postInput) > $this->minLenLimit)
            {
                //儲存接收紀錄
//                Storage::put($this->receivePath.Uuid::generate(1), $postInput);
                //解碼
                $jsonData =  $postInput;//$this->ASE->decode(($postInput)); //2020-08-12 不在需要加密
                //擷取『{}』內的資料
                $jsonData = substr($jsonData,0,strrpos($jsonData,'}')+1);
                //儲存接收紀錄
                Storage::put($this->receivePath.Uuid::generate(1), $jsonData);
                //判斷是否為ＪＳＯＮ
                if(CheckLib::isJSON($jsonData))
                {
                    //解析ＪＳＯＮ
                    $context = $this->resolver($jsonData);
                } else {
                    //錯誤：非ＪＳＯＮ格式
                    $context = $this->errorReply('E00002',$postInput,$jsonData);
                }
            }
        }

        if(is_array($context))
        {
            return response()->json($context)->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', '*')
                ->header('Access-Control-Allow-Headers', 'Origin, Methods, Content-Type, Authorization')
                ->header('Access-Control-Allow-Credentials', true);
        } else {
            return response($context,200)->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', '*')
                ->header('Access-Control-Allow-Headers', 'Origin, Methods, Content-Type, Authorization')
                ->header('Access-Control-Allow-Credentials', true);
        }
    }


    /**
     * 解析ＪＳＯＮ
     */
    public function resolver($jsonData)
    {
        $ret = array();
        $jsonObj = json_decode($jsonData);//JSON解碼

        //1. 如果沒有指定FUNCODE 視為格式不正確
        if(!isset($jsonObj->funcode))
        {
            $ret = $this->errorReply('E00003','',$jsonData);
        } else {
            //1-1. 取得格式代碼
            $funcode = $jsonObj->funcode;

            //2. 取得class 的 namespace && path
            $className = 'App\\API\\APP\\' . $funcode;
            $classPath = app_path().'/API/APP/' . $funcode . '.php';
            //3. 確認「格式代碼」 是否存在
            if(file_exists($classPath))
            {
                //使用class物件
                $controller =  new $className($jsonObj,$this->clientIp);
                if(isset($jsonObj->version))
                {
                    if( b_app_version::chkVersion($jsonObj->version) )
                    {
                        //取得回傳格式
                        $ret = $controller->toShow();
                    } else {
                        //錯誤：版本過舊，請更新ＡＰＰ版本
                        $this->errorReply('E00005','',$classPath);
                    }
                } else {
                    //取得回傳格式
                    $ret = $controller->toShow();
                }

            }else {
                //錯誤：非ＪＳＯＮ格式
                $ret = $this->errorReply('E00009',$jsonData,$classPath);
            }
        }


        //4. 儲存回傳紀錄
        Storage::put($this->replyPath.Uuid::generate(1), json_encode($ret));
        //5. 顯示 加密後 回傳資料
        //$this->response($ret);
        return $ret;
    }



    /**
     * 錯誤訊息
     * @param $code int 錯誤代碼，預設為E00001
     * @param $postInput string 接收到內容
     * @param $jsonData string 解碼後內容
     */
    public function errorReply($code = 'E00001', $postInput = '', $jsonData = '')
    {
        //取得錯誤代碼
        $errMsg = Lang::get('sys_api.'.$code);
        if(!$errMsg) return 'X';//如果沒有相關錯誤代碼

        //回傳格式（錯誤：「代碼」「訊息」）
        $ret = ['err'=> ['code'=>$code , 'msg'=> $errMsg]];

        //如果是測試模式，顯示接收到內容，與解碼後內容
        if($this->isTest && (!$postInput || !$jsonData) )
        {
            $ret = $ret + ['receive'=> $postInput,'decode'=> str_replace('"','',$jsonData)];
        }
        //print_r($ret);

        //回傳錯誤訊息，中斷
        return $ret;
    }
}
