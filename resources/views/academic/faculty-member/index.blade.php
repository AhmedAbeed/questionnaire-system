@extends('layouts.app')

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('home'), 'active' => false],
            ['name' => 'الأكاديمية', 'url' => '#', 'active' => true],
            ['name' => 'أعضاء هيئة التدريس', 'url' => '#', 'active' => true],
        ]"
    />
@endsection

@section('content')
    <div class="row">
        <x-stat-card
            title="{{ __('Total Faculty Members') }}"
            icon="feather icon-user"
            badge-color="primary"
            id="total-faculty-members"
        />
    </div>

    <x-page-header
        :title="'أعضاء هيئة التدريس'"
        :page-description="'قائمة أعضاء هيئة التدريس بالجامعة'"
        :action-items="[]"
    />

    <div class="col-lg-12">
        <x-data-table
            :headers="['#', 'الاسم', 'الرقم القومي', 'الكلية', 'عدد المقررات', 'الأجراءات']"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'name', 'name' => 'name', 'searchable' => true],
                ['data' => 'national_id', 'name' => 'national_id', 'searchable' => true],
                ['data' => 'faculty', 'name' => 'faculty', 'searchable' => false],
                ['data' => 'total_courses', 'name' => 'total_courses', 'searchable' => false],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
            ]"
            :data-url="route('academic.faculty-member.dataTable')"
        />
    </div>
@endsection


@push('scripts')
<script src="{{ asset('assets/js/pages/faculty-member.js') }}"
        data-endpoints='@json([
            "statsEndpoint" => route("academic.faculty-member.stats"),
            "destroyEndpoint" => route("academic.faculty-member.destroy", ["faculty-member" => ":faculty-member"])
        ])'></script>
@endpush




