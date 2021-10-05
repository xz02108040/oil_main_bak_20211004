<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\HTTCLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_door_supply_whitelist_pass;
use App\Model\WorkPermit\wp_check_kind;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_identity;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_pipeline;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_permit_workitem_a;
use App\Model\WorkPermit\wp_permit_workitem_b;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_worker;
use App\Model\WorkPermit\wp_work_workitem;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class FindWorkPermitController extends Controller
{
    use EmpTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | FindWorkPermitController
    |--------------------------------------------------------------------------
    |
    | 查詢工作許可證 相關資料
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
        $this->hrefMain         = 'findPermit';

        $this->identity_C       = sys_param::getParam('PERMIT_SUPPLY_WORKER',9);

    }
    /**
     * 搜尋廠區相關
     *
     * @return void
     */
    public function findPermit(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $data   = [];
        $type   = $request->has('type')? $request->type : 0;
        $id     = is_numeric($request->id) ? $request->id : 0;
        $pid    = is_numeric($request->pid) ? $request->pid : 0;
        $wid    = is_numeric($request->wid) ? $request->wid : 0;
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 2)
            {
                //搜尋：工作項目
                $data = wp_permit_workitem::getSelect($id);
            }elseif($type == 1)
            {
                //搜尋：危險等級
                $data = wp_permit_danger::getSelect($id);
            }elseif($type == 3)
            {
                $aproc          = wp_work::getAproc($wid);
                $worker_aproc   = (in_array($aproc,['A','C']))? '' : 'R';
                $isIn           = ($worker_aproc == 'R')? 1 : 0;
                //工安＆工負
                $rootAry        = wp_work_worker::getRootMen($wid);

                $isInMenAry     = wp_work_worker::getSelect($wid,0,0,0,['A','P',$worker_aproc],[],$isIn);
                if($id == 9)
                {
                    $isInMenAry = array_merge($isInMenAry,$rootAry);
                }

                //                dd($isInMenAry);
                $memberAry  = view_door_supply_whitelist_pass::getProjectMemberWhitelistSelect($pid,[],$id,1,0,$isInMenAry);



                //搜尋：該工程身份人員
                $data   = $memberAry;
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return response()->json($data);
    }

    public function findWorkItem(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $html   = '';
        $isGenHtml = 0;
        $data   = ['danger'=>[],'check'=>[]];
        $this->langText = 'sys_workpermit';
        $type   = $request->has('type')? $request->type : 0;
        $wid    = is_numeric($request->wid) ? $request->wid : 0;

        if($wid)
        {
            $dangerAry = wp_permit_workitem_a::getSelect($wid);
            $checkAry  = wp_permit_workitem_b::getSelect($wid);

            $data   = ['danger'=>$dangerAry,'check'=>$checkAry];
        }
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return response()->json($data);
    }
    /**
     * 搜尋&產生 專業人員維護畫面
     *
     * @return void
     */
    public function findPermitWork(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $html   = '';
        $isGenHtml = 0;
        $this->langText = 'sys_workpermit';
        $type   = $request->has('type')? $request->type : 0;
        $wid    = is_numeric($request->wid) ? $request->wid : 0;
        $pid    = is_numeric($request->pid) ? $request->pid : 0;
        $iid    = is_numeric($request->iid) ? $request->iid : 0;
        $uid    = is_numeric($request->uid) ? $request->uid : 0;
        $kid    = is_numeric($request->kid) ? $request->kid : 0;
        $url    = is_numeric($request->url) ? $request->url : 1;
        switch ($url) {
            case 1 :
                $this->hrefMain = 'exa_wpworkorder';
                break;
            case 2 :
                $this->hrefMain = 'wpworkorder';
                break;
            case 3 :
                $this->hrefMain = 'exa_wpworkorder2';
                break;
        }
        $isEdit = is_numeric($request->isCheck) ? $request->isCheck : 0;
        $isShow = is_numeric($request->isShow) ? $request->isShow : 1;
        $form   = new FormLib(0,'#','POST','form-inline');
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 3)
            {
                //負責移除不需要的
                if($kid)
                {
                    $identityMemberAry  = Session::get($this->hrefMain.'.identityMemberAry',[]);
                    unset($identityMemberAry[$kid]);
                    Session::put($this->hrefMain.'.identityMemberAry',$identityMemberAry);
                }

            }elseif($type == 2)
            {
                //負責新增
                if($iid && $uid > 0)
                {
                    $identityMemberAry  = Session::get($this->hrefMain.'.identityMemberAry',[]);
                    $identityMemberAry[$uid] = $iid;
                    Session::put($this->hrefMain.'.identityMemberAry',$identityMemberAry);
                }

            }elseif($type == 1)
            {
                //只負責產生畫面
                $isGenHtml = 1;
            }

            //是否要產生畫面
            if($isGenHtml)
            {
                $identityAry1       = b_supply_engineering_identity::getSelect(1,[1,2]);
                $identityAry2       = b_supply_engineering_identity::getSelect(0);
                $memberAry          = view_door_supply_whitelist_pass::getProjectMemberWhitelistSelect($pid,[],$this->identity_C);
                $identityMemberAry  = Session::get($this->hrefMain.'.identityMemberAry',[]);
                $identityMemberAry2 = Session::get($this->hrefMain.'.identityMemberAry2',[]);
                $store_id           = Session::get($this->hrefMain.'.store_id',0);
                $list_aproc         = Session::get($this->hrefMain.'.list_aproc','A');
                $isToday            = Session::get($this->hrefMain.'.isToday',0);
                //dd($identityMemberAry);
                //工程身份
                $no    = 0;
                $tBody = [];
                //table
                $table = new TableLib();
                //標題
                $heads[] = ['title'=>'No'];
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_41')]; //工程身份
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_43')]; //成員
                if($isShow == 2)
                {
                    $heads[] = ['title'=>Lang::get($this->langText.'.permit_46')]; //在廠狀態
                }
                $table->addHead($heads,$isEdit);

                //加入
                if($isEdit)
                {
                    $tBody[] = ['0'=>[ 'name'=> '','b'=>1,'style'=>'width:5%;'],
                        '11'=>[ 'name'=> $form->select('identity_id',$identityAry1)],
                        '12'=>[ 'name'=> $form->select('worker',$memberAry)],
                        '99'=>[ 'name'=> $form->linkbtn( '#',Lang::get('sys_btn.btn_44') ,'2','addMember','','addMember();return false;') ]
                    ];
                }

                //加入成員
                if(count($identityMemberAry))
                {
                    foreach ($identityMemberAry as $key => $val)
                    {
                        if($key)
                        {
                            $no++;


                            $user_id      = (in_array($val,[1,2]))? substr($key,1) : substr($key,3);
                            $name1        = isset($identityAry2[$val])? $identityAry2[$val] : '';
                            $name2        = $user_id.' '.User::getName($user_id);
                            $name3        = '';
                            //按鈕
                            $btn          = $form->hidden('identityMemberAry['.$key.']',$val);
                            $btn         .= $form->linkbtn( '#',Lang::get('sys_btn.btn_23') ,'4','delMember','','delMember('.$key.');return false;'); //按鈕
                            if($isEdit)
                            {
                                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                                    '11'=>[ 'name'=> $name1],
                                    '12'=>[ 'name'=> $name2],
                                    '99'=>[ 'name'=> $btn ]
                                ];
                            } else {
                                if($isShow == 2)
                                {
                                    $isNow = 1;
                                    if(!in_array($list_aproc,['A']))
                                    {
                                        $isNow = 0;
                                    }

                                    if(!$isNow)
                                    {
                                        //已經收工，以當時的進出時間為主
                                        $doorData = isset($identityMemberAry2[$key])? $identityMemberAry2[$key] : [];
                                        $door_stamp1 = isset($doorData['door_stime'])? $doorData['door_stime'] : '';
                                        $door_stamp2 = isset($doorData['door_etime'])? $doorData['door_etime'] : '';
                                        $door_stamp3 = isset($doorData['work_time'])?  $doorData['work_time'] : '';
                                        if($door_stamp1)
                                        {
                                            $name3 = Lang::get('sys_base.base_40243',['time1'=>$door_stamp1,'time2'=>$door_stamp2]);
                                            $name3.= ($door_stamp3)? Lang::get('sys_base.base_40249',['time3'=>$door_stamp3]) : '';
                                        }

                                    } else {
                                        //是否已經在廠
                                        list($isIn,$name3) = HTTCLib::getMenDoorStatus($store_id,$user_id);
                                        if(!$isIn) $name3  = HtmlLib::Color($name3,'red',1);
                                    }


                                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                                        '11'=>[ 'name'=> $name1],
                                        '12'=>[ 'name'=> $name2],
                                        '99'=>[ 'name'=> $name3],
                                    ];
                                } else {
                                    $name3 = '';
                                    if($isToday && !in_array($list_aproc,['A','B']))
                                    {
                                        if($list_aproc == 'W' || ($list_aproc == 'R' && empty($identityMemberAry2[$key]['door_stime'])))
                                        {
                                            //是否已經在廠
                                            list($isIn,$name3) = HTTCLib::getMenDoorStatus($store_id,$user_id);
                                            if(!$isIn) $name3  = HtmlLib::Color($name3,'red',1);
                                            $name3             = '【'.$name3.'】';
                                        } else {
                                            //已經收工，以當時的進出時間為主
                                            $doorData = isset($identityMemberAry2[$key])? $identityMemberAry2[$key] : [];
                                            $door_stamp1 = isset($doorData['door_stime'])? $doorData['door_stime'] : '';
                                            $door_stamp2 = isset($doorData['door_etime'])? $doorData['door_etime'] : '';
                                            $door_stamp3 = isset($doorData['work_time'])?  $doorData['work_time'] : '';
                                            if($door_stamp1)
                                            {
                                                $name3 = '，'.Lang::get('sys_base.base_40243',['time1'=>$door_stamp1,'time2'=>$door_stamp2]);
                                                $name3.= ($door_stamp3)? Lang::get('sys_base.base_40249',['time3'=>$door_stamp3]) : '';
                                            }

                                        }

                                    }
                                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                                        '11'=>[ 'name'=> $name1],
                                        '12'=>[ 'name'=> $name2.$name3]
                                    ];
                                }
                            }
                        }
                    }
                }

                $table->addBody($tBody);
                //專業人員
                $html = $table->output();
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }

    /**
     * 搜尋&產生 許可工作項目
     *
     * @return void
     */
    public function findPermitWorkItem(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $html   = '';
        $isGenHtml = 0;
        $this->langText = 'sys_workpermit';
        $type   = $request->has('type')? $request->type : 0;
        $iid    = is_numeric($request->iid) ? $request->iid : 0;
        $kid    = is_numeric($request->kid) ? $request->kid : 0;
        $url    = is_numeric($request->url) ? $request->url : 1;
        $isEdit = is_numeric($request->isCheck) ? $request->isCheck : 0;
        switch ($url) {
            case 1 :
                $this->hrefMain = 'exa_wpworkorder';
                break;
            case 2 :
                $this->hrefMain = 'wpworkorder';
                break;
            case 3 :
                $this->hrefMain = 'exa_wpworkorder2';
                break;
        }
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 3)
            {
                //負責移除不需要的
                if($kid)
                {
                    $itemworkAry  = Session::get($this->hrefMain.'.itemworkAry',[]);
                    unset($itemworkAry[$kid]);
                    Session::put($this->hrefMain.'.itemworkAry',$itemworkAry);
                }

            }elseif($type == 2)
            {
                //負責新增
                if($iid > 0)
                {
                    $itemworkAry  = Session::get($this->hrefMain.'.itemworkAry',[]);
                    $itemworkAry[$iid] = $iid;
                    Session::put($this->hrefMain.'.itemworkAry',$itemworkAry);
                }

            }elseif($type == 1)
            {
                //只負責產生畫面
                $isGenHtml = 1;
            }

            //是否要產生畫面
            if($isGenHtml)
            {
                $showRowMax     = 5;
                $itemworkAry    = Session::get($this->hrefMain.'.itemworkAry',[]);

                //3. 產生畫面
                //輸出ＴＡＢＬＥ
                $table = new TableLib();
                //標題
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_9')];  //狀態
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_10')]; //工作項目
                $table->addHead($heads,0);
                for($i = 1; $i<=3 ; $i++)
                {
                    $itemHtml       = '';
                    $showRowCnt     = 1;
                    $selectAllAry   = wp_permit_workitem::getSelect($i,0,0);
                    $itemTitle      = wp_permit_kind::getName($i);
//                    dd($selectAllAry,$itemworkAry);
                    foreach ($selectAllAry as $id => $name)
                    {
                        $checked = (isset($itemworkAry[$id]))? true : false;
                        $memo    = (isset($itemworkAry[$id]))? $itemworkAry[$id] : '';

                        if(!$isEdit)
                        {
                            if(!$checked) continue;
                        }
                        if($showRowCnt == 1) $itemHtml .= '<div >';

                        if(!$isEdit)
                        {
                            $itemHtml .= '&nbsp;&nbsp;■&nbsp;&nbsp;';
                        } else {
                            $itemHtml .= FormLib::checkbox('itemwork['.$id.'][val]',$id,$checked,'itemwork','toEven(this.value)');
                        }
                        $itemHtml .= '<label>'.$name.'</label>&nbsp;&nbsp;';
                        if(wp_permit_workitem::isText($id))
                        {
                            $itemHtml .= ($isEdit)? \Form::text('itemwork['.$id.'][memo]',$memo) : $memo;
                        }
                        $showRowCnt++;
                        if($showRowCnt == $showRowMax)
                        {
                            $itemHtml .= '</div>';
                            $showRowCnt = 1;
                        }
                    }

                    $tBody[] = ['0' =>[ 'name'=> $itemTitle,'b'=>1,'style'=>'width:10%;'],
                                '12'=>[ 'name'=> $itemHtml],
                    ];
                }
                $table->addBody($tBody);
                //輸出ＨＴＭＬ
                $html = $table->output();
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }

    /**
     * 搜尋&產生 檢點單類型
     *
     * @return void
     */
    public function findPermitWorkCheck(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $html   = '';
        $isGenHtml = 0;
        $this->langText = 'sys_workpermit';
        $type   = $request->has('type')? $request->type : 0;
        $iid    = is_numeric($request->iid) ? $request->iid : 0;
        $kid    = is_numeric($request->kid) ? $request->kid : 0;
        $url    = is_numeric($request->url) ? $request->url : 1;
        $isEdit = is_numeric($request->isCheck) ? $request->isCheck : 0;
        switch ($url) {
            case 1 :
                $this->hrefMain = 'exa_wpworkorder';
                break;
            case 2 :
                $this->hrefMain = 'wpworkorder';
                break;
            case 3 :
                $this->hrefMain = 'exa_wpworkorder2';
                break;
        }
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 3)
            {
                //負責移除不需要的
                if($kid)
                {
                    $checkAry  = Session::get($this->hrefMain.'.checkAry',[]);
                    unset($checkAry[$kid]);
                    Session::put($this->hrefMain.'.checkAry',$checkAry);
                }

            }elseif($type == 2)
            {
                //負責新增
                if($iid > 0)
                {
                    $checkAry  = Session::get($this->hrefMain.'.checkAry',[]);
                    $checkAry[$iid] = $iid;
                    Session::put($this->hrefMain.'.checkAry',$checkAry);
                }

            }elseif($type == 1)
            {
                //只負責產生畫面
                $isGenHtml = 1;
            }

            //是否要產生畫面
            if($isGenHtml)
            {
                //1. 附加檢點單全部
                $selectAllAry   = wp_check_kind::getSelect(0,0,[1]);

                //2. 取得已選擇 附加檢點單
                $checkAry      = Session::get($this->hrefMain.'.checkAry',[]);
                //dd($selectAllAry,$checkAry);
                //3. 產生畫面
                $showRowMax = 5;
                $showRowCnt = 1;
                foreach ($selectAllAry as $id => $name)
                {
                    $checked = (isset($checkAry[$id]))? true : false;
                    if(!$isEdit)
                    {
                        if(!$checked) continue;
                    }
                    if($showRowCnt == 1) $html .= '<div class="form-group">';

                    if(!$isEdit)
                    {
                        $html .= '&nbsp;&nbsp;■&nbsp;&nbsp;';
                    } else {
                        $html .= FormLib::checkbox('check['.$id.']',$id,$checked);
                    }

                    $html .= '<label>'.$name.'</label>';
                    $showRowCnt++;
                    if($showRowCnt == $showRowMax)
                    {
                        $html .= '</div>';
                        $showRowCnt = 1;
                    }
                }
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }

    /**
     * 搜尋&產生 危害告知
     *
     * @return void
     */
    public function findPermitWorkDanger(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $html   = '';
        $isGenHtml = 0;
        $this->langText = 'sys_workpermit';
        $type   = $request->has('type')? $request->type : 0;
        $iid    = is_numeric($request->iid) ? $request->iid : 0;
        $kid    = is_numeric($request->kid) ? $request->kid : 0;
        $url    = is_numeric($request->url) ? $request->url : 1;
        $isEdit = is_numeric($request->isCheck) ? $request->isCheck : 0;
        switch ($url) {
            case 1 :
                $this->hrefMain = 'exa_wpworkorder';
                break;
            case 2 :
                $this->hrefMain = 'wpworkorder';
                break;
            case 3 :
                $this->hrefMain = 'exa_wpworkorder2';
                break;
        }
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 3)
            {
                //負責移除不需要的
                if($kid)
                {
                    $dangerAry  = Session::get($this->hrefMain.'.dangerAry',[]);
                    unset($dangerAry[$kid]);
                    Session::put($this->hrefMain.'.dangerAry',$dangerAry);
                }

            }elseif($type == 2)
            {
                //負責新增
                if($iid > 0)
                {
                    $dangerAry  = Session::get($this->hrefMain.'.dangerAry',[]);
                    $dangerAry[$iid] = $iid;
                    Session::put($this->hrefMain.'.dangerAry',$dangerAry);
                }

            }elseif($type == 1)
            {
                //只負責產生畫面
                $isGenHtml = 1;
            }

            //是否要產生畫面
            if($isGenHtml)
            {
                //1. 危害告知全部
                $dangerAllAry   = wp_permit_danger::getSelect(0);
                //dd($dangerAry);
                //2. 取得已選擇
                $dangerAry      = Session::get($this->hrefMain.'.dangerAry',[]);

                //3. 產生畫面
                $showRowMax = 5;
                $showRowCnt = 1;
                foreach ($dangerAllAry as $danger_id => $danger_name)
                {
                    $checked = (isset($dangerAry[$danger_id]))? true : false;
                    if(!$isEdit)
                    {
                        if(!$checked) continue;
                    }
                    if($showRowCnt == 1) $html .= '<div class="form-group">';

                    if(!$isEdit)
                    {
                        $html .= '&nbsp;&nbsp;■&nbsp;&nbsp;';
                    } else {
                        $html .= FormLib::checkbox('danger['.$danger_id.']',$danger_id,$checked);
                    }
                    $html .= '<label>'.$danger_name.'</label>';
                    $showRowCnt++;
                    if($showRowCnt == $showRowMax)
                    {
                        $html .= '</div>';
                        $showRowCnt = 1;
                    }
                }
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }

    /**
     * 搜尋&產生 危害告知
     *
     * @return void
     */
    public function findPermitWorkLine(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $html   = '';
        $isGenHtml = 0;
        $this->langText = 'sys_workpermit';
        $type   = $request->has('type')? $request->type : 0;
        $iid    = is_numeric($request->iid) ? $request->iid : 0;
        $kid    = is_numeric($request->kid) ? $request->kid : 0;
        $url    = is_numeric($request->url) ? $request->url : 1;
        $isEdit = is_numeric($request->isCheck) ? $request->isCheck : 0;
        switch ($url) {
            case 1 :
                $this->hrefMain = 'exa_wpworkorder';
                break;
            case 2 :
                $this->hrefMain = 'wpworkorder';
                break;
            case 3 :
                $this->hrefMain = 'exa_wpworkorder2';
                break;
        }
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 3)
            {
                //負責移除不需要的
                if($kid)
                {
                    $lineAry  = Session::get($this->hrefMain.'.lineAry',[]);
                    unset($lineAry[$kid]);
                    Session::put($this->hrefMain.'.lineAry',$lineAry);
                }

            }elseif($type == 2)
            {
                //負責新增
                if($iid > 0)
                {
                    $lineAry  = Session::get($this->hrefMain.'.lineAry',[]);
                    $lineAry[$iid] = $iid;
                    Session::put($this->hrefMain.'.lineAry',$lineAry);
                }

            }elseif($type == 1)
            {
                //只負責產生畫面
                $isGenHtml = 1;
            }

            //是否要產生畫面
            if($isGenHtml)
            {
                //1. 危害告知全部
                $lineAllAry   = wp_permit_pipeline::getSelect(0);
                //dd($dangerAry);
                //2. 取得已選擇
                $lineAry      = Session::get($this->hrefMain.'.lineAry',[]);

                //3. 產生畫面
                $showRowMax = 5;
                $showRowCnt = 1;
                foreach ($lineAllAry as $line_id => $line_name)
                {
                    $checked = (isset($lineAry[$line_id]))? true : false;
                    $memo    = (isset($lineAry[$line_id]))? $lineAry[$line_id] : '';
                    if(!$isEdit)
                    {
                        if(!$checked) continue;
                    }
                    if($showRowCnt == 1) $html .= '<div class="form-group">';

                    if(!$isEdit)
                    {
                        $html .= '&nbsp;&nbsp;■&nbsp;&nbsp;';
                    } else {
                        $html .= FormLib::checkbox('line['.$line_id.']',$line_id,$checked);
                    }
                    $html .= '<label>'.$line_name.'</label>';
                    if($memo) $html .= '('.$memo.')';
                    $showRowCnt++;
                    if($showRowCnt == $showRowMax)
                    {
                        $html .= '</div>';
                        $showRowCnt = 1;
                    }
                }
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }
}
