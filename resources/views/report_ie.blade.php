{{-- resources/views/admin/dashboard.blade.php --}}

@extends('adminlte.page_ie')

@section('title', __('sys_base.base_title'))

@section('content_header')
    @if(isset($title))
    <h1>{!! $title !!}</h1>
    @endif
@stop

@section('content')
    {!! $content !!}
@stop

@section('css')
    @if(isset($css))
    <style>{!! $css !!}</style>
    @endif
@stop

@section('js')
    @if(isset($js))
        <script>{!! $js !!}</script>
    @endif
@stop
