<?php namespace App\Lib;

use Lang;
/**
 * Form
 *
 * @date 2018/09/26
 */
class FormLib{

    public $out;

    /**
     * 1.產生FormLib
     * FormLib constructor.
     * @param int $type （1:使用route 2:使用網址）
     * @param $url （1:使用route 2:使用網址）
     * @param string $method （預設ＧＥＴ）
     * @param string $class
     * @param string $files
     * @param string $divClass
     * @param string $divStyle
     */
    function __construct($type=0,$url,$method='GET',$style=0,$files='flase',$class=''){
        $fromStyle    = ($style == 2)? 'form-inline ' : (($style == 1)? 'form-horizontal ' : '');
        $this->style  = $style;
        $this->out    = "";

        //\Form START
        if($type)
        {
            $form = \Form::open(array('route' => $url, 'method' => $method,'class'=>$fromStyle.$class,'files'=> $files));
        }
        else
        {
            $form = \Form::open(array('url' => $url, 'method' => $method,'class'=>$class,'files'=> $files));
        }
        //從中間插入
        $formAry = explode('>',$form);
        $formAry[0] .= '><div class="box-body"'; //加上 box-body

        $this->out = implode('>',$formAry);
    }


    /**
     * 新增 ＨＴＭＬ內容
     * @param string $inputHtml
     */
    public function addHtml($inputHtml="")
    {
        $this->out .= $inputHtml;
    }

    /**
     * [ATL] 新增一個文章區塊 開始
     *
     * @param $cont
     */
    public function boxBody($cont)
    {
        $this->out  = '<div class="box-body">';
        $this->out .= $cont;
        $this->out .= '</div>';
    }

    /**
     * [ATL] 新增一個文章區塊 結束
     *
     * @param $cont
     */
    public function boxFoot($cont,$align='2',$isBodyEnd = 1)
    {
        if($isBodyEnd) $this->out .= '</div>'; //如果有用 box-body
        $alignClass = ($align == 2)? 'text-center' : (($align == 3)? 'text-right' : '');
        $this->out .= '<div class="box-footer '.$alignClass.'">';
        $this->out .= $cont;
        $this->out .= '</div>';
    }

    //加入一個input欄位
    public function add($dID,$inputHtml="",$label='',$isRequired = 0,$labelClass='control-label ',$inputClass='')
    {
        $labelcol = ($this->style == 1)? 'col-sm-2 ' : '';
        $inputcol = ($this->style == 1)? 'col-sm-10 ' : '';

        $this->out .= '<div class="form-group">
    				            ';
        if($label)
        {
            $this->out .= '<label for="'.$dID.'" class="'.$labelcol.$labelClass.'">'.$label.'：';
            if($isRequired) $this->out .= '<span style="color:red">＊</span>';
            $this->out .= '</label>';
        }
        $this->out .= '<div id="'.$dID.'_title" class="'.$inputcol.$inputClass.'">';
        $this->out .= $inputHtml;
        $this->out .= '</div>';
        $this->out .= '</div>';
        if($this->style == 2) $this->out .= '&nbsp;';
    }

    //加入n個input欄位
    public function addMore($inputs)
    {
        if(count($inputs))
        {
            $labelcol = ($this->style == 1)? 'col-sm-2 ' : '';
            $inputcol = ($this->style == 1)? 'col-sm-10 ' : '';
            //START
            $this->out .= '<div class="form-group">
    				       ';
            foreach($inputs as $value)
            {
                $dID        = (isset($value['id']))?         $value['id']:'dID';
                $label      = (isset($value['label']))?      $value['label']:'';
                $isRequired = (isset($value['isRequired']))? $value['isRequired']:0;
                $labelClass = (isset($value['labelClass']))? $value['labelClass']:'control-label ';
                $inputClass = (isset($value['inputClass']))? $value['inputClass']:'';
                $inputHtml  = (isset($value['input']))?      $value['input']:'';
                //如果沒有input
                if($inputHtml)
                {
                    if($label)
                    {
                        $this->out .= '<label for="'.$dID.'" class="'.$labelcol.$labelClass.'">'.$label;
                        if($isRequired) $this->out .= '<span style="color:red">＊</span>';
                        $this->out .= '</label>';
                    }
                    $this->out .= '<div id="'.$dID.'_title" class="'.$inputcol.$inputClass.'">';
                    $this->out .= $inputHtml;
                    $this->out .= '</div>';
                }
            }
            //END
            $this->out .= '</div>';
            if($this->style == 2) $this->out .= '&nbsp;';
        }
    }

    /**
     * [ATL] 有Ｔａｂ的文章區塊
     * @param array $tabAry
     * @return string
     */
    public static function customTab($tabAry = [])
    {
        if(!count($tabAry)) return '';
        $outHead = $outBody = '';
        $f = 1;

        foreach ($tabAry as $val)
        {
            if(isset($val['head']) && isset($val['body']))
            {
                //#Tab Header
                $isActive = ($f == 1)? 'active' : '';
                $outHead .= '<li class="'.$isActive.'"><a href="#tab_'.$f.'" data-toggle="tab">'.$val['head'].'</a></li>';

                //#Tab Body
                $outBody .= '<div class="tab-pane '.$isActive.'" id="tab_'.$f.'">'.$val['body'].'</div>';

                $f++;
            }
        }
        //組合 tab
        $out = '<div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
            '.$outHead.'
            </ul>
            <div class="tab-content">
            '.$outBody.'
            </div>
            </div>';

        return $out;
    }

    /**
     * 新增 一行
     * @param string $html
     * @param int $size
     * @param int $offset
     */
    public function addRow($html = '',$size = 8,$offset = 2)
    {
        $this->out .= '<div class="row">
                <div class="col-lg-'.$size.' col-lg-offset-'.$offset.'">
                    '.$html.'
                </div><!-- /.col-lg-4 -->
            </div><!-- /.row -->';
    }

    /**
     * 新增 一行 ＋ 內容
     * @param string $html
     * @param int $size
     * @param int $offset
     */
    public function addRowCnt($html = '')
    {
        $this->out .= '<div class="row">
                '.$html.'
            </div><!-- /.row -->';
    }

    /**
     * 新增一個置中
     * @param string $name
     * @param string $btnName
     */
    public function addCenterInput($name = 'name',$btnName= 'Go!')
    {
        $this->out .= '<div class="row">
                <div class="col-lg-4 col-lg-offset-4">
                    <div class="input-group">
                        <input type="text" name="'.$name.'" class="form-control input-lg"   autocomplete="off" /> 
                        <span class="input-group-btn">
                            <button class="btn btn-default btn-lg" id="btnSubmit" type="submit" >'.$btnName.'</button>
                        </span>
                    </div><!-- /input-group -->
                </div><!-- /.col-lg-4 -->
            </div><!-- /.row -->';
    }

    /**
     * [static] Form -> Memo
     * @param $id               DIV ID
     * @param array $select     下拉內容
     * @param string $value     數值
     * @param string $size      DIV 大小
     * @param string $class     Class
     * @return string
     */
    public function memo($memo = '',$id = 'memo1',$size = '6', $label = '')
    {
        $out = '';
        if(!$label) $label = Lang::get('sys_base.base_10018');
        if($label) $out .= '<div class="col-xs-1 text-right"><h5>'.$label.'：</h5></div>';
        $out .= '<div class="col-xs-'.$size.'" id="'.$id.'">';
        $out .= $memo;
        $out .= '</div>';
        return $out;
    }

    /**
     * [static] Form -> Text Input
     * @param $id               DIV ID
     * @param string $value     數值
     * @param string $size      DIV 大小
     * @param string $class     Class
     * @param string $isRead    Readonly
     * @return string
     */
    public static function text($id ,$value = '',$size = '6', $label = '', $class = '', $isRead = 0, $labelSzie = 1)
    {
        $out = '';
        $inputAry = ['id'=>$id,'class'=>'form-control '.$class,'autocomplete'=>'off'];
        if($isRead) $inputAry['readonly'] = 'readonly';
        if($label) $out .= '<div class="col-xs-'. $labelSzie .' text-right"><h5>'.$label.'：</h5></div>';
        $out .= '<div class="col-xs-'.$size.'">';
        $out .= \Form::text($id,$value,$inputAry);
        $out .= '</div>';
        return $out;
    }

    /**
     * [static] Form -> Text Input
     * @param $id               DIV ID
     * @param string $value     數值
     * @param string $size      DIV 大小
     * @param string $class     Class
     * @param string $isRead    Readonly
     * @return string
     */
    public static function textStr($id ,$value = '', $label = '', $class = '', $otherAry = [])
    {
        $out = '';
        $inputAry = ['id'=>$id,'class'=>'form-control '.$class];
        if(count($otherAry)) $inputAry += $otherAry;
        if($label) $out .= $label.'：';
        $out .= \Form::text($id,$value,$inputAry);

        return $out;
    }
    /**
     * [static] Form -> Text Input
     * @param $id               DIV ID
     * @param string $value     數值
     * @param string $size      DIV 大小
     * @param string $class     Class
     * @param string $isRead    Readonly
     * @return string
     */
    public static function textSelect($id ,$selectAry, $value = '', $label = '', $class = '', $otherAry = [])
    {
        $out = '';
        $inputAry = ['id'=>$id,'class'=>'form-control '.$class];
        if(count($otherAry)) $inputAry += $otherAry;
        if($label) $out .= $label.'：';
        $out .= \Form::select($id,$selectAry,$value,$inputAry);

        return $out;
    }

    /**
     * [static] Form -> Text Input Number
     * @param $id               DIV ID
     * @param string $value     數值
     * @param string $size      DIV 大小
     * @param string $min       最小值
     * @param string $max       最大值
     * @return string
     */
    public static function number($id ,$value = '',$size = '6', $min = 0, $max = 100, $class = '')
    {
        $out  = '<div class="col-xs-'.$size.'">';
        $out .= \Form::number($id,$value,['id'=>$id,'min'=>$min,'max'=>$max,'class'=>'form-control '.$class,'autocomplete'=>'off']);
        $out .= '</div>';
        return $out;
    }

    /**
     * [static] Form -> Text date
     * @param $id               DIV ID
     * @param string $value     數值
     * @param string $size      DIV 大小
     * @param string $class     Class
     * @return string
     */
    public static function date($id ,$value = '',$size = '6', $label = '', $class = '',$min = '',$max = '')
    {
        $out = '';
        if($label) $out .= '<div class="col-xs-1 text-right"><h5>'.$label.'：</h5></div>';
        $out .= '<div class="col-xs-'.$size.'">';
        $out .= '<div class="input-group date">';
        $out .= '<div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </div>';
        $out .= \Form::text($id,$value,['id'=>$id,'class'=>'form-control pull-right '.$class,'data-provide'=>'datepicker','min'=>$min,'max'=>$max,'autocomplete'=>'off']);
        $out .= '</div>';
        $out .= '</div>';
        return $out;
    }

    /**
     * [static] Form -> Text time
     * @param $id               DIV ID
     * @param string $value     數值
     * @param string $size      DIV 大小
     * @param string $class     Class
     * @return string
     */
    public static function time($id ,$value = '',$size = '6', $label = '', $class = '')
    {
        $out = '';
        if($label) $out .= '<div class="col-xs-1 text-right"><h5>'.$label.'：</h5></div>';
        $out .= '<div class="col-xs-'.$size.'">';
        $out .= '<div class="input-group timepicker">';
        $out .= '<div class="input-group-addon">
                    <i class="fa fa-clock-o"></i>
                  </div>';
        $out .= \Form::text($id,$value,['id'=>$id,'class'=>'form-control pull-right '.$class,'data-provide'=>'timepicker','autocomplete'=>'off']);
        $out .= '</div>';
        $out .= '</div>';
        return $out;
    }

    /**
     * [static] Form -> Text Password
     * @param $id               DIV ID
     * @param string $size      DIV 大小
     * @param string $class     Class
     * @return string
     */
    public static function pwd($id ,$size = '6', $class = '')
    {
        $out  = '<div class="col-xs-'.$size.'">';
        //$out .= \Form::password($id,['class'=>'form-control '.$class]);
        $out .= \Form::input('password', $id, '123456',['id'=>$id,'class'=>'form-control '.$class,'autocomplete'=>'off']);
        $out .= HtmlLib::Color(Lang::get('sys_base.base_pwdmemo'),'red',1);
        $out .= '</div>';
        return $out;
    }

    /**
     * [static] Form -> TextArea CKEditor
     * @param $id               DIV ID
     * @param string $size      DIV 大小
     * @param string $value     內容
     * @return string
     */
    public static function ckeditor($id ,$value = '',$size = '6')
    {
        $out  = '<div class="col-xs-'.$size.'"><div class="pad">
              ';
        $out .= '<textarea id="'.$id.'" name="'.$id.'" class="form-control " rows="10" cols="80" data-provide="ckeditor">'.$value.'</textarea>';
        $out .= '</div></div>';
        return $out;
    }

    /**
     * [static] Form -> Checkbox
     * @param $id               DIV ID
     * @param string $size      DIV 大小
     * @param string $value     內容
     * @param string $class     Class
     * @return string
     */
    public static function checkbox($id ,$value = '',$check = 0, $class = '',$idname = '', $onclick = '',$disabled = '')
    {
        $valueAry = ['id'=>$idname,'class'=>' '.$class,'onclick'=>$onclick];
        if($disabled) $valueAry['disabled'] = $disabled;
        $out = \Form::checkbox($id, $value, $check,$valueAry);
        return $out;
    }

    /**
     * [static] Form -> Radio
     * @param $id               DIV ID
     * @param string $check     是否已選
     * @param string $value     內容
     * @param string $class     Class
     * @return string
     */
    public static function radio($id ,$value = '',$check = 0, $class = '')
    {
        $out = \Form::radio($id, $value, $check,['class'=>'form-control '.$class]);
        return $out;
    }

    /**
     * [static] Form -> File Input
     * @param $id               DIV ID
     * @param string $helper    提示文字
     * @return string
     */
    public static function file($id,$helper = '',$isImg = 1)
    {
        $file_upload_limit = config('mycfg.file_upload_limit_name','10MB');
        $limitStr = \Lang::get('sys_base.base_filemax',['limit'=>$file_upload_limit]);
        $filememo = ($isImg)? (($isImg == 2)? 'base_imgmemo2' : 'base_imgmemo') : 'base_filememo';
        $helper = ($helper)? $helper : \Lang::get('sys_base.'.$filememo).$limitStr;//請選擇一個檔案上傳

        $out  = \Form::file($id);
        $out .= '<p class="help-block">'.$helper.'</p>';
        return $out;
    }

    /**
     * [static] Form -> TextArea Input
     * @param $id               DIV ID
     * @param string $label     標題
     * @param string $helper    提示文字
     * @param string $value     內容
     * @return string
     */
    public static function textarea($id,$label='',$helper='',$value = '')
    {
        return '<textarea id="'.$id.'" name="'.$id.'" class="form-control textarea" placeholder="'.$label.'"
                          style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;">'.$value.'</textarea>
              '. (($helper)? '<p class="help-block">'.$helper.'</p>' : '');
    }

    /**
     * [static] Form -> Select Input
     * @param $id               DIV ID
     * @param array $select     下拉內容
     * @param string $value     數值
     * @param string $size      DIV 大小
     * @param string $class     Class
     * @return string
     */
    public static function select($id,$select = array(''=>''),$value = '',$size = '6', $label = '', $class = '')
    {
        $out = '';
        if($label) $out .= '<div class="col-xs-1 text-right"><h5>'.$label.'：</h5></div>';
        $out .= '<div class="col-xs-'.$size.'">';
        $out .= \Form::select($id,$select,$value,['id'=>$id,'class'=>'form-control select2 '.$class]);
        $out .= '</div>';
        return $out;
    }

    /**
     * [static] Form -> canvas
     * @param string $html
     * @param int $size
     * @param int $offset
     */
    public function canvas($id = 'signature-pad',$size = 8,$offset = 2)
    {
        $this->out .= '<div class="row">
                <div class="col-lg-'.$size.' col-lg-offset-'.$offset.'" style="border-color: #5a6268">
                    <div id="wrapper" class="wrapper" >
                      <canvas id="signature-pad" class="signature-pad" height=400 height=600 ></canvas>
                    </div>
                </div><!-- /.col-lg-4 -->
            </div><!-- /.row -->';
    }

    /**
     * [static] Form -> Text hidden
     * @param $img              DIV ID
     * @param string $value     內容
     * @return string
     */
    public static function hidden($id,$value)
    {
        return \Form::hidden($id,$value,['id'=>$id]);
    }

    /**
     * [static] Form -> Image Div
     * @param $img              DIV ID
     * @param string $alert     如果沒有圖片，則顯示文字內容
     * @param int $showType     顯示方式 1:正常 2:圓角
     * @param int $size         DIV 大小
     * @param string $weight    長
     * @param string $height    寬
     * @param string $class     Class
     * @return string
     */
    public static function img($img,$alert = '',$showType = 1,$size = 12,$weight = '',$height = '',$class = 'img-responsive pad')
    {
        $out  = '<div class="col-xs-'.$size.'">';
        $out .= ($img) ? HtmlLib::img($img,$alert,$showType,$weight,$height,$class) : '';
        $out .= '</div>';
        return $out;
    }

    /**
     * [static] Form -> Submit
     * @param string $value     按鈕名稱
     * @param int $color        按鈕顏色
     * @param string $name      按鈕ＩＤ
     * @param string $class     Class
     * @param string $onclick   ＪＳ
     * @return mixed
     */
    public static function submit($value='',$color = 0, $name='btn_submit',$class="",$onclick='')
    {
        if(!$value) $value = \Lang::get('sys_btn.btn_9');
        //\Form Submit
        return \Form::submit($value,array("name"=>$name,"id"=>$name,"class"=>"btn btn-".(HtmlLib::getbgColor($color).' ').$class,"onclick"=>$onclick));
    }

    /**
     * Form -> Submit
     * @param string $value     按鈕名稱
     * @param int $color        按鈕顏色
     * @param string $name      按鈕ＩＤ
     * @param string $class     Class
     * @param string $onclick   ＪＳ
     * @return mixed
     */
    public function addSubmit($value='',$color = 0, $name='btn_submit',$class="",$onclick='')
    {
        if(!$value) $value = \Lang::get('sys_btn.btn_9');
        //\Form Submit
        $this->out .= \Form::submit($value,array("name"=>$name,"class"=>"btn btn-".(HtmlLib::getbgColor($color).' ').$class,"onclick"=>$onclick));
    }

    /**
     * [static] Form -> Link Button
     * @param string $url       網址
     * @param string $name      按鈕名稱
     * @param int $color        按鈕顏色
     * @param string $id        ID
     * @param string $class     Class
     * @param string $onclick   ＪＳ
     * @param string $target    Target
     * @param string $isbig     大按鈕
     * @return mixed
     */
    public static function linkbtn($url,$name,$color="0",$id = '',$class='',$onclick='',$target='',$isbig = false)
    {
        return  HtmlLib::btn($url,$name,$color,$id,$class,$onclick,$target,$isbig);
    }

    /**
     * Form -> Link Button
     * @param string $url       網址
     * @param string $name      按鈕名稱
     * @param int $color        按鈕顏色
     * @param string $id        ID
     * @param string $class     Class
     * @param string $onclick   ＪＳ
     * @param string $target    Target
     * @param string $isbig     大按鈕
     * @return mixed
     */
    public function addLinkBtn($url,$name,$color = "0",$id = '',$class = '',$onclick = '',$target = '',$isbig = false)
    {
        $this->out .= '&nbsp;'.HtmlLib::btn($url,$name,$color,$id,$class,$onclick,$target,$isbig);
        //多加 空白
        if($this->style == 2) $this->out .= '&nbsp;';
    }

    /**
     * Form -> Button Tag
     * @param string $id        ID
     * @param string $name      按鈕名稱
     * @param int $color        按鈕顏色
     * @param string $class     Class
     * @param string $onclick   ＪＳ
     * @param string $target    Target
     * @param string $datatoggle
     * @return mixed
     */
    public static function addButton($id,$name,$color = 0,$class="",$target='',$datatoggle = '',$onclick='')
    {
        $btncolor = ' btn-'.HtmlLib::getbgColor($color).' ';
        //Text
        $out  = '<button type="button" id="'.$id.'" class="btn '.$btncolor.$class.'" onclick="'.$onclick.'" data-toggle="'.$datatoggle.'" data-target="'.$target.'">';
        $out .= $name;
        $out .= '</button>';
        return $out;
    }

    //顯示頁數
    public function showNum($url,$show,$label='每頁顯示',$labelClass='',$inputClass='')
    {
        $inputHtml = \Form::select('showNum', ['10'=>'10','25'=>'25','50'=>'50','100'=>'100'],$show,array("class"=>"form-control","style"=>"width:5em;","onchange"=>"chgPage('".$url."',this.value);"));
        if($inputHtml)
        {
            $this->out .= '<div class="form-group text-right" style="float:right">
    				         <label for="showNum" class="'.$labelClass.'">'.$label.'</label>
    				            ';
            $this->out .= '<span id="showNum" class="'.$inputClass.'">';
            $this->out .= $inputHtml;
            $this->out .= '</span>筆';
            $this->out .= '</div>';
            if($this->style == 2) $this->out .= '&nbsp;';
        }
    }

    //Div Start
    public function divStart($algin='left',$moreClass='',$moreStyle='')
    {
        $this->out .= '<div class="form-group '.$moreClass.'" style="text-algin:'.$algin.';'.$moreStyle.'">';
    }

    //Add 隨意內容
    public function addString($string = '')
    {
        $this->out .= $string;
    }

    //Div End
    public function divEnd()
    {
        $this->out .= '</div>';
    }

    //Hr
    public function addHr()
    {
        $this->out .= '<hr class="hrBoot"/>';
    }

    //輸出結果
    public function output($isFinish=1)
    {
        if($isFinish){
            //\Form END
            $this->out .= '
           '.\Form::close();
        }
        return $this->out;
    }

    //輸出結果
    public static function close()
    {
        return \Form::close();
    }
}
