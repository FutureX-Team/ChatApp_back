<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Tweet;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * GET /users/{id}
     * عرض بروفايل مستخدم (عام)
     */
    public function show($id)
    {
        $user = User::select('id', 'username', 'email', 'avatar_url', 'dark_mode', 'is_disabled', 'created_at')
            ->findOrFail($id);

        // عدد تغريدات المستخدم
        $tweets_count = Tweet::where('user_id', $user->id)->count();

        return response()->json([
            'user' => $user,
            'stats' => [
                'tweets_count' => $tweets_count,
            ],
        ]);
    }

    /**
     * GET /users/{id}/tweets
     * تغريدات مستخدم محدد (عام)
     */
    public function tweets($id)
    {
        // تأكد أن المستخدم موجود
        User::findOrFail($id);

        $tweets = Tweet::with('user:id,username,avatar_url')
            ->where('user_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($tweets);
    }

    /**
     * GET /me
     * معلوماتي (يتطلب auth:sanctum)
     */
    // public function me(Request $request)
    // {
    //     $me = $request->user()->only([
    //         'id', 'username', 'email', 'avatar_url', 'dark_mode', 'is_disabled', 'role', 'created_at',
    //     ]);

    //     // عدد تغريداتي
    //     $me['tweets_count'] = Tweet::where('user_id', $request->user()->id)->count();

    //     return response()->json($me);
    // }
    public function me(Request $request)
    {
        // log incoming request user id
        Log::info('Me endpoint called for user ID: ' . $request->user()->id);

        $me = $request->user()->only([
            'id', 'username', 'email', 'avatar_url', 'dark_mode', 'is_disabled', 'role', 'created_at',
        ]);
        Log::info('User basic info fetched', $me);

        // عدد تغريداتي
        $me['tweets_count'] = Tweet::where('user_id', $request->user()->id)->count();
        Log::info('Tweets count calculated for user ID: ' . $request->user()->id, [
            'tweets_count' => $me['tweets_count']
        ]);

        Log::info('Returning profile response for user ID: ' . $request->user()->id);

        return response()->json($me);
    }


    /**
     * PUT /me
     * تحديث معلوماتي (username/email/avatar_url/dark_mode/password) - يتطلب auth:sanctum
     */
    public function updateMe(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'username'   => ['sometimes','string','max:50', Rule::unique('users','username')->ignore($user->id)],
            'email'      => ['sometimes','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'avatar_url' => ['nullable','string','max:255'],
            'dark_mode'  => ['sometimes','boolean'],
            // إن حاب تضيف تأكيد كلمة المرور: أرسل password_confirmation مع الطلب وأضف 'confirmed'
            'password'   => ['sometimes','string','min:8'],
        ]);

        if ($request->filled('username'))   $user->username   = $request->username;
        if ($request->filled('email'))      $user->email      = $request->email;
        if ($request->has('avatar_url'))    $user->avatar_url = $request->avatar_url; // يسمح بالقيمة null
        if ($request->has('dark_mode'))     $user->dark_mode  = (bool) $request->dark_mode;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $user->only(['id','username','email','avatar_url','dark_mode','is_disabled','role','created_at']),
        ]);
    }
}
