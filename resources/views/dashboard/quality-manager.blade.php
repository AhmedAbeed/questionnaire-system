@extends('layouts.app')

@section('breadcrumb')
<x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('home'), 'active' => true],
        ]"
    />@endsection

@section('content')

    <!-- Stats Cards -->  
    <div class="row">
        <x-stat-card 
            title="إجمالي الطلاب"
            icon="icon-user"
            badge_color="primary"
            id="total-students"
        />
        <x-stat-card 
            title="الاستبيانات النشطة"
            icon="icon-activity"
            badge_color="success"
            id="active-questionnaires"
        />
        <x-stat-card 
            title="إجمالي الاستجابات"
            icon="icon-bar-chart"
            badge_color="info"
            id="total-responses"
        />
    </div>

    <div class="row">
        <!-- Response Rate Chart -->
        <div class="col-12 col-md-8">
            <div class="card border-0 shadow-sm my-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <h4 class="card-title text-center mb-0">معدل الاستجابة</h4>
                        <div class="text-center">
                            <div class="btn-group" role="group" dir="ltr">
                                <button type="button" class="btn btn-outline-primary chart-period-btn me-2" data-period="weekly" aria-label="عرض البيانات الأسبوعية">أسبوعي</button>
                                <button type="button" class="btn btn-outline-primary chart-period-btn me-2 active" data-period="monthly" aria-label="عرض البيانات الشهرية">شهري</button>
                                <button type="button" class="btn btn-outline-primary chart-period-btn" data-period="yearly" aria-label="عرض البيانات السنوية">سنوي</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                        <div class="chart-container" style="position: relative; height: 300px;">
                            <canvas id="responseRateChart" aria-label="رسم بياني لمعدل الاستجابة"></canvas>
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.config = {
            statsEndpoint: "{{ route('quality-manager.dashboard.stats') }}",
            chartDataEndpoint: "{{ route('quality-manager.dashboard.chart-data') }}"
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="{{ asset('assets/js/pages/quality-manager.js') }}"></script>
@endpush

