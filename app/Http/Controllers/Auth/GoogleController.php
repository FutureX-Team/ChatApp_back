<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid','email','profile'])
            ->stateless()
            ->redirect();
    }

    public function callback()
    {
        try {
            $g = Socialite::driver('google')->stateless()->user();

            $email = $g->getEmail();
            $front = rtrim(config('app.frontend_url', env('FRONTEND_URL', '/')), '/');

            if (! $email) {
                return redirect($front.'/login?error=no-email');
            }

            $user = User::where('google_id', $g->getId())->first()
                ?? User::where('email', $email)->first();

            if (! $user) {
                $base = Str::slug(Str::before($email, '@')) ?: 'user';
                $username = $base;
                for ($i = 1; User::where('username', $username)->exists(); $i++) {
                    $username = "{$base}-{$i}";
                }

                $user = User::create([
                    'username'   => $username,
                    'email'      => $email,
                    'name'       => $g->getName() ?: $username,
                    'google_id'  => $g->getId(),
                    'avatar_url' => $g->getAvatar(),
                    'password'   => Hash::make(Str::random(32)), // do NOT store null unless column is nullable
                ]);
            } else {
                $user->update([
                    'google_id'  => $g->getId(),
                    'avatar_url' => $g->getAvatar(),
                ]);
            }

            // Mint a token for your SPA
            $token = $user->createToken('web')->plainTextToken;
            return redirect($front.'/auth/callback?token='.$token.'&ok=1');

        } catch (\Throwable $th) {
            report($th);
            $front = rtrim(config('app.frontend_url', env('FRONTEND_URL', '/')), '/');
            return redirect($front.'/login?error=google');
        }
    }
}
