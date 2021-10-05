<?php

namespace App\Http\Controllers;

use App\Lib\AESLib;
use App\Lib\CheckLib;
use App\Model\b_app_version;
use Illuminate\Http\Request;
use Lang;
use Storage;
use Uuid;
use Session;
use \Curl\Curl;

class CurlController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | App Api Controller
    |--------------------------------------------------------------------------
    |
    | 負責接收 App Api 傳遞資料
    |
    | @time 2019/03/07
    |
    */


    /**
     * Router.
     *
     * @var string
     */
    protected $redirectTo = '/doorcurl';


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
        $this->isTest      = 1;  //測試模式：0:否,1:是
        $this->minLenLimit = 10; //接收長度限制
        $this->errno       = 0;
        $this->clientIp    = '';
        $this->time_start = microtime(true);

        //存放資料夾位置
        $this->receivePath        = config('mycfg.api_receive_path').date('Y/m/d/H/i/s/');//接收資料夾
        $this->replyPath          = config('mycfg.api_reply_path').date('Y/m/d/H/i/s/');//傳送資料夾
    }

    /**
     * 處理ＡＰＩ接收/回覆
     * @param Request $request
     */
    public function index(Request $request)
    {
        if(!$request->isMethod('post')) return '';
        //來源ＩＰ
        $this->clientIp  = $request->ip();
        //POST 接收
        $postInput = file_get_contents("php://input");

        //如果非空值,且長度大於 Ｘ
        if(strlen($postInput) > $this->minLenLimit)
        {
            //CURL start
            $curl = new Curl();
            //關閉憑證檢核
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
            $url = 'https://shcs1.chuangfu168.com.tw/app';
            $curl->post($url, $postInput);
            $ret = $curl->response;
            $curl->close();
            echo $ret;
            exit;
        } else {
            //儲存接收紀錄
            //Storage::put($this->receivePath.Uuid::generate(1), $postInput);
            //錯誤：沒有資料/資料長度不夠
            $this->errorReply();
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
        if(!isset($jsonObj->funcode)) $this->errorReply('E00003','',$jsonData);
        //1-1. 取得格式代碼
        $funcode = $jsonObj->funcode;

        //2. 取得class 的 namespace && path
        $className = 'App\\Module\\APP\\' . $funcode;
        $classPath = app_path().'/Module/APP/' . $funcode . '.php';
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
            $this->errorReply('E00009',$jsonData,$classPath);
        }

        //4. 儲存回傳紀錄
        Storage::put($this->replyPath.Uuid::generate(1), $ret);

        //5. 顯示 加密後 回傳資料
        $this->response($ret);
    }

    /**
     * 回覆加密後的訊息
     */
    public function response($message = '', $type = 1)
    {
        if(!$message) return '';

        if($type && !is_array($message))
        {
            //1. 回傳訊息，中斷
            echo $this->ASE->encode($message);
            exit;
        } else {
            //2. 回覆json
            return response()->json($message);
        }
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
        $errMsg = Lang::get('api.'.$code);
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
        $this->response(json_encode($ret));
    }
}
