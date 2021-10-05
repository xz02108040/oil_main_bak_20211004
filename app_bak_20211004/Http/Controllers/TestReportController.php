<?php

namespace App\Http\Controllers;

use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use Lang;
use Request;
use Config;
use Html;
use DB;
use Storage;

class TestReportController extends Controller
{
    use DoorTrait,ReptDoorMenInOutTodayTrait;
    /**
     * 建構子
     */
    public function __construct()
    {

    }

    /**
     * 顯示測試內容
     * @param Request $request
     */
    public function index(Request $request)
    {
        $A1 = rand(100,500);
        $A2 = rand(20,100);
        $A3 = rand(20,100);
        $A4 = rand(30,$A1/2);
        $A5 = rand(10,$A2/2);
        $A6 = rand(10,$A3/2);

        $T1 = $A1 + $A2 + $A3;
        $T2 = $A4 + $A5 + $A6;



        $showAry = [];
        $showAry['repot_title'] = '承攬商門禁刷卡系統';
        $showAry['repot_sub_title'] = '門禁儀錶板';

        $showAry['info_title1'] = '目前廠內總人數:';
        $showAry['info_cnt1']   = $T1;
        $showAry['info_unit1']  = '人';
        $showAry['info_title2'] = '目前場內車輛數:';
        $showAry['info_cnt2']   = $T2;
        $showAry['info_unit2']  = '輛';
        $showAry['info_title3'] = '目前時間:';
        $showAry['info_cnt3']   = date('Y-m-d H:i');

        $showAry['reptcnt'][1]['no']        = 1;
        $showAry['reptcnt'][1]['title']     = '目前廠內<span class="redfont">人數</span>:';
        $showAry['reptcnt'][1]['sub_title'] = '大林廠';
        $showAry['reptcnt'][1]['cnt']       = $A1;
        $showAry['reptcnt'][1]['unit']      = '人';
        $showAry['reptcnt'][1]['btn']       = '查詢更多';
        $showAry['reptcnt'][1]['type']      = 1;

        $showAry['reptcnt'][2]['no']        = 4;
        $showAry['reptcnt'][2]['title']     = '目前廠內<span class="redfont">車輛</span>:';
        $showAry['reptcnt'][2]['sub_title'] = '大林廠';
        $showAry['reptcnt'][2]['cnt']       = $A4;
        $showAry['reptcnt'][2]['unit']      = '輛';
        $showAry['reptcnt'][2]['btn']       = '查詢更多';
        $showAry['reptcnt'][2]['type']      = 2;

        $showAry['reptcnt'][3]['no']        = 2;
        $showAry['reptcnt'][3]['title']     = '目前廠內<span class="redfont">人數</span>:';
        $showAry['reptcnt'][3]['sub_title'] = '烏材林課';
        $showAry['reptcnt'][3]['cnt']       = $A2;
        $showAry['reptcnt'][3]['unit']      = '人';
        $showAry['reptcnt'][3]['btn']       = '查詢更多';
        $showAry['reptcnt'][3]['type']      = 1;

        $showAry['reptcnt'][4]['no']        = 5;
        $showAry['reptcnt'][4]['title']     = '目前廠內<span class="redfont">車輛</span>:';
        $showAry['reptcnt'][4]['sub_title'] = '烏材林課';
        $showAry['reptcnt'][4]['cnt']       = $A5;
        $showAry['reptcnt'][4]['unit']      = '輛';
        $showAry['reptcnt'][4]['btn']       = '查詢更多';
        $showAry['reptcnt'][4]['type']      = 2;

        $showAry['reptcnt'][5]['no']        = 3;
        $showAry['reptcnt'][5]['title']     = '目前廠內<span class="redfont">人數</span>:';
        $showAry['reptcnt'][5]['sub_title'] = '觀音課';
        $showAry['reptcnt'][5]['cnt']       = $A3;
        $showAry['reptcnt'][5]['unit']      = '人';
        $showAry['reptcnt'][5]['btn']       = '查詢更多';
        $showAry['reptcnt'][5]['type']      = 1;

        $showAry['reptcnt'][6]['no']        = 6;
        $showAry['reptcnt'][6]['title']     = '目前廠內<span class="redfont">車輛</span>:';
        $showAry['reptcnt'][6]['sub_title'] = '觀音課';
        $showAry['reptcnt'][6]['cnt']       = $A6;
        $showAry['reptcnt'][6]['unit']      = '輛';
        $showAry['reptcnt'][6]['btn']       = '查詢更多';
        $showAry['reptcnt'][6]['type']      = 2;

        return view('template.doorreport1024_test',$showAry);
    }

    public function page()
    {

        $contents = '<div class="row">
                <div class="col-lg-8 col-lg-offset-2">
                    <div class="wrapper" id="wrapper1">
                      <canvas id="signature-pad" class="signature-pad" height=400 width=600 ></canvas>
                    </div>
                </div><!-- /.col-lg-4 -->
            </div><!-- /.row -->
                   <input id="test" type="submit" value="TEST">
                            ';
        //jsavascript
        $js = '$(document).ready(function() {
                    
                    var wrapper1 = $("#wrapper1");
                    var canvas1 = $("#signature-pad");
                    canvas1.prop("width",wrapper1.width());
                    canvas1.prop("height",wrapper1.height());
                    
                    var canvas  = document.getElementById("signature-pad");
                    var signaturePad = new SignaturePad(canvas, {
                      backgroundColor: "rgb(255, 255, 255)" // necessary for saving image as JPEG; can be removed is only saving as PNG or SVG
                    });
                    
                    $("#test").click(function(){
                        if (signaturePad.isEmpty()) {
                            return alert("Please provide a signature first.");
                        }
  
                        var data = signaturePad.toDataURL("image/png");
                        alert(data);
                        return false;
                    })
                } );
                
                
                
                ';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>'TEST','content'=>$contents,'menu'=>[],'js'=>$js];
        return view('index',$retArray);
    }
}
