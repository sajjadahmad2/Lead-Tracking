@php
    $breadcrumb = [['name' => 'Statstics', 'url' => route('statstics')]];
@endphp
@extends('layouts.app')

@section('css')

@endsection
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('content')
    <div class="row ">
        @if (is_role() == 'company')
            <div class="col-md-12 ">
                <div class="row">
                    <form class="d-flex flex-wrap"style="gap:10px">
                        <div class="form-group col-md-3">
                            <label for="example-date-input" class="form-control-label">Date</label>
                            <input class="form-control" type="date" value="2018-11-23" id="example-date-input">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="example-month-input" class="form-control-label">Month</label>
                            <input class="form-control" type="month" value="2018-11" id="example-month-input">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="example-week-input" class="form-control-label">Week</label>
                            <input class="form-control" type="week" value="2018-W23" id="example-week-input">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="exampleFormControlSelect1">Example select</label>
                            <select class="form-control" id="exampleFormControlSelect1">
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                                <option>4</option>
                                <option>5</option>
                            </select>
                        </div>

                    </form>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card card-xxl-stretch-50 mb-5">
                    <div class="card-body" style=" width: 60% !Important;">

                        <div class="col-md-4">

                            @php
                                $href =
                                    'https://marketplace.gohighlevel.com/oauth/chooselocation?response_type=code&redirect_uri=' .
                                    route('authorization.gohighlevel.callback') .
                                    '&client_id=' .
                                    supersetting('crm_client_id') .
                                    '&scope=' .
                                    getCRMScopes();
                                $description = 'Connect to CRM Agency';
                                $auth = is_connected();
                                if ($auth) {
                                    $description =
                                        'Already Connected to CRM Agency! - ' . auth()->user()->agency_name ?? '';
                                }
                            @endphp
                            @include('htmls.elements.anchor', [
                                'href' => $href,
                                'description' => $description,
                            ])
                        </div>

                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
@section('js')

@endsection
