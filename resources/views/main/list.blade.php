@php
    $breadcrumb = [['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => $page_title, 'url' => $page_route]];
@endphp
@extends('layouts.app')
@section('title', $page_title ?? 'No title')
@section('page-title', $page_title ?? 'No title')

@section('content')
    <div class="row">
        <div class="col-md-12 text-right py-2 d-flex flex-row-reverse justify-content-between align-items-center" >
            @if(strpos($page_route,'companylocation') == false)
            <a href="{{ route($page_route . '.add') }}" class="btn btn-primary  py-3" style="float: right">Add
                {{ $page_title ?? 'Add New' }}</a>
                @if (is_role() == 'company')
                @include('components.dashboardcard',[
                    'data' => $locationCount,
                    'cardName'=>"Total Location"])
                @include('components.dashboardcard',[
                    'data' => $totalLeads,
                    'cardName'=>"Total Leads Demand"])
                @include('components.dashboardcard',[
                    'data' => $contactsCount,
                    'cardName'=>"Total Leads Delivered"])
                    @endif
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
        $('#kt_datatable').DataTable({
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
    </script>
@endsection
