@extends('layouts.app')

@section('breadcrumb')
<x-layouts.breadcrumbbar
    title="الرئيسية"
    :breadcrumbs="[
        ['name' => 'الرئيسية', 'url' => route('admin.home'), 'active' => false],
        ['name' => 'الأسئلة', 'url' => '#', 'active' => true],
        ['name' => 'إنشاء أسئلة', 'url' => '#', 'active' => true],
    ]"
/>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    /* Likert Options Styling */
    .likert-option-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent !important;
    }
    
    .likert-option-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
        border-left-color: var(--bs-primary) !important;
    }
    
    .likert-option-card.new-option {
        animation: slideInFromTop 0.5s ease-out;
    }
    
    @keyframes slideInFromTop {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .likert-option-number {
        transition: all 0.3s ease;
    }
    
    .likert-option-card:hover .likert-option-number {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    .remove-likert-option-btn {
        transition: all 0.3s ease;
    }
    
    .remove-likert-option-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(220,53,69,0.3);
    }
    
    .likert-preview-item {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .likert-preview-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
        border-color: var(--bs-primary) !important;
    }
    
    .likert-preview-connector {
        padding: 0 10px;
    }
    
    .likert-preview-connector i {
        font-size: 18px;
        transition: all 0.3s ease;
    }
    
    .likert-preview-item:hover + .likert-preview-connector i,
    .likert-preview-connector:hover i {
        color: var(--bs-primary) !important;
        transform: scale(1.2);
    }
    
    /* Form control enhancements */
    .border-primary-subtle {
        border-color: rgba(var(--bs-primary-rgb), 0.2) !important;
    }
    
    .bg-light {
        background-color: rgba(var(--bs-light-rgb), 0.5) !important;
    }
    
    /* Card enhancements */
    .card.border-0.shadow-sm {
        box-shadow: 0 2px 10px rgba(0,0,0,0.08) !important;
    }
    
    .bg-light-subtle {
        background-color: rgba(var(--bs-light-rgb), 0.3) !important;
    }
    
    /* Button enhancements */
    .btn-primary.rounded-pill {
        box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.3);
        transition: all 0.3s ease;
    }
    
    .btn-primary.rounded-pill:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.4);
    }
</style>
@endpush

@section('content')

<x-page-header
    :title="'إنشاء أسئلة'"
    :page-description="'إنشاء أسئلة جديدة'"
    :action-items="[]"
/>

<form action="#" method="POST" id="questionsForm">
    @csrf
    
    <div id="questionsContainer" class="row g-4">
        <!-- First question will be added here -->
    </div>

    <div class="d-flex justify-content-between gap-3 mt-4 mb-5">
        <button type="button" class="btn btn-outline-primary rounded-3" id="addQuestion">
            <i class="fa fa-plus-circle me-1"></i> إضافة سؤال جديد
        </button>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.home') }}" class="btn btn-outline-secondary rounded-3">
                <i class="fa fa-times me-1"></i> إلغاء
            </a>
            <button type="submit" class="btn btn-primary rounded-3">
                <i class="fa fa-save me-1"></i> حفظ جميع الأسئلة
            </button>
        </div>
    </div>
</form>

<!-- Question Template -->
<template id="questionTemplate">
    <div class="col-12">
        <div class="question-item card shadow-sm border-0 rounded-4 mb-2">
            <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-primary mb-0">
                        <i class="fa fa-question-circle me-2"></i> سؤال <span class="question-number">1</span>
                    </h5>
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-3 remove-question">
                        <i class="fa fa-trash-alt me-1"></i> حذف السؤال
                    </button>
                </div>
            </div>
            <div class="card-body pt-3">
                <!-- Question Text -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-pen me-2"></i> نص السؤال
                    </label>
                    <textarea class="form-control form-control border border-primary question-text" name="questions[0][text]" rows="3" required></textarea>
                </div>

                <!-- Question Description -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-info-circle me-2"></i> وصف السؤال
                    </label>
                    <textarea class="form-control border border-primary  question-description" name="questions[0][description]" rows="2" placeholder="أدخل وصفاً للسؤال (اختياري)"></textarea>
                    <small class="text-muted">
                        <i class="fa fa-lightbulb me-1"></i> يمكنك إضافة وصف توضيحي للسؤال لمساعدة المستخدمين على فهمه بشكل أفضل
                    </small>
                </div>

                <!-- Question Type -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-list-alt me-2"></i> نوع السؤال
                    </label>
                    <select class="form-select border border-primary  question-type" name="questions[0][type_id]" required>
                        <option value="">اختر نوع السؤال</option>
                        @foreach($questionTypes as $type)
                            <option value="{{ $type->id }}" data-type="{{ $type->name }}">
                                {{ __($type->name) }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted mt-2 d-block instructor-note d-none">
                        <i class="fa fa-info-circle me-1"></i> يمكنك اختيار اسم المحاضر أو صورة المحاضر كخيارات للإجابة في الأسئلة الموجهة للمقررات
                    </small>
                </div>

                <!-- Question Category -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-folder me-2"></i> التصنيف
                    </label>
                    <div class="input-group">
                        <select class="form-select border border-primary  question-category" name="questions[0][category_id]">
                            <option value="">اختر التصنيف</option>
                            @foreach($questionCategories as $category)
                                <option value="{{ $category->id }}">{{ __($category->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Options Container (Dynamic) -->
                <div class="options-container mb-4  p-3 rounded-3" style="display: none;">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-check-square me-2"></i> خيارات الإجابة
                    </label>
                    <div class="options-list">
                        <div class="option-item mb-2">
                            <div class="input-group">
                                <input type="text" class="form-control border border-primary bg-white" name="questions[0][options][]" placeholder="أدخل الخيار">
                                <button type="button" class="btn btn-outline-danger remove-option">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary rounded-3 mt-3 add-option">
                        <i class="fa fa-plus me-1"></i> إضافة خيار
                    </button>
                </div>

                <!-- Likert Scale Options (Dynamic) -->
                <div class="likert-container mb-4" style="display: none;">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-gradient-primary text-white border-0 rounded-top-4">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-chart-bar me-3 fs-4"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">إعدادات مقياس ليكرت</h6>
                                    <small class="opacity-75">تخصيص خيارات المقياس والتصنيف</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <!-- Configuration Section -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-semibold text-primary mb-2">
                                            <i class="fa fa-sort-numeric-up me-2"></i> عدد النقاط
                                        </label>
                                        <select class="form-select form-select border-2 border-primary-subtle bg-light likert-points" name="questions[][likert_points]">
                                            <option value="3">3 نقاط</option>
                                            <option value="5" selected>5 نقاط</option>
                                        </select>
                                        <div class="form-text">
                                            <i class="fa fa-info-circle me-1"></i> عدد الخيارات المتاحة في المقياس
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-semibold text-primary mb-2">
                                            <i class="fa fa-sliders-h me-2"></i> نوع المقياس
                                        </label>
                                        <select class="form-select form-select border-2 border-primary-subtle bg-light likert-type" name="questions[][likert_type]">
                                            <option value="custom">مخصص</option>
                                            <option value="satisfaction">رضا</option>
                                            <option value="agreement">موافقة</option>
                                            <option value="importance">أهمية</option>
                                        </select>
                                        <div class="form-text">
                                            <i class="fa fa-info-circle me-1"></i> نوع التقييم المستخدم في المقياس
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Options Management Section -->
                            <div class="likert-options-container">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">
                                            <i class="fa fa-list-ol me-2 text-primary"></i> خيارات المقياس
                                        </h6>
                                        <small class="text-muted">أضف أو عدّل خيارات المقياس حسب احتياجاتك</small>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm rounded-pill add-likert-option">
                                        <i class="fa fa-plus me-1"></i> إضافة خيار
                                    </button>
                                </div>
                                
                                <div class="likert-options-list">
                                    <!-- Options will be dynamically added here -->
                                </div>
                                
                                <!-- Visual Preview Section -->
                                <div class="likert-preview mt-4">
                                    <div class="card border-0 bg-light-subtle">
                                        <div class="card-header bg-transparent border-bottom">
                                            <h6 class="fw-semibold text-muted mb-0">
                                                <i class="fa fa-eye me-2 text-primary"></i> معاينة المقياس
                                            </h6>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="likert-scale-preview">
                                                <!-- Preview will be dynamically updated -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script 
    src="{{ asset('assets/js/pages/questions.js') }}"
    type="text/javascript"
    data-endpoints='@json([
        "storeEndpoint" => route("questions.store"),
        "sessionRefreshEndpoint" => route("session.refresh-csrf"),
        "loginEndpoint" => route("login")
    ])'>
</script>
@endpush

