<div class="sidebar">
    <!-- Start Logobar -->
    <div class="logobar">
        <a href="javascript:void(0);" class="logo logo-large">
            <img src="{{ asset('assets/images/logo/logo-wide.png') }}" class="img-fluid" alt="logo">
        </a>
        <a href="javascript:void(0);" class="logo logo-small">
            <img src="{{ asset('assets/images/logo/uni-logo.png') }}" class="img-fluid" alt="logo">
        </a>
    </div>
    <!-- End Logobar -->
    
    <!-- Start Navigationbar -->
    <div class="navigationbar">
        <ul class="vertical-menu">
            @foreach($menuItems as $item)
                <li @if(isset($item['active']) && $item['active']) class="active" @endif>
                    @if(isset($item['link']))
                        {{-- Top level item with direct link --}}
                        <a href="{{ $item['link'] }}">
                            <img src="{{ asset('assets/images/svg-icon/'.$item['icon'].'.svg') }}" 
                                 class="img-fluid" alt="{{ $item['icon'] }}">
                            <span>{{ $item['title'] }}</span>
                            @if(isset($item['badge']))
                                <span class="badge {{ $item['badge']['class'] }} pull-left">
                                    {{ $item['badge']['text'] }}
                                </span>
                            @endif
                        </a>
                    @else
                        {{-- Top level item without direct link (has submenu) --}}
                        <a href="javascript:void(0);">
                            <img src="{{ asset('assets/images/svg-icon/'.$item['icon'].'.svg') }}" 
                                 class="img-fluid" alt="{{ $item['icon'] }}">
                            <span>{{ $item['title'] }}</span>
                            @if(isset($item['badge']))
                                <span class="badge {{ $item['badge']['class'] }} pull-left">
                                    {{ $item['badge']['text'] }}
                                </span>
                            @endif
                            @if(isset($item['submenu']))
                                <i class="feather icon-chevron-right"></i>
                            @endif
                        </a>
                    @endif
                    
                    {{-- Submenu items --}}
                    @if(isset($item['submenu']))
                        <ul class="vertical-submenu">
                            @foreach($item['submenu'] as $submenu)
                                <li @if(isset($submenu['active']) && $submenu['active']) class="active" @endif>
                                    @if(isset($submenu['link']))
                                        <a href="{{ $submenu['link'] }}" 
                                           @if(isset($submenu['active']) && $submenu['active']) class="active" @endif>
                                            {{ $submenu['title'] }}
                                        </a>
                                    @else
                                        <a href="javascript:void(0);">
                                            {{ $submenu['title'] }}
                                            @if(isset($submenu['submenu']))
                                                <i class="feather icon-chevron-right"></i>
                                            @endif
                                        </a>
                                    @endif
                                    
                                    {{-- Sub-submenu items (third level) --}}
                                    @if(isset($submenu['submenu']))
                                        <ul class="vertical-submenu">
                                            @foreach($submenu['submenu'] as $subSubmenu)
                                                <li @if(isset($subSubmenu['active']) && $subSubmenu['active']) class="active" @endif>
                                                    <a href="{{ $subSubmenu['link'] }}" 
                                                       @if(isset($subSubmenu['active']) && $subSubmenu['active']) class="active" @endif>
                                                        {{ $subSubmenu['title'] }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
    <!-- End Navigationbar -->
</div>