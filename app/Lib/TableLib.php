<?php namespace App\Lib;
/**
 * 負責產生Table
 *
 * @version v2.0.0
 * @date 2017/08/08
 */
class TableLib{

    public $out;
    public $path;

    //建構子
    function __construct($path="",$id="table1",$class="",$style="",$tableStyle = 1){
        $this->path = $path;
        $tableDefaultClass = ($tableStyle)? 'table table-bordered table-hover ' : '';
        //Form START
        $this->out = '<table id="'.$id.'" class=" '.$tableDefaultClass.$class.'" style="'.$style.'">';
	}

    //table header
    public function addHead($heads,$isFun=1)
    {
        if(is_array($heads))
        {
            $text1 = \Lang::get('sys_btn.btn_22'); //功能
            $this->out .= '<thead>
                              <tr>';
            foreach($heads as $value)
            {
                //參數
                $title  = (isset($value['title']))?$value['title']:"&nbsp;";
                $class  = isset($value['class'])? " class='".$value['class']."'":'';
                $style  = isset($value['style'])? " style='".$value['style']."'":'';
                $align  = isset($value['align'])? " align='".$value['align']."'" : '';
                //HTML
                $this->out .= '<th '.$align.$class.$style.'>'.$title.'</th>';
            }
            if($isFun) {
                $this->out .= '<th align=center >'.$text1.'</th>';
            }
            $this->out .= '</tr></thead>
                           ';
        }
    }

    //table body
    public function addBody($bodys)
    {

        if(is_array($bodys))
        {
            $text2 = \Lang::get('sys_btn.btn_13'); //修改
            $text3 = \Lang::get('sys_btn.btn_23'); //刪除
            foreach($bodys as $value)
            {
                $i = 0;
                $bgClass = ($i%2 == 0)? 'even' : 'odd' ;
                $this->out .= '<tr class="'.$bgClass.'">';
                foreach($value as $key1 => $value1)
                {
                    $i++;
                    $tmp   = '';
                    $name  = isset($value1['name'])? $value1['name'] : '';
                    $col   = isset($value1['col'])? " colspan=".$value1['col'] : '';
                    $row   = isset($value1['row'])? " rowspan=".$value1['row'] : '';
                    $blod  = isset($value1['b'])? " rowspan=".$value1['b'] : '';
                    $class = isset($value1['class'])? " ".$value1['class'] : '';
                    $align = isset($value1['align'])? " align=".$value1['align'] : '';
                    $style = isset($value1['style'])? " style='".$value1['style']."'" : '';
                    $label = isset($value1['label'])? $value1['label'] : -1;
                    $badge = isset($value1['badge'])? $value1['badge'] : -1;

                    if($key1 === 'edit')
                    {
                        $tmp .= '<td>
                                           <a class="" title="'.$text2.'" href="'.$this->path.'/'.$name.'/edit"><span class="glyphicon glyphicon-edit"> </span></a>     
                                       </td>';
                    }
                    elseif($key1 === 'fun')
                    {
                        $tmp .= '<td>
                                           <a class="" title="'.$text2.'" href="'.$this->path.'/'.$name.'/edit"><span class="glyphicon glyphicon-edit"> </span></a>     
                                           <a class="deleteLink" title="'.$text3.'" href="'.$this->path.'?delete='.$name.'"><span class="glyphicon glyphicon-trash"> </span></a>     
                                       </td>';
                    }
                    else
                    {

                        $tmp .= '<td '.$class.$style.$col.$row.$align.' class="'.$class.'">';
                        // label
                        if($label >= 0)
                        {
                            $tmp .= '<span class="label label-'.HtmlLib::getbgColor($label).'">';
                        }elseif($badge >= 0)
                        {
                            $tmp .= '<span class="badge badge-'.HtmlLib::getbgColor($badge).'">';
                        }
                        //blod
                        if($blod) $tmp .= '<b>';

                        //main content
                        $tmp .= $name;

                        //blod
                        if($blod) $tmp .= '</b>';
                        // label
                        if($label >= 0 || $badge >= 0)
                        {
                            $tmp .= '</span>';
                        }
                        $tmp .= '</td>';
                    }
                    $this->out .= $tmp;
                }
                $this->out .= '</tr>';
            }
        }
    }


    //輸出結果
    public function output()
    {
       $this->out .= '</table>';
       return $this->out;
    }
}
