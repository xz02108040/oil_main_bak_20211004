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

class Rept1Controller extends Controller
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
        $this->hrefMain         = 'report_1';

        $this->pageTitleMain    = '承攬商人員進出紀錄';//大標題
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
        $tomonths  = date('Y-m');
		$toyears   = date('Y');
        $listAry   = [];
        $months    = $request->months;
		$years     = $request->years;
        $supplyAry = b_supply::getSelect();  //承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
		$bfactory = b_factory_d::getSelect();//門別陣列
        $aproc     = SHCSLib::getCode('DOOR_INOUT_TYPE2',0);
        $datemenu    =array('0'=>'請選擇','1'=>'日期區間','2'=>'年度月份','3'=>'年度');

        $sdate     = $request->sdate;
        $edate     = $request->edate;
		$months    = $request->months;
        $years     = $request->years;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //門別
		$did       = $request->did; //統編
		$eid       = $request->eid; //案號
        $fid       = $request->fid; //姓名
		$gid       = $request->gid; //身分證
        $hid       = $request->hid; //日期選單
        $iid       = $request->iid; //承商名稱
		
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $months = $years = $aid = $bid = $cid = $did = $eid = $fid = $gid = $hid = $iid = '';
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
        if(!$months)
        {
            $months = Session::get($this->hrefMain.'.search.months',$tomonths);
        } else {
            Session::put($this->hrefMain.'.search.months',$months);
        }
		if(!$years)
        {
            $years = Session::get($this->hrefMain.'.search.years',$toyears);
        } else {
            Session::put($this->hrefMain.'.search.years',$years);
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
		if(!$did)
        {
            $did = Session::get($this->hrefMain.'.search.did','');
        } else {
            Session::put($this->hrefMain.'.search.did',$did);
        }
		if(!$eid)
        {
            $eid = Session::get($this->hrefMain.'.search.eid','');
        } else {
            Session::put($this->hrefMain.'.search.eid',$eid);
        }
		if(!$fid)
        {
            $fid = Session::get($this->hrefMain.'.search.fid','');
        } else {
            Session::put($this->hrefMain.'.search.fid',$fid);
        }
		if(!$gid)
        {
            $gid = Session::get($this->hrefMain.'.search.gid','');
        } else {
            Session::put($this->hrefMain.'.search.gid',$gid);
        }
        if(!$hid)
        {
            $hid = Session::get($this->hrefMain.'.search.hid',1);
        } else {
            Session::put($this->hrefMain.'.search.hid',$hid);
        }
        if(!$iid)
        {
            $iid = Session::get($this->hrefMain.'.search.iid','');
        } else {
            Session::put($this->hrefMain.'.search.iid',$iid);
        }

        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        // if(1)
        if($aid || $bid)
        {
            // $SQL = "SELECT A.id,A.rfid_code,B.name AS USERNAME,A.job_kind,B.account,A.door_date,
			// 		MIN(convert(varchar(19),A.door_stamp,120)) AS door_stamp, A.door_type,A.door_memo,A.door_result,E.name AS factory_d,
			// 		D.sub_name,D.project_no ,D.name AS project_name,A.img_path
			// 		FROM dbo.log_door_inout A 
			// 		JOIN dbo.b_cust B ON A.b_cust_id=B.id
			// 		JOIN (SELECT DISTINCT A.id,A.project_no,A.name,B.b_supply_id,C.name AS supply_name,C.sub_name,B.b_cust_id FROM dbo.e_project A JOIN dbo.b_supply_member B
			// 		ON B.b_supply_id = A.b_supply_id JOIN dbo.b_supply C ON A.b_supply_id=C.id 
			// 		WHERE :tax_num IN (C.tax_num,'') AND :supply_id IN (A.b_supply_id,'') AND :project_no IN (A.project_no,'')
			// 		) D ON B.id=D.b_cust_id AND A.e_project_id=D.id
			// 		JOIN dbo.b_factory_d E ON A.b_factory_d_id=E.id
			// 		WHERE A.door_type='1' AND :account IN (B.account,'') AND :username IN (B.name,'') AND :factory IN (A.b_factory_id,'') $query
			// 		AND :factory_d_id IN (A.b_factory_d_id,'') AND A.door_date BETWEEN :sdate AND :edate
			// 		AND A.err_code=0 AND A.door_type IN (1,2) 
			// 		GROUP BY A.id,A.rfid_code,B.name,A.job_kind,B.account,
			// 				A.door_date,A.door_type,A.door_memo,A.door_result,E.name,
			// 				D.sub_name,D.project_no,D.name,A.img_path
			// 		UNION
			// 		SELECT A.id,A.rfid_code,B.name AS USERNAME,A.job_kind,B.account,A.door_date,
			// 		MAX(convert(varchar(19),A.door_stamp,120)) AS door_stamp, A.door_type,A.door_memo,A.door_result,E.name AS factory_d,
			// 		D.sub_name,D.project_no ,D.name AS project_name,A.img_path
			// 		FROM dbo.log_door_inout A 
			// 		JOIN dbo.b_cust B ON A.b_cust_id=B.id
			// 		JOIN (SELECT DISTINCT A.id,A.project_no,A.name,B.b_supply_id,C.name AS supply_name,C.sub_name,B.b_cust_id FROM dbo.e_project A JOIN dbo.b_supply_member B
			// 		ON B.b_supply_id = A.b_supply_id JOIN dbo.b_supply C ON A.b_supply_id=C.id 
			// 		WHERE :tax_num1 IN (C.tax_num,'') AND :supply_id1 IN (A.b_supply_id,'') AND :project_no1 IN (A.project_no,'')
			// 		) D ON B.id=D.b_cust_id AND A.e_project_id=D.id
			// 		JOIN dbo.b_factory_d E ON A.b_factory_d_id=E.id
			// 		WHERE A.door_type='2' AND :account1 IN (B.account,'') AND :username1 IN (B.name,'') AND :factory1 IN (A.b_factory_id,'') $query
			// 		AND :factory_d_id1 IN (A.b_factory_d_id,'') AND A.door_date BETWEEN :sdate1 AND :edate1
			// 		AND A.err_code=0 AND A.door_type IN (1,2) 
			// 		GROUP BY A.id,A.rfid_code,B.name,A.job_kind,B.account,
			// 					A.door_date,A.door_type,A.door_memo,A.door_result,E.name,
			// 					D.sub_name,D.project_no,D.name,A.img_path";

            $query = "";
            if (!empty($iid)) {
                $query .= " AND D.name LIKE '%$iid%' "; 
            }
            if($hid=='1')
			{
				$query .= " AND A.door_date BETWEEN '".$sdate."' AND '".$edate."'";
			}
			else if($hid=='2')
			{
				$query .= " AND convert(varchar(7),A.door_date,120)= '".$months."'";
			}else if($hid='3')
			{
				$query .= " AND DATEPART(YEAR,A.door_date)='".$years."'";
			}
            $SQL = "SELECT A.id, A.rfid_code, B.name AS USERNAME, A.job_kind, B.account, A.door_date, convert(varchar(19),A.door_stamp,120) AS door_stamp,
                        A.door_type, A.door_result, C.name AS factory_d, D.sub_name, E.project_no, E.name AS project_name,A.img_path, F.supply_in_count
            FROM (
                SELECT * -- 承攬商人員 依日別 最早進入紀錄
                FROM dbo.log_door_inout A 
                WHERE A.door_stamp IN (SELECT MIN(A.door_stamp) AS door_stamp
                    FROM dbo.log_door_inout A 
                    WHERE A.door_type = 1
                    GROUP BY A.b_cust_id, A.door_date)
            UNION
                SELECT * -- 承攬商人員 依日別 最晚離開紀錄
                FROM dbo.log_door_inout A 
                WHERE A.door_stamp IN (
                    SELECT MAX(A.door_stamp) AS door_stamp
                    FROM dbo.log_door_inout A 
                    WHERE A.door_type = 2
                    GROUP BY A.b_cust_id, A.door_date)
                ) A
            JOIN dbo.b_cust B ON B.id=A.b_cust_id
            JOIN dbo.b_factory_d C ON C.id=A.b_factory_d_id
            JOIN dbo.b_supply D ON D.id=A.b_supply_id
            JOIN dbo.e_project E ON E.id=A.e_project_id
            JOIN (SELECT A.b_supply_id, A.door_date, COUNT(DISTINCT(A.b_cust_id)) AS supply_in_count FROM dbo.log_door_inout A WHERE A.door_type = 1 GROUP BY A.b_supply_id, A.door_date) F ON F.b_supply_id=A.b_supply_id AND F.door_date=A.door_date -- 承攬商人員入場人數
            WHERE :tax_num IN (D.tax_num, '') AND :project_no IN (E.project_no, '')
            AND :supply_id IN (D.id, '') AND :account IN (B.account, '') AND :username IN (B.name,'')
            AND :factory IN (A.b_factory_id,'') AND :factory_d_id IN (A.b_factory_d_id,'')
            $query
            ";

			//$listAry = DB::select($SQL);
			$listAry = DB::select($SQL,['tax_num'=>$did,'project_no'=>$eid,'supply_id'=>$bid,'account'=>$gid,'username'=>$fid,'factory'=>$aid,'factory_d_id'=>$cid]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['卡號','姓名','工程身分','身分證','進出日期','進出時間','進出狀態','進出結果','進出門別','承攬商','工程案號','工程名稱','承攬商人員入場人數'];
                foreach ($listAry as $value)
                {
                    $tmp            = [];
					$tmp[]          = $value->rfid_code;
                    $tmp[]          = $value->USERNAME;
                    $tmp[]          = $value->job_kind;
                    $tmp[]          = substr($value->account,0,3) . '*****' . substr($value->account,-2);
                    $tmp[]          = $value->door_date;
                    $tmp[]          = $value->door_stamp;
                    $tmp[]          = isset($aproc[$value->door_type])?$aproc[$value->door_type]:'';
                    $tmp[]          = $value->door_result == "Y" ? "允許進出" : "不允許進出";
                    $tmp[]          = $value->factory_d;
				    $tmp[]          = $value->sub_name;
                    $tmp[]          = $value->project_no;
                    $tmp[]          = $value->project_name;
                    $tmp[]          = $value->supply_in_count;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '承攬商人員進出紀錄_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->select('hid',$datemenu,$hid,2,'日期選單');
		$html.= '<div id="dates" style="display:block;">';
        $html.= $form->date('sdate',$sdate,2,'開始日期');
		$html.= $form->date('edate',$edate,2,'結束日期');
		$html.= '</div>';
		$html.= '<div id="monthss" style="display:none;">';
		$html.= $form->date('months',$months,2,'年度月份');
		$html.= '</div>';
		$html.= '<div id="yearss" style="display:none;">';
		$html.= $form->date('years',$years,2,'年度');
		$html.= '</div>';
        $form->addRowCnt($html);

        $html = $form->select('aid', $storeAry, $aid, 2, '廠區'); //下拉選擇
        $html .= $form->select('cid', $bfactory, $cid, 2, '門別');
        $html .= $form->select('bid', $supplyAry, $bid, 2, '承攬商');
        $html .= $form->text('iid', $iid, 2, '承攬商名稱');
        $form->addRowCnt($html);
        $html = $form->text('did',$did,2,'統編'); 
		$html.= $form->text('eid',$eid,2,'案號');
		$html.= $form->text('fid',$fid,2,'姓名');
		$html.= $form->text('gid',$gid,2,'身分證');
        $form->addRowCnt($html);

        $html = $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $form->addRow($html,12,10);
        //至少一個搜尋條件
        $html = HtmlLib::Color('說明：請至少一個搜尋條件(廠區＆承商)','red',1);
        $form->addRow($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
       
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'卡號'];
        $heads[] = ['title'=>'姓名']; 
        $heads[] = ['title'=>'工程身份'];
		$heads[] = ['title'=>'身分證'];
        $heads[] = ['title'=>'進出日期'];
        $heads[] = ['title'=>'進出時間'];
        $heads[] = ['title'=>'進出狀態'];
        $heads[] = ['title'=>'進出結果'];
		$heads[] = ['title'=>'進出門別'];
		$heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'工程案號'];
		$heads[] = ['title'=>'工程名稱'];
		$heads[] = ['title'=>'照片'];
        $heads[] = ['title'=>'承攬商人員入場人數'];

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->rfid_code;
                $rept2           = $value->USERNAME;
                $rept3           = $value->job_kind;
                $rept4           = substr($value->account,0,3) . '*****' . substr($value->account,-2);
                $rept5           = $value->door_date;
                $rept6           = $value->door_stamp;
                $rept7           = isset($aproc[$value->door_type])?$aproc[$value->door_type]:'';
                $rept8           = $value->door_result == "Y" ? "允許進出" : "不允許進出";
                $rept9           = $value->factory_d;
				$rept10           = $value->sub_name;
                $rept11           = $value->project_no;
                $rept12           = $value->project_name;

                $img_url = '';
                if($value->img_path)
                {
                    if(strpos($value->img_path,'http')!==false)
                    {
                        $img_url = $value->img_path;
                    } else {
                        $img_url = '/img/Door/'.SHCSLib::encode($value->id);
                    }
                }
                $rept13          = ($value->img_path)? HtmlLib::btn($img_url,'查看',3,'','','','_blank') : '';
                $rept14           = $value->supply_in_count;

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
							'11'=>[ 'name'=> $rept11],
							'12'=>[ 'name'=> $rept12],
							'13'=>[ 'name'=> $rept13],
                            '14'=>[ 'name'=> $rept14],
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
                    if($("#hid").val()==1)
					{
						$("#dates")[0].style.display= "block";
						$("#monthss")[0].style.display= "none";
						$("#yearss")[0].style.display= "none";
					}
					else if($("#hid").val()==2)
					{
						$("#dates")[0].style.display= "none";
						$("#monthss")[0].style.display= "block";
						$("#yearss")[0].style.display= "none";
					}else if($("#hid").val()==3)
					{
						$("#dates")[0].style.display= "none";
						$("#monthss")[0].style.display= "none";
						$("#yearss")[0].style.display= "block";
					};
					$("#sdate,#edate").datepicker({
						format: "yyyy-mm-dd",
						language: "zh-TW",
					});
					$("#months").datepicker({
						format: "yyyy-mm",
						language: "zh-TW",
						viewMode: "months",
						minViewMode: "months",
					});
					$("#years").datepicker({
						format: " yyyy", 
						viewMode: "years", 
						minViewMode: "years",
						language: "zh-TW"
					});
					$("#hid").change(function(){
						
						if($("#hid").val()==1)
						{
							$("#dates")[0].style.display= "block";
							$("#monthss")[0].style.display= "none";
							$("#yearss")[0].style.display= "none";
						}
						else if($("#hid").val()==2)
						{
							$("#dates")[0].style.display= "none";
							$("#monthss")[0].style.display= "block";
							$("#yearss")[0].style.display= "none";
						}else  if($("#hid").val()==3)
						{
							$("#dates")[0].style.display= "none";
							$("#monthss")[0].style.display= "none";
							$("#yearss")[0].style.display= "block";
						}
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
