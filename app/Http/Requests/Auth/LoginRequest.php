<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\AdminUser;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * Checks admin_users first, then users table.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = $this->input('email');
        $password = $this->input('password');
        $remember = $this->boolean('remember');

        // The login form may indicate which side of the system it represents
        // via a `?as=admin` query string (admin login form) or a `login_as` field.
        // When omitted, default to the tenant (User) login.
        $asAdmin = $this->boolean('as_admin')
            || $this->routeIs('admin.login.*')
            || $this->input('as') === 'admin';

        if ($asAdmin) {
            // Admin login — must match an active AdminUser
            $adminUser = AdminUser::where('email', $email)
                ->where('is_active', true)
                ->first();

            if ($adminUser && Hash::check($password, $adminUser->password)) {
                Auth::guard('admin')->login($adminUser, $remember);
                $adminUser->updateLoginInfo();
                RateLimiter::clear($this->throttleKey());
                session(['is_admin_login' => true]);

                return;
            }
        } else {
            // Tenant login — must match an active User
            if (Auth::attempt($this->only('email', 'password') + ['is_active' => true], $remember)) {
                RateLimiter::clear($this->throttleKey());
                session(['is_admin_login' => false]);

                return;
            }
        }

        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
