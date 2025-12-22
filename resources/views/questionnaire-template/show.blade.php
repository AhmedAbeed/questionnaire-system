@extends('layouts.app')

@section('breadcrumb')
    {{-- Breadcrumb Section --}}
    <x-layouts.breadcrumbbar
        title="عرض النموذج"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('admin.home'), 'active' => false],
            ['name' => 'الاستبيانات قوالب', 'url' => route('questionnaire.template.index'), 'active' => false],
            ['name' => 'عرض النموذج', 'url' => '#', 'active' => true],
        ]"
    />
@endsection

{{-- ===================== Custom Styles ===================== --}}
@push('styles')
<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }
    .hover-shadow:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .info-item {
        padding: 0.75rem 0;
    }
    .question-item:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.02);
    }
    .options-list {
        max-height: 200px;
        overflow-y: auto;
    }
    .option-item:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
    }
    .card-header {
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    }
    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .question-item .d-flex {
            flex-direction: column;
            gap: 1rem;
        }
        .question-item .flex-shrink-0 {
            align-self: flex-start;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    {{-- ===================== Statistics Cards Section ===================== --}}
    <div class="row g-4 mb-5">
        {{-- Number of Questions Card --}}
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body text-center p-4">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fa fa-list-ul fa-2x text-white"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2 fw-normal">عدد الأسئلة</h6>
                    <h2 class="fw-bold text-dark mb-0">{{ number_format($numQuestions) }}</h2>
                </div>
            </div>
        </div>
        {{-- Usage Count Card --}}
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body text-center p-4">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="fa fa-copy fa-2x text-white"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2 fw-normal">عدد مرات الاستخدام</h6>
                    <h2 class="fw-bold text-dark mb-0">{{ number_format($deployedCount) }}</h2>
                </div>
            </div>
        </div>
        {{-- Question Types Card --}}
        <div class="col-xl-4 col-md-12">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body text-center p-4">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fa fa-tags fa-2x text-white"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-3 fw-normal">أنواع الأسئلة</h6>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @forelse($questionTypeStats as $type => $count)
                            <span class="badge bg-light text-dark border px-3 py-2">
                                {{ __($type) }} 
                                <span class="badge bg-primary ms-1">{{ $count }}</span>
                            </span>
                        @empty
                            <span class="text-muted small">لا توجد أنواع أسئلة</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== Template Information Section ===================== --}}
    <div class="row mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-info-circle ms-2"></i>
                        <h5 class="mb-0 fw-semibold text-white">معلومات النموذج</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        {{-- Template Name --}}
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="form-label text-muted small fw-semibold mb-1">اسم النموذج</label>
                                <p class="mb-0 fw-medium">{{ $template->name ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        {{-- Template Status --}}
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="form-label text-muted small fw-semibold mb-1">الحالة</label>
                                <div>
                                    @if($template->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                            <i class="fa fa-check-circle me-1"></i>
                                            مفعل
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
                                            <i class="fa fa-times-circle me-1"></i>
                                            غير مفعل
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- Template Description --}}
                        <div class="col-12">
                            <div class="info-item">
                                <label class="form-label text-muted small fw-semibold mb-1">الوصف</label>
                                <p class="mb-0 text-muted">{{ $template->description ?? 'لا يوجد وصف متاح' }}</p>
                            </div>
                        </div>
                        {{-- Creation Date --}}
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="form-label text-muted small fw-semibold mb-1">تاريخ الإنشاء</label>
                                <p class="mb-0">
                                    @if($template->created_at)
                                        <i class="fa fa-calendar-alt text-muted me-1"></i>
                                        {{ $template->created_at->locale('ar')->translatedFormat('d F Y') }}
                                        <small class="text-muted">{{ $template->created_at->locale('ar')->translatedFormat('h:i A') }}</small>
                                    @else
                                        <span class="text-muted">غير متاح</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div> {{-- /row --}}
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== Questions Section ===================== --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-question-circle ms-2"></i>
                            <h5 class="mb-0 fw-semibold text-white">الأسئلة في هذا النموذج</h5>
                        </div>
                        <span class="badge bg-light text-primary border px-3 py-2">
                            {{ number_format($numQuestions) }} سؤال
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @forelse($template->templateQuestions ?? [] as $index => $tq)
                        {{-- ===================== Single Question Item ===================== --}}
                        <div class="question-item border-bottom p-4 {{ $loop->last ? 'border-bottom-0' : '' }}">
                            <div class="d-flex align-items-start gap-3">
                                {{-- Question Number --}}
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <span class="fw-bold text-white">{{ $loop->iteration }}</span>
                                    </div>
                                </div>
                                {{-- Question Content --}}
                                <div class="flex-grow-1">
                                    <div class="question-header mb-3">
                                        <h6 class="mb-2 fw-semibold text-dark">
                                            {{ $tq->question->text ?? 'نص السؤال غير متاح' }}
                                        </h6>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                                <i class="fa fa-tag me-1"></i>
                                                {{ __($tq->question->type->name ?? 'نوع غير محدد') }}
                                            </span>
                                            @if($tq->is_required)
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                    <i class="fa fa-asterisk me-1"></i>
                                                    إلزامي
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    {{-- Question Options --}}
                                    @if(($tq->question->type->name ?? '') === 'Likert Scale')
                                        <div class="likert-scale-preview my-4">
                                            <div class="d-flex align-items-center justify-content-between position-relative" style="min-height: 70px;">
                                                @php
                                                    $options = $tq->question->options;
                                                    $count = $options->count();
                                                @endphp
                                                @foreach($options as $option)
                                                    <div class="text-center flex-fill position-relative" style="z-index:2;">
                                                        <div class="rounded-circle border border-2 bg-white mx-auto mb-2 shadow-sm d-flex align-items-center justify-content-center"
                                                             style="width: 44px; height: 44px;">
                                                            <span class="fw-bold text-primary fs-5">{{ $option->value }}</span>
                                                        </div>
                                                        <div class="small text-muted" style="min-width: 60px;">{{ $option->option_text }}</div>
                                                    </div>
                                                    @if(!$loop->last)
                                                        <div class="flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 32px; z-index:1;">
                                                            <div style="height: 4px; background: linear-gradient(90deg, #0d6efd33 0%, #0d6efd99 100%); width: 100%; border-radius: 2px;"></div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                            <div class="d-flex justify-content-between mt-2 px-2">
                                                <span class="small text-secondary">{{ $options->first()->option_text ?? '' }}</span>
                                                <span class="small text-secondary">{{ $options->last()->option_text ?? '' }}</span>
                                            </div>
                                        </div>
                                    @elseif($tq->question->hasOptions() && $tq->question->options->count())
                                        <div class="question-options">
                                            <p class="mb-2 fw-semibold text-muted small">الخيارات المتاحة:</p>
                                            <div class="options-list">
                                                @foreach($tq->question->options as $option)
                                                    <div class="option-item d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
                                                        <div class="option-indicator bg-white rounded-circle border d-flex align-items-center justify-content-center" 
                                                             style="width: 20px; height: 20px;">
                                                            <i class="fa fa-circle text-muted" style="font-size: 8px;"></i>
                                                        </div>
                                                        <span class="flex-grow-1">{{ $option->option_text }}</span>
                                                        <span class="badge bg-white text-muted border small">{{ $option->value }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-5 text-center">
                            <div class="mb-3">
                                <i class="fa fa-question-circle fa-3x text-muted opacity-50"></i>
                            </div>
                            <h6 class="text-muted mb-2">لا توجد أسئلة في هذا النموذج</h6>
                            <p class="text-muted small mb-0">يرجى إضافة أسئلة لهذا النموذج لتتمكن من استخدامه</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
