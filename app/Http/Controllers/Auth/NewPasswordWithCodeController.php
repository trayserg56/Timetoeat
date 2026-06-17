<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordWithCodeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class NewPasswordWithCodeController extends Controller
{
    public function store(ResetPasswordWithCodeRequest $request): RedirectResponse
    {
        $email = $request->string('email')->toString();
        $code = $request->string('code')->toString();
        $table = config('auth.passwords.'.config('auth.defaults.passwords').'.table', 'password_reset_tokens');
        $expiresInMinutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $rateLimitKey = 'password-reset-verify:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return back()->withErrors([
                'code' => 'Слишком много попыток. Попробуйте позже или запросите новый код.',
            ], 'resetPassword');
        }

        $record = DB::table($table)->where('email', $email)->first();

        if (! $record || ! is_string($record->token) || ! Hash::check($code, $record->token)) {
            RateLimiter::hit($rateLimitKey, 900);

            return back()->withErrors([
                'code' => 'Код не подошёл. Проверьте письмо и попробуйте ещё раз.',
            ], 'resetPassword');
        }

        $createdAt = $record->created_at ? Carbon::parse((string) $record->created_at) : null;

        if (! $createdAt || $createdAt->addMinutes($expiresInMinutes)->isPast()) {
            DB::table($table)->where('email', $email)->delete();

            return back()->withErrors([
                'code' => 'Срок действия кода истёк. Запросите новый код.',
            ], 'resetPassword');
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            DB::table($table)->where('email', $email)->delete();
            RateLimiter::hit($rateLimitKey, 900);

            return back()->withErrors([
                'code' => 'Код не подошёл. Проверьте письмо и попробуйте ещё раз.',
            ], 'resetPassword');
        }

        $user->forceFill([
            'password' => $request->string('password')->toString(),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table($table)->where('email', $email)->delete();
        RateLimiter::clear($rateLimitKey);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return back()->with('success', 'Пароль обновлён. Вы уже вошли в аккаунт.');
    }
}
