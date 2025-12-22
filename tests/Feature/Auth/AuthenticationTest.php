<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Schema;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $startTime;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for SQLite
        $this->artisan('migrate:fresh');
        
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'name' => 'Test User',
            'full_name' => 'Test User Full Name',
            'is_active' => true,
            'forced_password_change' => false
        ]);
    }

    protected function startTimer()
    {
        $this->startTime = microtime(true);
    }

    protected function getExecutionTime()
    {
        return microtime(true) - $this->startTime;
    }

    #[Test]
    public function it_can_show_login_form()
    {
        $this->startTimer();
        
        $response = $this->get(route('login'));
        
        $executionTime = $this->getExecutionTime();
        
        $response->assertStatus(200)
                ->assertViewIs('auth.login');
        
        $this->assertLessThan(1.0, $executionTime, 'Login form load time should be less than 1 second');
    }

    #[Test]
    public function it_can_login_with_valid_credentials()
    {
        $this->startTimer();
        
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        
        $executionTime = $this->getExecutionTime();
        
        $response->assertRedirect();
        $this->assertAuthenticated();
        
        $this->assertLessThan(2.0, $executionTime, 'Login process should complete within 2 seconds');
    }

    #[Test]
    public function it_cannot_login_with_invalid_credentials()
    {
        $this->startTimer();
        
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
        
        $executionTime = $this->getExecutionTime();
        
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        
        $this->assertLessThan(1.0, $executionTime, 'Failed login should complete within 1 second');
    }

    #[Test]
    public function it_can_logout_user()
    {
        $this->actingAs($this->user);
        
        $this->startTimer();
        
        $response = $this->post(route('logout'));
        
        $executionTime = $this->getExecutionTime();
        
        $response->assertRedirect(route('login'));
        $this->assertGuest();
        
        $this->assertLessThan(1.0, $executionTime, 'Logout should complete within 1 second');
    }

    #[Test]
    public function it_can_show_forgot_password_form()
    {
        $this->startTimer();
        
        $response = $this->get(route('password.request'));
        
        $executionTime = $this->getExecutionTime();
        
        $response->assertStatus(200)
                ->assertViewIs('auth.forgot-password');
        
        $this->assertLessThan(1.0, $executionTime, 'Forgot password form load time should be less than 1 second');
    }

    #[Test]
    public function it_can_show_reset_password_form()
    {
        $token = Password::createToken($this->user);
        
        $this->startTimer();
        
        $response = $this->get(route('password.reset', $token));
        
        $executionTime = $this->getExecutionTime();
        
        $response->assertStatus(200)
                ->assertViewIs('auth.reset-password');
        
        $this->assertLessThan(1.0, $executionTime, 'Reset password form load time should be less than 1 second');
    }

    #[Test]
    public function it_can_reset_password()
    {
        $token = Password::createToken($this->user);
        
        $this->startTimer();
        
        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        
        $executionTime = $this->getExecutionTime();
        
        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewPassword123!', $this->user->fresh()->password));
        
        $this->assertLessThan(2.0, $executionTime, 'Password reset should complete within 2 seconds');
    }

    #[Test]
    public function it_enforces_rate_limiting_on_login()
    {
        $this->startTimer();
        
        // Attempt login multiple times with wrong credentials
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login'), [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }
        
        $executionTime = $this->getExecutionTime();
        
        // Verify rate limiting is working
        $this->assertTrue(RateLimiter::tooManyAttempts(
            'login:test@example.com|' . request()->ip(),
            5
        ));
        
        $this->assertLessThan(3.0, $executionTime, 'Rate limiting check should complete within 3 seconds');
    }

    #[Test]
    public function it_enforces_rate_limiting_on_password_reset()
    {
        $this->startTimer();
        
        // Attempt password reset multiple times
        for ($i = 0; $i < 4; $i++) {
            $this->post(route('password.email'), [
                'email' => 'test@example.com',
            ]);
        }
        
        $executionTime = $this->getExecutionTime();
        
        // Verify rate limiting is working
        $this->assertTrue(RateLimiter::tooManyAttempts(
            Str::lower('test@example.com') . '|' . request()->ip(),
            3
        ));
        
        $this->assertLessThan(3.0, $executionTime, 'Rate limiting check should complete within 3 seconds');
    }
} 