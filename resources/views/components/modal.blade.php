@props([
    'id',
    'title',
    'size' => '',
    'closeButton' => true
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog {{ $size }}">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between">
                <div class="div">
                    <h5 class="modal-title" id="{{ $id }}Label">{{ $title }}</h5>
                </div>
                @if($closeButton)
                <div class="div">
                    <button type="button" class="btn-close btn-close-white" aria-label="Close" data-bs-dismiss="modal"></button>
                </div>
                @endif
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            @if(isset($footer))
            <div class="modal-footer">
                {{ $footer }}
            </div>
            @endif
        </div>
    </div>
</div> 