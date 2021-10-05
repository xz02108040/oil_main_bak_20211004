<?php

namespace App\Http\Middleware;

use App\Lib\SHCSLib;
use App\Lib\TokenLib;
use Closure;
use Session;
use Lang;

class TokenAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $uri = $request->route()->uri;
        $isFind = $isImg = $isFile = 1;
        $baseExtAry = ['/','login','logout','sess','testapi','testsql','Privacy','rept_1','rept_2','rept_3','rept_4','toolapi'];
        $doorExtAry = ['doorapi','httcapi','paircardapi','reptapi','rept_doorinout_t','rept_doorinout_t2','rept_activitylog','guest','pushtest','genBcustPwd'];
        $extUrlAry  = array_merge($baseExtAry ,$doorExtAry);
        if(strlen($uri) > 1)
        {
            $uri = str_replace(['List','Edit','Create','post','/{id}','new_'],['','','','','',''],$uri);//CRUD
            $isFind     = (substr($uri,0,4) == 'find')? 1 : 0;
            $isImg      = (substr($uri,0,4) == 'img/')? 1 : 0;
            $isFile     = (substr($uri,0,4) == 'file')? 1 : 0;
            $isRept     = (substr($uri,0,6) == 'report')? 1 : 0;
            $isRept2    = (substr($uri,0,10) == 'checkorder')? 1 : 0;
        }
        //例外名單
        if(!in_array($uri,$extUrlAry) && (!$isFind && !$isImg && !$isFile && !$isRept && !$isRept2) )
        {
            //1. 判斷是否有無token (已登入)
            $isExistToken = TokenLib::isTokenExist(0, Session::get('user.token'),'web');
            if(!isset($isExistToken->token))
            {
                Session::flush();
                Session::flash('message',  Lang::get('sys_base.base_10137'));
                //2019-08-30 除錯使用
                Session::put('log.tokenAuth.url',$uri);
                Session::put('log.tokenAuth.isFind',$isFind);
                Session::put('log.tokenAuth.isImg',$isImg);
                Session::put('log.tokenAuth.isFile',$isFile);
                Session::put('log.tokenAuth.stamp',date('Y-m-d H:i:s'));
                return redirect('/login');
            }
            //權限
            $authAry = SHCSLib::toArray(Session::get('user.menu_auth',[]));
            //如果權限不存在
            if(!isset($authAry[$uri]))
            {
                //2020-10-25 除錯使用
                Session::put('log.menuauth.url',$uri);
                return redirect('/')->withErrors(\Lang::get('sys_base.base_auth'));
            }
        }

        return $next($request);
    }
}
