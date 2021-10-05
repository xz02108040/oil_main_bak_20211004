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
use App\Model\Factory\b_factory_d;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;
use Excel;

class Rept32Controller extends Controller
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
        $this->hrefMain         = 'report_32';

        $this->pageTitleMain    = '轄區部門巡邏會簽統計';//大標題
        $this->pageTitleList    = '';//列表


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
		$bfactory = b_factory_d::getSelect();//門別陣列
        $aproc     = SHCSLib::getCode('RP_SUPPLY_CAR_APROC',0);

        $sdate     = $request->sdate;
        $edate     = $request->edate;
		$eid       = $request->eid; // 轄區
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $eid = '';
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
        if(!$eid)
        {
            $eid = Session::get($this->hrefMain.'.search.eid','');
        } else {
            Session::put($this->hrefMain.'.search.eid',$eid);
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
            $SQL = "SELECT A.name, B.工作許可證數量, ISNULL(C.測氣次數, 0) 測氣次數, ISNULL(D.巡邏會簽次數, 0) 巡邏會簽次數 FROM be_dept A
                    JOIN (SELECT A.be_dept_id1, COUNT(*) 工作許可證數量 FROM wp_work A
                        WHERE A.sdate BETWEEN '$sdate' AND '$edate'
                        GROUP BY A.be_dept_id1) B ON B.be_dept_id1=A.id
                    LEFT JOIN (SELECT D.id,COUNT(*) 測氣次數 FROM wp_work_check_record1 A -- wp_work_check_record1 為測氣紀錄
                        JOIN dbo.wp_work B ON A.wp_work_id=B.id 
                        JOIN dbo.b_cust_e C ON A.record_user=C.b_cust_id
                        JOIN dbo.be_dept D ON C.be_dept_id=D.id
                        WHERE  A.wp_check_id=4 AND A.record_stamp BETWEEN '$sdate' AND '$edate 23:59:59' -- wp_check_id=4 (轄區測氣) wp_check_id=3 (承覽商測氣)
                        GROUP BY  D.id) C ON C.id=A.id
                    JOIN (SELECT D.id, COUNT(*) 巡邏會簽次數 FROM wp_work_check_topic A -- wp_work_check_topic 為檢點單資料
                        JOIN dbo.wp_work B ON A.wp_work_id=B.id 
                        JOIN dbo.b_cust_e C ON A.record_user=C.b_cust_id
                        JOIN dbo.be_dept D ON C.be_dept_id=D.id
                        WHERE A.wp_check_id=2 AND A.record_stamp BETWEEN '$sdate' AND '$edate 23:59:59' -- wp_check_id=2 (轄區巡邏)
                        GROUP BY  D.id) D ON D.id=A.id";
            // 註：在施工階段，轄區巡邏和測氣是兩種功能，依據 APP 按的按鈕分別記錄，故也有可能只有巡邏沒有測氣，或只有測氣沒有按巡邏也有可能
            if (!empty($eid)) { // 轄區
                $SQL .= " WHERE A.name LIKE '%$eid%' ";
            }
            $listAry = DB::select($SQL);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['轄區','工作許可證數量','巡邏會簽次數','測氣次數'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->name;
                    $tmp[]  = $value->工作許可證數量;
                    $tmp[]  = $value->巡邏會簽次數;
                    $tmp[]  = empty($value->測氣次數) ? '0' : $value->測氣次數;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '範例_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->date('sdate',$sdate,2,'開始日期');
        $html.= $form->date('edate',$edate,2,'結束日期');
        // $html.= $form->select('aid',$storeAry,$aid,2,'廠區');
		$html.= $form->text('eid',$eid,2,'轄區');
        $form->addRowCnt($html);

        $html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $html.= '</div>';
        $form->addRowCnt($html);
      
		//$html = '統計人數：'.count($listAry);
		//$form->addRow($html,8,0);
		$form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'轄區'];
        $heads[] = ['title'=>'工作許可證數量'];
        $heads[] = ['title'=>'巡邏會簽次數'];
        $heads[] = ['title'=>'測氣次數'];
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->name;
                $rept2           = $value->工作許可證數量;
                $rept3           = $value->巡邏會簽次數;
                $rept4           = empty($value->測氣次數) ? '0' : $value->測氣次數;

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
                            '3'=>[ 'name'=> $rept3],
                            '4'=>[ 'name'=> $rept4],
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
