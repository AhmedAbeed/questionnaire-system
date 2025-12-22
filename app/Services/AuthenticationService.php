<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthenticationService
{
    protected $userRepository;
    protected $maxAttempts;
    protected $decayMinutes;
    protected $passwordResetMaxAttempts;
    protected $passwordResetDecayMinutes;

    /**
     * Create a new service instance.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->maxAttempts = config('auth.rate_limiting.login.max_attempts', 5);
        $this->decayMinutes = config('auth.rate_limiting.login.decay_minutes', 5);
        $this->passwordResetMaxAttempts = config('auth.rate_limiting.password_reset.max_attempts', 3);
        $this->passwordResetDecayMinutes = config('auth.rate_limiting.password_reset.decay_minutes', 1);
    }

    /**
     * Generate a throttle key for rate limiting.
     *
     * @param string $email
     * @param Request $request
     * @return string
     */
    protected function throttleKey(string $email, Request $request): string
    {
        return 'password_reset:' . Str::lower($email) . '|' . $request->ip();
    }

    /**
     * Attempt to log in a user with rate limiting.
     *
     * @param Request $request
     * @return array Contains success status, user (if successful), or error message
     */
    public function login(Request $request): array
    {
        try {
            $email = Str::lower($request->input('email'));
            $throttleKey = 'login:' . $email . '|' . $request->ip();

            if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts)) {
                $secondsRemaining = RateLimiter::availableIn($throttleKey);
                $timeRemaining = $this->formatTimeRemaining($secondsRemaining);

                return [
                    'success' => false,
                    'error' => __('Too many login attempts. Please try again in :time.', ['time' => $timeRemaining])
                ];
            }

            if (Auth::attempt(['email' => $email, 'password' => $request->input('password')], $request->filled('remember'))) {
                RateLimiter::clear($throttleKey);
                return [
                    'success' => true,
                    'user' => Auth::user()
                ];
            }

            RateLimiter::hit($throttleKey, $this->decayMinutes * 60);
            return [
                'success' => false,
                'error' => $this->getThrottleError($throttleKey)
            ];
        } catch (\Exception $e) {
            Log::error('Login failed: ' . $e->getMessage(), ['email' => $email, 'ip' => $request->ip()]);
            return [
                'success' => false,
                'error' => __('An unexpected error occurred. Please try again later.')
            ];
        }
    }

    /**
     * Get the localized error message based on the number of login attempts.
     *
     * @param string $throttleKey
     * @return string
     */
    protected function getThrottleError(string $throttleKey): string
    {
        $remaining = RateLimiter::remaining($throttleKey, $this->maxAttempts);

        if ($remaining <= 2 && $remaining > 0) {
            return __("Incorrect email or password. :count attempts remaining before lockout.", ['count' => $remaining]);
        }

        return __('Incorrect email or password');
    }

    /**
     * Format the remaining time for lockout in a human-readable format.
     *
     * @param int $seconds
     * @return string
     */
    protected function formatTimeRemaining(int $seconds): string
    {
        if ($seconds < 60) {
            return __(':count seconds', ['count' => $seconds]);
        }

        $minutes = ceil($seconds / 60);
        return __(':count minutes', ['count' => $minutes]);
    }

    /**
     * Log out the currently authenticated user.
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request): void
    {
        try {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (\Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage(), ['ip' => $request->ip()]);
        }
    }

    /**
     * Send a password reset link to the user with rate limiting.
     *
     * @param string $email
     * @param Request $request
     * @return string
     */
    public function sendPasswordResetLink(string $email, Request $request): string
    {
        try {
            $throttleKey = $this->throttleKey($email, $request);

            if (RateLimiter::tooManyAttempts($throttleKey, $this->passwordResetMaxAttempts)) {
                $secondsRemaining = RateLimiter::availableIn($throttleKey);
                $timeRemaining = $this->formatTimeRemaining($secondsRemaining);
                return __('Too many password reset attempts. Please try again in :time.', ['time' => $timeRemaining]);
            }

            $status = Password::sendResetLink(['email' => $email]);

            if ($status !== Password::RESET_LINK_SENT) {
                RateLimiter::hit($throttleKey, $this->passwordResetDecayMinutes * 60);
            }

            return $status;
        } catch (\Exception $e) {
            Log::error('Password reset link failed: ' . $e->getMessage(), ['email' => $email]);
            return Password::INVALID_USER;
        }
    }

    /**
     * Reset the user's password.
     *
     * @param array $data
     * @return string
     */
    public function resetPassword(array $data): string
    {
        try {
            return Password::reset(
                $data,
                function ($user, $password) {
                    $this->userRepository->updatePassword($user, $password);
                    event(new PasswordReset($user));
                }
            );
        } catch (\Exception $e) {
            Log::error('Password reset failed: ' . $e->getMessage(), ['email' => $data['email']]);
            return Password::INVALID_TOKEN;
        }
    }
}