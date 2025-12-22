@props([
    'id' => '',
    'title' => '',
    'icon' => '',
    'badge_color' => 'primary',
])

<div class="col-lg-3 col-md-6 col-sm-12">
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center justify-content-between">
                <div class="col-7 text-end mt-2 mb-2">
                    <h5 class="card-title font-14">{{ $title }}</h5>
                    <h4 class="mb-0" id="{{ $id }}-value">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </h4>
                </div>
                <div class="col-5 text-start">
                    <span class="action-icon badge badge-{{ $badge_color }} ms-0 text-white" aria-label="{{ $title }} icon">
                        <i class="feather {{ $icon }}" aria-hidden="true"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col-8">
                    <h6 class="font-13" id="{{ $id }}-updated">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </h6>
                </div>
            </div>
        </div>
    </div>
</div>