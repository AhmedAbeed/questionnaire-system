

<div class="card border-0 shadow-sm rounded-3 h-100">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h5 class="fw-bold m-0"><i class="bi bi-database-fill me-2"></i>بنك الأسئلة</h5>
            <a href="{{ route('questions.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-gear-fill me-1"></i> إدارة
            </a>
        </div>
        <div class="text-center mb-3 pb-2 border-bottom border-primary">
            <p class="text-muted small mb-1"><i class="bi bi-question-circle me-1"></i>إجمال الأسئلة</p>
            <h2 class="display-6 fw-bold text-primary mb-0">{{ $totalQuestions }}</h2>
        </div>
        <div class="mb-3 pb-2 border-bottom border-primary">
            <p class="text-muted small mb-1"><i class="bi bi-question-circle me-1"></i>أنواع الأسئلة</p>
            @if (!empty($questionTypes))
            @foreach ($questionTypes as $type)
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small"><i class="{{ $type['icon'] }} me-1"></i>{{ __($type['name']) }}</span>
                        <span class="fw-bold text-secondary">{{ $type['count'] }}</span>
                    </div>
                </div>
            @endforeach
            @endif
        </div>
        <div>
            <p class="text-muted small mb-2"><i class="bi bi-tag-fill me-1"></i>الفئات الأكثر استخداماً</p>
            @if (!empty($categories))
            @foreach ($categories as $category)
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small"><i class="{{ $category['icon'] }} me-1"></i>{{ __($category['name']) }}</span>
                        <span class="badge bg-light text-dark">{{ $category['percentage'] }}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $category['color'] }}" role="progressbar" style="width: {{ $category['percentage'] }}%;" aria-valuenow="{{ $category['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            @endforeach
            @endif
        </div>
        <div class="text-center mt-3 pt-1">
            <a href="{{ route('questions.create') }}" class="btn btn-primary w-100">
                <i class="bi bi-plus-circle me-1"></i> إضافة سؤال جديد
            </a>
        </div>
    </div>
</div>
