$(document).ready(() => {
    // === Config & Endpoints ===
    const endpoints = window.routes || {};

    // === Helpers ===
    const showAlert = (type, title, text) => {
        Swal.fire({ icon: type, title, text, confirmButtonText: 'حسناً' });
    };

    const showToast = (type, title) => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: title,
            showConfirmButton: false,
            timer: 3000
        });
    };

    const statCards = ['total-deployed-questionnaires'];

    const showStatErrorState = () => {
        statCards.forEach(id => {
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
    };

    const ajaxDefaults = {
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    };

    const showLoadingState = $element => {
        const $spinner = $('<span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>');
        $element.prop('disabled', true).after($spinner);
    };

    const hideLoadingState = $element => {
        $element.prop('disabled', false).next('.spinner-border').remove();
    };

    const reloadDataTable = callback => {
        if ($.fn.DataTable.isDataTable('#datatable')) {
            $('#datatable').DataTable().ajax.reload(callback);
        }
    };

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
        if (response.success !== false) {
            if (typeof onSuccess === 'function') onSuccess();
            showToast('success', response.message || `تم ${action} بنجاح`);
        } else {
            showAlert('error', 'خطأ!', response.message || `حدث خطأ أثناء ${action}`);
        }
    };

    // --- Stats ---
    const populateStats = data => {
        try {
            if (data && data.total_deployed_questionnaires) {
                const stats = data.total_deployed_questionnaires;
                $('#total-deployed-questionnaires-value').html(`<h4>${stats.value || 0}</h4>`);
                $('#total-deployed-questionnaires-updated').html(`${stats.updated || 'غير محدد'}`);
            } else {
                showStatErrorState();
            }
        } catch (error) {
            console.error('Error populating stats:', error);
            showStatErrorState();
        }
    };

    const loadStats = () => {
        $.ajax({
            ...ajaxDefaults,
            url: endpoints.statsEndpoint,
            method: 'GET',
            success: res => {
                if (res.success !== false && res.data) populateStats(res.data);
                else {
                    showAlert('error', 'خطأ!', res.message || 'خطأ في تحميل البيانات');
                    showStatErrorState();
                }
            },
            error: xhr => {
                console.error('Error loading stats:', xhr);
                handleError('تحميل البيانات', xhr);
                showStatErrorState();
            }
        });
    };

    // --- Select2 Configuration ---
    const initSelect2 = () => {
        const select2Config = {
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            minimumResultsForSearch: 5,
            language: {
                noResults: () => "لا توجد نتائج",
                searching: () => "جاري البحث..."
            }
        };

        $('.select2-target-type').select2({ ...select2Config, placeholder: 'اختر نوع المستهدف' });
        $('.select2-course').select2({ ...select2Config, placeholder: 'اختر المقرر' });
        $('.select2-semester').select2({ ...select2Config, placeholder: 'اختر الفصل الدراسي' });
        $('.select2-faculty').select2({ ...select2Config, placeholder: 'اختر الكلية' });
        $('.select2-program').select2({ ...select2Config, placeholder: 'اختر البرنامج' });
    };

    // --- Filter Handlers ---
    const initFilters = () => {
        // Show initial target section
        $('#courseTargetSection').show();
        $('#facultyTargetSection').hide();

        // Reset filters
        $('#resetFilters').click(() => {
            $('#filterForm')[0].reset();
            $('.select2-target-type, .select2-course, .select2-semester, .select2-faculty, .select2-program').val(null).trigger('change');
            reloadDataTable();
        });

        // Search with debounce
        let searchTimeout;
        $('#filterName').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => reloadDataTable(), 500);
        });

        // Filter change handlers
        $('#filterTargetType, #filterCourse, #filterSemester, #filterStartDate, #filterEndDate, #filterFaculty, #filterProgram').on('change', function() {
            const $this = $(this);
            showLoadingState($this);
            reloadDataTable(() => hideLoadingState($this));
        });

        // Date validation
        $('#filterStartDate, #filterEndDate').on('change', function() {
            const startDate = $('#filterStartDate').val();
            const endDate = $('#filterEndDate').val();
            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                showToast('error', 'تاريخ البدء يجب أن يكون قبل تاريخ الانتهاء');
                $(this).val('');
                reloadDataTable();
            }
        });

        // Target type toggle
        $('input[name="targetType"]').on('change', function() {
            const targetType = $(this).val();
            $('.target-section').hide();
            if (targetType === '1') {
                $('#courseTargetSection').fadeIn();
            } else if (targetType === '3') {
                $('#facultyTargetSection').fadeIn();
            }
        });

        // Faculty change handler
        $('#filterFaculty').on('change', function() {
            const facultyId = $(this).val();
            const $programSelect = $('#filterProgram');
            
            $programSelect.html('<option value="">اختر البرنامج</option>').trigger('change');
            
            if (facultyId) {
                $programSelect.prop('disabled', true).html('<option value="">جاري التحميل...</option>');
                
                $.ajax({
                    url: endpoints.programsByFaculty,
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: response => {
                        $programSelect.html('<option value="">اختر البرنامج</option>');
                        if (response && Array.isArray(response)) {
                            response.forEach(program => {
                                $programSelect.append($('<option></option>').val(program.id).text(program.name));
                            });
                        }
                    },
                    error: xhr => {
                        console.error('Error loading programs:', xhr);
                        $programSelect.html('<option value="">خطأ في التحميل</option>');
                        showToast('error', 'حدث خطأ أثناء تحميل البرامج');
                    },
                    complete: () => $programSelect.prop('disabled', false).trigger('change')
                });
            } else {
                $programSelect.prop('disabled', false).html('<option value="">اختر البرنامج</option>').trigger('change');
            }
        });

        // Toggle filters section
        $('#toggleFilters').on('click', function() {
            const $button = $(this);
            const $section = $('#filtersSection');
            const $icon = $button.find('i');
            const $label = $button.find('span');
            
            $section.slideToggle(300, function() {
                if ($section.is(':visible')) {
                    $icon.removeClass('fa-filter').addClass('fa-times');
                    $label.text('إغلاق خيارات البحث');
                    $button.removeClass('btn-outline-primary').addClass('btn-outline-danger');
                } else {
                    $icon.removeClass('fa-times').addClass('fa-filter');
                    $label.text('خيارات البحث');
                    $button.removeClass('btn-outline-danger').addClass('btn-outline-primary');
                }
            });
        });
    };

    // --- Modal Handlers ---
    const initModals = () => {
        let editCloseDateModal, exportNonRespondingModal;
        
        try {
            const editModalElement = document.getElementById('editCloseDateModal');
            const exportModalElement = document.getElementById('exportNonRespondingModal');
            
            if (editModalElement) editCloseDateModal = new bootstrap.Modal(editModalElement);
            if (exportModalElement) exportNonRespondingModal = new bootstrap.Modal(exportModalElement);
        } catch (error) {
            console.warn('Bootstrap modals not available:', error);
        }

        // Edit close date
        $(document).on('click', '.edit-close-date', function() {
            if (!editCloseDateModal) {
                console.error('Edit close date modal not initialized');
                return;
            }
            
            const id = $(this).data('id');
            const closeDate = $(this).data('close-date');
            $('#questionnaireId').val(id);
            $('#closeDate').val(closeDate);
            editCloseDateModal.show();
        });

        // Save close date
        $('#saveCloseDate').click(function() {
            const $button = $(this);
            const $spinner = $button.find('.spinner-border');
            const $buttonText = $button.find('.btn-text');
            const form = $('#editCloseDateForm');
            const id = $('#questionnaireId').val();
            const closeDate = $('#closeDate').val();

            if (!id || !closeDate) {
                showToast('error', 'يرجى ملء جميع الحقول المطلوبة');
                return;
            }

            $button.prop('disabled', true);
            $spinner.removeClass('d-none');
            $buttonText.text('جاري الحفظ...');

            $.ajax({
                url: endpoints.updateCloseDate.replace('PLACEHOLDER_ID', id),
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify({ close_date: closeDate }),
                success: response => {
                    if (editCloseDateModal) editCloseDateModal.hide();
                    form[0].reset();
                    reloadDataTable();
                    handleSuccess('تحديث تاريخ الإغلاق', response);
                },
                error: xhr => handleError('تحديث تاريخ الإغلاق', xhr),
                complete: () => {
                    $button.prop('disabled', false);
                    $spinner.addClass('d-none');
                    $buttonText.text('حفظ التغييرات');
                }
            });
        });

        // Export non-responding
        $('#exportNonResponding').click(function() {
            const $button = $(this);
            const $spinner = $button.find('.spinner-border');
            const $buttonText = $button.find('.btn-text');
            const form = $('#exportNonRespondingForm');
            const facultyId = $('#faculty').val();
            const semesterId = $('#semester').val();

            if (!facultyId || !semesterId) {
                showToast('error', 'يرجى اختيار الكلية والفصل الدراسي');
                return;
            }

            $button.prop('disabled', true);
            $spinner.removeClass('d-none');
            $buttonText.text('جاري التصدير...');

            const downloadForm = $('<form>', {
                'method': 'POST',
                'action': endpoints.exportNonResponding,
                'style': 'display: none;'
            });

            downloadForm.append(
                $('<input>', { 'type': 'hidden', 'name': '_token', 'value': $('meta[name="csrf-token"]').attr('content') }),
                $('<input>', { 'type': 'hidden', 'name': 'faculty_id', 'value': facultyId }),
                $('<input>', { 'type': 'hidden', 'name': 'semester_id', 'value': semesterId })
            );

            $('body').append(downloadForm);
            downloadForm.submit();
            downloadForm.remove();

            setTimeout(() => {
                form[0].reset();
                if (exportNonRespondingModal) exportNonRespondingModal.hide();
                $button.prop('disabled', false);
                $spinner.addClass('d-none');
                $buttonText.text('تصدير');
            }, 1000);
        });

        // Show export modal
        $(document).on('click', '.export-non-responding', function() {
            if (exportNonRespondingModal) exportNonRespondingModal.show();
        });
    };

    // === Init ===
    initSelect2();
    initFilters();
    initModals();
    loadStats();
});