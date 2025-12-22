@extends('layouts.auth')

@section('title', 'إعادة تعيين كلمة المرور')

@section('page_title', 'إعادة تعيين كلمة المرور')
@section('page_description', 'أدخل كلمة المرور الجديدة')

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
    
    <form method="POST" action="{{ route('password.update') }}" id="resetPasswordForm">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        
        <!-- Email Input (Hidden) -->
        <input type="hidden" name="email" value="{{ $email ?? old('email') }}">
        
        <!-- New Password Input -->
        <div class="form-floating position-relative">
            <input type="text" 
                   class="form-control @error('password') is-invalid @enderror" 
                   id="password" 
                   name="password"
                   placeholder="••••••"
                   required
                   autofocus>
            <label for="password">كلمة المرور الجديدة</label>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text" id="passwordRequirements">
                <div class="requirement" data-requirement="length">
                    <i class="fas fa-times text-danger"></i> 8 أحرف على الأقل
                </div>
                <div class="requirement" data-requirement="uppercase">
                    <i class="fas fa-times text-danger"></i> حرف كبير واحد على الأقل
                </div>
                <div class="requirement" data-requirement="lowercase">
                    <i class="fas fa-times text-danger"></i> حرف صغير واحد على الأقل
                </div>
                <div class="requirement" data-requirement="number">
                    <i class="fas fa-times text-danger"></i> رقم واحد على الأقل
                </div>
                <div class="requirement" data-requirement="special">
                    <i class="fas fa-times text-danger"></i> رمز خاص واحد على الأقل (@$!%*#?&)
                </div>
            </div>
        </div>
        
        <!-- Confirm Password Input -->
        <div class="form-floating position-relative">
            <input type="text" 
                   class="form-control" 
                   id="password_confirmation" 
                   name="password_confirmation"
                   placeholder="••••••"
                   required>
            <label for="password_confirmation">تأكيد كلمة المرور</label>
            <div class="form-text" id="passwordMatch">
                <i class="fas fa-times text-danger"></i>
                <span id="passwordMatchText">كلمات المرور غير متطابقة</span>
            </div>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" id="submitBtn" class="btn btn-primary w-100 mb-3" disabled>
            إعادة تعيين كلمة المرور
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
        const password = $('#password');
        const confirmPassword = $('#password_confirmation');
        const submitBtn = $('#submitBtn');
        const passwordMatch = $('#passwordMatch');
        const requirements = {
            length: /.{8,}/,
            uppercase: /[A-Z]/,
            lowercase: /[a-z]/,
            number: /[0-9]/,
            special: /[@$!%*#?&]/
        };

        function validatePassword() {
            let isValid = true;
            const value = password.val();
            const confirmValue = confirmPassword.val();

            // Check each requirement
            Object.keys(requirements).forEach(req => {
                const requirement = $(`.requirement[data-requirement="${req}"]`);
                const icon = requirement.find('i');
                
                if (requirements[req].test(value)) {
                    requirement.removeClass('text-danger').addClass('text-success');
                    icon.removeClass('fa-times text-danger').addClass('fa-check text-success');
                } else {
                    requirement.removeClass('text-success').addClass('text-danger');
                    icon.removeClass('fa-check text-success').addClass('fa-times text-danger');
                    isValid = false;
                }
            });

            // Check password match
            const match = value === confirmValue && value !== '';
            const matchIcon = $('#passwordMatch i');
            const matchText = $('#passwordMatchText');
            
            if (match) {
                matchIcon.removeClass('fa-times text-danger').addClass('fa-check text-success');
                matchText.text('كلمات المرور متطابقة');
            } else {
                matchIcon.removeClass('fa-check text-success').addClass('fa-times text-danger');
                matchText.text('كلمات المرور غير متطابقة');
            }
            
            // Enable/disable submit button
            submitBtn.prop('disabled', !(isValid && match));
        }

        // Add event listeners
        password.on('input', validatePassword);
        confirmPassword.on('input', validatePassword);

        // Add loading state to form submission
        $('#resetPasswordForm').on('submit', function() {
            submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الحفظ...').prop('disabled', true);
        });
    });
    
    // Add smooth animations
    $(window).on('load', function() {
        $('.auth-container').hide().fadeIn(800);
    });
</script>

<style>
.requirement {
    margin-bottom: 0.25rem;
    transition: color 0.3s ease;
}
.requirement i {
    margin-right: 0.5rem;
}
#passwordMatch {
    margin-top: 0.5rem;
    transition: all 0.3s ease;
}
</style>
@endsection 