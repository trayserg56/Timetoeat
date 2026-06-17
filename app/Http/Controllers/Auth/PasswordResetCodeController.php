<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordCodeRequest;
use App\Mail\Auth\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetCodeController extends Controller
{
    public function store(ForgotPasswordCodeRequest $request): RedirectResponse
    {
        $email = $request->string('email')->toString();
        $rateLimitKey = sprintf('password-reset-code:%s|%s', $email, $request->ip());
        $throttleSeconds = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.throttle', 60);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->withErrors([
                'email' => "Код уже отправлялся недавно. Попробуйте снова через {$seconds} сек.",
            ], 'forgotPassword');
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresInMinutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            DB::table(config('auth.passwords.'.config('auth.defaults.passwords').'.table', 'password_reset_tokens'))
                ->updateOrInsert(
                    ['email' => $email],
                    [
                        'token' => Hash::make($code),
                        'created_at' => now(),
                    ],
                );

            Mail::to($user->email)->send(new PasswordResetCodeMail($code, $expiresInMinutes));
        }

        RateLimiter::hit($rateLimitKey, $throttleSeconds);

        return back()->with('success', 'Если аккаунт с таким email существует, код уже отправлен.');
    }
}
