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
use App\Model\WorkPermit\wp_check_topic_a;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;
use Excel;

class Rept33Controller extends Controller
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
        $this->hrefMain         = 'report_33';

        $this->pageTitleMain    = '氣體偵測異常事件報表';//大標題
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
		$eid       = $request->eid; //案號
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
            $SQL = "SELECT B.permit_no,B.wp_permit_danger,B.wp_permit_workitem_memo,E.name AS 施工地點,C.project_no,B.sdate,
					A.氣體名稱,CASE WHEN X1 != 'record1' AND A.數值 = '0' THEN '' ELSE 數值 END AS 數值,
                    F.name AS 偵測員,D.name AS supply_name, A.X1
					FROM dbo.View_Gaseous_Anomaly A
					JOIN dbo.wp_work B ON A.wp_work_id=B.id
					JOIN dbo.e_project C ON B.e_project_id=C.id
					JOIN dbo.b_supply D ON B.b_supply_id=D.id
					JOIN dbo.b_factory E ON B.b_factory_id=E.id
					JOIN dbo.b_cust F ON A.record_user=F.id
					WHERE B.sdate BETWEEN :sdate AND :edate AND :project_no IN (C.project_no,'') AND :factory IN (E.id,'')
					";

            $gasNameArr = array(
                'record1' => '氧氣',
                'record2' => '可燃性氣體',
                'record3' => '一氧化碳',
                'record4' => '硫化氫',
                'record5' => '其他'
            );

            $gasUnitArr = array(
                'record1' => '%',
                'record2' => '%',
                'record3' => 'ppm',
                'record4' => 'ppm',
            );
            $listAry = DB::select($SQL, ['sdate' => $sdate, 'edate' => $edate, 'project_no' => $eid, 'factory' => $aid]);
            //Excel
            if ($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['許可證單號','危險等級','工作內容','施工地點','工程案號','承攬商','施工日期','氣體種類','數值','偵測人員'];
                foreach ($listAry as $value)
                {
                    if($value->X1 == 'record1' && $value->數值 == 20.9) { // 若氧氣數值 20.9 不算異常
                        continue;
                    }

                    if(key_exists($value->X1, $gasUnitArr)){
                        $parseValue = $this->parseGasValue($value->數值, $gasUnitArr[$value->X1]);
                        if(SHCSLib::isPermitCheckOverLimit($value->X1, $parseValue)){
                            continue;
                        }
                    }
                    
                    $tmp    = [];
					$tmp[]  = $value->permit_no;
                    $tmp[]  = $value->wp_permit_danger;
					$tmp[]  = $value->wp_permit_workitem_memo;
					$tmp[]  = $value->施工地點;
					$tmp[]  = $value->project_no;
                    $tmp[]  = $value->supply_name;
                    $tmp[]  = $value->sdate;
                    $tmp[]  = empty($value->氣體名稱) ? $gasNameArr[$value->X1] : $value->氣體名稱;
                    $tmp[]  = $value->數值;
					$tmp[]  = $value->偵測員;
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
        $html.= $form->select('aid',$storeAry,$aid,2,'廠區');
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
        $heads[] = ['title'=>'許可證單號'];
        $heads[] = ['title'=>'危險等級'];
		$heads[] = ['title'=>'工作內容'];
		$heads[] = ['title'=>'施工地點'];
		$heads[] = ['title'=>'工程案號'];
        $heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'施工日期'];
		$heads[] = ['title'=>'氣體種類'];
		$heads[] = ['title'=>'數值'];
		$heads[] = ['title'=>'偵測人員'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                if($value->X1 == 'record1' && $value->數值 == 20.9) { // 若氧氣數值 20.9 不算異常
                    continue;
                }

                if(key_exists($value->X1, $gasUnitArr)){
                    $parseValue = $this->parseGasValue($value->數值, $gasUnitArr[$value->X1]);
                    if(SHCSLib::isPermitCheckOverLimit($value->X1, $parseValue)){
                        continue;
                    }
                }

                $no++;
                $rept1           = $value->permit_no;
                $rept2           = $value->wp_permit_danger;
				$rept3           = $value->wp_permit_workitem_memo;
				$rept4           = $value->施工地點;
				$rept5           = $value->project_no;
                $rept6           = $value->supply_name;
				$rept7           = $value->sdate;
				$rept8           = empty($value->氣體名稱) ? $gasNameArr[$value->X1] : $value->氣體名稱;
				$rept9           = $value->數值;
				$rept10          = $value->偵測員;

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
            if(isset($tBody)) $table->addBody($tBody);
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

    /**
     * 解析數值 去除單位
     * @param int $value
     * @param string $unit
     * @return int|mixed|string
     */
    protected function parseGasValue($value = 0, $unit = '')
    {
        if(!$unit) return $value;
        $valueAry   = explode($unit, $value);
        $count      = count($valueAry);
        if($count == 2 && $valueAry[1] == ''){
            $ret = $valueAry[0];
        }else{
            $ret = $value;
        }

        return $ret;
    }
}
