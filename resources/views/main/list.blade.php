@php
    $breadcrumb = [['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => $page_title, 'url' => $page_route]];
@endphp
@extends('layouts.app')
@section('title', $page_title ?? 'No title')
@section('page-title', $page_title ?? 'No title')

@section('content')
    <div class="row">
            @if(strpos($page_route,'companylocation') !== false)
                @if (is_role() == 'admin')
                @include('components.dashboardcard',[
                    'data' => $locationCount,
                    'cardName'=>"Total Location"])
                @include('components.dashboardcard',[
                    'data' => $totalLeads,
                    'cardName'=>"Total Leads Demand"])
                @include('components.dashboardcard',[
                    'data' => $contactsCount,
                    'cardName'=>"Total Leads Delivered"])
                @else
                @include('components.dashboardcard',[
                    'data' => $totalLeads,
                    'cardName'=>"Total Leads Demand"])
                @include('components.dashboardcard',[
                    'data' => $contactsCount,
                    'cardName'=>"Total Leads Delivered"])
                @endif

            @endif
        <div class="col-md-12 text-right py-2 d-flex flex-row-reverse justify-content-between align-items-center" >
  @if(strpos($page_route,'companylocation') !== false && is_role()=='company')
                    {{-- @php
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
                <a href="{{ $href }}" class="btn btn-primary  py-3" style="float: right">{{$description}}</a> --}}
                <a href="{{ route($page_route . '.syncdata', ['id' => login_id()]) }}" class="btn btn-primary py-3" id="syncDataBtn" style="float: right">Sync Data</a>

                @else
                  <a href="{{ route($page_route . '.add') }}" class="btn btn-primary  py-3" style="float: right">Add
                {{ $page_title ?? 'Add New' }}</a>
  @endif
  @if(strpos($page_route,'user') !== false && is_role()=='admin')
  <a href="{{ route($page_route . '.syncCRMdata', ['id' => login_id()]) }}" class="btn btn-primary py-3" id="syncCRMDataBtn" style="float: right">Sync CRM Data</a>
  @endif
        </div>
        <div class="col-md-12 mx-auto">
            <div id="expbuttons"></div>
            <div class="card card-xxl-stretch-50 mb-5 mb-xl-10">
                <!--begin::Body-->
                <div class="card-body pt-5">
                    <div class="table-responsive">
                        <table id="kt_datatable" class="table table-row-bordered gy-5">
                            <thead>
                                <tr class="fw-semibold fs-6 text-muted">
                                    <td class="text-start"> Id </td>
                                    @foreach ($table_fields as $field)
                                        <td class="text-start">{{ $field }}</td>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>

                        </table>
                    </div>
                </div>
                <!--end::Body-->
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
       var table = $('#kt_datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route($page_route . '.list') }}",
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                @foreach ($table_fields as $key => $value)
                    {
                        data: '{{ $key }}',
                        name: '{{ $key }}',
                        @if ($key === 'action' || $key === 'Action')
                            searchable: false,
                            orderable: false
                        @endif
                    },
                @endforeach
            ]
        });
    $('#syncDataBtn').click(function(event){
        event.preventDefault();
        var url = $(this).attr('href');
        $('body').append('<div id="loader" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.8);z-index:1000;"><div style="position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);"><img src="{{asset('assets/img/Spinner.gif')}}" alt="Loading..."></div></div>');
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response){
                if(response.status == 'success'){
                    table.draw();
                }
            },
            error: function(xhr, status, error){

                alert('An error occurred: ' + error);
            },
            complete: function(){

                $('#loader').remove();
            }
        });
    });

    $('#syncCRMDataBtn').click(function(event){
        event.preventDefault();

        var url = $(this).attr('href');

        // Show loader
        $('body').append('<div id="loader" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.8);z-index:1000;"><div style="position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);"><img src="{{asset('assets/img/Spinner.gif')}}" alt="Loading..."></div></div>');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response){
                if(response.status == 'success'){
                    table.draw();
                }
            },
            error: function(xhr, status, error){

                alert('An error occurred: ' + error);
            },
            complete: function(){

                $('#loader').remove();
            }
        });
    });

    </script>
@endsection
