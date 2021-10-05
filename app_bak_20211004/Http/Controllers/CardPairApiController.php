<?php

namespace App\Http\Controllers;

use App\Lib\AESLib;
use App\Lib\CheckLib;
use Illuminate\Http\Request;
use Lang;
use Storage;
use Uuid;
use Session;

class CardPairApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CardPairApiController
    |--------------------------------------------------------------------------
    |
    | 負責接收 配卡Api 傳遞資料
    |
    | @time 2020/06/23
    |
    */


    /**
     * Router.
     *
     * @var string
     */
    protected $redirectTo = '/paircardapi';


    /**
     * 建構子
     */
    public function __construct()
    {
        //解碼專用
        $this->ASE = new AESLib();
        //參數
        $this->receiveType      = 1;  //傳送格式：0:否,1:是
        $this->isTest           = 1;  //測試模式：0:否,1:是
        $this->minLenLimit      = 10; //接收長度限制
        $this->errno            = 0;  //錯誤判斷
        $this->clientIp         = ''; //來源端ＩＰ
        $this->responseType     = 1;  //1:JSON 2:JSON+加密
        $this->time_start       = microtime(true);

        //存放資料夾位置
        $this->receivePath        = config('mycfg.api_receive_path2').date('Y/m/d/H/i/s/');//接收資料夾
        $this->replyPath          = config('mycfg.api_reply_path2').date('Y/m/d/H/i/s/');//傳送資料夾
    }

    /**
     * 處理ＡＰＩ接收/回覆
     * @param Request $request
     */
    public function index(Request $request)
    {
        //if(!$request->isMethod('post')) return '';
        //來源ＩＰ
        $this->clientIp  = $request->ip();
        //傳送格式
        if($this->receiveType)
        {
            //POST 接收
            $postInput = file_get_contents("php://input");
//            dd($postInput);
            $this->getJosnRequest($postInput);
        } else {
            $this->getPostRequest($request);
        }
    }

    /**
     * ＪＳＯＮ
     */
    public function getJosnRequest($postInput)
    {
        //如果非空值,且長度大於 Ｘ
        if(strlen($postInput) > $this->minLenLimit)
        {
            //儲存接收紀錄
            //Storage::put($this->receivePath.Uuid::generate(1), $postInput);
            //解碼(測試：不用解碼)
            $jsonData =  ($this->isTest)? $postInput : $this->ASE->decode(($postInput));
            //擷取『{}』內的資料
            $jsonData = substr($jsonData,0,strrpos($jsonData,'}')+1);
            //儲存接收紀錄
            Storage::put($this->receivePath.Uuid::generate(1), $jsonData);
            //判斷是否為ＪＳＯＮ
            if(CheckLib::isJSON($jsonData))
            {
                //解析ＪＳＯＮ
                $this->resolver($jsonData);
            } else {
                //錯誤：非ＪＳＯＮ格式
                $this->errorReply('E00002',$postInput,$jsonData);
            }
        } else {
            //儲存接收紀錄
            //if($this->isTest) Storage::put($this->receivePath.Uuid::generate(1), $postInput);
            //錯誤：沒有資料/資料長度不夠
            //$this->errorReply();
            //2019-11-09
            //只回傳200
            return response()->json(['success' => 'success'], 200);
        }
    }

    /**
     * POST
     */
    public function getPostRequest($request)
    {
        $op     = $request->has('op')?      $request->op : '';      //FUNCTION
        $uid    = $request->has('uid')?     $request->uid : '';     //ＩＰ
        $pwd    = $request->has('pwd')?     $request->pwd : '';     //識別碼
        $id     = $request->has('id')?      $request->id : '';     //識別碼
        $time   = $request->has('time')?    $request->time : '';     //識別碼
        $mode   = $request->has('mode')?    $request->mode : '';     //識別碼
        $n      = $request->has('n')?       $request->n : '';     //識別碼
        $place  = $request->has('place')?   $request->place : '';     //識別碼
        $idcode = $request->has('idcode')?  $request->idcode : '';     //識別碼
        $ischg  = $request->has('ischg')?   $request->ischg : '';     //識別碼
        $isdoor = $request->has('isdoor')?  $request->isdoor : '';     //識別碼
        //$img    = ($request->has('img') && is_string($request->img)) ? $request->img : '';     //識別碼
        $img    = ($request->has('file')) ? $request->file : ($request->has('img')? $request->img : '');     //識別碼
        //dd([$request->all(),$op,$uid,$pwd]);

        if(!$op || !$uid || !$pwd)
        {
            //2019-11-09
            //只回傳200
            return response()->json(['success' => 'success'], 200);
            //錯誤：沒有資料/資料長度不夠
//            $this->errorReply();

        } else {
           $JsonAry = [];
            $JsonAry['funcode'] = $op;
            $JsonAry['uid']     = $uid;
            $JsonAry['pwd']     = $pwd;
            $JsonAry['id']      = $id;
            $JsonAry['time']    = $time;
            $JsonAry['mode']    = $mode;
            $JsonAry['n']       = $n;
            $JsonAry['place']   = $place;
            $JsonAry['img']     = $img;
            $JsonAry['idcode']  = $idcode;
            $JsonAry['ischg']   = $ischg;
            $JsonAry['isdoor']  = $isdoor;
            $jsonData = json_encode($JsonAry);
            //儲存接收紀錄
            Storage::put($this->receivePath.Uuid::generate(1), $jsonData);
            //dd($jsonData);
            //解析ＪＳＯＮ
            $this->resolver($jsonData);
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
        $className = 'App\\API\\PairCard\\' . $funcode;
        $classPath = app_path().'/API/PairCard/' . $funcode . '.php';
        //3. 確認「格式代碼」 是否存在
        if(file_exists($classPath))
        {
            //使用class物件
            $controller =  new $className($jsonObj,$this->clientIp);
            //取得回傳格式
            $ret = $controller->toShow();

        }else {
            //錯誤：相對應格式不存在
            $this->errorReply('E00003',$jsonData,$classPath);
        }
        //4. 儲存回傳紀錄
        Storage::put($this->replyPath.Uuid::generate(1), json_encode($ret,JSON_UNESCAPED_UNICODE));

        //5. 顯示 加密後 回傳資料
        $this->response($ret);
    }

    /**
     * 回覆加密後的訊息
     */
    public function response($message = '')
    {
        if(!$message) return response()->json([]);

        if($this->responseType == 2)
        {
            echo$this->ASE->encode($message);
            exit;
        } else {
            if(is_array($message)) $message = json_encode($message,JSON_UNESCAPED_UNICODE);
            //echo (strlen($message)).chr(13).chr(10).($message);
            echo ($message);
            exit;
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
        $errMsg = Lang::get('sys_api.'.$code);
        if(!$errMsg) Lang::get('sys_api.E00000');//如果沒有相關錯誤代碼

        //回傳格式（錯誤：「代碼」「訊息」）
        $ret = ['err'=> ['code'=>$code , 'msg'=> $errMsg]];

        //如果是測試模式，顯示接收到內容，與解碼後內容
        if($this->isTest && (!$postInput || !$jsonData) )
        {
            $ret = $ret + ['receive'=> $postInput,'decode'=> str_replace('"','',$jsonData)];
        }
        //print_r($ret);

        //回傳錯誤訊息，中斷
        $this->response($ret);
    }
}
