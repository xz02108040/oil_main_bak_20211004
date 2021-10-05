<!DOCTYPE html>
<html  lang="zh-Hant">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1" />
    <meta content="no-cache" name="Cache-Control"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />

    <title>{{Lang::get('sys_comp.COMP_TITLE').Lang::get('sys_base.base_title')}}</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css">
{{--    {!! Html::style('css/bootstrap.min.css')!!}--}}
{!! Html::style('css/signin.css')!!}
<!--[if !IE]> -->
    {{--<script src="{{ asset('vendor/adminlte/plugins/jQuery/jquery-2.2.3.min.js') }}"></script>--}}
    <script src="{{ url('js/jquery.min.js') }}"></script>
    <!-- <![endif]-->
    <!--[if lte IE 9]>
    <script
      src="https://code.jquery.com/jquery-1.9.1.min.js"
      integrity="sha256-wS9gmOZBqsqWxgIVgA8Y9WcQOa7PgSIX+rPA0VL2rbQ="
      crossorigin="anonymous"></script>
    <! [endif]-->
    {{--    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>--}}
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    @yield('css')

</head>
<body>
<div id="container" >
    <!--     <div id="container-fluid" > -->
    <!-- header -->
    <div id="header">
        @yield('header')
    </div>
    <!-- content -->
    <div id="content">
        <div class="row">
            @yield('content')
        </div>

    </div>

</div>
<!-- javaScriptDiv -->
<div id="jsDiv">
    <script type="text/javascript">
        @yield('jsDiv')
    </script>
</div>
</body>
</html>
