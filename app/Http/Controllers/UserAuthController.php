<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserAuthController extends Controller
{
    // POST /api/register
    public function register(Request $request)
    {
        $start = microtime(true);

        // لا تسجّل كلمة المرور أبداً
        Log::info('AUTH.REGISTER start', [
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'username'   => $request->input('username'),
            'email'      => $request->input('email'),
        ]);

        $data = $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'email'    => 'required|string|email:rfc,dns|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = new User();
        $user->username      = $data['username'];
        $user->email         = $data['email'];
        $user->password_hash = Hash::make($data['password']);
        $user->save();

        Log::info('AUTH.REGISTER user_created', ['user_id' => $user->id]);

        // أنشئ توكن (لا تسجّل قيمته في اللوق)
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('AUTH.REGISTER success', [
            'user_id'     => $user->id,
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json([
            'token'   => $token,
            'message' => 'User registered successfully',
            'user'    => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
            ],
        ], 201);
    }

    // POST /api/login
    public function login(Request $request)
    {
        $start = microtime(true);

        Log::info('AUTH.LOGIN start', [
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'email'      => $request->input('email'),
        ]);

        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            Log::warning('AUTH.LOGIN user_not_found', ['email' => $data['email']]);
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!Hash::check($data['password'], $user->password_hash)) {
            Log::warning('AUTH.LOGIN wrong_password', ['user_id' => $user->id]);
            return response()->json(['message' => 'Wrong password'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('AUTH.LOGIN success', [
            'user_id'     => $user->id,
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json([
            'token'   => $token,
            'message' => 'Logged in',
            'user'    => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => $user->role,
            ],
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        $start = microtime(true);
        $uid   = optional($request->user())->id;

        Log::info('AUTH.LOGOUT start', [
            'user_id'    => $uid,
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // تحقّق من وجود توكن حالي قبل الحذف
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
            Log::info('AUTH.LOGOUT token_revoked', ['user_id' => $uid]);
        } else {
            Log::warning('AUTH.LOGOUT no_current_token', ['user_id' => $uid]);
        }

        Log::info('AUTH.LOGOUT success', [
            'user_id'     => $uid,
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json(['message' => 'Logged out successfully']);
    }
}
