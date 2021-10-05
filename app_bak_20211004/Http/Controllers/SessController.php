<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;

class SessController extends Controller
{
    /**
     * 建構子
     */
    public function __construct()
    {
        //$this->middleware('token');
    }

    /**
     * 顯示Session
     */
    public function index(Request $request)
    {
        //保護機制
        //if($request->has('show') && $request->show == 'httc168@show')
        {
            dd(Session::all());
        }
    }
}
