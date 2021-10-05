{{-- resources/views/admin/dashboard.blade.php --}}

@extends('template.doorreport1024_pc')

@section('title', __('sys_base.base_title'))


@section('content')
    {!! $content !!}
@stop

@section('js')
    @if(isset($js))
        <script>{!! $js !!}</script>
    @endif
@stop
