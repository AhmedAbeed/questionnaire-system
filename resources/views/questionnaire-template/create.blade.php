@extends('layouts.app')

@push('styles')
<style>
    .search-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    input[type="checkbox"] {
        width: 18px;
        height: 18px;
        border: 2px solid #dee2e6;
        border-radius: 4px;
        cursor: pointer;
    }

    .toggle-switch {
        width: 44px;
        height: 24px;
    }
    .toggle-slider {
        background-color: #dee2e6;
        transition: .4s;
        border-radius: 34px;
    }
    .toggle-slider:before {
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .toggle-slider {
        background-color: #0d6efd;
    }
    input:checked + .toggle-slider:before {
        transform: translateX(20px);
    }

    .question-card {
        transition: all 0.2s ease;
    }
    .question-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .dd-handle {
        cursor: move;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .question-card:hover .dd-handle {
        opacity: 1;
    }

    [dir="rtl"] .dd-handle {
        margin-left: 0.5rem;
    }
    [dir="rtl"] .search-input {
        padding-right: 2.5rem;
    }

    /* Custom colored scrollbar for .overflow-auto */
    .overflow-auto::-webkit-scrollbar {
        width: 8px;
        background: #e9ecef;
    }
    .overflow-auto::-webkit-scrollbar-thumb {
        background: #931a23; /* Bootstrap 5 primary color */
        border-radius: 4px;
    }
    .overflow-auto::-webkit-scrollbar-thumb:hover {
        background: #7d161e; /* Bootstrap 5 primary hover color */
    }
    .overflow-auto {
        scrollbar-width: thin;
        scrollbar-color: #931a23 #e9ecef;
    }
</style>
@endpush

@section('breadcrumb')
<x-layouts.breadcrumbbar
    title="الرئيسية"
    :breadcrumbs="[
        ['name' => 'الرئيسية', 'url' => route('admin.home'), 'active' => false],
        ['name' => 'الاستبيان', 'url' => '#', 'active' => true],
        ['name' => 'نماذج الاستبيان إنشاء', 'url' => '#', 'active' => true],
    ]"
/>
@endsection

@section('content')
<x-page-header
    :title="'نماذج الاستبيان إنشاء'"
    :page-description="'نماذج الاستبيان التي يوجد بها طلاب شاركوا في الاستبيان'"
    :action-items="[]"

/>

<form id="editTemplateForm" action="#" method="POST">
    @csrf
    <!-- Template Settings Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-primary">
            <h5 class="card-title h6 text-white mb-0">إعدادات النموذج</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12">
                    <label for="templateName" class="form-label fw-bold">اسم النموذج <span class="text-danger">*</span></label>
                    <input type="text" id="templateName" name="template_name" placeholder="اسم النموذج" required class="form-control border-primary" />
                </div>
                <div class="col-12">
                    <label for="templateDescription" class="form-label fw-bold">وصف النموذج</label>
                    <textarea id="templateDescription" name="template_description" rows="3" class="form-control border-primary" placeholder="وصف مختصر"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">الحالة</label>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked />
                            <label class="form-check-label" for="is_active" id="activeStatusLabel">
                                <span class="badge bg-success" id="activeStatus">مفعل</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions Management Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-primary ">
            <h5 class="card-title h6 text-white mb-0">إدارة الأسئلة</h5>
        </div>
        <div class="card-body p-4">
        <div class="row g-4">
                        <!-- Available Questions -->
                        <div class="col-lg-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary">
                                    <h5 class="card-title h6 text-white mb-0">الأسئلة المتاحة</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3 position-relative">
                                        <input type="text" class="form-control search-input ps-5 border-primary" id="availableQuestionsSearch" placeholder="ابحث عن سؤال...">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-primary"><i class="fa fa-search"></i></span>
                                    </div>
                                    <div class="border rounded p-3 position-relative overflow-auto" style="max-height: 500px;">
                                        <div class="not-found-message text-center text-muted small py-4" id="availableQuestionsNotFound" style="display:none;">
                                            <i class="fa fa-eye fs-2 mb-2"></i><br>لا توجد أسئلة مطابقة
                                        </div>
                                        @foreach($questions as $question)
                                        <div class="card mb-2 question-card border-0 shadow-sm" data-question-id="{{ $question->id }}" data-question-options='@json($question->options)'>
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-start gap-2 flex-md-row flex-column">
                                                    <div class="flex-grow-1">
                                                        <p class="mb-1 small fw-medium">{{ $question->text }}</p>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            @php
                                                                $typeBadge = [
                                                                    'Single Choice' => [
                                                                        'text' => 'اختيار من متعدد',
                                                                        'bg' => 'bg-primary',
                                                                        'icon' => 'ri-radio-button-line'
                                                                    ],
                                                                    'Multiple Choice' => [
                                                                        'text' => 'اختيار متعدد',
                                                                        'bg' => 'bg-info',
                                                                        'icon' => 'ri-checkbox-multiple-line'
                                                                    ],
                                                                    'Text' => [
                                                                        'text' => 'نص',
                                                                        'bg' => 'bg-secondary',
                                                                        'icon' => 'ri-text'
                                                                    ],
                                                                    'Rating' => [
                                                                        'text' => 'تقييم',
                                                                        'bg' => 'bg-warning',
                                                                        'icon' => 'ri-star-line'
                                                                    ],
                                                                    'Likert Scale' => [
                                                                        'text' => 'مقياس ليكرت',
                                                                        'bg' => 'bg-success',
                                                                        'icon' => 'ri-scales-3-line'
                                                                    ],
                                                                    'Date' => [
                                                                        'text' => 'تاريخ',
                                                                        'bg' => 'bg-danger',
                                                                        'icon' => 'ri-calendar-line'
                                                                    ],
                                                                    'Time' => [
                                                                        'text' => 'وقت',
                                                                        'bg' => 'bg-danger',
                                                                        'icon' => 'ri-time-line'
                                                                    ],
                                                                    'File' => [
                                                                        'text' => 'ملف',
                                                                        'bg' => 'bg-dark',
                                                                        'icon' => 'ri-file-line'
                                                                    ],
                                                                    'Image' => [
                                                                        'text' => 'صورة',
                                                                        'bg' => 'bg-dark',
                                                                        'icon' => 'ri-image-line'
                                                                    ]
                                                                ];
                                                                $currentType = $typeBadge[$question->type->name] ?? [
                                                                    'text' => $question->type->name,
                                                                    'bg' => 'bg-primary',
                                                                    'icon' => 'ri-question-line'
                                                                ];
                                                            @endphp
                                                            <span class="badge {{ $currentType['bg'] }} small">
                                                                <i class="fa {{
                                                                    match($question->type->name) {
                                                                        'Single Choice' => 'fa-dot-circle',
                                                                        'Multiple Choice' => 'fa-check-square',
                                                                        'Text' => 'fa-font',
                                                                        'Rating' => 'fa-star',
                                                                        'Likert Scale' => 'fa-balance-scale',
                                                                        'Date' => 'fa-calendar',
                                                                        'Time' => 'fa-clock',
                                                                        'File' => 'fa-file',
                                                                        'Image' => 'fa-image',
                                                                        default => 'fa-question',
                                                                    }
                                                                }} me-1"></i>{{ $currentType['text'] }}
                                                            </span>
                                                            <span class="text-muted small">
                                                                <i class="fa fa-tag me-1"></i>{{ $question->category?->name ?? 'لا يوجد تصنيف' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column gap-1 ms-2">
                                                        <button type="button" class="btn btn-sm btn-info show-options-btn" data-question-id="{{ $question->id }}" title="عرض الخيارات">
                                                            <i class="fa fa-list-ul"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-primary add-question-btn" data-id="{{ $question->id }}">
                                                            <i class="fa fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Questions -->
                        <div class="col-lg-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary">
                                    <h5 class="card-title h6 text-white mb-0">الأسئلة المختارة</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3 position-relative">
                                        <input type="text" class="form-control search-input ps-5 border-primary" id="selectedQuestionsSearch" placeholder="ابحث عن سؤال مختار...">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-primary"><i class="fa fa-search"></i></span>
                                    </div>
                                    <div class="dd border rounded p-3 position-relative overflow-auto" id="nestable" style="max-height: 500px;">
                                        <div class="not-found-message text-center text-muted small py-4" id="selectedQuestionsNotFound" style="display:none;">
                                            <i class="fa fa-eye fs-2 mb-2"></i><br>لا توجد أسئلة مطابقة
                                        </div>
                                        <ol class="dd-list">
                                            <li class="dd-item empty-state">
                                                <div class="card mb-2 border-0 shadow-sm">
                                                    <div class="card-body p-4 text-center">
                                                        <div class="text-muted mb-2">
                                                            <i class="fa fa-inbox fs-1"></i>
                                                        </div>
                                                        <p class="mb-0 small text-muted">لا توجد أسئلة مختارة بعد</p>
                                                    </div>
                                                </div>
                                            </li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

        </div>
    </div>
    <div class="card-footer bg-transparent border-0">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#discardModal">
                <i class="fa fa-times me-2"></i>إلغاء
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-2"></i>حفظ
            </button>
        </div>
    </div>
</form>

<!-- Discard Changes Modal -->
<div id="discardModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="rounded-circle bg-warning-subtle text-warning p-3 d-inline-flex mb-3">
                    <i class="fa fa-exclamation-triangle fs-3"></i>
                </div>
                <h5 class="mb-2">إلغاء التغييرات</h5>
                <p class="text-secondary mb-4">هل أنت متأكد أنك تريد إلغاء التغييرات؟ سيتم إلغاء جميع التغييرات الغير محفوظة.</p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">متابعة التعديل</button>
                    <a href="{{ route('admin.home') }}" class="btn btn-warning">إلغاء التغييرات</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question Options Modal -->
<x-modal id="questionOptionsModal" title="خيارات السؤال" size="modal-lg">
    <div id="questionOptionsModalBody">
        <!-- سيتم تعبئة الخيارات هنا بواسطة جافاسكريبت -->
    </div>
    <x-slot name="footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
    </x-slot>
</x-modal>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/pages/questionnaire-template-create.js') }}" data-endpoints='@json(["storeEndpoint" => route("questionnaire.template.store")])'></script>
@endpush