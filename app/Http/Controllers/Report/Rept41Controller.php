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

class Rept41Controller extends Controller
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
        $this->hrefMain         = 'report_41';

        $this->pageTitleMain    = '假日開立工作許可證施工清單';//大標題
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
        $aid       = $request->aid; //廠區
		$eid       = $request->eid=''; //案號
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $eid = '';
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
            $SQL = "SELECT B.name AS 轄區部門,'施工地點：'+F.name AS 施工地點,b_factory_memo AS 工作內容,A.sdate,C.name AS 承攬商,
							D.name+'/'+E.name AS 部門監造, mobile1 AS 手機,A.wp_permit_danger AS 危險等級 FROM dbo.wp_work A
					JOIN dbo.be_dept B ON A.be_dept_id1=B.id
					JOIN dbo.b_supply C ON A.b_supply_id=C.id
					JOIN dbo.be_dept D ON A.be_dept_id2=D.id
					JOIN dbo.b_cust E ON A.proejct_charge=E.id
					JOIN dbo.b_factory_b F ON A.b_factory_b_id=F.id
					JOIN dbo.b_cust_a G ON A.proejct_charge=G.b_cust_id
					--WHERE isHoliday='Y'
					";
					
			

			
            $listAry = DB::select($SQL);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['轄區部門','工作內容','施工日期與時間','承攬商','監造部門/監造人員姓名/手機','危險作業等級','出勤主管姓名與手機','消防車警戒'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->轄區部門;
					$tmp[]  = $value->施工地點;
                    $tmp[]  = $value->工作內容;
					$tmp[]  = $value->sdate;
					$tmp[]  = $value->承攬商;
					$tmp[]  = $value->部門監造;
					$tmp[]  = $value->手機;
					$tmp[]  = $value->危險等級;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '門禁卡片逾期未還清單_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->date('sdate',$sdate,2,'日期');
        //$html.= $form->select('aid',$storeAry,$aid,2,'廠區');
		//$html.= $form->text('eid',$eid,2,'案號'); 


        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $form->addRowCnt($html);
        //至少一個搜尋條件
        $html = HtmlLib::Color('說明：請至少一個搜尋條件(廠區＆承商)','red',1);
        $form->addRow($html);
      
		
		//$html = '統計人數：'.count($listAry);
		//$form->addRow($html,8,0);
		$form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'轄區部門'];
        $heads[] = ['title'=>'工作內容'];
		$heads[] = ['title'=>'施工日期與時間'];
		$heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'監造部門/監造人員姓名/手機'];
		$heads[] = ['title'=>'危險作業等級'];
		$heads[] = ['title'=>'出勤主管姓名與手機'];
		$heads[] = ['title'=>'消防車警戒'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->轄區部門;
                $rept2  		 = $value->施工地點;
				$rept3  		 = $value->工作內容;
				$rept4  		 = $value->sdate;
				$rept5  		 = $value->承攬商;
				$rept6  		 = $value->部門監造;
				$rept7  		 = $value->手機;
				$rept8  		 = $value->危險等級;

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2.'<br>'.$rept3],
							'3'=>[ 'name'=> $rept4],
							'4'=>[ 'name'=> $rept5],
							'5'=>[ 'name'=> $rept6'<br>'.$rept7],
							'6'=>[ 'name'=> $rept8],
							
							
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
