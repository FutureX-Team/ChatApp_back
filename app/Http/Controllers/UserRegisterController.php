<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserRegisterController extends Controller
{
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
}
