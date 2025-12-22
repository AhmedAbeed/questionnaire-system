$(document).ready(() => {
    // === Config & Endpoints ===
    const scriptTag = document.querySelector('script[src*="student.js"]');
    let endpoints = {};
    if (scriptTag && scriptTag.dataset.endpoints) {
        try {
            endpoints = JSON.parse(scriptTag.dataset.endpoints);
        } catch (e) {
            console.error('Failed to parse endpoints data attribute:', e);
        }
    }

    // === Helpers ===
    const showAlert = (type, title, text) => {
        Swal.fire({ icon: type, title, text, confirmButtonText: 'حسناً' });
    };

    const statCards = ['total-students'];

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

    // --- Stats ---
    const populateStats = data => {
        try {
            $('#total-students-value').html(`<h4>${data.total_students.value}</h4>`);
            $('#total-students-updated').html(`${data.total_students.updated}`);
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
                showAlert('error', 'خطأ!', xhr.responseJSON?.message || 'خطأ في تحميل البيانات');
                showStatErrorState();
            }
        });
    };

    // --- Delete Student ---
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

    // --- Delete Handlers ---
    $(document).on('click', '.delete-student', function() {
        const studentId = $(this).data('id');
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'سيتم حذف الطالب وجميع التسجيلات والاستبيانات واستجاباتها المرتبطة به بشكل نهائي ولن تتمكن من استعادتها!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، احذف!',
            cancelButtonText: 'إلغاء'
        }).then(result => {
            if (result.isConfirmed) {
                const $button = $(`.delete-student[data-id="${studentId}"]`);
                showLoadingState($button);
                $.ajax({
                    ...ajaxDefaults,
                    url: endpoints.destroyEndpoint.replace(':student', studentId),
                    type: 'DELETE',
                    success: response => {
                        handleSuccess('حذف', response, () => {
                            reloadDataTable();
                            loadStats();
                        });
                    },
                    error: xhr => handleError('حذف', xhr),
                    complete: () => hideLoadingState($button)
                });
            }
        });
    });

    // === Init ===
    loadStats();
});