@extends('layouts.app')

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('home'), 'active' => false],
            ['name' => 'الأكاديمية', 'url' => '#', 'active' => true],
            ['name' => 'الطلاب', 'url' => '#', 'active' => true],
        ]"
    />
@endsection

@section('content')
    <div class="row">
    <x-stat_card
                title="{{__('Total Students')}}"
                icon="feather icon-user"
                badge-color="primary"
                id="total-students"
            />
           
    </div>

    <x-page-header
        :title="'الطلاب'"
        :page-description="'قائمة الطلاب بالجامعة'"
        :action-items="[]"
    />

    <div class="col-lg-12">
        <x-data-table
            :headers="['#', 'الاسم', 'الرقم القومي', 'الرقم الأكاديمي', 'عدد التسجيلات', 'الأجراءات']"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'name', 'name' => 'name', 'searchable' => true],
                ['data' => 'national_id', 'name' => 'national_id', 'searchable' => true],
                ['data' => 'academic_id', 'name' => 'academic_id', 'searchable' => false],
                ['data' => 'enrollments_count', 'name' => 'enrollments_count', 'searchable' => false],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
            ]"
            :data-url="route('academic.student.dataTable')"
        />
    </div>
@endsection
@push('scripts')
<script 
    src="{{ asset('assets/js/pages/student.js') }}"
    data-endpoints='@json([
        "statsEndpoint" => route("academic.student.stats"),
        "destroyEndpoint" => route("academic.student.destroy", ["student" => ":student"])
    ])'>
</script>
@endpush




