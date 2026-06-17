<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as Middleware;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;

class FilamentAuthenticate extends Middleware
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return;
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();

        $panel = Filament::getCurrentOrDefaultPanel();

        if ($user instanceof FilamentUser && ! $user->canAccessPanel($panel)) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw new AuthenticationException(
                'Войдите под учётной записью администратора.',
                $guards,
                $this->redirectTo($request),
            );
        }

        abort_if(
            ! ($user instanceof FilamentUser) && config('app.env') !== 'local',
            403,
        );
    }
}
