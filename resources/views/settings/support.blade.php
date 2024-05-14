@php
    $breadcrumb = [['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Support', 'url' => '#']];
@endphp
@extends('layouts.app')
@section('title', 'Support')
@section('page-title', 'Support')

@section('content')
<style>
    .video-container {
    position: relative;
    width: 100%;
    min-height:362px;
    max-height: 500px;
    padding-top: 0%; /* 16:9 aspect ratio */
}

.video-container iframe {
    position: absolute;
    top: 2;
    left: 0;
    margin-top:-25%;
    width: 500px;
    height: 500px;
}
</style>
<div class="row">
    @if(is_role() == 'company')
    @foreach($data as $key => $value)
    <div class="col-md-6">
        <div class="card card-xxl-stretch-50 mb-5 d-flex justify-content-center align-items-start">
            <div class="card-body pt-5">
                <div class="card-title">
                    <h5>{{ $value['title'] }}</h5>
                </div>
                <div class="card-text" >
                    {!! $value['videourl'] !!}
                </div>
            </div>
        </div>
    </div>
    @if(($key + 1) % 2 == 0)
    </div>
    <div class="row">
    @endif
    @endforeach
    @endif
</div>


@endsection
