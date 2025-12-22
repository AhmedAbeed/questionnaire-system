<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    protected $authService;

    /**
     * Create a new controller instance.
     *
     * @param AuthenticationService $authService
     */
    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Show the form for requesting a password reset link.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given email.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => __('The email field is required.'),
            'email.email' => __('The email must be a valid email address.'),
        ]);

        $status = $this->authService->sendPasswordResetLink($request->input('email'), $request);

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Show the password reset form.
     *
     * @param string $token
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showResetForm(string $token, Request $request)
    {
        $email = $request->input('email');

        if (!$email) {
            return redirect()->route('password.request')->withErrors(['email' => __('Email is required.')]);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Handle a password reset request.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
        ], [
            'email.required' => __('The email field is required.'),
            'email.email' => __('The email must be a valid email address.'),
            'password.required' => __('The password field is required.'),
            'password.min' => __('The password must be at least 8 characters.'),
            'password.confirmed' => __('The password confirmation does not match.'),
            'password.regex' => __('The password must contain at least one lowercase letter, one uppercase letter, one number, and one special character.'),
        ]);

        $status = $this->authService->resetPassword(
            $request->only('email', 'password', 'password_confirmation', 'token')
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)])->withInput();
    }
}