$(document).ready(() => {
    // === Config & Endpoints ===
    const scriptTag = document.querySelector('script[src*="questionnaire-template-create.js"]');
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

    const showLoadingState = $element => {
        const $spinner = $('<span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>');
        $element.prop('disabled', true).after($spinner);
    };
    const hideLoadingState = $element => {
        $element.prop('disabled', false).next('.spinner-border').remove();
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

    // === Nestable Setup ===
    $('#nestable').nestable({
        maxDepth: 1,
        handleClass: 'dd-handle'
    });

    // === Form Events ===
    $('#is_active').on('change', function() {
        const value = $(this).is(':checked');
        $('#activeStatus').text(value ? 'مفعل' : 'غير مفعل');
        $('#activeStatus').toggleClass('bg-success', value);
        $('#activeStatus').toggleClass('bg-warning', !value);
    });
    $('#editTemplateForm').on('submit', e => {
        e.preventDefault();
        handleFormSubmit($(e.currentTarget));
    });

    // === Question Events ===
    $(document).on('click', '.add-question-btn', function() {
        const $btn = $(this);
        const questionId = $btn.data('id');
        const $questionCard = $btn.closest('.question-card');
        if (!$btn.prop('disabled')) {
            addQuestionToTemplate($btn, $questionCard, questionId);
        }
    });
    $(document).on('click', '.delete-btn', function() {
        const $questionCard = $(this).closest('.dd-item');
        const questionId = $questionCard.data('id');
        removeQuestionFromTemplate($questionCard, questionId);
    });
    $(document).on('click', '.info-btn', function() {
        const $questionCard = $(this).closest('.question-card');
        let questionId = $questionCard.data('id') || $questionCard.data('question-id');
        let questionText = $questionCard.find('p').first().text();
        let questionType = $questionCard.find('.badge').first().text();
        let questionCategory = $questionCard.find('.text-muted').last().text().trim();
        let options = [];
        // Look for the original available question card to get options
        const $availableCard = $(`.question-card[data-question-id='${questionId}']`).first();
        if ($availableCard.length) {
            try {
                options = $availableCard.data('question-options') || [];
            } catch (e) { options = []; }
        }
        showOptionsModal(questionText, questionType, questionCategory, options);
    });
    // Show options modal for available questions
    $(document).on('click', '.show-options-btn', function() {
        const $btn = $(this);
        const $questionCard = $btn.closest('.question-card');
        let options = [];
        let questionText = $questionCard.find('p').first().text();
        let questionType = $questionCard.find('.badge').first().text();
        let questionCategory = $questionCard.find('.text-muted').last().text().trim();
        try {
            options = $questionCard.data('question-options') || [];
        } catch (e) {
            options = [];
        }
        showOptionsModal(questionText, questionType, questionCategory, options);
    });

    // === Modal Events ===
    $('a.btn-outline-secondary').on('click', e => {
        e.preventDefault();
        $('#discardModal').modal('show');
    });
    $('.modal').on('click', function(e) {
        if (e.target === this) $(this).modal('hide');
    });

    // === Search Events ===
    $('#availableQuestionsSearch').on('input', function() {
        const search = $(this).val().toLowerCase();
        let anyVisible = false;
        $('.question-card', '.card-body:has(#availableQuestionsSearch)').each(function() {
            const text = $(this).find('p').text().toLowerCase();
            const match = text.includes(search);
            $(this).toggle(match);
            if (match) anyVisible = true;
        });
        if (anyVisible) {
            $('#availableQuestionsNotFound').hide();
        } else {
            $('#availableQuestionsNotFound').show();
        }
    });
    $('#selectedQuestionsSearch').on('input', function() {
        const search = $(this).val().toLowerCase();
        let anyVisible = false;
        $('#nestable .dd-list .question-card').each(function() {
            const text = $(this).find('p').text().toLowerCase();
            const match = text.includes(search);
            $(this).closest('li').toggle(match);
            if (match) anyVisible = true;
        });
        if (anyVisible) {
            $('#nestable .dd-list .empty-state').hide();
            $('#selectedQuestionsNotFound').hide();
        } else {
            $('#nestable .dd-list .empty-state').hide();
            $('#selectedQuestionsNotFound').show();
        }
    });

    // === Functions ===
    function addQuestionToTemplate($btn, $questionCard, questionId) {
        const questionText = $questionCard.find('p').text();
        const questionType = $questionCard.find('.badge').text();
        const questionCategory = $questionCard.find('.text-muted').text().trim();
        const typeClass = questionType === 'نص' ? 'bg-secondary' : 'bg-primary';
        const $selectedQuestionsContainer = $('#nestable .dd-list');
        $selectedQuestionsContainer.find('.empty-state').remove();
        const $newQuestionCard = $(
            `<li class="dd-item question-card" data-id="${questionId}">
                <div class="card mb-2 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start gap-2">
                            <div class="dd-handle text-muted mt-1">
                                <i class="fa fa-grip-lines"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small fw-medium">${questionText}</p>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge ${typeClass} small">${questionType}</span>
                                    <span class="text-muted small">
                                        <i class="fa fa-tag me-1"></i>${questionCategory}
                                    </span>
                                    <div class="form-check form-switch ms-auto">
                                        <input class="form-check-input required-toggle" type="checkbox" role="switch" id="required-${questionId}" checked>
                                        <label class="form-check-label small" for="required-${questionId}">مطلوب</label>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-link text-muted p-0 info-btn" title="معلومات">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                    <button type="button" class="btn btn-link text-danger p-0 delete-btn" title="حذف">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </li>`
        );
        $selectedQuestionsContainer.append($newQuestionCard);
        $btn.prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
    }

    function removeQuestionFromTemplate($questionCard, questionId) {
        $(`.add-question-btn[data-id="${questionId}"]`).prop('disabled', false).addClass('btn-primary').removeClass('btn-secondary');
        $questionCard.remove();
        if ($('#nestable .dd-list .dd-item').length === 0) {
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

    function showOptionsModal(questionText, questionType, questionCategory, options) {
        let html = '';
        if (options && options.length > 0) {
            html += `<div class='mb-2'><strong>خيارات الإجابة:</strong></div>`;
            html += `<div class='table-responsive'><table class='table table-bordered table-striped align-middle mb-0'><thead class='table-light'><tr><th>#</th><th>النص</th><th>القيمة</th><th>الترتيب</th></tr></thead><tbody>`;
            options.forEach((opt, idx) => {
                html += `<tr><td>${idx + 1}</td><td>${opt.option_text ?? '-'}</td><td>${opt.value ?? '-'}</td><td>${opt.order ?? '-'}</td></tr>`;
            });
            html += '</tbody></table></div>';
        } else {
            html += '<div class="alert alert-info text-center mt-3">لا توجد خيارات لهذا السؤال.</div>';
        }
        $('#questionOptionsModalBody').html(html);
        const modal = new bootstrap.Modal(document.getElementById('questionOptionsModal'));
        modal.show();
    }

    function handleFormSubmit($form) {
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();
        const orderedQuestions = [];
        $('#nestable .dd-list .dd-item').each(function(index) {
            if (!$(this).hasClass('empty-state')) {
                const questionId = $(this).data('id');
                const isRequired = $(this).find('.required-toggle').is(':checked');
                orderedQuestions.push({
                    id: questionId,
                    order: index,
                    is_required: isRequired ? 1 : 0
                });
            }
        });
        const formData = {
            name: $('#templateName').val(),
            description: $('#templateDescription').val(),
            is_active: $('#is_active').is(':checked') ? 1 : 0,
            questions: orderedQuestions
        };
        $.ajax({
            url: endpoints.storeEndpoint || '',
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: () => {
                showLoadingState($submitBtn);
            },
            success: response => {
                if (response.success) {
                    handleSuccess('حفظ', response, () => {
                        window.location.reload();
                    });
                } else {
                    handleError('حفظ', response);
                }
            },
            error: xhr => {
                handleError('حفظ', xhr);
            },
            complete: () => {
                hideLoadingState($submitBtn);
                $submitBtn.text(originalText);
            }
        });
    }
}); 