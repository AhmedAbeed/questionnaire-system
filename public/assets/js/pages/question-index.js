$(document).ready(() => {

    // === Config & Endpoints ===
    const scriptTag = document.querySelector('script[src*="question-index.js"]');
    let endpoints = {};
    if (scriptTag && scriptTag.dataset.endpoints) {
        try {
            endpoints = JSON.parse(scriptTag.dataset.endpoints);
        } catch (e) {
            console.error('Failed to parse endpoints data attribute:', e);
        }
    }

    // === Stat Card IDs ===
    const statCardIds = [
        'total-questions',
        'total-question-types',
        'total-question-categories'
    ];

    // === Helpers ===
    const showAlert = (type, title, text) => {
        Swal.fire({ icon: type, title, text, confirmButtonText: 'حسناً' });
    };

    const showStatErrorState = () => {
        statCardIds.forEach(id => {
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
            if (typeof onSuccess === 'function') onSuccess();
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

    const showLoadingState = ($element) => {
        const $spinner = $('<span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>');
        $element.prop('disabled', true).after($spinner);
    };

    const hideLoadingState = ($element) => {
        $element.prop('disabled', false).next('.spinner-border').remove();
    };

    // === Stats Loading ===
    const loadStats = () => {
        $.ajax({
            url: endpoints.statsEndpoint,
            method: 'GET',
            success: (response) => {
                const data = response.data;
                $('#total-questions-value').html(`<h4>${data.total_questions.value}</h4>`);
                $('#total-questions-updated').html(`${data.total_questions.updated}`);
                $('#total-question-types-value').html(`<h4>${data.total_questions_types.value}</h4>`);
                $('#total-question-types-updated').html(`${data.total_questions_types.updated}`);
                $('#total-question-categories-value').html(`<h4>${data.total_questions_categories.value}</h4>`);
                $('#total-question-categories-updated').html(`${data.total_questions_categories.updated}`);
            },
            error: (xhr) => {
                handleError('تحميل البيانات', xhr);
                showStatErrorState();
            }
        });
    };

    // === Delete Handlers ===
    const confirmAndDelete = (questionId) => {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'سيتم حذف هذا السؤال بشكل نهائي ولن تتمكن من استعادته!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، احذف!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteQuestion(questionId);
            }
        });
    };

    const deleteQuestion = (questionId) => {
        const $button = $(`.delete-item[data-id="${questionId}"]`);
        showLoadingState($button);
        $.ajax({
            url: `/questions/${questionId}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                handleSuccess('حذف', response, () => {
                    $('#datatable').DataTable().ajax.reload();
                    loadStats();
                });
            },
            error: (xhr) => {
                handleError('حذف', xhr);
            },
            complete: () => {
                hideLoadingState($button);
            }
        });
    };

    const setupDeleteHandlers = () => {
        $(document).on('click', '.delete-item', function() {
            const id = $(this).data('id');
            confirmAndDelete(id);
        });
    };

    // === Show Options Handler (icon button) ===
    $(document).on('click', '.show-options', function() {
        const optionsData = $(this).data('options');
        let options = [];
        try {
            options = typeof optionsData === 'string' ? JSON.parse(optionsData) : optionsData;
        } catch (e) {
            options = [];
        }
        if (options && options.length > 0) {
            // Check if any option has value/order
            const hasValue = options.some(opt => opt.value && opt.value !== '');
            const hasOrder = options.some(opt => (opt.order !== undefined && opt.order !== null && opt.order !== ''));

            let html = `
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th> النص</th>
                                ${hasValue ? "<th> القيمة</th>" : ''}
                                ${hasOrder ? "<th> الترتيب</th>" : ''}
                            </tr>
                        </thead>
                        <tbody>
            `;
            options.forEach((opt, idx) => {
                html += `<tr>`;
                html += `<td>${idx + 1}</td>`;
                html += `<td> ${opt.option_text ?? '-'}</td>`;
                if (hasValue) {
                    html += `<td>${opt.value ? ` ${opt.value}` : '-'}</td>`;
                }
                if (hasOrder) {
                    html += `<td>${(opt.order || opt.order === 0) ? ` ${opt.order}` : '-'}</td>`;
                }
                html += `</tr>`;
            });
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            $('#questionOptionsModalBody').html(html);
            const modal = new bootstrap.Modal(document.getElementById('questionOptionsModal'));
            modal.show();
        } else {
            $('#questionOptionsModalBody').html('<div class="alert alert-info text-center">لا توجد خيارات لهذا السؤال.</div>');
        }
    });

    // === Init ===
    const init = () => {
        loadStats();
        setupDeleteHandlers();
    };

    init();
}); 