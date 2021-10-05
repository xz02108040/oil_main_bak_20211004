<?php

namespace App\Http\Controllers\Report;

use App\Exports\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;
use Excel;

class ReptExampleController extends Controller
{
    use SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ReptExampleController
    |--------------------------------------------------------------------------
    |
    | [報表]報表名稱
    |
    */

    /**
     * 環境參數
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     */
    public function __construct()
    {
        //身分驗證
        $this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'report_example';

        $this->pageTitleMain    = '大標題';//大標題
        $this->pageTitleList    = '列表標題';//列表


    }
    /**
     * 首頁內容
     *
     * @return void
     */
    public function index(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $out = $js ='';
        $no        = 0;
        $today     = date('Y-m-d');
        $supplyAry = b_supply::getSelect();  //承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
        $aproc     = SHCSLib::getCode('RP_SUPPLY_CAR_APROC');

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = '';
            Session::forget($this->hrefMain.'.search');
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate',$today);
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate',$today);
        } else {
            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate; //如果結束日期 小於開始日期
            Session::put($this->hrefMain.'.search.edate',$edate);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid',0);
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid',0);
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if(!$cid)
        {
            $cid = Session::get($this->hrefMain.'.search.cid','');
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(1)
//        if(!$aid || !$bid)
        {
            $SQL = "select * from b_cust where isClose = 'N' AND id = :t ";
            if($aid)
            {
                $SQL .= " AND id = '".$aid."'";
            }
            if($bid)
            {
                $SQL .= " AND id = '".$bid."'";
            }
            if($cid)
            {
                $SQL .= " AND id = '".$cid."'";
            }

            $listAry = DB::select($SQL,['t'=>2100001143]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['廠區','承攬商','工程案件','承商人員','進出狀態','進出時間','進出結果'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
                    $tmp[]  = $value->id;
                    $tmp[]  = $value->name;
                    $tmp[]  = $value->bc_type;
                    $tmp[]  = $value->b_menu_group_id;
                    $tmp[]  = $value->isLogin;
                    $tmp[]  = $value->pusher_id;
                    $tmp[]  = $value->sign_img;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '範例_'.date('Ymdhis').'.ods');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->date('sdate',$sdate,1,'開始日期');
        $html.= $form->date('edate',$edate,1,'結束日期');
        $form->addRowCnt($html);

        $html = $form->select('aid',$storeAry,$aid,2,'廠區'); //下拉選擇
        $html.= $form->select('bid',$supplyAry,$bid,2,'承攬商');
        $html.= $form->text('cid',$cid,2,'姓名'); //文字input
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $form->addRowCnt($html);
        //至少一個搜尋條件
        $html = HtmlLib::Color('說明：請至少一個搜尋條件(廠區＆承商)','red',1);
        $form->addRow($html);
        $form->addHr();
        //統計人數
        $html = '統計人數：'.count($listAry);
        $form->addRow($html,8,0);
        $form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'廠區']; //廠區
        $heads[] = ['title'=>'承攬商']; //承攬商
        $heads[] = ['title'=>'工程案件']; //工程案件
        $heads[] = ['title'=>'承商人員']; //承商人員
        $heads[] = ['title'=>'進出狀態']; //進出狀態
        $heads[] = ['title'=>'進出時間']; //進出時間
        $heads[] = ['title'=>'進出結果']; //進出結果

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->id;
                $rept2           = $value->name;
                $rept3           = $value->bc_type;
                $rept4           = $value->b_menu_group_id;
                $rept5           = $value->isLogin;
                $rept6           = $value->pusher_id;
                $rept7           = $value->sign_img;

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
                            '3'=>[ 'name'=> $rept3],
                            '4'=>[ 'name'=> $rept4],
                            '5'=>[ 'name'=> $rept5],
                            '6'=>[ 'name'=> $rept6],
                            '7'=>[ 'name'=> $rept7],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $out .= $table->output();
        unset($table);



        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTile,$out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table1").DataTable({
                        "language": {
                        "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
                    }
                    });
                    $("#sdate,#edate").datepicker({
                        format: "yyyy-mm-dd",
                        startDate: "today",
                        language: "zh-TW"
                    });
             });';

        $css = '
                
             
            ';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js,'css'=>$css];

        return view('index',$retArray);
    }

}
