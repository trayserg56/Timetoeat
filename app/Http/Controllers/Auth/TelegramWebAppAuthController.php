<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TelegramWebAppAuthRequest;
use App\Services\TelegramWebAppAuthService;
use Illuminate\Http\JsonResponse;

class TelegramWebAppAuthController extends Controller
{
    public function store(TelegramWebAppAuthRequest $request, TelegramWebAppAuthService $authService): JsonResponse
    {
        $user = $authService->authenticate($request->validated('init_data'));

        if ($user === null) {
            return response()->json([
                'message' => 'Не удалось подтвердить данные Telegram.',
            ], 422);
        }

        $request->session()->regenerate();

        return response()->json([
            'ok' => true,
        ]);
    }
}
