<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GuestAuthController extends Controller
{
    public function ensure(\Illuminate\Http\Request $request)
    {
        $deviceId = $request->cookie('gid') ?: (string) \Illuminate\Support\Str::uuid();
        $guest = \App\Models\Guest::firstOrCreate(['device_id' => $deviceId], [
            'nickname' => 'guest_' . uniqid(),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
        $guest->tokens()->where('name', 'guest')->delete();
        $token = $guest->createToken('guest')->plainTextToken;

        return response()->json([
            'guest' => ['id' => $guest->id, 'nickname' => $guest->nickname],
            'token' => $token,
        ])->cookie('gid', $deviceId, 60 * 24 * 365, '/', null, false, true, false, 'Lax');
    }

}
