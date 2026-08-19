<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Chrome拡張機能からのログイン専用エンドポイント。
 * メール/パスワードを受け取り、Sanctumの個人アクセストークンを発行する。
 * SPA(Cookie)認証ではなく、拡張機能に保存する長期トークン方式。
 */
class ExtensionAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($validated)) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが違います。',
            ]);
        }

        $user = Auth::user();

        // 既存の拡張機能用トークンがあれば一旦破棄してから新規発行(多重ログイン防止)
        $user->tokens()->where('name', 'chrome-extension')->delete();

        $token = $user->createToken('chrome-extension', [
            'credentials:read',
            'credentials:write',
        ])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }
}
