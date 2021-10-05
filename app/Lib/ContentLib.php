<?php namespace App\Lib;

use Lang;

/**
 * 負責adminlte元件
 *
 * @version v2.1.0
 * @date 2017/08/08
 */
class ContentLib{

    protected $out;

    public function __construct()
    {
        $this->out = '';
    }

    /**
     * 一行 元件
     * @param $item
     */
    public function rowTo($item)
    {
        $this->out .= '<div class="row">';
        $this->out .= (is_string($item)) ? $item : implode('',$item);
        $this->out .= '</div>';
    }


    public function box_table($tbTitle ,$tbCont,$color = 0,$size = 12)
    {
        return '<div class="col-xs-'.$size.'">
        <!-- general form elements -->
        <div class="box box-'.HtmlLib::getbgColor($color).'">
            <div class="box-header with-border">
              <h3 class="box-title">'.$tbTitle.'</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">'.$tbCont.'</div>
        </div>';
    }

    public function info_box($text='',$num = '',$unit ='',$icon='asterisk',$color=1,$href = '#', $target='_self')
    {
        return '<div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
            <a href="'.url($href).'" target="'.$target.'">
            <span class="info-box-icon '.$this->bgcolor($color).'"><i class="fa fa-fw fa-'.$icon.'"></i></span>
            </a>
            <div class="info-box-content">
              <span class="info-box-text">'.$text.'</span>
              <span class="info-box-number">'.$num.'<br/><small>'.$unit.'</small></span>
            </div>
            <!-- /.info-box-content -->
          </div>
          <!-- /.info-box -->
          </div>
          ';
    }

    public function box_form($title,$cont,$color = 0,$size = 12)
    {
        return '<div class="col-md-'.$size.'">
        <div class="box box-'.HtmlLib::getbgColor($color).'">
            <div class="box-header with-border">
              <h3 class="box-title">'.$title.'</h3>
            </div>
            <!-- /.box-header -->
            '.$cont.'</div>';
    }

    public function small_box($title,$cont,$icon = '',$color = 1,$uri = '#')
    {
        $more = __('sys_btn.btn_30');
        return '<div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box '.$this->bgcolor($color).'">
            <div class="inner">
              <h3>'.$cont.'</h3>

              <p>'.$title.'</p>
            </div>
            <div class="icon">
              <i class="ion ion-'.$icon.'"></i>
            </div>
            <a href="'.$uri.'" class="small-box-footer">'.$more.' <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->';
    }


    public static function genSolidBox($title,$cont,$color = 1,$type = 1)
    {
        $boxinfo = ContentLib::getBoxInfo($type);
        $info1   = $boxinfo['info1'];
        $info2   = $boxinfo['info2'];
        $info3   = $boxinfo['info3'];
        return '<div class="box box-'.ContentLib::boxcolor($color).' '.$info1.' box-solid">
                            <div class="box-header with-border">
                              <h3 class="box-title">'.$title.'</h3>
                
                              '.$info2.'
                              <!-- /.box-tools -->
                            </div>
                            <!-- /.box-header -->
                            <div class="box-body">
                              '.$cont.'
                            </div>
                            '.$info3.'
                            <!-- /.box-body -->
                        </div>';
    }

    public function solid_box_3_row( $type1,$size1,$title1,$cont1,$color1 = 1, $type2 = 1,$size2 = 0,$title2 = '',$cont2 = '',$color2 = 1, $type3 = 1,$size3 = 0,$title3 = '',$cont3 = '',$color3 = 1)
    {
        $box2 = $box3 = '';
        if(!is_numeric($size1) || $size1 < 0|| $size1 > 12) $size1 = 4;
        if(!is_numeric($size2) || $size2 < 0|| $size2 > 12) $size2 = 4;
        if(!is_numeric($size3) || $size3 < 0|| $size3 > 12) $size3 = 4;

        $box1 = '<div class="col-md-'.$size1.'">
                        '.$this->genSolidBox($title1,$cont1,$color1,$type1).'
                    </div>';

        if($title2){
            $box2 = '<div class="col-md-'.$size2.'">
                        '.$this->genSolidBox($title2,$cont2,$color2,$type2).'
                    </div>';
        }
        if($title3){
            $box3 = '<div class="col-md-'.$size3.'">
                        '.$this->genSolidBox($title3,$cont3,$color3,$type3).'
                    </div>';
        }


        return '<div class="row">
                    '.$box1.$box2.$box3.'
                </div>';
    }


    public function output()
    {
        return $this->out;
    }


    public static function getBoxInfo($type)
    {
        $ret = [];
        switch ($type)
        {
            case 4:
                //Loading
                $ret['info1'] = '';
                $ret['info2'] = '';
                $ret['info3'] = '<div class="overlay">
                                  <i class="fa fa-refresh fa-spin"></i>
                                </div>';
                break;
            case 3:
                //Expandable
                $ret['info1'] = 'collapsed-box';
                $ret['info2'] = '<div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
                                </button>
                              </div>';
                $ret['info3'] = '';
                break;
            case 2:
                //Removable
                $ret['info1'] = '';
                $ret['info2'] = '<div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i>
                                </button>
                              </div>';
                $ret['info3'] = '';
                break;
            case 1:
            default :
                //Collapsable
                $ret['info1'] = '';
                $ret['info2'] = '<div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                                </button>
                              </div>';
                $ret['info3'] = '';
                break;

        }
        return $ret;
    }

    public static function genModal($id = 'myModal',$title,$conent = '',$bgcolor = 1)
    {
        return '<!-- Modal -->
<div class="modal fade" id="'.$id.'" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">'.$title.'</h4>
      </div>
      <div class="modal-body">
        '.$conent.'
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-'.ContentLib::boxcolor($bgcolor).'" data-dismiss="modal">'.Lang::get('sys_btn.btn_20').'</button>
      </div>
    </div>
  </div>
</div>';
    }


    public static function boxcolor($color)
    {
        $ret = '';
        switch ($color)
        {
            case 0:
                $ret = 'default';
                break;
            case 1:
                $ret = 'primary';
                break;
            case 2:
                $ret = 'success';
                break;
            case 3:
                $ret = 'info';
                break;
            case 4:
                $ret = 'warning';
                break;
            case 5:
                $ret = 'danger';
                break;
        }

        return $ret;
    }

    public function bgcolor($color)
    {
        $ret = '';
        switch ($color)
        {
            case 1:
                $ret = 'bg-blue';
                break;
            case 2:
                $ret = 'bg-green';
                break;
            case 3:
                $ret = 'bg-aqua';
                break;
            case 4:
                $ret = 'bg-yellow';
                break;
            case 5:
                $ret = 'bg-red';
                break;


        }

        return $ret;
    }

}
