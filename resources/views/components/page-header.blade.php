<div class="row justify-content-between align-items-center" dir="rtl">
    <div class="col-12 col-md-6 text-end">
        <h3 class="page-title">{{ $title }}</h3>
        @if($pageDescription)
            <p class="text-muted">{{ $pageDescription }}</p>
        @endif
    </div>

    <div class="col-12 col-md-6 text-start">
        @foreach($actionItems as $item)
            @if($item['type'] === 'button')
                <button 
                    class="{{ $item['class'] }} me-2"
                    @if(!empty($item['modal']))
                        data-bs-toggle="modal" 
                        data-bs-target="#{{ $item['modal']['target'] ?? 'defaultModal' }}"
                    @elseif(!empty($item['url']))
                        onclick="window.location.href='{{ $item['url'] }}'"
                    @endif
                    @if(!empty($item['id']))
                        id="{{ $item['id'] }}"
                    @endif
                >
                <span>{{ $item['label'] }}</span>

                    @if(!empty($item['icon']))
                        <i class="{{ $item['icon'] }} me-1"></i>
                    @endif
                </button>
            @elseif($item['type'] === 'dropdown')
                <div class="dropdown me-2 d-inline-block">
                    <button 
                        class="{{ $item['class'] ?? 'btn btn-outline-secondary' }} dropdown-toggle" 
                        type="button" 
                        data-bs-toggle="dropdown"
                    >
                    {{ $item['label'] }}

                        @if(!empty($item['icon']))
                            <i class="{{ $item['icon'] }} me-1"></i>
                        @endif
                    </button>
                    <ul class="dropdown-menu">
                        @foreach($item['items'] as $dropdownItem)
                            <li>
                                <a class="dropdown-item" 
                                    @if(!empty($dropdownItem['modal']))
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#{{ $dropdownItem['modal']['target'] }}"
                                    @elseif(!empty($dropdownItem['url']))
                                        href="{{ $dropdownItem['url'] }}"
                                    @endif
                                >
                                    @if(!empty($dropdownItem['icon']))
                                        <i class="{{ $dropdownItem['icon'] }} me-1"></i>
                                    @endif
                                    {{ $dropdownItem['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>
</div>