<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta content="maximum-scale=1.0" name="viewport"/>
    {!! Html::style('css/bootstrap.min.css')!!}
    {!! Html::style('css/web031024x768.css')!!}
    <title>{{Lang::get('sys_base.base_title')}}</title>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
</head>
<body style="margin: 0;background: rgba(229, 228, 228, 1.0);">
<div class="web031024x768">
    <div style="height: auto; min-height: 100%;">
        <div class="web031024x768">
            <div style="width: 1024px; height: 100%; position:relative; margin:auto;">
                <div class="navigationbarbgb">
                </div>
                <div class="u627fu652cu5546u9580u7981u5237u5361u7cfbu7d71">
                    {!! $repot_title !!}
                </div>
                <div class="report_men_total">
                    <div class="report_men_total_title">
                        {!! $info_title1 !!}
                    </div>
                    <div class="report_men_total_unit">
                        {!! $info_unit1 !!}
                    </div>
                    <div id="total_men" class="report_men_total_cnt">
                        {!! $info_cnt1 !!}
                    </div>
                </div>
                <div class="report_car_total">
                    <div class="report_car_total_title">
                        {!! $info_title2 !!}
                    </div>
                    <div class="report_car_total_unit">
                        {!! $info_unit2 !!}
                    </div>
                    <div id="total_car" class="report_car_total_cnt">
                        {!! $info_cnt2 !!}
                    </div>
                </div>
                <img src="./images/report2/web031024x768-iconbtnorangebg-copy-3@2x.png" class="iconbtnorangebgcopy3"  />
                <img src="./images/report2/web031024x768-iconbtnorangebg-copy-4@2x.png" class="iconbtnorangebgcopy4"  />
                <img src="./images/report2/web031024x768-element--mainlogo.png" class="elementmainlogo"  />
                <img src="./images/report2/web031024x768-elementfooterbg.png" class="elementfooterbg"  />
                <img src="./images/report2/web031024x768-rectangle-2.png" class="rectangle2"  />
                <img src="./images/report2/web031024x768-subtitlebg.png" class="subtitlebg"  />
                <div class="u9580u7981u5100u9336u677f">
                    {!! $repot_sub_title !!}
                </div>

                <div id="reportshowgroup" class="report_group">
                    {!! $content !!}
                </div>


                <img src="./images/report2/web031024x768-elementarrow.png" class="elementarrow"  />
                <div class="u76eeu524du6642u95932019022312">
                    <span class="span1">{!! $info_title3 !!}</span><span class="span2">{!! $info_cnt3 !!}</span>
                </div>
                <img src="./images/report2/web031024x768-iconbtnorangebg-copy-5@2x.png" class="iconbtnorangebgcopy5"  />
                <img src="./images/report2/web031024x768-elementfooterlogo.png" class="elementfooterlogo"  />
                <img src="./images/report2/web031024x768-element--logos.png" class="elementlogos" />
            </div>


    </div>
</div>
@yield('js')
<!-- End of Scripts -->
</body>
</html>
