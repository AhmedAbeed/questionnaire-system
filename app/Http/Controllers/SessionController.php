<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    /**
     * Get the remaining session time
     */
    public function remaining(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['expired' => true]);
            }
        
        // Get absolute expiration time
        $expiresAt = $request->session()->get('auth.expires_at');
        
        if (!$expiresAt) {
            // If not set, create it now using Carbon for better date handling
            $lifetime = config('session.lifetime');
            $expiresAt = Carbon::now()->addMinutes($lifetime)->timestamp;
            $request->session()->put('auth.expires_at', $expiresAt);
        }
        
        // Check both absolute expiration and inactivity timeout
        $lastActivity = $request->session()->get('auth.last_activity', Carbon::now()->timestamp);
        $inactivityTimeout = config('session.inactivity_timeout', 15) * 60; // Default 15 minutes in seconds
        $inactivityExpiresAt = $lastActivity + $inactivityTimeout;
        
        $absoluteRemainingSeconds = $expiresAt - Carbon::now()->timestamp;
        $inactivityRemainingSeconds = $inactivityExpiresAt - Carbon::now()->timestamp;
        
        // Use the smaller of the two times
        $remainingSeconds = min($absoluteRemainingSeconds, $inactivityRemainingSeconds);
        
        return response()->json([
            'success' => true,
            'expired' => $remainingSeconds <= 0,
            'remaining_seconds' => max(0, $remainingSeconds),
            'inactivity_timeout' => $inactivityRemainingSeconds > 0,
            'absolute_timeout' => $absoluteRemainingSeconds > 0
        ]);
        } catch (Exception $e) {
            logError('Failed to get remaining session time', 'SessionController', $e);
            throw new Exception('Failed to get remaining session time');
        }
    }
    
    /**
     * Keep the session alive
     */
    public function extend(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['status' => 'error', 'message' => 'Not authenticated'], 401);
            }
        
        // Generate CSRF token if needed for extra security
        $token = $request->session()->token();
        
        // Update the last activity timestamp
        $now = Carbon::now()->timestamp;
        $request->session()->put('auth.last_activity', $now);
        
        // Check if we need to extend the absolute expiration
        $expiresAt = $request->session()->get('auth.expires_at');
        $lifetime = config('session.lifetime') * 60; // Convert minutes to seconds        
        
        $newExpiresAt = $now + $lifetime;
        $request->session()->put('auth.expires_at', $newExpiresAt);
  
        
        // Calculate remaining time
        $inactivityTimeout = config('session.inactivity_timeout', 15) * 60;
        $inactivityExpiresAt = $now + $inactivityTimeout;
        
        return response()->json([
            'success' => true,
            'message' => 'Session extended',
            'expires_at' => $newExpiresAt,
            'inactivity_expires_at' => $inactivityExpiresAt,
            'absolute_remaining' => $newExpiresAt - $now,
            'inactivity_remaining' => $inactivityTimeout,
            'csrf_token' => $token
        ]);
        } catch (Exception $e) {
            logError('Failed to extend session', 'SessionController', $e);
            throw new Exception('Failed to extend session');
        }
    }
    
    /**
     * Handle session timeout
     */
    public function timeout(Request $request)
    {
        try {
            // Log the timeout for security auditing
            logger()->info('Session timeout for user', [
                'user_id' => Auth::id() ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
        ]);
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
        } catch (Exception $e) {
            logError('Failed to handle session timeout', 'SessionController', $e);
            throw new Exception('Failed to handle session timeout');
        }
    }

    public function idleTimout(Request $request){
        try {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
        return redirect()->route('login')->with('error', 'Your session has expired due to inactivity. Please log in again.');
        } catch (Exception $e) {
            logError('Failed to handle idle timeout', 'SessionController', $e);
            throw new Exception('Failed to handle idle timeout');
        }
    }
    
    /**
     * Initialize session security settings after login
     * Call this from your LoginController after successful authentication
     */
    public function initializeSession(Request $request)
    {
        try {
            $now = Carbon::now()->timestamp;
            $lifetime = config('session.lifetime') * 60; // Convert minutes to seconds
            
        // Store authentication data in a namespace to keep it organized
        $request->session()->put('auth.expires_at', $now + $lifetime);
        $request->session()->put('auth.last_activity', $now);
        $request->session()->put('auth.ip_address', $request->ip());
        $request->session()->put('auth.user_agent', $request->userAgent());
        $request->session()->put('auth.session_id', Str::random(40));
        
        // Regenerate session ID to prevent session fixation
        $request->session()->regenerate();
        } catch (Exception $e) {
            logError('Failed to initialize session', 'SessionController', $e);
            throw new Exception('Failed to initialize session');
        }
    }
    
    /**
     * Middleware method to check session security
     * Register this in a middleware and apply to protected routes
     */
    public function validateSession(Request $request)
    {
        try {
            if (!Auth::check()) {
                return false;
            }
            
        // Check for session hijacking by comparing IP and user agent
        $ipAddress = $request->session()->get('auth.ip_address');
        $userAgent = $request->session()->get('auth.user_agent');
        
        $ipValid = $ipAddress === $request->ip();
        $userAgentValid = $userAgent === $request->userAgent();
        
        // If critical security parameters have changed, invalidate the session
        if (!$ipValid || !$userAgentValid) {
            logger()->warning('Possible session hijacking attempt', [
                'user_id' => Auth::id(),
                'original_ip' => $ipAddress,
                'current_ip' => $request->ip(),
                'original_user_agent' => $userAgent,
                'current_user_agent' => $request->userAgent()
            ]);
            
            return false;
        }
        
        // Check for timeouts
        $now = Carbon::now()->timestamp;
        $expiresAt = $request->session()->get('auth.expires_at', 0);
        $lastActivity = $request->session()->get('auth.last_activity', 0);
        $inactivityTimeout = config('session.inactivity_timeout', 15) * 60;
        
        // Check both absolute timeout and inactivity timeout
        if ($now > $expiresAt || $now > ($lastActivity + $inactivityTimeout)) {
            return false;
        }
        
        // Update last activity timestamp
        $request->session()->put('auth.last_activity', $now);
        
        return true;
        } catch (Exception $e) {
            logError('Failed to validate session', 'SessionController', $e);
            throw new Exception('Failed to validate session');
        }
    }

    /**
     * Refresh the CSRF token
     */
    public function refreshCsrf(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

        // Regenerate the CSRF token
        $request->session()->regenerateToken();
        $token = $request->session()->token();

        return response()->json([
            'success' => true,
            'token' => $token
        ]);
        } catch (Exception $e) {
            logError('Failed to refresh CSRF token', 'SessionController', $e);
            throw new Exception('Failed to refresh CSRF token');
        }
    }
}