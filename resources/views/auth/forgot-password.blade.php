@extends('layouts.auth')

@section('title', 'نسيت كلمة المرور')

@section('page_title', 'نسيت كلمة المرور')
@section('page_description', 'أدخل بريدك الإلكتروني لإعادة تعيين كلمة المرور')

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
    
    <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm">
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
        
        <!-- Submit Button -->
        <button type="submit" id="submitBtn" class="btn btn-primary w-100 mb-3">
            إرسال رابط إعادة التعيين
        </button>

        <!-- Back to Login -->
        <div class="text-center">
            <a href="{{ route('login') }}" class="text-muted">
                <i class="fas fa-arrow-right me-1"></i>
                العودة لتسجيل الدخول
            </a>
        </div>
    </form>
@endsection

@section('additional_js')
<script>
    $(document).ready(function() {
        // Add loading state to form submission
        $('#forgotPasswordForm').on('submit', function() {
            const $btn = $('#submitBtn');
            $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الإرسال...').prop('disabled', true);
        });
    });
    
    // Add smooth animations
    $(window).on('load', function() {
        $('.auth-container').hide().fadeIn(800);
    });
</script>
@endsection 