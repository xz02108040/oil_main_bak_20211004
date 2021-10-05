<?php
namespace App\Http\Traits;

use App\Lib\AESLib;
use App\Lib\CheckLib;
use \Curl\Curl;
use Uuid;
use Storage;
/**
 *
 */
trait CurlTraits {

    /**
     * 指定
     */
    public function curl($url,$data)
    {
        $ret = '';
        if(!$url || !$data) return $ret;

        //AES
        $aes = new AESLib(); //加密物件

        //JSON
        $json = $aes->encode(json_encode($data));
//        dd($json,$data);

        //CURL start
        $curl = new Curl();
        //關閉憑證檢核
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 1);
        $curl->post($url, $json);

        if ($curl->error) {
            $ret = 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage ;

        } else {
            $ret = $curl->response;

            //如果回傳是 ＪＳＯＮ格式
            if(CheckLib::isJSON($ret))
            {
                $json     = json_decode($ret);//解析ＪＳＯＮ
                $jsonData = $aes->decode($json->d,$json->i);//ＪＳＯＮ物件
                $ret      = substr($jsonData,0,strrpos($jsonData,'}')+1);//取的真正回傳內容
                $ret      = (CheckLib::isJSON($ret))? json_decode($ret) : '';//解析ＪＳＯＮ
//                $this->isTokenFailure($ret);
            }
        }
        $curl->close();

        return $ret;
    }

    /**
     * 回傳Token失效，則刪除session
     * @param $ret
     * @return string
     */
    public function isTokenFailure($ret)
    {
        if(!is_object($ret)) return '';
        //如果出現Token 失效，則強制登出
        if(isset($ret->err) && isset($ret->err->code) && $ret->err->code == 'E00005')
        {
            \Session::forget('user');
        }
    }
}
