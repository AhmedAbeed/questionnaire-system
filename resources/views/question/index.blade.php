@extends('layouts.app')

@push('styles')
    <link href="{{ asset('assets/plugins/dataTables/datatables.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('admin.home'), 'active' => false],
            ['name' => 'الأسئلة', 'url' => '#', 'active' => true],
        ]"
    />
@endsection

@section('content')
<div class="row">
        <x-stat_card
                title="{{__('Total Questions')}}"
                icon="feather icon-user"
                badge-color="primary"
                id="total-questions"
            />
            <x-stat_card
                title="{{__('Total Questions Types')}}"
                icon="feather icon-database"
                badge-color="secondary"
                id="total-question-types"
            />
            <x-stat_card
                title="{{__('Total Questions Categories')}}"
                icon="feather icon-bar-chart"
                badge-color="info"
                id="total-question-categories"
            />
    </div>

    <x-page-header
        :title="'الأسئلة'"
        :page-description="'الأسئلة المتاحة'"
        :action-items="[
            ['type' => 'button', 'label' => 'إضافة سؤال', 
             'icon' => 'fa fa-plus', 'class' => 'btn btn-primary',
             'url' => route('questions.create')
             ]
        ]"
    />

    <!-- Start col -->
    <div class="col-lg-12">
        <x-data-table
            :headers="['#', 'السؤال', 'الوصف', 'النوع', 'التصنيف', 'التاريخ', 'الإجراءات']"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'text', 'name' => 'text'],
                ['data' => 'description', 'name' => 'description'],
                ['data' => 'type', 'name' => 'type.name'],
                ['data' => 'category', 'name' => 'category.name'],
                ['data' => 'created_at', 'name' => 'created_at'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ]"
            data-url="{{ route('questions.dataTable') }}" 
        />
    </div>
    <!-- End col -->

    {{-- Modal for Question Options --}}
    <x-modal id="questionOptionsModal" title="خيارات السؤال">
        <div id="questionOptionsModalBody">
            <!-- سيتم تعبئة الخيارات هنا بواسطة جافاسكريبت -->
        
    </x-modal>
    
@endsection

@push('scripts')
<script 
    src="{{ asset('assets/js/pages/question-index.js') }}"
    data-endpoints='@json([
        "statsEndpoint" => route("questions.stats"),
    ])'>
</script>
@endpush






