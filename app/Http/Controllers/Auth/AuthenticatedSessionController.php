<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;

class AuthenticatedSessionController extends Controller
{
    public function create(): RedirectResponse
    {
        return to_route('home', ['auth' => 'login']);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return back()->with('success', 'Вы вошли в аккаунт.');
    }

    public function destroy(): RedirectResponse
    {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return to_route('home')->with('success', 'Вы вышли из аккаунта.');
    }
}
