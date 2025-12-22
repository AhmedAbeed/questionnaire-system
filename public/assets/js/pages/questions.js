$(document).ready(() => {
    // === Config & Endpoints ===
    const scriptTag = document.querySelector('script[src*="questions.js"]');
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

    const statCards = ['total-questions'];

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

    const select2Config = {
        theme: 'bootstrap-5',
        language: {
            noResults: () => "لا توجد نتائج",
            searching: () => "جاري البحث..."
        },
        dir: 'rtl'
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

    // === Question Management ===
    let questionCount = 0;

    const updateQuestionNumbers = () => {
        try {
            $('.question-item').each((index, element) => {
                $(element).find('.question-number').text(index + 1);
            });
        } catch (error) {
            console.error('Error updating question numbers:', error);
            handleError('تحديث أرقام الأسئلة', { responseJSON: { message: 'حدث خطأ في تحديث أرقام الأسئلة' } });
        }
    };

    const updateQuestionIndices = () => {
        $('.col-12').each((index, element) => {
            $(element).find('[name^="questions["]').each((_, input) => {
                const name = $(input).attr('name');
                $(input).attr('name', name.replace(/questions\[\d+\]/, `questions[${index}]`));
            });
        });
    };

    const createQuestionElement = () => {
        try {
            const $questionElement = $('<div>').addClass('col-12');
            $questionElement.html($('#questionTemplate').html());
            
            const currentIndex = $('#questionsContainer').children().length;
            
            $questionElement.find('[name^="questions["]').each((_, element) => {
                const name = $(element).attr('name');
                $(element).attr('name', name.replace(/questions\[\d*\]/, `questions[${currentIndex}]`));
            });
            
            return $questionElement;
        } catch (error) {
            console.error('Error creating question element:', error);
            handleError('إنشاء عنصر السؤال', { responseJSON: { message: 'حدث خطأ في إنشاء عنصر السؤال' } });
            return null;
        }
    };

    const toggleQuestionContainers = ($optionsContainer, $likertContainer, $ratingContainer, $instructorNote, questionType) => {
        $optionsContainer.hide();
        $likertContainer.hide();
        $ratingContainer.hide();
        $instructorNote.addClass('d-none');

        if (questionType === 'Multiple Choice' || questionType === 'Single Choice') {
            $optionsContainer.show();
        } else if (questionType === 'Likert Scale') {
            $likertContainer.show();
        } else if (questionType === 'Rating') {
            $ratingContainer.show();
        } else if (questionType === 'Instructor Select') {
            $instructorNote.removeClass('d-none');
        }
    };

    const addOption = ($optionsList, questionIndex) => {
        const $optionItem = $('<div>').addClass('option-item mb-2');
        $optionItem.html(`
            <div class="input-group">
                <input type="text" class="form-control border border-primary bg-white" name="questions[${questionIndex}][options][]" placeholder="أدخل الخيار">
                <button type="button" class="btn btn-outline-danger remove-option">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        `);
        $optionsList.append($optionItem);
    };

    const initializeLikertOptions = ($questionElement) => {
        const $likertContainer = $questionElement.find('.likert-container');
        const $pointsSelect = $likertContainer.find('.likert-points');
        const $likertTypeSelect = $likertContainer.find('.likert-type');
        const $optionsList = $likertContainer.find('.likert-options-list');
        const $addOptionBtn = $likertContainer.find('.add-likert-option');
        const $questionTypeSelect = $questionElement.find('.question-type');

        const questionIndex = $('#questionsContainer').children().index($questionElement);

        $pointsSelect.attr('name', `questions[${questionIndex}][likert_points]`);
        $likertTypeSelect.attr('name', `questions[${questionIndex}][likert_type]`);

        const toggleRequired = (isRequired) => {
            $optionsList.find('input').prop('required', isRequired);
        };

        toggleRequired(false);

        const defaultOptions = {
            satisfaction: {
                3: [
                    { text: 'ضعيف', value: 1 },
                    { text: 'متوسط', value: 2 },
                    { text: 'جيد', value: 3 }
                ],
                5: [
                    { text: 'ضعيف', value: 1 },
                    { text: 'مقبول', value: 2 },
                    { text: 'جيد', value: 3 },
                    { text: 'جيد جدا', value: 4 },
                    { text: 'ممتاز', value: 5 }
                ]
            },
            agreement: {
                3: [
                    { text: 'غير موافق', value: 1 },
                    { text: 'محايد', value: 2 },
                    { text: 'موافق', value: 3 }
                ],
                5: [
                    { text: 'غير موافق بشدة', value: 1 },
                    { text: 'غير موافق', value: 2 },
                    { text: 'محايد', value: 3 },
                    { text: 'موافق', value: 4 },
                    { text: 'موافق بشدة', value: 5 }
                ]
            },
            importance: {
                3: [
                    { text: 'غير مهم', value: 1 },
                    { text: 'متوسط الأهمية', value: 2 },
                    { text: 'مهم', value: 3 }
                ],
                5: [
                    { text: 'غير مهم', value: 1 },
                    { text: 'قليل الأهمية', value: 2 },
                    { text: 'متوسط الأهمية', value: 3 },
                    { text: 'مهم', value: 4 },
                    { text: 'مهم جدا', value: 5 }
                ]
            }
        };

        const createOptionElement = (text, value, order) => {
            return $(`
                <div class="likert-option-card card border-0 shadow-sm mb-3 new-option">
                    <div class="card-body p-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-auto">
                                <div class="likert-option-number d-flex align-items-center justify-content-center bg-primary text-white rounded-circle" 
                                     style="width: 40px; height: 40px; font-weight: bold; font-size: 14px;">
                                    ${order}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted mb-2">
                                    <i class="fa fa-font me-1"></i> نص الخيار
                                </label>
                                <input type="text" class="form-control form-control border-2 border-primary-subtle bg-light likert-option-input" 
                                       name="questions[${questionIndex}][options][]" 
                                       value="${text}" placeholder="أدخل نص الخيار" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold text-muted mb-2">
                                    <i class="fa fa-hashtag me-1"></i> القيمة
                                </label>
                                <input type="number" class="form-control form-control border-2 border-primary-subtle bg-light likert-option-value" 
                                       name="questions[${questionIndex}][values][]" 
                                       value="${value}" min="1" max="${$pointsSelect.val()}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold text-muted mb-2">
                                    <i class="fa fa-sort-numeric-up me-1"></i> الترتيب
                                </label>
                                <input type="number" class="form-control form-control border-2 border-primary-subtle bg-light likert-option-order" 
                                       name="questions[${questionIndex}][orders][]" 
                                       value="${order}" min="1" required>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-danger btn-lg rounded-circle remove-likert-option-btn remove-likert-option" 
                                        title="حذف الخيار" style="width: 45px; height: 45px;">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        };

        const updateOptions = () => {
            let points = parseInt($pointsSelect.val());
            // Only allow 3 or 5 points
            if (points !== 3 && points !== 5) {
                points = 5;
                $pointsSelect.val('5');
            }
            const type = $likertTypeSelect.val();
            $optionsList.empty();

            if (type === 'custom') {
                for (let i = 1; i <= points; i++) {
                    $optionsList.append(createOptionElement('', i, i));
                }
            } else if (defaultOptions[type] && defaultOptions[type][points]) {
                defaultOptions[type][points].forEach((option, index) => {
                    $optionsList.append(createOptionElement(option.text, option.value, index + 1));
                });
            }
            
            updatePreview();
        };

        const updatePreview = () => {
            const $previewContainer = $likertContainer.find('.likert-scale-preview');
            const $options = $optionsList.find('.likert-option-card');
            
            if ($options.length === 0) {
                $previewContainer.html(`
                    <div class="text-center py-4">
                        <i class="fa fa-info-circle text-muted fs-1 mb-3"></i>
                        <p class="text-muted mb-0">لا توجد خيارات لعرضها</p>
                    </div>
                `);
                return;
            }
            
            let previewHTML = '<div class="row g-2 justify-content-center">';
            $options.each((index, element) => {
                const $option = $(element);
                const text = $option.find('input[name*="[options][]"]').val() || `خيار ${index + 1}`;
                const value = $option.find('input[name*="[values][]"]').val() || index + 1;
                
                previewHTML += `
                    <div class="col-auto">
                        <div class="likert-preview-item card border-2 border-primary-subtle bg-light text-center p-3" 
                             style="min-width: 120px;" title="${text} (${value})">
                            <div class="fw-bold text-primary mb-1">${value}</div>
                            <div class="small text-muted">${text}</div>
                        </div>
                    </div>
                `;
                
                if (index < $options.length - 1) {
                    previewHTML += `
                        <div class="col-auto d-flex align-items-center">
                            <div class="likert-preview-connector">
                                <i class="fa fa-arrow-right text-muted"></i>
                            </div>
                        </div>
                    `;
                }
            });
            previewHTML += '</div>';
            
            $previewContainer.html(previewHTML);
        };

        $pointsSelect.on('change', updateOptions);
        $likertTypeSelect.on('change', updateOptions);
        
        $addOptionBtn.on('click', () => {
            const currentOptions = $optionsList.children().length;
            const maxOptions = parseInt($pointsSelect.val());
            
            if (currentOptions < maxOptions) {
                const $newOption = createOptionElement('', currentOptions + 1, currentOptions + 1);
                $optionsList.append($newOption);
                
                setTimeout(() => {
                    $newOption.removeClass('new-option');
                }, 500);
                
                updatePreview();
                
                if (typeof toastr !== 'undefined') {
                    toastr.success(`تم إضافة الخيار ${currentOptions + 1} بنجاح`);
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.warning(`لا يمكن إضافة أكثر من ${maxOptions} خيارات`);
                }
            }
        });

        $optionsList.on('click', '.remove-likert-option', function() {
            const $optionItem = $(this).closest('.likert-option-card');
            if ($optionsList.children().length > 1) {
                $optionItem.fadeOut(300, function() {
                    $(this).remove();
                    $optionsList.children().each((index, element) => {
                        $(element).find(`input[name="questions[${questionIndex}][orders][]"]`).val(index + 1);
                        $(element).find('.likert-option-number').text(index + 1);
                    });
                    updatePreview();
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.success('تم حذف الخيار بنجاح');
                    }
                });
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error('يجب أن يكون هناك خيار واحد على الأقل');
                }
            }
        });

        $optionsList.on('input', 'input', updatePreview);

        $questionTypeSelect.on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const questionType = selectedOption.data('type');
            toggleRequired(questionType === 'Likert Scale');
        });

        updateOptions();
    };

    const initializeQuestion = ($questionElement) => {
        const $typeSelect = $questionElement.find('.question-type');
        const $optionsContainer = $questionElement.find('.options-container');
        const $likertContainer = $questionElement.find('.likert-container');
        const $ratingContainer = $questionElement.find('.rating-container');
        const $instructorNote = $typeSelect.closest('.mb-4').find('.instructor-note');
        const $optionsList = $questionElement.find('.options-list');
        const $addOptionBtn = $questionElement.find('.add-option');
        const $removeQuestionBtn = $questionElement.find('.remove-question');
        const $categorySelect = $questionElement.find('.question-category');

        $typeSelect.select2(select2Config);
        $categorySelect.select2(select2Config);

        const questionIndex = $('#questionsContainer').children().index($questionElement);

        $questionElement.find('[name^="questions[]["]').each((_, element) => {
            const name = $(element).attr('name');
            $(element).attr('name', name.replace('questions[]', `questions[${questionIndex}]`));
        });

        $typeSelect.on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const questionType = selectedOption.data('type');
            toggleQuestionContainers($optionsContainer, $likertContainer, $ratingContainer, $instructorNote, questionType);
            
            if ((questionType === 'Multiple Choice' || questionType === 'Single Choice') && $optionsList.children().length === 0) {
                addOption($optionsList, questionIndex);
            }
        });

        $addOptionBtn.on('click', function() {
            addOption($optionsList, questionIndex);
        });

        $removeQuestionBtn.on('click', function() {
            if ($('#questionsContainer').children().length > 1) {
                $questionElement.remove();
                updateQuestionIndices();
                updateQuestionNumbers();
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error('يجب أن يكون هناك سؤال واحد على الأقل');
                }
            }
        });

        $optionsList.on('click', '.remove-option', function() {
            $(this).closest('.option-item').remove();
        });

        initializeLikertOptions($questionElement);
    };

    const addNewQuestion = () => {
        try {
            const $questionElement = createQuestionElement();
            if ($questionElement) {
                $('#questionsContainer').append($questionElement);
                initializeQuestion($questionElement);
                questionCount++;
                updateQuestionNumbers();
            }
        } catch (error) {
            console.error('Error adding new question:', error);
            handleError('إضافة سؤال جديد', { responseJSON: { message: 'حدث خطأ في إضافة سؤال جديد' } });
        }
    };

    // === Form Handlers ===
    const setupFormHandlers = () => {
        $('#questionsForm').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const formData = new FormData(this);
            const cleanedData = new FormData();
            
            try {
                for (let [key, value] of formData.entries()) {
                    if (key.endsWith('[]') && !value) {
                        continue;
                    }
                    if (key.includes('rating_stars')) {
                        continue;
                    }
                    cleanedData.append(key, value);
                }
                
                $.ajax({
                    ...ajaxDefaults,
                    url: endpoints.storeEndpoint || window.config?.routes?.storeEndpoint,
                    type: 'POST',
                    data: cleanedData,
                    processData: false,
                    contentType: false,
                    success: response => {
                        handleSuccess('حفظ الأسئلة', response, () => {
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        });
                    },
                    error: xhr => handleError('حفظ الأسئلة', xhr)
                });
            } catch (error) {
                console.error('Error submitting form:', error);
                handleError('حفظ الأسئلة', { responseJSON: { message: 'حدث خطأ أثناء معالجة النموذج' } });
            }
        });
    };

    const setupQuestionHandlers = () => {
        try {
            $('#addQuestion').on('click', addNewQuestion);
        } catch (error) {
            console.error('Error setting up question handlers:', error);
            handleError('إعداد معالجات الأسئلة', { responseJSON: { message: 'حدث خطأ في إعداد معالجات الأسئلة' } });
        }
    };

    // === Initialization ===
    const initializeSelect2 = () => {
        try {
            $('.form-select').select2(select2Config);
        } catch (error) {
            console.error('Error initializing Select2:', error);
            handleError('تهيئة القوائم المنسدلة', { responseJSON: { message: 'حدث خطأ في تهيئة القوائم المنسدلة' } });
        }
    };

    const setupSessionRefresh = () => {
        setInterval(() => {
            $.get(endpoints.sessionRefreshEndpoint || window.config?.routes?.sessionRefreshEndpoint).done((data) => {
                if (data.success) {
                    $('meta[name="csrf-token"]').attr('content', data.token);
                }
            }).fail((xhr) => {
                if (xhr.status === 401) {
                    window.location.href = window.config.routes.loginEndpoint;
                }
            });
        }, 30 * 60 * 1000);
    };

    const init = () => {
        try {
            initializeSelect2();
            setupFormHandlers();
            setupQuestionHandlers();
            addNewQuestion();
            setupSessionRefresh();
        } catch (error) {
            console.error('Error initializing Questions module:', error);
            handleError('تهيئة وحدة الأسئلة', { responseJSON: { message: 'حدث خطأ في تهيئة وحدة الأسئلة' } });
        }
    };

    // === Init ===
    init();
}); 