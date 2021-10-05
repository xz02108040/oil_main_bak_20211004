<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="no-cache" name="Cache-Control"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title_prefix', config('adminlte.title_prefix', ''))
@yield('title', config('adminlte.title', 'HTTC'))
@yield('title_postfix', config('adminlte.title_postfix', ''))</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.6 -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/bootstrap/css/bootstrap.min.css') }}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ url('css/font-awesome.min.css') }}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="{{ url('css/ionicons.min.css') }}">

    <link rel="stylesheet" href="{{ url('css/daterangepicker.css') }}">
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="{{ url('css/bootstrap-datepicker.min.css') }}">
    <!-- Bootstrap Color Picker -->
{{--    <link rel="stylesheet" href="{{ url('css/bootstrap-colorpicker.min.css') }}">--}}
    <!-- Bootstrap time Picker -->
    <link rel="stylesheet" href="{{ url('css/bootstrap-timepicker.min.css') }}">
    <!-- Select2 -->
{{--    <link rel="stylesheet" href="{{ url('css/select2.min.css') }}">--}}
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="css/iCheck/all.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/AdminLTE.min.css') }}">

    @if(config('adminlte.plugins.datatables'))
        <!-- DataTables -->
            <link rel="stylesheet" href="{{ url('css/datatables.min.css') }}">
    @endif

    @yield('adminlte_css')

    <!--[if lt IE 9]>
            <script src="{{ url('js/html5shiv.min.js') }}"></script>
            <script src="{{ url('js/respond.min.js') }}"></script>
    <![endif]-->
</head>
<body class="hold-transition @yield('body_class')">

@yield('body')
<!--[if !IE]> -->
{{--<script src="{{ asset('vendor/adminlte/plugins/jQuery/jquery-2.2.3.min.js') }}"></script>--}}
<script src="{{ url('js/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/bootstrap/js/bootstrap.min.js') }}"></script>
@if(config('adminlte.plugins.datatables'))
    <!-- DataTables -->
    <script src="{{ url('js/datatables.min.js') }}"></script>
@endif
<!-- <![endif]-->
<!--[if lte IE 9]>
<script
  src="https://code.jquery.com/jquery-1.9.1.min.js"
  integrity="sha256-wS9gmOZBqsqWxgIVgA8Y9WcQOa7PgSIX+rPA0VL2rbQ="
  crossorigin="anonymous"></script>
<! [endif]-->
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@yield('adminlte_js')

</body>
</html>
