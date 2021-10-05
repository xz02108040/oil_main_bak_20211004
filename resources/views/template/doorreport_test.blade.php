<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta content="width=1920, maximum-scale=1.0" name="viewport"/>
    {!! Html::style('css/bootstrap.min.css')!!}
    {!! Html::style('css/httcdoor.css')!!}
    <title>{{Lang::get('sys_base.base_title')}}</title>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
</head>
<body style="margin: 0;background: rgba(229, 228, 228, 1.0);">
<div class="httcdoor">
    <div style="height: auto; min-height: 100%;">
        <div class="navigationbarbgb">
        </div>
        <div class="reporttitle">
            {!! $repot_title !!}
        </div>
        <!-- 目前廠內總人數 -->
        <div class="infogroup1">
            <div class="infotitle1">
                {!! $info_title1 !!}
            </div>
            <div class="info_cnt1">
                {!! $info_cnt1 !!}
            </div>
            <div class="info_unit1">
                {!! $info_unit1 !!}
            </div>
        </div>
        <div class="infoicon1">
            <div class="infoiconimgdiv">
                <img src="./images/report/blue-avatar_2@2x.png" class="infoiconimg"/>
            </div>
        </div>

        <!-- 目前場內車輛數 -->
        <div class="infogroup2">
            <div class="infotitle2">
                {!! $info_title2 !!}
            </div>
            <div class="info_cnt2">
                {!! $info_cnt2 !!}
            </div>
            <div class="info_unit2">
                {!! $info_unit2 !!}
            </div>
        </div>
        <div class="infoicon2">
            <div class="infoiconimgdiv">
                <img src="./images/report/blue-car_2@2x.png" class="infoiconimg" />
            </div>
        </div>
        <!-- 目前時間 -->
        <div class="infogroup3">
            <span class="span1">{!! $info_title3 !!} </span><span class="span2">{!! $info_cnt3 !!}</span>
        </div>
        <div class="infoicon3">
            <div class="infoiconimgdiv">
                <img src="./images/report/blue-time@2x.png" class="infoiconimg" />
            </div>
        </div>
        <!-- 目前時間 END -->

        <!-- 底部ＢＡＲ -->
        <div class="elementfooterbg">
            <img src="./images/report/web03-mask.png" class="mask" />
            <img src="./images/report/web03-layer-1-copy.png" class="layer1copy" />
            <img src="./images/report/web03-layer-1-1.png" class="layer11" />
        </div>
        <div class="elementfooterlogo">
            <img src="./images/report/footer_logo.png" class="footlogo" />
        </div>
        <!-- 左上角ＬＯＧＯ -->
        <div class="elementmainlogo">
            <img src="./images/report/web03-oval@2x.png" class="oval" />
            <img src="./images/report/web03-u4e2du6cb9@2x.png" class="u4e2du6cb9" />
        </div>

        <!-- 報表內容 -->
        <div class="content">
            <img src="./images/report/web03-rectangle-2.png" class="rectangle2"/>
            <img src="./images/report/web03-rectangle-2-1.png" class="rectangle21"/>
            <div class="reportsubtitle">
                {!! $repot_sub_title !!}
            </div>
            <div class="reportshowgroup">
            @foreach ($reptcnt as $val)
                <!-- NO.1.報表區塊 -->
                <div class="reportdiv{!! $val['no'] !!}">
                    <!-- 內容區塊 -->
                    <div class="reportcontent">
                        <div class="reportgroup">
                            <div class="reportgroupsub">
                                <!-- 背景方塊 -->
                                @if(isset($val['no']) && $val['no'] < 4)
                                    <div class="reporttopbg"></div>
                                @endif
                                <!-- 底部方塊 -->
                                <div class="reportcontentbg"></div>
                                <!-- 上方標題區塊 -->
                                <div class="reportheadbg"></div>
                                <!-- 分隔線 -->
                                <img src="./images/report/web03-line-2-4.png" class="linebar" />
                                <div class="repttitlesub">
                                    {!! $val['sub_title'] !!}
                                </div>
                                <div class="reptunit">
                                    {!! $val['unit'] !!}
                                </div>
                                <div class="reptcnt">
                                    {!! $val['cnt'] !!}
                                </div>
                                <div class="repttitle">
                                    {!! $val['title'] !!}
                                </div>
                                <!-- 圖片ＩＣＯＮ -->
                                <div class="reportgroupsubicon">
                                    <div class="subicondiv">
                                        @if(isset($val['type']) && $val['type'] == 2)
                                            <img src="./images/report/blue-car@2x.png" class="subiconimg" />
                                        @else
                                            <img src="./images/report/blue-avatar_3@2x.png" class="subiconimg" />
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- 按鈕區塊 -->
                        <div class="reportbutton">
                            <!-- 按鈕區塊底色 -->
                            <div class="reportbuttonbg"></div>
                            <!-- 按鈕區塊ICON -->
                            <div class="btnmorearrow">
                                <img src="./images/report/web03-btnmorearrow@2x.png" class="btnmorearrowimg" />
                            </div>
                            <!-- 按鈕區塊名稱 -->
                            <div class="reportbuttonname">
                                {!! $val['btn'] !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
            <div class="elementarrow">
                <div class="background">
                </div>
                <img src="./images/report/web03-elementarrow@2x.png" class="elementarrow1" />
            </div>
        </div>


    </div>
</div>
{!! Html::script('js/jquery.min.js') !!}
{!! Html::script('js/bootstrap.min.js') !!}
@if(isset($js))
    <script>{!! $js !!}</script>
@endif
<!-- End of Scripts -->
</body>
</html>
