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
use App\Model\Engineering\e_project_type;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;
use Excel;

class Rept29Controller extends Controller
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
        $this->hrefMain         = 'report_29';

        $this->pageTitleMain    = '工程案件逾期分析報表';//大標題
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
        $supplyAry = b_supply::getSelect();       //承攬商陣列
        $storeAry  = b_factory::getSelect();      //廠區陣列
		$bfactory = b_factory_d::getSelect();     //門別陣列
		$typeAry  = e_project_type::getSelect();  //案件分類陣列
        $aproc     = SHCSLib::getCode('ENGINEERING_APROC',0);

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //門別
		$did       = $request->did; //統編
		$eid       = $request->eid; //案號

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
            $SQL = "SELECT X.project_no,X.name AS project_name,W.name AS project_type,X.aproc,V.次數,
						   CONVERT(VARCHAR,X.開始日期,120)+'～'+CONVERT(VARCHAR,ISNULL(X.原結束日期,X.新結束日期),120) AS 施工期間,X.新結束日期,
						   DATEDIFF(DAY,ISNULL(X.原結束日期,X.新結束日期),X.新結束日期) AS 差異天數,
						   Y.name AS charge_user,Z.name AS b_supply_id
					FROM (
						SELECT A.id,A.project_no,A.name,A.project_type,A.aproc,A.sdate AS 開始日期,MIN(B.old_edate) AS 原結束日期,
						A.edate AS 新結束日期,A.charge_user,A.b_supply_id
						FROM dbo.e_project A
						JOIN dbo.e_project_history B ON A.id=B.e_project_id
						--WHERE A.sdate BETWEEN :sdate AND :edate OR A.edate BETWEEN :sdate1 AND :edate1
						WHERE :sdate BETWEEN A.sdate AND A.edate OR :edate BETWEEN A.sdate AND A.edate 
						GROUP BY A.id,A.project_no,A.name,A.project_type,A.aproc,
								 A.sdate,A.edate,A.charge_user,A.b_supply_id
					) X JOIN dbo.b_cust Y ON X.charge_user=Y.id
					JOIN dbo.b_supply Z ON X.b_supply_id=Z.id
					JOIN dbo.e_project_type W ON X.project_type=W.id
					JOIN (
					SELECT e_project_id,new_aproc,COUNT(*) AS 次數 FROM e_project_history
					GROUP BY e_project_id,new_aproc
					)  V ON X.id=V.e_project_id AND X.aproc=V.new_aproc
					";
			
            $listAry = DB::select($SQL,['sdate'=>$sdate,'edate'=>$edate]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['工程案號','案件名稱','案件分類','工程進度','次數','工程期間','實際結案日期','差異天數','監造名稱','承攬商'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->project_no;
                    $tmp[]  = $value->project_name;
					$tmp[]  = $value->project_type;
					$tmp[]  = isset($aproc[$value->aproc])?$aproc[$value->aproc]:'';
					$tmp[]  = $value->次數;
					$tmp[]  = $value->施工期間;
					$tmp[]  = $value->新結束日期;
					$tmp[]  = $value->差異天數;
					$tmp[]  = $value->charge_user;
					$tmp[]  = $value->b_supply_id;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '工程案件報表_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->date('sdate',$sdate,2,'工程起');
        $html.= $form->date('edate',$edate,2,'工程訖');
        $form->addRowCnt($html);

        $html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $form->addRowCnt($html);
        //至少一個搜尋條件
        $html = HtmlLib::Color('','red',1);
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
        $heads[] = ['title'=>'工程案號'];
        $heads[] = ['title'=>'案件名稱'];
		$heads[] = ['title'=>'案件分類'];
		$heads[] = ['title'=>'工程進度'];
		$heads[] = ['title'=>'次數'];
		$heads[] = ['title'=>'工程期間'];
		$heads[] = ['title'=>'實際結案日期'];
		$heads[] = ['title'=>'延長天數'];
		$heads[] = ['title'=>'監造名稱'];
		$heads[] = ['title'=>'承攬商'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->project_no;
				$rept2           = $value->project_name;
				$rept3           = $value->project_type;
                $textColor       = $value->aproc == "C" ? "red" : "green";
                $rept4           = isset($aproc[$value->aproc]) ? "<span style='color: $textColor'>" . $aproc[$value->aproc] . "</span>" : '';
				$rept5           = $value->次數;
				$rept6           = $value->施工期間;
				$rept7           = $value->新結束日期;
				$rept8           = $value->差異天數;
				$rept9           = $value->charge_user;
				$rept10          = $value->b_supply_id;

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
