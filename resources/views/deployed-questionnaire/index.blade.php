@extends('layouts.app')

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('home'), 'active' => false],
            ['name' => 'الاستبيانات', 'url' => '#', 'active' => true],
        ]"
    />
@endsection

@section('content')
    <div class="row">
        <x-stat_card
            title="{{__('Total Deployed Questionnaires')}}"
            icon="feather icon-database"
            badge-color="primary"
            id="total-deployed-questionnaires"
        />
    </div>

    <x-page-header
        :title="'الاستبيانات'"
        :page-description="'الاستبيانات المتاحة'"
        :action-items="[
             ['type' => 'dropdown', 'label' => __('تصدير البيانات'),
                 'class' => 'btn btn-primary',
                 'items' => [
                     ['label' => __('تصدير قائمة الطلاب غير المستجيبين'), 'modal' => ['target' => 'exportNonRespondingModal']],
                 ]
             ],
             ['type' => 'button', 'label' => __('خيارات البحث'), 'icon' => 'fa fa-filter', 'class' => 'btn btn-secondary', 'id' => 'toggleFilters', 'data-icon' => 'fa-filter'],
        ]"
    />

    <div class="col-lg-12">

        <!-- Advanced Filter Section -->
        <div class="card border-0 shadow bg-white mb-4" id="filtersSection" style="display: none;">
            <div class="card-body p-4">
                <form id="filterForm" class="row g-4">
                    <!-- Basic Filters -->
                    <div class="col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-secondary border-0">
                                <h6 class="mb-0 text-white fw-bold">
                                    <i class="fa fa-list-ul me-2"></i>
                                    الفلاتر الأساسية
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">
                                        <i class="fa fa-poll me-1 text-primary"></i>
                                        الاستبيان
                                    </label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25">
                                            <i class="fa fa-search text-white"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control border-secondary border-opacity-25" 
                                               id="filterName" 
                                               placeholder="ابحث عن الاستبيان..."
                                               autocomplete="off"
                                               data-bs-toggle="tooltip"
                                               title="يمكنك البحث بالاسم أو الوصف">
                                        <div class="invalid-feedback">
                                            يرجى إدخال نص للبحث
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">
                                        <i class="fa fa-tags me-1 text-secondary"></i>
                                        نوع المستهدف
                                    </label>
                                    <select class="form-select border-secondary border-opacity-25 select2-target-type" id="filterTargetType">
                                        <option value="">الكل</option>
                                        @foreach($targetTypes as $type)
                                            <option value="{{ $type->id }}">{{ __($type->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Target Filters -->
                    <div class="col-lg-4">
                        <div class="card h-100 border-primary border-opacity-25 shadow-sm">
                            <div class="card-header bg-primary bg-opacity-10 border-0">
                                <h6 class="mb-0 text-white fw-bold">
                                    <i class="fa fa-bullseye me-2"></i>
                                    المستهدف
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- Target Type Toggle Buttons -->
                                <div class="btn-group w-100 mb-4" role="group">
                                    <input type="radio" class="btn-check" name="targetType" id="courseType" value="1" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary fw-semibold" for="courseType">
                                        <i class="fa fa-book me-1"></i> المقرر
                                    </label>

                                    @if(auth()->user()->hasRole('admin'))
                                    <input type="radio" class="btn-check" name="targetType" id="facultyType" value="3" autocomplete="off">
                                    <label class="btn btn-outline-primary fw-semibold" for="facultyType">
                                        <i class="fa fa-university me-1"></i> الكلية
                                    </label>
                                    @endif
                                </div>

                                <!-- Course Target -->
                                <div id="courseTargetSection" class="target-section mb-3">
                                    <div class="card border-primary border-opacity-50">
                                        <div class="card-header bg-primary bg-opacity-10 py-2 border-0">
                                            <h6 class="mb-0 text-white fw-bold fs-6">
                                                <i class="fa fa-book me-2"></i>المقرر
                                            </h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-book-open me-1 text-primary"></i>
                                                    المقرر
                                                </label>
                                                <select class="form-select border-primary border-opacity-25 select2-course" id="filterCourse">
                                                    <option value="">اختر المقرر</option>
                                                    @foreach($courses as $course)
                                                        <option value="{{ $course->id }}" data-code="{{ $course->code }}">{{ $course->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-0">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-calendar-alt me-1 text-primary"></i>
                                                    الفصل الدراسي
                                                </label>
                                                <select class="form-select border-primary border-opacity-25 select2-semester" id="filterSemester">
                                                    <option value="">اختر الفصل الدراسي</option>
                                                    @foreach($semesters as $semester)
                                                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Faculty Target -->
                                <div id="facultyTargetSection" class="target-section mb-3">
                                    <div class="card border-primary border-opacity-50">
                                        <div class="card-header bg-primary bg-opacity-10 py-2 border-0">
                                            <h6 class="mb-0 text-white fw-bold fs-6">
                                                <i class="fa fa-university me-2"></i>الكلية والبرامج
                                            </h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-building me-1 text-primary"></i>
                                                    الكلية
                                                </label>
                                                <select class="form-select border-primary border-opacity-25 select2-faculty" id="filterFaculty">
                                                    <option value="">اختر الكلية</option>
                                                    @foreach($faculties as $faculty)
                                                        <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-graduation-cap me-1 text-primary"></i>
                                                    البرنامج
                                                </label>
                                                <select class="form-select border-primary border-opacity-25 select2-program" id="filterProgram">
                                                    <option value="">اختر البرنامج</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date Filters -->
                    <div class="col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-info bg-opacity-10 border-0">
                                <h6 class="mb-0 text-white fw-bold">
                                    <i class="fa fa-calendar-o me-2"></i>
                                    الفترة الزمنية
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">
                                        <i class="fa fa-play me-1 text-info"></i>
                                        تاريخ البدء
                                    </label>
                                    <input type="datetime-local" 
                                           class="form-control border-info border-opacity-25" 
                                           id="filterStartDate">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">
                                        <i class="fa fa-stop me-1 text-info"></i>
                                        تاريخ الانتهاء
                                    </label>
                                    <input type="datetime-local" 
                                           class="form-control border-info border-opacity-25" 
                                           id="filterEndDate">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12">
                        <div class="border-top pt-4 mt-2">
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" id="resetFilters">
                                    <i class="fa fa-undo me-2"></i> إعادة تعيين
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <x-data-table
            :headers="['#', 'الاستبيان', 'نوع المستهدف', 'المستهدف', 'الفصل الدراسي', 'نسبة الإكمال', 'تاريخ البدء', 'تاريخ الانتهاء', 'تاريخ الإنشاء', 'الإجراءات']"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex'],
                ['data' => 'name', 'name' => 'name'],
                ['data' => 'target_type.name', 'name' => 'targetType.name'],
                ['data' => 'target', 'name' => 'target'],
                ['data' => 'semester', 'name' => 'semester'],
                ['data' => 'completion_rate', 'name' => 'completion_rate'],
                ['data' => 'open_date', 'name' => 'open_date'],
                ['data' => 'close_date', 'name' => 'close_date'],
                ['data' => 'created_at', 'name' => 'created_at'],
                ['data' => 'actions', 'name' => 'actions']
            ]"
            :filters="[
                ['name' => 'name', 'selector' => '#filterName'],
                ['name' => 'target_type', 'selector' => '#filterTargetType'],
                ['name' => 'course' , 'selector' => '#filterCourse'],
                ['name' => 'semester', 'selector' => '#filterSemester'],
                ['name' => 'faculty', 'selector' => '#filterFaculty'],
                ['name' => 'program', 'selector' => '#filterProgram'],
                ['name' => 'start_date', 'selector' => '#filterStartDate'],
                ['name' => 'end_date', 'selector' => '#filterEndDate']
            ]"
            data-url="{{ route('questionnaires.deployed.dataTable') }}"
        />
    </div>

    <x-modal id="editCloseDateModal" title="{{ __('تعديل تاريخ الإغلاق') }}">
        <form id="editCloseDateForm">
            <input type="hidden" id="questionnaireId" name="questionnaire_id">
            <div class="mb-3">
                <label for="closeDate" class="form-label">{{ __('تاريخ الإغلاق') }}</label>
                <input type="datetime-local" class="form-control" id="closeDate" name="close_date" required>
            </div>
        </form>

        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
            <button type="button" class="btn btn-primary" id="saveCloseDate">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                <span class="btn-text">{{ __('حفظ التغييرات') }}</span>
            </button>
        </x-slot>
    </x-modal>

    <x-modal id="exportNonRespondingModal" title="{{ __('Export Non-Responding Students') }}">
        <div class="alert alert-info mb-3">
            {{ __('This export will include students who are enrolled in courses this semester but have not completed the course surveys deployed for this semester.') }}
        </div>
        <form id="exportNonRespondingForm">
            <div class="mb-3">
                <label for="faculty" class="form-label">{{ __('الكلية') }}</label>
                <select class="form-select" id="faculty" name="faculty_id" required {{ auth()->user()->hasRole('faculty-dean') ? 'disabled' : '' }}>
                    <option value="">{{ __('اختر الكلية') }}</option>
                    @if(auth()->user()->hasRole('faculty-dean'))
                        <option value="{{ auth()->user()->faculty_id }}" selected>{{ auth()->user()->faculty->name }}</option>
                    @else
                        @foreach($faculties as $faculty)
                            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="mb-3">
                <label for="semester" class="form-label">{{ __('الفصل الدراسي') }}</label>
                <select class="form-select" id="semester" name="semester_id" required>
                    <option value="">{{ __('اختر الفصل الدراسي') }}</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
            <button type="button" class="btn btn-primary" id="exportNonResponding">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                <span class="btn-text">{{ __('تصدير') }}</span>
            </button>
        </x-slot>
    </x-modal>
@endsection
@push('scripts')
<script>
    window.routes = {
        statsEndpoint: "{{ route('questionnaires.deployed.stats') }}",
        updateCloseDate: "{{ route('questionnaires.deployed.update-close-date', ['id' => 'PLACEHOLDER_ID']) }}",
        exportNonResponding: "{{ route('questionnaires.deployed.export-non-responding') }}",
        programsByFaculty: "{{ route('faculties.programs') }}"
    };
</script>
<script src="{{ asset('assets/js/pages/questionnaire-deployed-index.js') }}"></script>
@endpush





