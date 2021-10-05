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

class Rept21Controller extends Controller
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
        $this->hrefMain         = 'report_21';

        $this->pageTitleMain    = '工安查核表';//大標題
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
        $aid       = $request->aid; // 工作許可證編號
        $bid       = $request->bid; // 工程案號
        $cid       = $request->cid; // 工安查核人員
        $did       = $request->did; // 違規內容

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $did = '';
            Session::forget($this->hrefMain.'.search');
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate','');
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate','');
        } else {
            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate; //如果結束日期 小於開始日期
            Session::put($this->hrefMain.'.search.edate',$edate);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid','');
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid','');
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if (!$cid) {
            $cid = Session::get($this->hrefMain . '.search.cid', '');
        } else {
            Session::put($this->hrefMain . '.search.cid', $cid);
        }
        if (!$did) {
            $did = Session::get($this->hrefMain . '.search.did', '');
        } else {
            Session::put($this->hrefMain . '.search.did', $did);
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

            // 日期區間查詢 sql
            $dateSql = "";
            if (!empty($sdate) && !empty($edate)) {
                $dateSql = " AND A.apply_stamp BETWEEN '$sdate' AND '$edate 23:59:59' ";
            } else if (!empty($sdate)) {
                $dateSql = " AND A.apply_stamp >= '$sdate' ";
            } else if (!empty($edate)) {
                $dateSql = " AND A.apply_stamp <= '$edate 23:59:59' ";
            }
            
            // 完整查詢 sql
            $SQL = "SELECT D.project_no,D.name AS project_name,F.name AS supply_name,B.name AS username,B.account,
					convert(varchar,A.apply_stamp,120) AS apply_stamp,A.violation_record1,C.name AS apply_user, E.permit_no
					FROM e_violation_contractor A
					JOIN dbo.b_cust B ON A.b_cust_id=B.id
					JOIN dbo.b_cust C ON A.apply_user=C.id
					JOIN dbo.e_project D ON A.e_project_id=D.id
                    LEFT JOIN dbo.wp_work E ON A.wp_work_id=E.id
                    JOIN dbo.b_supply F ON A.b_supply_id=F.id
                    WHERE :aid IN (E.permit_no, '') AND :bid IN (D.project_no,'') AND :cid IN (C.name, '') AND :did IN (A.violation_record1, '')
                    $dateSql
					";
					
            $listAry = DB::select($SQL, ['aid'=>$aid,'bid'=>$bid,'cid'=>$cid,'did'=>$did]);
            
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['工程案號','工程案名','工作許可證編號','承攬商','成員姓名','身分證','違規時間','違規事項','查核人員'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->project_no;
                    $tmp[]  = $value->project_name;
                    $tmp[]  = $value->permit_no;
					$tmp[]  = $value->supply_name;
					$tmp[]  = $value->username;
					$tmp[]  = substr($value->account,0,3) . '*****' . substr($value->account,-2);
					$tmp[]  = $value->apply_stamp;
					$tmp[]  = $value->violation_record1;
					$tmp[]  = $value->apply_user;
					
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

        $html = $form->date('sdate',$sdate,2,'開始日期');
        $html.= $form->date('edate',$edate,2,'結束日期');
        $form->addRowCnt($html);

        $html = $form->text('aid',$aid,2,'工作許可證編號');
        $html.= $form->text('bid',$bid,2,'工程案號');
        $html.= $form->text('cid',$cid,2,'工安查核人員');
        $html.= $form->text('did',$did,2,'違規內容');
        $form->addRowCnt($html);

        $html = $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋

        $form->addRow($html,12,10);
	
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
        $heads[] = ['title'=>'工程案名'];
        $heads[] = ['title'=>'工作許可證編號'];
		$heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'成員姓名'];
		$heads[] = ['title'=>'身分證'];
		$heads[] = ['title'=>'違規時間'];
		$heads[] = ['title'=>'違規事項'];
		$heads[] = ['title'=>'查核人員'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->project_no;
                $rept2           = $value->project_name;
                $rept3           = $value->permit_no;
				$rept4           = $value->supply_name;
				$rept5           = $value->username;
				$rept6           = substr($value->account,0,3) . '*****' . substr($value->account,-2);
				$rept7           = $value->apply_stamp;
				$rept8           = $value->violation_record1;
				$rept9           = $value->apply_user;
                
				
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
