@extends('layouts.app')

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('home'), 'active' => false],
            ['name' => 'التسجيل', 'url' => '#', 'active' => true],
        ]"
    />
@endsection

@push('styles')
<style>
    .select2-container {
        z-index: 9999 !important;
    }

    .select2-dropdown {
        z-index: 9999 !important;
    }
</style>
@endpush

@section('content')
    <div class="row">
        <x-stat_card
                title="{{__('Total Enrollments')}}"
                icon="feather icon-database"
                badge-color="primary"
                id="total-enrollments"
            />
    </div>

    @if(auth()->user()->hasRole('admin'))
        <x-page-header
            :title="'التسجيل'"
            :page-description="'التسجيل التلقائي للطلاب'"
            :action-items="[
                ['type' => 'dropdown', 
                 'label' => 'تسجيل جديد', 
                 'icon' => 'fa fa-plus',
                 'class' => 'btn btn-primary',
                 'items' => [
                     ['label' => 'إضافة تسجيل واحد', 'icon' => 'fa fa-user-plus', 'modal' => ['target' => 'singleEnrollmentModal']],
                     ['label' => 'استيراد التسجيلات', 'icon' => 'fa fa-upload', 'modal' => ['target' => 'importModal']],
                     ['label' => 'استيراد المدرسين', 'icon' => 'fa fa-users', 'modal' => ['target' => 'importInstructorsModal']]
                 ]
                ],
                ['type' => 'button', 'label' => 'خيارات البحث', 'icon' => 'fa fa-filter', 'class' => 'btn btn-secondary', 'id' => 'toggleFilters', 'data-icon' => 'fa-filter']
            ]"
        />
    @else
        <x-page-header
            :title="'التسجيل'"
            :page-description="'التسجيل التلقائي للطلاب'"
            :action-items="[
                ['type' => 'dropdown', 
                 'label' => 'تسجيل جديد', 
                 'icon' => 'fa fa-plus',
                 'class' => 'btn btn-primary',
                 'items' => [
                     ['label' => 'إضافة تسجيل واحد', 'icon' => 'fa fa-user-plus', 'modal' => ['target' => 'singleEnrollmentModal']]
                 ]
                ],
                ['type' => 'button', 'label' => 'خيارات البحث', 'icon' => 'fa fa-filter', 'class' => 'btn btn-secondary', 'id' => 'toggleFilters', 'data-icon' => 'fa-filter']
            ]"
        />
    @endif

    <!-- Advanced Filter Section -->
    <div class="card border-0 shadow bg-white mb-4" id="filtersSection" style="display: none;">
        <div class="card-body p-4">
            <form id="filterForm" class="row g-8">
                <!-- Basic Filters -->
                <div class="col-lg-8">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-secondary border-0">
                            <h6 class="mb-0 text-white fw-bold">
                                <i class="fa fa-list-ul me-2"></i>
                                الفلاتر الأساسية
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-dark">
                                        <i class="fa fa-user me-1 text-primary"></i>
                                        اسم الطالب
                                    </label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25">
                                            <i class="fa fa-search text-white"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control border-secondary border-opacity-25" 
                                               id="filterStudentName" 
                                               placeholder="ابحث عن الطالب..."
                                               autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-dark">
                                        <i class="fa fa-id-card me-1 text-primary"></i>
                                        الرقم القومي
                                    </label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25">
                                            <i class="fa fa-search text-white"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control border-secondary border-opacity-25" 
                                               id="filterNationalId" 
                                               placeholder="ابحث بالرقم القومي..."
                                               autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-dark">
                                        <i class="fa fa-id-badge me-1 text-primary"></i>
                                        الرقم الأكاديمي
                                    </label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25">
                                            <i class="fa fa-search text-white"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control border-secondary border-opacity-25" 
                                               id="filterAcademicId" 
                                               placeholder="ابحث بالرقم الأكاديمي..."
                                               autocomplete="off">
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Filters -->
                <div class="col-lg-4">
                    <div class="card h-100 border-primary border-opacity-25 shadow-sm">
                        <div class="card-header bg-primary bg-opacity-10 border-0">
                            <h6 class="mb-0 text-white fw-bold">
                                <i class="fa fa-graduation-cap me-2"></i>
                                معلومات أكاديمية
                            </h6>
                        </div>
                        <div class="card-body">
                            @if(auth()->user()->hasRole('superadmin'))
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fa fa-university me-1 text-primary"></i>
                                    الكلية
                                </label>
                                <select class="form-select select2-faculty border-primary border-opacity-25" id="filterFaculty">
                                    <option value="">اختر الكلية</option>
                                    @foreach($faculties as $faculty)
                                        <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fa fa-book-open me-1 text-primary"></i>
                                    البرنامج
                                </label>
                                <select class="form-select select2-program border-primary border-opacity-25" id="filterProgram">
                                    <option value="">اختر البرنامج</option>
                                    @if(!auth()->user()->hasRole('superadmin'))
                                        @foreach($programs as $program)
                                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">
                                        <i class="fa fa-book me-1 text-primary"></i>
                                        المقرر
                                    </label>
                                    <select class="form-select select2-course border-secondary border-opacity-25" id="filterCourse">
                                        <option value="">الكل</option>
                                        @foreach($courses as $course)
                                            <option value="{{ $course->id }}">{{ $course->name }}</option>
                                        @endforeach
                                    </select>
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

    <div class="col-lg-12">
        <x-data-table
            :headers="['#', 'اسم الطالب', 'الرقم القومي', 'الرقم الأكاديمي', 'الكلية', 'البرنامج', 'الفصل الدراسي', 'المقرر', 'رمز المقرر', 'المدرس', 'التاريخ', 'الإجراءات']"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'student_name', 'name' => 'student_name'],
                ['data' => 'national_id', 'name' => 'national_id'],
                ['data' => 'academic_id', 'name' => 'academic_id'],
                ['data' => 'faculty_name', 'name' => 'faculty_name'],
                ['data' => 'program_name', 'name' => 'program_name'],
                ['data' => 'semester_name', 'name' => 'semester_name'],
                ['data' => 'course_name', 'name' => 'course_name'],
                ['data' => 'course_code', 'name' => 'course_code'],
                ['data' => 'instructor_name', 'name' => 'instructor_name'],
                ['data' => 'created_at', 'name' => 'created_at'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
            ]"
            :filters="[
                ['name' => 'student_name', 'selector' => '#filterStudentName'],
                ['name' => 'national_id', 'selector' => '#filterNationalId'],
                ['name' => 'academic_id', 'selector' => '#filterAcademicId'],
                ['name' => 'course_id', 'selector' => '#filterCourse'],
                ['name' => 'faculty_id', 'selector' => '#filterFaculty'],
                ['name' => 'program_id', 'selector' => '#filterProgram'],
            ]"
            data-url="{{ route('academic.enrollments.dataTable') }}"
        />
    </div>
    <!-- End col -->

    <!-- Import Modal -->
    <x-modal id="importModal" title="إضافة مستخدم جديد" size="modal-lg">
        <form id="importForm" action="{{ route('academic.enrollments.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="semester_id" class="form-label">اختر الفصل الدراسي</label>
                <select class="form-select" id="semester_id" name="semester_id" required>
                    <option value="" disabled>اختر الفصل الدراسي</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="file" class="form-label">اختر ملف Excel</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
                @error('file')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <!-- Backend Messages -->
            <div class="alert alert-success mt-3 d-none" id="task-progress-message"></div>
            <!-- Progress bar -->
            <div class="progress-container" style="display: none;">
                <div class="progress-text">جاري المعالجة...</div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div class="error-message text-danger mt-2" style="display: none;"></div>
            </div>
        </form>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            <button type="submit" class="btn btn-primary" id="saveButton" form="importForm">تحميل</button>
        </x-slot:footer>
    </x-modal>

    <!-- Instructors Import Modal -->
    <x-modal id="importInstructorsModal" title="تحميل بيانات المدرسين" size="modal-lg">
        <form id="importInstructorsForm" action="{{ route('academic.enrollments.import.instructor') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="instructor_semester_id" class="form-label">اختر الفصل الدراسي</label>
                <select class="form-select" id="instructor_semester_id" name="semester_id" required>
                    <option value="">-- اختر الفصل الدراسي --</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="instructors_file" class="form-label">اختر ملف Excel</label>
                <input type="file" class="form-control" id="instructors_file" name="file" accept=".xlsx,.xls,.csv" required>
                @error('file')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="progress-container" style="display: none;">
                <div class="progress-text">جاري المعالجة...</div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div class="error-message text-danger mt-2" style="display: none;"></div>
            </div>
        </form>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            <button type="submit" class="btn btn-success" id="saveInstructorsButton" form="importInstructorsForm">تحميل</button>
        </x-slot:footer>
    </x-modal>

    <!-- Single Enrollment Modal -->
    <x-modal id="singleEnrollmentModal" title="إضافة تسجيل واحد" size="modal-lg">
        <form id="singleEnrollmentForm" action="{{ route('academic.enrollments.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="student_id" class="form-label">الطالب</label>
                    <select class="form-select select2-student" id="student_id" name="student_id" required>    
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="faculty" class="form-label">الكلية</label>
                    <input type="text" class="form-control" id="faculty" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="program" class="form-label">البرنامج</label>
                    <input type="text" class="form-control" id="program" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="course_id" class="form-label">المقرر</label>
                    <select class="form-select select2-course" id="course_id" name="course_id" required>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="semester_id" class="form-label">الفصل الدراسي</label>
                    <select class="form-select select2-semester" id="semester_id" name="semester_id" required>
                        <option value="">-- اختر الفصل الدراسي --</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            <button type="submit" class="btn btn-primary" id="saveSingleEnrollmentButton" form="singleEnrollmentForm">حفظ</button>
        </x-slot:footer>
    </x-modal>

@endsection

@push('scripts')
<script>
    window.config = {
        routes: {
            statsEndpoint: "{{ route('academic.enrollments.stats') }}",
            coursesEndpoint: "{{ route('academic.enrollments.courses') }}",
            studentsEndpoint: "{{ route('academic.student.all') }}",
            programsByFacultyEndpoint: "{{ route('academic.programs.by-faculty') }}",
            importProgressEndpoint: "{{ route('academic.enrollments.import.progress', ['taskId' => ':taskId']) }}",
            destroyEndpoint: "{{ route('academic.enrollments.destroy', ['enrollment' => ':enrollment']) }}",
            storeEndpoint: "{{ route('academic.enrollments.store') }}"
        },
        role: "{{ auth()->user()->getRoleNames()->first() }}"
    };
</script>
<script src="{{ asset('assets/js/pages/enrollment.js') }}"></script>
@endpush
