@extends('adminlte::master')

@section('adminlte_css')
    <link rel="stylesheet"
          href="{{ asset('vendor/adminlte/dist/css/skins/skin-' . config('adminlte.skin', 'blue') . '.min.css')}} ">
    @stack('css')
    @yield('css')
@stop

@section('body_class', 'skin-' . config('adminlte.skin', 'blue') . ' sidebar-mini ' . (config('adminlte.layout') ? [
    'boxed' => 'layout-boxed',
    'fixed' => 'fixed',
    'top-nav' => 'layout-top-nav'
][config('adminlte.layout')] : '') . (config('adminlte.collapse_sidebar') ? ' sidebar-collapse ' : ''))

@section('body')
    <div class="wrapper">

        <!-- Main Header -->
        <header class="main-header">
            @if(config('adminlte.layout') == 'top-nav')
                <nav class="navbar navbar-static-top" role="navigation">

                <div class="container">
                    <div class="navbar-header">
                        <a href="{{ url(config('adminlte.dashboard_url', '/')) }}" class="navbar-brand">
                            {!! config('adminlte.logo', '<b>Admin</b>LTE') !!}
                        </a>
                        <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                            <span class="sr-only">Toggle navigation</span>
                        </a>
                    </div>

                    <!-- Collect the nav links, forms, and other content for toggling -->
                    <div class="collapse navbar-collapse pull-left" id="navbar-collapse">
                        <ul class="nav navbar-nav">
                            @each('adminlte::partials.menu-item-top-nav', config('adminlte::adminlte.menu'), 'item')
                        </ul>
                    </div>
                    <!-- /.navbar-collapse -->
            @else
            <!-- Logo -->
            <a href="{{ url(config('adminlte.dashboard_url', 'home')) }}" class="logo">
                <!-- mini logo for sidebar mini 50x50 pixels -->
                <span class="logo-mini">{!! config('adminlte.logo_mini', '<b>A</b>LT') !!}</span>
                <!-- logo for regular state and mobile devices -->
                <span class="logo-lg">{!! \Session::get('user.store_name') !!}</span>
            </a>

            <!-- Header Navbar -->
            <nav class="navbar navbar-static-top" role="navigation">
                <!-- Sidebar toggle button-->
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                    <span class="sr-only">Toggle navigation</span>
                </a>
            @endif
                <!-- Navbar Right Menu -->
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <!-- 登入者資訊 -->
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="{{url('images/user-icon.png')}}" class="user-image" alt="User Image">
                                <span class="hidden-xs">{{Lang::get('sys_base.base_10007',['user'=>session('user.name',Lang::get('sys_base.base_10006'))])}}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <!-- User image -->
                                <li class="user-header">
                                    <img src="{{url('images/photo_users.svg')}}" class="img-circle" alt="User Image">
                                    <p>
                                        {{Lang::get('sys_base.base_10008',['user'=>session('user.name',Lang::get('sys_base.base_10006')),'user_title'=>session('user.user_title',Lang::get('sys_base.base_10009'))])}}
                                        <small>{{Lang::get('sys_base.base_10010',['user_subtitle'=>session('user.user_subtitle','')])}}</small>
                                    </p>
                                </li>
                                <!-- Menu Footer-->
                                <li class="user-footer">
                                    <div class="pull-right">
                                        <a href="{{url('myinfo')}}" class="btn btn-default btn-flat">{{Lang::get('sys_base.base_10013')}}</a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- 登出按鈕 -->
                        <li>
                            <a href="{{ url(config('adminlte.logout_url', 'logout')) }}">
                                <i class="fa fa-fw fa-power-off"></i> {{ __('sys_btn.btn_4') }}
                            </a>
                        </li>
                    </ul>
                </div>
                @if(config('adminlte.layout') == 'top-nav')
                </div>
                @endif
            </nav>
        </header>

        @if(config('adminlte.layout') != 'top-nav')
        <!-- Left side column. contains the logo and sidebar -->
        <aside class="main-sidebar">

            <!-- sidebar: style can be found in sidebar.less -->
            <section class="sidebar">

                <!-- Sidebar Menu -->
                <ul class="sidebar-menu" data-widget="tree">
                    @each('adminlte.partials.menu-item', $menu, 'item')
                </ul>
                <!-- /.sidebar-menu -->

            </section>
            <!-- /.sidebar -->

        </aside>
        @endif

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            @if(config('adminlte.layout') == 'top-nav')
            <div class="container">
            @endif

            <!-- Error / Session -->
                @if ($errors->any())
                    @foreach ($errors->all() as $error)
                        <div class="alert alert-warning alert-dismissible" >
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h4><i class="icon fa fa-ban"></i>{!! $error !!}</h4>
                        </div>
                    @endforeach
                @endif
                @if (Session::has('message'))
                    <br/>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h4><i class="icon fa fa-check"></i>{!! Session::get('message') !!}</h4>
                    </div>
                @endif
            <!-- /.Error / Session -->
            <!-- Content Header (Page header) -->
            <section class="content-header">
                @yield('content_header')
            </section>

            <!-- Main content -->
            <section class="content">

                @yield('content')

            </section>
            <!-- /.content -->
            @if(config('adminlte.layout') == 'top-nav')
            </div>
            <!-- /.container -->
            @endif
        </div>
        <!-- /.content-wrapper -->

    </div>
    <!-- ./wrapper -->
@stop

@section('adminlte_js')
    <script src="{{ url('js/ckeditor/ckeditor.js') }}"></script>
{{--    <script src="{{ url('js/select2.full.min.js') }}"></script>--}}
    <script src="{{ url('js/daterangepicker.js') }}"></script>
    <script src="{{ url('js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ url('js/bootstrap-datepicker.zh-TW.js') }}"></script>
    <script src="{{ url('js/bootstrap-datetimepicker.min.js') }}"></script>
{{--    <script src="{{ url('js/bootstrap-datetimepicker.zh-TW.js') }}"></script>--}}
    <script src="{{ url('js/bootstrap-colorpicker.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@2.3.2/dist/signature_pad.min.js"></script>
{{--    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>--}}
    <script src="{{ url('js/adminlte.min.js') }}"></script>
    <script src="{{ url('js/ppsign/vendor/modernizr.js') }}"></script>
    <script src="{{ url('js/ppsign/vendor.js') }}"></script>
    <script src="{{ url('js/ppsign/plugins.js') }}"></script>
    <script src="{{ url('js/ppsign/l398s.js') }}"></script>

    <!-- daterangepicker -->
    <link rel="stylesheet" href="{{ url('css/daterangepicker.css') }}">
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="{{ url('css/bootstrap-datepicker.min.css') }}">
    <!-- Bootstrap Color Picker -->
    <link rel="stylesheet" href="{{ url('css/bootstrap-colorpicker.min.css') }}">
    <!-- Bootstrap time Picker -->
    <link rel="stylesheet" href="{{ url('css/bootstrap-timepicker.min.css') }}">
    <!-- Style by Dooradmin -->
    <link rel="stylesheet" href="{{ url('css/httcdoor_main.css') }}">
    <!-- Select2 -->
{{--    <link rel="stylesheet" href="{{ url('css/select2.min.css') }}">--}}
    @stack('js')
    @yield('js')
@stop
