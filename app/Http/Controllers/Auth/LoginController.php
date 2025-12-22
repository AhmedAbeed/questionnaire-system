<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;

class LoginController extends Controller
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
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        $result = $this->authService->login($request);

        if ($result['success']) {
            $request->session()->regenerate();
            return redirect()->intended(route('home'));
        }

        return redirect()
            ->back()
            ->withErrors(['email' => $result['error']])
            ->withInput();
    }

    /**
     * Validate the login request with localized messages.
     *
     * @param Request $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => __('The email field is required.'),
            'email.email' => __('The email must be a valid email address.'),
            'password.required' => __('The password field is required.'),
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request);
        return redirect()->route('login');
    }
}