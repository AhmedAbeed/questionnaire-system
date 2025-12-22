@extends('layouts.app')

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('home'), 'active' => false],
            ['name' => 'الأكاديمية', 'url' => '#', 'active' => false],
            ['name' => 'المواد الدراسية', 'url' =>'#', 'active' => true],
        ]"
    />
@endsection

@section('content')
    <!-- Stat Card Row -->
    <div class="row">
        <x-stat_card
            title="{{ __('Total Courses') }}"
            icon="feather icon-user"
            badge-color="primary"
            id="total-courses"
        />
    </div>

    <!-- Page Header -->
    <x-page-header
        :title="'المواد الدراسية'"
        :page-description="'إدارة المواد الدراسية في النظام'"
        :action-items="[]"
    />

    <!-- Data Table -->
    <div class="col-lg-12">
        <x-data-table
            :headers="['#', 'كود المادة', 'اسم المادة', 'الكلية', 'عدد الطلاب', 'عدد الساعات', 'الاجراءات']"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'code', 'name' => 'code', 'searchable' => true],
                ['data' => 'name', 'name' => 'name', 'searchable' => true],
                ['data' => 'faculty', 'name' => 'faculty', 'searchable' => true],
                ['data' => 'students_count', 'name' => 'students_count', 'searchable' => false],
                ['data' => 'credit_hours', 'name' => 'credit_hours', 'searchable' => false],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
            ]"
            data-url="{{ route('academic.courses.dataTable') }}"
        />
    </div>
@endsection

@push('scripts')
<script 
    src="{{ asset('assets/js/pages/course.js') }}"
    data-endpoints='@json([
        "statsEndpoint" => route("academic.courses.stats"),
        "destroyEndpoint" => route("academic.courses.destroy", ["course" => ":course"])
    ])'>
</script>
@endpush

