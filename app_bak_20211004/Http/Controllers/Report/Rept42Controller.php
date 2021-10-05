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

class Rept42Controller extends Controller
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
        $this->hrefMain         = 'report_42';

        $this->pageTitleMain    = '門禁卡片逾期未還清單';//大標題
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
        $bid       = $request->bid; //承商
		$eid       = $request->eid; //案號
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $eid = $bid = '';
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
            $SQL = "SELECT DISTINCT  B.project_no,B.project_name,B.sub_name,CONVERT(VARCHAR,B.sdate)+'～'+CONVERT(VARCHAR,B.edate) AS 工程期間,
					CONVERT(VARCHAR,A.sdate,23) AS 領卡日,A.rfid_code,DATEDIFF(DAY,B.edate,GETDATE()) AS 天數,B.username,C.name AS 監造,D.name AS deptname
					FROM dbo.view_used_rfid A  
					LEFT JOIN (SELECT Z.id AS e_project_id,Z.project_no,Z.name AS project_name,X.tax_num,
					X.name AS supply_name,X.sub_name,Y.b_cust_id,z.sdate,z.edate,X.id AS b_supply_id,W.name AS username,
					Z.charge_user,Z.charge_dept
					FROM dbo.b_supply X 
					JOIN dbo.e_project_s Y ON X.id=Y.b_supply_id AND X.isClose='N' --AND Y.isClose='N'
					JOIN dbo.e_project Z ON (Y.e_project_id=Z.id  AND Z.aproc IN ('O','X','C') AND Z.isClose='N') OR (Z.isClose='Y')
					JOIN dbo.b_cust W ON Y.b_cust_id=W.id AND W.isClose='N'
					) B ON B.b_cust_id = A.b_cust_id AND A.b_supply_id=B.b_supply_id AND A.e_project_id=B.e_project_id
					JOIN dbo.b_cust C ON B.charge_user=C.id AND C.isClose='N'
					JOIN dbo.be_dept D ON B.charge_dept=D.id AND D.isClose='N'
					WHERE DATEDIFF(DAY,B.edate,GETDATE()) >= 0 AND A.sdate BETWEEN :sdate AND :edate
                    AND :bid IN (B.b_supply_id, '') AND :eid IN (B.project_no, '')
					";

            $listAry = DB::select($SQL, ['sdate' => $sdate, 'edate' => $edate . ' 23:59:59', 'bid'=>$bid, 'eid'=>$eid]);

            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['工程案號','工程名稱','承攬商','工程有效期間','領卡日期','逾期卡號','逾期天數','姓名','監造部門','監造人員'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->project_no;
                    $tmp[]  = $value->project_name;
					$tmp[]  = $value->sub_name;
					$tmp[]  = $value->工程期間;
					$tmp[]  = $value->領卡日;
					$tmp[]  = $value->rfid_code;
					$tmp[]  = $value->天數;
					$tmp[]  = $value->username;
					$tmp[]  = $value->deptname;
					$tmp[]  = $value->監造;
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
        $html.= $form->date('sdate',$sdate,2,'開始日期');
        $html.= $form->date('edate',$edate,2,'結束日期');
        $html.= $form->select('bid',$supplyAry,$bid,2,'承攬商');
		$html.= $form->text('eid',$eid,2,'案號'); 
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
        $heads[] = ['title'=>'工程案號'];
        $heads[] = ['title'=>'工程名稱'];
		$heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'工程有效期間'];
		$heads[] = ['title'=>'領卡日期'];
		$heads[] = ['title'=>'逾期卡號'];
		$heads[] = ['title'=>'逾期天數'];
		$heads[] = ['title'=>'姓名'];
		$heads[] = ['title'=>'監造部門'];
		$heads[] = ['title'=>'監造人員'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->project_no;
                $rept2  		 = $value->project_name;
				$rept3  		 = $value->sub_name;
				$rept4  		 = $value->工程期間;
				$rept5  		 = $value->領卡日;
				$rept6  		 = $value->rfid_code;
				$rept7  		 = $value->天數;
				$rept8  		 = $value->username;
				$rept9  		 = $value->deptname;
				$rept10  		 = $value->監造;

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
							'3'=>[ 'name'=> $rept3],
							'4'=>[ 'name'=> $rept4],
							'5'=>[ 'name'=> $rept5],
							'6'=>[ 'name'=> $rept6],
							'7'=>[ 'name'=> $rept7],
							'8'=>[ 'name'=> $rept8],
							'9'=>[ 'name'=> $rept9],
							'10'=>[ 'name'=> $rept10],
							
							
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
