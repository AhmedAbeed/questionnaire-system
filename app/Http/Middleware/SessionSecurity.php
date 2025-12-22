<?php

namespace App\Http\Middleware;

use App\Http\Controllers\SessionController;
use Closure;
use Illuminate\Http\Request;

class SessionSecurity
{
    /**
     * @var SessionController
     */
    protected $sessionController;

    /**
     * Create a new middleware instance.
     *
     * @param SessionController $sessionController
     * @return void
     */
    public function __construct(SessionController $sessionController)
    {
        $this->sessionController = $sessionController;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip validation for public routes
        if ($request->routeIs('login') || $request->routeIs('logout') || $request->routeIs('session.timeout')) {
            return $next($request);
        }

        // Validate session security
        if (!$this->sessionController->validateSession($request)) {
            // Redirect to timeout page
            return redirect()->route('session.timeout');
        }

        return $next($request);
    }
}