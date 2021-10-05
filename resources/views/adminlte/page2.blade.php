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
    <div class="wrapper2" style="width:100%;min-height: 100vh;max-height:100%;background-color: #CDDAEE">

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper2">
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
            <section class="content" id="content-div" style="width:100%;height:100%;">

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
    <script src="{{ url('js/select2.full.min.js') }}"></script>
    <script src="{{ url('js/daterangepicker.js') }}"></script>
    <script src="{{ url('js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ url('js/bootstrap-datepicker.zh-TW.js') }}"></script>
    <script src="{{ url('js/moment.locales.js') }}"></script>
    <script src="{{ url('js/bootstrap-datetimepicker.min.js') }}"></script>
{{--    <script src="{{ url('js/bootstrap-datetimepicker.zh-TW.js') }}"></script>--}}
    <script src="{{ url('js/bootstrap-colorpicker.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>

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
    <link rel="stylesheet" href="{{ url('css/select2.min.css') }}">
    @stack('js')
    @yield('js')
@stop
