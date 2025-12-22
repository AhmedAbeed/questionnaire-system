@if (!empty($activeQs))
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-0 py-2 d-flex align-items-center justify-content-between">
        <h4 class="mb-0 fw-bold fs-5 p-2">
            <i class="fa fa-list-check me-1"></i> {{ __('Active Questionnaires') }}
        </h4>
    </div>
    <div class="card-body p-3">
        <!-- Loading State -->
        <div id="carousel-loading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">جاري تحميل الاستبيانات النشطة...</p>
        </div>

        <!-- Carousel Container -->
        <div id="carousel" class="carousel slide d-none" data-bs-ride="carousel">
            <div class="carousel-inner">
                <!-- Content will be dynamically updated via JavaScript -->
            </div>

            <!-- Carousel Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#carousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon bg-primary rounded-circle p-2"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carousel" data-bs-slide="next">
                <span class="carousel-control-next-icon bg-primary rounded-circle p-2"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</div>
@else
    <div class="alert alert-info text-center" role="alert">
        لا توجد استبيانات نشطة متاحة حاليًا.
    </div>
@endif
