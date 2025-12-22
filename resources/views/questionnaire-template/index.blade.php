@extends('layouts.app')


@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('admin.home'), 'active' => false],
            ['name' => 'الاستبيانات قوالب', 'url' => '#', 'active' => false],
            ['name' => 'عرض القوالب', 'url' => '#', 'active' => true],
        ]"
    />
@endsection

@section('content')
    <div class="row">
    <div class="row">
        <x-stat_card
                title="{{__('Total Questionnaire Templates')}}"
                icon="feather icon-database"
                badge-color="primary"
                id="total-questionnaire-templates"
            />
    </div>
    </div>

    <x-page-header
        :title="'الاستبيانات قوالب'"   
        :page-description="'الاستبيانات قوالب المتاحة'"
        :action-items="[
            ['type' => 'button', 'label' => 'إضافة قالب استبيان', 
             'icon' => 'fa fa-plus', 'class' => 'btn btn-primary',
             'url' => route('questionnaire.template.create')
             ]
        ]"
    />

    <!-- Start col -->
    <div class="col-lg-12">
        <x-data-table
            :headers="['#', 'الاسم', 'الوصف', 'الحالة', 'عدد الاستبيانات', 'التاريخ', 'الإجراءات']"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => false, 'orderable' => false],
                ['data' => 'name', 'name' => 'name'],
                ['data' => 'description', 'name' => 'description'],
                ['data' => 'status', 'name' => 'status'],
                ['data' => 'count', 'name' => 'count'],
                ['data' => 'created_at', 'name' => 'created_at'],
                ['data' => 'actions', 'name' => 'actions', 'searchable' => false, 'orderable' => false],
            ]"
            data-url="{{ route('questionnaire.template.dataTable') }}"
        />
    </div>
    <!-- End col -->

@endsection
@push('scripts')
<script 
    src="{{ asset('assets/js/pages/questionniare-template-index.js') }}"
    data-endpoints='@json([
        "statsEndpoint" => route("questionnaire.template.stats"),
    ])'>
</script>
@endpush

