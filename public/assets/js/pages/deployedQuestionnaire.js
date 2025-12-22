$(document).ready(() => {
    // === Config & Endpoints ===
    const endpoints = window.routes || {};

    // === Helpers ===
    const showAlert = (type, title, text) => {
        Swal.fire({ icon: type, title, text, confirmButtonText: 'حسناً' });
    };

    const ajaxDefaults = {
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    };

    // === State Management ===
    let selectedQuestionIds = [];

    // === Template Handlers ===
    const setupTemplateHandlers = () => {
        // Template type selection handler
        $('input[name="template_type"]').change(function() {
            if ($(this).val() === 'template') {
                $('#templateSelection').show();
                $('#name').prop('required', true);
                // Clear selected questions when switching to template mode
                $('#nestable .dd-list').empty();
                selectedQuestionIds = [];
                resetQuestionButtons();
                $('#questionnaireSettings').slideUp();
            } else {
                $('#templateSelection').hide();
                $('#name').prop('required', true);
                // Show questionnaire settings but don't auto-select questions for new questionnaire
                $('#questionnaireSettings').slideDown();
                // Clear any previously selected questions
                $('#nestable .dd-list').empty();
                selectedQuestionIds = [];
                resetQuestionButtons();
                // Add empty state
                $('#nestable .dd-list').append(`
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
                `);
            }
        });

        // Template selection handler
        $('#template_id').change(function() {
            const selectedTemplate = $(this).val();
            if (selectedTemplate) {
                loadTemplateData(selectedTemplate);
            } else {
                $('#questionnaireSettings').slideUp();
                $('#nestable .dd-list').empty();
                selectedQuestionIds = [];
                resetQuestionButtons();
            }
        });
    };

    // === Helper Functions ===
    const resetQuestionButtons = () => {
        $('.add-question-btn').prop('disabled', false)
            .addClass('btn-primary')
            .removeClass('btn-secondary');
    };

    const autoSelectAllQuestions = () => {
        const $questionsList = $('#nestable .dd-list');
        $questionsList.empty();
        selectedQuestionIds = [];

        // Get all available questions and add them to selected list
        const availableQuestions = $('.question-card');
        
        if (availableQuestions.length === 0) {
            $questionsList.append(`
                <li class="dd-item empty-state" data-id="0">
                    <div class="card mb-2 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="text-muted mb-2">
                                <i class="fa fa-inbox fs-1"></i>
                            </div>
                            <p class="mb-0 small text-muted">لا توجد أسئلة متاحة</p>
                        </div>
                    </div>
                </li>
            `);
            return;
        }

        availableQuestions.each(function() {
            const $questionCard = $(this);
            const $btn = $questionCard.find('.add-question-btn');
            const questionId = $btn.data('id');
            const questionText = $questionCard.find('p.small.fw-medium').text();
            const questionType = $questionCard.find('.badge').first().text();

            if (!selectedQuestionIds.includes(questionId)) {
                addQuestion(questionId, questionText, questionType, $btn);
            }
        });
    };

    // === Target Handlers ===
    const setupTargetHandlers = () => {
        // Target type change handler
        $('#target_type_id').change(function() {
            const selectedOption = $(this).find('option:selected');
            const scope = selectedOption.data('scope');
            const role = selectedOption.data('role');
            
            // Hide all target options
            $('.target-options').hide();
            
            // Show relevant target options based on scope
            switch(scope) {
                case 'academic':
                    if (role === 'student') {
                        $('#studentTarget').show();
                    } else if (role === 'lecturer') {
                        $('#lecturerTarget').show();
                    }
                    break;
                case 'administrative':
                    $('#workerTarget').show();
                    break;
            }
        });

        // Student target method change handler
        $('input[name="student_target_method"]').change(function() {
            const method = $(this).val();
            handleStudentTargetMethod(method);
        });

        // Course selection type change handler
        $('input[name="course_selection_type"]').change(function() {
            const selectionType = $(this).val();
            handleCourseSelectionType(selectionType);
        });

        // Faculty scope change handler
        $('input[name="faculty_scope"]').change(function() {
            const scope = $(this).val();
            handleFacultyScope(scope);
        });

        // Program scope change handler
        $('input[name="program_scope"]').change(function() {
            const scope = $(this).val();
            handleProgramScope(scope);
        });

        // Semester scope change handler
        $('input[name="semester_scope"]').change(function() {
            const scope = $(this).val();
            handleSemesterScope(scope);
        });
    };

    // === Question Handlers ===
    const setupQuestionHandlers = () => {
        // Add question button handler
        $(document).on('click', '.add-question-btn', function() {
            const $btn = $(this);
            const questionId = $btn.data('id');
            const $questionCard = $btn.closest('.question-card');
            const questionText = $questionCard.find('p.small.fw-medium').text();
            const questionType = $questionCard.find('.badge').first().text();

            addQuestion(questionId, questionText, questionType, $btn);
        });

        // Delete question button handler
        $(document).on('click', '.delete-btn', function() {
            const $questionCard = $(this).closest('.dd-item');
            const questionId = $questionCard.data('id');
            removeQuestion(questionId, $questionCard);
        });

        // Show options button handler
        $(document).on('click', '.show-options-btn', function() {
            const questionId = $(this).data('question-id');
            showQuestionOptions(questionId);
        });
    };

    // === Search Handlers ===
    const setupSearchHandlers = () => {
        // Available questions search
        $('#availableQuestionsSearch').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const $questions = $('.question-card');
            let visibleCount = 0;

            $questions.each(function() {
                const $question = $(this);
                const questionText = $question.find('p.small.fw-medium').text().toLowerCase();
                const questionType = $question.find('.badge').first().text().toLowerCase();
                const questionCategory = $question.find('.text-muted.small').text().toLowerCase();

                if (questionText.includes(searchTerm) || 
                    questionType.includes(searchTerm) || 
                    questionCategory.includes(searchTerm)) {
                    $question.show();
                    visibleCount++;
                } else {
                    $question.hide();
                }
            });

            // Show/hide not found message
            if (visibleCount === 0 && searchTerm !== '') {
                $('#availableQuestionsNotFound').show();
            } else {
                $('#availableQuestionsNotFound').hide();
            }
        });

        // Selected questions search
        $('#selectedQuestionsSearch').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const $questions = $('.dd-item:not(.empty-state)');
            let visibleCount = 0;

            $questions.each(function() {
                const $question = $(this);
                const questionText = $question.find('.question-text').text().toLowerCase();
                const questionType = $question.find('.badge').first().text().toLowerCase();

                if (questionText.includes(searchTerm) || questionType.includes(searchTerm)) {
                    $question.show();
                    visibleCount++;
                } else {
                    $question.hide();
                }
            });

            // Show/hide not found message
            if (visibleCount === 0 && searchTerm !== '') {
                $('#selectedQuestionsNotFound').show();
            } else {
                $('#selectedQuestionsNotFound').hide();
            }
        });
    };

    // === Question Options Modal ===
    const showQuestionOptions = (questionId) => {
        const $questionCard = $(`.question-card[data-question-id="${questionId}"]`);
        const options = $questionCard.data('question-options');
        
        if (!options || options.length === 0) {
            $('#questionOptionsModalBody').html(`
                <div class="text-center text-muted py-4">
                    <i class="fa fa-info-circle fs-2 mb-2"></i>
                    <p class="mb-0">لا توجد خيارات لهذا السؤال</p>
                </div>
            `);
        } else {
            let optionsHtml = '<div class="row g-3">';
            options.forEach((option, index) => {
                optionsHtml += `
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary">${index + 1}</span>
                                    <span class="fw-medium">${option.option_text}</span>
                                </div>
                                ${option.value ? `<small class="text-muted">القيمة: ${option.value}</small>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            optionsHtml += '</div>';
            $('#questionOptionsModalBody').html(optionsHtml);
        }
        
        $('#questionOptionsModal').modal('show');
    };

    // === Form Handlers ===
    const setupFormHandlers = () => {
        $('#deployQuestionnaireForm').on('submit', function(e) {
            e.preventDefault();
            const questionnaireData = buildQuestionnaireData();
            if (!questionnaireData) return;

            if (questionnaireData.questions.length === 0) {
                showAlert('error', 'خطأ!', 'يرجى إضافة أسئلة للاستبيان');
                return;
            }

            submitQuestionnaire(questionnaireData);
        });
    };

    // === Preview Handlers ===
    const setupPreviewHandlers = () => {
        $('#previewBtn').on('click', function() {
            const questionnaireData = buildQuestionnaireData();
            if (!questionnaireData) return;

            showPreview(questionnaireData);
        });
    };

    // === Template Operations ===
    const loadTemplateData = (templateId) => {
        $('#questionnaireSettings').slideDown();
        $('#nestable .dd-list').empty();
        selectedQuestionIds = [];
        resetQuestionButtons();

        $.ajax({
            url: endpoints.templateEndpoint.replace(':id', templateId),
            method: 'GET',
            success: response => {
                if (response.success && response.data) {
                    $('#name').val(response.data.name);
                    loadTemplateQuestions(response.data.data.template_questions);
                } else {
                    handleError('تحميل بيانات النموذج', { responseJSON: { message: 'Invalid response format' } });
                }
            },
            error: xhr => handleError('تحميل بيانات النموذج', xhr)
        });
    };

    const loadTemplateQuestions = (templateQuestions) => {
        const $questionsList = $('#nestable .dd-list');
        $questionsList.empty();
        
        // Reset all question buttons first
        resetQuestionButtons();

        templateQuestions.forEach((templateQuestion) => {
            const question = templateQuestion.question;
            selectedQuestionIds.push(question.id);
            
            const questionHtml = createQuestionHtml(question, templateQuestion.is_required);
            $questionsList.append(questionHtml);

            $(`.add-question-btn[data-id="${question.id}"]`)
                .prop('disabled', true)
                .addClass('btn-secondary')
                .removeClass('btn-primary');
        });
    };

    const createQuestionHtml = (question, isRequired) => {
        return `
            <li class="dd-item" data-id="${question.id}">
                <div class="card mb-2 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start gap-2">
                            <div class="dd-handle text-muted mt-1">
                                <i class="ri-drag-move-line"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small fw-medium question-text">${question.text}</p>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge bg-primary small">${question.type.name || ''}</span>
                                    <div class="form-check form-switch ms-auto">
                                        <input class="form-check-input required-toggle" type="checkbox" role="switch" id="required-${question.id}" ${isRequired ? 'checked' : ''}>
                                        <label class="form-check-label small" for="required-${question.id}">مطلوب</label>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-link text-muted p-0 info-btn" title="معلومات">
                                        <i class="ri-information-line"></i>
                                    </button>
                                    <button type="button" class="btn btn-link text-danger p-0 delete-btn" title="حذف">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        `;
    };

    // === Question Operations ===
    const addQuestion = (questionId, questionText, questionType, $btn) => {
        if (selectedQuestionIds.includes(questionId)) return;

        const $selectedQuestionsContainer = $('#nestable .dd-list');
        $selectedQuestionsContainer.find('.empty-state').remove();
        selectedQuestionIds.push(questionId);

        const $newQuestionCard = $(`
            <li class="dd-item" data-id="${questionId}">
                <div class="card mb-2 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start gap-2">
                            <div class="dd-handle text-muted mt-1">
                                <i class="ri-drag-move-line"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small fw-medium question-text">${questionText}</p>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge bg-primary small">${questionType}</span>
                                    <div class="form-check form-switch ms-auto">
                                        <input class="form-check-input required-toggle" type="checkbox" role="switch" id="required-${questionId}" checked>
                                        <label class="form-check-label small" for="required-${questionId}">مطلوب</label>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-link text-muted p-0 info-btn" title="معلومات">
                                        <i class="ri-information-line"></i>
                                    </button>
                                    <button type="button" class="btn btn-link text-danger p-0 delete-btn" title="حذف">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        `);

        $selectedQuestionsContainer.append($newQuestionCard);
        $btn.prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
    };

    const removeQuestion = (questionId, $questionCard) => {
        selectedQuestionIds = selectedQuestionIds.filter(id => id !== questionId);
        $questionCard.remove();
        
        $(`.add-question-btn[data-id="${questionId}"]`)
            .prop('disabled', false)
            .addClass('btn-primary')
            .removeClass('btn-secondary');
        
        if ($('#nestable .dd-list').children().length === 0) {
            $('#nestable .dd-list').append(`
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
            `);
        }
    };

    // === Target Method Handlers ===
    const handleStudentTargetMethod = (method) => {
        $('.faculty-selection, .program-selection, .course-selection, .level-selection').hide();
        
        switch(method) {
            case 'faculty':
                $('.faculty-selection, .level-selection').show();
                break;
            case 'program':
                $('.faculty-selection, .program-selection, .level-selection').show();
                break;
            case 'course':
                $('.course-selection').show();
                $('input[name="course_selection_type"][value="specific"]').prop('checked', true);
                $('#specificEnrollmentSelection').show();
                $('#allEnrollmentsSelection').hide();
                break;
        }
    };

    const handleCourseSelectionType = (selectionType) => {
        if (selectionType === 'specific') {
            $('#specificEnrollmentSelection').show();
            $('#allEnrollmentsSelection').hide();
            $('.faculty-selection, .program-selection').hide();
        } else {
            $('#specificEnrollmentSelection').hide();
            $('#allEnrollmentsSelection').show();
        }
    };

    const handleFacultyScope = (scope) => {
        if (scope === 'specific') {
            $('#specificFacultiesSelection').show();
        } else {
            $('#specificFacultiesSelection').hide();
            $('#specificProgramsSelection').hide();
            $('input[name="program_scope"][value="all"]').prop('checked', true);
        }
    };

    const handleProgramScope = (scope) => {
        if (scope === 'specific') {
            $('#specificProgramsSelection').show();
            loadPrograms();
        } else {
            $('#specificProgramsSelection').hide();
        }
    };

    const handleSemesterScope = (scope) => {
        if (scope === 'specific') {
            $('#specificSemestersSelection').show();
        } else {
            $('#specificSemestersSelection').hide();
        }
    };

    // === Data Loading ===
    const loadPrograms = () => {
        const facultyScope = $('input[name="faculty_scope"]:checked').val();
        const selectedFaculties = [];
        
        if (facultyScope === 'specific') {
            $('input[name="selected_faculties[]"]:checked').each(function() {
                selectedFaculties.push($(this).val());
            });
        }

        if (selectedFaculties.length > 0 || facultyScope === 'all') {
            $.ajax({
                url: '/api/programs',
                method: 'GET',
                data: {
                    faculty_scope: facultyScope,
                    faculty_ids: selectedFaculties
                },
                success: response => {
                    const $programsList = $('#programsList');
                    $programsList.empty();
                    
                    if (response.length === 0) {
                        $programsList.append('<div class="text-muted">لا توجد برامج متاحة</div>');
                    } else {
                        response.forEach(function(program) {
                            $programsList.append(`
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="selected_programs[]" id="prog${program.id}" value="${program.id}">
                                    <label class="form-check-label" for="prog${program.id}">
                                        ${program.name}
                                    </label>
                                </div>
                            `);
                        });
                    }
                },
                error: xhr => handleError('تحميل البرامج', xhr)
            });
        } else {
            $('#programsList').empty();
        }
    };

    // === Data Building ===
    const buildQuestionnaireData = () => {
        try {
            const templateType = $('input[name="template_type"]:checked').val();
            const templateId = $('#template_id').val();
            
            const questionnaire = {
                template: {
                    type: templateType,
                    id: templateType === 'template' ? templateId : null
                },
                target: {
                    type: $('#target_type_id').val(),
                    scope: $('#target_type_id option:selected').data('scope'),
                    role: $('#target_type_id option:selected').data('role'),
                    data: buildTargetData()
                },
                settings: {
                    name: $('#name').val(),
                    status: $('#status').val(),
                    open_date: $('#open_date').val(),
                    close_date: $('#close_date').val(),
                    deployment_strategy: $('#deployment_strategy').val()
                },
                questions: buildQuestionsData()
            };

            return questionnaire;
        } catch (error) {
            showAlert('error', 'خطأ!', error.message);
            return null;
        }
    };

    const buildTargetData = () => {
        const targetType = $('#target_type_id option:selected').data('role');
        const data = {};

        switch(targetType) {
            case 'student':
                data.method = $('input[name="student_target_method"]:checked').val();
                if (data.method === 'faculty') {
                    data.faculty_id = $('#organizational_unit_id').val();
                    data.level = $('#student_level').val() || null;
                } else if (data.method === 'program') {
                    data.faculty_id = $('#organizational_unit_id').val();
                    data.program_id = $('#program_id').val();
                    data.level = $('#student_level').val() || null;
                } else if (data.method === 'course') {
                    data.course_selection_type = $('input[name="course_selection_type"]:checked').val();
                    if (data.course_selection_type === 'specific') {
                        data.course_id = $('#course_id').val();
                        data.semester_id = $('#enrollment_semester').val();
                    } else {
                        data.faculty_scope = $('input[name="faculty_scope"]:checked').val();
                        data.program_scope = $('input[name="program_scope"]:checked').val();
                        data.semester_scope = $('input[name="semester_scope"]:checked').val();
                        data.selected_faculties = $('input[name="selected_faculties[]"]:checked').map((_, el) => $(el).val()).get();
                        data.selected_programs = $('input[name="selected_programs[]"]:checked').map((_, el) => $(el).val()).get();
                        data.selected_semesters = $('input[name="selected_semesters[]"]:checked').map((_, el) => $(el).val()).get();
                    }
                }
                break;
            case 'lecturer':
                data.faculty_id = $('#faculty_id').val();
                data.department_id = $('#department_id').val() || null;
                break;
            case 'worker':
                data.department_id = $('#department_id').val();
                data.job_title = $('#job_title').val() || null;
                break;
        }

        return data;
    };

    const buildQuestionsData = () => {
        const questions = [];
        $('#nestable .dd-list .dd-item').each((index, item) => {
            if (!$(item).hasClass('empty-state')) {
                questions.push({
                    id: $(item).data('id'),
                    order: index,
                    required: $(item).find('.required-toggle').is(':checked')
                });
            }
        });
        return questions;
    };

    // === AJAX Operations ===
    const submitQuestionnaire = (questionnaireData) => {
        const formData = new FormData();
        formData.append('questionnaire', JSON.stringify(questionnaireData));

        $.ajax({
            url: endpoints.storeEndpoint,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: formData,
            processData: false,
            contentType: false,
            success: response => {
                handleSuccess('إنشاء', response, () => {
                    window.location.href = response.redirect_url || window.location.href;
                });
            },
            error: xhr => handleError('إنشاء', xhr)
        });
    };

    // === Preview Operations ===
    const showPreview = (questionnaireData) => {
        const $modal = $('#previewModal');
        const $title = $modal.find('.preview-title');
        const $questions = $modal.find('.preview-questions');

        $title.text(questionnaireData.settings.name);
        $questions.empty();

        questionnaireData.questions.forEach((questionData, index) => {
            // Get question details from the DOM
            const $questionElement = $(`.dd-item[data-id="${questionData.id}"]`);
            const questionText = $questionElement.find('p.small.fw-medium').text();
            const questionType = $questionElement.find('.badge.bg-primary').text();
            
            const $questionCard = $(`
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title">${index + 1}. ${questionText}</h6>
                        <p class="card-text text-muted small">${questionType}</p>
                        ${questionData.required ? '<span class="badge bg-danger">مطلوب</span>' : ''}
                    </div>
                </div>
            `);
            $questions.append($questionCard);
        });

        $modal.modal('show');
    };

    // === Error & Success Handlers ===
    const handleError = (action, xhr) => {
        let errorMessage = `حدث خطأ أثناء ${action}`;
        
        if (xhr.responseJSON && xhr.responseJSON.errors) {
            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
        }
        if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
        }
        
        showAlert('error', 'خطأ!', errorMessage);
    };

    const handleSuccess = (action, response, onSuccess) => {
        if (response.success) {
            if (typeof onSuccess === 'function') {
                onSuccess();
            }
            Swal.fire({
                icon: 'success',
                title: `تم ${action}!`,
                text: response.message,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            showAlert('error', 'خطأ!', response.message || `حدث خطأ أثناء ${action}`);
        }
    };

    // === Initialization ===
    const init = () => {
        // Initialize Nestable for questions
        $('#nestable').nestable({
            maxDepth: 1
        });

        // Setup all handlers
        setupTemplateHandlers();
        setupTargetHandlers();
        setupQuestionHandlers();
        setupFormHandlers();
        setupPreviewHandlers();
        setupSearchHandlers();

        // Handle initial state
        const selectedTemplateType = $('input[name="template_type"]:checked').val();
        if (selectedTemplateType === 'new') {
            // If "create new" is selected by default, show settings but don't auto-select questions
            $('#questionnaireSettings').slideDown();
            // Ensure empty state is shown
            if ($('#nestable .dd-list').children().length === 0) {
                $('#nestable .dd-list').append(`
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
                `);
            }
        }
    };

    // === Init ===
    init();
}); 