<!-- Topbar -->
<div class="topbar">
    <!-- Start row -->
    <div class="row align-items-center">
        <!-- Start col -->
        <div class="col-md-12 align-self-center">
            <div class="togglebar">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item">
                        <div class="menubar">
                            <a class="menu-hamburger" href="javascript:void(0);">
                                <img src="{{ asset('assets/images/svg-icon/collapse.svg') }}" class="img-fluid menu-hamburger-collapse" alt="collapse">
                                <img src="{{ asset('assets/images/svg-icon/close.svg') }}" class="img-fluid menu-hamburger-close" alt="close">
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="infobar">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item">
                        <div class="notifybar">
                            <div class="dropdown">
                                <a class="dropdown-toggle infobar-icon" href="#" role="button" id="notificationlink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <img src="{{ asset('assets/images/svg-icon/notifications.svg') }}" class="img-fluid" alt="notifications">
                                </a>
                                <div class="dropdown-menu" aria-labelledby="notificationlink">
                                    <div class="notification-dropdown-title">
                                        <h4>{{ __('Notifications') }}</h4>
                                    </div>
                                    <ul class="list-unstyled">
                                        <li class="d-flex p-2 mt-1 dropdown-item">
                                            <div class="media-body">
                                                <h5 class="action-title">{{__('No new notifications')}}</h5>
                                                <p><span class="timing">{{__('You are all caught up!')}}</span></p>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="list-inline-item">
                        <div class="profilebar">
                            <div class="dropdown">
                                <a class="dropdown-toggle" href="#" role="button" id="profilelink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <img src="{{ asset('assets/images/users/profile.svg') }}" class="img-fluid" alt="profile">
                                    <span class="feather icon-chevron-down live-icon"></span>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="profilelink">
                                    <div class="dropdown-item">
                                        <div class="profilename">
                                            <h5>{{ auth()->user()->name }}</h5>
                                        </div>
                                    </div>
                                    <div class="userbox">
                                        <ul class="list-unstyled mb-0">
                                            <li class="d-flex p-2 mt-1 dropdown-item">
                                                <a href="javascript:void(0);" id="logout" class="profile-icon">
                                                    <img src="{{ asset('assets/images/svg-icon/logout.svg') }}" class="img-fluid" alt="logout">
                                                    {{__("Logout")}}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            
        </div>
        <!-- End col -->
    </div>
    <!-- End row -->
</div>
<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#logout').on('click', function(e) {
            e.preventDefault();
            $('#logout-form').submit();
        });
    });
</script>
@endpush