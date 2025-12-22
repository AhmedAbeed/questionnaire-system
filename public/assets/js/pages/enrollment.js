$(document).ready(function() {
    // Enrollment Module
    const Enrollment = {
        // Configuration
        config: {
            statsEndpoint: window.config.routes.statsEndpoint,
            coursesEndpoint: window.config.routes.coursesEndpoint,
            studentsEndpoint: window.config.routes.studentsEndpoint,
            programsByFacultyEndpoint: window.config.routes.programsByFacultyEndpoint,
            importProgressEndpoint: window.config.routes.importProgressEndpoint,
            destroyEndpoint: window.config.routes.destroyEndpoint,
            storeEndpoint: window.config.routes.storeEndpoint,
            statCards: ['total-enrollments'],
            Role: window.config.role,
            select2: {
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                minimumResultsForSearch: 5,
                language: {
                    noResults: function() { return "لا توجد نتائج"; },
                    searching: function() { return "جاري البحث..."; },
                    inputTooShort: function() { return "الرجاء إدخال حرفين على الأقل للبحث"; },
                    errorLoading: function() { return "حدث خطأ أثناء تحميل النتائج"; }
                },
                escapeMarkup: function(markup) { return markup; }
            }
        },

        // Initialize module
        init: function() {
            this.loadStats();
            this.initializeSelect2();
            this.setupHandlers();
        },

        // Setup all handlers
        setupHandlers: function() {
            this.setupModalHandlers();
            this.setupFormHandlers();
            this.setupFilterHandlers();
            this.setupDeleteHandlers();
            this.setupImportHandlers();
        },

        // Load stats values
        loadStats: function() {
            $.ajax({
                url: this.config.statsEndpoint,
                method: 'GET',
                success: (response) => {
                    $('#total-enrollments-value').html(`<h4>${response.data.total_enrollments.value}</h4>`);
                    $('#total-enrollments-updated').html(`${response.data.total_enrollments.updated}`);
                },
                error: (xhr) => {
                    this.handleError('تحميل البيانات', xhr);
                    this.showStatErrorState();
                }
            });
        },

        // Show error state for stat cards
        showStatErrorState: function() {
            this.config.statCards.forEach(id => {
                $(`#${id}-value`).html(`
                    <div class="alert alert-danger py-1 px-2 mb-0">
                        <i class="feather icon-alert-circle me-1"></i>
                        <small>خطأ في التحميل</small>
                    </div>
                `);
                $(`#${id}-updated`).html(`
                    <i class="feather icon-alert-circle me-1"></i>
                    <span class="text-danger">حدث خطأ في تحميل البيانات</span>
                `);
            });
        },

        // Initialize Select2 dropdowns
        initializeSelect2: function() {
            const select2BaseConfig = {
                ...this.config.select2,
                dropdownParent: $('#singleEnrollmentModal').length ? $('#singleEnrollmentModal') : $('body')
            };

            // Student Select2
            $('.select2-student').select2({
                ...select2BaseConfig,
                placeholder: 'اختر الطالب',
                minimumInputLength: 2,
                ajax: this.getStudentAjaxConfig(),
                templateResult: function(data) {
                    return data.loading ? data.text : $('<span>' + data.text + '</span>');
                },
                templateSelection: function(data) {
                    return data.text || data.name + ' (' + data.student_id + ')';
                }
            }).on('select2:open', function() {
                const url = new URL(window.location.href);
                url.searchParams.delete('term');
                url.searchParams.delete('_type');
                url.searchParams.delete('q');
                window.history.replaceState({}, '', url);
            });

            // Course Select2
            $('.select2-course').select2({
                ...select2BaseConfig,
                placeholder: 'اختر المقرر',
                minimumInputLength: 2,
                ajax: this.getCourseAjaxConfig(),
                templateResult: function(data) {
                    return data.loading ? data.text : $('<span>' + data.text + '</span>');
                },
                templateSelection: function(data) {
                    return data.text || data.name + ' (' + data.code + ')';
                }
            });

            // Faculty Select2
            $('.select2-faculty').select2({
                ...select2BaseConfig,
                placeholder: 'اختر الكلية'
            });

            // Program Select2
            $('.select2-program').select2({
                ...select2BaseConfig,
                placeholder: 'اختر البرنامج'
            });

            // Filter Select2
            $('#filterCourse').select2({
                ...this.config.select2,
                placeholder: 'اختر المقرر',
                minimumInputLength: 2,
                ajax: this.getCourseAjaxConfig(),
                templateResult: function(data) {
                    return data.loading ? data.text : $('<span>' + data.text + '</span>');
                },
                templateSelection: function(data) {
                    return data.text || data.name + ' (' + data.code + ')';
                }
            });

            $('#filterFaculty').select2({
                ...this.config.select2,
                placeholder: 'اختر الكلية',
                allowClear: true
            });

            $('#filterProgram').select2({
                ...this.config.select2,
                placeholder: 'اختر البرنامج',
                allowClear: true
            });
        },

        // Setup modal handlers
        setupModalHandlers: function() {
            const self = this;
            
            $('.modal').on('shown.bs.modal', function() {
                const $modal = $(this);
                const modalZIndex = parseInt($modal.css('z-index'), 10);
                $('.select2-container--bootstrap-5').css('z-index', modalZIndex + 10);

                $modal.find('.select2-course, .select2-faculty, .select2-program, .select2-student').each(function() {
                    const $element = $(this);
                    const currentData = $element.select2('data');
                    $element.select2('destroy');

                    let config = { ...self.config.select2, dropdownParent: $modal };
                    
                    if ($element.hasClass('select2-course')) {
                        config = { 
                            ...config, 
                            placeholder: 'اختر المقرر', 
                            minimumInputLength: 2, 
                            ajax: self.getCourseAjaxConfig(),
                            templateResult: function(data) {
                                return data.loading ? data.text : $('<span>' + data.text + '</span>');
                            },
                            templateSelection: function(data) {
                                return data.text || data.name + ' (' + data.code + ')';
                            }
                        };
                    } else if ($element.hasClass('select2-faculty')) {
                        config.placeholder = 'اختر الكلية';
                    } else if ($element.hasClass('select2-program')) {
                        config.placeholder = 'اختر البرنامج';
                    } else if ($element.hasClass('select2-student')) {
                        config = { 
                            ...config, 
                            placeholder: 'اختر الطالب', 
                            minimumInputLength: 2, 
                            ajax: self.getStudentAjaxConfig(),
                            templateResult: function(data) {
                                return data.loading ? data.text : $('<span>' + data.text + '</span>');
                            },
                            templateSelection: function(data) {
                                return data.text || data.name + ' (' + data.student_id + ')';
                            }
                        };
                    }
                    
                    $element.select2(config);
                    if (currentData && currentData.length > 0) {
                        $element.val(currentData[0].id).trigger('change');
                    }
                });
            });

            $('#singleEnrollmentModal').on('hidden.bs.modal', () => this.resetEnrollmentForm());
            $('.modal').on('click', function(e) {
                if (e.target === this) $(this).modal('hide');
            });
        },

        // Setup form handlers
        setupFormHandlers: function() {
            const self = this;
            
            $(document).on('select2:select', '#student_id', function(e) {
                const data = e.params.data;
                if (data.faculty) $('#faculty').val(data.faculty);
                if (data.program) $('#program').val(data.program);
                $('#course_id').val(null).trigger('change');
            });

            $('#singleEnrollmentForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $submitBtn = $form.find('button[type="submit"]');
                const originalText = $submitBtn.text();
                self.showLoadingState($submitBtn);

                $.ajax({
                    url: $form.attr('action'),
                    method: $form.attr('method'),
                    data: $form.serialize(),
                    success: (response) => {
                        self.handleSuccess('حفظ', response, () => {
                            $('#singleEnrollmentModal').modal('hide');
                            self.reloadDataTable();
                            self.loadStats();
                        });
                    },
                    error: (xhr) => {
                        self.handleError('حفظ', xhr);
                    },
                    complete: () => {
                        self.hideLoadingState($submitBtn);
                        $submitBtn.text(originalText);
                    }
                });
            });
        },

        // Setup filter handlers
        setupFilterHandlers: function() {
            const self = this;
            let searchTimeout;
            
            $('#toggleFilters').on('click', function() {
                const $button = $(this);
                const $section = $('#filtersSection');
                const $icon = $button.find('i');
                const $label = $button.find('span');
                $section.slideToggle(300, function() {
                    $icon.toggleClass('fa-filter fa-times');
                    $label.text($section.is(':visible') ? 'إغلاق خيارات البحث' : 'خيارات البحث');
                });
            });

            $('#resetFilters').on('click', function() {
                self.resetFilters();
            });

            // Text input filters
            ['#filterStudentName', '#filterNationalId', '#filterAcademicId'].forEach(selector => {
                $(selector).on('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        self.reloadDataTable();
                    }, 500);
                });
            });

            // Select filters
            $('#filterCourse, #filterFaculty, #filterProgram').on('change', function() {
                const $element = $(this);
                self.showLoadingState($element);
                
                if ($element.attr('id') === 'filterFaculty') {
                    self.updateProgramsByFaculty($element.val());
                }
                
                self.reloadDataTable(function() {
                    self.hideLoadingState($element);
                });
            });

            if (this.config.Role === "admin") {
                $('#filterFaculty').on('change', function() {
                    const facultyId = $(this).val();
                    self.updateProgramsByFaculty(facultyId);
                });
            }
        },

        // Setup delete handlers
        setupDeleteHandlers: function() {
            $(document).on('click', '.delete-enrollment', function() {
                Enrollment.confirmAndDelete($(this).data('id'));
            });
        },

        // Setup import handlers
        setupImportHandlers: function() {
            const self = this;

            $('#importModal').on('show.bs.modal', function() {
                $('.progress-container').hide();
                $('.progress-bar').css('width', '0%').text('0%').attr('aria-valuenow', 0);
                $('.error-message').hide().text('');
                $('#saveButton').prop('disabled', false);
            });

            $('#importForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $submitBtn = $('#saveButton');
                self.showLoadingState($submitBtn);
                $('.progress-container').show();
                const formData = new FormData(this);

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: (response) => {
                        if (response.success) {
                            setTimeout(() => self.pollProgress(response.data.taskId), 2000);
                        } else {
                            self.handleError('تحميل', response);
                            $('.error-message').text('فشل في بدء عملية التحميل.').show();
                        }
                    },
                    error: (xhr) => {
                        self.handleError('تحميل', xhr);
                        $('.error-message').text('حدث خطأ أثناء تحميل الملف. يرجى المحاولة مرة أخرى.').show();
                    },
                    complete: () => {
                        self.hideLoadingState($submitBtn);
                    }
                });
            });

            $('#importInstructorsModal').on('show.bs.modal', function() {
                $('.progress-container', this).hide();
                $('.progress-bar', this).css('width', '0%').text('0%').attr('aria-valuenow', 0);
                $('.error-message', this).hide().text('');
                $('#saveInstructorsButton').prop('disabled', false);
            });

            $('#importInstructorsForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $submitBtn = $('#saveInstructorsButton');
                self.showLoadingState($submitBtn);
                $('.progress-container', this).show();
                const formData = new FormData(this);

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: (response) => {
                        if (response.success && response.data.taskId) {
                            setTimeout(() => self.pollInstructorsProgress(response.data.taskId), 2000);
                        } else {
                            self.handleError('تحميل', response);
                            $('.error-message', '#importInstructorsModal').text('فشل في بدء عملية التحميل.').show();
                        }
                    },
                    error: (xhr) => {
                        self.handleError('تحميل', xhr);
                        $('.error-message', '#importInstructorsModal').text('حدث خطأ أثناء تحميل الملف.').show();
                    },
                    complete: () => {
                        self.hideLoadingState($submitBtn);
                    }
                });
            });
        },

        // Get student AJAX configuration
        getStudentAjaxConfig: function() {
            return {
                url: this.config.studentsEndpoint,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page || 1,
                        program_id: $('#program').val() || null,
                        faculty_id: $('#faculty').val() || null
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results.map(item => ({
                            id: item.id,
                            text: item.name + ' (' + item.student_id + ')',
                            student_id: item.student_id,
                            name: item.name,
                            faculty: item.faculty_name,
                            program: item.program_name
                        })),
                        pagination: { more: data.current_page < data.last_page }
                    };
                },
                cache: true
            };
        },

        // Get course AJAX configuration
        getCourseAjaxConfig: function() {
            return {
                url: this.config.coursesEndpoint,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page || 1,
                        program_id: $('#program').val() || null
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results.map(item => ({
                            id: item.id,
                            text: item.name + ' (' + item.code + ')',
                            code: item.code,
                            name: item.name,
                            faculty: item.faculty
                        })),
                        pagination: { more: data.current_page < data.last_page }
                    };
                },
                cache: true
            };
        },

        // Poll progress for imports
        pollProgress: function(taskId) {
            const progressUrl = this.config.importProgressEndpoint.replace(':taskId', taskId);
            const self = this;
            
            const interval = setInterval(() => {
                $.ajax({
                    url: progressUrl,
                    type: 'GET',
                    success: (response) => {
                        if (!response.success) {
                            self.handleImportError(response.message || 'حدث خطأ أثناء تتبع التقدم.');
                            clearInterval(interval);
                            return;
                        }
                        const taskData = response.data;
                        self.toggleProgressMessage(taskData.message);
                        const percentage = taskData.progress || 0;
                        $('.progress-bar', '#importModal')
                            .css('width', percentage + '%')
                            .text(Math.round(percentage) + '%')
                            .attr('aria-valuenow', Math.round(percentage));

                        switch (taskData.status) {
                            case 'completed':
                                clearInterval(interval);
                                self.handleImportSuccess(taskData);
                                break;
                            case 'completed_with_errors':
                                clearInterval(interval);
                                self.handleImportWithErrors(taskData);
                                break;
                            case 'failed':
                                clearInterval(interval);
                                self.handleImportError(taskData.message || 'فشل في معالجة المهمة');
                                break;
                            case 'pending':
                                self.toggleProgressMessage('في انتظار المعالجة...');
                                break;
                            case 'processing':
                                break;
                            default:
                                clearInterval(interval);
                                self.handleImportError('حالة غير معروفة للمهمة');
                                break;
                        }
                    },
                    error: (xhr) => {
                        clearInterval(interval);
                        const errorMessage = xhr.status === 404 ? 'المهمة غير موجودة' :
                                            xhr.status === 500 ? 'خطأ في الخادم أثناء تتبع التقدم' :
                                            'حدث خطأ أثناء تتبع التقدم';
                        self.handleImportError(errorMessage);
                    }
                });
            }, 1000);
        },

        // Poll instructors progress
        pollInstructorsProgress: function(taskId) {
            const progressUrl = this.config.importProgressEndpoint.replace(':taskId', taskId);
            const self = this;
            
            const interval = setInterval(() => {
                $.ajax({
                    url: progressUrl,
                    type: 'GET',
                    success: (response) => {
                        if (!response.success) {
                            self.handleImportError(response.message || 'حدث خطأ أثناء تتبع التقدم.');
                            clearInterval(interval);
                            return;
                        }
                        const taskData = response.data;
                        self.toggleProgressMessage(taskData.message);
                        const percentage = taskData.progress || 0;
                        $('.progress-bar', '#importInstructorsModal')
                            .css('width', percentage + '%')
                            .text(Math.round(percentage) + '%')
                            .attr('aria-valuenow', Math.round(percentage));

                        switch (taskData.status) {
                            case 'completed':
                                clearInterval(interval);
                                self.handleInstructorsImportSuccess(taskData);
                                break;
                            case 'completed_with_errors':
                                clearInterval(interval);
                                self.handleInstructorsImportWithErrors(taskData);
                                break;
                            case 'failed':
                                clearInterval(interval);
                                self.handleImportError(taskData.message || 'فشل في معالجة المهمة');
                                break;
                            case 'pending':
                                self.toggleProgressMessage('في انتظار المعالجة...');
                                break;
                            case 'processing':
                                break;
                            default:
                                clearInterval(interval);
                                self.handleImportError('حالة غير معروفة للمهمة');
                                break;
                        }
                    },
                    error: (xhr) => {
                        clearInterval(interval);
                        const errorMessage = xhr.status === 404 ? 'المهمة غير موجودة' :
                                            xhr.status === 500 ? 'خطأ في الخادم أثناء تتبع التقدم' :
                                            'حدث خطأ أثناء تتبع التقدم';
                        self.handleImportError(errorMessage);
                    }
                });
            }, 1000);
        },

        // Toggle progress message
        toggleProgressMessage: function(message) {
            const $element = $('#task-progress-message');
            $element.toggleClass('d-none', !message).html(message);
        },

        // Handle import success
        handleImportSuccess: function(taskData) {
            const self = this;
            Swal.fire({
                title: 'تم التحميل بنجاح',
                text: taskData.message || 'تم تحميل البيانات بنجاح',
                icon: 'success',
                confirmButtonText: 'موافق',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                $('#importModal').modal('hide');
                $('.progress-container').hide();
                $('.progress-bar').css('width', '0%');
                $('#importForm')[0].reset();
                $('#saveButton').prop('disabled', false);
                self.reloadDataTable();
                self.loadStats();
            });
        },

        // Handle import with errors
        handleImportWithErrors: function(taskData) {
            const self = this;
            const downloadButton = taskData.file ? 
                `<br><a href="${taskData.file}" class="btn btn-warning mt-3" download="errors.csv" target="_blank">تحميل ملف الأخطاء</a>` : '';
            Swal.fire({
                title: 'اكتمل التحميل مع أخطاء',
                html: `${taskData.message}${downloadButton}`,
                icon: 'warning',
                confirmButtonText: 'موافق',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                $('#importModal').modal('hide');
                $('.progress-container').hide();
                $('#task-progress-message').html('').hide();
                $('.progress-bar').css('width', '0%');
                $('#importForm')[0].reset();
                $('#saveButton').prop('disabled', false);
                self.reloadDataTable();
                self.loadStats();
            });
        },

        // Handle import error
        handleImportError: function(message) {
            Swal.fire({
                title: 'خطأ',
                text: message,
                icon: 'error',
                confirmButtonText: 'موافق',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                $('.progress-container').hide();
                $('.progress-bar').css('width', '0%');
                $('#importForm')[0].reset();
                $('#saveButton').prop('disabled', false);
                $('#task-progress-message').html('').hide();
            });
        },

        // Handle instructors import success
        handleInstructorsImportSuccess: function(taskData) {
            const self = this;
            Swal.fire({
                title: 'تم التحميل بنجاح',
                text: taskData.message || 'تم تحميل البيانات بنجاح',
                icon: 'success',
                confirmButtonText: 'موافق',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                $('#importInstructorsModal').modal('hide');
                $('.progress-container', '#importInstructorsModal').hide();
                $('.progress-bar', '#importInstructorsModal').css('width', '0%');
                $('#importInstructorsForm')[0].reset();
                $('#saveInstructorsButton').prop('disabled', false);
                self.reloadDataTable();
                self.loadStats();
            });
        },

        // Handle instructors import with errors
        handleInstructorsImportWithErrors: function(taskData) {
            const self = this;
            const downloadButton = taskData.file ? 
                `<br><a href="${taskData.file}" class="btn btn-warning mt-3" download="errors.csv" target="_blank">تحميل ملف الأخطاء</a>` : '';
            Swal.fire({
                title: 'اكتمل التحميل مع أخطاء',
                html: `${taskData.message}${downloadButton}`,
                icon: 'warning',
                confirmButtonText: 'موافق',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                $('#importInstructorsModal').modal('hide');
                $('.progress-container', '#importInstructorsModal').hide();
                $('#task-progress-message', '#importInstructorsModal').html('').hide();
                $('.progress-bar', '#importInstructorsModal').css('width', '0%');
                $('#importInstructorsForm')[0].reset();
                $('#saveInstructorsButton').prop('disabled', false);
                self.reloadDataTable();
                self.loadStats();
            });
        },

        // Confirm and delete enrollment
        confirmAndDelete: function(enrollmentId) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم حذف جميع استجابات الاستبيان المرتبطة بهذا التسجيل بشكل نهائي ولن تتمكن من استعادتها!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، احذف!',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.deleteEnrollment(enrollmentId);
                }
            });
        },

        // Delete enrollment
        deleteEnrollment: function(enrollmentId) {
            const $button = $(`.delete-enrollment[data-id="${enrollmentId}"]`);
            this.showLoadingState($button);
            
            $.ajax({
                url: this.config.destroyEndpoint.replace(':enrollment', enrollmentId),
                type: 'DELETE',
                headers: { 
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => {
                    this.handleSuccess('حذف', response, () => {
                        this.reloadDataTable();
                        this.loadStats();
                    });
                },
                error: (xhr) => {
                    this.handleError('حذف', xhr);
                },
                complete: () => {
                    this.hideLoadingState($button);
                }
            });
        },

        // Utility functions
        resetEnrollmentForm: function() {
            $('#singleEnrollmentForm')[0].reset();
            $('#student_id, #course_id').val(null).trigger('change');
            $('#faculty, #program').val('');
        },

        resetFilters: function() {
            $('#filterForm')[0].reset();
            $('.select2-course, .select2-faculty, .select2-program').val(null).trigger('change');
            this.reloadDataTable();

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'تم إعادة تعيين الفلاتر',
                showConfirmButton: false,
                timer: 2000
            });
        },

        reloadDataTable: function(callback) {
            if ($.fn.DataTable.isDataTable('#datatable')) {
                $('#datatable').DataTable().ajax.reload(callback);
            }
        },

        showLoadingState: function($element) {
            const $spinner = $('<span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>');
            $element.prop('disabled', true).after($spinner);
        },

        hideLoadingState: function($element) {
            $element.prop('disabled', false).next('.spinner-border').remove();
        },

        updateProgramsByFaculty: function(facultyId) {
            const $programSelect = $('#filterProgram');
            $programSelect.html('<option value="">اختر البرنامج</option>');

            if (facultyId) {
                $.ajax({
                    url: this.config.programsByFacultyEndpoint,
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: (response) => {
                        if (response && response.length > 0) {
                            response.forEach(program => {
                                $programSelect.append($('<option>').val(program.id).text(program.name));
                            });
                        }
                        $programSelect.trigger('change');
                    },
                    error: (xhr) => {
                        this.handleError('تحميل البرامج', xhr);
                    }
                });
            } else {
                $programSelect.trigger('change');
            }
        },

        // Handle error responses
        handleError: function(action, xhr) {
            let errorMessage = `حدث خطأ أثناء ${action}`;
            
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'خطأ!',
                text: errorMessage
            });
        },

        // Handle success responses
        handleSuccess: function(action, response, onSuccess) {
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
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ!',
                    text: response.message || `حدث خطأ أثناء ${action}`
                });
            }
        }
    };

    // Initialize the module
    Enrollment.init();
});