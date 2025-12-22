$(document).ready(() => {
    // === Config & Endpoints ===
    const scriptTag = document.querySelector('script[src*="questionniare-template-index.js"]');
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

    const statCards = ['total-questionnaire-templates'];

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

    // === Stats ===
    const populateStats = data => {
        try {
            $('#total-questionnaire-templates-value').html(`<h4>${data.total_questionnaire_templates.value}</h4>`);
            $('#total-questionnaire-templates-updated').html(`${data.total_questionnaire_templates.updated}`);
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

    // === Loading State ===
    const showLoadingState = $element => {
        const $spinner = $('<span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>');
        $element.prop('disabled', true).after($spinner);
    };
    const hideLoadingState = $element => {
        $element.prop('disabled', false).next('.spinner-border').remove();
    };

    // === Error/Success Handlers ===
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

    // === Init ===
    loadStats();
}); 