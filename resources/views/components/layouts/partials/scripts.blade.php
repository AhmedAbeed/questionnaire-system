<!-- Core JS -->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('assets/js/modernizr.min.js') }}"></script>
<script src="{{ asset('assets/js/detect.js') }}"></script>
<script src="{{ asset('assets/js/jquery.slimscroll.js') }}"></script>
<script src="{{ asset('assets/js/vertical-menu.js') }}"></script>
<script src="{{ asset('assets/js/core.js') }}"></script>

<!-- Plugins JS -->
<script src="{{ asset('assets/plugins/switchery/switchery.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/slick/slick.min.js') }}"></script>
<script src="{{ asset('assets/plugins/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/plugins/dataTables/datatables.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<!-- Session Management -->
@if (app()->environment('production'))
    <script src="{{ asset('assets/js/session-timer.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const routes = {
                keepAlive: "{{ route('session.keep-alive') }}",
                remaining: "{{ route('session.remaining') }}",
                extend: "{{ route('session.extend') }}",
                timeout: "{{ route('session.timeout') }}",
                idleTimeout: "{{ route('session.idle-timeout') }}"
            };

            const idleTimeout = 10 * 60 * 1000; // 10 minutes
            const idleWarningThreshold = 60 * 1000; // 1 minute
            const sessionWarningThreshold = 60; // 1 minute

            const sessionManager = new SessionManager(routes, sessionWarningThreshold, idleTimeout, idleWarningThreshold);
        });
    </script>
@endif

<!-- Custom Scripts -->
@stack('scripts')
