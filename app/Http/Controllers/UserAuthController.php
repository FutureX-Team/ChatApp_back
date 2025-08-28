<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserAuthController extends Controller
{

    // register
    public function register(Request $request)
    {
        // 422 يُرجع تلقائيًا ValidationException ما تحتاج try/catch
        $data = $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'email'    => 'required|string|email:rfc,dns|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            // لا نستقبل role من العميل لمنع التصعيد
        ]);

        $user = new User();
        $user->username      = $data['username'];
        $user->email         = $data['email'];
        $user->password_hash = Hash::make($data['password']);
        
        $user->save();

        // لو تستخدم Sanctum (كوكيز)، دخول تلقائي بعد التسجيل
        
        $token = $user->createToken('auth_token')->plainTextToken;  
        return response()->json([
            'token'  => $token,
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
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        if (!Hash::check($data['password'], $user->password_hash)) {
            return response()->json(['message' => 'Wrong password'], 401);
        }
        // إنشاء توكن جديد
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'message' => 'Logged in',
            'user' => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => $user->role,
            ],
        ]);
    }

    // POST /api/logout (No benefit without session) (ما في فايدة بدون جلسة)
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    
}
