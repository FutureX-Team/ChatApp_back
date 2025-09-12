<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\{Hash, Log, RateLimiter};
use Illuminate\Support\Str;

class UserAuthController extends Controller
{
    // POST /api/register
    public function register(Request $request)
    {
        $start = microtime(true);

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

        // NEW: تطبيع
        $email    = mb_strtolower(trim($data['email']));
        $username = trim($data['username']);

        $user = new User();
        $user->username      = $username;
        $user->email         = $email;
        $user->password_hash = Hash::make($data['password']);
        $user->save();

        Log::info('AUTH.REGISTER user_created', ['user_id' => $user->id]);

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
    // POST /api/login
    public function login(Request $request)
    {
        $start = microtime(true);

        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // NEW: تطبيع الإيميل + Rate Limiter
        $email = mb_strtolower(trim($data['email']));
        $ip    = $request->ip();
        $key   = 'login:' . sha1($email . '|' . $ip);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $secs = RateLimiter::availableIn($key);
            Log::warning('AUTH.LOGIN locked_out', ['email' => $email, 'ip' => $ip, 'retry_after' => $secs]);
            return response()->json(['message' => 'Too many attempts. Try again later.', 'retry_after' => $secs], 429);
        }

        Log::info('AUTH.LOGIN start', [
            'ip'         => $ip,
            'user_agent' => $request->userAgent(),
            'email'      => $email,
        ]);

        $user = User::where('email', $email)->first();
        if (!$user) {
            RateLimiter::hit($key, 60);
            Log::warning('AUTH.LOGIN user_not_found', ['email' => $email]);
            return response()->json(['message' => 'User not found'], 404);
        }

        // NEW: فحص تعليق الحساب (لن يكسر لو العمود غير موجود)
        if (isset($user->is_suspended) && $user->is_suspended) {
            Log::warning('AUTH.LOGIN suspended', ['user_id' => $user->id]);
            return response()->json(['message' => 'Account suspended'], 423);
        }

        if (!Hash::check($data['password'], $user->password_hash)) {
            RateLimiter::hit($key, 60);
            Log::warning('AUTH.LOGIN wrong_password', ['user_id' => $user->id]);
            return response()->json(['message' => 'Wrong password'], 401);
        }

        RateLimiter::clear($key);

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

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Not authenticated'], 401);
        }

        $token = $user->currentAccessToken();
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
