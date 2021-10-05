<?php namespace App\Lib;
use App\Model\Factory\b_factory;
use App\Model\sys_param;
use Lang;

/**
 * 負責產生HTML 元件
 *
 * @version v2.1.0
 * @date 2017/08/08
 */
class HtmlLib{

    /**
     * 產生GOOGLE MAP IFRAME
     */
    public static function genMapIframe($GPSX,$GPSY)
    {
        if(!$GPSX) return '';

        $GPS = $GPSX.','.$GPSY;

        return '<iframe width="400" height="400" src="https://www.google.com/maps?q='.$GPS.'&z=16&t=&hl=zh-TW&output=embed"></iframe>';
        ;
    }
    /**
     * 產生空白
     */
    public static function genSpaceHtml($num = 1)
    {
        $ret = '';
        for($i = 1; $i < $num; $i++)
        {
            $ret .= '＿';
        }
        return $ret;
    }

    /**
     * 產生 儀表板「1024X768」報表大小
     * @param int $no
     * @param $title
     * @param $cnt
     * @param string $href
     * @return string
     */
    public static function genHttcPermitReportDiv_1024($no = 1,$title,$title_sub,$cnt,$href = '#')
    {
        $no         = (in_array($no,[1,2,3,4,5,6]))? $no : 1;
        $level      = ($no > 3)? 2 : 1; //第一層 是人，第二層是車
        $bgno       = ($level == 1)? 2 : 2; //2: 藍色 3:灰色
        $unit       = ($level == 1)? Lang::get('sys_base.base_40311') : Lang::get('sys_base.base_40311');
        $imgDiv     = ($level == 1)? 'blue-avatar_3@2x.png' : 'blue-avatar_3@2x.png';
        $btn        = Lang::get('sys_base.base_40213');

        $bgTitle = '<!-- 標題後背景 -->
                    <div class="titlebg1"></div>
                    <!-- 內容背景 -->
                    <div class="reportcontentbg"></div>
                    <!-- 標題背景 -->
                    <div class="titlebg'.$bgno.'"></div>';

        return '<div class="report_div'.$no.'">
                    <!-- 內容區塊 -->
                        <div class="report_show">
                    <!-- 內容區塊-2 -->
                            <div class="report_show_main">
                                '.$bgTitle.'
                                <div class="report_title">
                                    '.$title.'
                                </div>
                                <div class="report_unit">
                                    '.$unit.'
                                </div>
                                <div class="report_memo">
                                    '.$cnt.'
                                </div>
                                <div class="report_subtitle">
                                    '.$title_sub.'
                                </div>
                                <div class="report_icon">
                                    <img src="./images/report/'.$imgDiv.'" class="ovalicon"  />
                                </div>
                            </div>
                        </div>
                        <!-- 按鈕區塊 
                        <div class="report_btn_div">
                            <a href="'.$href.'">
                            <img src="./images/report2/web031024x768-btnmore-copy-2.png" class="btnmore"  />
                            <div class="report_btn">
                                '.$btn.'
                            </div>
                            </a>
                        </div>-->
                    </div>';
    }

    /**
     * 產生 儀表板「1024X768」報表大小
     * @param int $no
     * @param $title
     * @param $cnt
     * @param string $href
     * @return string
     */
    public static function genHttcDoorReportDiv_1024($mode,$no = 1,$title,$cnt,$href = '#')
    {
        $no         = (in_array($no,[1,2,3,4,5,6]))? $no : 1;
        $level      = ($no > 3)? 2 : 1; //第一層 是人，第二層是車
        $bgno       = ($level == 1)? 2 : 3; //2: 藍色 3:灰色
        $title_sub  = ($level == 1)? Lang::get('sys_base.base_40206') : Lang::get('sys_base.base_40207');
        $unit       = ($level == 1)? Lang::get('sys_base.base_40211') : Lang::get('sys_base.base_40212');
        $imgDiv     = ($level == 1)? 'data:image/png;base64,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' : 'data:image/png;base64,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';
        $btn        = Lang::get('sys_base.base_40213');

        if($mode == 'P'){
            $bgimg   = ($level == 2)? 'data:image/png;base64,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' : 'data:image/png;base64,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';
            $bgTitle = '<img src="'.$bgimg.'" class="titlebg_img" />';
        } else {
            $bgTitle = '<!-- 標題後背景 -->
                                <div class="titlebg1"></div>
                                <!-- 內容背景 -->
                                <div class="reportcontentbg"></div>
                                <!-- 標題背景 -->
                                <div class="titlebg'.$bgno.'"></div>';
        }

        return '<div class="report_div'.$no.'">
                    <!-- 內容區塊 -->
                        <div class="report_show">
                    <!-- 內容區塊-2 -->
                            <div class="report_show_main">
                                '.$bgTitle.'
                                <div class="report_title">
                                    '.$title.'
                                </div>
                                <div class="report_unit">
                                    '.$unit.'
                                </div>
                                <div class="report_memo">
                                    '.$cnt.'
                                </div>
                                <div class="report_subtitle">
                                    '.$title_sub.'
                                </div>
                                <div class="report_icon">
                                    <img src="'.$imgDiv.'" class="ovalicon"  />
                                </div>
                            </div>
                        </div>
                        <!-- 按鈕區塊 -->
                        <div class="report_btn_div">
                            <a href="'.$href.'">
                            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAARwAAAAuCAMAAADN71bAAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAEFUExURfLy8vLy8vLy8/Ly8/Ly8vLy8vLy8/Ly8kxpcfLy8/Ly8/Pz8/Hx8/Ly8vf39/Ly8/////Ly8vLy8vLy8/Ly8/Ly8/////T09P////Hx8/Hx8/Ly8/T09P////Ly8/f39/Pz8/Hx8vLy8/T09PHx8vPz8/Hx8naDk9TX3Ovr7fDw8dXY3LzCyuDi5XiElO/w8c3R1ZylsLzByayzvXqGlpihre7u8NLV2sPIzqauuOzs7uXm6OPl6Ofo6+nq7HyImPDx8neElKCps9fa3X+LmsnN05KcqZWfq5qjr3mFlc7S1+3u7+Di5I2YpYmUodve4YSQnu/v8IGNm3uHl4aRn5Gbp32JmD+aZ6IAAAAmdFJOU9mL1Pg9suafAL/9Kc5RIfwSep7nhdIHRwGok6xaBdUeffP+MN5tu9V4GAAAAPBJREFUaN7t18lSAlEMQNEojaRRQJwVRBGwbRxwBkecRVTEAf//U9xKmSzc3/sJp+olL6IaJMfWabjc7NKkqmh+DQur6YpKkMbBbnROkih4ZYR54zYjGLilwAEHHHDAAQcccMABh8D52zY4bhvRAThu+9HJFThereubd3C87p86z+B4dT8fY3Dcqfw9iMFxeul/nYFj9/bRO+dZ2d3evV4wkO2a7YdLVrnd7uHRKZ9Au82dvWPOB6f6VoPb6l+BAw444IADDjjggAMO/W5KqiB4rcoiCF7zMp5AwS5cEC2WcbCaWFFRzY6EQsOVCrVl1R+6jmcLTc1P3gAAAABJRU5ErkJggg==" class="btnmore"  />
                            <div class="report_btn">
                                '.$btn.'
                            </div>
                            </a>
                        </div>
                    </div>';
    }

    public static function genHttcDoorReportDiv_1920($mode,$no = 1,$title,$cnt,$href = '#')
    {
        $no         = (in_array($no,[1,2,3,4,5,6]))? $no : 1;
        $level      = ($no > 3)? 2 : 1; //第一層 是人，第二層是車
        $bgDiv      = ($level == 1)? '<div class="reporttopbg"></div>' : '';
        $title_sub  = ($level == 1)? Lang::get('sys_base.base_40206') : Lang::get('sys_base.base_40207');
        $unit       = ($level == 1)? Lang::get('sys_base.base_40211') : Lang::get('sys_base.base_40212');
        $imgDiv     = ($level == 1)? 'data:image/png;base64,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' : 'data:image/png;base64,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';
        $btn        = Lang::get('sys_base.base_40213');
        if($mode == 'P'){
            $bgimg   = ($level == 2)? 'data:image/png;base64,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' : 'data:image/png;base64,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';
            $bgTitle = '<img src="'.$bgimg.'" class="titlebg_img" />';
        }

        return '<div class="reportdiv'.$no.'">
                    <!-- 內容區塊 -->
                    <div class="reportcontent">
                        <div class="reportgroup">
                            <div class="reportgroupsub">
                                <!-- 背景方塊 -->
                                '.$bgDiv.'
                                <!-- 底部方塊 -->
                                <div class="reportcontentbg"></div>
                                <!-- 上方標題區塊 -->
                                <div class="reportheadbg"></div>
                                <!-- 分隔線 -->
                                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAfoAAAADCAYAAAB7/80sAAAAAXNSR0IArs4c6QAAAE1JREFUWAnt0DEOgEAMA8GchATPpeXV0RXUhndE48rtzkpydtdVRoAAAQIECIwSSCpH977/84wqE0OAAAECBAhUsl4MBAgQIECAwGCBDwEQEJfQug4IAAAAAElFTkSuQmCC" class="linebar" />
                                <div class="repttitlesub" id="repttitlesub'.$no.'">
                                    '.$title.'
                                </div>
                                <div class="reptunit" id="reptunit'.$no.'">
                                    '.$unit.'
                                </div>
                                <div class="reptcnt" id="reptcnt'.$no.'">
                                    '.$cnt.'
                                </div>
                                <div class="repttitle" id="repttitle'.$no.'">
                                    '.$title_sub.'
                                </div>
                                <!-- 圖片ＩＣＯＮ -->
                                <div class="reportgroupsubicon">
                                    <div class="subicondiv">
                                         <img src="'.$imgDiv.'" class="subiconimg" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- 按鈕區塊 -->
                        <div class="reportbutton">
                            <a href="'.$href.'">
                            <!-- 按鈕區塊底色 -->
                            <div class="reportbuttonbg"></div>
                            <!-- 按鈕區塊ICON -->
                            <div class="btnmorearrow">
                                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABwAAAAuCAYAAAA7v3kyAAAAAXNSR0IArs4c6QAABNVJREFUWAnNmH1oG3UYx39PLmlNinWyrhX9Qx3a+QL+oSJiu2nntM7NP9xYikOElGrXNCortnktHmteLlX/kLyMKAg6UYjKdDKZVK3vzHcUQTbnVGSMWqZDcdEmdz+f55JLkzTN7tor+qPk7vndc9/PPc89v+fuyth/NcYmkpJvIrFnpflAAF8k6VUULqkwYOl40OMBAL4ScEtRFAplcc7cvnDqeXF62lqeM3FHjZD0MMp+hbOnGOcC2QDsYIejY8fIiDNHtlmjDCRBXzixnXP2AuayiWxM6/v21lV3iQ/d+wfZZowqIAn6JtKbFJBfZZy1qACALwWL445YoH92RYAkOhZO3whMeYNzfn4RAkdsALdFQsO/LBdaKppqmcmQ+7AAwga8jyeLR/i6Alc+DMXT66o9jVsLUlop4Yul13JZmcJI19I8XsAsCNZeyT/0VaWfkf26EWoCkt99XABbNwP2Lc1hQa3hhcK7/mh6veZjdNsQSGLR4OBJh8N+MzD4hGys4FZZkd/0RtJbyDY6zgokQXFk4Le2c1pvxZS+pQI4s3Ou7McGcY9RYMN7WCsmZrNNuSMzL2KU2+gYnqzgryc+7tlb67uYbQhIItlsVvji6K9PYyG5NFFsEMF4yBPV7EZbw0ASQxj4IqnHcTsyLw5PTI57Hpm36+8tCahJecPJEEInNBsjfea6zvYHnE6nrM3VbpcFJDEsHLfCeBLDVrUQ+oq9s32n6HTO1cLIXjaQRLyRxE4sn2exmNRHGkKn2ppb7x4dve8vOl45TAGSIK1LzuWXcKHaycYldBgE4U5sHr+TrQ3TgCSI93QDpvZ1ag4qADuUFZpup+axIkASDUSS18qcH6I2SDam97iNN28Kj9//o2rTj9kjGE5cn2fwqVZISP3ZcVHLFaLL9bfp7y3+aHJ1QWZpXK3FqmVwBjjsIhgFZipQjCcuzOX5FN7Dq0pZO20RhK2xwNBHWhZNKxpfLHUZlxHG+SUkjlU6w6y23rhv19cajLa6nhaVJ9TbD0TS1ygF/oEGo3sGgqW7FmYK0BtO3VRQ5Pfwnl1Agvjc/M7GhC7JP3ys3sUtK0LsML1YiVMovEoVB/jcIrD1kdDQiXowmlsyEN9hd2A7O8AZd5Rg06tt9o2xgOfUYjCaX1KV4lv6AFd4BquxdMFwANdZn7dU+o2AhqsUYaP44TNZFgXY5+i6ul/s6Zn/PikfXLhjCOgNJ2LYsnyaDFggIQWGH8b2hcHqG7qAIueWXDSVxjQOarII2YOvFY9qtt7tWYGZTMb2w+zcPnzs9KmiGA3+7Y4HH3xSL6TSryFQzGQcudm5lzGNm0swGSPrjweHn6sUMbK/KNArZc7j+bmDKNZVhLF/cFH3YRpfMwKo9VU/Pmsnxcf2tufzhbdx/gb1GMCfVrBslUKeQ7W+Ru0FEQbDyYvzxe5xOYlhCk+BAJuxVX1mVLyef1WEY/HUldjxp9HxUnJG2AmLxbpRCrirOn49Ib1zZSA9pRWZURqLTRjgGL4E9UiBoe/1iunxU1sT/n/mFnwleAc7fpt6EsA3AjR343fgT3pEjPiovVTBUseufy6diGn82N7SskXc7TptREivrwp0dLYP5I7OdCCO29fYtomDrjN6BZbsR4ucPseWLPB/PfFf9FOa0xJl7JQAAAAASUVORK5CYII=" class="btnmorearrowimg" />
                            </div>
                            <!-- 按鈕區塊名稱 -->
                            <div class="reportbuttonname">
                                '.$btn.'
                            </div>
                            </a>
                        </div>
                    </div>
                </div>';
    }

    /**
     * 產生 有顏色的標籤區塊
     * @param string $str
     * @param int $color
     * @return string
     */
    public static function genLabel($str = '', $color = 0, $fontsize = 1.5)
    {
        $colorStr = HtmlLib::getbgColor($color);
        return '<span class="label label-'.$colorStr.'" style="font-size: '.$fontsize.'em">'.$str.'</span>';
    }

    /**
     * [ATL] 文章區塊 開始
     * @param $title
     * @param string $color
     * @return string
     */
    public static function genBoxStart($title,$color = '0')
    {
        $colorStr = HtmlLib::getbgColor($color);
        return '<div class="box box-'.$colorStr.' color-palette-box">
                    <div class="box-header with-border">
                      <h3 class="box-title text-'.$colorStr.'"><i class="fa fa-tag"></i>&nbsp;&nbsp;&nbsp;'.$title.'</h3>
                      <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                        <i class="fa fa-minus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">';
    }
    /**
     * [ATL] 文章區塊 開始
     * @param $title
     * @param string $color
     * @return string
     */
    public static function genBoxStart2($title,$color = '0', $blod = 0)
    {
        $blodsolid = ($blod)? 'box-solid' : '';
        $colorStr = HtmlLib::getbgColor($color);
        return '<div class="box box-'.$colorStr.' collapsed-box '.$blodsolid.'">
                    <div class="box-header with-border">
                      <h3 class="box-title text-'.$colorStr.'"><i class="fa fa-tag"></i>&nbsp;&nbsp;&nbsp;'.$title.'</h3>
                      <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" >
                        <i class="fa fa-plus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">';
    }

    /**
     * [ATL] 文章按鈕 結束
     * @return string
     */
    public static function genBoxEnd()
    {
        return '</div></div>';
    }

    /**
     * [ATL] ICON
     * @param string $icon
     * @return string
     */
    public static function genIcon($icon)
    {
        return '<i class="fa fa-fw fa-'.$icon.'"></i>';
    }

    /**
     * [BootStrap] 產生顏色Tag
     * @param int $c
     * @return string
     */
    public static function getbgColor($c = 0)
    {
        if($c == 1) //藍色
        {
            return 'primary';
        }elseif($c == 2) //綠色
        {
            return 'success';
        }elseif($c == 3) //天藍色
        {
            return 'info';
        }elseif($c == 4) //黃色
        {
            return 'warning';
        }elseif($c == 5) //紅色
        {
            return 'danger';
        }elseif($c == 6) //藏青
        {
            return 'navy';
        }elseif($c == 7) //藍綠色
        {
            return 'teal';
        }elseif($c == 8) //紫色
        {
            return 'purple';
        }elseif($c == 9) //酒紅色
        {
            return 'maroon';
        }elseif($c == 10) //黑色
        {
            return 'black';
        }else{
            return 'default'; //白色
        }
    }

    /**
     * 產生 地圖＿GPS位置
     * @param array $GPS
     * @return string
     */
    public static function genMap($GPS,$zoom = 12){
        if(count($GPS) && isset($GPS['GPSX']) && isset($GPS['GPSY']) && $GPS['GPSX'] && $GPS['GPSY'])
        {
            $MAPURL = config('map.map_url');
            $gps = $GPS['GPSY'].','.$GPS['GPSX'];
            //網址參數
            $url_location = '&location='.$gps;
            $url_size     = '&size=800*400';
            $url_zoom     = '&zoom='.$zoom;
            $url_maker    = '&markers=mid,,A:'.$gps;
            $url_key      = '&key='.config('map.map_key');
            //組合
            $url = $MAPURL.$url_location.$url_size.$url_zoom.$url_maker.$url_key;

            $ret = ($MAPURL)? '<div><iframe src="'.$url.'" width="100%" height="400px" scrolling="yes" frameborder="0"> </iframe></div>' : '';
        } else {
            $ret = '';
        }

        return $ret;
    }
    /**
     * 產生 標題
     * @param string $title 標題名稱
     * @return string
     */
    public static function genTitle($title)
    {
        $out  = '';
        $out .= '<div class="btn-toolbar" role="toolbar">';
        $out .= '  <div class="pull-left">';
        $out .= '     <h2><b>'.$title.'</b></h2>';
        $out .= '  </div>';
        $out .= '</div>';

        return $out;
    }

    /**
     * 產生 顏色 & 粗體 字型
     * @param string $str
     * @param string $color
     * @param int $blod
     *
     * @return string
     */
    public static function Color($str,$color="red",$blod=0){
        $ret = "";
        //若字串為空
        if(!strlen($str)) return $str;
        //組合
        $ret .= '<span style="color:'.$color.'">';
        if($blod) $ret.= '<b>';
        $ret .= $str;
        if($blod) $ret.= '</b>';
        $ret.= '</span>';
        //回傳
        return $ret;
    }

    /**
     * 產生 JavaScript Alert
     * @param string $str
     *
     * @return string
     */
    public static function toAlert($str,$isReturn = 'N'){
        $ret = "";
        //若字串為空
        if(!strlen($str)) return $ret;
        //組合
        $ret = 'alert("'.$str.'");';

        if($isReturn == 'Y')
        {
            $ret = 'return false;';
        }
        //回傳
        return $ret;
    }

    /**
     * 產生圖片 ＨＴＭＬ
     * @param $path
     * @param string $alt
     * @param int $show
     * @param string $w
     * @param string $h
     * @param string $class
     * @return mixed
     */
    public static function img($path,$alt="",$show=0, $w='',$h ='',$class = '')
    {
        $showClass = ($show == 1)? 'img-rounded ' : '';
        $styleAry = ['class'=>$showClass.$class,'loading'=>'lazy'];
        if($w) $styleAry['width'] = $w;
        if($h) $styleAry['height'] = $h;
        return \Html::image($path, $alt, $styleAry);
    }

    /**
     * 產生 ＬＩＮＫ按鈕
     * @param $url
     * @param $name
     * @param string $color
     * @param string $id
     * @param string $class
     * @param string $onclick
     * @param string $target
     * @param bool $isbig
     * @return string
     */
    public static function btn($url,$name,$color="0",$id = '',$class='',$onclick='',$target='',$isbig = false)
    {
        $colorclass = ($color > 5 & $color < 11)? ' bg-' : ' btn-';
        $btncolor = $colorclass.HtmlLib::getbgColor($color);
        $btnBig   = ($isbig)? ' btn-lg' : '';
        //\Form Cancel
        return  link_to($url, $name, array("id"=>$id,"class"=>"btn ".$class.$btncolor.$btnBig,"onclick"=>$onclick,"target"=>$target));
    }

    /**
    * 產生分頁
    * @param int $nowPage    現在第幾頁
    * @param int $maxPage    最多幾頁
    * @param int $href       網址
    * @param int $maxShowNum 顯示最多幾個按鈕
    * @param int $more       網頁參數
    *
    * @return string
    */
    public static function genPagination($nowPage,$maxPage,$href,$maxShowNum=10,$more="")
    {
        //$lastPage	上一頁
        //$nextPage	下一頁
        //$pageMax	每頁最大頁碼
        //$pageMin	每頁最小頁碼

        //預設每頁之分頁按鈕 最多顯示10個
        $maxShowNum  = ($maxShowNum > 1)? $maxShowNum : 10;
        $nowPage     = ($nowPage > 1)? $nowPage : 1;
        $maxPage     = ($maxPage > 1)? $maxPage : 1;
        //下一頁 & 上一頁
        $nextPage    = ($nowPage > $maxPage)?$maxPage:($nowPage + 1);
        $lastPage    = ($nowPage > 2)?($nowPage - 1):1;
        //頁面顯示最大頁數
        $pageRangMax = ceil($nowPage/$maxShowNum) * $maxShowNum;
        $pageRangMax = ($maxPage > $pageRangMax)? $pageRangMax : $maxPage;
        //頁面顯示最小頁數
        $pageRangMin = (floor($nowPage/$maxShowNum) * $maxShowNum) + (($nowPage%$maxShowNum==0)?($maxShowNum*-1):1);
        if($pageRangMin <= 0) $pageRangMin = 1;

        //產生頁碼連結
        $page_link  = "<div class='' style='text-align: center'>";
        $page_link .= "<nav>";
        $page_link .= "<ul class='pagination'>";
        if ($nowPage != 1) {
            $page_link .= "<li class=''>
                              <a href='".$href."?&page=".$lastPage.$more."' aria-label='Previous'>
                                <span aria-hidden='true'>&laquo;</span>
                              </a>
                           </li>";
       	}
        for ($i = $pageRangMin; $i <= $pageRangMax; $i++) {
            $class = ($i == $nowPage)?"active":"";
       	    $page_link .= "<li class='".$class."'><a href='".$href."?&page=".$i.$more."'>".$i."</a></li>";
       	}
        if ($nowPage != $maxPage) {
       	    $page_link .= "<li>
                              <a href='".$href."?&page=".$nextPage.$more."' aria-label='Next'>
                                <span aria-hidden='true'>&raquo;</span>
                              </a>
                           </li>";
       	}
        $page_link .= "</ul>";
        $page_link .= "</nav>";
        $page_link .= "</div>";
        //回傳頁碼連結
        return $page_link;
    }
}
