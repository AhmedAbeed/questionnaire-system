@extends('layouts.app')

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="الرئيسية"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('respondent.home'), 'active' => true],
            ['name' => 'الاستبيانات المتاحة', 'url' => route('respondent.home'), 'active' => true],
        ]"
    />
@endsection

@section('content')
    <x-page-header
        title="الاستبيانات المتاحة"
        page-description="اكتشف وشارك في الاستبيانات المتاحة لك"
        :action-items="[]"
    />

    <div class="row g-3">
        @if ($questionnaires->isEmpty())
            <div class="col-12">
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="fa fa-info-circle me-2 fs-4"></i>
                    <div>
                        لا توجد استبيانات متاحة لك حاليًا.
                    </div>
                </div>
            </div>
        @else
            @foreach ($questionnaires as $questionnaire)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="icon-wrapper">
                                        <i class="fa fa-clipboard text-primary"></i>
                                    </div>
                                    <span class="badge bg-primary rounded-pill px-2 py-1 small">
                                        {{ $questionnaire->deployedQuestions()->count() }} سؤال
                                    </span>
                                </div>
                                <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
                                    ~{{ $questionnaire->estimated_time ?? '20' }} د
                                </span>
                            </div>

                            <h3
                                class="card-title h6 mb-2 fw-bold text-truncate"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="{{ $questionnaire->name ?? 'استبيان بدون عنوان' }}"
                            >
                                {{ Str::limit($questionnaire->name ?? 'استبيان بدون عنوان', 25) }}
                            </h3>

                            @if($questionnaire->description)
                                <p
                                    class="card-text text-muted small mb-3 text-truncate"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="{{ $questionnaire->description ?? 'لا يوجد وصف متاح' }}"
                                >
                                    {{ Str::limit($questionnaire->description ?? 'لا يوجد وصف متاح', 30) }}
                                </p>
                            @endif

                            @if($questionnaire->targets->isNotEmpty())
                                <div class="targets-wrapper mb-3">
                                    @foreach($questionnaire->targets as $target)
                                        <div class="d-flex align-items-center gap-1 text-muted small mb-1">
                                            @if($target->faculty)
                                                <i class="fa fa-university text-primary"></i>
                                                <span>{{ $target->faculty->name }}</span>
                                            @elseif($target->program)
                                                <i class="fa fa-graduation-cap text-primary"></i>
                                                <span>{{ $target->program->name }}</span>
                                            @elseif($target->semesterCourse?->course)
                                                <i class="fa fa-book text-primary"></i>
                                                <span>{{ $target->semesterCourse->course->name }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="p-1">
                                            <i class="fa fa-calendar text-primary small"></i>
                                        </div>
                                        <span class="text-muted small">
                                            {{ $questionnaire->close_date ? $questionnaire->close_date->locale('ar')->format('Y/m/d h:i A') : 'غير محدد' }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="p-1">
                                            <i class="fa fa-clock-o text-info small"></i>
                                        </div>
                                        <span class="badge bg-info text-white small">
                                            {{ $questionnaire->close_date ? $questionnaire->close_date->locale('ar')->diffForHumans() : 'غير محدد' }}
                                        </span>
                                    </div>
                                </div>
                                <a
                                    href="{{ route('questionnaires.deployed.show', $questionnaire->id ?? 0) }}"
                                    class="btn btn-primary btn-sm rounded-pill px-3 py-1 d-flex align-items-center gap-1"
                                >
                                    <span>ابدأ</span>
                                    <i class="fa fa-arrow-left small"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection

@push('styles')
<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }
    .hover-shadow:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .transition-all {
        transition: all 0.3s ease;
    }
    .icon-wrapper {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        border-radius: 8px;
    }
    .icon-wrapper i {
        font-size: 1rem;
    }
    .targets-wrapper {
        max-height: 80px;
        overflow-y: auto;
        scrollbar-width: thin;
    }
    .targets-wrapper::-webkit-scrollbar {
        width: 4px;
    }
    .targets-wrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    .targets-wrapper::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    .targets-wrapper::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush