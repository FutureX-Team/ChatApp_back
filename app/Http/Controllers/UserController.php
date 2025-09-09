<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Tweet;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    /**
     * GET /users/{id}
     * عرض بروفايل مستخدم (عام)
     */
    public function show($id)
    {
        $start = microtime(true);
        Log::info('USERS.SHOW: start', ['target_user_id' => $id]);

        try {
            $user = User::select('id', 'username', 'email', 'avatar_url', 'dark_mode', 'is_disabled', 'created_at')
                ->findOrFail($id); // 404 تلقائيًا إذا غير موجود

            $tweets_count = Tweet::where('user_id', $user->id)->count();

            Log::info('USERS.SHOW: success', [
                'target_user_id' => $id,
                'tweets_count'   => $tweets_count,
                'duration_ms'    => round((microtime(true) - $start) * 1000, 2),
            ]);

            return response()->json([
                'user'  => $user,
                'stats' => ['tweets_count' => $tweets_count],
            ]);
        } catch (ModelNotFoundException $e) {
            Log::warning('USERS.SHOW: not_found', [
                'target_user_id' => $id,
                'duration_ms'    => round((microtime(true) - $start) * 1000, 2),
            ]);
            throw $e; // يترك Laravel يرجّع 404
        } catch (\Throwable $e) {
            Log::error('USERS.SHOW: error', [
                'target_user_id' => $id,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * GET /users/{id}/tweets
     * تغريدات مستخدم محدد (عام)
     */
    public function tweets($id)
    {
        $start = microtime(true);
        Log::info('USERS.TWEETS: start', ['target_user_id' => $id]);

        try {
            User::findOrFail($id);

            $tweets = Tweet::with('user:id,username,avatar_url')
                ->where('user_id', $id)
                ->orderByDesc('created_at')
                ->get();

            Log::info('USERS.TWEETS: success', [
                'target_user_id' => $id,
                'count'          => $tweets->count(),
                'duration_ms'    => round((microtime(true) - $start) * 1000, 2),
            ]);

            return response()->json($tweets);
        } catch (ModelNotFoundException $e) {
            Log::warning('USERS.TWEETS: user_not_found', [
                'target_user_id' => $id,
                'duration_ms'    => round((microtime(true) - $start) * 1000, 2),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('USERS.TWEETS: error', [
                'target_user_id' => $id,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * GET /me
     * معلوماتي (يتطلب auth:sanctum)
     */
    public function me(Request $request)
    {
        $start = microtime(true);
        $uid = optional($request->user())->id;
        Log::info('ME.SHOW: start', ['user_id' => $uid]);

        if (!$uid) {
            Log::warning('ME.SHOW: unauthenticated');
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $me = $request->user()->only([
            'id',
            'username',
            'email',
            'avatar_url',
            'dark_mode',
            'is_disabled',
            'role',
            'created_at',
        ]);
        Log::info('ME.SHOW: basic_loaded', ['user_id' => $uid]);

        $me['tweets_count'] = Tweet::where('user_id', $uid)->count();

        Log::info('ME.SHOW: success', [
            'user_id'     => $uid,
            'tweets_count' => $me['tweets_count'],
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json($me);
    }

    /**
     * PUT /me
     * تحديث معلوماتي
     */
    public function updateMe(Request $request)
    {
        $start = microtime(true);
        $uid = optional($request->user())->id;
        Log::info('ME.UPDATE: start', ['user_id' => $uid]);

        $user = $request->user();

        // لا نسجّل القيم نفسها؛ فقط المفاتيح لتجنّب حسّاسات
        Log::info('ME.UPDATE: validating', [
            'user_id' => $uid,
            'keys'    => array_keys($request->all()),
        ]);

        $request->validate([
            'username'   => ['sometimes', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email'      => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar_url' => ['nullable', 'string', 'max:500'],
            'dark_mode'  => ['sometimes', 'boolean'],
            'password'   => ['sometimes', 'string', 'min:8'],
        ]);

        $changed = [];

        if ($request->filled('username')) {
            $changed['username'] = $request->username;
            $user->username = $request->username;
        }
        if ($request->filled('email')) {
            $changed['email']    = $request->email;
            $user->email    = $request->email;
        }
        if ($request->has('avatar_url')) {
            $changed['avatar_url'] = true;
            $user->avatar_url = $request->avatar_url;
        } // يسجّل وجود التغيير فقط
        if ($request->has('dark_mode')) {
            $changed['dark_mode'] = (bool)$request->dark_mode;
            $user->dark_mode = (bool)$request->dark_mode;
        }

        if ($request->filled('password')) {
            $changed['password'] = '***';
            $user->password_hash = bcrypt($request->password);
        }

        try {
            $user->save();

            Log::info('ME.UPDATE: success', [
                'user_id'     => $uid,
                'changed'     => array_keys($changed),
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            ]);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user'    => $user->only(['id', 'username', 'email', 'avatar_url', 'dark_mode', 'is_disabled', 'role', 'created_at']),
            ]);
        } catch (\Throwable $e) {
            Log::error('ME.UPDATE: error', [
                'user_id' => $uid,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
