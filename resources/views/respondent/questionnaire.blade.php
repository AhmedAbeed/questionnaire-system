@extends('layouts.app')

@push('styles')
    <style>
        /* Card hover animation effects */
        .question-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .question-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        }
        
        /* Question number indicator styling */
        .question-number {
            min-width: 32px;
            height: 32px;
            font-weight: bold;
        }
        
        /* Responsive grid for multiple choice options */
        .options-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .options-container {
                grid-template-columns: 1fr;
            }
        }

        /* Checkbox and radio button styling */
        .form-check .form-check-input {
            margin-left: 10px;
        }

        .form-check-input:checked {
            background-color: #931a23 !important;
            border-color: #931a23 !important;
        }

        /* Modern Instructor Select Styling - Specific to instructor selection only */
        .instructor-select-wrapper {
            margin-top: 1rem;
        }

        .instructor-select-wrapper .instructor-select-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            max-height: 600px;
            overflow-y: auto;
            padding: 1rem;
            scrollbar-width: thin;
            scrollbar-color: #931a23 #f0f0f0;
        }

        .instructor-select-wrapper .instructor-select-container::-webkit-scrollbar {
            width: 8px;
        }

        .instructor-select-wrapper .instructor-select-container::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 4px;
        }

        .instructor-select-wrapper .instructor-select-container::-webkit-scrollbar-thumb {
            background-color: #931a23;
            border-radius: 4px;
            border: 2px solid #f0f0f0;
        }

        .instructor-select-wrapper .instructor-select-label {
            display: block;
            cursor: pointer;
        }

        .instructor-select-wrapper .instructor-card {
            background: #fff;
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .instructor-select-wrapper .instructor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .instructor-select-wrapper .instructor-card.selected {
            border-color: #931a23;
            background-color: #fff5f6;
        }

        .instructor-select-wrapper .instructor-image {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .instructor-select-wrapper .instructor-card:hover .instructor-image {
            transform: scale(1.05);
        }

        .instructor-select-wrapper .instructor-info {
            text-align: center;
            width: 100%;
        }

        .instructor-select-wrapper .instructor-name {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .instructor-select-wrapper .instructor-select-hint {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .instructor-select-wrapper .instructor-count {
            background-color: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .instructor-select-wrapper .instructor-radio {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .instructor-select-wrapper .instructor-radio:checked + .instructor-card {
            border-color: #931a23;
            background-color: #fff5f6;
        }

        @media (max-width: 768px) {
            .instructor-select-wrapper .instructor-select-container {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                max-height: none;
                overflow-y: visible;
            }
        }
        
    </style>
@endpush

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('respondent.home'), 'active' => false],
            ['name' => 'الاستبيانات', 'url' => '#', 'active' => false],
            ['name' => 'إجابة الاستبيان', 'url' => '#', 'active' => true],
        ]"
    />
@endsection

@section('content')
    <!-- Page Header -->
    <x-page-header
        :title="'إجابة الاستبيان'"
        :page-description="'الرجاء ملء جميع الحقول المطلوبة.'"
        :action-items="[]"
    />

    <!-- Survey Information Section -->
    <div class="row g-4 mb-4 mt-3">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div class="flex-grow-1">
                            <h3 class="card-title h4 mb-2 fw-bold text-primary">
                                {{ $questionnaireDeployed->name }}
                            </h3>
                            <p class="card-text text-muted mb-0">
                                {{ $questionnaireDeployed->description ?? 'لا يوجد وصف متاح' }}
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="d-flex align-items-center gap-2 text-muted py-2 px-3 bg-light rounded-pill">
                                <i class="bi bi-clock"></i>
                                <span>~{{ $questionnaireDeployed->estimated_time ?? '10' }} دقائق</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Targets Information Section -->
                    @if($questionnaireDeployed->targets->isNotEmpty())
                        <div class="border-top pt-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="bi bi-bullseye text-primary fs-5"></i>
                                <h5 class="h6 mb-0 fw-bold text-primary">المستهدفون</h5>
                            </div>
                            <div class="row g-3">
                                @foreach($questionnaireDeployed->targets->take(2) as $target)
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border-start border-3 border-primary">
                                            @if($target->faculty)
                                                <div class="flex-shrink-0">
                                                    <i class="bi bi-university text-primary fs-4"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-semibold text-dark">الكلية</h6>
                                                    <p class="mb-0 text-muted">{{ $target->faculty->name }}</p>
                                                </div>
                                            @elseif($target->program)
                                                <div class="flex-shrink-0">
                                                    <i class="bi bi-mortarboard text-primary fs-4"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-semibold text-dark">البرنامج</h6>
                                                    <p class="mb-0 text-muted">{{ $target->program->name }}</p>
                                                </div>
                                            @elseif($target->semesterCourse?->course)
                                                <div class="flex-shrink-0">
                                                    <i class="bi bi-book text-primary fs-4"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-semibold text-dark">
                                                        <i class="bi bi-book me-2"></i>المقرر
                                                    </h6>
                                                    <p class="mb-0 text-dark">
                                                        <strong>{{ $target->semesterCourse->course->name }}</strong>
                                                        <small class="d-block text-dark">رمز المقرر: {{ $target->semesterCourse->course->code }}</small>
                                                        @if($target->semesterCourse->semester)
                                                            <small class="d-block text-dark">الفصل الدراسي: {{ $target->semesterCourse->semester->name }}</small>
                                                        @endif
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Survey Form -->
    <form action="{{ route('response.create') }}" method="POST" enctype="multipart/form-data" class="mb-5" id="questionnaire-form">
        @csrf
        <!-- Include the questionnaire ID as a hidden input -->
        <input type="hidden" name="questionnaire_id" value="{{ $questionnaireDeployed->id }}">
        
        <div class="row g-4">
            <!-- Loop through all questions -->
            @foreach ($questionnaireDeployed->deployedQuestions()->with(['question', 'options'])->orderBy('order')->get() as $index => $deployedQuestion)
                <!-- Question Card -->
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-3 overflow-hidden question-card">
                        <div class="card-body p-4">
                            <!-- Question Header -->
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="question-number rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                    {{ $index + 1 }}
                                </div>
                                <h5 class="card-title mb-0 pt-1">
                                    {{ $deployedQuestion->question->text }}
                                    @if ($deployedQuestion->is_required)
                                        <span class="text-danger ms-1">*</span>
                                    @endif
                                </h5>
                            </div>
                            
                            <!-- Question Response Area -->
                            <div>
                                @php
                                    $questionType = $deployedQuestion->question->type->name ?? 'Multiple Choice';
                                @endphp

                                <!-- Multiple Choice Options -->
                                @if ($deployedQuestion->question->type->name === 'Likert Scale' || $deployedQuestion->hasOptions())
                                    @php
                                        $questionOptions = $deployedQuestion->options()->orderBy('order')->get();
                                        $isInstructorSelect = $deployedQuestion->question->type->name === 'Instructor Select';
                                    @endphp
                                    @if ($isInstructorSelect)
                                        <div class="instructor-select-wrapper">
                                            <div class="text-center mb-3">
                                                <span class="instructor-count">
                                                    <i class="bi bi-people-fill me-1"></i>
                                                    {{ count($questionOptions) }} مدرب متاح
                                                </span>
                                            </div>
                                            <div class="instructor-select-container">
                                                @forelse ($questionOptions as $option)
                                                    <label class="instructor-select-label">
                                                        <input 
                                                            class="instructor-radio"
                                                            type="radio" 
                                                            name="responses[{{ $deployedQuestion->id }}][option_id]"
                                                            id="option-{{ $deployedQuestion->id }}-{{ $option->id }}" 
                                                            value="{{ $option->id }}"
                                                            @if ($deployedQuestion->is_required) required @endif
                                                        >
                                                        <div class="instructor-card">
                                                            <div class="text-center">
                                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($option->option_text) }}&background=931a23&color=fff&size=200" 
                                                                     alt="{{ $option->option_text }}" 
                                                                     class="instructor-image"
                                                                >
                                                            </div>
                                                            <div class="instructor-info">
                                                                <div class="instructor-name">{{ $option->option_text }}</div>
                                                                <div class="instructor-select-hint">اضغط لاختيار هذا المدرب</div>
                                                            </div>
                                                        </div>
                                                    </label>
                                                @empty
                                                    <div class="text-muted">لا توجد خيارات متاحة</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    @else
                                        <div class="options-container">
                                            @forelse ($questionOptions as $option)
                                                <div class="form-check form-check d-flex align-items-center justify-content-start mb-2">
                                                    <input 
                                                        class="form-check-input border border-primary"
                                                        type="radio" 
                                                        name="responses[{{ $deployedQuestion->id }}][option_id]"
                                                        id="option-{{ $deployedQuestion->id }}-{{ $option->id }}" 
                                                        value="{{ $option->id }}"
                                                        @if ($deployedQuestion->is_required) required @endif
                                                    >
                                                    <label 
                                                        class="form-check-label" 
                                                        for="option-{{ $deployedQuestion->id }}-{{ $option->id }}"
                                                    >
                                                        {{ $option->option_text }}
                                                    </label>
                                                </div>
                                            @empty
                                                <div class="text-muted">لا توجد خيارات متاحة</div>
                                            @endforelse
                                        </div>
                                    @endif
                                
                                <!-- Text Answer -->
                                @elseif ($questionType == 'Text')
                                    <textarea 
                                        class="form-control" 
                                        name="responses[{{ $deployedQuestion->id }}][text_response]" 
                                        rows="4"
                                        placeholder="أدخل إجابتك هنا..."
                                        @if ($deployedQuestion->is_required) required @endif
                                    ></textarea>
                                
                                <!-- Date Answer -->
                                @elseif ($questionType == 'Date')
                                    <input 
                                        type="date" 
                                        class="form-control" 
                                        name="responses[{{ $deployedQuestion->id }}][text_response]"
                                        @if ($deployedQuestion->is_required) required @endif
                                    >
                                
                                <!-- Time Answer -->
                                @elseif ($questionType == 'Time')
                                    <input 
                                        type="time" 
                                        class="form-control" 
                                        name="responses[{{ $deployedQuestion->id }}][text_response]"
                                        @if ($deployedQuestion->is_required) required @endif
                                    >
                                
                                <!-- Number Answer -->
                                @elseif ($questionType == 'Number')
                                    <input 
                                        type="number" 
                                        class="form-control" 
                                        name="responses[{{ $deployedQuestion->id }}][numeric_value]"
                                        @if ($deployedQuestion->is_required) required @endif
                                    >
                                
                                <!-- File Upload -->
                                @elseif ($questionType == 'File')
                                    <div class="file-upload-wrapper">
                                        <input 
                                            type="file" 
                                            class="form-control" 
                                            name="responses[{{ $deployedQuestion->id }}][text_response]"
                                            accept=".pdf,.doc,.docx"
                                            @if ($deployedQuestion->is_required) required @endif
                                        >
                                        <small class="text-muted mt-1 d-block">الملفات المسموحة: PDF, DOC, DOCX</small>
                                    </div>
                                
                                <!-- Image Upload -->
                                @elseif ($questionType == 'Image')
                                    <div class="image-upload-wrapper">
                                        <input 
                                            type="file" 
                                            class="form-control" 
                                            name="responses[{{ $deployedQuestion->id }}][text_response]"
                                            accept="image/*"
                                            @if ($deployedQuestion->is_required) required @endif
                                        >
                                        <small class="text-muted mt-1 d-block">الملفات المسموحة: صور فقط</small>
                                    </div>
                                @endif
                                
                                <!-- Validation Error Messages -->
                                @error("responses.{$deployedQuestion->id}")
                                    <div class="text-danger fs-6 mt-2">{{ $message }}</div>
                                @enderror
                                @error("responses.{$deployedQuestion->id}.*")
                                    <div class="text-danger fs-6 mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            
            <!-- Form Submit Buttons -->
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-end gap-3">
                            <a 
                                href="{{ url()->previous() }}" 
                                class="btn btn-light rounded-pill px-4 text-black d-flex align-items-center justify-content-center"
                                aria-label="إلغاء"
                            >
                                <i class="bi bi-x me-1"></i> إلغاء
                            </a>
                            <button 
                                type="submit" 
                                class="btn btn-primary rounded-pill px-4"
                                aria-label="إرسال الإجابات"
                            >
                                <i class="bi bi-send me-1"></i> إرسال الإجابات
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('questionnaire-form');
    const questionnaireId = '{{ $questionnaireDeployed->id }}';
    const startTime = new Date().toISOString();
        
    /**
     * Initializes the response data structure
     * @returns {Object} The initial empty response object
     */
    function initializeResponseData() {
        return {
            questionnaire_id: questionnaireId,
            start_time: startTime,
            completion_time: null,
            time_taken: null,
            responses: {
                @foreach ($questionnaireDeployed->deployedQuestions as $deployedQuestion)
                    '{{ $deployedQuestion->id }}': {
                        type: '{{ $deployedQuestion->question->type->id }}',
                        option_id: null,
                        text_response: null,
                        numeric_value: null,
                        file_upload: null
                    },
                @endforeach
            }
        };
    }
    
    // Initialize response data object
    const responseData = initializeResponseData();
    
    /**
     * Collects all form data including files and prepares it for submission
     * @returns {FormData} FormData object with all form inputs
     */
    function collectFormData() {
        const formData = new FormData();
        
        // Add questionnaire ID
        formData.append('questionnaire_id', questionnaireId);
        
        // Add timing data
        const completionTime = new Date().toISOString();
        const start = new Date(startTime);
        const end = new Date(completionTime);
        const timeTaken = Math.floor((end - start) / 1000); // Convert to seconds
        
        responseData.start_time = startTime;
        responseData.completion_time = completionTime;
        responseData.time_taken = timeTaken;
        
        // Process each question response
        Object.keys(responseData.responses).forEach(questionId => {
            const questionSelector = `[name^="responses[${questionId}]"]`;
            const inputs = form.querySelectorAll(questionSelector);
            
            inputs.forEach(input => {
                // Handle different input types
                if (input.type === 'radio' && input.checked) {
                    formData.append(`responses[${questionId}][option_id]`, input.value);
                    responseData.responses[questionId].option_id = input.value;
                } else if (input.type === 'text' || input.tagName === 'TEXTAREA') {
                    if (input.value) {
                        formData.append(`responses[${questionId}][text_response]`, input.value);
                        responseData.responses[questionId].text_response = input.value;
                    }
                } else if (input.type === 'number') {
                    if (input.value) {
                        formData.append(`responses[${questionId}][numeric_value]`, input.value);
                        responseData.responses[questionId].numeric_value = input.value;
                    }
                } else if (input.type === 'date' || input.type === 'time') {
                    if (input.value) {
                        formData.append(`responses[${questionId}][text_response]`, input.value);
                        responseData.responses[questionId].text_response = input.value;
                    }
                } else if (input.type === 'file' && input.files && input.files.length > 0) {
                    // Handle files separately to ensure they're properly sent
                    formData.append(`responses[${questionId}][file]`, input.files[0]);
                    responseData.responses[questionId].file_upload = input.files[0].name;
                }
            });
        });
        
        // Also include the JSON data for server-side processing
        formData.append('responseData', JSON.stringify(responseData));
        
        return formData;
    }
    
    /**
     * Submits the form data via fetch API
     * @param {FormData} formData - The form data to submit
     * @returns {Promise} Promise for the fetch request
     */
    async function submitFormData(formData) {
        const csrfToken = document.querySelector('input[name="_token"]').value;
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                    // Note: Don't set Content-Type with FormData
                },
                body: formData,
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'حدث خطأ أثناء إرسال البيانات');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Submission error:', error);
            throw error;
        }
    }
    
    /**
     * Shows a success message and redirects
     */
    function showSuccessMessage() {
        Swal.fire({
            title: 'تم بنجاح!',
            text: 'تم إرسال إجاباتك بنجاح',
            icon: 'success',
            confirmButtonText: 'حسناً',
            confirmButtonColor: '#931a23'
        }).then(() => {
            window.location.href = '{{ route('respondent.home') }}';
        });
    }
    
    /**
     * Shows an error message with details
     * @param {string} message - Error message to display
     */
    function showErrorMessage(message) {
        Swal.fire({
            title: 'خطأ!',
            text: message || 'حدث خطأ أثناء إرسال البيانات',
            icon: 'error',
            confirmButtonText: 'حسناً',
            confirmButtonColor: '#931a23'
        });
    }
    
    /**
     * Sets the form to loading state
     * @param {boolean} isLoading - Whether to show loading state
     */
    function setLoadingState(isLoading) {
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        
        if (isLoading) {
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> جاري الإرسال...';
            submitButton.disabled = true;
        } else {
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
        }
    }
    
    /**
     * Validates required fields before submission
     * @returns {boolean} Whether all required fields are filled
     */
    function validateForm() {
        const requiredInputs = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredInputs.forEach(input => {
            // Check if it's a radio button group
            if (input.type === 'radio') {
                const name = input.name;
                const radioGroup = form.querySelectorAll(`[name="${name}"]`);
                
                // Check if any radio in the group is checked
                const isChecked = Array.from(radioGroup).some(radio => radio.checked);
                
                if (!isChecked) {
                    // Get the question element
                    const questionCard = input.closest('.question-card');
                    if (questionCard) {
                        questionCard.classList.add('border-danger');
                        setTimeout(() => questionCard.classList.remove('border-danger'), 3000);
                    }
                    isValid = false;
                }
            } 
            // For other input types
            else if (!input.value.trim()) {
                input.classList.add('is-invalid');
                setTimeout(() => input.classList.remove('is-invalid'), 3000);
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Handles form submission
     * @param {Event} event - The form submit event
     */
    async function handleFormSubmit(event) {
        event.preventDefault();
        
        if (!validateForm()) {
            showErrorMessage('يرجى ملء جميع الحقول المطلوبة');
            return;
        }
        
        setLoadingState(true);
        
        try {
            const formData = collectFormData();
            const result = await submitFormData(formData);
            setLoadingState(false);
            showSuccessMessage();
        } catch (error) {
            setLoadingState(false);
            showErrorMessage(error.message);
        }
    }
    
    // Add form submission handler
    form.addEventListener('submit', handleFormSubmit);
});
</script>
@endpush