@extends('layouts.auth')

@section('title', 'تسجيل الدخول')

@section('page_title', 'تسجيل الدخول')
@section('page_description', 'يسعدنا رؤيتك مجددًا – أدخل بياناتك')

@section('content')
    @if(session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <form method="POST" action="{{ route('login') }}" id="loginForm">
        @csrf
        
        <!-- Email Input -->
        <div class="form-floating">
            <input type="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   id="email" 
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="name@nmu.edu.eg"
                   required
                   autofocus>
            <label for="email">البريد الإلكتروني</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        
        <!-- Password Input -->
        <div class="form-floating position-relative">
            <input type="password" 
                   class="form-control @error('password') is-invalid @enderror" 
                   id="password" 
                   name="password"
                   placeholder="••••••"
                   required>
            <label for="password">كلمة المرور</label>
            <button type="button" class="password-toggle" onclick="togglePassword()">
                <i class="fas fa-eye" id="toggleIcon"></i>
            </button>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        
        <!-- Remember Me & Forgot Password -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input custom-checkbox" 
                       type="checkbox" 
                       id="remember" 
                       name="remember"
                       {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">
                    تذكرني
                </label>
            </div>
            <a href="{{ route('password.request') }}" class="forgot-password">
                نسيت كلمة المرور؟
            </a>
        </div>
        
        <!-- Login Button -->
        <button type="submit" id="loginBtn" class="btn btn-primary w-100 mb-3">
            تسجيل الدخول
        </button>
    </form>
@endsection

@section('additional_js')
<script>
    $(document).ready(function() {
        // Add loading state to form submission
        $('#loginForm').on('submit', function() {
            const $btn = $('#loginBtn');
            $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري التحميل...').prop('disabled', true);
        });
    });
    
    // Toggle password visibility
    function togglePassword() {
        const passwordField = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.className = 'fas fa-eye-slash';
        } else {
            passwordField.type = 'password';
            toggleIcon.className = 'fas fa-eye';
        }
    }
    
    // Add smooth animations
    $(window).on('load', function() {
        $('.auth-container').hide().fadeIn(800);
    });
</script>
@endsection
