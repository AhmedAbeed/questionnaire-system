<div class="breadcrumbbar">
    <div class="row align-items-center">
        <div class="col-md-8 col-lg-8">
            <div class="breadcrumb-list text-end">
                <ol class="breadcrumb">
                    @foreach($breadcrumbs as $index => $breadcrumb)
                        <li class="breadcrumb-item {{ $breadcrumb['active'] ? 'active' : '' }}"
                            {{ $breadcrumb['active'] ? 'aria-current="page"' : '' }}>
                            @if($index === 0)
                                <i class="fa fa-home"></i>
                            @endif
                            @if($breadcrumb['active'])
                                {{ $breadcrumb['name'] }}
                            @else
                                <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['name'] }}</a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</div>