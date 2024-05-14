@php
    $breadcrumb = [['name' => 'Dashboard', 'url' => route('dashboard')]];
@endphp
@extends('layouts.app')

@section('css')

@endsection
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('content')
    <div class="row ">
        @if (is_role() == 'company')
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
