@extends('layouts.app')

@section('breadcrumb')
<x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('admin.home'), 'active' => true],
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
            title="إجمالي الاستجابات"
            icon="icon-bar-chart"
            badge_color="info"
            id="total-responses"
        />
        <x-stat-card 
            title="الاستبيانات النشطة"
            icon="icon-bar-chart"
            badge_color="info"
            id="active-questionnaires"
        />
    </div>

    <!-- Question Bank and Chart -->
    <div class="row justify-content-center">
        <!-- Question Bank Stats -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-column flex-xl-row">
                        <h5 class="fw-bold m-0"><i class="bi bi-database-fill me-2"></i>بنك الأسئلة</h5>
                        <a href="{{ route('questions.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-gear-fill me-1"></i> إدارة
                        </a>
                    </div>
                    <!-- Total Questions -->
                    <div class="text-center mb-3 pb-2 border-bottom border-primary">
                        <p class="text-muted small mb-1"><i class="bi bi-question-circle me-1"></i>إجمال الأسئلة</p>
                        <h2 class="display-6 fw-bold text-primary mb-0">{{ $questionBankStats['totalQuestions'] }}</h2>
                    </div>
                    <!-- Question Types -->
                    <div class="mb-3 pb-2 border-bottom border-primary">
                        <p class="text-muted small mb-1"><i class="bi bi-question-circle me-1"></i>أنواع الأسئلة</p>
                        @if (!empty($questionBankStats['questionTypes']))
                            @foreach ($questionBankStats['questionTypes'] as $type)
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small"><i class="{{ $type['icon'] }} me-1"></i>{{ __($type['name']) }}</span>
                                        <span class="fw-bold text-secondary">{{ $type['count'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <!-- Most Used Categories -->
                    <div>
                        <p class="text-muted small mb-2"><i class="bi bi-tag-fill me-1"></i>الفئات الأكثر استخداماً</p>
                        @if (!empty($questionBankStats['categories']))
                            @foreach ($questionBankStats['categories'] as $category)
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small"><i class="{{ $category['icon'] }} me-1"></i>{{ __($category['name']) }}</span>
                                        <span class="badge bg-light text-dark">{{ $category['percentage'] }}%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-{{ $category['color'] }}" role="progressbar" style="width: {{ $category['percentage'] }}%;" aria-valuenow="{{ $category['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <!-- Add New Question Button -->
                    <div class="text-center mt-3 pt-1">
                        <a href="{{ route('questions.create') }}" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i> إضافة سؤال جديد
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Response Rate Chart -->
        <div class="col-12 col-md-8 mt-2 mt-md-0">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-xl-row gap-3">
                        <h4 class="card-title text-center mb-0">معدل الاستجابة</h4>
                        <div class="text-center">
                            <div class="btn-group" role="group" dir="ltr">
                                <button type="button" class="btn btn-outline-primary chart-period-btn me-2" data-period="weekly" aria-label="عرض البيانات الأسبوعية">أسبوعي</button>
                                <button type="button" class="btn btn-outline-primary chart-period-btn active me-2" data-period="monthly" aria-label="عرض البيانات الشهرية">شهري</button>
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
    <script src="{{ asset('assets/plugins/chart.js/chart.umd.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/admin.js') }}" 
        data-endpoints='@json(["statsEndpoint" => route("admin.dashboard.stats"), "chartDataEndpoint" => route("admin.dashboard.chart-data")])'></script>
@endpush