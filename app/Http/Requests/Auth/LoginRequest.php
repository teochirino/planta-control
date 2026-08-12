<?php

namespace App\Http\Requests\Auth;

use App\Models\ItalianetUser;
use App\Models\User;
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
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! $this->attemptCredentials()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Verificar credenciales: el perfil Administrador (id_profile=7) sigue usando la
     * contraseña local de la tabla "users". El resto de perfiles se valida en vivo
     * contra la contraseña real del usuario en italianet_users.users (fuente de la
     * verdad corporativa) — un usuario sin vínculo a italianet no puede iniciar sesión.
     */
    protected function attemptCredentials(): bool
    {
        $user = User::where('email', $this->input('email'))->first();

        if (! $user) {
            return false;
        }

        if ($user->id_profile === 7) {
            return Auth::attempt($this->only('email', 'password'), $this->boolean('remember'));
        }

        if (! $user->user_main_id) {
            return false;
        }

        $italianetUser = ItalianetUser::find($user->user_main_id);

        if (! $italianetUser || (int) $italianetUser->status !== 1 || ! $italianetUser->password) {
            return false;
        }

        if (! Hash::check($this->input('password'), $italianetUser->password)) {
            return false;
        }

        Auth::login($user, $this->boolean('remember'));

        return true;
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
