@extends('layouts.app')

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('home'), 'active' => false],
            ['name' => 'الأكاديمية', 'url' => '#', 'active' => true],
            ['name' => 'الكليات', 'url' => '#', 'active' => true],
        ]"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Statistics Cards Row -->
        <div class="row">
                <x-stat-card
                    title="{{__('Total Faculties')}}"
                    icon="feather icon-user"
                    badge-color="primary"
                    id="total-faculties"
                />

                <x-stat-card
                    title="{{__('Total Programs')}}"
                    icon="feather icon-bar-chart"
                    badge-color="success"
                    id="total-programs"
                />
        </div>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <x-page-header
                    :title="'الكليات'"
                    :page-description="'الكليات الجامعية'"
                    :action-items="[]"
                />
            </div>
        </div>

        <!-- Data Table -->
        <div class="col-12">
            <x-data-table
                :headers="['#', 'الاسم', 'عدد البرامج', 'عدد الطلاب','عدد اعضاء التدريس', 'التاريخ', 'الأجراءات']"
                :columns="[
                    ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                    ['data' => 'name', 'name' => 'name', 'searchable' => true],
                    ['data' => 'total_programs', 'name' => 'total_programs', 'searchable' => false],
                    ['data' => 'total_students', 'name' => 'total_students', 'searchable' => false],
                    ['data' => 'total_faculty_members', 'name' => 'total_faculty_members', 'searchable' => false],
                    ['data' => 'created_at', 'name' => 'created_at', 'searchable' => false],
                    ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
                ]"
                data-url="{{ route('academic.faculties.dataTable') }}"
            />
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/pages/faculty.js') }}" 
        data-endpoints='@json(["statsEndpoint" => route("academic.faculties.stats")])'></script>
@endpush




