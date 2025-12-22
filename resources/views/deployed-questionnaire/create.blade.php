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

    /* Improve delete button visibility */
    .delete-btn {
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }
    
    .delete-btn:hover {
        opacity: 1;
        color: #dc3545 !important;
    }
    
    .dd-item:hover .delete-btn {
        opacity: 1;
    }
    
    /* Improve add button states */
    .add-question-btn:disabled {
        cursor: not-allowed;
    }
    
    /* Empty state styling */
    .empty-state .dd-handle {
        opacity: 0.5;
        text-align: center;
        padding: 2rem;
        color: #6c757d;
        font-style: italic;
    }
    
    /* Question card hover effects */
    .question-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .question-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nestable2@1.6.0/dist/jquery.nestable.min.css">
@endpush

@section('breadcrumb')
<x-layouts.breadcrumbbar
    title="الرئيسية"
    :breadcrumbs="[
        ['name' => 'الرئيسية', 'url' => route('admin.home'), 'active' => false],
        ['name' => 'الاستبيان', 'url' => '#', 'active' => true],
        ['name' => 'إنشاء استبيان منتشر', 'url' => '#', 'active' => true],
    ]"
/>
@endsection

@section('content')
<x-page-header
    :title="'إنشاء استبيان منتشر'"
    :page-description="'إنشاء استبيان منتشر بناءً على نموذج استبيان معين'"
    :action-items="[]"

/>

<form id="deployQuestionnaireForm" action="{{ route('questionnaires.deployed.store') }}" method="POST">
    @csrf
    
    <!-- Template Selection Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-primary">
            <h5 class="card-title h6 text-white mb-0">اختيار النموذج</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="template_type" id="useTemplate" value="template" checked>
                        <label class="form-check-label" for="useTemplate">
                            استخدام نموذج موجود
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="template_type" id="createNew" value="new">
                        <label class="form-check-label" for="createNew">
                            إنشاء استبيان جديد
                        </label>
                    </div>
                </div>
                
                <div class="col-12 template-selection" id="templateSelection">
                    <label for="template_id" class="form-label fw-bold">اختر النموذج <span class="text-danger">*</span></label>
                    <select class="form-select" id="template_id" name="template_id" required>
                        <option value="">اختر النموذج</option>
                        @foreach($activeQestionnaireTemplates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Audience Selection Card -->
    <div class="card mb-4 border-0 shadow-sm" id="audienceSelection">
        <div class="card-header bg-primary">
            <h5 class="card-title h6 text-white mb-0">تحديد الجمهور المستهدف</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <!-- Target Type Selection -->
                <div class="col-12">
                    <label for="target_type_id" class="form-label fw-bold">نوع المستهدف <span class="text-danger">*</span></label>
                    <select class="form-select" id="target_type_id" name="target_type_id" required>
                        <option value="">اختر نوع المستهدف</option>
                        @foreach($questionnaireTargetTypes as $type)
                            <option value="{{ $type->id }}" data-scope="{{ $type->scope }}" data-role="{{ $type->name }}">{{ __($type->name) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Students Target -->
                <div id="studentTarget" class="target-options" style="display: none;">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">تحديد المستهدفين</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input target-method" type="radio" name="student_target_method" id="byFaculty" value="faculty">
                                    <label class="form-check-label" for="byFaculty">
                                        حسب الكلية
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input target-method" type="radio" name="student_target_method" id="byProgram" value="program">
                                    <label class="form-check-label" for="byProgram">
                                        حسب البرنامج
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input target-method" type="radio" name="student_target_method" id="byCourse" value="course">
                                    <label class="form-check-label" for="byCourse">
                                        حسب المقرر
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Faculty Selection -->
                        <div class="col-md-6 faculty-selection" style="display: none;">
                            <label for="organizational_unit_id" class="form-label fw-bold">الكلية</label>
                            <select class="form-select" id="organizational_unit_id" name="organizational_unit_id">
                                <option value="">اختر الكلية</option>
                                @foreach($faculties as $faculty)
                                    <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Program Selection -->
                        <div class="col-md-6 program-selection" style="display: none;">
                            <label for="program_id" class="form-label fw-bold">البرنامج</label>
                            <select class="form-select" id="program_id" name="program_id">
                                <option value="">اختر البرنامج</option>
                            </select>
                        </div>

                        <!-- Course Selection -->
                        <div class="col-md-6 course-selection" style="display: none;">
                            <label for="course_selection_type" class="form-label fw-bold">اختيار المقررات</label>
                            <div class="d-flex gap-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input course-selection-type" type="radio" name="course_selection_type" id="specificEnrollment" value="specific" checked>
                                    <label class="form-check-label" for="specificEnrollment">
                                        تسجيل محدد
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input course-selection-type" type="radio" name="course_selection_type" id="allEnrollments" value="all">
                                    <label class="form-check-label" for="allEnrollments">
                                        جميع التسجيلات
                                    </label>
                                </div>
                            </div>

                            <!-- Specific Enrollment Selection -->
                            <div id="specificEnrollmentSelection">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="course_id" class="form-label fw-bold">اختر المقرر</label>
                                        <select class="form-select" id="course_id" name="course_id">
                                            <option value="">اختر المقرر</option>
                                            @foreach($courses as $course)
                                                <option value="{{ $course->id }}">{{ $course->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="enrollment_semester" class="form-label fw-bold">الفصل الدراسي</label>
                                        <select class="form-select" id="enrollment_semester" name="enrollment_semester">
                                            <option value="">اختر الفصل الدراسي</option>
                                            @foreach($semesters as $term)
                                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- All Enrollments Selection -->
                            <div id="allEnrollmentsSelection" style="display: none;">
                                <div class="row g-3">
                                    <!-- Faculty Scope Selection -->
                                    <div class="col-12">
                                        <label class="form-label fw-bold">نطاق الكليات</label>
                                        <div class="d-flex gap-3 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input faculty-scope" type="radio" name="faculty_scope" id="allFaculties" value="all" checked>
                                                <label class="form-check-label" for="allFaculties">
                                                    جميع الكليات
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input faculty-scope" type="radio" name="faculty_scope" id="specificFaculties" value="specific">
                                                <label class="form-check-label" for="specificFaculties">
                                                    كليات محددة
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Specific Faculties Selection -->
                                    <div id="specificFacultiesSelection" style="display: none;">
                                        <div class="col-12">
                                            <label class="form-label fw-bold">اختر الكليات</label>
                                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                                @foreach($faculties as $faculty)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="selected_faculties[]" id="fac{{ $faculty->id }}" value="{{ $faculty->id }}">
                                                    <label class="form-check-label" for="fac{{ $faculty->id }}">
                                                        {{ $faculty->name }}
                                                    </label>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Program Scope Selection -->
                                    <div class="col-12">
                                        <label class="form-label fw-bold">نطاق البرامج</label>
                                        <div class="d-flex gap-3 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input program-scope" type="radio" name="program_scope" id="allPrograms" value="all" checked>
                                                <label class="form-check-label" for="allPrograms">
                                                    جميع البرامج
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input program-scope" type="radio" name="program_scope" id="specificPrograms" value="specific">
                                                <label class="form-check-label" for="specificPrograms">
                                                    برامج محددة
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Specific Programs Selection -->
                                    <div id="specificProgramsSelection" style="display: none;">
                                        <div class="col-12">
                                            <label class="form-label fw-bold">اختر البرامج</label>
                                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;" id="programsList">
                                                <!-- Programs will be loaded dynamically -->
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Semester Scope Selection -->
                                    <div class="col-12">
                                        <label class="form-label fw-bold">نطاق الفصول الدراسية</label>
                                        <div class="d-flex gap-3 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input semester-scope" type="radio" name="semester_scope" id="allSemesters" value="all" checked>
                                                <label class="form-check-label" for="allSemesters">
                                                    جميع الفصول
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input semester-scope" type="radio" name="semester_scope" id="specificSemesters" value="specific">
                                                <label class="form-check-label" for="specificSemesters">
                                                    فصول محددة
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Specific Semesters Selection -->
                                    <div id="specificSemestersSelection" style="display: none;">
                                        <div class="col-12">
                                            <label class="form-label fw-bold">اختر الفصول الدراسية</label>
                                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                                @foreach($semesters as $term)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="selected_semesters[]" id="sem{{ $term->id }}" value="{{ $term->id }}">
                                                    <label class="form-check-label" for="sem{{ $term->id }}">
                                                        {{ $term->name }}
                                                    </label>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Level Selection -->
                        <div class="col-md-6 level-selection" style="display: none;">
                            <label for="student_level" class="form-label fw-bold">المستوى الدراسي</label>
                            <select class="form-select" id="student_level" name="student_level">
                                <option value="">اختر المستوى الدراسي</option>
                                <option value="1">المستوى الأول</option>
                                <option value="2">المستوى الثاني</option>
                                <option value="3">المستوى الثالث</option>
                                <option value="4">المستوى الرابع</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Teachers Target -->
                <div id="teacherTarget" class="target-options" style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="faculty_id" class="form-label fw-bold">الكلية</label>
                            <select class="form-select" id="faculty_id" name="faculty_id">
                                <option value="">اختر الكلية</option>
                                @foreach($faculties as $faculty)
                                    <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="department_id" class="form-label fw-bold">القسم</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">اختر القسم</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Workers Target -->
                <div id="workerTarget" class="target-options" style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="department_id" class="form-label fw-bold">الإدارة</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">اختر الإدارة</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="job_title" class="form-label fw-bold">المسمى الوظيفي</label>
                            <select class="form-select" id="job_title" name="job_title">
                                <option value="">اختر المسمى الوظيفي</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Questionnaire Settings Card -->
    <div class="card mb-4 border-0 shadow-sm" id="questionnaireSettings">
        <div class="card-header bg-primary">
            <h5 class="card-title h6 text-white mb-0">إعدادات الاستبيان</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-bold">اسم الاستبيان <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                

                <div class="col-md-6">
                    <label for="status" class="form-label fw-bold">حالة الاستبيان <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="draft">مسودة</option>
                        <option value="active">نشط</option>
                        <option value="closed">مغلق</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="open_date" class="form-label fw-bold">تاريخ البداية <span class="text-danger">*</span></label>
                    <input type="datetime-local" id="open_date" name="open_date" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="close_date" class="form-label fw-bold">تاريخ النهاية <span class="text-danger">*</span></label>
                    <input type="datetime-local" id="close_date" name="close_date" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="deployment_strategy" class="form-label fw-bold">الاستراتيجية التطويرية</label>
                    <select class="form-select" id="deployment_strategy" name="deployment_strategy">
                        <option value="single">واحد</option>
                        <option value="per_target">لكل مستهدف</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions Management Card -->
    <div class="card mb-4 border-0 shadow-sm" id="questionsManagement">
        <div class="card-header bg-primary">
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

    <!-- Submit Button -->
    <div class="text-end">
        <button type="button" class="btn btn-outline-primary me-2" id="previewBtn">
            <i class="fa fa-eye me-2"></i>معاينة
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save me-2"></i>حفظ الاستبيان
        </button>
    </div>
</form>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="previewModalLabel">معاينة الاستبيان</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="preview-container">
                    <h4 class="preview-title mb-4"></h4>
                    <div class="preview-questions"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
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
<script src="https://cdn.jsdelivr.net/npm/nestable2@1.6.0/dist/jquery.nestable.min.js"></script>
<script>
    window.routes = {
            templateEndpoint: '{{ route('questionnaire.template.data', ['id' => ':id']) }}',
            storeEndpoint: '{{ route('questionnaires.deployed.store') }}'
    };
</script>
<script src="{{ asset('assets/js/pages/deployedQuestionnaire.js') }}"></script>
@endpush
