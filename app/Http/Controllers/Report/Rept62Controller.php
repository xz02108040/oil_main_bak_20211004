<?php

namespace App\Http\Controllers\Report;

use DB;
use Auth;
use Lang;
use Excel;
use Session;
use App\Lib\LogLib;
use App\Model\User;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Lib\ContentLib;
use App\Exports\ExcelExport;
use Illuminate\Http\Request;
use App\Model\Supply\b_supply;
use App\Http\Traits\SessTraits;
use App\Model\Factory\b_rfid_a;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_d;
use App\Http\Controllers\Controller;
use App\Model\Engineering\et_course;
use App\Model\Supply\b_supply_member;
use App\Http\Traits\Factory\RFIDTrait;
use App\Model\Engineering\e_violation;
use App\Http\Traits\Engineering\TraningMemberTrait;
use App\Http\Traits\Supply\SupplyMemberLicenseTrait;

class Rept62Controller extends Controller
{
    use TraningMemberTrait, SupplyMemberLicenseTrait, RFIDTrait, SessTraits;
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
        $this->hrefMain         = 'report_62';
        $this->hrefMainDetail2  = 'report_62';
        $this->hrefDetail_rfid  = 'report_62_rfid';
        $this->hrefDetail_license = 'report_62_license';
        $this->hrefDetail_traning = 'report_62_traning';
        $this->hrefDetail_log_inout = 'report_62_log_inout';
        $this->hrefDetail_wp_work = 'report_30';
        $this->hrefDetail_violation = 'report_17';
        // $this->hrefDetail_violation = 'report_62_violation';

        $this->pageTitleMain    = '成員歷史紀錄查詢'; //大標題
        $this->pageTitleList    = '成員公司列表'; //主畫面列表
        $this->title_rfid       = Lang::get('sys_rept.rept_501'); //配卡紀錄
        $this->title_license    = Lang::get('sys_supply.supply_53'); //專業證照
        $this->title_traning    = Lang::get('sys_supply.supply_27'); //教育訓練
        $this->title_log_inout  = Lang::get('sys_rept.rept_502'); //進出紀錄
        $this->title_violation  = Lang::get('sys_rept.rept_503'); //違規

        $this->pageBackBtn      = Lang::get('sys_btn.btn_5'); //[按鈕]返回
        $this->Icon             = HtmlLib::genIcon('caret-square-o-right');
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
        $out = $js = '';
        $no        = 0;
        $today     = date('Y-m-d');
        $supplyAry = b_supply::getSelect();  //承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
        $aproc     = SHCSLib::getCode('RP_SUPPLY_CAR_APROC');

        $bc_id     = $request->bc_id; //身分證
        $name      = $request->name;  //姓名
        $b_cust_id = $request->b_cust_id; //帳號ID

        //清除搜尋紀錄
        if ($request->has('clear')) {
            $bc_id  = $name = $b_cust_id = '';
            Session::forget($this->hrefMain . '.search');
        }
        if (!$bc_id) {
            $bc_id = Session::get($this->hrefMain . '.search.bc_id', '');
        } else {
            Session::put($this->hrefMain . '.search.bc_id', $bc_id);
        }
        if (!$name) {
            $name = Session::get($this->hrefMain . '.search.name', '');
        } else {
            Session::put($this->hrefMain . '.search.name', $name);
        }
        if (!$b_cust_id) {
            $b_cust_id = Session::get($this->hrefMain . '.search.b_cust_id', '');
        } else {
            Session::put($this->hrefMain . '.search.b_cust_id', $b_cust_id);
        }
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if (1)
        //        if(!$aid || !$bid)
        {
            $isShow = '1';

            $sWhere = " AND bc_type = '3' ";
            $sWhere2 = "";
            if ($bc_id) {
                $sWhere .= " AND d.account = '" . $bc_id . "'";
            }
            if ($name) {
                $sWhere .= " AND d.name LIKE '%" . $name . "%'";
            }
            $sWhere2 = $sWhere;
            if ($b_cust_id) {
                $sWhere = " AND d.id = '" . $b_cust_id . "'";
                $sWhere2 .= " AND d.b_cust_id = '" . $b_cust_id . "'";
            }
            //搜尋條件為空時，不執行SQL語法
            if (!$bc_id && !$name && !$b_cust_id) {
                $sWhere = " AND 1 = 0 ";
                $isShow = '0';
            }

            $SQL = " SELECT TOP 1 * FROM view_user d WHERE 1 = 1 {$sWhere2} ";
            $InfoAry = DB::select($SQL);

            $SQL2 = "
            SELECT DISTINCT a.b_supply_id,e.name AS supply_name,a.created_at AS sdate,a.close_stamp AS edate,a.resign_memo
            ,c.project_no,c.name AS project_name
            ,STUFF((SELECT DISTINCT ','+name FROM e_project_license b1 JOIN b_supply_engineering_identity b2 ON b1.engineering_identity_id=b2.id WHERE b1.e_project_id=b.e_project_id and b1.b_cust_id = b.b_cust_id and b2.id in ('1','2','9') for xml path('')),1,1,'') AS job_kind_name
            ,STUFF((SELECT DISTINCT ','+name FROM e_project_license b1 JOIN b_supply_engineering_identity b2 ON b1.engineering_identity_id=b2.id WHERE b1.e_project_id=b.e_project_id and b1.b_cust_id = b.b_cust_id and b2.id not in ('1','2','9') for xml path('')),1,1,'') AS job_kind_name2
            ,f.status_val AS isUT_name
            FROM b_supply_member a
            LEFT JOIN e_project_s b ON a.b_cust_id = b.b_cust_id AND a.b_supply_id = b.b_supply_id
            LEFT JOIN e_project c ON b.e_project_id = c.id
            JOIN b_cust d ON a.b_cust_id = d.id
            JOIN b_supply e ON a.b_supply_id = e.id
            LEFT JOIN sys_code f ON b.isUT = f.status_key AND f.status_code = 'UT_KIND'
            WHERE 1 = 1 
            --AND :bc_id IN (d.account,'') AND :name IN (d.name,'')
            {$sWhere}
            ORDER BY a.created_at 
            ";

            $listAry = DB::select($SQL2);
            //        dd(self::getEloquentSqlWithBindings($manUser));

            //Excel
            if ($request->has('download')) {
                $excelReport = [];
                $excelReport[] = ['承攬商', '日期區間', '離職說明', '工程案號', '工程案件', '工程身分', '作業主管', '尿檢'];
                foreach ($listAry as $value) {
                    $tmp    = [];

                    if ($value->b_supply_id != '999') {
                        $tmp[]  = $value->supply_name;
                    } else {
                        $tmp[]  = Lang::get('sys_emp.emp_8'); //離職
                    }

                    $sdate = isset($value->sdate) ? date('Y-m-d', strtotime($value->sdate)) . ' ~ ' : '';
                    $edate = isset($value->edate) ? date('Y-m-d', strtotime($value->edate)) : Lang::get('sys_emp.emp_31'); //任職中
                    $tmp[]  = $sdate . $edate;
                    $tmp[]  = $value->resign_memo;

                    $tmp[]  = $value->project_no;
                    $tmp[]  = $value->project_name;

                    $tmp[]           = $value->job_kind_name;
                    $tmp[]           = $value->job_kind_name2;
                    $tmp[]           = $value->isUT_name;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport', $excelReport);
                return Excel::download(new ExcelExport(), $this->pageTitleMain . '_' . date('Ymdhis') . '.xls');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0, $hrefMain, 'POST', 'form-inline');
        $html = $form->text('bc_id', $bc_id, 2, Lang::get('sys_base.base_10906')); //身分證
        $html .= $form->text('name', $name, 2, Lang::get('sys_base.base_10707')); //姓名
        if ($this->isRoot == 'Y') {
            $html .= $form->text('b_cust_id', $b_cust_id, 2, 'ID'); //帳號ID
        }
        $html .= $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_29'), '3', 'download'); //下載按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear', '', ''); //清除搜尋
        $form->addRowCnt($html);

        //至少一個搜尋條件
        $html = HtmlLib::Color('說明：請至少一個搜尋條件身分證＆姓名', 'red', 1);
        $form->addRow($html);
        $form->addHr();

        //統計人數
        // $html = '統計人數：' . count($listAry);
        // $form->addRow($html, 8, 0);
        // $form->addHr();

        //輸出
        $out .= $form->output(1);

        //--1.個人資訊--//
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title' => 'ID'];
        $heads[] = ['title' => Lang::get('sys_base.base_10707')];  //姓名
        $heads[] = ['title' => Lang::get('sys_base.base_10906')];  //身分證
        $heads[] = ['title' => Lang::get('sys_rept.rept_501')];    //配卡紀錄
        $heads[] = ['title' => Lang::get('sys_supply.supply_53')]; //專業證照
        $heads[] = ['title' => Lang::get('sys_supply.supply_27')]; //教育訓練
        $heads[] = ['title' => Lang::get('sys_rept.rept_502')];    //進出紀錄
        $heads[] = ['title' => Lang::get('sys_rept.rept_315')];    //工單
        $heads[] = ['title' => Lang::get('sys_rept.rept_503')];    //違規

        $table->addHead($heads, 0);
        if (count($InfoAry)) {
            foreach ($InfoAry as $val) {
                $rept1           = $val->b_cust_id;
                $rept2           = $val->name;
                if ($this->isRoot == 'Y') {
                    $rept3           = $val->bc_id;
                }else{
                    $rept3           = SHCSLib::genBCID($val->bc_id);
                }
                $rept4           = HtmlLib::btn(SHCSLib::url($this->hrefDetail_rfid, '', 'id=' . SHCSLib::encode($rept2)), Lang::get('sys_btn.btn_30'), 2); //配卡紀錄按鈕
                $rept5           = HtmlLib::btn(SHCSLib::url($this->hrefDetail_license, '', 'id=' . SHCSLib::encode($rept1)), Lang::get('sys_btn.btn_30'), 3); //專業證照按鈕
                $rept6           = HtmlLib::btn(SHCSLib::url($this->hrefDetail_traning, '', 'id=' . SHCSLib::encode($rept1)), Lang::get('sys_btn.btn_30'), 4); //教育訓練按鈕
                $rept7           = HtmlLib::btn(SHCSLib::url($this->hrefDetail_log_inout, '', 'id=' . SHCSLib::encode($rept1)), Lang::get('sys_btn.btn_30'), 8); //進出紀錄按鈕
                $rept8           = HtmlLib::btn(SHCSLib::url($this->hrefDetail_wp_work, '', 'id=' . SHCSLib::encode($val->bc_id)), Lang::get('sys_btn.btn_30'), 7,'','','','_blank'); //工單按鈕
                $params = json_encode(array('iid' => $val->bc_id, 'sdate' => date('Y-m-01'), 'edate' => date('Y-m-d'), 'months' => date('Y-m'), 'years' => date('Y')));
                $rept9 = HtmlLib::btn(
                    SHCSLib::url($this->hrefDetail_violation, '', 'encode=' . SHCSLib::encode($params)),
                    Lang::get('sys_btn.btn_30'),10,'','','','_blank'); //違規按鈕

                $tBody[] = [
                    '1' => ['name' => $rept1],
                    '2' => ['name' => $rept2],
                    '3' => ['name' => $rept3],
                    '4' => ['name' => $rept4],
                    '5' => ['name' => $rept5],
                    '6' => ['name' => $rept6],
                    '7' => ['name' => $rept7],
                    '8' => ['name' => $rept8],
                    '9' => ['name' => $rept9],
                ];
            }
            $table->addBody($tBody);
        }

        //輸出
        if ($isShow) {
            $out .= $table->output(1);
        }
        unset($table, $tBody);

        //增加空白列，排版用
        $form2 = new FormLib(0, $hrefMain, 'POST', 'form-inline');
        $form2->addHr();
        if ($isShow) {
            $out .= $form2->output(1);
        }

        //--2.資料列表--//
        //table2
        $table2 = new TableLib($hrefMain, 'table2');
        //標題
        $heads = $tBody = [];
        $heads[] = ['title' => 'NO'];
        $heads[] = ['title' => Lang::get('sys_engineering.engineering_93')];  //承攬商
        $heads[] = ['title' => Lang::get('sys_rept.rept_14')];                //日期區間
        $heads[] = ['title' => Lang::get('sys_base.base_60003')];             //離職說明
        $heads[] = ['title' => Lang::get('sys_engineering.engineering_4')];   //工程案號
        $heads[] = ['title' => Lang::get('sys_engineering.engineering_1')];   //工程案件
        $heads[] = ['title' => Lang::get('sys_engineering.engineering_170')]; //工程身分
        $heads[] = ['title' => Lang::get('sys_engineering.engineering_171')]; //作業主管
        $heads[] = ['title' => Lang::get('sys_engineering.engineering_136')]; //尿檢

        $table2->addHead($heads, 0);
        if (count($listAry)) {
            foreach ($listAry as $value) {
                $no++;
                if ($value->b_supply_id != '999') {
                    $rept1           = $value->supply_name;
                } else {
                    $rept1           = HtmlLib::Color(Lang::get('sys_emp.emp_8'), 'red', 1); //離職
                }

                $sdate = isset($value->sdate) ? date('Y-m-d', strtotime($value->sdate)) . ' ~ ' : '';
                $edate = isset($value->edate) ? date('Y-m-d', strtotime($value->edate)) : HtmlLib::Color(Lang::get('sys_emp.emp_31'), 'red', 1); //任職中
                $rept2           = $sdate . $edate;
                $rept8           = $value->resign_memo;

                $rept3           = $value->project_no;
                $rept4           = $value->project_name;

                $rept5           = $value->job_kind_name;
                $rept6           = $value->job_kind_name2;
                $rept7           = $value->isUT_name;

                $tBody[] = [
                    '0' => ['name' => $no, 'b' => 1, 'style' => 'width:5%;'],
                    '1' => ['name' => $rept1],
                    '2' => ['name' => $rept2],
                    '8' => ['name' => $rept8],
                    '3' => ['name' => $rept3],
                    '4' => ['name' => $rept4],
                    '5' => ['name' => $rept5],
                    '6' => ['name' => $rept6],
                    '7' => ['name' => $rept7],
                ];
            }
            $table2->addBody($tBody);
        }
        //輸出
        $out .= $table2->output();
        unset($table2);

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTile, $out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table2").DataTable({
                        "language": {
                        "url": "' . url('/js/' . Lang::get('sys_base.table_lan') . '.json') . '"
                    }
                    });
                    $("#sdate,#edate").datepicker({
                        format: "yyyy-mm-dd",
                        startDate: "today",
                        language: "zh-TW"
                    });
             });';

        $css = '';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title" => $this->pageTitleMain, 'content' => $contents, 'menu' => $this->sys_menu, 'js' => $js, 'css' => $css];

        return view('index', $retArray);
    }

    /**
     * 配卡紀錄
     */
    public function rfid(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $out = $js = $contents = '';
        $no  = 0;
        $name = SHCSLib::decode($request->id);
        $usedAry  = SHCSLib::getCode('RFID_USED', 1);
        $closeAry = SHCSLib::getCode('CLOSE', 1);
        //view元件參數
        $tbTitle  = $this->pageTitleList . $this->Icon . $this->title_rfid; //列表標題
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        //資料內容
        $listAry = $this->getApiRFIDList('', '', 'N', '', $name, '');

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0, $tbTitle, 'POST', 'form-inline');
        $form->addLinkBtn($hrefBack, $btnBack, 1); //返回
        $form->addHr();
        // //搜尋
        // $html = $form->select('isInProject',$isInProjectAry,$isInProject,2,Lang::get('sys_supply.supply_52'));
        // $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        // $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        // $form->addRowCnt($html);
        // $form->addHr();
        // $memo = Lang::get('sys_supply.supply_70');
        // $form->addHtml(HtmlLib::Color($memo,'red',1));
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib('test');
        //標題
        $heads[] = ['title' => 'NO'];
        $heads[] = ['title' => Lang::get('sys_rfid.rfid_11')];     //分類
        $heads[] = ['title' => Lang::get('sys_rfid.rfid_1')];      //卡片編碼
        $heads[] = ['title' => Lang::get('sys_supply.supply_21')]; //身分證
        $heads[] = ['title' => Lang::get('sys_rfid.rfid_2')];      //卡片內容
        $heads[] = ['title' => Lang::get('sys_rfid.rfid_5')];      //開始日期
        $heads[] = ['title' => Lang::get('sys_rfid.rfid_6')];      //結束日期
        $heads[] = ['title' => Lang::get('sys_rfid.rfid_15')];     //使用狀態
        $heads[] = ['title' => Lang::get('sys_rfid.rfid_13')];     //狀態

        $table->addHead($heads, 0);
        if (count($listAry)) {
            foreach ($listAry as $value) {
                $no++;
                $id           = $value->id;
                $name1        = $value->rfid_type_name; //
                $name2        = $value->rfid_code; //
                $name3        = $value->nation_name . ' / ' . SHCSLib::genBCID($value->bc_id); //
                $name4        = $value->name; //
                $name5        = $value->b_rfid_a_id; //
                $name6        = substr($value->sdate, 0, 11);          //
                $name7        = substr($value->edate, 0, 11);          //
                $usedStr      = b_rfid_a::getUsedCnt($name5);
                $usedStr      = ($usedStr) ? ('<span style="font-size: 1.2em">' . $usedStr . '</span>') : '';
                $usedStr1     = isset($usedAry[$value->isUsed]) ? $usedAry[$value->isUsed] : '';
                $usedStr1    .= $value->close_memo ? '  ( ' . $value->close_memo . ' )' : '';
                $isUsedCnt    = $value->isUsed === 'Y' ? $usedStr : $usedStr1; //
                $isUsedColor  = $value->isUsed === 'Y' ? 2 : 5; //顏色
                $isClose      = isset($closeAry[$value->isClose]) ? $closeAry[$value->isClose] : ''; //
                $isCloseColor = $value->isClose === 'Y' ? 5 : 2; //顏色

                $tBody[] = [
                    '0' => ['name' => $no, 'b' => 1, 'style' => 'width:5%;'],
                    '1' => ['name' => $name1],
                    '11' => ['name' => $name4],
                    '3' => ['name' => $name3],
                    '2' => ['name' => $name2],
                    '6' => ['name' => $name6],
                    '7' => ['name' => $name7],
                    '21' => ['name' => $isUsedCnt, 'label' => $isUsedColor],
                    '22' => ['name' => $isClose, 'label' => $isCloseColor],
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
        $content->rowTo($content->box_table($tbTitle, $out, 2));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> Javascript
        //-------------------------------------------//
        $js = '$(document).ready(function() {
            $("#table1").DataTable({
                "language": {
                "url": "' . url('/js/' . Lang::get('sys_base.table_lan') . '.json') . '"
            }
            });
            
        } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title" => $this->pageTitleMain, 'content' => $contents, 'menu' => $this->sys_menu, 'js' => $js];
        return view('index', $retArray);
    }

    /**
     * 專業證照
     *
     * @return void
     */
    public function license(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $no = 0;
        $out = $js = '';
        $mid       = SHCSLib::decode($request->id); //成員帳號ID

        if (!$mid || !is_numeric($mid)) {
            $msg = Lang::get('sys_supply.supply_1002');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param1 = 'mid=' . $request->mid;
            $member = User::getName($mid);
            $isCloseType = User::isClose($mid);
        }
        //view元件參數
        $tbTitle  = $this->pageTitleList . $this->Icon . $this->title_license; //列表標題
        $hrefMain = $this->hrefMain;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiSupplyMemberLicenseList(0, $mid, 0, '');

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0, $hrefMain, 'POST', 'form-inline');
        $form->addLinkBtn($hrefBack, $btnBack, 1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title' => 'NO'];
        $heads[] = ['title' => Lang::get('sys_supply.supply_19')];
        $heads[] = ['title' => Lang::get('sys_supply.supply_123')]; //承攬商
        $heads[] = ['title' => Lang::get('sys_supply.supply_32')];
        $heads[] = ['title' => Lang::get('sys_supply.supply_71')]; //證號
        $heads[] = ['title' => Lang::get('sys_supply.supply_79')]; //發證類型
        $heads[] = ['title' => Lang::get('sys_supply.supply_74')]; //發證日期
        $heads[] = ['title' => Lang::get('sys_supply.supply_33')]; //有效日期
        $heads[] = ['title' => Lang::get('sys_supply.supply_34'), 'style' => 'width:15%;']; //
        $heads[] = ['title' => Lang::get('sys_supply.supply_35'), 'style' => 'width:15%;']; //
        $heads[] = ['title' => Lang::get('sys_supply.supply_36'), 'style' => 'width:15%;']; //

        $table->addHead($heads, 0);
        if (count($listAry)) {
            foreach ($listAry as $value) {
                $no++;
                $id           = $value->id;
                $name1        = $member; ///
                $name7        = b_supply::getName($value->b_supply_id); ///承攬商
                $name2        = $value->license; ///
                $name3        = $value->edate; ///
                $name4        = $value->license_code; ///
                $name5        = $value->sdate; ///
                $name6        = $value->edate_type_name; ///
                $show_name3   = $value->show_name3 ? $value->show_name3 : Lang::get('sys_btn.btn_29');
                $show_name4   = $value->show_name4 ? $value->show_name4 : Lang::get('sys_btn.btn_29');
                $show_name5   = $value->show_name5 ? $value->show_name5 : Lang::get('sys_btn.btn_29');
                $fileLink1    = ($value->filePath1) ? $form->linkbtn($value->filePath1, $show_name3, 4, '', '', '', '_blank') : '';
                $fileLink2    = ($value->filePath2) ? $form->linkbtn($value->filePath2, $show_name4, 4, '', '', '', '_blank') : '';
                $fileLink3    = ($value->filePath3) ? $form->linkbtn($value->filePath3, $show_name5, 4, '', '', '', '_blank') : '';

                $tBody[] = [
                    '0' => ['name' => $no, 'b' => 1, 'style' => 'width:5%;'],
                    '1' => ['name' => $name1],
                    '7' => ['name' => $name7],
                    '2' => ['name' => $name2],
                    '4' => ['name' => $name4],
                    '6' => ['name' => $name6],
                    '5' => ['name' => $name5],
                    '3' => ['name' => $name3],
                    '11' => ['name' => $fileLink1],
                    '12' => ['name' => $fileLink2],
                    '13' => ['name' => $fileLink3],
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
        $content->rowTo($content->box_table($tbTitle, $out, 3));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table1").DataTable({
                        "language": {
                        "url": "' . url('/js/' . Lang::get('sys_base.table_lan') . '.json') . '"
                    }
                    });
                    
                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title" => $this->pageTitleMain, 'content' => $contents, 'menu' => $this->sys_menu, 'js' => $js];
        return view('index', $retArray);
    }

    /**
     * 教育訓練
     *
     * @return void
     */
    public function traning(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $out = $js = $isValid = '';
        $no  = 0;
        $aproc = ['O'];
        $closeAry = SHCSLib::getCode('CLOSE');
        $overAry  = SHCSLib::getCode('DATE_OVER');
        $passAry  = SHCSLib::getCode('PASS', 1);
        unset($passAry['O']);
        unset($passAry['C']);
        $courseAry = et_course::getSelect();
        $bid      = ($request->bid) ? $request->bid : '';
        $cid      = ($request->cid) ? $request->cid : '';
        //成員ＩＤ
        $mid      = SHCSLib::decode($request->id);
        if (!$mid || !is_numeric($mid)) {
            $msg = Lang::get('sys_supply.supply_1002');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param1 = 'mid=' . $request->mid;
            $param2 = '?pid=' . Session::get('sys_supply.pid');
            $member = User::getName($mid);
            $isCloseType = User::isClose($mid);
        }

        //清除
        if ($request->has('clear')) {
            $bid = $cid = '';
            Session::forget($this->hrefMain . '.search');
        }
        if ($cid) {
            Session::put($this->hrefMain . '.search.pid', $cid);
        } else {
            $cid = Session::get($this->hrefMain . '.search.cid', '');
        }
        if ($bid) {
            Session::put($this->hrefMain . '.search.bid', $bid);
        } else {
            $bid = Session::get($this->hrefMain . '.search.bid', '');
        }
        if ($bid == 'Y') {
            $aproc = ['O'];
            $isValid = 'Y';
        }
        if ($bid == 'N') {
            $aproc = ['A', 'P', 'R', 'C'];
        }
        if ($bid == 'O') {
            $aproc = ['O'];
            $isValid = 'N';
        }

        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList . $this->Icon . $this->title_traning; //列表標題
        $hrefMain = $this->hrefMain;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiTraningMemberSelf($mid, $cid, $aproc);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0, $this->hrefDetail_traning, 'POST', 'form-inline');
        $form->addLinkBtn($hrefBack, $btnBack, 1); //返回
        $form->addHr();
        /*暫時不顯示搜尋條件，有需要之後再啟用
        if($mid)
        {
            //
            $html = '';
            $html.= $form->select('cid',$courseAry,$cid,2,Lang::get('sys_engineering.engineering_41'));
            $html.= $form->select('bid',$passAry,$bid,2,Lang::get('sys_engineering.engineering_12'));
            $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
            $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
            $html.= $form->hidden('pid',$request->pid);
            $form->addRowCnt($html);
        }
        */

        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title' => 'NO'];
        $heads[] = ['title' => Lang::get('sys_supply.supply_12')]; //成員
        $heads[] = ['title' => Lang::get('sys_supply.supply_19')]; //成員
        $heads[] = ['title' => Lang::get('sys_supply.supply_27')]; //教育訓練
        $heads[] = ['title' => Lang::get('sys_supply.supply_52')]; //進度
        $heads[] = ['title' => Lang::get('sys_engineering.engineering_80')]; //報名申請
        $heads[] = ['title' => Lang::get('sys_engineering.engineering_82')]; //審查時間
        $heads[] = ['title' => Lang::get('sys_engineering.engineering_103')]; //過期日
        $heads[] = ['title' => Lang::get('sys_engineering.engineering_33')]; //狀態

        $table->addHead($heads, 0);
        if (count($listAry)) {
            foreach ($listAry as $value) {
                $no++;
                $name11       = $value['supply']; //
                $name1        = $value['user']; //
                $name2        = HtmlLib::Color($value['course'], 'blue', 1); //
                $name3        = $value['isOver'] == 'Y' ? HtmlLib::Color($value['aproc_name'], 'red') : $value['aproc_name']; //
                $name4        = $value['apply_date']; //
                $name6        = $value['pass_date']; //
                $name7        = HtmlLib::Color($value['valid_date'], '', 1); //
                if ($value['valid_date']) {
                    $overColor    = $value['isOver'] == 'Y' ? 'red' : 'blue';
                    $dateover     = $value['isOver'] == 'Y' ? 'N' : 'Y';
                    $name7       .= isset($overAry[$dateover]) ? '（' . HtmlLib::Color($overAry[$dateover], $overColor, 1) . '）' : ''; //停用
                }
                $isClose      = isset($closeAry[$value['isClose']]) ? $closeAry[$value['isClose']] : ''; //停用
                $isCloseColor = $value['isClose'] == 'Y' ? 5 : 2; //停用顏色

                $tBody[] = [
                    '0' => ['name' => $no, 'b' => 1, 'style' => 'width:5%;'],
                    '11' => ['name' => $name11],
                    '1' => ['name' => $name1],
                    '2' => ['name' => $name2],
                    '3' => ['name' => $name3],
                    '4' => ['name' => $name4],
                    '6' => ['name' => $name6],
                    '7' => ['name' => $name7],
                    '90' => ['name' => $isClose, 'label' => $isCloseColor],
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
        $content->rowTo($content->box_table($tbTitle, $out, 4));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table1").DataTable({
                        "language": {
                        "url": "' . url('/js/' . Lang::get('sys_base.table_lan') . '.json') . '"
                    }
                    });
                    
                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title" => $this->pageTitleMain, 'content' => $contents, 'menu' => $this->sys_menu, 'js' => $js];
        return view('index', $retArray);
    }

    /**
     * 進出紀錄
     *
     * @return void
     */
    public function log_inout(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $out = $js = '';
        $no        = 0;
        $today     = date('Y-m-d');
        $tomonths  = date('Y-m');
        $toyears   = date('Y');

        $id        = SHCSLib::decode($request->id); //人員ID
        $months    = $request->months;
        $years     = $request->years;
        $supplyAry = b_supply_member::getSupplySelect($id);  //成員的所有承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
        $bfactory  = b_factory_d::getSelect(); //門別陣列
        $aproc     = SHCSLib::getCode('DOOR_INOUT_TYPE2', 0);
        $datemenu    = array('0' => '請選擇', '1' => '日期區間', '2' => '年度月份', '3' => '年度');

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $months    = $request->months;
        $years     = $request->years;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //門別
        $did       = $request->did; //統編
        $eid       = $request->eid; //案號
        // $fid       = $request->fid; //姓名
        // $gid       = $request->gid; //身分證
        $hid       = $request->hid; //日期選單
        $iid       = $request->iid; //承商名稱


        //清除搜尋紀錄
        if ($request->has('clear')) {
            $sdate = $edate = $months = $years = $aid = $bid = $cid = $did = $eid = $hid = $iid = '';
            Session::forget($this->hrefDetail_log_inout . '.search');
        }
        //進出日期
        if (!$sdate) {
            $sdate = Session::get($this->hrefDetail_log_inout . '.search.sdate', $today);
        } else {
            if (strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefDetail_log_inout . '.search.sdate', $sdate);
        }
        if (!$edate) {
            $edate = Session::get($this->hrefDetail_log_inout . '.search.edate', $today);
        } else {
            if (strtotime($edate) < strtotime($sdate)) $edate = $sdate; //如果結束日期 小於開始日期
            Session::put($this->hrefDetail_log_inout . '.search.edate', $edate);
        }
        if (!$months) {
            $months = Session::get($this->hrefDetail_log_inout . '.search.months', $tomonths);
        } else {
            Session::put($this->hrefDetail_log_inout . '.search.months', $months);
        }
        if (!$years) {
            $years = Session::get($this->hrefDetail_log_inout . '.search.years', $toyears);
        } else {
            Session::put($this->hrefDetail_log_inout . '.search.years', $years);
        }
        if (!$aid) {
            $aid = Session::get($this->hrefDetail_log_inout . '.search.aid', 0);
        } else {
            Session::put($this->hrefDetail_log_inout . '.search.aid', $aid);
        }
        if (!$bid) {
            $bid = Session::get($this->hrefDetail_log_inout . '.search.bid', 0);
        } else {
            Session::put($this->hrefDetail_log_inout . '.search.bid', $bid);
        }
        if (!$cid) {
            $cid = Session::get($this->hrefDetail_log_inout . '.search.cid', '');
        } else {
            Session::put($this->hrefDetail_log_inout . '.search.cid', $cid);
        }
        if (!$did) {
            $did = Session::get($this->hrefDetail_log_inout . '.search.did', '');
        } else {
            Session::put($this->hrefDetail_log_inout . '.search.did', $did);
        }
        if (!$eid) {
            $eid = Session::get($this->hrefDetail_log_inout . '.search.eid', '');
        } else {
            Session::put($this->hrefDetail_log_inout . '.search.eid', $eid);
        }
        if (!$hid) {
            $hid = Session::get($this->hrefDetail_log_inout . '.search.hid', 1);
        } else {
            Session::put($this->hrefDetail_log_inout . '.search.hid', $hid);
        }
        if (!$iid) {
            $iid = Session::get($this->hrefDetail_log_inout . '.search.iid', '');
        } else {
            Session::put($this->hrefDetail_log_inout . '.search.iid', $iid);
        }

        //view元件參數

        $tbTile   = $this->pageTitleList . $this->Icon . $this->title_log_inout; //列表標題
        $hrefMain = $this->hrefDetail_log_inout; //路由
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if (1)
        //        if(!$aid || !$bid)
        {
            $query = "";
            if (!empty($iid)) {
                $query .= " AND D.name LIKE '%$iid%' ";
            }
            if ($hid == '1') {
                $query .= " AND A.door_date BETWEEN '" . $sdate . "' AND '" . $edate . "'";
            } else if ($hid == '2') {
                $query .= " AND convert(varchar(7),A.door_date,120)= '" . $months . "'";
            } else if ($hid = '3') {
                $query .= " AND DATEPART(YEAR,A.door_date)='" . $years . "'";
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
            AND :supply_id IN (D.id, '') 
            AND :factory IN (A.b_factory_id,'') AND :factory_d_id IN (A.b_factory_d_id,'')
            AND B.id = {$id}
            $query
            ";

            $listAry = DB::select($SQL, ['tax_num' => $did, 'project_no' => $eid, 'supply_id' => $bid, 'factory' => $aid, 'factory_d_id' => $cid]);
            //Excel
            if ($request->has('download')) {
                $excelReport = [];
                $excelReport[] = ['卡號', '姓名', '工程身分', '身分證', '進出日期', '進出時間', '進出狀態', '進出結果', '進出門別', '承攬商', '工程案號', '工程名稱', '承攬商人員入場人數'];
                foreach ($listAry as $value) {
                    $tmp            = [];
                    $tmp[]          = $value->rfid_code;
                    $tmp[]          = $value->USERNAME;
                    $tmp[]          = $value->job_kind;
                    $tmp[]          = substr($value->account, 0, 3) . '*****' . substr($value->account, -2);
                    $tmp[]          = $value->door_date;
                    $tmp[]          = $value->door_stamp;
                    $tmp[]          = isset($aproc[$value->door_type]) ? $aproc[$value->door_type] : '';
                    $tmp[]          = $value->door_result == "Y" ? "允許進出" : "不允許進出";
                    $tmp[]          = $value->factory_d;
                    $tmp[]          = $value->sub_name;
                    $tmp[]          = $value->project_no;
                    $tmp[]          = $value->project_name;
                    $tmp[]          = $value->supply_in_count;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport', $excelReport);
                return Excel::download(new ExcelExport(), '承攬商人員進出紀錄_' . date('Ymdhis') . '.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0, $this->hrefDetail_log_inout . '?id=' . SHCSLib::encode($id), 'POST', 'form-inline');
        $form->addLinkBtn($hrefBack, $btnBack, 1); //返回
        $form->addHr();
        $html = '';
        $html .= $form->select('hid', $datemenu, $hid, 2, '日期選單');
        $html .= '<div id="dates" style="display:block;">';
        $html .= $form->date('sdate', $sdate, 2, '開始日期');
        $html .= $form->date('edate', $edate, 2, '結束日期');
        $html .= '</div>';
        $html .= '<div id="monthss" style="display:none;">';
        $html .= $form->date('months', $months, 2, '年度月份');
        $html .= '</div>';
        $html .= '<div id="yearss" style="display:none;">';
        $html .= $form->date('years', $years, 2, '年度');
        $html .= '</div>';
        $form->addRowCnt($html);

        $html = $form->select('aid', $storeAry, $aid, 2, '廠區'); //下拉選擇
        $html .= $form->select('cid', $bfactory, $cid, 2, '門別');
        $html .= $form->select('bid', $supplyAry, $bid, 2, '承攬商');
        // $html .= $form->text('iid', $iid, 2, '承攬商名稱');
        $form->addRowCnt($html);
        $html = $form->text('did', $did, 2, '統編');
        $html .= $form->text('eid', $eid, 2, '案號');
        // $html.= $form->text('fid',$fid,2,'姓名');
        // $html.= $form->text('gid',$gid,2,'身分證');
        $form->addRowCnt($html);

        $html = $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search'); //搜尋按鈕
        // $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //下載按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear', '', ''); //清除搜尋
        $form->addRow($html, 12, 10);
        //至少一個搜尋條件
        // $html = HtmlLib::Color('說明：請至少一個搜尋條件(廠區＆承商)','red',1);
        // $form->addRow($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title' => 'NO'];
        $heads[] = ['title' => '卡號'];
        $heads[] = ['title' => '姓名'];
        $heads[] = ['title' => '工程身份'];
        $heads[] = ['title' => '身分證'];
        $heads[] = ['title' => '進出日期'];
        $heads[] = ['title' => '進出時間'];
        $heads[] = ['title' => '進出狀態'];
        $heads[] = ['title' => '進出結果'];
        $heads[] = ['title' => '進出門別'];
        $heads[] = ['title' => '承攬商'];
        $heads[] = ['title' => '工程案號'];
        $heads[] = ['title' => '工程名稱'];
        $heads[] = ['title' => '照片'];
        $heads[] = ['title' => '承攬商人員入場人數'];

        $table->addHead($heads, 0);
        if (count($listAry)) {
            foreach ($listAry as $value) {
                $no++;
                $rept1           = $value->rfid_code;
                $rept2           = $value->USERNAME;
                $rept3           = $value->job_kind;
                $rept4           = substr($value->account, 0, 3) . '*****' . substr($value->account, -2);
                $rept5           = $value->door_date;
                $rept6           = $value->door_stamp;
                $rept7           = isset($aproc[$value->door_type]) ? $aproc[$value->door_type] : '';
                $rept8           = $value->door_result == "Y" ? "允許進出" : "不允許進出";
                $rept9           = $value->factory_d;
                $rept10           = $value->sub_name;
                $rept11           = $value->project_no;
                $rept12           = $value->project_name;

                $img_url = '';
                if ($value->img_path) {
                    if (strpos($value->img_path, 'http') !== false) {
                        $img_url = $value->img_path;
                    } else {
                        $img_url = '/img/Door/' . SHCSLib::encode($value->id);
                    }
                }
                $rept13          = ($value->img_path) ? HtmlLib::btn($img_url, '查看', 3, '', '', '', '_blank') : '';
                $rept14           = $value->supply_in_count;

                $tBody[] = [
                    '0' => ['name' => $no, 'b' => 1, 'style' => 'width:5%;'],
                    '1' => ['name' => $rept1],
                    '2' => ['name' => $rept2],
                    '3' => ['name' => $rept3],
                    '4' => ['name' => $rept4],
                    '5' => ['name' => $rept5],
                    '6' => ['name' => $rept6],
                    '7' => ['name' => $rept7],
                    '8' => ['name' => $rept8],
                    '9' => ['name' => $rept9],
                    '10' => ['name' => $rept10],
                    '11' => ['name' => $rept11],
                    '12' => ['name' => $rept12],
                    '13' => ['name' => $rept13],
                    '14' => ['name' => $rept14],
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
        $content->rowTo($content->box_table($tbTile, $out, 1));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table1").DataTable({
                        "language": {
                        "url": "' . url('/js/' . Lang::get('sys_base.table_lan') . '.json') . '"
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
        $retArray = ["title" => $this->pageTitleMain, 'content' => $contents, 'menu' => $this->sys_menu, 'js' => $js, 'css' => $css];

        return view('index', $retArray);
    }

    /**
     * 違規
     *
     * @return void
     */
    public function violation(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $out = $js ='';
        $no        = 0;
        $monthfirstDay = date('Y-m-01');
        $today     = date('Y-m-d');
        $tomonths  = date('Y-m');
		$toyears   = date('Y');
        $id        = SHCSLib::decode($request->id); //人員ID

        $supplyAry = b_supply_member::getSupplySelect($id);  //承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
		$bfactory = b_factory_d::getSelect();//門別陣列
		$eviolation = e_violation::getSelect(); //違規事項陣列
        $aproc     = SHCSLib::getCode('RP_SUPPLY_CAR_APROC',0);
        $datemenu    =array('0'=>'請選擇','1'=>'日期區間','2'=>'年度月份','3'=>'年度');

        if (isset($request->encode)) {
            $params = json_decode(SHCSLib::decode($request->encode), true);
        }

        $sdate     = isset($params) ? $params['sdate'] : $request->sdate;
        $edate     = isset($params) ? $params['edate'] : $request->edate;
        $months    = isset($params) ? $params['months'] : $request->months;
        $years     = isset($params) ? $params['years'] : $request->years;
        $aid       = $request->aid; //廠區
        $bid       = isset($params) ? $params['supply_id'] : $request->bid; //承商
        $cid       = isset($params) ? $params['project_no'] : $request->cid; //案號
        $did       = isset($params) ? $params['project_name'] : $request->did; //案名
        $eid       = $request->eid; //人員
        $fid       = isset($params) ? $params['violation_id'] : $request->fid; //違規事項
        $gid       = isset($params) ? $params['supply_name'] : $request->gid; //承商名稱
        $hid       = isset($params) ? $params['hid'] : $request->hid; //日期選單
        $iid       = $request->iid; //身分證

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $did = $eid = $fid = $gid = $iid = '';
            Session::forget($this->hrefDetail_violation.'.search');
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefDetail_violation.'.search.sdate',$monthfirstDay);
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefDetail_violation.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefDetail_violation.'.search.edate',$today);
        } else {
            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate; //如果結束日期 小於開始日期
            Session::put($this->hrefDetail_violation.'.search.edate',$edate);
        }
        if(!$months)
        {
            $months = Session::get($this->hrefDetail_violation.'.search.months',$tomonths);
        } else {
            //if(strtotime($months) > strtotime($tomonths)) $months = $tomonths;
			//date('Y-m-d', strtotime('+1 year'));
            Session::put($this->hrefDetail_violation.'.search.months',$months);
        }
		if(!$years)
        {
            $years = Session::get($this->hrefDetail_violation.'.search.years',$toyears);
        } else {
            Session::put($this->hrefDetail_violation.'.search.years',$years);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefDetail_violation.'.search.aid',0);
        } else {
            Session::put($this->hrefDetail_violation.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefDetail_violation.'.search.bid',0);
        } else {
            Session::put($this->hrefDetail_violation.'.search.bid',$bid);
        }
        if(!$cid)
        {
            $cid = Session::get($this->hrefDetail_violation.'.search.cid','');
        } else {
            Session::put($this->hrefDetail_violation.'.search.cid',$cid);
        }
        if(!$did)
        {
            $did = Session::get($this->hrefDetail_violation.'.search.did','');
        } else {
            Session::put($this->hrefDetail_violation.'.search.did',$did);
        }
        if(!$eid)
        {
            $eid = Session::get($this->hrefDetail_violation.'.search.eid','');
        } else {
            Session::put($this->hrefDetail_violation.'.search.eid',$eid);
        }
		if(!$fid)
        {
            $fid = Session::get($this->hrefDetail_violation.'.search.fid','');
        } else {
            Session::put($this->hrefDetail_violation.'.search.fid',$fid);
        }
        if(!$gid)
        {
            $gid = Session::get($this->hrefDetail_violation.'.search.gid','');
        } else {
            Session::put($this->hrefDetail_violation.'.search.gid',$gid);
        }
        if(!$hid)
        {
            $hid = Session::get($this->hrefDetail_violation.'.search.hid',1);
        } else {
            Session::put($this->hrefDetail_violation.'.search.hid',$hid);
        }
        if(!$iid)
        {
            $iid = Session::get($this->hrefDetail_violation.'.search.iid','');
        } else {
            Session::put($this->hrefDetail_violation.'.search.iid',$iid);
        }
        //view元件參數
        $tbTile   = $this->pageTitleList . $this->Icon . $this->title_violation; //列表標題
        $hrefMain = $this->hrefDetail_violation; //路由
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(1)
//        if(!$aid || !$bid)
        {

            // 模糊查詢條件
            $query = "";
            if (!empty($gid)) { // 承攬商名稱
                $query .= " AND C.name LIKE '%$gid%' ";
            }
            if($hid=='1')
			{
				$query .= " AND A.apply_date BETWEEN '".$sdate."' AND '".$edate."'";
			}
			else if($hid=='2')
			{
				$query .= " AND convert(varchar(7),A.apply_date,120)= '".$months."'";
			}else if($hid='3')
			{
				$query .= " AND DATEPART(YEAR,A.apply_date)='".$years."'";
			}
            $SQL = "SELECT B.project_no AS 工程案號,B.name AS 工程名稱,C.name AS 廠商名稱,D.name AS 承攬商人員,D.account AS 身分證,
					A.apply_stamp AS 違規時間, A.violation_record4 AS 違規分類,A.violation_record2 AS 違規法規,
					A.violation_record1 AS 違規事項, A.violation_record3 AS 違規罰則,ISNULL(CONVERT(VARCHAR(10),A.limit_edate,120),'無') 再次入場日期
					FROM e_violation_contractor A
					JOIN dbo.e_project B ON A.e_project_id=B.id
					JOIN dbo.b_supply C ON A.b_supply_id=C.id
					JOIN dbo.b_cust D ON A.b_cust_id=D.id
					WHERE :project_no IN (B.project_no,'') AND :projectname IN (B.name,'') AND :username IN (D.name,'') AND :e_violation_id IN (A.e_violation_id,'')
					AND :supply_id IN (C.id,'') AND :iid IN (D.account, '')
                    AND A.b_cust_id = {$id}
                    $query
					--AND DATEPART(MONTH,A.apply_date)=11 AND DATEPART(YEAR,A.apply_date)=DATEPART(YEAR,GETDATE())   --(月份)
					--AND DATEPART(YEAR,A.apply_date)=2020     --(年度)
					";
			
            $listAry = DB::select($SQL,['project_no'=>$cid,'supply_id'=>$bid,'projectname'=>$did,'username'=>$eid,'e_violation_id'=>$fid, 'iid'=>$iid]);
			//Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['工程案號','工程名稱','承攬商','承攬商成員','身分證','違規時間','違規分類','違規法規','違規事項','違規法則','再次入場日期'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->工程案號;
                    $tmp[]  = $value->工程名稱;
					$tmp[]  = $value->廠商名稱;
					$tmp[]  = $value->承攬商人員;
					$tmp[]  = substr($value->身分證,0,3) . '*****' . substr($value->身分證,-2);
					$tmp[]  = $value->違規時間;
					$tmp[]  = $value->違規分類;
					$tmp[]  = $value->違規法規;
					$tmp[]  = $value->違規事項;
					$tmp[]  = $value->違規罰則;
					$tmp[]  = $value->再次入場日期;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '違規清單_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain. '?id=' . SHCSLib::encode($id),'POST','form-inline');
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

		$html = $form->select('bid',$supplyAry,$bid,2,'承攬商');
        // $html.= $form->text('gid',$gid,2,'承攬商名稱'); 
        // $html.= $form->text('eid',$eid,2,'姓名');
        // $html.= $form->text('iid',$iid,2,'身分證');
        $form->addRowCnt($html);

		$html = $form->select('fid',$eviolation,$fid,2,'違規事項');
		$html.= $form->text('did',$did,2,'案名'); 
		$html.= $form->text('cid',$cid,2,'案號'); 
        $form->addRowCnt($html);

        $html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
        if (isset($params)) {
            $html .= $form->linkbtn($this->hrefMain, '返回', 2);
        }
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        // $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //下載按鈕
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
        $heads[] = ['title'=>'工程案名'];
		$heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'承攬商成員'];
		$heads[] = ['title'=>'身分證'];
		$heads[] = ['title'=>'違規時間'];
		$heads[] = ['title'=>'違規分類'];
		$heads[] = ['title'=>'違規法規'];
		$heads[] = ['title'=>'違規事項'];
		$heads[] = ['title'=>'違規罰則'];
		$heads[] = ['title'=>'再次入場日期'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->工程案號;
                $rept2           = $value->工程名稱;
				$rept3           = $value->廠商名稱;
				$rept4           = $value->承攬商人員;
				$rept5           = substr($value->身分證,0,3) . '*****' . substr($value->身分證,-2);
				$rept6           = $value->違規時間;
				$rept7           = $value->違規分類;
				$rept8           = $value->違規法規;
				$rept9           = $value->違規事項;
				$rept10           = $value->違規罰則;
				$rept11           = $value->再次入場日期;

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
        $content->rowTo($content->box_table($tbTile,$out,10));
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
