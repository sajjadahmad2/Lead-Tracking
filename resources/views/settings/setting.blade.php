@php
$breadcrumb = [['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Settings', 'url' => '#']];
@endphp
@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
@php
$isrole=is_role();
@endphp
<div class="row">
    @if($isrole == 'admin' )
    <div class="col-md-12 mx-auto">
        <div class="card card-xxl-stretch-50 mb-5 mb-xl-10">
            <div class="card-body pt-5">
                @include('htmls.form', $admin_form_fields, [
                'action' => route('setting.save'),
                'method' => 'POST',
                ])
                <div class="col-md-4 mt-5">

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
