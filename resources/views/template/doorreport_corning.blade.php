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
<body style="margin: 0;">
<div class="container-fluid" >

    <div class="text-center">
        <h4>{!! $title !!}<small>{!! $title_sub !!}</small></h4>
    </div>
    <!-- 報表內容 -->
    <div class="content">
        <div class="row">
            <div class="col-sm-6">作業內容描述(由申請人填寫)Work description ( fill by applicant) </div>
            <div class="col-sm-6">工單編號Work order No.：32432432443432</div>
        </div>
        <div class="header_hr">12333333333333333333333333333333333333</div>
        <div class="row">
            <div class="col-sm-2">填表日期Date：</div>
            <div class="col-sm-2">工單聯絡人Applicant：</div>
            <div class="col-sm-2">聯絡電話Phone Number：</div>
        </div>
        {!! $content !!}
    </div>
</div>
@if(isset($js))
    <script>{!! $js !!}</script>
@endif
<!-- End of Scripts -->
</body>
</html>
