<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - بوابة الطلاب الجامعية</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet" type="text/css">    
    <link href="{{ asset('assets/css/auth-style.css') }}" rel="stylesheet" type="text/css">    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" type="text/css">
    @yield('additional_css')
</head>
<body>
    <div class="main-container">
        <div class="auth-container">
            <!-- Form Section -->
            <div class="form-section">
                <!-- Logo -->
                <div class="logo-section">
                    <img src="{{ asset('assets/images/logo/uni-logo.png') }}" 
                         alt="شعار جامعة المنصورة الجديدة" 
                         class="logo">
                </div>

                <!-- Title -->
                <div class="title-section">
                    <h1>@yield('page_title')</h1>
                    <p class="small text-muted mt-2">@yield('page_description')</p>
                </div>
                
                @yield('content')
            </div>

            <!-- Promotional Section -->
            <div class="promotional-section">
                <!-- Support Button -->
                <button class="support-btn">
                    <i class="fas fa-headphones me-2"></i>
                    الدعم الفني
                </button>

                <div class="promo-content">
                    <!-- Hero Image -->
                    <div class="hero-image-container">
                        <img src="{{ asset('assets/images/authentication/auth-hero.svg') }}" 
                             alt="Authentication Hero" 
                             class="hero-image">
                    </div>

                    <!-- Questionnaire Title -->
                    <div class="questionnaire-title">
                        <h2>نظام الاستبيانات</h2>
                        <p>رأيك هو الأساس في بناء جامعة أكثر تميزًا... سجّل الدخول وابدأ بالتغيير.</p>
                    </div>

                    <!-- Copyright Notice -->
                    <div class="copyright-text">
                        <p>© {{ date('Y') }} جامعة المنصورة الجديدة. جميع الحقوق محفوظة</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/modernizr.min.js') }}"></script>
    <script src="{{ asset('assets/js/detect.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.slimscroll.js') }}"></script>
    @yield('additional_js')
</body>
</html>
