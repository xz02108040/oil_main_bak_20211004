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
use App\Model\Engineering\et_course;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;
use Excel;

class Rept26Controller extends Controller
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
        $this->hrefMain         = 'report_26';

        $this->pageTitleMain    = '承攬商教育訓練快到期報表';//大標題
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
		$etcourse  = et_course::getSelect();
        $aproc     = SHCSLib::getCode('RP_SUPPLY_CAR_APROC',0);

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $aid       = $request->aid; //教育訓練名稱

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
            $SQL = "SELECT * FROM (
                        SELECT B.name AS supplyname,C.name username,D.name coursename,MAX(A.valid_date ) AS valid_date
                        FROM dbo.et_traning_m A
                        JOIN dbo.b_supply B ON A.b_supply_id=B.id
                        JOIN dbo.b_cust C ON A.b_cust_id=C.id
                        JOIN dbo.et_course D ON A.et_course_id=D.id
                        WHERE A.isClose='N' AND b.isClose='N' 
                        AND C.isClose='N' AND :et_course_id IN (D.id,'')
                        AND A.aproc = 'O'
                        GROUP BY B.name,C.name ,D.name 
                    ) AS tab
                    WHERE DATEDIFF(DAY,GETDATE(),tab.valid_date) BETWEEN 0 AND 30 
					";
			
            $listAry = DB::select($SQL,[':et_course_id'=>$aid]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['承攬商名稱','承攬商人員','教育訓練名稱','教育(有效)到期日'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->supplyname;
                    $tmp[]  = $value->username;
					$tmp[]  = $value->coursename;
					$tmp[]  = $value->valid_date;
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
        

        $html = $form->select('aid',$etcourse,$aid,2,'課程'); //下拉選擇
        


        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
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
        $heads[] = ['title'=>'承攬商名稱'];
        $heads[] = ['title'=>'承攬商人員'];
		$heads[] = ['title'=>'教育訓練名稱'];
		$heads[] = ['title'=>'教育(有效)到期日'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->supplyname;
                $rept2           = $value->username;
				$rept3           = $value->coursename;
				$rept4           = $value->valid_date;
				
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
