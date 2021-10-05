<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\sys_param;
use App\Model\WorkPermit\wp_check;
use App\Model\WorkPermit\wp_work;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;

class WorkPermitProcessShowController extends Controller
{
    use SessTraits;
    use WorkPermitWorkOrderProcessTrait,WorkPermitWorkTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitProcessTopicTrait,WorkPermitDangerTrait,WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitWorkOrderlineTrait,WorkPermitWorkOrderTrait;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitTopicShowController
    |--------------------------------------------------------------------------
    |
    | 工作許可證 顯示生命週期
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
        $this->hrefMain         = 'workpermitprocessshow';
        $this->hrefWork         = 'wpworkorder';
        $this->langText         = 'sys_workpermit';
        $this->hrefPrint        = 'printpermit';

        $this->hrefMainDetail   = 'workpermitprocessshow/';
        $this->hrefMainNew      = 'new_workpermitprocessshow/';
        $this->routerPost       = 'postWorkpermitprocessshow';

        $this->pageTitleMain    = Lang::get($this->langText.'.title25');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list25');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new25');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit25');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pagePrintBtn     = Lang::get('sys_btn.btn_42');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

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
        //工作許可證ＩＤ
        $urlid1   = $request->wid ? $request->wid : '';
        $urlid2   = $request->lid ? $request->lid : '';
        $urlfrom   = $request->from ? $request->from : '';
        $work_id  = SHCSLib::decode($urlid1);
        $list_id  = SHCSLib::decode($urlid2);
        $equalImg = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAA7klEQVR42mNkgABeIK4D4mggloCKMQLxfzQ2shguNS+AeCkQNwHxZ0ao4YeBWA+HBlItgLEvArEtiNENxCUMtAE9IAueEQgWcn0ADi4Y4z+NLMDQQHWAywdvgXgKEP8g0hwOIM4BYmFcPkC3YDoQZ5Po2KlAnElsEIFcn0uiBZOhviAqiNYAcRiJFqwC4hBigwgEngLxHyINZwFiGQY8qWj4JVOqBxE6WM1AXiSHEusDUJomJ5lmo5kzcBntDQN5RYUIsUGEi012MqWpBaAKR5LE8CYWPIdVmcU08kEvXSp9BgbUZgulwfWcAanZAgCnBI76sx2a+AAAAABJRU5ErkJggg==" width="15pt" >';
        $checkAry = ['Y'=>'■','N'=>'□',''=>'□','='=>$equalImg];
        $param_lookworker1      = sys_param::getParam('PERMIT_TOPIC_A_ID_LOOK_WORK1',0);
        $param_lookworker2      = sys_param::getParam('PERMIT_TOPIC_A_ID_LOOK_WORK2',0);
        if(!$work_id || !$list_id) return \Redirect::back()->withErrors(Lang::get($this->langText.'.permit_10026'));
        $workNo = wp_work::getNo($work_id);

        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$workNo;//列表標題
        $hrefMain = $this->hrefMain.'?wid='.$urlid1.'&lid='.$urlid2;

        $hrefBack = isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], strrpos($_SERVER['HTTP_REFERER'], '/') + 1) : $this->hrefWork;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getMyPermitWorkOrderProcess($work_id,$list_id);
        list($local,$localGPSX,$localGPSY) = wp_work::getLocalGPS($work_id);
        //列印之檔案
        $fileAry = $this->getWorkPermitWorkOrderCheckFile($work_id);
        if($request->has('showtest'))
        {
            dd($listAry[3]);
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//

        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $form->addLinkBtn($this->hrefPrint.'?id='.$urlid1, $this->pagePrintBtn,5,'','','','_blank'); //新增
        $form->addLinkBtn($hrefBack, $btnBack,2); //新增
        $form->addHr();
        if(count($fileAry))
        {
            //--- Box Start ---//
            $html = HtmlLib::genBoxStart2(Lang::get($this->langText.'.permit_30'),3);
            $form->addHtml( $html );
            foreach ($fileAry as $val)
            {
                $link = ($val['router'])? $val['router'] : $val['path'];
                $form->addLinkBtn($link . "?id=$urlid1", $val['name'],8,'file','','','_blank'); //
            }
            //--- Box End ---//
            $html = HtmlLib::genBoxEnd();
            $form->addHtml($html);
            $form->addHr();
        }
        //工作許可證題目-作答
        if(is_array($listAry) && count($listAry))
        {
            foreach ($listAry as $val)
            {
                $work_process_id    = $val['id'];
                $process_id    = $val['process_id'];
                $boxTitle           = $val['title'];
                $process_name       = $val['name'];
                $process_charger    = $val['charge_user'];
                $stime              = $val['stime'];
                $etime              = $val['etime'] ? $val['etime'] : HtmlLib::Color(Lang::get($this->langText . '.permit_145'), 'red');
                $work_time          = Lang::get($this->langText . '.permit_144', ['name1' => $val['stime'], 'name2' => $etime, 'name3' => $val['times']]);
                $ansRecordAry       = $val['permit'];
                $color              = $val['color_num'];

                if($request->has('showtest'))
                {
                    dd($listAry);
                }
                //--- Box Start ---//
                $html = HtmlLib::genBoxStart2(Lang::get($this->langText.'.sub_title3',['title'=>$boxTitle]),$color);
                $form->addHtml( $html );

                //階段名稱＆負責人
                $form->add('nameT101', HtmlLib::Color($process_name, 'blue', 1), Lang::get($this->langText . '.permit_142'));
                $form->add('nameT101', HtmlLib::Color($process_charger, 'blue', 1), Lang::get($this->langText . '.permit_143'));
                if ($process_id == 19) {
                    $form->add('nameT101', HtmlLib::Color($stime, 'blue', 1), Lang::get($this->langText . '.permit_167'));
                    $form->add('nameT101', HtmlLib::Color($etime, 'blue', 1), Lang::get($this->langText . '.permit_168'));
                } else {
                    $form->add('nameT101', HtmlLib::Color($work_time, 'blue', 1), Lang::get($this->langText . '.permit_140'));
                }

                if(count($ansRecordAry))
                {
                    $form->addHr();
                    foreach ($ansRecordAry as $value)
                    {
                        $topic_type = isset($value['topic_type'])? $value['topic_type'] : 0;
                        //危險告知
                        if($topic_type == 4)
                        {
                            $html = '';
                            if(count($value['option']))
                            {
                                foreach ($value['option'] as $key => $val)
                                {
                                    $topic_ans[$val['topic_a_id']]['ans'] = '=';
                                    $topic_ans[$val['topic_a_id']]['ans_type'] = $val['ans_type'];
                                    $ansCheckbox = ($val['ans_value'] == '已告知')? 'Ｖ' : '=';
                                    $html .= $ansCheckbox.$val['name'].$val['context'].'<br/>';
                                }
                            }
                            $form->add('nameT101',ContentLib::genSolidBox(Lang::get($this->langText.'.permit_45'),$html,1,3),$value['name']);
                        }
                        //簽名
                        elseif($topic_type == 3)
                        {
                            $html = '';
                            if(count($value['option']))
                            {
                                foreach ($value['option'] as $key => $val)
                                {
                                    //簽名
                                    if($val['wp_option_type'] == 6)
                                    {
                                        $img   = ($val['ans_value'])? Html::image($val['ans_value'],'',['class'=>'img-responsive','height'=>'30%']) : '';
                                        $html .=  $img.'<br/>';
                                    }elseif($val['wp_option_type'] == 5){
                                        $html .= $val['name'].'<br/>';
                                    }elseif($val['wp_option_type'] == 8) {
                                        //GPS
                                        $gps   = ($val['ans_value'])? $val['ans_value'] : '' ;
                                        if($gps)
                                        {
                                            $gpsAry = explode(',',$gps);
                                            $GPSX   = isset($gpsAry[0])? $gpsAry[0] : 0;
                                            $GPSY   = isset($gpsAry[1])? $gpsAry[1] : 0;

                                            //2019-12-30 新增 工作地點跟 施工地點 距離
                                            $distanceStr = '';
                                            if($localGPSX && $localGPSY && $GPSX && $GPSY && $GPSX != '0.0' && $GPSY != '0.0')
                                            {
                                                //工作地點：距離指定施工區域「:name1」:name2公尺  ！
                                                $distance = SHCSLib::getGPSDistance($localGPSX,$localGPSY,$GPSX,$GPSY,2);
                                                $distanceStr = Lang::get('sys_base.base_10942',['name1'=>$local,'name2'=>$distance]).'<br/>';
                                                $distanceStr = HtmlLib::Color($distanceStr,'blue',1);
                                            } else {
                                                if(!$localGPSX || !$localGPSY)
                                                {
                                                    $distanceStr .= Lang::get('sys_base.base_10943').'<br/>';
                                                }
                                                if(!$GPSX || $GPSX == '0.0' || !$GPSY || $GPSY == '0.0')
                                                {
                                                    $distanceStr .= Lang::get('sys_base.base_10944').'<br/>';
                                                }
                                                $distanceStr = HtmlLib::Color($distanceStr,'red',1);
                                            }

                                            $html .=  $distanceStr;

                                            $html .=  HtmlLib::genMapIframe($GPSX,$GPSY).'<br/>';
                                        }
                                    }
                                    //20210528修改
                                    else if($val['wp_option_type'] == 2 && $val['topic_a_id'] == 202){ //暫停原因
                                        $html .= HtmlLib::Color( $val['name'],'#000',1).'：<br/>';
                                        $html .= Lang::get('sys_base.base_10959') .'：'. $val['ans_value'].'<br/>';

                                    }else if($val['wp_option_type'] == 9 && in_array($val['topic_a_id'], [209, 217])) { //復工前需要氣體偵測
                                        $html .= HtmlLib::Color( $val['name'],'#000',1).'：<br/>';


                                        $wp_check_id  = isset($val['wp_check_id'])? $val['wp_check_id'] : 0;
                                        //table
                                        $heads      = $tBody1 = $tBody2 = $option = [];
                                        $check_kind     = wp_check::getKindID($wp_check_id);
                                        $topicOptinType = ($check_kind == 1)? [2,4] : [];
                                        //$optionAry = isset($val1['check']['option'])? $val1['check']['option'] : [];
                                        //if($work_process_id == 49854 && $value['topic_id'] == 2) dd($work_process_id,$val1['check']);

                                        if($check_kind == 1 && isset($val['check']))
                                        {
                                            //table顯示
                                            $table  = new TableLib();;
                                            //標題
                                            foreach ($val['check'] as $val2)
                                            {
                                                foreach ($val2['option'] as $val3)
                                                {
                                                    $optiontmp = [];
                                                    $check_topic_a_option_type = $val3['wp_option_type'];

                                                    if(in_array($check_topic_a_option_type,$topicOptinType))
                                                    {
                                                        $optiontmp['check_topic_a_id']   = $val3['check_topic_a_id'];
                                                        $optiontmp['ans_type']           = $val3['ans_type'];
                                                        $option[] = $optiontmp;

                                                        //時間格式
                                                        if($val3['wp_option_type'] == 4)
                                                        {
                                                            $heads[]  = ['title'=>$val3['name']];
                                                            $tBody1[] = ['name'=>''];
                                                            $tBody2[] = ['name'=>$val3['ans_value']];
                                                        }
                                                        //數值格式
                                                        if($val3['wp_option_type'] == 2)
                                                        {
                                                            //if($boxTitle == '施工階段')  dd($val2);
                                                            $heads[] = ['title'=>$val3['name']];
                                                            $checkColor = '';
                                                            if($val3['safe_action'] == 'between')
                                                            {
                                                                if((floatval($val3['safe_limit1']) > floatval($val3['ans_value'])) || floatval($val3['safe_limit2']) < floatval($val3['ans_value']))
                                                                {
                                                                    $checkColor = 'red';
                                                                }
                                                            }
                                                            if($val3['safe_action'] == 'more')
                                                            {
                                                                if(floatval($val3['safe_limit1']) >= floatval($val3['ans_value']))
                                                                {
                                                                    $checkColor = 'red';
                                                                }
                                                            }
                                                            if($val3['safe_action'] == 'under')
                                                            {
                                                                if(floatval($val3['safe_limit1']) <= floatval($val3['ans_value']))
                                                                {
                                                                    $checkColor = 'red';
                                                                }
                                                            }
                                                            //if(count($otherAry)) dd($otherAry);
                                                            $tBody1[] = ['name'=>$val3['safe_val']];
                                                            $tBody2[] = ['name'=>HtmlLib::Color($val3['ans_value'],$checkColor,1)];
                                                        }
                                                    }
                                                }
                                            }

                                            $table->addHead($heads,0);
                                            $table->addBody([$tBody1,$tBody2]);
                                            $html .= $table->output();
                                        }
                                        foreach ($val['check'] as $val2)
                                        {
                                            foreach ($val2['option'] as $val3)
                                            {
                                                $optiontmp = [];
                                                $check_topic_a_option_type = $val3['wp_option_type'];

                                                if(!in_array($check_topic_a_option_type,$topicOptinType))
                                                {
                                                    $optiontmp['check_topic_a_id']   = $val3['check_topic_a_id'];
                                                    $optiontmp['ans_type']           = $val3['ans_type'];
                                                    $name3                           = $val3['name'];
                                                    $ans_value3                      = isset($checkAry[$val3['ans_value']])? $checkAry[$val3['ans_value']] : $val3['ans_value'];
                                                    $ans_value3                      = HtmlLib::Color($ans_value3,'blue',1);
                                                    $option[] = $optiontmp;

                                                    if(in_array($check_topic_a_option_type,[6,7])) {
                                                        //圖片
                                                        $img   = ($val3['ans_value'])? Html::image($val3['ans_value'],'',['class'=>'img-responsive','height'=>'30%']) : '';
                                                        $html .=  $name3.$img.'<br/>';
                                                    }
                                                    elseif($check_topic_a_option_type == 5) {
                                                        //純顯示
                                                        $html .=  '<b>'.$name3.'</b><br/>';
                                                    }
                                                    //數值格式
                                                    else
                                                    {
                                                        $html .=  $name3.$ans_value3.'<br/>';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $form->add('nameT102',$html,$value['name']);
                        }
                        //一般文字<多選>
                        else
                        {
                            $html = '';
                            if(count($value['option']))
                            {
                                foreach ($value['option'] as $key => $val1)
                                {
                                    $topic_a_id         = isset($val1['topic_a_id'])? $val1['topic_a_id'] : 0;
                                    $ans_value          = isset($val1['ans_value'])? $val1['ans_value'] : '';
                                    $wp_option_type     = isset($val1['wp_option_type'])? $val1['wp_option_type'] : 1;
                                    $wp_check_id        = isset($val1['wp_check_id'])? $val1['wp_check_id'] : 0;
                                    $topic_name         = $val1['name'].((!strpos('：',$val1['name']))? '：' : '');
                                    //if($work_process_id == 49738 && $value['topic_id'] == 10) dd($work_process_id,$value);
                                    if($wp_option_type == 1)
                                    {
                                        $html .= $topic_name.HtmlLib::Color($ans_value,'blue',1).'<br/>';
                                    }
                                    elseif(in_array($wp_option_type,[2,4,3,10,13,14,17,18]))
                                    {
                                        if(!is_string($ans_value)) $ans_value = '';
                                        //if($ans_value == '施工人員(人數、姓名或另附名冊)：') dd();
                                        if($topic_a_id == 120)
                                        {
                                            $ans_value = date('H:i',strtotime($ans_value));
                                        }
                                        if($topic_a_id == 121)
                                        {
                                            $ans_value = date('H:i',strtotime($ans_value));
                                        }
                                        $html .= $topic_name.HtmlLib::Color($ans_value,'blue',1).'<br/>';
                                    }
                                    //檢點單
                                    elseif($wp_option_type == 9){
                                        //table
                                        $heads      = $tBody1 = $tBody2 = $option = [];
                                        $check_kind     = wp_check::getKindID($wp_check_id);
                                        $topicOptinType = ($check_kind == 1)? [2,4] : [];
                                        //$optionAry = isset($val1['check']['option'])? $val1['check']['option'] : [];
                                        //if($work_process_id == 49854 && $value['topic_id'] == 2) dd($work_process_id,$val1['check']);

                                        if($check_kind == 1 && isset($val1['check']))
                                        {
                                            //table顯示
                                            $table  = new TableLib();;
                                            //標題
                                            foreach ($val1['check'] as $val2)
                                            {
                                                foreach ($val2['option'] as $val3)
                                                {
                                                    $optiontmp = [];
                                                    $check_topic_a_option_type = $val3['wp_option_type'];
//                                                if($check_topic_a_option_type == 7) dd('Y',$val3);
//                                                if($work_process_id == 49854 && $val3['check_topic_a_id'] == 25) dd($val3,$val3['wp_option_type']);

                                                    if(in_array($check_topic_a_option_type,$topicOptinType))
                                                    {
                                                        $optiontmp['check_topic_a_id']   = $val3['check_topic_a_id'];
                                                        $optiontmp['ans_type']           = $val3['ans_type'];
                                                        $option[] = $optiontmp;

                                                        //時間格式
                                                        if($val3['wp_option_type'] == 4)
                                                        {
                                                            $heads[]  = ['title'=>$val3['name']];
                                                            $tBody1[] = ['name'=>''];
                                                            $tBody2[] = ['name'=>$val3['ans_value']];
                                                        }
                                                        //數值格式
                                                        if($val3['wp_option_type'] == 2)
                                                        {
                                                            //if($boxTitle == '施工階段')  dd($val2);
                                                            $heads[] = ['title'=>$val3['name']];
                                                            $checkColor = '';
                                                            if($val3['safe_action'] == 'between')
                                                            {
                                                                if((floatval($val3['safe_limit1']) > floatval($val3['ans_value'])) || floatval($val3['safe_limit2']) < floatval($val3['ans_value']))
                                                                {
                                                                    $checkColor = 'red';
                                                                }
                                                            }
                                                            if($val3['safe_action'] == 'more')
                                                            {
                                                                if(floatval($val3['safe_limit1']) >= floatval($val3['ans_value']))
                                                                {
                                                                    $checkColor = 'red';
                                                                }
                                                            }
                                                            if($val3['safe_action'] == 'under')
                                                            {
                                                                if(floatval($val3['safe_limit1']) <= floatval($val3['ans_value']))
                                                                {
                                                                    $checkColor = 'red';
                                                                }
                                                            }
                                                            //if(count($otherAry)) dd($otherAry);
                                                            $tBody1[] = ['name'=>$val3['safe_val']];
                                                            $tBody2[] = ['name'=>HtmlLib::Color($val3['ans_value'],$checkColor,1)];
                                                        }
                                                    }
                                                }
                                            }

                                            $table->addHead($heads,0);
                                            $table->addBody([$tBody1,$tBody2]);
                                            $html .= $table->output();
                                        }
                                        foreach ($val1['check'] as $val2)
                                        {
                                            foreach ($val2['option'] as $val3)
                                            {
                                                $optiontmp = [];
                                                $check_topic_a_option_type = $val3['wp_option_type'];
//                                                if($check_topic_a_option_type == 7) dd('Y',$val3);
//                                                if($work_process_id == 49854 && $val3['check_topic_a_id'] == 25) dd($val3,$val3['wp_option_type']);

                                                if(!in_array($check_topic_a_option_type,$topicOptinType))
                                                {
                                                    $optiontmp['check_topic_a_id']   = $val3['check_topic_a_id'];
                                                    $optiontmp['ans_type']           = $val3['ans_type'];
                                                    $name3                           = $val3['name'];
                                                    $ans_value3                      = isset($checkAry[$val3['ans_value']])? $checkAry[$val3['ans_value']] : $val3['ans_value'];
                                                    $ans_value3                      = HtmlLib::Color($ans_value3,'blue',1);
                                                    $option[] = $optiontmp;

                                                    if(in_array($check_topic_a_option_type,[6,7])) {
                                                        //圖片
                                                        $img   = ($val3['ans_value'])? Html::image($val3['ans_value'],'',['class'=>'img-responsive','height'=>'30%']) : '';
                                                        $html .=  $name3.$img.'<br/>';
                                                    }
                                                    elseif($check_topic_a_option_type == 5) {
                                                        //純顯示
                                                        $html .=  '<b>'.$name3.'</b><br/>';
                                                    }
                                                    //數值格式
                                                    else
                                                    {
                                                        $html .=  $name3.$ans_value3.'<br/>';
                                                    }
                                                }
                                            }
                                        }


                                    } elseif($wp_option_type == 7) {
                                        //圖片
                                        $img   = ($val1['ans_value'])? Html::image($val1['ans_value'],'',['class'=>'img-responsive','height'=>'30%']) : '';
                                        $html .=  $img.'<br/>';
                                    } elseif($wp_option_type == 8) {
                                        //GPS
                                        $gps   = ($val1['ans_value'])? $val1['ans_value'] : '' ;
                                        if($gps)
                                        {
                                            $gpsAry = explode(',',$gps);
                                            $GPSX   = isset($gpsAry[0])? $gpsAry[0] : 0;
                                            $GPSY   = isset($gpsAry[1])? $gpsAry[1] : 0;

                                            //2019-12-30 新增 工作地點跟 施工地點 距離
                                            $distanceStr = '';
                                            if($localGPSX && $localGPSY && $GPSX && $GPSY && $GPSX != '0.0' && $GPSY != '0.0')
                                            {
                                                //工作地點：距離指定施工區域「:name1」:name2公尺  ！
                                                $distance = SHCSLib::getGPSDistance($localGPSX,$localGPSY,$GPSX,$GPSY,2);
                                                $distanceStr = Lang::get('sys_base.base_10942',['name1'=>$local,'name2'=>$distance]).'<br/>';
                                                $distanceStr = HtmlLib::Color($distanceStr,'blue',1);
                                            } else {
                                                if(!$localGPSX || !$localGPSY)
                                                {
                                                    $distanceStr .= Lang::get('sys_base.base_10943').'<br/>';
                                                }
                                                if(!$GPSX || $GPSX == '0.0' || !$GPSY || $GPSY == '0.0')
                                                {
                                                    $distanceStr .= Lang::get('sys_base.base_10944').'<br/>';
                                                }
                                                $distanceStr = HtmlLib::Color($distanceStr,'red',1);
                                            }

                                            $html .=  $distanceStr;
                                            $html .=  HtmlLib::genMapIframe($GPSX,$GPSY).'<br/>';
                                        }
                                    } else {
                                        $html .= $topic_name.'<br/>';
                                    }
                                }
                            }
                            $topic_name = ($value['topic_id'] == -1)? $value['topic'] : $value['name'];
                            $form->add('nameT102',$html,$topic_name);
                        }

                    }
                }

                //--- Box End ---//
                $html = HtmlLib::genBoxEnd();
                $form->addHtml($html);
            }
        }
        $out .= $form->output(1);

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTitle,$out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table1").DataTable({
                        "language": {
                        "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
                    }
                    });
                    
                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }
}
